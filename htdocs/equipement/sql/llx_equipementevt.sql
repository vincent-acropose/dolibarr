-- ===================================================================
-- Copyright (C) 2012-2015 Charlie Benke <charlie@patas-monkey.com>
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

create table llx_equipementevt
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  fk_equipement				integer,
  fk_equipementevt_type		integer NOT NULL,	-- type d'événement sur l'équipement
  datec						datetime,			-- date de création de la ligne d'événement
  description				text,				-- description de la ligne d'evénement
  fk_user_author			integer,			-- créateur de l'événement
  datee						datetime,			-- date de début de l'événement
  dateo						datetime,			-- date de fin de l'événement
  tms						timestamp,
  fulldayevent				smallint(6),		-- événement en mode journée
  total_ht					double(24,8),		-- cout associé é l'événement
  fk_fichinter				integer,			-- intervention lié é l'événement
  fk_contrat				integer,			-- contrat lié é l'événement
  fk_expedition				integer,			-- expédition lié é l'événement
  fk_project				integer,			-- projet lié é l'événement
  fk_operation				integer,			-- operation lié é l'événement
  fk_ticket					integer,			-- ticket lié é l'événement
  import_key 				VARCHAR( 14 ) NULL DEFAULT NULL
)ENGINE=innodb;
