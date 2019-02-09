CREATE TABLE IF NOT EXISTS `#__j2xml_websites` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`title` varchar(255) NOT NULL DEFAULT '',
	`alias` varchar(255) NOT NULL DEFAULT '',
	`remote_url` varchar(255) NOT NULL DEFAULT '',
	`username` varchar(255) NOT NULL DEFAULT '' COMMENT 'client_id',
	`password` varchar(255) NOT NULL DEFAULT '' COMMENT 'client_secret',
	`state` tinyint(3) NOT NULL DEFAULT '0',
	`checked_out` int(11) NOT NULL DEFAULT '0',
	`checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__j2xml_websites`
	CHANGE `username` `username` varchar(255) NOT NULL DEFAULT '' COMMENT 'client_id',
	CHANGE `password` `password` varchar(255) NOT NULL DEFAULT '' COMMENT 'client_secret',
	ADD COLUMN `type` int(1) unsigned NOT NULL DEFAULT 0 COMMENT '0: username/password, 1: oauth2'
	;
	