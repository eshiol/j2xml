CREATE TABLE IF NOT EXISTS `#__j2xml_usergroups` (
	`id` int(10) unsigned NOT NULL,
	`parent_id` int(10) unsigned NOT NULL DEFAULT '0',
	`title` varchar(100) NOT NULL DEFAULT '',
	PRIMARY KEY (`id`)
	) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 DEFAULT COLLATE = utf8mb4_unicode_ci;
