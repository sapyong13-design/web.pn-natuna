#!/usr/bin/env python3
import http.server
import os
import subprocess
import sys
import tempfile
import threading
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
WARMER = ROOT / "tools" / "warm-public-cache.py"


class Handler(http.server.BaseHTTPRequestHandler):
    counts = {}

    def do_GET(self):
        count = self.counts.get(self.path, 0)
        assert self.headers.get("User-Agent") == "lscache_runner", self.headers.get("User-Agent")
        self.counts[self.path] = count + 1
        self.send_response(200)
        self.send_header("Content-Type", "text/html")
        self.send_header("X-LiteSpeed-Cache", "miss" if count == 0 else "hit")
        self.end_headers()
        self.wfile.write(b"ok")

    def log_message(self, *_args):
        pass


def main():
    server = http.server.ThreadingHTTPServer(("127.0.0.1", 0), Handler)
    thread = threading.Thread(target=server.serve_forever)
    thread.daemon = True
    thread.start()
    base = "http://127.0.0.1:{}".format(server.server_port)
    try:
        with tempfile.TemporaryDirectory() as directory:
            sitemap = Path(directory) / "sitemap.xml"
            sitemap.write_text(
                '<?xml version="1.0"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
                '<url><loc>{0}/</loc></url><url><loc>{0}/profil</loc></url>'
                '<url><loc>https://example.com/asing</loc></url></urlset>'.format(base),
                encoding="utf-8",
            )
            result = subprocess.run(
                [sys.executable, str(WARMER), "--base-url", base, "--sitemap", str(sitemap), "--passes", "2", "--delay", "0"],
                text=True,
                encoding="utf-8",
                capture_output=True,
                env=dict(os.environ),
            )
    finally:
        server.shutdown()
        thread.join()
        server.server_close()

    assert result.returncode == 0, result.stderr
    assert Handler.counts == {"/": 2, "/profil": 2}, Handler.counts
    assert "Cache warmer pass 1: 2 URL; hit=0, miss=2" in result.stdout, result.stdout
    assert "Cache warmer pass 2: 2 URL; hit=2, miss=0" in result.stdout, result.stdout

    htaccess = (ROOT / ".htaccess").read_text(encoding="utf-8")
    migration = (ROOT / "database/migrations/20261017_extend_litespeed_cache_ttl.sql").read_text(encoding="utf-8")
    cron = (ROOT / "tools/cron-refresh-all.sh").read_text(encoding="utf-8")
    assert "CacheMaxStaleAge 3600" in htaccess
    assert "'$.cacheTimeout', '120'" in migration
    assert "'$.homePageCacheTimeout', '15'" in migration
    assert "warm-public-cache.py" in cron and "--passes 2" in cron
    print("Public cache warmer contract: ok")


if __name__ == "__main__":
    main()
