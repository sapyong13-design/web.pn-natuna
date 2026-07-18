# Mobile Navigation Information Architecture Design

## Tujuan

Membuat drawer mobile PN Natuna lebih mudah dipindai tanpa mengubah route publik, sambil mempertahankan navigasi desktop Joomla. Perbaikan mencakup pengelompokan menu panjang, label deskriptif, route shortcut kanonis, submenu tingkat ketiga yang terbaca, active-state aksesibel, footer ringkas, dan status mode gelap yang tidak bertumpuk.

## Struktur data

Perubahan menu disimpan sebagai migrasi SQL idempoten baru. Menu utama tetap: Beranda, Tentang Pengadilan, Layanan Publik, Layanan Hukum, Informasi Perkara, Berita & Pengumuman, Reformasi Birokrasi, Transparansi, AMPUH, Kontak.

Perubahan:
- `Berita` menjadi `Berita & Pengumuman`.
- `Hubungi Kami` menjadi `Kontak`.
- Area I–VI menjadi `Area I · Manajemen Perubahan`, `Area II · Penataan Tata Laksana`, `Area III · Penataan Sistem SDM`, `Area IV · Penguatan Akuntabilitas`, `Area V · Penguatan Pengawasan`, dan `Area VI · Peningkatan Kualitas Pelayanan`.
- `Penginputan Data Eksekusi` di-unpublish dari mainmenu karena fungsi internal.
- Transparansi dibagi lewat separator menu bertipe heading: Akuntabilitas Kinerja, Keuangan, Survei & Integritas, Informasi Publik. Item existing menjadi anak heading tanpa mengubah alias/link artikelnya.
- Informasi Perkara dibagi menjadi Biaya & Prosedur serta Data & Administrasi.
- Parent yang memiliki landing page tetap dapat dinavigasi. Drawer menyisipkan link `Ringkasan <nama parent>` sebagai item pertama submenu hanya pada runtime mobile, tanpa menduplikasi row DB.

## Drawer

Drawer mempertahankan dialog modal, focus trap, Escape, inert, scroll lock, dan accordion satu cabang per tingkat. Saat dibuka, route aktif digulir ke tengah bila berada di luar viewport. Submenu tingkat ketiga memakai font minimal 0.78rem dan indentasi lebih kecil.

Footer drawer setinggi sekitar 68–72px. Mode gelap menampilkan satu status: `Mati` saat `aria-pressed=false`, `Aktif` saat true. CSS selector status harus lebih spesifik daripada selector umum span menu. Telepon dan WhatsApp tetap target 44px.

Warna drawer menggunakan token `--nav-drawer-*` dengan override dark mode.

## Route kanonis

Shortcut internal memakai `/layanan-publik/permohonan-informasi` dan `/layanan-publik/regulasi-pengaduan`. Semua route menu lokal diverifikasi HTTP 200 memakai URL absolut.

## Verifikasi

Tes migrasi memakai database sementara, memeriksa struktur, label, published state, nested-set valid, serta replay idempoten. Kontrak source memeriksa status mode gelap tunggal, link ringkasan runtime, active-scroll, token, font, dan footer. Browser menguji 390×844 serta 320×568 dalam light/dark mode.