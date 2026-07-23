-- Repair closing anchors corrupted by the prior regex backreference and remove the visible "1" artifact.
UPDATE #__content
SET introtext=REPLACE(introtext,'</span>1','</span></a>'),modified=UTC_TIMESTAMP()
WHERE id IN (
  SELECT article_id FROM (
    SELECT DISTINCT CAST(SUBSTRING_INDEX(link,'id=',-1) AS UNSIGNED) AS article_id
    FROM #__menu
    WHERE menutype='mainmenu' AND published=1 AND path LIKE 'transparansi/%'
      AND link LIKE 'index.php?option=com_content&view=article&id=%'
  ) AS linked_transparency_articles
)
AND introtext LIKE '%</span>1%';
