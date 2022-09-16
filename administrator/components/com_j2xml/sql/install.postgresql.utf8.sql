CREATE TABLE IF NOT EXISTS "#__j2xml_usergroups" (
	"id" serial NOT NULL,
	"parent_id" bigint DEFAULT 0 NOT NULL,
	"title" varchar(100) DEFAULT '' NOT NULL,
	PRIMARY KEY ("id"));
