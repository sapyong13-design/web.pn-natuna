-- Kotak pencarian beranda: perbaiki tujuan formulir dan daftar saran.
--
-- Modul 807 (`mod_custom`, posisi `home-search`) masih mengirim ke
-- `index.php?option=com_search&view=search`. Komponen `com_search` dihapus Joomla
-- sejak versi 4, sehingga setiap pencarian dari beranda berakhir di 404. Rute
-- pengganti `/cari` sudah didaftarkan migrasi 20260902 di menu tersembunyi dan
-- membaca parameter kueri bernama `q`, sama seperti formulir di overlay pencarian
-- (`templates/pn_natuna_2026/index.php`) dan di halaman galat (`error.php`).
--
-- Perubahan pada formulir:
--   * `action="/cari"`, tanpa medan tersembunyi `option`/`view`;
--   * satu masukan bernama `q` menggantikan `searchword`;
--   * `role="search"` supaya pembaca layar mengenalinya sebagai tengara pencarian;
--   * label tetap tersembunyi secara visual, judul modul sudah menyebut "Pencarian";
--   * kelas `search-box` dipertahankan agar gaya yang ada tidak perlu diubah.
--
-- Perubahan pada `<datalist>`: saran "JDIH Peraturan" tidak pernah menghasilkan
-- satu pun hasil pada indeks Smart Search - saran yang menuntun warga ke halaman
-- kosong lebih buruk daripada tidak ada saran. Penggantinya "Peraturan dan
-- Kebijakan", judul persis halaman yang dicari, dan hasil teratasnya adalah
-- halaman itu sendiri. Tujuh saran lain sudah diuji dan semuanya berisi.
--
-- Idempoten: `UPDATE` hanya menyentuh baris yang isinya belum sama persis, jadi
-- pemutaran kedua tidak mengubah apa pun.
SET @home_search_form := '<form class="search-box" action="/cari" method="get" role="search"><label class="visually-hidden" for="home-search-q">Cari informasi di situs Pengadilan Negeri Natuna</label><input id="home-search-q" name="q" type="search" placeholder="Cari informasi di sini..." list="search-suggestions" autocomplete="off" enterkeyhint="search"><datalist id="search-suggestions"><option value="Jadwal Sidang"></option><option value="Biaya Perkara"></option><option value="e-Court"></option><option value="SIWAS Pengaduan"></option><option value="Peraturan dan Kebijakan"></option><option value="PPID Informasi"></option><option value="Maklumat Pelayanan"></option><option value="Alamat &amp; Kontak"></option></datalist><button type="submit">Cari</button></form>';

-- Modul sasaran harus ada dengan identitas yang diharapkan; kalau tidak, migrasi
-- gagal keras alih-alih diam-diam tidak mengubah apa pun.
CREATE TEMPORARY TABLE home_search_dependency_check (dependency_count INT NOT NULL, CHECK (dependency_count=1));
INSERT INTO home_search_dependency_check
SELECT COUNT(*) FROM #__modules WHERE id=807 AND module='mod_custom' AND position='home-search';
DROP TEMPORARY TABLE home_search_dependency_check;

UPDATE #__modules
SET content=@home_search_form
WHERE id=807
  AND module='mod_custom'
  AND position='home-search'
  AND content<>@home_search_form;
