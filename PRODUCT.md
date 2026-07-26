# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

**Primary: masyarakat pencari keadilan di Kabupaten Natuna.** Orang awam yang datang
sesekali, tidak menguasai istilah hukum, dan sedang mengurus satu hal konkret —
jadwal sidang hari ini, prosedur mengajukan perkara, biaya panjar, jam buka PTSP,
cara minta salinan putusan. Banyak di antaranya membuka situs dari ponsel di wilayah
kepulauan dengan koneksi tidak stabil. **Ketika kepentingan bentrok di satu halaman,
kelompok ini yang menang.**

Audiens lain yang nyata dan harus tetap terlayani, tapi tidak boleh mendikte tata letak:

- **Pihak berperkara dan advokat** — pengguna berulang yang menelusuri perkara lewat
  SIPP dan e-Court, mengurus delegasi, mediasi, eksekusi. Butuh kecepatan, bukan penjelasan.
- **Tim penilai dan asesor** — penilai Zona Integritas (WBK/WBBM), AMPUH 2026, SAKIP,
  dan keterbukaan informasi. Mencari bukti dokumen yang terlacak, bukan layanan.
- **Aparat internal PN Natuna** — memakai situs sebagai etalase kerja dan rujukan dokumen.

## Product Purpose

Situs resmi **Pengadilan Negeri Natuna Kelas II**, satuan kerja di bawah Mahkamah Agung
RI melalui Direktorat Jenderal Badan Peradilan Umum dan Pengadilan Tinggi Kepulauan Riau.

Situs ini melakukan tiga pekerjaan sekaligus, dan pemiliknya menyatakan **ketiganya
sama-sama mengikat**:

1. **Menyelesaikan urusan orang tanpa mereka perlu menelepon atau datang** ke Jalan Batu
   Sisir. Ini pekerjaan utamanya.
2. **Lulus penilaian** — WBK/WBBM, AMPUH 2026, indeks keterbukaan informasi. Yang dihitung
   kelengkapan dan keterlacakan bukti.
3. **Patuh publikasi** — dokumen wajib MA RI terbit tepat waktu dan tidak basi.

Ketegangan di antara ketiganya nyata: konten yang lahir demi penilaian (LKjIP, SAKIP,
LHKPN, Zona Integritas) berjumlah besar dan jarang dicari pengunjung biasa. Pemilik
memutuskan tegangan itu diselesaikan per kasus, bukan dengan aturan umum. **Jangan
menyelesaikannya diam-diam dengan mengubur konten kepatuhan atau menenggelamkan
layanan publik.**

## Positioning

Situs pengadilan tingkat pertama di daerah kepulauan terluar yang **angka publiknya bisa
ditelusuri sampai dokumen sumber**, dan **datanya memperbarui diri sendiri** lewat cron —
bukan disalin manual tiap bulan lalu basi diam-diam. Kartu realisasi anggaran menautkan
tiap periode ke PDF SP2D-nya; skor SKM/IPAK menautkan ke dokumen publikasinya; berita
instansi ditarik langsung dari MA RI, Badilum, dan PT Kepri.

## Operating Context

- **Wilayah yurisdiksi:** Kabupaten Natuna, Provinsi Kepulauan Riau. Kantor di Jalan Batu
  Sisir, Desa Sungai Ulu, Kecamatan Bunguran Timur. Konteks kepulauan: jarak fisik ke
  kantor mahal, sehingga penyelesaian mandiri lewat situs punya nilai nyata.
- **Jam layanan:** Senin–Kamis 08.00–16.30, Jumat 08.00–17.00 WIB. Situs merender status
  buka/tutup secara dinamis di server dan memperbaruinya di klien.
- **Rutinitas pembaruan otomatis (cron harian):** feed instansi (MA RI, Badilum, PT Kepri),
  YouTube, SIPP, survei SKM/IPAK, dan realisasi DIPA. Runner tunggal
  `tools/cron-refresh-all.sh`; kredensial di luar webroot.
- **Sumber dokumen eksternal:** folder Google Drive publik untuk PDF DIPA dan survei.
  Penamaan berkas di folder itu tidak konsisten — isi PDF yang menentukan, bukan namanya.
- **Alur rilis:** kerja lokal (Laragon, PHP 8.3, MySQL 8.4) → staging `new.pn-natuna.go.id`
  di cPanel → cutover ke `pn-natuna.go.id` dengan rollback.

## Capabilities and Constraints

**Kapabilitas terkonfirmasi:** hero slider tiga slide (sambutan/layanan, berita &
pengumuman, Zona Integritas), pencarian, jadwal sidang dari cache SIPP, jam layanan
dinamis, direktori layanan PTSP, prosedur perkara, transparansi anggaran & kinerja,
galeri fasilitas, feed Instagram dan YouTube, peta lokasi, checklist AMPUH 2026 di
route `/ampuh`, mode gelap, dan panel aksesibilitas.

**Kendala yang mengikat pekerjaan berikutnya — semuanya dinyatakan pemilik:**

- **Struktur menu wajib mengikuti template Badilum/MA RI.** Susunan dan penamaan menu
  tidak boleh dirapikan menurut selera desain, sekalipun terlihat berulang atau janggal.
- **Aksesibilitas WCAG 2.2 adalah kontrak, bukan aspirasi.** Target sentuh 44px, kontras,
  navigasi keyboard, kendali rotasi otomatis. Sudah diuji mesin dan wajib tetap lulus.
