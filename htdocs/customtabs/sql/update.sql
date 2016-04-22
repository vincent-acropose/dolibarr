ALTER TABLE  `llx_customtabs` ADD  `importenabled` smallint  NULL DEFAULT 0;
ALTER TABLE  `llx_customtabs` ADD  `exportenabled` smallint  NULL DEFAULT 0;
ALTER TABLE  `llx_customtabs` ADD  `colnameline` smallint  NULL DEFAULT 1;
ALTER TABLE  `llx_customtabs` ADD  `colnamebased` smallint  NULL DEFAULT NULL;
ALTER TABLE  `llx_customtabs` ADD  `csvseparator` VARCHAR(5) NULL DEFAULT NULL;
ALTER TABLE  `llx_customtabs` ADD  `csvenclosure` VARCHAR(5) NULL DEFAULT NULL;