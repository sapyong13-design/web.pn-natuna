-- Compact the sidebar heading while retaining the court and PTSP scope inside the card.
UPDATE #__modules
SET title = 'Jam Layanan',
    content = REPLACE(content, '<div class="service-hours-card">', '<div class="service-hours-card"><p class="service-hours-kicker">Pengadilan &amp; PTSP</p>')
WHERE id = 115
  AND position = 'home-service-info'
  AND content NOT LIKE '%service-hours-kicker%';
