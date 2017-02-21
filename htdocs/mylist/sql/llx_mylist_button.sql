-- ===================================================================
-- Copyright (C) 2014		Charles-Fr Benke	<charles.fr@benke.fr>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

create table llx_mylist_button
(
	rowid		integer AUTO_INCREMENT PRIMARY KEY, -- clé principale
	fk_mylist	integer NOT NULL,					-- clé d'accès à la liste
	label		varchar(30) NOT NULL,				-- Nom du bouton 
	description	text NULL DEFAULT NULL,				-- Tooltip du bouton
	posbutton	integer,							-- position du bouton
	active		integer NULL DEFAULT NULL ,			-- le bouton est actif ou pas
	perms		text NULL DEFAULT NULL ,			-- droit d'accès associé au bouton
	querybutton	text NULL DEFAULT NULL				-- requete associé au bouton
)ENGINE=innodb;