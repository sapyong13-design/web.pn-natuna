-- Memperbaiki hasil replay migrasi lama: data- yang bertumpuk membuat lazy-loader tidak menemukan iframe.
-- Pola (data-)* juga mencocokkan satu atau banyak prefiks, sehingga setiap pemutaran ulang tetap menghasilkan satu data-src.
UPDATE #__modules
SET content = REGEXP_REPLACE(
    content,
    '(?:data-)*src="https://maps[.]google[.]com/maps[?]q=Kantor%20Pengadilan%20Negeri%20Ranai&t=&z=17&ie=UTF8&iwloc=&output=embed"',
    'data-src="https://maps.google.com/maps?q=Kantor%20Pengadilan%20Negeri%20Ranai&t=&z=17&ie=UTF8&iwloc=&output=embed"'
)
WHERE id = 810;

-- Replay lama dapat menggandakan kicker berurutan; grup pertama dipertahankan, sisanya diringkas.
UPDATE #__modules
SET content = REGEXP_REPLACE(
    content,
    '(<p class="service-hours-kicker">[^<]*</p>)(?:<p class="service-hours-kicker">[^<]*</p>)+',
    '\\1'
)
WHERE id = 115;
