-- Enable the plugin:

UPDATE `#__extensions`
SET `enabled` = 0
WHERE `type` = 'plugin'
AND `folder` = 'system'
AND `element` = 'basicauth';
