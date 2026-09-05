# Handoff Keamanan Produksi — Cloudflare + cPanel

> **Status:** runbook wajib sebelum go-live. Isi dokumen ini adalah tindakan yang harus dilakukan; **bukan bukti bahwa setting sudah aktif**. Jangan tempel token, password, private key, cookie, dump DB, atau nilai rahasia ke repo/tiket/chat.

## 0. Urutan wajib dan stop condition

| Prioritas | Tindakan | Stop condition |
|---|---|---|
| P0 | Anggap semua secret yang pernah masuk Git/backup/chat sudah bocor; inventaris, rotasi, purge Git | Jangan produksi sebelum credential aktif berbeda dari nilai terekspos |
| P0 | Backup bersih, patch Joomla/extension, hapus akun asing, MFA administrator | Jangan membuka `/administrator` ke publik sebelum selesai |
| P0 | Cloudflare proxied DNS, Full (strict), origin certificate, origin lock | Jangan mengganti DNS final sebelum uji HTTPS origin berhasil |
| P0 | Managed WAF + aturan admin/login/API + rate limit | Jangan memakai Block global sebelum melihat event dan menguji allowlist |
| P1 | CSP `Report-Only`, perbaikan violation, lalu enforce bertahap | Jangan enforce bila admin/editor/login rusak |
| P1 | Monitoring, file integrity, backup off-site, drill IR | Go-live hanya bila alert dan restore test terbukti |

Simpan bukti non-rahasia: waktu, operator, screenshot setting (nilai sensitif ditutup), ID rule, hasil command, dan tiket perubahan. Siapkan rollback pada setiap langkah.

Jadwal operasional rinci ada di `SECURITY-BACKUP-MONITORING-RUNBOOK.md`; wrapper DB `tools/secure-db-backup.sh` dan scanner `tools/security/security_cli.py` harus disalin ke lokasi CLI privat di luar document root, bukan ikut deployment web.

### 0.1 Batas perubahan repo dan dashboard

- **Actionable di repo:** hapus secret/dump dari tracking dan deployment, pertahankan hanya template tanpa nilai, kunci installer/tool publik, dokumentasikan baseline extension, dan jalankan pemeriksaan artefak sebelum deploy.
- **Manual di dashboard/host:** Cloudflare Access/WAF/rate limit/DNS/TLS, cPanel Directory Privacy/firewall, MFA dan pemutusan sesi Joomla. Tidak ada item manual yang dianggap aktif sampai operator menyimpan bukti dashboard serta hasil uji bertanggal.
- Jangan menaruh email operator, IP origin/admin, Access service token, recovery code, password, atau API token di repo. Gunakan dashboard, password manager organisasi, dan daftar IP bernama bila plan mendukung.

## 1. Preflight P0: containment, akun, secret, dan Git

### 1.1 Jika ada indikasi judol/defacement/admin takeover

1. Aktifkan **Cloudflare > Security > Settings > Under Attack mode** hanya saat serangan aktif. Alternatif lebih aman: custom rule Managed Challenge untuk traffic mencurigakan.
2. Di cPanel, buat halaman maintenance statis bila perlu; jangan menghapus artefak sebelum salinan forensik.
3. Ambil snapshot read-only: file webroot, database, access/error log, Joomla action log, daftar user/admin, cron, `.htaccess`, DNS, dan Cloudflare Security Events. Catat waktu UTC dan SHA-256 arsip. Simpan off-host terbatas akses.
4. Putus sesi: Joomla **Users > Manage** nonaktifkan akun asing; ganti password semua Super Users; periksa **Users > User Actions Log**. Nonaktifkan extension/template tidak dikenal. Jangan login dari perangkat yang diduga terinfeksi.
5. Rotasi berurutan: registrar/DNS dan Cloudflare; email pemulihan; cPanel/SSH/FTP; DB; Joomla Super Users/API token; SMTP; integrasi pihak ketiga; key backup. Gunakan password manager dan MFA. Cabut token lama setelah nilai baru terpasang dan diuji.
6. Rebuild dari source + DB yang diketahui bersih. Jangan hanya menghapus halaman judol: cari persistence di cron, user, plugin, PHP writable directory, `.user.ini`, `.htaccess`, prepend file, dan database content/meta.


### 1.2 Jika password Joomla saat ini belum boleh dirotasi

Password yang pernah terekspos **tetap dianggap diketahui penyerang**. Kontrol kompensasi berikut hanya mengurangi peluang pemakaian; tidak membuat password aman dan tidak menggantikan rotasi:

