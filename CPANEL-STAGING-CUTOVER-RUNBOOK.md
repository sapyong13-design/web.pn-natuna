# Runbook GitHub, Staging cPanel, dan Cutover PN Natuna

Runbook kanonis untuk instalasi pertama rebuild Joomla ke `new.pn-natuna.go.id`, update berikutnya melalui `git pull`, pengujian privat, lalu penggantian website utama `pn-natuna.go.id` dengan rollback cepat.

> GitHub adalah source code deployment. Database dan `configuration.php` tetap dikelola privat di cPanel. Jangan menaruh password, `$secret`, dump SQL, private key, token, IP origin, atau backup di Git, chat, tiket, dan document root publik.

## 1. Arsitektur wajib

```text
Produksi aktif
pn-natuna.go.id
/home/CPANEL_USER/public_html
DB: CPANEL_USER_production

Staging terisolasi
new.pn-natuna.go.id
/home/CPANEL_USER/new.pn-natuna.go.id
DB: CPANEL_USER_pnnatuna_staging
```

- Jangan aktifkan **Share document root** untuk `new.pn-natuna.go.id`.
- Jangan hubungkan staging langsung ke database produksi.
- Staging adalah clone file dan database produksi dengan kredensial DB serta path staging sendiri.
- Pertahankan website dan database produksi lama sampai cutover stabil dan restore telah diuji.

## 2. Model deployment GitHub

Gunakan private source checkout di luar document root:

```text
Source: /home/CPANEL_USER/repos/web.pn-natuna
Staging webroot: /home/CPANEL_USER/new.pn-natuna.go.id
```

Alur update: GitHub branch `continue-joomla-rebuild-polish` → `git pull --ff-only` di checkout privat → build ZIP allowlist → backup staging → extract ke webroot → apply migration registry → clear cache dan QA.

Jangan clone atau pull repo langsung di `public_html` maupun webroot subdomain. Repo memuat docs, tools, dan SQL yang tidak boleh memiliki URL publik.

### 2.1 Instalasi checkout pertama kali

Gunakan cPanel Git Version Control atau Terminal dengan deploy key read-only untuk repo private. Jangan menaruh Personal Access Token pada URL remote atau shell history.

```bash
mkdir -p /home/CPANEL_USER/repos
cd /home/CPANEL_USER/repos
git clone --branch continue-joomla-rebuild-polish --single-branch \
  https://github.com/sapyong13-design/web.pn-natuna.git web.pn-natuna
cd web.pn-natuna
git status --short --branch
git rev-parse HEAD
```

### 2.2 Update source berikutnya

```bash
cd /home/CPANEL_USER/repos/web.pn-natuna
git status --short --branch
git fetch origin
git pull --ff-only origin continue-joomla-rebuild-polish
git rev-parse HEAD
```

Stop jika checkout tidak bersih atau pull bukan fast-forward. Jangan memakai `git reset --hard` atau force pull. Semua perubahan source dibuat di workstation dan dipush ke GitHub; checkout server diperlakukan read-only.

## 3. Artefak deploy dari checkout GitHub

Setelah pull, bangun release dari private checkout:

```bash
cd /home/CPANEL_USER/repos/web.pn-natuna
COMMIT=$(git rev-parse --short HEAD)
mkdir -p /home/CPANEL_USER/private/releases
python3 tools/build-deploy-package.py /home/CPANEL_USER/private/releases/pn-natuna-deploy-$COMMIT.zip
sha256sum /home/CPANEL_USER/private/releases/pn-natuna-deploy-$COMMIT.zip
```

Builder memakai allowlist dan mengecualikan `configuration.php`, `.git`, database, docs, tools, cache, log, backup, archive, dan secret. Verifikasi hash sebelum upload:

```bash
sha256sum /home/CPANEL_USER/private/releases/pn-natuna-deploy-COMMIT.zip
```

Atau Windows:

