<?php
/* Copyright (C) 2014	Charles-Fr BENLE		<charles.fr@benke.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/custom-parc/class/customtabs.class.php
 * \ingroup custom-tabs
 * \brief File of class to manage tabs
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class to manage members type
 */
class Dictionary extends CommonObject
{
	public $table_element = 'dictionary';
	var $id;
	
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	function __construct($db) {
		$this->db = $db;
	}
	
	/**
	 * Fonction qui permet de les infos de table
	 *
	 * @param int $rowid Id of member type to load
	 * @param string $tablename table
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id, $ref) {
		$this->id = 1;
		return 1;
	}
}
?>