1. Sebelum membuka admin, wajibkan outer gate Cloudflare Access atau cPanel Directory Privacy, MFA Joomla per operator, Managed Challenge/rate limit, dan origin lock.
2. Putus semua sesi Joomla aktif; revoke/regenerate API token, remember-me cookie, app password, service token, recovery link, dan secret integrasi yang dapat memberi akses setara. Rotasi secret non-password yang boleh diubah, termasuk Cloudflare/cPanel/DB/SMTP bila pernah terekspos.
3. Pisahkan akun: akun harian bukan Super User; setiap operator memakai identitas sendiri; akun lama/bersama/asing diblokir. Buat dua akun break-glass organisasi dengan MFA berbeda, recovery code offline tersegel, dan alert pemakaian.
4. Audit login dan User Actions Log setiap hari sampai password dirotasi; alert login sukses/gagal berulang, perubahan user/MFA, instalasi extension, perubahan template/config, dan publish massal.

**Residual risk eksplisit:** credential stuffing atau login langsung tetap mungkin bila outer gate/MFA salah konfigurasi, sesi/token lama masih berlaku, perangkat operator terinfeksi, atau jalur origin melewati Cloudflare. Password terekspos tetap merupakan secret kompromi. Catat owner dan tanggal rotasi terdekat; jangan menutup insiden atau menyatakan risiko selesai sebelum password diganti dari perangkat bersih.
### 1.3 Inventaris secret tanpa menampilkannya

Periksa history dan working tree dari workstation tepercaya. Command berikut hanya menampilkan nama file kandidat, bukan baris/nilai yang cocok. Tetap perlakukan nama file dan terminal log sebagai data terbatas:

```bash
git log --all --name-only --pretty=format: | sort -u
git grep -l -I -E '(password|passwd|secret|token|api[_-]?key|private[_-]?key)' -- ':!database/*.sql'
```

Periksa khusus `configuration.php`, `.env*`, dump SQL, archive deployment, log, backup, private key, dan credential script. Jangan kirim hasil mentah.

### 1.4 Rotasi dan purge Git

Rotasi **lebih dahulu**; purge history tidak membatalkan secret. Owner repo menjalankan dari clone mirror terisolasi setelah backup/tag koordinasi:

```bash
git clone --mirror <REPOSITORY_URL> repo-purge.git
cd repo-purge.git
git filter-repo --path configuration.php --path-glob 'database/*.sql' --path-glob '*.env*' --invert-paths
git push --force --mirror
```

Sesuaikan daftar path berdasarkan audit; `git filter-repo` diperlukan dan operasi menulis ulang semua commit/tag. Jika secret berada di file yang harus dipertahankan, gunakan replacement map lokal yang tidak dikomit. Setelah force-push: hapus fork/cache/Actions artifact/release asset terkait bila memiliki akses, minta semua kontributor re-clone (bukan merge history lama), aktifkan secret scanning/push protection bila plan mendukung, lalu verifikasi:

```bash
git rev-list --objects --all
git log --all -- configuration.php
```

**Rollback purge:** mirror backup hanya untuk pemulihan data, bukan untuk mengaktifkan kembali credential lama. Jika rewrite salah, owner dapat force-push mirror backup, memperbaiki filter, lalu mengulang. Catat commit boundary dan siapa yang re-clone.

## 2. cPanel dan Joomla P0

Dashboard path dapat berbeda menurut tema/provider.

1. **cPanel > MultiPHP Manager:** pilih PHP versi Joomla yang masih didukung; jangan jalankan versi EOL.
2. **cPanel > MultiPHP INI Editor:** produksi `display_errors=Off`, `log_errors=On`, `allow_url_include=Off`; batasi `upload_max_filesize`/`post_max_size` sesuai kebutuhan editor. Jangan menonaktifkan fungsi PHP secara buta karena host/extension berbeda.
3. **cPanel > SSL/TLS Status:** pasang AutoSSL publik atau **Cloudflare > SSL/TLS > Origin Server > Create certificate**. Origin CA hanya dipercaya Cloudflare, bukan browser langsung. Simpan private key hanya di cPanel/key store, mode ketat, tidak di repo.
4. **Outer gate admin:** pilih Cloudflare Access pada §2.1. Jika plan/host tidak mendukungnya, gunakan **cPanel > Directory Privacy** pada direktori `administrator`: buat user Basic Auth organisasi yang berbeda dari akun Joomla, simpan di password manager, dan uji login, MFA, editor, media/AJAX, logout, serta error page. Jangan melindungi seluruh webroot. Basic Auth lewat HTTPS saja; revoke user saat operator keluar. Provider tertentu tidak menerapkan Directory Privacy konsisten pada rewrite Joomla—verifikasi respons dari browser privat dan `curl -I`, jangan berasumsi.
5. **cPanel > Cron Jobs:** hanya cron terdokumentasi; absolute path; jangan taruh secret di command. Uptime check harus menuju endpoint publik read-only khusus, bukan `/administrator`.
6. **cPanel > Backup/Backup Wizard** dan provider backup: jadwal pada §8; jangan simpan backup di `public_html`.
7. **Joomla Administrator > System > Update:** Joomla core dan extension didukung; hapus extension/template tidak dipakai.
8. **Users > Manage:** satu akun per operator, privilege minimum, tidak memakai username umum `admin`, tidak berbagi akun.
9. **Users > Manage > [user] > Multi-factor Authentication:** wajib MFA untuk semua Super Users; ideal WebAuthn/passkey atau TOTP dengan recovery code offline.
10. **System > Global Configuration:** HTTPS paksa, error reporting produksi, logging cukup; pastikan `/api/index.php` hanya dipakai bila benar-benar dibutuhkan. Nonaktifkan plugin Web Services/API token yang tidak diperlukan.
11. Integrasikan **Cloudflare Turnstile** pada login/form publik memakai extension Joomla tepercaya dan dipelihara. Turnstile melengkapi, bukan mengganti MFA/rate limit. Secret key hanya di konfigurasi server; uji keyboard, no-JS/error, dan aksesibilitas.

