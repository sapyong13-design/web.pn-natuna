-- Replace the self-closing dollar SVG glyph used in verified Indonesian money contexts.
SET @dollar_glyph := '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>';
SET @rupiah_glyph := '<text class="svc-rupiah-icon" x="12" y="15.5" text-anchor="middle" aria-hidden="true">Rp</text>';
UPDATE #__content
SET introtext=REPLACE(introtext,@dollar_glyph,@rupiah_glyph),modified=UTC_TIMESTAMP()
WHERE id IN (
  SELECT article_id FROM (
    SELECT CAST(SUBSTRING_INDEX(link,'id=',-1) AS UNSIGNED) AS article_id
    FROM #__menu
    WHERE menutype='mainmenu' AND published=1
      AND path IN ('informasi-perkara','layanan-publik/standar-pelayanan','informasi-perkara/prosedur-eksekusi')
  ) AS linked_articles
) AND introtext LIKE CONCAT('%',@dollar_glyph,'%');
