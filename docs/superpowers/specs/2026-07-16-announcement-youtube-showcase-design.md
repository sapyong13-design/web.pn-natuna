# Announcement and YouTube Showcase Design

## Tujuan

Menggabungkan Pengumuman Baru dan video resmi PN Natuna dalam satu section homepage yang ringkas, interaktif, cepat, dan mudah dipelihara.

## Layout

Gunakan layout desktop 45:55:

- Kiri: tepat satu pengumuman terbaru kategori Pengumuman.
- Kanan: preview/player video rasio 16:9 dan rail lima video.
- Mobile: pengumuman di atas, player di bawah, rail menjadi horizontal snap.
- Header section memakai judul `Pengumuman & Video Terbaru`, kicker `Informasi Resmi & Dokumentasi`, link `Semua Pengumuman`, dan link `Lihat Channel YouTube`.

Pengumuman memakai struktur visual showcase saat ini: gambar dokumen, badge Terbaru, tanggal, judul, ringkasan singkat, dan CTA. Dua pengumuman ringkas lama dihapus dari section ini.

## Sumber Video

Channel resmi:

- Handle: `@PengadilanNegeriNatuna-t8q`
- Channel ID: `UCuPb35OggK2PKdW7Ed0qszA`
- Feed: `https://www.youtube.com/feeds/videos.xml?channel_id=UCuPb35OggK2PKdW7Ed0qszA`

Video wajib:

1. `-Di2t-yUZ1I`, **Video Profile Pengadilan Negeri / Perikanan Ranai**.
2. `kQ0dMRp1W_g`, **Tata cara penggunaan e-Berpadu**.

Kedua video diverifikasi tersedia dan mengembalikan iframe publik melalui YouTube oEmbed pada 16 Juli 2026. Renderer tetap menyediakan link `Tonton di YouTube` bila kebijakan embed berubah.

Komposisi akhir selalu maksimal lima video:

1. Dua video wajib dalam urutan di atas.
2. Tiga video terbaru dari feed resmi.
3. Item feed yang ID-nya sama dengan video wajib atau item sebelumnya dilewati.

## Refresh dan Cache

Tambahkan refresher PHP mengikuti pola feed Instagram/instansi:

- Ambil Atom feed resmi YouTube tanpa API key.
- Normalisasi video ID, judul, tanggal publikasi, URL, dan thumbnail.
- Gabungkan dua video wajib dengan tiga item feed terbaru setelah deduplikasi.
- Simpan JSON cache lokal yang di-ignore Git.
- Cron tidak menimpa cache valid bila fetch atau parsing gagal.
- Renderer memakai cache lokal saja agar request homepage tidak bergantung pada YouTube.
- Jika cache belum ada atau invalid, renderer tetap menampilkan dua video wajib dengan metadata fallback yang dikurasi.

## Interaksi Player

- Halaman awal hanya memuat thumbnail lokal/YouTube image, bukan iframe.
- Video pertama menjadi preview aktif.
- Klik tombol Putar memuat iframe `https://www.youtube-nocookie.com/embed/{videoId}?autoplay=1&rel=0` di tempat.
- Klik rail sebelum player aktif mengganti thumbnail, judul, dan video aktif.
- Klik rail setelah player aktif mengganti iframe ke video terpilih.
- Item aktif memakai `aria-current="true"`; semua kontrol keyboard-accessible.
- Label sumber item: `Wajib` atau `Terbaru`.
- Link channel membuka channel resmi pada tab baru dengan `rel="noopener"`.

## Performa dan Privasi

- Tidak ada YouTube iframe, script, atau cookie sebelum pengguna menekan Putar.
- Thumbnail memakai `loading="lazy"` dan ukuran eksplisit.
- Player menjaga rasio 16:9 untuk mencegah layout shift.
- Interaksi hanya mengubah `src`, teks, dan state kelas; tidak menganimasikan layout properties.
- Hormati `prefers-reduced-motion`.

## Responsive

- Desktop di atas 1180px: grid 45:55.
- Tablet: satu kolom, pengumuman lalu video.
- Mobile hingga 760px: rail horizontal snap dengan lebar item terbatas, target sentuh minimal 44px.
- Tidak ada horizontal overflow pada body.

## Dark Mode dan Aksesibilitas

- Gunakan token warna template dan state dark mode yang sudah ada.
- Preview mempunyai alt kosong bila judul tersedia berdampingan; tombol Putar memiliki label video lengkap.
- Status perubahan video diumumkan melalui live region sopan.
- Focus ring jelas untuk tombol Putar dan item rail.
- Player memiliki `title` sesuai judul video aktif.

## Error Handling

- Fetch gagal: pertahankan cache terakhir.
- Feed kosong: dua video wajib tetap tampil.
- Thumbnail gagal: gunakan fallback visual brand PN Natuna.
- Video tidak dapat di-embed: sediakan link `Tonton di YouTube` untuk video aktif.
- JSON invalid: abaikan cache dan gunakan fallback wajib.

## Verification

- Kontrak renderer memastikan tepat satu pengumuman dan maksimal lima video.
- Kontrak deduplikasi memastikan dua video wajib tidak muncul kembali dari feed terbaru.
- Kontrak lazy player memastikan iframe tidak ada sebelum klik dan memakai `youtube-nocookie.com` setelah klik.
- Jalankan refresher terhadap feed resmi dan periksa cache berisi dua wajib plus tiga terbaru.
- Browser desktop dan mobile: uji pemilihan rail, play inline, keyboard, dark mode, tanpa layout shift, serta fallback cache.

## Batas Scope

- Tidak memakai YouTube Data API atau API key.
- Tidak membuat halaman video baru.
- Tidak mengubah section homepage lain.
- Tidak autoplay sebelum interaksi pengguna.
- Seluruh rangkaian dibuat dalam satu commit lokal dan tidak di-push tanpa perintah eksplisit.