### 2.1 Cloudflare Access untuk `/administrator` (manual dashboard)

1. **Zero Trust > Settings > Authentication:** hubungkan IdP organisasi dengan MFA; jangan mengandalkan one-time PIN ke mailbox yang juga menjadi recovery Joomla.
2. **Access > Applications > Add an application > Self-hosted:** hostname produksi, path `/administrator*`, session duration pendek (contoh 8 jam atau sesuai kebijakan). Pastikan aplikasi mencakup `/administrator`, trailing slash, subpath, dan query; uji URL langsung.
3. Policy urutan: `Allow` hanya grup admin organisasi; `Block` semua identitas lain. Jangan membuat `Bypass` publik. Service token hanya untuk machine endpoint terpisah, bukan browser admin; scope hostname/path exact, expiry pendek, dan rotate/revoke melalui dashboard.
4. Aktifkan log Access; uji operator sah, non-anggota, incognito, session expiry, logout IdP, dan akses direct-origin. Access tidak menggantikan MFA Joomla, WAF, atau origin lock.
5. **Break-glass:** dua operator memegang akses dashboard Cloudflare dengan MFA hardware/recovery terpisah. Simpan prosedur offline untuk menonaktifkan policy berdasarkan application/policy ID. Sebelum perubahan, satu operator tetap login melalui jalur kontrol dan operator kedua menguji sesi baru.

**Rollback darurat:** dari dashboard Cloudflare, disable policy/application terkait berdasarkan ID (jangan delete), lalu sementara aktifkan Directory Privacy atau IP/VPN allowlist yang sudah diuji. Jika Cloudflare tidak dapat diakses, provider boleh membuka origin hanya ke IP/VPN break-glass bertanggal kedaluwarsa; jangan membuat origin publik. Setelah pulih, ekspor log, perbaiki policy, aktifkan kembali Access, uji, lalu cabut ACL sementara.

### 2.2 Sesi, installer, dan extension

- Setelah perubahan akun/MFA atau insiden, gunakan Joomla session management/database procedure versi yang didukung untuk logout semua user; lakukan saat maintenance dan backup DB lebih dahulu. Revoke token pada setiap akun/integrasi, clear remember-me tokens, lalu verifikasi cookie lama tidak dapat membuka admin. Jangan menjalankan SQL generik tanpa memeriksa prefix/schema versi aktif.
- Hapus direktori/file installer dan paket instalasi tersisa setelah deploy. **System > Global Configuration > Permissions**: hanya Super User terpisah boleh install; nonaktifkan installer-from-web dan upload package bila operasional tidak membutuhkan. Jangan meninggalkan file manager, Adminer/phpMyAdmin, restore script, atau scanner pada URL publik.
- Extension allowlist harus mencatat nama, versi, vendor/source resmi, owner bisnis, tanggal review, dan alasan. Hapus extension/template/plugin di luar daftar setelah backup dan dependency check; update hanya dari channel resmi dengan checksum/signature bila tersedia. Review bulanan dan sebelum setiap deploy; review darurat saat advisory Joomla/vendor.

## 3. Cloudflare DNS dan TLS P0

### 3.1 DNS proxied

**Cloudflare dashboard > pilih zone > DNS > Records**:

- Set record web apex dan `www` ke **Proxied** (awan oranye).
- Mail (`MX`, host SMTP/IMAP/POP), SSH/FTP/cPanel jangan diproxy sebagai HTTP; gunakan hostname terpisah dan batasi IP/VPN.
- Hapus record lama (`A`, `AAAA`, wildcard, historical subdomain) yang membocorkan origin. Periksa sumber kebocoran lain: certificate transparency, email header, old DNS, Git, monitoring.
- Aktifkan **DNS > Settings > DNSSEC**, lalu pasang DS di registrar dan verifikasi sebelum menutup perubahan.

Verifikasi publik:

