INSERT INTO #__extensions
(package_id,name,type,element,folder,client_id,enabled,access,protected,manifest_cache,params,custom_data,system_data,checked_out,checked_out_time,ordering,state)
SELECT 0,'plg_system_loginthrottle','plugin','loginthrottle','system',0,1,1,0,
'{"name":"plg_system_loginthrottle","type":"plugin","creationDate":"2026-08","author":"Pengadilan Negeri Natuna","copyright":"","authorEmail":"","authorUrl":"","version":"1.0.0","description":"Rate limits repeated Joomla administrator login failures.","group":"system","filename":"loginthrottle"}',
'{}','','',0,NULL,0,0
WHERE NOT EXISTS (SELECT 1 FROM #__extensions WHERE type='plugin' AND folder='system' AND element='loginthrottle');

UPDATE #__extensions SET enabled=1
WHERE type='plugin' AND folder='system' AND element='loginthrottle';
