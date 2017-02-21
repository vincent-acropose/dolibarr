-- ============================================================================
-- Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
-- Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===========================================================================

CREATE TABLE `llx_webmail_files` (
	`rowid`			integer AUTO_INCREMENT PRIMARY KEY,
	`fk_mail`		int(11) NOT NULL DEFAULT '0',
	`fk_user`		int(11) NOT NULL DEFAULT '0',
	`datetime`		datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`file_name`		text NOT NULL,
	`file`			text NOT NULL,
	`file_size`		int(11) NOT NULL DEFAULT '0',
	`file_type`		varchar(255) NOT NULL DEFAULT '',
	`search`		mediumtext NOT NULL
 )ENGINE=innodb;