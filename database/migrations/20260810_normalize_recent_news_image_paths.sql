-- Nama kamera/WhatsApp tidak bermakna ketika gambar dibuka langsung. Tiga artikel
-- terbaru dipindahkan ke slug singkat yang tetap menjelaskan momen dan urutan foto.
-- Berkas lama tidak dihapus; redirect Apache mempertahankan URL yang pernah dibagikan.

UPDATE #__content
SET images = REPLACE(REPLACE(images,
        'images/PATCANIA.jpg',
        'images/berita/2026/alih-tugas-cania-kirana-1.webp'),
        'images/IMG_3408.jpg',
        'images/berita/2026/alih-tugas-cania-kirana-2.webp'),
    introtext = REPLACE(REPLACE(introtext,
        'images/PATCANIA.jpg',
        'images/berita/2026/alih-tugas-cania-kirana-1.webp'),
        'images/IMG_3408.jpg',
        'images/berita/2026/alih-tugas-cania-kirana-2.webp'),
    `fulltext` = REPLACE(REPLACE(`fulltext`,
        'images/PATCANIA.jpg',
        'images/berita/2026/alih-tugas-cania-kirana-1.webp'),
        'images/IMG_3408.jpg',
        'images/berita/2026/alih-tugas-cania-kirana-2.webp')
WHERE alias = 'pengantar-alih-tugas-cania-kirana-a-md-di-pengadilan-negeri-natuna';

UPDATE #__content
SET images = REPLACE(REPLACE(REPLACE(images,
        'images/WhatsApp Image 2026-07-31 at 08.17.57.jpeg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-1.webp'),
        'images/IMG_3150.jpg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-2.webp'),
        'images/IMG_3204.jpg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-3.webp'),
    introtext = REPLACE(REPLACE(REPLACE(introtext,
        'images/WhatsApp Image 2026-07-31 at 08.17.57.jpeg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-1.webp'),
        'images/IMG_3150.jpg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-2.webp'),
        'images/IMG_3204.jpg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-3.webp'),
    `fulltext` = REPLACE(REPLACE(REPLACE(`fulltext`,
        'images/WhatsApp Image 2026-07-31 at 08.17.57.jpeg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-1.webp'),
        'images/IMG_3150.jpg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-2.webp'),
        'images/IMG_3204.jpg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-3.webp')
WHERE alias = 'pertandingan-bola-voli-awali-rangkaian-hut-ke-81-ri-dan-mahkamah-agung-ri';

UPDATE #__content
SET images = REPLACE(REPLACE(REPLACE(images,
        'images/IMG_3729 1.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-1.webp'),
        'images/IMG_3701.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-2.webp'),
        'images/IMG_3738 1.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-3.webp'),
    introtext = REPLACE(REPLACE(REPLACE(introtext,
        'images/IMG_3729 1.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-1.webp'),
        'images/IMG_3701.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-2.webp'),
        'images/IMG_3738 1.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-3.webp'),
    `fulltext` = REPLACE(REPLACE(REPLACE(`fulltext`,
        'images/IMG_3729 1.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-1.webp'),
        'images/IMG_3701.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-2.webp'),
        'images/IMG_3738 1.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-3.webp')
WHERE alias = 'pertandingan-mobile-legends-jadi-laga-kedua-semarak-hut-ke-81-ri-dan-mahkamah-agung-ri';