```powershell
certutil.exe -hashfile C:/path/pn-natuna-deploy-COMMIT.zip SHA256
```

Jangan commit ZIP. Simpan release di `/home/CPANEL_USER/private/releases`. Jika ZIP harus disalin sementara ke webroot untuk File Manager, extract lalu hapus segera.

## 4. Backup sebelum perubahan

### 4.1 Database produksi

`cPanel → phpMyAdmin → database produksi → Export → Custom → SQL`

- Pilih seluruh tabel Joomla.
- Gunakan gzip jika besar.
- Sertakan struktur dan data.
- Simpan off-host dengan akses terbatas.
- Catat waktu, operator, nama database, ukuran, dan SHA-256 tanpa mencatat credential.

### 4.2 File produksi

`cPanel → File Manager → document root produksi → Select All → Compress`

Download backup, verifikasi arsip dapat dibuka, lalu hapus arsip dari hosting. Jangan menyimpan backup di `public_html`.

## 5. Buat subdomain staging

`cPanel → Domains → Create A New Domain`

```text
Domain: new.pn-natuna.go.id
Document root: /home/CPANEL_USER/new.pn-natuna.go.id
Share document root: Off
```

Lalu `cPanel → SSL/TLS Status → new.pn-natuna.go.id → Run AutoSSL`.

Jika DNS dikelola Cloudflare, buat record `new` sesuai origin/provider dan gunakan **Full (strict)**, bukan Flexible. Jangan mempublikasikan IP origin.

## 6. Lindungi staging sebelum dibuka

### 6.1 Directory Privacy

`cPanel → Directory Privacy → /home/CPANEL_USER/new.pn-natuna.go.id`

- Aktifkan **Password protect this directory**.
- Nama area: `PN Natuna Staging`.
- Buat user review khusus dengan password acak kuat.
- Jangan gunakan password Joomla, DB, cPanel, atau produksi.

Uji lewat incognito. Basic Auth harus muncul sebelum Joomla.

### 6.2 Noindex HTTP

Tambahkan hanya pada `.htaccess` staging:

```apache
<IfModule mod_headers.c>
  Header always set X-Robots-Tag "noindex, nofollow, noarchive"
</IfModule>
```

Di Joomla staging: `System → Global Configuration → Site Metadata → Robots → noindex, nofollow`.

Jika directive `Header` menghasilkan HTTP 500, hapus blok itu, pertahankan meta robots, dan minta provider memasang header melalui vhost/panel.

Verifikasi response header:

```text
X-Robots-Tag: noindex, nofollow, noarchive
```

## 7. Instalasi file staging pertama kali

`cPanel → File Manager → /home/CPANEL_USER/new.pn-natuna.go.id`

1. Pull private checkout GitHub dan bangun release ZIP.
2. Copy release ZIP sementara ke document root bila File Manager memerlukannya.
3. Extract langsung ke document root staging.
4. Pastikan tidak terbentuk folder berlapis.
5. Hapus ZIP sementara dari document root setelah extract.

Struktur benar:

```text
new.pn-natuna.go.id/
├── administrator/
├── components/
├── images/
├── libraries/
├── media/
├── modules/
├── plugins/
├── templates/
├── .htaccess
└── index.php
```

Permission umum:

```text
Directory: 755
File: 644
configuration.php: 640 atau 600 jika host mendukung
```

Jangan gunakan `777`.

## 8. Clone database produksi

### 8.1 Buat database dan user staging

`cPanel → Database Wizard`

Contoh nama akhir yang diberi prefix cPanel:

```text
Database: CPANEL_USER_pnnatuna_staging
User: CPANEL_USER_pnnatuna_stage
```

- Gunakan password generator, minimal 20 karakter acak.
- Simpan di password manager.
- Hubungkan user ke database staging.
- Berikan `ALL PRIVILEGES` hanya pada database staging.

### 8.2 Export dan import