- **Konten hidup di database Joomla, bukan di template.** Artikel dan modul tidak boleh
  di-hardcode. Perubahan data yang wajib mengikuti kode harus berupa migrasi SQL idempoten
  baru di `database/migrations/`; berkas migrasi yang sudah tercatat tidak boleh diedit.
- **Angka publik wajib terlacak ke sumber.** DIPA, SKM/IPAK, dan statistik perkara harus
  punya jejak ke PDF atau dokumen resmi. Angka lepas tanpa tautan sumber dilarang.
- **Identitas visual lembaga tidak boleh diganti.** Logo, warna maroon peradilan, dan
  lambang Kartika Cakra Anugraha tetap.

**Teknis:** Joomla 5, template kustom `pn_natuna_2026`, PHP 8.3, MySQL 8.4, shared hosting
cPanel. Prinsip pemeliharaan: Joomla-native bila cukup; kode kustom hanya untuk kebutuhan
yang tidak dipenuhi Joomla.

**Istilah yang dipakai apa adanya:** PTSP, SIPP, e-Court, PPID, Zona Integritas, WBK/WBBM,
SKM, IPAK, LHKPN, SAKIP, LKjIP, DIPA, prodeo, posbakum, delegasi, panjar. Ini kosakata
resmi peradilan — jangan diterjemahkan jadi bahasa awam tanpa persetujuan, tapi boleh
diberi penjelasan pendamping.

## Brand Commitments

- Nama resmi: **Pengadilan Negeri Natuna Kelas II**.
- Lambang Kartika Cakra Anugraha dan logo PN Natuna; palet maroon peradilan.
- Badge Reformasi Birokrasi di header.
- Suara: bahasa Indonesia formal kedinasan. Situs lembaga negara — tidak ada nada
  pemasaran, klaim superlatif, atau ajakan bergaya komersial.

## Evidence on Hand

Konten nyata yang sudah ada di repo dan database — **pakai ini, jangan mengarang pengganti**:

- **81 Berita, 12 Pengumuman**, 21 Profil Pengadilan, 17 Transparansi, 16 Layanan Publik,
  11 Zona Integritas, 8 Layanan Hukum, 8 Informasi Perkara.
- **Survei terpublikasi:** SKM TW2 2026 = 3,97/4,00 (99,27%, 61 responden); IPAK TW2 2026.
  Berkas di `images/surveys/` (PNG + PDF).
- **Realisasi DIPA** dari PDF SP2D di Google Drive; per Juni 2026 DIPA 01 pagu Rp 14,34 M
  serapan 54,96%, DIPA 03 pagu Rp 178,35 juta serapan 42,46%. Periode tersedia: Juni, Mei,
  Maret, Februari 2026 — **April tidak ada, dan ketiadaan itu jangan ditambal dengan
  interpolasi**.
- **Dataset AMPUH 2026:** `templates/pn_natuna_2026/data/ampuh-2026.json`.
- **Aset foto:** gedung pengadilan, Zona Integritas, role model, fasilitas publik di
  `images/`.

**Yang belum ada dan tidak boleh dikarang:** testimoni, studi kasus, angka kepuasan di luar
SKM/IPAK resmi, jumlah pengunjung, benchmark terhadap pengadilan lain, dan janji tingkat
layanan yang tidak tertulis di dokumen resmi.

## Product Principles

1. **Orang awam yang sedang buru-buru menang atas kelengkapan dokumen.** Konten kepatuhan
   tetap terbit lengkap dan mudah ditemukan penilai, tapi tidak boleh berdiri di depan
   jalan orang yang cuma mau tahu jadwal sidang.
2. **Angka tanpa jejak sumber tidak boleh tayang.** Setiap persentase, rupiah, dan skor
   menautkan ke dokumen aslinya. Kalau sumbernya belum ada, tampilkan ketiadaannya —
   jangan tampilkan angkanya.
3. **Data yang basi lebih berbahaya daripada data yang kosong.** Pembaruan otomatis harus
   bisa gagal dengan jujur: pertahankan cache lama, catat kegagalan, jangan pernah
   menampilkan angka lama seolah baru.
4. **Kewajiban dari MA RI diperlakukan sebagai batas, bukan bahan desain.** Struktur menu,
   daftar dokumen wajib, dan kosakata resmi diterima apa adanya; kebebasan desain dipakai
   di dalam batas itu.
5. **Aksesibilitas dan bobot halaman adalah fitur untuk pengguna kepulauan**, bukan
   pekerjaan rumah teknis. Koneksi lambat dan layar kecil adalah kondisi normal di sini.

## Accessibility & Inclusion

- **WCAG 2.2 AA** sebagai standar yang berlaku dan diuji mesin.
- Target sentuh minimum 44×44px; kontras teks memenuhi ambang pada terang dan gelap.
- Navigasi keyboard penuh, termasuk pola tab (panah/Home/End) yang seragam di seluruh situs.
- Konten yang bergerak otomatis lebih dari 5 detik wajib punya kendali jeda; `prefers-reduced-motion`
  dihormati.
- Halaman **Layanan Disabilitas** adalah komitmen layanan yang dinyatakan, bukan sekadar
  halaman informasi — antarmuka tidak boleh mengingkarinya.
- Mode gelap adalah kebutuhan keterbacaan, bukan gaya; kontraknya diuji terpisah.
