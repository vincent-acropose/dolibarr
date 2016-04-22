<?php
/* Copyright (C) 2014		Charles-Fr BENKE	<charles.fr@benke.fr>
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
 * or see http://www.gnu.org/
 */

/**
 * Function called to complete substitution array (before generating on ODT, or a personalized email)
 * functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * is inside directory htdocs/core/substitutions
 *
 * @param array $substitutionarray Array with substitution key=>val
 * @param Translate $langs Output langs
 * @param Object $object Object to use to get values
 * @return void The entry parameter $substitutionarray is modified
 */
function customtabs_completesubstitutionarray(&$substitutionarray, $langs, $object) {
	global $conf, $db;
	
	require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
	require_once DOL_DOCUMENT_ROOT . '/customtabs/class/customtabs.class.php';
	
	// cust_tablename_fieldname
	$extrafields = new ExtraFields($db);
	$customtabs = new Customtabs($db);
	
	// on r�cup�re les infos de la soci�t�
	$elementselect = 'thirdparty';
	$tbltabs = $customtabs->liste_array($elementselect, 1); // on ne s�lectionne que les onglets de type fiche, les listes sont g�r�es ailleurs
	if (count($tbltabs) > 0) {
		foreach ( $tbltabs as $customtabsarray ) {
			$elementtype = "cust_" . $customtabsarray['tablename'];
			// r�cup�ration des valeurs des champs
			$sql = "SELECT * FROM " . MAIN_DB_PREFIX . $elementtype . "_extrafields";
			$sql .= " WHERE fk_object = " . $object->socid;
			
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql)) {
					$obj = $db->fetch_object($resql);
					// r�cup des champs de la table
					$extrafields->fetch_name_optionals_label($elementtype);
					foreach ( $extrafields->attribute_type as $key => $value ) {
						$odtfieldname = $elementtype . "_" . $key;
						// on alimente le dictionnaire
						$substitutionarray[$odtfieldname] = $obj->$key;
					}
				}
			}
		}
	}
	
	// puis ceux associ� � l'�l�ment
	if ($object->element == 'societe')
		$elementselect = 'thirdparty';
	else
		$elementselect = $object->element;
	
	$tbltabs = $customtabs->liste_array($elementselect, 1);
	// var_dump($tbltabs );
	if (count($tbltabs) > 0) {
		foreach ( $tbltabs as $customtabsarray ) { // var_dump($customtabsarray);
			$elementtype = "cust_" . $customtabsarray['tablename'];
			// r�cup�ration des valeurs des champs
			$sql = "SELECT * FROM " . MAIN_DB_PREFIX . $elementtype . "_extrafields";
			$sql .= " WHERE fk_object = " . $object->id;
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql)) {
					$obj = $db->fetch_object($resql);
					// r�cup des champs de la table
					$extrafields->fetch_name_optionals_label($elementtype);
					foreach ( $extrafields->attribute_type as $key => $value ) {
						$odtfieldname = $elementtype . "_" . $key;
						$substitutionarray[$odtfieldname] = $obj->$key;
					}
				} else {
					// si pas de ligne d'extrafields (non renseign�)
					// on remplace les zones par des emplacements vide
					$extrafields->fetch_name_optionals_label($elementtype);
					foreach ( $extrafields->attribute_type as $key => $value ) {
						// pour afficher les zones � vide
						$odtfieldname = $elementtype . "_" . $key;
						$substitutionarray[$odtfieldname] = "";
					}
				}
			}
		}
	}
}
?>