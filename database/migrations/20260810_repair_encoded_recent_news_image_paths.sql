-- Joomla menyimpan sebagian src yang berisi spasi sebagai %20. Migrasi normalisasi
-- awal menangani bentuk literal; repair ini menutup bentuk URL-encoded secara idempoten.

UPDATE #__content
SET images = REPLACE(images,
        'images/WhatsApp%20Image%202026-07-31%20at%2008.17.57.jpeg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-1.webp'),
    introtext = REPLACE(introtext,
        'images/WhatsApp%20Image%202026-07-31%20at%2008.17.57.jpeg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-1.webp'),
    `fulltext` = REPLACE(`fulltext`,
        'images/WhatsApp%20Image%202026-07-31%20at%2008.17.57.jpeg',
        'images/berita/2026/bola-voli-hut-81-ri-ma-1.webp')
WHERE alias = 'pertandingan-bola-voli-awali-rangkaian-hut-ke-81-ri-dan-mahkamah-agung-ri';

UPDATE #__content
SET images = REPLACE(REPLACE(images,
        'images/IMG_3729%201.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-1.webp'),
        'images/IMG_3738%201.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-3.webp'),
    introtext = REPLACE(REPLACE(introtext,
        'images/IMG_3729%201.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-1.webp'),
        'images/IMG_3738%201.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-3.webp'),
    `fulltext` = REPLACE(REPLACE(`fulltext`,
        'images/IMG_3729%201.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-1.webp'),
        'images/IMG_3738%201.jpg',
        'images/berita/2026/mobile-legends-hut-81-ri-ma-3.webp')
WHERE alias = 'pertandingan-mobile-legends-jadi-laga-kedua-semarak-hut-ke-81-ri-dan-mahkamah-agung-ri';
