#!/usr/bin/env python3
"""Contract: the six Berita Terkini articles keep the official pn-natuna.go.id structure.

The migration rebuilds each article as the source publishes it: same paragraph order,
same heading order, same photo positions. Presentation belongs to the 2026 template, so
the stored HTML must stay free of legacy inline styling and of the unstyled gallery block.
The lead photo is the template hero and must never repeat inside the body.
"""
from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MIGRATION = ROOT / "database" / "migrations" / "20260829_restore_official_news_structure.sql"

ALT_MIGRATION = ROOT / "database" / "migrations" / "20260831_distinct_news_photo_alt_text.sql"
ALT_STATEMENT = re.compile(
    r"UPDATE #__content SET introtext=CONVERT\(0x([0-9a-f]+) USING utf8mb4\),"
    r"`fulltext`=CONVERT\(0x([0-9a-f]+) USING utf8mb4\),"
    r"images=CONVERT\(0x([0-9a-f]+) USING utf8mb4\) WHERE alias=CONVERT\(0x([0-9a-f]+) USING utf8mb4\);"
)
# alias -> (paragraphs, h2 headings, inline figures, lead photo promoted to the hero)
EXPECTED = {
    "sosialisasi-pn-natuna-pt-pos-indonesia-surat-tercatat": (
        7, 0, 2, "images/berita/2026/sosialisasi-pn-natuna-pt-pos-indonesia-surat-tercatat-1.webp",
    ),
    "candra-firmansyah-kasubbag-kepegawaian-ortala": (
        7, 0, 2, "images/berita/2026/candra-firmansyah-kasubbag-kepegawaian-ortala-1.webp",
    ),
    "penilaian-ampuh-ditjen-badilum-2026": (
        15, 5, 2, "images/berita/2026/penilaian-ampuh-ditjen-badilum-2026-1.webp",
    ),
    "legacy-sosialisasi-layanan-oleh-bpjs-kesehatan": (
        4, 0, 3, "images/news/imported/1f5d4faad26b53ab1dbb9b28bfa1d8efe8f5109e05f5e517dfd21a1f16d1765c.jpg",
    ),
    "legacy-sidang-keliling-di-kabupaten-kepulauan-anambas": (
        6, 0, 1, "images/news/imported/ac27aa5f4d4acd6b5a2c6f4c0826e7ccba0ffc4a64fbd7df5ea1842bdfa27b2a.jpg",
    ),
    "pengambilan-sumpah-atau-janji-cpns-menjadi-pns": (
        3, 0, 2, "images/berita/2026/pengambilan-sumpah-atau-janji-cpns-menjadi-pns-1.webp",
    ),
}

# Judul: lima artikel memakai judul sumber apa adanya, judul CPNS dipulihkan penuh
# karena sumber mengeja "Pengawai Negeri Sipil"; dua judul legacy dirapikan kapitalisasinya.
TITLES = {
    "sosialisasi-pn-natuna-pt-pos-indonesia-surat-tercatat": "Sosialisasi PN Natuna dengan PT Pos Indonesia KCP Ranai dan KCP Tarempa Bahas Penyampaian Panggilan lewat Surat Tercatat",
    "candra-firmansyah-kasubbag-kepegawaian-ortala": "Candra Firmansyah Resmi Menjabat Kepala Subbagian Kepegawaian dan Ortala Pengadilan Negeri Natuna",
    "penilaian-ampuh-ditjen-badilum-2026": "Menjaga Mutu dari Ujung Utara: Pengadilan Negeri Natuna Jalani Penilaian AMPUH Ditjen Badilum",
    "legacy-sosialisasi-layanan-oleh-bpjs-kesehatan": "Sosialisasi Layanan oleh BPJS Kesehatan",
    "legacy-sidang-keliling-di-kabupaten-kepulauan-anambas": "Sidang Keliling di Kabupaten Kepulauan Anambas",
    "pengambilan-sumpah-atau-janji-cpns-menjadi-pns": "Pengambilan Sumpah atau Janji Calon Pegawai Negeri Sipil Menjadi Pegawai Negeri Sipil",
}

# Salah ketik warisan impor legacy. Sekali diperbaiki, tidak boleh kembali.
FORBIDDEN = (
    "Pengawai",
    "seluh jajaran",
    "Perjalannya",
    "di hadari",
    "Bpak",
    "di ikuti",
    "diantaranya",
    "S.H.,M.H.",
    "Ciptanto,S.H.",
    "Dalam Kesempatan",
    "dan Saksi-saksi",
    "oleh ketiga PNS",
    # Tanda tanya berdiri sendiri = tanda em dash sumber hancur saat konten melewati
    # konsol non-UTF-8. Dua lead terbaru memakai "Natuna — Pengadilan Negeri Natuna".
    " ? ",
)
LEAD_EM_DASH = (
    "sosialisasi-pn-natuna-pt-pos-indonesia-surat-tercatat",
    "candra-firmansyah-kasubbag-kepegawaian-ortala",
)

STATEMENT = re.compile(
    r"UPDATE #__content SET title=CONVERT\(0x([0-9a-f]+) USING utf8mb4\),"
    r"introtext=CONVERT\(0x([0-9a-f]+) USING utf8mb4\),"
    r"`fulltext`=CONVERT\(0x([0-9a-f]+) USING utf8mb4\),"
    r"images=CONVERT\(0x([0-9a-f]+) USING utf8mb4\),"
    r"metadata=CONVERT\(0x([0-9a-f]+) USING utf8mb4\),"
    r"modified='[^']+' WHERE alias=CONVERT\(0x([0-9a-f]+) USING utf8mb4\);"
)


