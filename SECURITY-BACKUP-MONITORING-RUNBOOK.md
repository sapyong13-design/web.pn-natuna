# Runbook Backup, Monitoring, dan Pemulihan Produksi

> Instruksi operasi cPanel produksi. Simpan script, konfigurasi, log, baseline, dan backup **di luar `public_html`**, idealnya `$HOME/private/`. Jangan menaruh secret di cron, argumen command, tiket, chat, atau output. Status pengaturan harus dibuktikan; dokumen ini bukan bukti aktivasi.

## 1. Tata letak privat dan kredensial

```text
$HOME/private/bin/                 mode 0700 (script operasional)
$HOME/private/etc/                 mode 0700
$HOME/private/etc/backup.env       mode 0600
$HOME/private/backups/db/          mode 0700; file 0600
$HOME/private/baselines/           mode 0700; file 0600
$HOME/private/log/                 mode 0700; log 0600
```

Salin `tools/secure-db-backup.sh` ke `$HOME/private/bin/`, owner akun cPanel, mode `0700`. Contoh `$HOME/private/etc/backup.env` (isi nilai sebenarnya hanya di server):

```sh
BACKUP_DIR="$HOME/private/backups/db"
DB_HOST="localhost"
DB_NAME="nama_database"
DB_USER="user_database"
DB_PASSWORD="nilai_dari_password_manager"
RETENTION_DAYS=14
```

Wrapper membuat option file sementara mode `0600`, sehingga password tidak muncul di process list atau command cron; file dihapus melalui trap. Output hanya nama file, ukuran, dan UTC—tanpa host/user/password. Jangan aktifkan shell trace (`set -x`). Batasi akses log cron dan jangan kirim dump sebagai lampiran email.

## 2. Jadwal dan cron

Ganti `/home/CPANELUSER` dengan path absolut hasil cPanel, dan nama tool deteksi dengan path final dari paket keamanan. Gunakan **cPanel Cron Jobs**, bukan URL/webcron. Setiap script harus memakai lock dan exit nonzero saat gagal. Arahkan stdout/stderr ke log privat; cPanel email hanya ke mailbox operasi yang diawasi.

```cron
# DB konsisten harian, 01:17 UTC
17 1 * * * /home/CPANELUSER/private/bin/secure-db-backup.sh /home/CPANELUSER/private/etc/backup.env >>/home/CPANELUSER/private/log/backup.log 2>&1
# Integritas tiap 15 menit (exit 2 findings; exit 3 error)
*/15 * * * * /usr/local/bin/python3 /home/CPANELUSER/private/security/security_cli.py integrity check /home/CPANELUSER/public_html --baseline /home/CPANELUSER/private/security/integrity-baseline.json --json >>/home/CPANELUSER/private/log/integrity.log 2>&1
# Judol setelah export konten tersanitasi, tiap jam; audit akun mingguan memakai export tanpa hash/token
37 * * * * /usr/local/bin/python3 /home/CPANELUSER/private/security/security_cli.py judol --root /home/CPANELUSER/public_html --records /home/CPANELUSER/private/security/joomla-content-export.json --allowlist /home/CPANELUSER/private/security/judol-allowlist.txt --json >>/home/CPANELUSER/private/log/judol.log 2>&1
40 2 * * 0 /usr/local/bin/python3 /home/CPANELUSER/private/security/security_cli.py accounts /home/CPANELUSER/private/security/account-audit.json --json >>/home/CPANELUSER/private/log/accounts.log 2>&1
# Verifikasi checksum backup harian setelah backup
47 1 * * * cd /home/CPANELUSER/private/backups/db && sha256sum -c -- *.sha256 >>/home/CPANELUSER/private/log/backup-verify.log 2>&1
```

Jika tool final memakai nama/flag berbeda, salin command tepat dari dokumentasi tool; jangan membuat endpoint publik. Tes manual setiap command sebagai user cPanel sebelum menyimpan cron. Pastikan timezone cPanel dan dokumentasikan UTC aktual.

## 3. Siklus operasi

### Harian

- Periksa exit/status cron: backup baru non-kosong, `gzip -t` dan SHA-256 sukses; scan integritas/judol tidak melewatkan jadwal.
- Tinjau login cPanel/Joomla, perubahan Super User, extension, cron, `.htaccess`, `.user.ini`, file PHP baru/berubah, konten/link/domain judol, dan lonjakan 404/POST/login.
- Replikasi backup ke akun/provider berbeda dengan enkripsi sisi klien atau fasilitas backup terenkripsi provider. Kunci dekripsi disimpan di password manager/offline, **terpisah dari hosting dan backup**. Uji bahwa akun hosting yang diambil alih tidak dapat menghapus salinan off-account.
- Operator mengakui alert dan mencatat waktu UTC, severity, bukti, keputusan.

### Mingguan

- Verifikasi seluruh manifest SHA-256 dan keterbacaan archive; bandingkan baseline hanya terhadap rilis yang disetujui.
- Audit Joomla Super Users/MFA, cPanel/FTP/SSH/email, API token, cron, scheduled tasks, extension, writable directory, DNS dan Cloudflare event.
- Salin satu backup mingguan terenkripsi ke off-account. Retensi: 14 harian, 8 mingguan, 12 bulanan; kebijakan provider tidak boleh lebih pendek.
- Pulihkan DB terbaru ke environment terisolasi/nonpublik dengan credential baru; jalankan pemeriksaan tabel, homepage/admin login dengan akun uji, dan scan judol. Hapus drill dengan aman setelah bukti non-rahasia disimpan.