```bash
dig +short example.go.id A
dig +short www.example.go.id A
dig +short example.go.id AAAA
curl -sSvo /dev/null https://example.go.id/
```

Hasil A/AAAA web harus Cloudflare, bukan origin. Header respons biasanya memuat `server: cloudflare` dan `cf-ray`; jangan menjadikannya satu-satunya bukti.

**Rollback:** ubah record ke DNS-only hanya dalam insiden Cloudflare yang terkonfirmasi dan setelah origin firewall sementara aman; DNS-only membuka origin dan melewati WAF.

### 3.2 Full (strict), Origin CA, edge certificates

1. Pasang origin certificate valid untuk apex + `www` di cPanel (**SSL/TLS > Manage SSL Sites**). AutoSSL publik lebih mudah untuk akses darurat langsung; Origin CA cocok bila origin selalu lewat Cloudflare.
2. Uji origin dengan hostname/SNI tanpa mempublikasikan IP:

```bash
curl --resolve example.go.id:443:ORIGIN_IP -sSvo /dev/null https://example.go.id/
openssl s_client -connect ORIGIN_IP:443 -servername example.go.id </dev/null
```

Jangan menyalin output private key; certificate chain dan hostname harus valid untuk model certificate yang dipilih.
3. **Cloudflare > SSL/TLS > Overview > Configure:** pilih **Full (strict)**. Jangan Flexible atau Full non-strict.
4. **SSL/TLS > Edge Certificates:** Always Use HTTPS On; Automatic HTTPS Rewrites setelah uji; minimum TLS 1.2 bila kompatibel; TLS 1.3 On. Aktifkan HSTS terakhir, awal `max-age=300`, tanpa `includeSubDomains`/`preload`; naikkan bertahap setelah seluruh subdomain HTTPS.

**Rollback:** kembalikan HSTS policy sebelum max-age besar; untuk kegagalan 526, perbaiki certificate/chain/SNI origin—jangan menetapkan Flexible. Full non-strict hanya rollback sangat singkat dengan tiket insiden.

### 3.3 Authenticated Origin Pulls (AOP)

**SSL/TLS > Origin Server > Authenticated Origin Pulls** (nama/menu dan zone/per-hostname certificate bergantung plan/UI). AOP butuh konfigurasi Apache yang dapat memverifikasi client certificate. Shared cPanel sering tidak memberi akses vhost/CA setting; minta provider mengonfirmasi dukungan tertulis.

- Pilihan dasar: Cloudflare shared AOP certificate memastikan client berasal dari jaringan Cloudflare.
- Zone/per-hostname customer certificate memberi isolasi lebih kuat bila plan/API/host mendukung.
- Pasang CA verification di origin dahulu, uji dari Cloudflare dan direct, baru aktifkan AOP di dashboard.

**Rollback:** nonaktifkan AOP di dashboard dan origin verification dalam change window yang sama. Jangan meninggalkan satu sisi aktif karena menghasilkan 400/403/525.

## 4. Kunci origin P0

AOP tidak menggantikan firewall IP: shared certificate dapat berasal dari zone Cloudflare lain. Terapkan keduanya bila memungkinkan.

1. Di firewall provider/cPanel host, izinkan TCP 80/443 hanya dari daftar resmi **Cloudflare IPv4/IPv6**; izinkan IP monitoring khusus hanya jika benar-benar perlu. Sumber resmi: <https://www.cloudflare.com/ips/>.
2. Port cPanel/WHM/SSH/SFTP hanya IP kantor/VPN; matikan FTP plaintext.
3. Bila shared hosting tidak menyediakan firewall, minta provider menerapkan ACL. Apache `.htaccess` berdasarkan IP Cloudflare membantu tetapi bukan pengganti network firewall dan harus sinkron dengan daftar resmi.
4. Jangan mengandalkan header `CF-Connecting-IP` dari koneksi direct; percaya header tersebut hanya setelah koneksi dibatasi ke Cloudflare.

### 4.1 Refresh daftar IP Cloudflare tanpa lockout

Jangan commit daftar CIDR statis dan jangan menjalankan script yang langsung menimpa firewall shared-hosting. Setiap bulan, serta setelah notifikasi Cloudflare, operator mengambil `https://www.cloudflare.com/ips-v4` dan `https://www.cloudflare.com/ips-v6` dari workstation tepercaya, membandingkan dengan export ACL aktif, lalu provider/firewall admin menerapkan perubahan sebagai **add-before-remove**. Validasi format CIDR, HTTPS certificate, daftar tidak kosong, dan minimal satu CIDR IPv4 serta IPv6 sebelum perubahan. Simpan checksum/waktu sumber, bukan IP origin atau credential.

