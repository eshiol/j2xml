CREATE TABLE IF NOT EXISTS `#__j2xml_websites` (
	`id` int(11) unsigned NOT NULL auto_increment,
	`title` varchar(255) NOT NULL DEFAULT '',
	`alias` varchar(255) NOT NULL DEFAULT '',
	`remote_url` varchar(255) NOT NULL DEFAULT '',
	`username` varchar(255) NOT NULL DEFAULT '',
	`password` varchar(255) NOT NULL DEFAULT '',
	`state` tinyint(3) NOT NULL DEFAULT '0',
	`checked_out` int(11) NOT NULL DEFAULT '0',
	`checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY  (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;	

CREATE TABLE IF NOT EXISTS `#__j2xml_usergroups` (
	`id` int(10) unsigned NOT NULL,
	`parent_id` int(10) unsigned NOT NULL DEFAULT '0',
	`title` varchar(100) NOT NULL DEFAULT ''
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
	