def decode(payload: str) -> str:
    return bytes.fromhex(payload).decode("utf-8")


def main() -> int:
    assert MIGRATION.is_file(), "Official news structure migration is missing: {}".format(MIGRATION.name)
    sql = MIGRATION.read_text(encoding="utf-8")
    statements = STATEMENT.findall(sql)
    assert len(statements) == len(EXPECTED), "Expected {} article updates, found {}".format(len(EXPECTED), len(statements))

    seen = set()
    for title_hex, intro_hex, full_hex, images_hex, metadata_hex, alias_hex in statements:
        alias = decode(alias_hex)
        assert alias in EXPECTED, "Unexpected article alias in migration: {}".format(alias)
        seen.add(alias)
        paragraphs, headings, figures, hero = EXPECTED[alias]
        title = decode(title_hex)
        introtext, fulltext = decode(intro_hex), decode(full_hex)
        body = introtext + fulltext
        images = json.loads(decode(images_hex))
        metadata = json.loads(decode(metadata_hex))

        assert title == TITLES[alias], "{}: expected title {!r}, found {!r}".format(alias, TITLES[alias], title)
        for typo in FORBIDDEN:
            assert typo not in body and typo not in title, "{}: legacy typo must stay fixed: {!r}".format(alias, typo)
        if alias in LEAD_EM_DASH:
            assert "Natuna</strong> \u2014 Pengadilan" in introtext, "{}: source em dash in the lead must survive encoding".format(alias)

        assert body.count("<p>") == paragraphs, "{}: expected {} paragraphs, found {}".format(alias, paragraphs, body.count("<p>"))
        assert body.count("<h2>") == headings, "{}: expected {} headings, found {}".format(alias, headings, body.count("<h2>"))
        assert body.count("<figure") == figures, "{}: expected {} inline figures, found {}".format(alias, figures, body.count("<figure"))
        assert body.count("<figure") == body.count('class="editorial-article__figure"'), "{}: every inline photo must use the editorial figure class".format(alias)

        assert "news-inline-gallery" not in body, "{}: the unstyled gallery block must not come back".format(alias)
        assert "style=" not in body, "{}: legacy inline styling must stay out of stored content".format(alias)
        assert "<span" not in body, "{}: legacy span wrappers must stay out of stored content".format(alias)
        assert "<p></p>" not in body, "{}: empty paragraphs from the legacy import must stay removed".format(alias)

        # Joomla renders introtext immediately before fulltext, so the teaser paragraph
        # must exist exactly once and never be duplicated at the top of fulltext.
        assert introtext.count("<p>") == 1, "{}: introtext must hold exactly one lead paragraph".format(alias)
        lead = re.search(r"<p>(.*?)</p>", introtext, re.DOTALL).group(1)
        assert lead not in fulltext, "{}: the lead paragraph must not repeat inside fulltext".format(alias)

        assert images["image_intro"] == hero and images["image_fulltext"] == hero, "{}: hero photo must be {}".format(alias, hero)
        assert images["image_intro_alt"] and images["image_fulltext_alt"], "{}: hero photo needs alternative text".format(alias)
        assert "/" + hero not in body, "{}: hero photo must not repeat inside the body".format(alias)
        assert metadata.get("legacy_source_url", "").startswith("https://www.pn-natuna.go.id/"), "{}: official source URL must be recorded".format(alias)

        for src, width, height in re.findall(r'<img src="([^"]+)" alt="[^"]*" width="(\d+)" height="(\d+)"', body):
            asset = ROOT / src.lstrip("/")
            assert asset.is_file(), "{}: referenced photo is missing from the repository: {}".format(alias, src)
            assert int(width) > 0 and int(height) > 0, "{}: photo {} needs intrinsic dimensions against layout shift".format(alias, src)
        assert body.count("<img ") == body.count(' loading="lazy"'), "{}: inline photos must stay lazy loaded".format(alias)
        assert (ROOT / hero).is_file(), "{}: hero photo is missing from the repository: {}".format(alias, hero)

    assert seen == set(EXPECTED), "Migration misses articles: {}".format(sorted(set(EXPECTED) - seen))

    # Setiap foto menjelaskan dirinya sendiri: alt figur tidak boleh kembar dalam satu
    # artikel, dan alt hero tidak boleh menyalin judul (pembaca layar mengucap dua kali).
    assert ALT_MIGRATION.is_file(), "Distinct photo alt migration is missing: {}".format(ALT_MIGRATION.name)
    alt_sql = ALT_MIGRATION.read_text(encoding="utf-8")
    alt_statements = ALT_STATEMENT.findall(alt_sql)
    assert len(alt_statements) == len(EXPECTED), "Expected {} alt updates, found {}".format(len(EXPECTED), len(alt_statements))
    for intro_hex, full_hex, images_hex, alias_hex in alt_statements:
        alias = decode(alias_hex)
        assert alias in EXPECTED, "Unexpected article alias in alt migration: {}".format(alias)
        body = decode(intro_hex) + decode(full_hex)
        images = json.loads(decode(images_hex))
        alts = re.findall(r'<img src="[^"]+" alt="([^"]*)"', body)
        assert all(alt.strip() for alt in alts), "{}: every inline photo needs alternative text".format(alias)
        assert len(set(alts)) == len(alts), "{}: inline photos must not share one alt text".format(alias)
        hero_alt = images["image_fulltext_alt"]
        assert hero_alt and hero_alt not in alts, "{}: hero needs its own alt text".format(alias)
        assert hero_alt != TITLES[alias], "{}: hero alt must describe the photo, not repeat the headline".format(alias)

    print("official news structure contract: ok")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