Urutan aman: export ACL; tambah CIDR baru; uji hostname melalui Cloudflare via IPv4/IPv6; uji direct origin tetap ditolak; baru hapus CIDR usang. Operator kedua mempertahankan console provider/WHM terbuka. Bila uji gagal, restore export melalui console out-of-band. Karena kemampuan cPanel/provider belum terbukti, repo sengaja tidak menyediakan script firewall otomatis yang dapat mengunci origin.

Verifikasi dari host di luar allowlist:

```bash
curl --connect-timeout 10 --resolve example.go.id:443:ORIGIN_IP -sSvo /dev/null https://example.go.id/
curl -sSvo /dev/null https://example.go.id/
```

Direct origin harus timeout/deny; hostname Cloudflare harus 200/redirect yang diharapkan. Uji IPv4 dan IPv6. **Rollback:** simpan export ACL; pulihkan rule sebelumnya via console provider, bukan melalui koneksi yang sedang diblok.

## 5. WAF, bot, dan rate limiting P0

Menu Cloudflare baru umumnya **Security > Security rules**; UI lama dapat menampilkan **Security > WAF > Custom rules / Rate limiting rules / Managed rules**. Kapabilitas, jumlah rules, regex, Bot Management, action, counting characteristic, dan duration bergantung plan. Gunakan operator `starts_with`/`eq` berikut agar kompatibel luas; bila field/action ditolak UI, gunakan varian yang ditawarkan plan dan dokumentasikan deviasi.

### 5.1 Managed rules

**Security > WAF > Managed rules**:

- Aktifkan **Cloudflare Managed Ruleset** yang tersedia; Free memakai Free Managed Ruleset subset.
- Aktifkan **Cloudflare OWASP Core Ruleset** bila plan mendukung. Mulai sensitivity rendah/score threshold konservatif atau Log/Simulate bila tersedia; tinjau Security Events 24–72 jam; tune per rule ID/path, bukan mematikan ruleset.
- Jangan cache `/administrator`, login, API, atau respons authenticated. Buat **Caching > Cache Rules > Bypass** untuk `starts_with(http.request.uri.path, "/administrator") or starts_with(http.request.uri.path, "/api/")` dan path login aktual.

### 5.2 Custom rules — urutkan allow sempit sebelum challenge/block

Untuk plan yang mendukung named IP list, buat daftar `joomla_admin_ips` **di dashboard/API terproteksi**, isi dengan IP/VPN nyata, dan batasi hak edit. Nilai tidak masuk repo. Jika plan tidak mendukung named list, masukkan IP nyata langsung melalui expression builder dashboard dan simpan ekspor rule pada vault terbatas, bukan repo. Jangan aktifkan allowlist sebelum dua operator dan jalur rollback diuji.

**R1 Admin allow known operator (opsional, IP stabil/VPN):**

```text
(starts_with(http.request.uri.path, "/administrator")) and
(ip.src in $joomla_admin_ips)
```

Action: `Skip` hanya komponen yang diperlukan (mis. custom admin challenge/rate rule); **jangan skip Managed WAF global** tanpa alasan. Biarkan audit logging.

**R2a Protect administrator tanpa allowlist IP (default paling aman untuk IP dinamis):**

```text
starts_with(http.request.uri.path, "/administrator")
```

Action: `Managed Challenge`. Gunakan aturan ini bila admin memakai IP dinamis dan R1 tidak dibuat. Jangan ubah menjadi `Block`, karena itu akan memblokir semua admin.

**R2b Protect administrator dengan allowlist (hanya jika R1 memakai IP/VPN nyata dan break-glass sudah diuji):**

```text
(starts_with(http.request.uri.path, "/administrator")) and
not (ip.src in $joomla_admin_ips)
```

Action awal: `Managed Challenge`. Pertimbangkan `Block` non-allowlist hanya setelah VPN/IP stabil, second operator, dan rollback diuji. Jangan memakai country-only allow sebagai kontrol tunggal. Bila named list tidak tersedia, bangun ekuivalen di dashboard memakai nilai nyata; jangan menyalin placeholder ke rule.

**R3 Challenge suspicious login/API writes:**

```text
(
  starts_with(http.request.uri.path, "/administrator") or
  starts_with(http.request.uri.path, "/api/")
) and
(http.request.method in {"POST" "PUT" "PATCH" "DELETE"})
```

Action: `Managed Challenge` untuk browser login; untuk machine API challenge dapat merusak client—gunakan service token/mTLS/IP allowlist atau rate limit, lalu Block yang tidak terotorisasi. Jika Joomla API tidak digunakan, block `/api/` di origin dan edge.

**R4 Block common probes (gunakan hanya path yang pasti tidak dipakai):**

```text
http.request.uri.path eq "/.env" or
starts_with(http.request.uri.path, "/.git/") or
http.request.uri.path eq "/xmlrpc.php" or
starts_with(http.request.uri.path, "/wp-admin") or
starts_with(http.request.uri.path, "/wp-login.php")
```

