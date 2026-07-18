# Ultra-wide Layout Containment Design

## Tujuan

Menjaga beranda tetap terbaca dan proporsional saat viewport sangat lebar atau browser di-zoom keluar ekstrem, tanpa mengubah tampilan desktop normal dan mobile.

## Keputusan

Gunakan shell visual terpusat dengan lebar maksimum `1920px` pada viewport desktop. Elemen utama yang dibatasi: `.site-header`, `.hero`, `.quick-links`, `.site-main`, `.home-grid`, `.home-juknis-layout`, dan `.site-footer`.

Latar `body` menjadi gutter netral. Tiap section mempertahankan warna dan background miliknya. Batas hanya aktif mulai `1921px`, sehingga Full HD 1920×1080 pada zoom 100%, seluruh breakpoint tablet, dan mobile tidak berubah.

## Perilaku

- Viewport sampai 1920px: tampilan identik dengan kondisi sebelumnya dan memenuhi lebar Full HD.
- Viewport di atas 1920px: shell tetap 1920px dan terpusat, gutter kiri-kanan simetris.
- Hero, navigasi, grid, dan footer tidak meregang tanpa batas.
- Zoom browser tetap ditangani secara alami oleh CSS, tanpa deteksi zoom JavaScript.
- Sticky navigation mengikuti shell yang sama saat aktif.

## Verifikasi

- Kontrak sumber memastikan breakpoint, daftar shell, batas `1920px`, centering, dan sticky navigation tersedia.
- Browser diperiksa pada 1366×768, 1920×1080, 2560×1440, dan 3840×2160.
- Pada 3840px, shell harus berlebar 1920px dan berposisi sekitar `x=960px`.
- Pada Full HD 1920px dan viewport lebih kecil, shell harus tetap selebar viewport.

## Revert

Seluruh perubahan disimpan dalam satu commit lokal. `git revert <commit>` mengembalikan kondisi sebelumnya tanpa mengubah riwayat lain.