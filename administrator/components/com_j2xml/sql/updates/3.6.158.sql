ALTER TABLE `#__j2xml_websites`
	CHANGE `username` `username` varchar(255) NOT NULL DEFAULT '' COMMENT 'client_id',
	CHANGE `password` `password` varchar(255) NOT NULL DEFAULT '' COMMENT 'client_secret',
	ADD COLUMN `type` int(1) unsigned NOT NULL DEFAULT 0 COMMENT '0: username/password, 1: oauth2'
	;
	