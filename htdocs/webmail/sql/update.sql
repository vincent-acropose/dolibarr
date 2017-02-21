ALTER TABLE llx_webmail_users ADD COLUMN entity integer DEFAULT 1 NOT NULL;
ALTER TABLE llx_webmail_mail ADD COLUMN entity integer DEFAULT 1 NOT NULL;