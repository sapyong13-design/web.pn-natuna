#!/usr/bin/env python3
"""Clear the Joomla cache, run every contract, then smoke test the rendered pages.

Joomla caches the article view by article id and Itemid, not by full URL, so a
`?cb=<timestamp>` query does not bust it. After editing a template the browser
can therefore keep serving markup produced by the previous edit, and a broken
change looks like it works while a working change looks like it failed. This
runner enforces the only safe order: clean first, then verify.
"""
from __future__ import annotations

import argparse
from pathlib import Path
import re
import shutil
import subprocess
import sys
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
CACHE_HINT = "cli/joomla.php cache:clean"
# Penanda yang wajib ada di setiap artikel Berita/Pengumuman setelah render.
ARTICLE_MARKERS = (
    'class="editorial-article__masthead"',
    'class="editorial-article__body"',
)
# Penanda yang justru menandakan regresi bila muncul kembali.
ARTICLE_ABSENT = (
    "editorial-article__kicker",
    "<b >Fatal error",
    "Fatal error</b>",
    "Uncaught Error",
)
LISTING_PATHS = ("/berita-dan-pengumuman/berita", "/berita-dan-pengumuman/pengumuman")
ARTICLE_LINK = re.compile(r'href="(/(?:berita|pengumuman)/[a-z0-9][a-z0-9\-]{6,})"')


def run(command: list[str], cwd: Path) -> tuple[int, str]:
    process = subprocess.run(command, cwd=cwd, capture_output=True, text=True, encoding="utf-8", errors="replace")
    return process.returncode, (process.stdout or "") + (process.stderr or "")


def clean_cache(php: str) -> None:
    code, output = run([php, "cli/joomla.php", "cache:clean"], ROOT)
    if code != 0:
        raise SystemExit("cache clean failed; refusing to verify against stale markup:\n" + output.strip())
    print("cache cleaned")


def run_contracts(php: str, python: str) -> int:
    failures = 0
    total = 0
    for path in sorted(ROOT.glob("tools/test_*.php")) + sorted(ROOT.glob("tools/test_*.py")):
        runner = php if path.suffix == ".php" else python
        total += 1
        code, output = run([runner, path.relative_to(ROOT).as_posix()], ROOT)
        if code != 0:
            failures += 1
            print("FAIL {}".format(path.name))
            for line in output.strip().splitlines()[-4:]:
                print("     " + line)
    print("contracts: {}/{} passed".format(total - failures, total))
    return failures


def fetch(url: str, timeout: int) -> tuple[int, str]:
    request = urllib.request.Request(url, headers={"User-Agent": "pn-natuna-verify/1.0"})
    try:
        with urllib.request.urlopen(request, timeout=timeout) as response:
            return response.status, response.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as error:
        return error.code, error.read().decode("utf-8", "replace")


def smoke(base_url: str, timeout: int) -> int:
    base = base_url.rstrip("/")
    failures = 0
    articles: list[str] = []
    for path in LISTING_PATHS:
        status, body = fetch(base + path, timeout)
        if status != 200:
            print("FAIL {} returned {}".format(path, status))
            failures += 1
            continue
        found = ARTICLE_LINK.findall(body)
        if not found:
            print("FAIL {} lists no articles".format(path))
            failures += 1
            continue
        articles.append(found[0])
    for path in articles:
        status, body = fetch(base + path, timeout)
        if status != 200:
            print("FAIL {} returned {}".format(path, status))
            failures += 1
            continue
        for marker in ARTICLE_MARKERS:
            if marker not in body:
                print("FAIL {} is missing {}".format(path, marker))
                failures += 1
        for marker in ARTICLE_ABSENT:
            if marker in body:
                print("FAIL {} still renders {}".format(path, marker))
                failures += 1
    print("smoke: {} pages checked, {} problems".format(len(LISTING_PATHS) + len(articles), failures))
    return failures


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--php", default=shutil.which("php") or "php", help="PHP binary used for the cache clean and PHP contracts")
    parser.add_argument("--python", default=sys.executable, help="Python interpreter used for the Python contracts")
    parser.add_argument("--base-url", default="http://localhost:8080", help="running site to smoke test")
    parser.add_argument("--skip-smoke", action="store_true", help="skip the render check when no server is running")
    parser.add_argument("--timeout", type=int, default=20, help="per-request timeout in seconds")
    args = parser.parse_args()

    clean_cache(args.php)
    failures = run_contracts(args.php, args.python)
    if args.skip_smoke:
        print("smoke: skipped")
    else:
        try:
            failures += smoke(args.base_url, args.timeout)
        except OSError as error:
            raise SystemExit("smoke test could not reach {}: {}. Start the site or pass --skip-smoke.".format(args.base_url, error))

    if failures:
        print("verification failed ({} problems). Re-run after fixing; {} runs first every time.".format(failures, CACHE_HINT))
        return 1
    print("verification ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
