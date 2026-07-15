"""Contracts for importing public content from a compromised legacy shell."""
from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path
import tempfile

ROOT = Path(__file__).resolve().parents[1]
SPEC = spec_from_file_location("live_news", ROOT / "tools" / "import-live-news.py")
assert SPEC and SPEC.loader
MODULE = module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)
listing = (ROOT / "tools/fixtures/live-news-listing.html").read_text(encoding="utf-8")
detail = (ROOT / "tools/fixtures/live-news-detail.html").read_text(encoding="utf-8")
links, pages = MODULE.parse_listing(listing, "https://www.pn-natuna.go.id/index.php/en/berita/berita-terkini")
assert links == ["https://www.pn-natuna.go.id/index.php/en/berita/berita-terkini/berita-satu"]
assert pages == ["https://www.pn-natuna.go.id/index.php/en/berita/berita-terkini?start=10"]
record = MODULE.parse_detail(detail, links[0], "berita")
assert record["title"] == "Berita Satu"
assert record["alias"] == "legacy-berita-satu"
assert record["published"] == "2026-07-10 07:49:18"
assert "Isi <strong>aman</strong>" in record["full_html"]
for forbidden in ("<script", "<iframe", "onclick", "onerror", "evil.invalid", "createSuperUser"):
    assert forbidden not in record["full_html"]
assert record["images"] == ["https://www.pn-natuna.go.id/images/artikel/foto.jpeg"]
spaced = detail.replace('/images/artikel/foto.jpeg', '/images/artikel/zom 1.jpeg')
spaced_record = MODULE.parse_detail(spaced, links[0], "berita")
assert spaced_record["images"] == ["https://www.pn-natuna.go.id/images/artikel/zom%201.jpeg"]
for bad in ("http://www.pn-natuna.go.id/x", "https://evil.invalid/x", "javascript:alert(1)"):
    try:
        MODULE.validate_source_url(bad)
    except ValueError:
        pass
    else:
        raise AssertionError("unsafe source accepted: " + bad)
with tempfile.TemporaryDirectory() as folder:
    manifest = Path(folder) / "manifest.json"
    MODULE.write_manifest(manifest, [record])
    first = manifest.read_bytes()
    MODULE.write_manifest(manifest, [record])
    assert manifest.read_bytes() == first
sql = MODULE.render_sql([dict(record, local_image="images/news/imported/foto.webp")])
assert "catid" in sql and "12" in sql
assert "legacy_source_url" in sql
assert "ON DUPLICATE KEY" not in sql
assert "<script" not in sql
quoted = MODULE.sql_quote("Tema “Pelayanan”")
assert quoted.startswith("CONVERT(0x") and quoted.endswith(" USING utf8mb4)")
assert "“" not in quoted
assert MODULE.sql_quote("") == "''"
print("live news importer contract: ok")
