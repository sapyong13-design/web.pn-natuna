#!/usr/bin/env python3
"""Generate reversible local cleanup SQL for imported news duplicates."""
import argparse
import hashlib
from difflib import SequenceMatcher
from html import escape, unescape
from html.parser import HTMLParser
import json
from pathlib import Path
import re


class ParagraphParser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.depth = 0
        self.skip = 0
        self.parts = []
        self.paragraphs = []

    def handle_starttag(self, tag, attrs):
        if tag in ("script", "style", "iframe", "object", "svg"):
            self.skip += 1
        if tag == "p" and not self.skip:
            self.depth += 1
            if self.depth == 1:
                self.parts = []

    def handle_endtag(self, tag):
        if tag in ("script", "style", "iframe", "object", "svg") and self.skip:
            self.skip -= 1
            return
        if tag == "p" and self.depth and not self.skip:
            self.depth -= 1
            if self.depth == 0:
                text = " ".join("".join(self.parts).split())
                if text:
                    self.paragraphs.append(text)

    def handle_data(self, data):
        if self.depth and not self.skip:
            self.parts.append(data)


def clean_legacy_text(value):
    replacements = {"┬á": " ", "ÔÇ£": "“", "ÔÇØ": "”", "ÔÇÖ": "’"}
    value = unescape(value).replace("\xa0", " ")
    for broken, repaired in replacements.items():
        value = value.replace(broken, repaired)
    return " ".join(value.split()).strip()


def extract_excerpt(html):
    parser = ParagraphParser()
    parser.feed(str(html or ""))
    paragraphs = [clean_legacy_text(text) for text in parser.paragraphs]
    paragraphs = [text for text in paragraphs if text]
    if not paragraphs:
        return ""
    excerpt = paragraphs[0]
    index = 1
    while len(excerpt) < 80 and index < len(paragraphs):
        excerpt = clean_legacy_text(excerpt + " " + paragraphs[index])
        index += 1
    return excerpt


def meaningful_length(row):
    return len(" ".join(re.sub(r"<[^>]+>", " ", str(row.get("fulltext", ""))).split()))

def event_alias(alias):
    value = str(alias).removeprefix("legacy-")
    value = re.sub(r"(?:^|-)2026(?:-|$)", "-", value)
    return value.strip("-")


def same_event(left, right):
    left_date = str(left.get("publish_up") or "")[:10]
    right_date = str(right.get("publish_up") or "")[:10]
    if not left_date or left_date != right_date:
        return False
    if str(left.get("alias", "")).startswith("legacy-") == str(right.get("alias", "")).startswith("legacy-"):
        return False
    return SequenceMatcher(None, event_alias(left.get("alias")), event_alias(right.get("alias"))).ratio() >= 0.65



def choose_winner(rows):
    def key(row):
        non_legacy = not str(row.get("alias", "")).startswith("legacy-")
        published = str(row.get("publish_up") or "")
        return (non_legacy, published, meaningful_length(row), -int(row["id"]))
    return max(rows, key=key)


def local_image_hash(row, root):
    relative = str(row.get("image") or "").strip().lstrip("/").replace("\\", "/")
    if not relative.startswith("images/") or ".." in Path(relative).parts:
        return ""
    path = root / relative
    return hashlib.sha256(path.read_bytes()).hexdigest() if path.is_file() else ""


def build_cleanup(rows, root):
    groups = {}
    for row in rows:
        digest = local_image_hash(row, root)
        if digest:
            groups.setdefault(digest, []).append(row)
    losers = []
    winners = {}
    for group in groups.values():
        if len(group) < 2:
            continue
        winner = choose_winner(group)
        for row in group:
            if int(row["id"]) != int(winner["id"]):
                losers.append(int(row["id"]))
                winners[int(row["id"])] = int(winner["id"])
    for index, left in enumerate(rows):
        for right in rows[index + 1:]:
            if not same_event(left, right):
                continue
            winner = choose_winner([left, right])
            loser = right if int(winner["id"]) == int(left["id"]) else left
            loser_id = int(loser["id"])
            if loser_id not in losers:
                losers.append(loser_id)
                winners[loser_id] = int(winner["id"])
    loser_set = set(losers)
    excerpts = {}
    for row in rows:
        article_id = int(row["id"])
        if article_id in loser_set or not str(row.get("alias", "")).startswith("legacy-") or str(row.get("introtext", "")).strip():
            continue
        excerpt = extract_excerpt(row.get("fulltext", ""))
        if excerpt:
            excerpts[article_id] = excerpt
    return {"losers": sorted(losers), "winners": dict(sorted(winners.items())), "excerpts": dict(sorted(excerpts.items()))}


def sql_quote(value):
    text = str(value)
    return "''" if text == "" else "CONVERT(0x{} USING utf8mb4)".format(text.encode("utf-8").hex())


def render_sql(losers, excerpts):
    lines = ["-- Reversible duplicate-news cleanup. Losers move to Joomla trash."]
    for article_id in sorted(losers):
        lines.append("UPDATE #__content SET state = -2, modified = NOW() WHERE id = {} AND catid = 12 AND state = 1;".format(int(article_id)))
    for article_id, excerpt in sorted(excerpts.items()):
        html = "<p>{}</p>".format(escape(excerpt))
        lines.append("UPDATE #__content SET introtext = {}, modified = NOW() WHERE id = {} AND catid = 12 AND state = 1 AND TRIM(introtext) = '';".format(sql_quote(html), int(article_id)))
    return "\n".join(lines) + "\n"


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", type=Path, required=True)
    parser.add_argument("--root", type=Path, required=True)
    parser.add_argument("--sql", type=Path, required=True)
    parser.add_argument("--report", type=Path, required=True)
    args = parser.parse_args()
    rows = json.loads(args.input.read_text(encoding="utf-8"))
    result = build_cleanup(rows, args.root)
    args.sql.parent.mkdir(parents=True, exist_ok=True)
    args.sql.write_text(render_sql(result["losers"], result["excerpts"]), encoding="utf-8")
    args.report.write_text(json.dumps(result, indent=2, sort_keys=True) + "\n", encoding="utf-8")
    print("News cleanup: {} duplicates trashed, {} excerpts repaired".format(len(result["losers"]), len(result["excerpts"])))


if __name__ == "__main__":
    main()
