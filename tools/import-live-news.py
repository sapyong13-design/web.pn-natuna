#!/usr/bin/env python3
"""Import article bodies from compromised legacy PN Natuna shell safely."""
import argparse
from datetime import datetime, timezone
from html import escape
from html.parser import HTMLParser
import hashlib
import json
import http.client
from pathlib import Path
import re
import unicodedata
from urllib.parse import quote, urljoin, urlparse
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

HOST = "www.pn-natuna.go.id"
import time
BASE = "https://" + HOST
ALLOWED_TAGS = {"p", "strong", "b", "em", "i", "ul", "ol", "li", "blockquote", "h2", "h3", "h4", "br", "a", "img", "figure", "figcaption", "table", "thead", "tbody", "tr", "th", "td"}
VOID_TAGS = {"br", "img"}
MAX_HTML = 5 * 1024 * 1024
MAX_IMAGE = 12 * 1024 * 1024
CACHE_DIR = Path(__file__).resolve().parents[1] / "tmp/live-news-cache"


def cache_paths(url):
    key = hashlib.sha256(url.encode("utf-8")).hexdigest()
    return CACHE_DIR / (key + ".bin"), CACHE_DIR / (key + ".json")


def validate_source_url(url):
    parsed = urlparse(url)
    if parsed.scheme != "https" or parsed.hostname != HOST or parsed.username or parsed.password:
        raise ValueError("unsafe source URL: " + url)
    return url


def slugify(value):
    value = unicodedata.normalize("NFKD", value).encode("ascii", "ignore").decode("ascii").lower()
    value = re.sub(r"[^a-z0-9]+", "-", value).strip("-")
    return value or "artikel"


class ListingParser(HTMLParser):
    def __init__(self, base):
        HTMLParser.__init__(self, convert_charrefs=True)
        self.base = base
        self.links = []
        self.pages = []

    def handle_starttag(self, tag, attrs):
        if tag != "a":
            return
        href = dict(attrs).get("href", "")
        absolute = urljoin(self.base, href)
        parsed = urlparse(absolute)
        if parsed.hostname != HOST or parsed.scheme != "https":
            return
        path = parsed.path.rstrip("/")
        base_path = urlparse(self.base).path.rstrip("/")
        if path.startswith(base_path + "/") and "?" not in absolute and "#" not in absolute:
            self.links.append(absolute)
        if parsed.path == urlparse(self.base).path and "start=" in parsed.query:
            self.pages.append(absolute)


class DetailParser(HTMLParser):
    def __init__(self, source_url):
        HTMLParser.__init__(self, convert_charrefs=True)
        self.source_url = source_url
        self.title = ""
        self.date = ""
        self.in_title = False
        self.in_body = False
        self.body_depth = 0
        self.skip_depth = 0
        self.output = []
        self.images = []

    def handle_starttag(self, tag, attrs):
        values = dict(attrs)
        if tag == "meta" and values.get("property") == "datePublished":
            self.date = values.get("content", "")
        if tag in ("h1", "h2") and values.get("itemprop") == "headline":
            self.in_title = True
        if tag == "div" and values.get("property") == "text":
            self.in_body = True
            self.body_depth = 1
            return
        if not self.in_body:
            return
        if tag == "div":
            self.body_depth += 1
        if tag in ("script", "iframe", "object", "embed", "form", "style", "svg"):
            self.skip_depth += 1
            return
        if self.skip_depth or tag not in ALLOWED_TAGS:
            return
        clean = []
        if tag == "a":
            href = values.get("href", "")
            absolute = urljoin(self.source_url, href)
            parsed = urlparse(absolute)
            if parsed.scheme in ("http", "https"):
                clean.extend([("href", absolute), ("rel", "noopener noreferrer")])
        elif tag == "img":
            src = values.get("src", "")
            absolute = quote(urljoin(self.source_url, src), safe=":/?&=%#")
            try:
                validate_source_url(absolute)
            except ValueError:
                return
            self.images.append(absolute)
            clean.extend([("src", absolute), ("alt", values.get("alt", "")), ("loading", "lazy")])
        elif tag in ("td", "th") and values.get("colspan", "").isdigit():
            clean.append(("colspan", values["colspan"]))
        attrs_html = "".join(" {}=\"{}\"".format(k, escape(v, quote=True)) for k, v in clean)
        self.output.append("<{}{}>".format(tag, attrs_html))

    def handle_startendtag(self, tag, attrs):
        self.handle_starttag(tag, attrs)

    def handle_endtag(self, tag):
        if self.in_title and tag in ("h1", "h2"):
            self.in_title = False
        if not self.in_body:
            return
        if self.skip_depth:
            if tag in ("script", "iframe", "object", "embed", "form", "style", "svg"):
                self.skip_depth -= 1
            return
        if tag == "div":
            self.body_depth -= 1
            if self.body_depth == 0:
                self.in_body = False
            return
        if tag in ALLOWED_TAGS and tag not in VOID_TAGS:
            self.output.append("</{}>".format(tag))

    def handle_data(self, data):
        if self.in_title:
            self.title += data
        if self.in_body and not self.skip_depth:
            self.output.append(escape(data))


