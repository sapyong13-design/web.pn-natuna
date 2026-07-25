-- Replace rendered brand images with optimized WebP files. PNG source tokens cannot occur in replacements.
UPDATE #__modules
SET content = REPLACE(
    REPLACE(
        REPLACE(
            REPLACE(content,
                'logo-pn-natuna.png', 'logo-pn-natuna.webp'),
            'logo-ampuh-certified.png', 'logo-ampuh-certified.webp'),
        'logo-asn-berakhlak-dark.png', 'logo-asn-berakhlak-dark.webp'),
    'logo-asn-berakhlak.png', 'logo-asn-berakhlak.webp')
WHERE id IN (110, 117, 806)
  AND (content LIKE '%logo-pn-natuna.png%'
       OR content LIKE '%logo-ampuh-certified.png%'
       OR content LIKE '%logo-asn-berakhlak-dark.png%'
       OR content LIKE '%logo-asn-berakhlak.png%');
