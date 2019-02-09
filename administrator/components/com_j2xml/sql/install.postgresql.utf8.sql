CREATE TABLE IF NOT EXISTS "#__j2xml_websites" (
	"id" serial NOT NULL,
	"title" varchar(255) DEFAULT '' NOT NULL,
	"alias" varchar(255) DEFAULT '' NOT NULL,
	"remote_url" varchar(255) DEFAULT '' NOT NULL,
	"username" varchar(255) DEFAULT '' NOT NULL,
	"password" varchar(255) DEFAULT '' NOT NULL,
	"state" smallint DEFAULT 0 NOT NULL,
	"checked_out" bigint DEFAULT 0 NOT NULL,
	"checked_out_time" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
	"type" smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY  ("id")
	);
COMMENT ON COLUMN "#__j2xml_websites"."username" IS 'client_id';
COMMENT ON COLUMN "#__j2xml_websites"."password" IS 'client_secret';
COMMENT ON COLUMN "#__j2xml_websites"."type" IS '0: username/password, 1: oauth2';
	