def parse_listing(html, base):
    parser = ListingParser(base)
    parser.feed(html)
    return sorted(set(parser.links)), sorted(set(parser.pages))


def parse_date(value):
    value = value.strip()
    if not value:
        return "2000-01-01 00:00:00"
    try:
        parsed = datetime.fromisoformat(value.replace("Z", "+00:00"))
        if parsed.tzinfo:
            parsed = parsed.astimezone(timezone.utc).replace(tzinfo=None)
        return parsed.strftime("%Y-%m-%d %H:%M:%S")
    except ValueError:
        raise ValueError("invalid publication date: " + value)


def parse_detail(html, source_url, category):
    validate_source_url(source_url)
    parser = DetailParser(source_url)
    parser.feed(html)
    title = " ".join(parser.title.split())
    if not title or not parser.output:
        raise ValueError("article body/title missing: " + source_url)
    return {"source_url": source_url, "category": category, "title": title, "alias": "legacy-" + slugify(urlparse(source_url).path.rsplit("/", 1)[-1] or title), "published": parse_date(parser.date), "full_html": "".join(parser.output).strip(), "images": sorted(set(parser.images))}


def fetch(url, limit=MAX_HTML):
    validate_source_url(url)
    data_path, metadata_path = cache_paths(url)
    if data_path.is_file() and metadata_path.is_file():
        data = data_path.read_bytes()
        if len(data) > limit:
            raise ValueError("cached response too large: " + url)
        metadata = json.loads(metadata_path.read_text(encoding="utf-8"))
        return data, metadata["content_type"]
    request = Request(url, headers={"User-Agent": "Mozilla/5.0 PN-Natuna-Public-Archive-Importer/1.0", "Accept": "text/html,application/xhtml+xml,image/avif,image/webp,image/png,image/jpeg"})
    last_error = None
    for attempt in range(4):
        try:
            with urlopen(request, timeout=30) as response:
                if response.geturl() != url:
                    validate_source_url(response.geturl())
                data = response.read(limit + 1)
                if len(data) > limit:
                    raise ValueError("response too large: " + url)
                content_type = response.headers.get_content_type()
                CACHE_DIR.mkdir(parents=True, exist_ok=True)
                data_path.write_bytes(data)
                metadata_path.write_text(json.dumps({"content_type": content_type, "url": url}, sort_keys=True) + "\n", encoding="utf-8")
                return data, content_type
        except HTTPError as error:
            if error.code < 500:
                raise
            last_error = error
        except URLError as error:
            if not isinstance(error.reason, (http.client.RemoteDisconnected, TimeoutError, ConnectionResetError, OSError)):
                raise
            last_error = error
        except (http.client.RemoteDisconnected, TimeoutError, ConnectionResetError) as error:
            last_error = error
        if attempt < 3:
            time.sleep(1.5 * (attempt + 1))
    raise RuntimeError("source fetch failed after retries: {}: {}".format(url, last_error))


def crawl_listing(start_url, category):
    queue = [validate_source_url(start_url)]
    seen_pages = set()
    detail_urls = set()
    while queue:
        page = queue.pop(0)
        if page in seen_pages:
            continue
        seen_pages.add(page)
        data, _ = fetch(page)
        links, pages = parse_listing(data.decode("utf-8", "replace"), start_url)
        detail_urls.update(links)
        queue.extend(p for p in pages if p not in seen_pages)
    records = []
    for url in sorted(detail_urls):
        data, _ = fetch(url)
        records.append(parse_detail(data.decode("utf-8", "replace"), url, category))
    return records, len(seen_pages)


