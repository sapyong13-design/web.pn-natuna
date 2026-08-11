-- Server-side public HTML cache for LiteSpeed Enterprise.
-- The component stores policy; the system plugin emits LiteSpeed-specific cache and purge headers.
-- Authenticated sessions, non-GET requests, administrator responses, errors, and excluded components remain uncached.

CREATE TABLE IF NOT EXISTS `#__modules_lscache` (
  `moduleid` int NOT NULL DEFAULT 0,
  `lscache_type` smallint DEFAULT 0,
  `lscache_ttl` smallint DEFAULT 0,
  `module_type` smallint DEFAULT 0,
  `vary_language` smallint DEFAULT 1,
  PRIMARY KEY (`moduleid`)
);

INSERT INTO `#__extensions`
  (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `locked`, `manifest_cache`, `params`, `custom_data`, `ordering`, `state`)
SELECT
  0,
  'COM_LSCACHE',
  'component',
  'com_lscache',
  '',
  1,
  1,
  0,
  0,
  0,
  '{"name":"COM_LSCACHE","type":"component","creationDate":"December 2017","author":"LiteSpeedTech","copyright":"Copyright Info","authorEmail":"info@litespeedtech.com","authorUrl":"www.litespeedtech.com","version":"1.5.1","description":"COM_LSCACHE_DESCRIPTION","group":"","changelogurl":"","filename":"lscache"}',
  '{}',
  '',
  0,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__extensions`
  WHERE `type` = 'component' AND `element` = 'com_lscache'
);

INSERT INTO `#__extensions`
  (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `locked`, `manifest_cache`, `params`, `custom_data`, `ordering`, `state`)
SELECT
  0,
  'LiteSpeed Cache Plugin',
  'plugin',
  'lscache',
  'system',
  0,
  1,
  1,
  0,
  0,
  '{"name":"LiteSpeed Cache Plugin","type":"plugin","creationDate":"Dec 2017","author":"LiteSpeedTech","copyright":"Copyright (C) 2005 - 2018 Open Source Matters. All rights reserved.","authorEmail":"info@litespeedtech.com","authorUrl":"www.litespeedtech.org","version":"1.5.1","description":"PLG_SYSTEM_LSCACHE_XML_DESCRIPTION","group":"","changelogurl":"","filename":"lscache"}',
  '{}',
  '',
  0,
  0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `#__extensions`
  WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'lscache'
);

UPDATE `#__extensions`
SET
  `enabled` = 1,
  `params` = '{"cacheEnabled":"1","cacheTimeout":"15","homePageCacheTimeout":"15","autoPurgePlugin":"1","autoPurgeLanguage":"1","autoPurgeArticleCategory":"1","logLevel":"-1","excludeOptions":["com_users","com_ajax","com_contact","com_privacy"],"excludeMenus":[],"excludeURLs":"","esiEnabled":"0","loginESI":"0","mobileCacheVary":"0","beforeRender":"0","purgePostBack":"0","serveStale":"1","loginOverrideESI":"0","loginCachable":"0","loginCacheVary":"0","loginExcludeMenus":[],"loginExcludeURLs":"","autoRecache":"0","recacheDuration":"5","recacheComponent":""}'
WHERE `type` = 'component' AND `element` = 'com_lscache';

UPDATE `#__extensions`
SET `enabled` = 1
WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'lscache';

-- Joomla's own page-cache plugin sits inside PHP and masks LiteSpeed hit/miss behavior.
-- Browser caching remains off; immutable assets keep their explicit .htaccess policy.
UPDATE `#__extensions`
SET
  `enabled` = 0,
  `params` = JSON_SET(COALESCE(NULLIF(`params`, ''), '{}'), '$.browsercache', '0')
WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'cache';

INSERT INTO `#__schemas` (`extension_id`, `version_id`)
SELECT `extension_id`, ''
FROM `#__extensions` AS `extension`
WHERE `extension`.`type` = 'component'
  AND `extension`.`element` = 'com_lscache'
  AND NOT EXISTS (
    SELECT 1 FROM `#__schemas` AS `schema_version`
    WHERE `schema_version`.`extension_id` = `extension`.`extension_id`
  );
