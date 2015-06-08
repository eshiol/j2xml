CREATE TABLE IF NOT EXISTS `#__j2xml_usergroups` (
	`id` int(10) unsigned NOT NULL,
	`parent_id` int(10) unsigned NOT NULL DEFAULT '0',
	`title` varchar(100) NOT NULL DEFAULT ''
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
	