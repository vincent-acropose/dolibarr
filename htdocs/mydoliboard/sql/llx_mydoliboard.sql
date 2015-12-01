-- ===================================================================
-- Copyright (C) 2013-2014	Charles-Fr Benke	<charles.fr@benke.fr>
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

create table llx_mydoliboard
(
 	rowid		integer AUTO_INCREMENT PRIMARY KEY, -- clé principale
	label		varchar(30) NULL DEFAULT NULL ,		-- titre de la page
	description	varchar(60) NULL DEFAULT NULL ,		-- description du tableau de bord
	titlemenu	varchar(20) NULL DEFAULT NULL ,		-- menu principal
	mainmenu	varchar(50) NULL DEFAULT NULL ,		-- menu principal
	leftmenu	varchar(50) NULL DEFAULT NULL ,		-- menu à gauche
	posmenu		varchar(50) NULL DEFAULT 100 ,		-- position dans le menu
	elementtab	varchar(50) NULL DEFAULT NULL ,		-- tab dans un element
	author		varchar(50) NULL DEFAULT NULL ,		-- auteur de la liste
	blocAmode	integer NULL DEFAULT NULL ,			-- type du bloc A 0 = standard, 1 tableau + graphique, 2 graphique seulement
	blocBmode	integer NULL DEFAULT NULL ,			-- type du bloc B
	blocCmode	integer NULL DEFAULT NULL ,			-- type du bloc C
	blocDmode	integer NULL DEFAULT NULL ,			-- type du bloc D
	blocATitle	Varchar(20) NULL DEFAULT NULL ,		-- titre du bloc A 
	blocBTitle	Varchar(20) NULL DEFAULT NULL ,		-- titre du bloc B 
	blocCTitle	Varchar(20) NULL DEFAULT NULL ,		-- titre du bloc C 
	blocDTitle	Varchar(20) NULL DEFAULT NULL ,		-- titre du bloc D 
	active		integer NULL DEFAULT NULL ,			-- la liste est active ou pas
	perms		text NULL DEFAULT NULL ,			-- droit d'accès associé au menu
	langs		text NULL DEFAULT NULL ,			-- langue du menu et du tableau
	paramfields	text NULL DEFAULT NULL 				-- champs de paramétrages et de filtrage
)ENGINE=innodb;