Action: `Block`. Joomla bukan WordPress; probe tersebut tidak valid. Origin tetap harus menolak dotfiles/backup/source map sensitif.

**R5 Bot policy:** aktifkan **Security > Bots > Bot Fight Mode** bila tersedia. Super Bot Fight Mode/Bot Management bersifat plan-dependent. Mulai challenge, tinjau false positive search engine dan accessibility tooling. Jangan memblokir verified bots secara global; verifikasi di Security Events, bukan hanya User-Agent yang dapat dipalsukan.

### 5.3 Rate limiting rules

Cloudflare **Security > Security rules > Create rule > Rate limiting rules**. Plan menentukan jumlah rule, period, mitigation timeout, custom counting expression, dan characteristic. Baseline berikut perlu tuning terhadap traffic sah:

**RL1 `/administrator` dan login POST:**

```text
(starts_with(http.request.uri.path, "/administrator")) and
(http.request.method eq "POST")
```

Count by IP bila itu satu-satunya opsi plan. Baseline: **5 request / 60 detik**, mitigation **Managed Challenge 10 menit** (atau Block jika plan tidak menawarkan challenge). IP kantor NAT dapat menggabungkan banyak operator; pantau dan sesuaikan.

**RL2 API write (hanya bila API aktif):**

```text
(starts_with(http.request.uri.path, "/api/")) and
(http.request.method in {"POST" "PUT" "PATCH" "DELETE"})
```

Baseline: **30 request / 60 detik per IP**, Block 10 menit. Sesuaikan ke client nyata; API machine harus punya auth dan allowlist. Plan rendah dengan satu rule: prioritaskan RL1, lalu blok API tidak terpakai di origin.

**RL3 public form/search abuse (bila endpoint teridentifikasi):** exact path + `POST`; baseline 10/60 detik, Managed Challenge. Jangan rate-limit seluruh `/` atau semua GET karena merusak crawler, NAT, dan uptime.

Cron dan uptime:

- `cron-refresh-instansi.php` seharusnya dijalankan via cPanel CLI, bukan URL publik. Jika masih HTTP, migrasikan ke CLI; sementara gunakan path exact + secret-independent source IP allowlist/service authentication. Jangan `Skip` berdasarkan User-Agent.
- Uptime gunakan `GET /` atau health endpoint read-only, frekuensi wajar; allow IP provider hanya bila challenge mengganggu. Tetap jalankan Managed WAF bila mungkin.

### 5.4 Rollout dan verifikasi WAF

1. Ambil baseline Security Events 24 jam.
2. Pasang satu rule, Managed Challenge/Log bila tersedia; uji admin, editor media, save article, login gagal/berhasil, API/cron/uptime.
3. **Security > Events/Analytics > Security Events:** filter rule ID; pastikan malicious probes match dan traffic sah tidak terblok.
4. Naikkan ke Block hanya setelah bukti.

Checks:

```bash
curl -I https://example.go.id/administrator/
curl -I https://example.go.id/.env
curl -I https://example.go.id/.git/config
curl -i -X POST https://example.go.id/administrator/
```

Expected: admin challenge/login sesuai policy; dotfile probes 403; repeated POST akhirnya 403/429/challenge. Jangan menjalankan brute-force besar. Browser: login MFA, save/edit/upload/logout, private/incognito, mobile, uptime dan cron.

**Rollback:** disable rule by ID, jangan delete; ambil screenshot/export expression dan event terlebih dahulu. Turunkan Block ke Managed Challenge; sempitkan path/method/IP. Jangan mematikan seluruh WAF untuk satu false positive.

## 6. Pertahanan admin dan judol berlapis

- Wajib MFA seluruh Super Users; minimal dua break-glass accounts milik organisasi, recovery code offline tersegel, alert setiap pemakaian.
- Role minimum: editor tidak menjadi Super User. Audit user mingguan dan Joomla User Actions Log harian.
- Restrict `/administrator` dengan Cloudflare Access (Zero Trust plan-dependent), VPN/IP allowlist, atau HTTP Basic Auth cPanel sebagai lapisan luar. Uji bahwa POST/AJAX/media tetap berfungsi.
- Turnstile pada login/form; rate limit; lockout moderat. Jangan mengandalkan URL admin tersembunyi.
- File webroot immutable/read-only sejauh operasi Joomla memungkinkan. Writable hanya cache/tmp/log/upload yang diperlukan; executable PHP ditolak di upload/cache/tmp.
- Monitor konten DB: judul/body/meta/menu/module/template style untuk keyword judi dan outbound link/domain baru. Jangan auto-delete; quarantine + alert agar bukti tidak hilang.
- Search monitoring harian:

```text
site:example.go.id (judi OR slot OR gacor OR casino OR togel)
site:example.go.id inurl:administrator
site:example.go.id (viagra OR payday OR crypto)
```