1. Export database produksi melalui phpMyAdmin.
2. Pilih database staging yang kosong.
3. `Import → pilih dump produksi → SQL → utf-8`.
4. Pastikan tabel muncul dengan prefix produksi, misalnya `pnn_content`, `pnn_menu`, dan `pnn_users`.

Jika import dibatasi ukuran, naikkan sementara melalui MultiPHP INI Editor atau minta provider mengimpor. Jangan mengunggah dump ke document root.

## 9. Buat configuration.php staging

Paket deploy tidak menyertakan `configuration.php`. Karena database staging adalah clone produksi:

1. Copy `configuration.php` produksi ke document root staging.
2. Pertahankan `$secret` produksi yang cocok dengan database clone.
3. Pertahankan `$dbprefix` produksi.
4. Ubah hanya koneksi DB staging, path log/tmp, dan `live_site`.

```php
public $host = 'localhost'; // atau nilai host DB produksi jika provider berbeda
public $user = 'CPANEL_USER_pnnatuna_stage';
public $password = 'PASSWORD_DB_STAGING';
public $db = 'CPANEL_USER_pnnatuna_staging';
public $dbprefix = 'pnn_'; // harus sama dengan prefix tabel hasil import
public $secret = 'PERTAHANKAN_NILAI_PRODUKSI';
public $live_site = '';
public $log_path = '/home/CPANEL_USER/new.pn-natuna.go.id/administrator/logs';
public $tmp_path = '/home/CPANEL_USER/new.pn-natuna.go.id/tmp';
```

Jangan membuat `$secret` baru setelah clone DB. Field terenkripsi Joomla, termasuk secret MFA, bergantung pada nilai yang cocok. Jangan mengirim nilai aktual ke chat.

Pastikan folder `administrator/logs` dan `tmp` ada serta writable oleh Joomla.

## 10. Cegah efek samping staging

Database clone membawa akun, email, token, modul, dan konfigurasi produksi. Sebelum pengujian:

- Pertahankan Directory Privacy.
- Pastikan HTTPS aktif.
- Nonaktifkan cron staging sampai setiap job ditinjau.
- Jangan jalankan job yang memperbarui data/integrasi produksi.
- Alihkan atau nonaktifkan pengiriman email staging agar tidak mengirim ke pengguna nyata.
- Jangan memasang webhook/API key produksi pada staging.
- Audit akun Super User; wajib MFA dan identitas per operator.

## 11. Terapkan migrasi kanonis

Jangan menjalankan SQL delta satu per satu. Gunakan registry migration.

Gunakan runner dan migrations dari private checkout GitHub:

```text
/home/CPANEL_USER/repos/web.pn-natuna/
├── tools/apply-db-migrations.py
└── database/migrations/
    ├── 20260713_restore_homepage_modules.sql
    ├── 20260714_news_channels_ordering.sql
    ├── 20260715_upsert_homepage_modules.sql
    ├── 20260716_ampuh_directory.sql
    ├── 20260717_ampuh_mainmenu.sql
    └── 20260718_reconcile_ampuh_mainmenu.sql
```

Dari cPanel Terminal:

```bash
python3 /home/CPANEL_USER/repos/web.pn-natuna/tools/apply-db-migrations.py \
  --host localhost \
  --user CPANEL_USER_pnnatuna_stage \
  --database CPANEL_USER_pnnatuna_staging \
  --prefix pnn_ \
  --mysql mysql \
  --migrations /home/CPANEL_USER/repos/web.pn-natuna/database/migrations
```

Jangan menaruh password pada command history. Gunakan mekanisme credential privat provider atau minta provider menjalankan migrasi. Verifikasi output:

```text
Database migrations current: N applied, M already present
```

Jangan menjalankannya terhadap DB produksi saat tahap staging.

## 12. Update staging berikutnya melalui git pull

Setelah instalasi pertama, setiap update mengikuti urutan ini:

1. Catat commit staging aktif dan backup file/DB staging.
2. Jalankan `git status` dan `git pull --ff-only` di checkout privat.
3. Bangun release ZIP baru berdasarkan commit hasil pull.
4. Aktifkan Joomla Offline bila update menyentuh banyak file atau migrasi.
5. Extract release ke webroot staging dengan overwrite.
6. Pastikan `configuration.php` staging tetap ada dan tidak berubah.
7. Jalankan migration registry terhadap DB staging.
8. Hapus ZIP sementara dari webroot.
9. Clear Joomla cache, expired cache, OPcache/provider cache, dan Cloudflare cache staging.
10. Jalankan QA dan catat commit deployed beserta SHA-256 release.

Jangan memakai `--reapply` pada update normal kecuali migrasi tertentu secara eksplisit memerlukannya dan backup tersedia.

Sebelum deployment pertama pada shared cPanel, pull versi terbaru lalu jalankan self-test non-destruktif:

```bash
cd /home/CPANEL_USER/repos/web.pn-natuna
git pull --ff-only origin continue-joomla-rebuild-polish
python3 tools/deploy-cpanel.py --self-test
```

Output wajib:

```text
cPanel deploy self-test: ok (Python 3.6.8)
```

Self-test tidak melakukan pull kedua, copy file, backup, atau akses database. Ia hanya memverifikasi runtime Python, command `git`/`mysql`/`mysqldump`, dan deployment allowlist. Jangan jalankan `--reset-database` jika self-test gagal.

### Perintah harian paling sederhana

Setelah preset tersedia dan file privat/marker sudah dibuat, setiap update staging cukup:

```bash
cd ~/repos/web.pn-natuna
python3 tools/deploy-cpanel.py --full-staging
```

Preset membaca nama database dari `~/new.pn-natuna.go.id/configuration.php`, lalu memakai credential `~/private/pn-natuna-db/staging.cnf` dan dump `~/private/pn-natuna-db/current.sql.gz`. Password tidak dibaca dari `configuration.php` dan tidak ditampilkan.

### 12.1 One-command full staging reset, hanya sebelum go-live

Selama `new.pn-natuna.go.id` belum menjadi produksi dan belum menyimpan konten server yang perlu dipertahankan, updater repo dapat men-deploy seluruh file dan me-reset DB staging dari dump privat:

```bash
cd /home/CPANEL_USER/repos/web.pn-natuna
python3 tools/deploy-cpanel.py \
  --target /home/CPANEL_USER/new.pn-natuna.go.id \
  --reset-database \
  --database CPANEL_USER_pnnatuna_staging \
  --mysql-config /home/CPANEL_USER/private/pn-natuna-db/staging.cnf \
  --database-dump /home/CPANEL_USER/private/pn-natuna-db/current.sql.gz
```

Persiapan marker wajib sekali saja:

```bash
printf '%s\n' 'new.pn-natuna.go.id' > /home/CPANEL_USER/new.pn-natuna.go.id/.pn-natuna-staging
chmod 600 /home/CPANEL_USER/new.pn-natuna.go.id/.pn-natuna-staging
```

Credential file privat:

```ini
[client]
host=localhost
user=CPANEL_USER_pnnatuna_stage
password="PASSWORD_DB_STAGING"
default-character-set=utf8mb4
```

Jangan menaruh `database=` pada `[client]`; `mysqldump` tidak menerima option singular tersebut. Updater memberikan nama DB eksplisit kepada `mysql` dan `mysqldump`. Kunci file:

```bash
chmod 600 /home/CPANEL_USER/private/pn-natuna-db/staging.cnf
```

Updater menjalankan `git pull --ff-only`, guard marker/hostname, menolak nama DB tanpa kata `staging`, membuat backup DB, me-reset DB dari `.sql.gz`, menyinkronkan file, membersihkan cache, dan health-check `/` serta `/ampuh`.

Kebijakan sync:

