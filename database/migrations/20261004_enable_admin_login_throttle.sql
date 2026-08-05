INSERT INTO #__extensions
(package_id,name,type,element,folder,client_id,enabled,access,protected,locked,manifest_cache,params,custom_data,checked_out,checked_out_time,ordering,state)
SELECT 0,'plg_system_loginthrottle','plugin','loginthrottle','system',0,1,1,0,0,
'{"name":"plg_system_loginthrottle","type":"plugin","creationDate":"2026-08","author":"Pengadilan Negeri Natuna","version":"1.0.0","description":"Rate limits repeated Joomla administrator login failures.","group":"system","filename":"loginthrottle"}',
'{}','',NULL,NULL,0,0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM #__extensions WHERE type='plugin' AND folder='system' AND element='loginthrottle');

UPDATE #__extensions SET enabled=1
WHERE type='plugin' AND folder='system' AND element='loginthrottle';