Gunakan Google Search Console/Bing Webmaster Tools: Security Issues, Manual Actions, Pages/Indexing, link/spam anomaly. Verifikasi ownership memakai DNS, bukan file HTML permanen bila tidak perlu. Alert perubahan title/description canonical, sitemap, robots, dan lonjakan URL 200/redirect.

## 7. CSP rollout P1

CSP harus mengikuti host aset nyata. Jangan menyalin allowlist luas, memakai `unsafe-eval`, atau membuat hash dari HTML akhir: script yang tersisip lewat artikel/modul tidak boleh otomatis dipercaya.

**Status repository lokal — bukan bukti produksi:** frontend membentuk `Content-Security-Policy` di `includes/pn-csp.php`. `script-src` hanya mengizinkan sumber tepercaya dan hash script yang didaftarkan melalui renderer Joomla; script badan template sudah dipindahkan ke berkas same-origin. `script-src-attr 'none'` memblokir event handler inline. Area administrator tetap memakai policy ketat dalam mode Report-Only, sementara `base-uri`, `object-src`, dan `frame-ancestors` tetap enforced. Tidak ada nonce per-respons karena HTML publik dapat disimpan LiteSpeed; nonce yang ikut tercache akan dipakai ulang.

Urutan deployment:

1. Jalankan `php tools/test_csp.php`, `php tools/test_lscache_csrf.php`, dan smoke browser lokal. Native plugin page cache Joomla harus tetap nonaktif; jalur cache HTML yang didukung hanya LSCache.
2. Deploy ke staging. Terapkan migrasi normal, lalu purge seluruh objek HTML LiteSpeed dan edge yang dibuat sebelum perubahan. Policy dan body yang tercache harus berasal dari build yang sama.
3. Verifikasi satu header CSP enforced pada homepage, artikel, pencarian, halaman 404, dan respons `tmpl=component`; pastikan `script-src` tidak memuat `'unsafe-inline'` atau `'unsafe-eval'`. SVG harus membalas `script-src 'none'`.
4. Uji desktop/mobile: menu, slider, pencarian, mode gelap, tab jadwal, Turnstile, map, Instagram, serta embed. Tidak boleh ada CSP violation tak dikenal.
5. Administrator tetap Report-Only sampai login, MFA, editor WYSIWYG, media upload, dan simpan artikel lulus satu siklus kerja. Baru pertimbangkan enforce policy script administrator berdasarkan laporan nyata.

Jika laporan CSP perlu dikumpulkan, gunakan endpoint terautentikasi/rate-limited atau layanan CSP. Laporan dapat berisi URL; batasi retensi dan akses. Jangan membuat endpoint publik tanpa kontrol.

**Rollback:** kembalikan frontend ke policy Report-Only versi sebelumnya dan purge cache HTML lagi. Jangan menghapus CSP permanen tanpa tiket dan bukti violation.

## 8. Backup, file integrity, monitoring

### Backup 3-2-1

- Database: harian; webroot/config: harian + sebelum deploy; retention contoh 7 harian, 4 mingguan, 12 bulanan sesuai kebijakan.
- Minimal tiga salinan, dua media, satu off-site/immutable. Enkripsi client-side; key terpisah. Backup cPanel di akun/server sama bukan off-site.
- Jangan backup cache/log sementara atau menaruh archive di webroot.
- Setiap bulan restore ke environment terisolasi: verifikasi checksum, Joomla boot, login admin, media, artikel, DB charset, dan waktu restore. Backup belum terbukti sampai restore berhasil.

### File integrity

Bangun manifest dari release bersih, simpan off-host/read-only:

```bash
find public_html -type f -not -path '*/cache/*' -not -path '*/tmp/*' -print0 | sort -z | xargs -0 sha256sum > manifest.sha256
sha256sum -c manifest.sha256
```

Pada Windows/local gunakan tool ekuivalen. Pisahkan baseline core/vendor dari direktori konten dinamis. Alert: file PHP baru/berubah, terutama uploads/cache/tmp; `.htaccess`, `.user.ini`, `configuration.php`; cron; administrator; template. Scheduled malware scan host/Imunify/ClamAV bila tersedia, tetapi checksum + review tetap wajib.

### Monitoring minimum

- Uptime/HTTPS/expiry setiap 5 menit dari dua lokasi; alert 5xx, redirect aneh, title berubah.
- Cloudflare Security Events harian; lonjakan challenge/block, negara/ASN/path baru.
- cPanel access/error log, disk/inode, cron failure, mail queue; Joomla action/login failure.
- DNS/NS/MX/certificate transparency change alert.
- Synthetic browser harian: homepage, search, key article; admin login check tanpa menyimpan password di script publik.
- Search Console mingguan dan keyword judol harian. Escalation owner + cadangan; uji notifikasi bulanan.

