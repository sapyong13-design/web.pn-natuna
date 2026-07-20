# Instagram homepage

Homepage tidak lagi memakai RSS.app, token Meta, cron Instagram, atau cache gambar lokal. Renderer aktif memakai embed profil resmi `https://www.instagram.com/pn.natuna/embed/`; Instagram menentukan enam post terbaru yang tampil. `tools/cron-refresh-instagram.php` dan parser/cache lama dipertahankan sementara sebagai kode nonaktif untuk rollback, tetapi jangan memasang cron atau `INSTAGRAM_RSS_URL` baru.

Konsekuensi: tidak ada secret dan tidak ada refresh server-side, tetapi tampilan/jumlah post mengikuti Instagram dan iframe memerlukan akses browser ke `www.instagram.com`.