- `templates`, `plugins`, `modules`, `components`, core Joomla, dan direktori code allowlist dimirror; file code yang telah dihapus/rename di Git ikut dihapus dari webroot.
- `images`, `files`, dan `media` di-copy tanpa delete agar upload Joomla yang tidak ada di Git tetap aman.
- `configuration.php`, `administrator/logs`, `cache`, `tmp`, SQL, docs, tools, backup, dan secret tidak pernah disalin dari repo.
- Daftar file memakai allowlist yang sama dengan `tools/build-deploy-package.py`.

Hentikan mode `--reset-database` setelah staging menjadi website utama atau mulai menerima konten penting. Setelah go-live, update DB hanya melalui migration baru yang kompatibel dengan MariaDB dan sudah diuji.
## 13. PHP dan cache

`cPanel → MultiPHP Manager`: gunakan PHP yang didukung Joomla, target saat ini PHP 8.3.

`cPanel → MultiPHP INI Editor`:

```ini
display_errors = Off
log_errors = On
allow_url_include = Off
memory_limit = 256M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 180
```

Bersihkan cache Joomla, OPcache/provider cache, dan Cloudflare cache setelah file/migrasi berubah.

## 14. QA staging wajib

Uji dengan Basic Auth aktif:

```text
https://new.pn-natuna.go.id/
https://new.pn-natuna.go.id/administrator/
https://new.pn-natuna.go.id/ampuh
```

### Fungsional

- Homepage, Profil Pengadilan, Layanan Publik, Berita, Pengumuman, Transparansi, Kontak.
- Login/logout administrator dan MFA.
- SEF URL, breadcrumb, menu desktop/mobile, upload media.
- Form/email hanya menuju target staging yang aman.
- Dark mode, keyboard, focus, reduced motion.
- Tidak ada HTTP 500, redirect loop, mixed content, atau error fatal.

### AMPUH

- 27 GOBI, 82 checklist, 405 sub-checklist, 2.043 dokumen.
- Search, highlight, reset, hasil kosong, filter desktop, select mobile.
- Rail desktop dan panah 44×44; sticky toolbar tidak menutup navbar.
- URL Drive valid terbuka di tab baru.
- Sub-checklist tanpa URL tidak menampilkan placeholder.
- Tidak ada horizontal overflow pada 320/390/760/761/1280/1440 px.

### Keamanan

- Basic Auth muncul sebelum Joomla.
- `X-Robots-Tag` dan meta noindex aktif.
- SSL valid.
- `configuration.php`, `.git`, dump, ZIP, backup, tools, dan private directory tidak dapat diunduh.
- PHP error display mati; log tersedia privat.

Catat bukti bertanggal. Instruksi tanpa bukti bukan verifikasi.

## 15. Persiapan cutover

Jika produksi tetap menerima artikel/perubahan selama staging, jangan memakai clone staging lama sebagai sumber final. Pada maintenance window:

1. Aktifkan maintenance produksi.
2. Backup file dan database produksi terbaru.
3. Buat database cutover baru, misalnya `CPANEL_USER_pnnatuna_v2`.
4. Import fresh dump produksi ke DB v2.
5. Terapkan seluruh migrasi kanonis ke DB v2.
6. Copy `configuration.php` staging sebagai basis, lalu arahkan ke DB v2.
7. Pertahankan `$secret` yang cocok dengan fresh clone produksi.
8. Smoke test melalui hostname sementara/provider preview yang mempertahankan Host/SNI.
9. Baru pindahkan domain utama.

Jangan overwrite database produksi lama. Jangan hapus database lama.

## 16. Metode cutover

### Metode A, ubah document root

Pilihan terbaik jika provider mengizinkan:

```text
pn-natuna.go.id
old root: /home/CPANEL_USER/public_html
new root: /home/CPANEL_USER/new.pn-natuna.go.id
```

Keuntungan: tidak memindahkan ribuan file dan rollback cepat. Pastikan path `log_path`/`tmp_path` tetap cocok dengan root aktual.