## 9. Incident response ringkas

1. **Declare:** catat UTC, reporter, scope, indikator; pilih incident lead dan scribe.
2. **Contain:** Cloudflare challenge/block rule sempit; disable akun/token terindikasi; batasi admin/origin. Jangan menghancurkan bukti.
3. **Preserve:** snapshot/log/DB/file + hash, Cloudflare events, audit log, cron, DNS, access list. Jaga akses dan chain-of-custody.
4. **Eradicate:** identifikasi initial access + persistence; patch; rebuild dari clean source; hapus akun/cron/webshell; rotasi semua credential terkait dari device bersih.
5. **Recover:** restore bersih; origin lock/WAF/MFA; canary; cek content/search index; monitor ketat 72 jam.
6. **Notify:** pimpinan, pengelola TI/CSIRT/Komdigi/BSSN atau pihak berwenang sesuai kewajiban organisasi dan klasifikasi data. Jangan membuat pernyataan publik spekulatif.
7. **Lessons learned:** timeline, root cause, kontrol gagal, owner/tanggal perbaikan; update baseline dan drill.

Indikator kompromi: admin baru/perubahan MFA, publish massal, title/meta/link judol, PHP baru di images/cache/tmp, cron asing, `.htaccess` redirect cloaking berdasar User-Agent/referrer, DB option/template berubah, lonjakan outbound mail, Search Console warning.

## 10. Checklist go-live dan bukti

- [ ] Semua secret terekspos sudah rotated/revoked; purge Git direncanakan/dikerjakan owner; contributor re-clone.
- [ ] Joomla/core/extension supported; Super Users bersih + MFA; break-glass diuji.
- [ ] Backup off-site encrypted + satu restore test berhasil.
- [ ] DNS web proxied; DNSSEC valid; origin IP tidak muncul di record aktif.
- [ ] Origin certificate valid; Cloudflare Full (strict); HTTPS redirect; HSTS bertahap.
- [ ] Direct origin 80/443 ditolak; Cloudflare path tetap sehat; IPv4/IPv6 diuji.
- [ ] AOP aktif hanya bila origin/provider mendukung dan kedua sisi diuji.
- [ ] Managed rules aktif sesuai plan; custom/rate rules diuji; cron/uptime tidak rusak.
- [ ] `/administrator` memiliki outer control + MFA + Turnstile/rate defense; API tidak terpakai ditutup.
- [ ] Dotfiles, backup, SQL, Git, config, logs tidak publik; response tidak membocorkan secret.
- [ ] CSP Report-Only berjalan, violation ditriage, rollout enforce memiliki rollback.
- [ ] File integrity baseline off-host; malware/content/search monitoring + alert owner aktif.
- [ ] IR contact tree dan tabletop/restore drill tercatat.
- [ ] Jika password Joomla belum dirotasi: exception owner/tanggal tercatat; semua kontrol kompensasi §1.2 terbukti; residual risk diterima pihak berwenang; rotasi tetap terjadwal.
- [ ] Access atau Directory Privacy diuji dari sesi sah/tidak sah; tidak ada Bypass publik; session expiry dan rollback break-glass terbukti.
- [ ] Sesi/cookie/token lama invalid; installer/tool publik tertutup; extension sesuai allowlist dan review bertanggal.

Final browser matrix: anonymous desktop/mobile, admin MFA, editor save/upload, form + Turnstile, API client sah, cron, uptime. Final command checks:

```bash
curl -sSIL https://example.go.id/
curl -sSI http://example.go.id/
curl -sSI https://example.go.id/administrator/
curl -sSI https://example.go.id/configuration.php
curl -sSI https://example.go.id/database/example.sql
curl -sSI https://example.go.id/.git/config
```

Expected: HTTP redirects ke canonical HTTPS; homepage 200; protected admin sesuai policy; secret/config/SQL/Git 403/404 tanpa body/metadata sensitif. Simpan hasil dengan domain produksi, tanggal UTC, dan rule IDs—tanpa cookie/token/IP origin.

## Rujukan resmi Cloudflare

- Full (strict): <https://developers.cloudflare.com/ssl/origin-configuration/ssl-modes/full-strict/>
- Origin CA: <https://developers.cloudflare.com/ssl/origin-configuration/origin-ca/>
- Authenticated Origin Pulls: <https://developers.cloudflare.com/ssl/origin-configuration/authenticated-origin-pull/>
- Managed rules: <https://developers.cloudflare.com/waf/managed-rules/>
- Rate limiting: <https://developers.cloudflare.com/waf/rate-limiting-rules/>
- Cloudflare IP ranges: <https://www.cloudflare.com/ips/>

Cloudflare UI dan entitlement berubah menurut plan. Saat dashboard berbeda, ikuti dokumentasi resmi terkini, rekam path aktual, dan jangan menurunkan tujuan kontrol.