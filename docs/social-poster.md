# Poster & video Instagram dari data DIPA

Satu perintah menghasilkan tiga berkas dari angka yang sama: poster portrait, video
Reels, dan garis besar caption. Semua angka dibaca dari modul Joomla `816`
(`Kinerja & Akuntabilitas`), jadi visual tidak bisa memuat angka berbeda dari situs.

```sh
php tools/make-dipa-poster.php                  # periode terbaru
php tools/make-dipa-poster.php --period=2026-07  # bulan tertentu (2026-7, "Juli", "Juli 2026")
php tools/make-dipa-poster.php --list            # daftar periode yang tersedia
php tools/make-dipa-poster.php --all             # semua periode + galeri
```

Keluaran di `tmp/social/`:

| Berkas | Isi |
| --- | --- |
| `dipa-ig-<periode>.html` | poster 1080x1350 |
| `dipa-ig-<periode>-video.html` | animasi 8 detik, frame deterministik |
| `dipa-ig-<periode>.md` | angka utama, sorotan, alur unggahan, caption |

`--out`, `--caption`, `--video` mengarahkan tiap keluaran ke path lain.

`--all` memanggil skrip ini sekali per periode lalu menulis `tmp/social/index.html`:
galeri berisi poster tiap bulan yang bisa dibandingkan berdampingan. Tiap poster hanya
memuat data sampai periode itu — poster Agustus tidak memperlihatkan angka September —
dan periode paling awal (Februari) tidak memuat kalimat "dibanding bulan sebelumnya"
karena tidak punya pembanding.

## Merender PNG

Buka HTML-nya di browser dengan viewport 1080x1350 lalu tangkap elemen `body`:

```js
await page.setViewport({ width: 1080, height: 1350, deviceScaleFactor: 2 });
await page.goto(url);
await page.evaluate(() => document.fonts.ready);
await page.screenshot({ path: "dipa.png", clip: { x: 0, y: 0, width: 1080, height: 1350 } });
```

## Merender MP4

Halaman animasi menerima dua mode:

- tanpa parameter — animasi berjalan di browser untuk pratinjau;
- `?frame=1` — mode rekam: tidak ada `requestAnimationFrame`, frame hanya berubah saat
  diminta lewat `window.seek(detik)` atau event `CustomEvent("seek", {detail: detik})`.
  Event dipakai karena penyadap otomasi kadang berjalan di world terpisah dari skrip
  halaman.

Rekam 24 fps selama 8 detik, lalu gabungkan:

```sh
ffmpeg -framerate 24 -i frames/f_%04d.png \
  -c:v libx264 -preset slow -crf 20 -pix_fmt yuv420p -movflags +faststart out.mp4
```

Frame harus persis 1080x1350 — kalau tangkapan browser keluar di 1062x1254, viewport
belum ditegakkan (metrik perangkat perlu di-override lewat CDP
`Emulation.setDeviceMetricsOverride`).

## Isi poster

- Kop: logo PN Natuna, "S.D. <periode>".
- Dua kartu: persentase serapan + selisih poin dari bulan sebelumnya + pagu/terserap.
- Grafik garis DIPA 01 dan DIPA 03 dari Februari sampai periode berjalan.
- Sorotan: kenaikan terbesar, total serapan, dan pertumbuhan sejak periode pertama.
- Kaki: sumber laporan dan `pn-natuna.go.id`.

Angka sorotan dihitung dari markup, bukan diketik: `delta_title` pada tiap
`dipa-delta` menyimpan selisih poinnya.

## Berkas contoh hasil

`tmp/social/dipa-ig-September-2026.{png,mp4,md}` dibuat 11 November 2026 dari data
periode September. `tmp/` diabaikan Git, jadi hasil render tidak ikut ter-commit.