def download_image(url, output_dir):
    try:
        data, content_type = fetch(url, MAX_IMAGE)
    except HTTPError as error:
        if error.code in (404, 410):
            return None
        raise
    signatures = [(b"\xff\xd8\xff", ".jpg", "image/jpeg"), (b"\x89PNG\r\n\x1a\n", ".png", "image/png"), (b"RIFF", ".webp", "image/webp")]
    extension = None
    for signature, suffix, mime in signatures:
        if data.startswith(signature) and content_type == mime:
            extension = suffix
            break
    if not extension:
        raise ValueError("invalid image: " + url)
    name = hashlib.sha256(data).hexdigest() + extension
    output_dir.mkdir(parents=True, exist_ok=True)
    path = output_dir / name
    if not path.exists():
        path.write_bytes(data)
    return "images/news/imported/" + name


def write_manifest(path, records):
    normalized = sorted(records, key=lambda item: (item["category"], item["source_url"]))
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(normalized, ensure_ascii=False, indent=2, sort_keys=True) + "\n", encoding="utf-8")


def sql_quote(value):
    return "'" + str(value).replace("\\", "\\\\").replace("'", "''") + "'"


def render_sql(records):
    lines = ["-- Generated public legacy content import. Source identity: metadata.\n"]
    for record in sorted(records, key=lambda item: item["source_url"]):
        catid = 12 if record["category"] == "berita" else 13
        metadata = json.dumps({"legacy_source_url": record["source_url"]}, ensure_ascii=False, separators=(",", ":"))
        image = record.get("local_image", "")
        images = json.dumps({"image_intro": image, "image_fulltext": image, "float_intro": "", "float_fulltext": "", "image_intro_alt": record["title"], "image_fulltext_alt": record["title"]}, ensure_ascii=False, separators=(",", ":"))
        lines.append("SET @legacy_id := (SELECT id FROM #__content WHERE JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.legacy_source_url')) = {} LIMIT 1);".format(sql_quote(record["source_url"])))
        lines.append("INSERT INTO #__content (asset_id,title,alias,introtext,`fulltext`,state,catid,created,created_by,created_by_alias,modified,modified_by,images,urls,attribs,version,ordering,metakey,metadesc,access,hits,metadata,featured,language,note,publish_up) SELECT 0,{0},{1},'',{2},1,{6},{3},0,'',{3},0,{4},'','{{}}',1,0,'','',1,0,{5},0,'*','legacy-public-import',{3} WHERE @legacy_id IS NULL;".format(*[sql_quote(v) for v in (record["title"], record["alias"], record["full_html"], record["published"], images, metadata)], catid))
        lines.append("UPDATE #__content SET title={0}, alias={1}, `fulltext`={2}, catid={6}, images={4}, metadata={5}, publish_up={3}, modified=NOW() WHERE id=@legacy_id;".format(*[sql_quote(v) for v in (record["title"], record["alias"], record["full_html"], record["published"], images, metadata)], catid))
    return "\n".join(lines) + "\n"


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--news-url", default=BASE + "/index.php/en/berita/berita-terkini")
    parser.add_argument("--announcement-url", action="append", default=[])
    parser.add_argument("--manifest", type=Path, default=Path(__file__).with_name("live-news-import.json"))
    parser.add_argument("--images", type=Path, default=Path(__file__).resolve().parents[1] / "images/news/imported")
    parser.add_argument("--sql", type=Path, default=Path(__file__).resolve().parents[1] / "database/migrations/20260719_import_live_news.sql")
    args = parser.parse_args()
    records, news_pages = crawl_listing(args.news_url, "berita")
    announcement_pages = 0
    for url in args.announcement_url:
        found, pages = crawl_listing(url, "pengumuman")
        records.extend(found)
        announcement_pages += pages
    for record in records:
        downloaded = [(url, download_image(url, args.images)) for url in record["images"]]
        available = [(url, path) for url, path in downloaded if path]
        record["missing_images"] = [url for url, path in downloaded if not path]
        record["local_images"] = [path for _, path in available]
        record["local_image"] = available[0][1] if available else ""
        for remote, path in available:
            record["full_html"] = record["full_html"].replace(remote, "/" + path)
        for remote in record["missing_images"]:
            encoded = re.escape(escape(remote, quote=True))
            record["full_html"] = re.sub(r'<img\b[^>]*\bsrc="' + encoded + r'"[^>]*>', '', record["full_html"], flags=re.IGNORECASE)
    write_manifest(args.manifest, records)
    args.sql.write_text(render_sql(records), encoding="utf-8")
    print("Imported manifest: {} records, {} news pages, {} announcement pages".format(len(records), news_pages, announcement_pages))


if __name__ == "__main__":
    main()
