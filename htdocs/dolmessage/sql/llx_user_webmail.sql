

CREATE TABLE IF NOT EXISTS `llx_userwebmail` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `number` int(11) DEFAULT '1',
  `title` varchar(64) DEFAULT NULL,
  `imap_login` varchar(255) NOT NULL,
  `imap_password` varbinary(64) NOT NULL,
  `imap_host` varchar(255) NOT NULL,
  `imap_port` int(4) NOT NULL DEFAULT '993',
  `imap_ssl` tinyint(1) NOT NULL DEFAULT '1',
  `imap_ssl_novalidate_cert` tinyint(1) NOT NULL DEFAULT '0',
  `fk_user` int(11) NOT NULL,
  PRIMARY KEY (`rowid`),
  UNIQUE KEY `number` (`number`,`fk_user`),
  KEY `fk_usermailboxconfig_fk_user` (`fk_user`)
)  ;


