-- Enable the plugin:

UPDATE [#__extensions]
SET [enabled] = 1
WHERE [type] = 'plugin'
AND [folder] = 'system'
AND [element] = 'j2xml';
