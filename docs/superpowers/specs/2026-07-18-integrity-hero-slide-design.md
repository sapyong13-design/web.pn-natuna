# Integrity Hero Slide Design

## Tujuan

Menambahkan poster Gratifikasi & Pungutan Liar sebagai slide kedua hero homepage. Slide Berita & Pengumuman berpindah ke urutan ketiga tanpa mengubah isi atau interaksinya.

## Urutan

1. Sambutan dan layanan.
2. Poster integritas full-bleed, seluruh poster menaut ke `/zona-integritas`.
3. Berita & Pengumuman.

## Presentasi poster

Poster disimpan sebagai WebP teroptimasi di `images/hero/integritas-tolak-gratifikasi-pungli-2026.webp`. Artwork tidak diberi overlay teks karena sudah mengandung identitas PN Natuna, pesan antigratifikasi, dan logo kampanye. Gambar memakai `object-fit: contain` agar teks serta logo tidak terpotong. Latar gelap hijau institusional mengisi ruang sisa pada rasio viewport yang berbeda.

Desktop menampilkan poster selebar mungkin dalam area hero. Mobile mempertahankan poster utuh; tinggi hero mengikuti rasio gambar agar tidak memotong pesan. Tautan memiliki label aksesibel dan focus ring yang jelas.

## Perilaku carousel

Carousel tetap memakai `initCarousel`, interval 7000 ms, panah manual, serta reduced-motion behavior yang sudah ada. Tiga dot memakai indeks 0, 1, 2. Slide poster lazy-load; slide sambutan tetap prioritas pertama. Slide berita tetap memakai preview dinamis.

## Verifikasi

Kontrak memeriksa tiga slide, urutan poster sebelum berita, route `/zona-integritas`, aset WebP lokal, tiga dot, serta tidak adanya overlay copy pada poster. Browser desktop dan mobile memeriksa poster utuh, tidak overflow, navigasi dot ketiga membuka berita, dan gambar poster tidak terpotong.