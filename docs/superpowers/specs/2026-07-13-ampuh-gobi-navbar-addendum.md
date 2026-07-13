# Addendum Direktori AMPUH 2026: 27 GOBI dan Navbar

## Koreksi sumber data

Workbook authoritative memiliki 27 GOBI. Merge ranges kolom A menentukan ownership baris secara langsung. Tiga batas GOBI berada di tengah checklist besar:

- GOBI 3 memiliki baris checklist 6.5, setelah GOBI 2.
- GOBI 11 memiliki bagian checklist 31, setelah GOBI 10.
- GOBI 18 memiliki bagian checklist 44, setelah GOBI 17.

Importer harus menetapkan setiap sub-checklist kepada GOBI berdasarkan merge range/baris sumber. Satu checklist besar boleh muncul pada lebih dari satu GOBI, tetapi setiap sub-checklist hanya muncul sekali. Ringkasan global menghitung 82 nomor checklist unik, bukan jumlah kemunculan checklist di seluruh GOBI.

Invariant final:

- 27 GOBI bernomor 1 sampai 27;
- 82 checklist besar unik bernomor 1 sampai 82;
- 405 sub-checklist unik;
- 2.043 dokumen;
- tidak ada sub-checklist atau file yang diduplikasi;
- agregat per GOBI dan per checklist dihitung dari bagian yang benar-benar tampil pada cabang tersebut.

## Navbar

Menu publik `AMPUH` ditempatkan pada `mainmenu` tepat setelah menu `Transparansi` dan sebelum `Hubungi Kami`. Menu mengarah ke artikel canonical `/ampuh`. Migrasi harus idempotent dan memakai nested-set rebuild Joomla/database setelah insertion agar `lft`/`rgt` tetap valid.
