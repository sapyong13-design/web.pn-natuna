"""Behavior contracts for local news deduplication cleanup."""
from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path
import tempfile

ROOT = Path(__file__).resolve().parents[1]
SPEC = spec_from_file_location("cleanup_news", ROOT / "tools/cleanup-news-import.py")
assert SPEC and SPEC.loader
MODULE = module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)

with tempfile.TemporaryDirectory() as folder:
    root = Path(folder)
    (root / "images/a").mkdir(parents=True)
    (root / "images/b").mkdir(parents=True)
    (root / "images/a/curated.jpg").write_bytes(b"same-image")
    (root / "images/b/imported.jpg").write_bytes(b"same-image")
    (root / "images/b/other.jpg").write_bytes(b"other-image")
    rows = [
        {"id": 10, "alias": "curated", "publish_up": "2026-06-01 00:00:00", "introtext": "Ada", "fulltext": "", "image": "images/a/curated.jpg"},
        {"id": 20, "alias": "legacy-curated", "publish_up": "2026-07-01 00:00:00", "introtext": "", "fulltext": "<p>Versi impor lebih baru.</p>", "image": "images/b/imported.jpg"},
        {"id": 30, "alias": "legacy-unique", "publish_up": "2026-05-01 00:00:00", "introtext": "", "fulltext": "<p><img src=\"x\"></p><p>Ringkasan unik yang bermakna.</p>", "image": "images/b/other.jpg"},
    ]
    result = MODULE.build_cleanup(rows, root)
    assert result["losers"] == [20]
    assert result["winners"] == {20: 10}
    assert result["excerpts"] == {30: "Ringkasan unik yang bermakna."}
    rows.extend([
        {"id": 40, "alias": "pengambilan-sumpah-atau-janji-cpns-menjadi-pns", "publish_up": "2026-06-04 03:00:17", "introtext": "Ada", "fulltext": "", "image": ""},
        {"id": 41, "alias": "legacy-pengambilan-sumpah-atau-janji-calon-pegawai-negeri-sipil-menjadi-pegawai-negeri-sipil", "publish_up": "2026-06-04 15:45:00", "introtext": "", "fulltext": "<p>Versi lama.</p>", "image": ""},
    ])
    result = MODULE.build_cleanup(rows, root)
    assert result["losers"] == [20, 41]
    assert result["winners"] == {20: 10, 41: 40}

legacy_rows = [
    {"id": 2, "alias": "legacy-old", "publish_up": "2026-01-01 00:00:00", "introtext": "", "fulltext": "<p>Panjang lama.</p>", "image": "images/a.jpg"},
    {"id": 3, "alias": "legacy-new-short", "publish_up": "2026-02-01 00:00:00", "introtext": "", "fulltext": "<p>Baru.</p>", "image": "images/b.jpg"},
    {"id": 4, "alias": "legacy-new-long", "publish_up": "2026-02-01 00:00:00", "introtext": "", "fulltext": "<p>Berita baru yang lebih lengkap.</p>", "image": "images/c.jpg"},
]
assert MODULE.choose_winner(legacy_rows)["id"] == 4
assert MODULE.extract_excerpt("<p><img src='x'></p><p>&nbsp;</p><p> Isi <strong>berita</strong>. </p>") == "Isi berita."
assert MODULE.extract_excerpt("<script>jahat()</script><p>Aman.</p>") == "Aman."
assert MODULE.extract_excerpt("<p>Ranai, 25 April 2024</p><p>Pengadilan Negeri Natuna mengikuti pembinaan teknis untuk meningkatkan mutu pelayanan kepada masyarakat.</p>") == "Ranai, 25 April 2024 Pengadilan Negeri Natuna mengikuti pembinaan teknis untuk meningkatkan mutu pelayanan kepada masyarakat."
assert MODULE.extract_excerpt("<p>Natuna, 10 Juli 2026.┬á Kegiatan pelayanan dilaksanakan untuk masyarakat.</p>") == "Natuna, 10 Juli 2026. Kegiatan pelayanan dilaksanakan untuk masyarakat."
assert MODULE.clean_legacy_text("Tema ÔÇ£PelayananÔÇØ dan pegawaiÔÇÖs") == "Tema “Pelayanan” dan pegawai’s"
sql = MODULE.render_sql([20], {30: "Ringkasan unik yang bermakna."})
assert "state = -2" in sql
assert "WHERE id = 20" in sql
assert "introtext = CONVERT(0x" in sql
assert "DELETE" not in sql.upper()
assert "Ringkasan unik" not in sql
assert MODULE.sql_quote("") == "''"
print("news cleanup contract: ok")
