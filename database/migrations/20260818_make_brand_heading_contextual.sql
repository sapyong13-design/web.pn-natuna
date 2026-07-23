-- Let the brand module render h1 on home and a non-heading title on internal pages without cross-page cache leakage.
UPDATE #__modules
SET params=JSON_SET(COALESCE(NULLIF(params,''),'{}'),'$.cache','0')
WHERE id=110 AND module='mod_custom' AND position='header-brand';
