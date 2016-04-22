<?php
/* Copyright (C) 2014	charles-Fr Benke	<charles.fr@benke.fr>
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
 * \file htdocs/customtabs/class/actions_customtabs.class.php
 * \ingroup customtabs
 * \brief Fichier de la classe des actions/hooks des �quipements
 */
class ActionsCustomtabs // extends CommonObject
{
	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 * 
	 * @param parameters meta datas of the hook (context, etc...)
	 * @param object the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param action current action (if set). Generally create or edit or null
	 * @return void
	 */
	function beforeODTSave($parameters, $object, $action) {
		global $conf, $langs, $db;
		$localHandler = $parameters['odfHandler'];
		
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		require_once DOL_DOCUMENT_ROOT . '/customtabs/class/customtabs.class.php';
		
		$extrafields = new ExtraFields($db);
		$customtabs = new Customtabs($db);
		
		// variables de limitation des contacts � r�cup�rer
		$nbLineOnODT = $conf->global->nbLineOnODT;
		
		$tbltabs = $customtabs->liste_array($elementselect, 2); // on g�re ici les onglets de type listes sont g�r�es ailleurs
		if (count($tbltabs) > 0) {
			foreach ( $tbltabs as $customtabsarray ) {
				$elementtype = "cust_" . $customtabsarray['tablename'];
				// r�cup�ration des valeurs des champs
				$sql = "SELECT * FROM " . MAIN_DB_PREFIX . $elementtype . "_extrafields";
				$sql .= " WHERE fk_object = " . $parameters['object']->id;
				// print "==".$sql;
				$resql = $db->query($sql);
				if ($resql) {
					try {
						$num = $db->num_rows($resql);
						// pour limiter si demand� les enregistrements
						if ($nbLineOnODT > 0 && $num > $nbLineOnODT)
							$num = $nbLineOnODT;
						$i = 0;
						// print html_entity_decode($localHandler->__toString());
						if ($num) {
							$listlines = $localHandler->setSegment('lines_' . $elementtype);
							// on boucle sur les lignes de l'onglet
							while ( $i < $num ) {
								$objp = $db->fetch_object($resql);
								$extrafields->fetch_name_optionals_label($elementtype);
								foreach ( $extrafields->attribute_type as $key => $value ) {
									try {
										$listlines->setVars('lines_' . $elementtype . "_" . $key, $val, true, 'UTF-8');
									} catch ( OdfException $e ) {
									} // pour d�sactiver cette erreur
catch ( SegmentException $e ) {
									} // pour d�sactiver cette erreur
								}
								$listlines->merge();
								$i ++;
							}
							$localHandler->mergeSegment($listlines);
						}
					} catch ( OdfException $e ) {
						// print $e->getMessage();
						$this->error = $e->getMessage();
						dol_syslog($this->error, LOG_WARNING);
						return - 1;
					}
				}
			}
		}
		
		return 0;
	}
}
?>