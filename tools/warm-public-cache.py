#!/usr/bin/env python3
"""Warm public LiteSpeed cache without sending concurrent PHP requests."""
import argparse
import os
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET

DEFAULT_BASE_URL = "https://pn-natuna.go.id"
USER_AGENT = "lscache_runner"

def parse_args():
    parser = argparse.ArgumentParser()
    parser.add_argument("--base-url", default=os.environ.get("PN_NATUNA_PUBLIC_URL", DEFAULT_BASE_URL))
    parser.add_argument("--sitemap", default=None)
    parser.add_argument("--homepage-only", action="store_true")
    parser.add_argument("--passes", type=int, default=2)
    parser.add_argument("--delay", type=float, default=0.15)
    parser.add_argument("--timeout", type=float, default=45.0)
    return parser.parse_args()


def normalize_base_url(value):
    parsed = urllib.parse.urlsplit(value.rstrip("/"))
    if parsed.scheme not in ("http", "https") or not parsed.hostname:
        raise ValueError("PN_NATUNA_PUBLIC_URL harus berupa URL HTTP(S) absolut")
    if parsed.username or parsed.password or parsed.query or parsed.fragment:
        raise ValueError("PN_NATUNA_PUBLIC_URL tidak boleh memuat kredensial, query, atau fragment")
    return urllib.parse.urlunsplit((parsed.scheme, parsed.netloc, "", "", ""))


def sitemap_urls(path, base_url):
    root = ET.parse(path).getroot()
    urls = []
    expected = urllib.parse.urlsplit(base_url)
    for node in root.findall("{http://www.sitemaps.org/schemas/sitemap/0.9}url"):
        loc = node.findtext("{http://www.sitemaps.org/schemas/sitemap/0.9}loc", "").strip()
        parsed = urllib.parse.urlsplit(loc)
        if parsed.scheme not in ("http", "https") or parsed.hostname != expected.hostname:
            continue
        path_and_query = urllib.parse.urlunsplit(("", "", parsed.path or "/", parsed.query, ""))
        urls.append(urllib.parse.urljoin(base_url + "/", path_and_query.lstrip("/")))
    homepage = base_url + "/"
    return [homepage] + [url for url in urls if url != homepage]


def fetch(url, timeout):
    request = urllib.request.Request(url, headers={"User-Agent": USER_AGENT, "Accept-Encoding": "identity"})
    started = time.monotonic()
    with urllib.request.urlopen(request, timeout=timeout) as response:
        response.read(1)
        elapsed = int((time.monotonic() - started) * 1000)
        return response.status, (response.headers.get("X-LiteSpeed-Cache") or "none").lower(), elapsed


def main():
    args = parse_args()
    if args.passes < 1 or args.passes > 3 or args.delay < 0 or args.timeout <= 0:
        print("Parameter warmer tidak valid.", file=sys.stderr)
        return 2
    try:
        base_url = normalize_base_url(args.base_url)
        sitemap = args.sitemap or os.path.join(os.environ.get("PN_NATUNA_JPATH_ROOT", os.getcwd()), "sitemap.xml")
        urls = [base_url + "/"] if args.homepage_only else sitemap_urls(sitemap, base_url)
    except (ValueError, OSError, ET.ParseError) as error:
        print("Cache warmer gagal membaca konfigurasi: {}".format(error), file=sys.stderr)
        return 2

    failures = []
    for pass_number in range(1, args.passes + 1):
        counts = {"hit": 0, "miss": 0, "none": 0}
        for index, url in enumerate(urls):
            try:
                status, cache, elapsed = fetch(url, args.timeout)
                if status != 200:
                    raise RuntimeError("HTTP {}".format(status))
                counts[cache if cache in counts else "none"] += 1
                print("pass={} {}/{} cache={} ms={} {}".format(pass_number, index + 1, len(urls), cache, elapsed, url))
            except (urllib.error.URLError, urllib.error.HTTPError, RuntimeError, TimeoutError) as error:
                failures.append((url, str(error)))
                print("FAIL pass={} {} {}".format(pass_number, url, error), file=sys.stderr)
            if args.delay and index + 1 < len(urls):
                time.sleep(args.delay)
        print("Cache warmer pass {}: {} URL; hit={}, miss={}, tanpa-header={}".format(pass_number, len(urls), counts["hit"], counts["miss"], counts["none"]))

    if failures:
        print("Cache warmer selesai dengan {} kegagalan.".format(len(failures)), file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
