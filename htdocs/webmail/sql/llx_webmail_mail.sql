-- ===================================================================
-- Copyright (C) 2014 Juanjo Menent <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ===================================================================

CREATE TABLE llx_webmail_mail
(

	`rowid`			integer AUTO_INCREMENT PRIMARY KEY,
	 entity integer DEFAULT 1 NOT NULL,		-- multi company id
	`fk_user` 		integer NOT NULL DEFAULT '0',
	`fk_soc` 		integer NOT NULL DEFAULT '0',
	`fk_contact`	integer NOT NULL DEFAULT '0',
  	`uidl` 			varchar(255) NOT NULL DEFAULT '',
  	`datetime` 		datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  	`size` 			integer NOT NULL DEFAULT '0',
  	`subject` 		text NOT NULL,
  	`body` 			mediumtext NOT NULL,
  	`state_new` 	integer NOT NULL DEFAULT '0',
  	`state_reply` 	integer NOT NULL DEFAULT '0',
  	`state_forward` integer NOT NULL DEFAULT '0',
  	`state_wait` 	integer NOT NULL DEFAULT '0',
  	`state_spam` 	integer NOT NULL DEFAULT '0',
  	`id_correo` 	integer NOT NULL DEFAULT '0',
  	`is_outbox` 	integer NOT NULL DEFAULT '0',
  	`state_sent` 	integer NOT NULL DEFAULT '0',
  	`state_error` 	varchar(255) NOT NULL DEFAULT '',
  	`state_crt` 	integer NOT NULL DEFAULT '0',
  	`state_archiv` 	integer NOT NULL DEFAULT '0',
  	`priority` 		integer NOT NULL DEFAULT '0',
  	`sensitivity` 	integer NOT NULL DEFAULT '0',
  	`from` 			text NOT NULL,
  	`to` 			text NOT NULL,
  	`cc` 			text NOT NULL,
  	`bcc` 			text NOT NULL,
  	`files` 		integer NOT NULL DEFAULT '0'
 )ENGINE=innodb;