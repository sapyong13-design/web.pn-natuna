-- Use one CSS-generated external-link arrow and remove every legacy arrow span from Transparency document cards.
UPDATE #__content
SET introtext=REGEXP_REPLACE(
      introtext,
      '<span[[:space:]]+aria-hidden="true">[^<]*</span>([[:space:]]*</a>)',
      '\\1'
    ),
    modified=UTC_TIMESTAMP()
WHERE id IN (
  SELECT article_id FROM (
    SELECT DISTINCT CAST(SUBSTRING_INDEX(link,'id=',-1) AS UNSIGNED) AS article_id
    FROM #__menu
    WHERE menutype='mainmenu' AND published=1 AND path LIKE 'transparansi/%'
      AND link LIKE 'index.php?option=com_content&view=article&id=%'
  ) AS linked_transparency_articles
)
AND introtext REGEXP '<span[[:space:]]+aria-hidden="true">[^<]*</span>[[:space:]]*</a>';