### Metode B, tukar folder

Jika primary domain wajib `public_html`:

```text
public_html → public_html_old_YYYYMMDD
new.pn-natuna.go.id → public_html
```

Sesuaikan:

```php
public $log_path = '/home/CPANEL_USER/public_html/administrator/logs';
public $tmp_path = '/home/CPANEL_USER/public_html/tmp';
public $live_site = '';
```

Gunakan rename folder dalam maintenance window, bukan copy file satu per satu di atas website aktif.

## 17. Verifikasi go-live

Sebelum membuka maintenance:

- Domain utama HTTPS 200 dan certificate valid.
- Homepage serta route utama tampil.
- `/administrator` login/MFA berfungsi.
- `/ampuh` memuat data dan interaksi lengkap.
- Upload media, form, email, cron yang disetujui, cache, dan log berfungsi.
- Hapus `X-Robots-Tag noindex` dan ubah Joomla Robots ke index/follow hanya pada domain utama.
- Directory Privacy staging tetap aktif sampai staging dipensiunkan.
- Cloudflare memakai Full (strict), WAF/rate limit diuji, dan admin dilindungi sesuai `SECURITY-DEPLOYMENT-HANDOFF.md`.

## 18. Rollback

Pertahankan:

```text
/home/CPANEL_USER/public_html_old_YYYYMMDD
CPANEL_USER_pnnatuna_old
```

Jika gagal:

1. Aktifkan maintenance.
2. Kembalikan document root lama atau rename folder lama menjadi `public_html`.
3. Kembalikan `configuration.php` ke DB lama.
4. Purge Joomla, OPcache/provider, dan Cloudflare cache.
5. Verifikasi homepage, admin, dan route penting.
6. Catat penyebab dan bukti; jangan menghapus artefak sebelum analisis.

Jangan hapus file/DB lama sampai website baru stabil beberapa hari dan restore off-site telah diuji.

## 19. Checklist operator

### Staging

- [ ] Backup file produksi terunduh dan diuji.
- [ ] Backup DB produksi terunduh dan diuji.
- [ ] `new.pn-natuna.go.id` memakai root terpisah.
- [ ] AutoSSL/Full (strict) aktif.
- [ ] Directory Privacy aktif.
- [ ] Header dan meta noindex aktif.
- [ ] Private Git checkout berada di luar document root; branch dan commit tercatat.
- [ ] Source update memakai `git pull --ff-only` pada checkout bersih.
- [ ] ZIP allowlist dibangun dari commit tersebut, diextract, lalu dihapus dari webroot.
- [ ] DB staging terpisah dan user hanya punya akses DB staging.
- [ ] `configuration.php` mempertahankan `$secret`/`$dbprefix`; hanya DB/path staging berubah.
- [ ] Cron, email, webhook, dan integrasi staging diamankan.
- [ ] Semua migrasi kanonis tercatat applied/already present.
- [ ] QA desktop/mobile/admin/AMPUH lulus.

### Cutover

- [ ] Maintenance window disetujui.
- [ ] Fresh backup produksi dibuat.
- [ ] Fresh DB cutover dibuat dari produksi terbaru dan dimigrasikan.
- [ ] Folder dan DB lama dipertahankan.
- [ ] Cutover document root/folder selesai.
- [ ] Smoke test utama lulus.
- [ ] Noindex/Directory Privacy tidak terbawa ke domain utama.
- [ ] Cache dipurge.
- [ ] Monitoring/log/backup berjalan.
- [ ] Rollback diuji atau setidaknya dry-run dengan bukti.

## 20. Referensi

- `HANDOFF.md`
- `SECURITY-DEPLOYMENT-HANDOFF.md`
- `SECURITY-BACKUP-MONITORING-RUNBOOK.md`
- `tools/build-deploy-package.py`
- `tools/apply-db-migrations.py`
- `database/migrations/`
