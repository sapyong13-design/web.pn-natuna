-- Kartu Maklumat merender dokumen 1414x2000 dan 1000x1414 ke dalam kotak 106x150
-- px: 177,9x dan 88,9x overscale, 482 KB terkirim untuk dua persegi krem yang
-- tidak terbaca. Hanya atribut src <img> yang dialihkan ke turunan 226x320;
-- data-maklumat-zoom pada tombol tetap menunjuk berkas resolusi penuh supaya
-- lightbox "Perbesar" tidak kehilangan apa pun.
--
-- Token sumber selalu memuat awalan `<img src=` sehingga atribut zoom pada
-- tombol tidak mungkin ikut tergantikan, dan token hasil tidak memuat token
-- sumber - migrasi aman diputar ulang.
UPDATE #__modules
SET content = REPLACE(
    REPLACE(content,
        '<img src="/images/layanan/maklumat-pelayanan-2026.webp"',
        '<img src="/images/layanan/maklumat-pelayanan-2026-thumb.webp" width="226" height="320"'),
    '<img src="/images/layanan/maklumat-layanan-informasi-publik.webp"',
    '<img src="/images/layanan/maklumat-layanan-informasi-publik-thumb.webp" width="226" height="320"')
WHERE id = 808
  AND (content LIKE '%<img src="/images/layanan/maklumat-pelayanan-2026.webp"%'
       OR content LIKE '%<img src="/images/layanan/maklumat-layanan-informasi-publik.webp"%');
