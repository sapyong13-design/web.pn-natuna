-- PPPK is an employment status, so it leads the role row and carries its own status class; the assignment follows it.
UPDATE #__content
SET introtext = REGEXP_REPLACE(
        introtext,
        '<div class="roster-role-row"><span class="roster-role">([^<]+)</span><span class="roster-role">PPPK</span></div>',
        '<div class="roster-role-row"><span class="roster-role roster-role-status">PPPK</span><span class="roster-role">\\1</span></div>'
    ),
    modified = UTC_TIMESTAMP(), modified_by = 0
WHERE introtext REGEXP '<span class="roster-role">([^<]+)</span><span class="roster-role">PPPK</span>';