### Bulanan

- Jalankan **restore drill penuh clean-room**: host/VM baru terisolasi, source dari commit/rilis bersih, dependency resmi, DB terverifikasi, konfigurasi baru; jangan menyalin executable dari server terduga kompromi.
- Ukur RPO/RTO aktual; rekam backup ID, checksum cocok, durasi, sampel data, hasil scan dan penanggung jawab. Target awal: RPO ≤24 jam, RTO ≤8 jam; owner menyetujui bila kebutuhan layanan berbeda.
- Uji jalur alert di luar jam kerja dan daftar kontak/escalation. Audit akses off-account dan lakukan recovery test key—tanpa menampilkan key.

## 4. Alert dan severity

| Severity | Contoh | SLA respons | Eskalasi |
|---|---|---:|---|
| SEV-1 Kritis | webshell, judol/defacement aktif, Super User asing, perubahan origin/DNS, exfiltration, backup terhapus massal | 15 menit | Ketua tim TI + pimpinan + provider/Cloudflare; aktifkan insiden |
| SEV-2 Tinggi | file core berubah tanpa change, cron/PHP baru, login admin anomali, backup harian gagal | 1 jam | Tim TI dan owner aplikasi; batasi akses sambil investigasi |
| SEV-3 Sedang | checksum satu archive gagal, scan terlambat, lonjakan probing tanpa compromise | 4 jam kerja | Operator; perbaiki dan pantau |
| SEV-4 Rendah | kapasitas/retensi mendekati batas, noise rule | 1 hari kerja | Backlog operasi dengan due date |

Alert wajib memuat timestamp UTC, hostname/lingkungan, kategori, severity, path/ID yang **disanitasi**, dan runbook link. Jangan memuat isi file, query, cookie, token, password, atau dump. Kegagalan monitor (heartbeat hilang) adalah SEV-2; jangan menganggap “tidak ada alert” berarti sehat.

## 5. Respons judol, webshell, atau takeover

1. **Deklarasi dan containment:** buka kanal insiden; batasi `/administrator` melalui Cloudflare Access/IP sementara; Managed Challenge/Under Attack hanya sesuai kebutuhan. Jangan login dari endpoint terduga terinfeksi.
2. **Preservasi:** jangan langsung menghapus. Ambil snapshot read-only file, DB, log, daftar proses/cron/user, DNS/Cloudflare event; catat UTC dan SHA-256; simpan off-host dengan chain of custody.
3. **Cabut akses:** nonaktifkan akun asing, putus sesi, blok persistence. Rotasi registrar/Cloudflare/email recovery/cPanel/SSH/FTP/DB/Joomla/SMTP/token dari perangkat bersih. Perubahan password Joomla saat ini tidak dilakukan proaktif, tetapi **wajib saat compromise terkonfirmasi**.
4. **Scope:** cari PHP/webshell di writable dirs, `.htaccess`, `.user.ini`, `auto_prepend_file`, cron, plugin/template, DB article/module/meta/menu, spam sitemap, redirect mobile/referrer, dan akun tersembunyi. Periksa tanggal sebelum indikator pertama.
5. **Eradikasi:** rebuild clean-room; jangan “membersihkan” server in-place sebagai pemulihan final. Hanya migrasikan media/data yang divalidasi; patch root cause sebelum membuka trafik.
6. **Recovery:** restore backup sebelum compromise yang checksum-nya cocok; gunakan credential/key baru; scan offline; uji admin, konten, SEF, upload, mail, log, WAF. Buka trafik bertahap dan pantau ketat 72 jam.
7. **Pelaporan:** simpan timeline, dampak, IOC, keputusan, notifikasi regulator/penegak kebijakan sesuai kewajiban instansi; retrospektif dan perbaikan kontrol.

## 6. Clean-room dan aturan restore

- Pilih titik pulih berdasarkan timeline/IOC, bukan sekadar backup terbaru. Backup dapat mengandung persistence.
- Verifikasi SHA-256 sebelum dekripsi/restore; kegagalan checksum menghentikan restore dan memicu SEV-2.
- Install Joomla/core/extension dari source resmi dan versi didukung. Cocokkan daftar extension dengan inventaris yang disetujui.
- Import DB memakai akun sementara privilege minimum; setelah validasi buat credential produksi baru. Jangan pakai credential dari host kompromi.
- Baseline integritas baru dibuat **setelah** owner menyetujui rilis bersih. Baseline lama dan bukti insiden dipertahankan read-only sesuai retensi legal.
- Backup tidak dianggap berhasil sampai salinan off-account dan restore drill terbukti.

## 7. Checklist penerimaan

- [ ] Seluruh path privat berada di luar document root dan permission diverifikasi.
- [ ] `ps` saat backup tidak menampilkan password; log tidak mengandung credential.
- [ ] Dua backup terbaru lulus `gzip -t` dan SHA-256; satu restore DB mingguan berhasil.
- [ ] Salinan terenkripsi off-account tidak dapat dihapus memakai akun cPanel.
- [ ] Cron harian/mingguan/bulanan dan heartbeat/alert diuji dengan kegagalan terkontrol.
- [ ] Kontak SEV-1 menerima simulasi; bukti non-rahasia disimpan.
- [ ] Clean-room bulanan memenuhi RPO/RTO atau exception owner tercatat.
