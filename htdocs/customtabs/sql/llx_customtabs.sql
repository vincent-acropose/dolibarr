-- ===================================================================
-- Copyright (C) 2014      Charles-Fr BENKE        <charles-fr@benke.fr>
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
-- ===================================================================
--
-- statut
-- 0 : actif
-- 1 : inactif

-- contient la liste les tabs définies
create table llx_customtabs
(
  rowid			integer AUTO_INCREMENT PRIMARY KEY,
  entity		integer DEFAULT 1 NOT NULL,		-- multi company id
  tms			timestamp,
  fk_statut		smallint NOT NULL DEFAULT 0,
  libelle		varchar(50) NOT NULL,
  element		varchar(50) NOT NULL,			-- élément où positionner l'onglet
  files			smallint NOT NULL DEFAULT 0,
  tablename		varchar(32) NOT NULL,
  mode			smallint NOT NULL DEFAULT 0,	-- fiche ou liste
  querydo		text NULL DEFAULT NULL ,		-- requete d'action sur la page
  fk_parent		smallint NULL DEFAULT 0,
  template		text NULL DEFAULT NULL,
  exportenabled	smallint NULL DEFAULT 0,		-- autorise l'exportation de la liste (0 si absent, 1 si autorisé )
  importenabled	smallint NULL DEFAULT 0,		-- autorise l'exportation de la liste (0 si absent, 1 si autorisé )
  colnameline	smallint NULL DEFAULT 1,		-- indique la ligne où se trouve les colonnes (0 si pas de ligne)
  colnamebased	smallint NULL DEFAULT NULL,		-- indique si le repérage des colonnes se fait sur leur code (0), leur nom (1) ou leur ordre (2)
  csvseparator	varchar(5) NULL DEFAULT NULL	-- indique le séparateur pour les champs
  
)ENGINE=innodb;
