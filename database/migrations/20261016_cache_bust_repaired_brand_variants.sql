-- Force clients that cached the flattened compact logo for one year to request the repaired transparent asset.
UPDATE #__modules
SET content = REPLACE(
        content,
        '/images/brand/logo-pn-natuna-96.webp 96w',
        '/images/brand/logo-pn-natuna-96.webp?v=20260811-alpha 96w'
    )
WHERE position = 'header-brand'
  AND module = 'mod_custom'
  AND content LIKE '%/images/brand/logo-pn-natuna-96.webp 96w%';
