-- Hapus kotak catatan dan sisa span panah di tiga halaman laporan survei.
--
-- Dua hal yang dibuang:
--
--   1. `<p class="transparency-note">…</p>` yang ditambahkan 20261106.
--      Tampil sebagai kotak kuning besar di atas daftar dokumen dan terasa
--      mengganggu. Informasi yang benar-benar perlu sudah tercakup paragraf
--      pembuka halaman, jadi kotak itu tidak menambah apa pun.
--
--   2. `<span aria-hidden="true">&nearr;</span>` di akhir setiap kartu.
--      Panah ↗ pada kartu digambar CSS lewat `.transparency-document::after`,
--      jadi span itu mubazir. Karena isinya entity, DOMDocument meng-escape-nya
--      dua kali dan yang tampil di halaman justru tulisan "&nearr;" — bukan
--      panah. `20260820_normalize_transparency_document_arrows.sql` pernah
--      membersihkan ini, tapi 20260805/20260806 menambahkannya lagi untuk kartu
--      baru dan refresh-surveys.py sempat meneruskannya.
--
-- Idempoten: WHERE hanya cocok selama masih ada yang perlu dibuang.

-- 1. Buang kotak catatan.
UPDATE #__content
SET introtext = REGEXP_REPLACE(introtext, '<p class="transparency-note">[^<]*</p>', ''),
    modified = UTC_TIMESTAMP()
WHERE id IN (42, 43, 85)
  AND introtext REGEXP '<p class="transparency-note">[^<]*</p>';

-- 2. Buang span panah, baik yang masih berupa entity maupun yang sudah
--    ter-escape oleh DOMDocument. Penggantinya satu backslash + 1 agar MySQL
--    membaca backreference, bukan backslash harfiah.
UPDATE #__content
SET introtext = REGEXP_REPLACE(
        REGEXP_REPLACE(
            introtext,
            '<span[[:space:]]+aria-hidden="true">[^<]*</span>([[:space:]]*</a>)',
            '\\1'
        ),
        '<span[[:space:]]+aria-hidden="true">&amp;[a-z]+;</span>([[:space:]]*</a>)',
        '\\1'
    ),
    modified = UTC_TIMESTAMP()
WHERE introtext REGEXP '<span[[:space:]]+aria-hidden="true">[^<]*</span>[[:space:]]*</a>'
   OR introtext REGEXP '<span[[:space:]]+aria-hidden="true">&amp;[a-z]+;</span>[[:space:]]*</a>';
