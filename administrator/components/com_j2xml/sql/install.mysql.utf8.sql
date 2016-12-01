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
	`type` int(1) unsigned NOT NULL DEFAULT 0 COMMENT '0: username/password, 1: oauth2',
	PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__oauth2_tokens` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`client_id` varchar(255) NOT NULL DEFAULT '',
	`client_secret` varchar(255) NOT NULL DEFAULT '',
	`redirect_uri` text,
	`state` tinyint(3) NOT NULL DEFAULT '0',
	`checked_out` int(11) NOT NULL DEFAULT '0',
	`checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`access_token` text,
	`refresh_token` text,
	`expire_time` datetime,
	`user_id` int(10) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__j2xml_usergroups` (
	`id` int(10) unsigned NOT NULL,
	`parent_id` int(10) unsigned NOT NULL DEFAULT '0',
	`title` varchar(100) NOT NULL DEFAULT ''
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
	