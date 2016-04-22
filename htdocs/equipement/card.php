<?php
/* Copyright (C) 2012-2015	Charlie BENKE	<charlie@patas-monkey.com>
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
 * \file htdocs/equipement/card.php
 * \brief Fichier fiche equipement
 * \ingroup equipement
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include ($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory

require_once DOL_DOCUMENT_ROOT . "/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT . "/fourn/class/fournisseur.facture.class.php";
require_once DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT . "/contrat/class/contrat.class.php";
require_once DOL_DOCUMENT_ROOT . "/expedition/class/expedition.class.php";
require_once DOL_DOCUMENT_ROOT . "/fichinter/class/fichinter.class.php";
require_once DOL_DOCUMENT_ROOT . "/projet/class/project.class.php";

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php";

dol_include_once('/equipement/core/modules/equipement/modules_equipement.php');
dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');

if (! empty($conf->global->EQUIPEMENT_ADDON) && is_readable(dol_buildpath("/equipement/core/modules/equipement/" . $conf->global->EQUIPEMENT_ADDON . ".php"))) {
	dol_include_once("/equipement/core/modules/equipement/" . $conf->global->EQUIPEMENT_ADDON . ".php");
}

$langs->load("companies");
$langs->load("products");
$langs->load("equipement@equipement");

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$productid = GETPOST('productid', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$mesg = GETPOST('msg', 'alpha');
$socFournid = GETPOST('fk_soc_fourn', 'alpha');
$socClienid = GETPOST('fk_soc_client', 'alpha');
$nbAddEquipement = GETPOST('nbAddEquipement', 'int');
$SerialMethod = GETPOST('SerialMethod', 'int');

// Security check
if ($user->societe_id)
	$socid = $user->societe_id;
$result = restrictedArea($user, 'equipement', $id, 'equipement', '', 'fk_soc_client');

$object = new Equipement($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label("equipement");

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array (
		'equipementcard',
		'globalcard' 
));

$parameters = array (
		'product' => $product 
);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	
	/*
 * Actions
 */

if ($action == 'confirm_cutEquipement' && $confirm == 'yes' && $user->rights->equipement->creer) {
	if ($object->fetch($id)) {
		$ref_new = GETPOST("ref_new");
		$quantitynew = GETPOST("quantitynew");
		$cloneevent = GETPOST("cloneevent");
		$object->cut_equipement($ref_new, $quantitynew, $cloneevent);
		Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
		exit();
	}
}

if ($action == 'confirm_validate' && $confirm == 'yes' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$object->fetch_thirdparty();
	
	$result = $object->setValid($user, $conf->equipement->outputdir);
	if ($result >= 0) {
		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'alpha'))
			$newlang = GETPOST('lang_id', 'alpha');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->client->default_lang;
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		// génére le pdf qui n'existe pas pour le moment
		$result = equipement_create($db, $object, GETPOST('model', 'alpha'), $outputlangs);
		Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
		exit();
	} else {
		$mesg = '<div class="error">' . $object->error . '</div>';
	}
} 

else if ($action == 'confirm_modify' && $confirm == 'yes' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$object->fetch_thirdparty();
	
	$result = $object->setDraft($user);
	if ($result >= 0) {
		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'alpha'))
			$newlang = GETPOST('lang_id', 'alpha');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->client->default_lang;
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		$result = equipement_create($db, $object, (! GETPOST('model', 'alpha')) ? $object->model : GETPOST('model', 'apha'), $outputlangs);
		Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
		exit();
	} else {
		$mesg = '<div class="error">' . $object->error . '</div>';
	}
} 

else if ($action == 'add' && $user->rights->equipement->creer) {
	$object->fk_product = $productid;
	$object->fk_soc_fourn = $socFournid;
	$object->fk_soc_client = $socClienid;
	$object->author = $user->id;
	$object->unitweight = GETPOST('unitweight', 'alpha');
	$object->description = GETPOST('description');
	$object->ref = $ref;
	$object->fk_entrepot = GETPOST('fk_entrepot', 'alpha');
	$datee = dol_mktime('23', '59', '59', $_POST["dateemonth"], $_POST["dateeday"], $_POST["dateeyear"]);
	$object->datee = $datee;
	$dateo = dol_mktime('23', '59', '59', $_POST["dateomonth"], $_POST["dateoday"], $_POST["dateoyear"]);
	$object->dateo = $dateo;
	$object->note_private = GETPOST('note_private', 'alpha');
	$object->note_public = GETPOST('note_public', 'alpha');
	$object->quantity = GETPOST('quantity', 'int');
	$object->nbAddEquipement = $nbAddEquipement;
	$object->SerialMethod = $SerialMethod;
	$object->SerialFourn = GETPOST('SerialFourn', 'alpha');
	$object->numversion = GETPOST('numversion', 'alpha');
	$object->model_pdf = GETPOST('modelpdf', 'alpha');
	$object->fk_factory = GETPOST('factoryid', 'int');
	
	if ($object->fk_product > 0) {
		$result = $object->create();
		if ($result > 0) {
			$id = $result; // Force raffraichissement sur fiche venant d'etre cree
			$action = '';
		} else {
			$langs->load("errors");
			$mesg = '<div class="error">' . $langs->trans($object->error) . '</div>';
			$action = 'create';
		}
	} else {
		$mesg = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("ThirdParty")) . '</div>';
		$action = 'create';
	}
} 

/*
 * Build doc
 */
else if ($action == 'builddoc' && $user->rights->equipement->creer) // En get ou en post
{
	$object->fetch($id);
	$object->fetch_thirdparty();
	$object->fetch_lines();
	
	if (GETPOST('model', 'alpha')) {
		$object->setDocModel($user, GETPOST('model', 'alpha'));
	}
	
	// Define output language
	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'alpha'))
		$newlang = GETPOST('lang_id', 'alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang = $object->client->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$result = equipement_create($db, $object, GETPOST('model', 'alpha'), $outputlangs);
	if ($result <= 0) {
		dol_print_error($db, $result);
		exit();
	}
	$action = "";
} 

// Remove file in doc form
else if ($action == 'remove_file') {
	if ($object->fetch($id)) {
		require_once (DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");
		
		$object->fetch_thirdparty();
		
		$langs->load("other");
		$upload_dir = $conf->equipement->dir_output;
		$file = $upload_dir . '/' . GETPOST('file');
		dol_delete_file($file, 0, 0, 0, $object);
		$mesg = '<div class="ok">' . $langs->trans("FileWasRemoved", GETPOST('file')) . '</div>';
	}
} 

else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->equipement->supprimer) {
	$object->fetch($id);
	// $object->fetch_thirdparty();
	$object->delete($user);
	
	Header('Location: ' . DOL_URL_ROOT . '/equipement/list.php?leftmenu=equipement');
	exit();
} else if ($action == 'confirm_delete' && $confirm != 'yes' && $user->rights->equipement->supprimer) {
	$action = '';
} else if (substr($action, 0, 7) == 'setExFi' && $user->rights->equipement->creer) {
	$keyExFi = substr($action, 7);
	$object->fetch($id, $ref);
	$res = $object->fetch_optionals($object->id, $extralabels);
	$object->array_options["options_" . $keyExFi] = $_POST["options_" . $keyExFi];
	$object->insertExtraFields();
} 

else if ($action == 'setnumref' && $user->rights->equipement->majserial) {
	$object->fetch($id);
	$result = $object->set_numref($user, GETPOST('editnumref', 'alpha'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setnumversion' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->set_numversion($user, GETPOST('numversion', 'alpha'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setquantity' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->set_quantity($user, GETPOST('quantity', 'alpha'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setnumimmocompta' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->set_numimmocompta($user, GETPOST('numimmocompta', 'alpha'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setentrepot' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->set_entrepot($user, GETPOST('fk_entrepot', 'alpha'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setdescription' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->set_description($user, GETPOST('description'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setnote_public' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setnote_private' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setetatequip' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->set_etatEquipement($user, GETPOST('fk_etatequipement'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} 

else if ($action == 'setclient' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->set_client($user, GETPOST('fk_soc_client'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setfactclient' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->set_fact_client($user, GETPOST('fk_fact_client'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setfactfourn' && $user->rights->equipement->creer) {
	$object->fetch($id);
	$result = $object->set_fact_fourn($user, GETPOST('fk_facture_fourn'));
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setdatee') {
	$datee = dol_mktime('23', '59', '59', $_POST["dateemonth"], $_POST["dateeday"], $_POST["dateeyear"]);
	
	$object->fetch($id);
	$result = $object->set_datee($user, $datee);
	if ($result < 0)
		dol_print_error($db, $object->error);
} else if ($action == 'setdateo') {
	$dateo = dol_mktime('23', '59', '59', $_POST["dateomonth"], $_POST["dateoday"], $_POST["dateoyear"]);
	
	$object->fetch($id);
	$result = $object->set_dateo($user, $dateo);
	if ($result < 0)
		dol_print_error($db, $object->error);
}

/*
 * View
 */
$extralabels = $extrafields->fetch_name_optionals_label('equipement');

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader('',$langs->trans('Equipements'));

if ($action == 'create') {
	/*
	 * Mode creation
	 * Creation d'un nouvel équipement
	 */
	
	$prod = new Product($db);
	
	print_fiche_titre($langs->trans("AddEquipement"));
	
	dol_htmloutput_mesg($mesg);
	
	if (! $conf->global->EQUIPEMENT_ADDON) {
		dol_print_error($db, $langs->trans("Error") . " " . $langs->trans("Error_EQUIPEMENT_ADDON_NotDefined"));
		Print $langs->trans("Error_EQUIPEMENT_ADDON_NotDefined");
		exit();
	}
	
	$object->date = dol_now();
	
	$obj = $conf->global->EQUIPEMENT_ADDON;
	// $obj = "mod_".$obj;
	
	$modequipement = new $obj();
	$numpr = $modequipement->getNextValue($soc, $object);
	
	if ($productid > 0) {
		$prod->fetch($productid);
		
		print '<form name="equipement" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="SerialMethod" value=' . GETPOST('SerialMethod') . '>';
		print '<input type="hidden" name="productid" value=' . $prod->id . '>';
		print '<input type="hidden" name="factoryid" value=' . GETPOST('factoryid') . '>';
		
		print '<table class="border" width="100%">';
		
		print '<tr><td class="fieldrequired"><table class="nobordernopadding" width="100%"><tr><td >' . $langs->trans("Product") . '</td><td align=right>';
		if (!empty($prod->description)) {
			print "<a href=# onclick=\"$('#descprod').toggle();\" >" . img_picto("", "edit_add") . "</a>";
		}
		print '</td></tr></table></td><td colspan="3">';
		print $prod->getNomUrl(1) . " : " . $prod->label . '</td></tr>';
		if (!empty($prod->description)) {
			print "<tr style='display:none' id='descprod'>";
			print '<td colspan=2>' . $prod->description . '</td><tr>';
		}
		
		print '<tr><td ' . (GETPOST('SerialMethod') == 3 ? ' class="fieldrequired" ' : '') . ' >';
		print $langs->trans("VersionNumber") . '</td><td><input name="numversion" value="' . GETPOST('numversion') . '"></td></tr>';
		// si produit a des fournisseurs
		$LstSupplier = $prod->list_suppliers();
		if (count($LstSupplier)) {
			print '<tr><td >' . $langs->trans("Fournisseur") . '</td><td>';
			// si un seul fournisseur on l'affiche sans choix possible
			if (count($LstSupplier) == 1) {
				print '<input type="hidden" name="fk_soc_fourn" value=' . $LstSupplier[0] . '>';
				$soc = new Societe($db);
				$soc->fetch($LstSupplier[0]);
				print $soc->getNomUrl(1);
			} else {
				// sinon c'est une liste de sélection
				$filterList = "";
				foreach ( $LstSupplier as &$arr ) {
					$filterList .= " s.rowid=" . $arr . " or";
				}
				// on vire le dernier 'or' et on rajoute la parenthése de fin
				$filterList = "( " . substr($filterList, 0, - 2) . ")";
				print $form->select_company('', 'fk_soc_fourn', $filterList, 1);
			}
			print '</td></tr>';
		}
		
		switch (GETPOST('SerialMethod')) {
			case 1 : // Internal Serial
			         // Si c'est un produit interne on génére nous-méme les numéros de série
				print '<tr><td class="fieldrequired">' . $langs->trans("RefStart") . '</td>';
				print '<td><input type=text name="ref" value="' . $numpr . '" disabled="disabled"></td></tr>' . "\n";
				print '<tr><td class="fieldrequired">' . $langs->trans("nbEquipementToCreate") . '</td>';
				print '<td >' . '<input type=text name="nbAddEquipement" value="' . (GETPOST("qtyEquipement") ? GETPOST("qtyEquipement") : "1") . '">';
				print '<input type=hidden name="quantity" value="1">';
				print "</td></tr>\n";
				break;
			
			case 2 : // External Serial
				print '<tr><td class="fieldrequired">';
				print $form->textwithpicto($langs->trans("ExternalSerial"), $langs->trans("YouCanAddMultipleSerialWithSeparator"), 1) . '</td>';
				print '<td>';
				print '<textarea name="SerialFourn" cols="80" rows="' . ROWS_3 . '"></textarea>';
				print '<input type=hidden name="quantity" value="1">';
				print '</td></tr>' . "\n";
				break;
			
			case 3 : // Series Mode
				print '<tr><td class="fieldrequired">' . $langs->trans("Quantity") . '</td>';
				print '<td><input type=text name="quantity" value="' . (GETPOST("qtyEquipement") ? GETPOST("qtyEquipement") : "1") . '">';
				print '<input type=hidden name="nbAddEquipement" value="1">';
				print '</td></tr>' . "\n";
				break;
		}
		
		// poid du produit
		print '<tr><td >' . $langs->trans("UnitWeight") . '</td>';
		print '<td><input type=text size=4 name="unitweight" value="">';
		print '</td></tr>' . "\n";
		
		// l'entrepot d'affectation est saisissable par défaut
		print '<tr><td >' . $langs->trans("EntrepotStock") . '</td><td>';
		select_entrepot(GETPOST('fk_entrepot'), 'fk_entrepot', 1, 1);
		print '</td></tr>' . "\n";
		
		// le client est saisissable aussi é la création pour gérer le pb des accés limité
		print '<tr><td >' . $langs->trans("Client") . '</td><td>';
		print $form->select_company($object->fk_soc_client, 'fk_soc_client', '', 1);
		print '</td></tr>' . "\n";
		
		// Date open
		print '<tr><td>' . $langs->trans("DateoLong") . '</td><td>';
		print $form->select_date($object->dateo, 'dateo', 0, 0, '', "dateo");
		print '</td></tr>' . "\n";
		
		// Date end
		print '<tr><td>' . $langs->trans("DateeLong") . '</td><td>';
		print $form->select_date('', 'datee', 0, 0, 1, "datee");
		print '</td></tr>' . "\n";
		
		// Description (must be a textarea and not html must be allowed (used in list view)
		print '<tr><td valign="top">' . $langs->trans("Description") . '</td>';
		print '<td>';
		print '<textarea name="description" cols="80" rows="' . ROWS_3 . '"></textarea>';
		print '</td></tr>';
		
		// Model
		print '<tr>';
		print '<td>' . $langs->trans("DefaultModel") . '</td>';
		print '<td colspan="2">';
		$liste = ModeleEquipement::liste_modeles($db);
		print $form->selectarray('model', $liste, $conf->global->EQUIPEMENT_ADDON_PDF);
		print "</td></tr>";
		
		// Public note
		print '<tr>';
		print '<td class="border" valign="top">' . $langs->trans('NotePublic') . '</td>';
		print '<td valign="top" colspan="2">';
		print '<textarea name="note_public" cols="80" rows="' . ROWS_3 . '"></textarea>';
		print '</td></tr>';
		
		// Private note
		if (! $user->societe_id) {
			print '<tr>';
			print '<td class="border" valign="top">' . $langs->trans('NotePrivate') . '</td>';
			print '<td valign="top" colspan="2">';
			print '<textarea name="note_private" cols="80" rows="' . ROWS_3 . '"></textarea>';
			print '</td></tr>';
		}
		
		print '</table>';
		
		print '<center><br>';
		print '<input type="submit" class="button" value="' . $langs->trans("CreateDraftEquipement") . '">';
		print '</center>';
		
		print '</form>';
	} else {
		// premiere étape on sélectionne le produit correspondand é l'équipement
		print '<form name="equipement" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
		print '<table class="border" width="100%">';
		print '<tr><td class="fieldrequired">' . $langs->trans("Products") . '</td><td>';
		print $form->select_produits('', 'productid', 0, $conf->product->limit_size, 0, - 1, 2, '', 0);
		print '</td></tr>';
		print '<tr><td class="fieldrequired">' . $langs->trans("EquipmentSerialMethod") . '</td><td>';
		$arraySerialMethod = array (
				'1' => $langs->trans("InternalSerial"),
				'2' => $langs->trans("ExternalSerial"),
				'3' => $langs->trans("SeriesMode") 
		);
		print $form->selectarray("SerialMethod", $arraySerialMethod);
		print '</td></tr>';
		print '</table>';
		print '<br><center>';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="submit" class="button" value="' . $langs->trans("CreateDraftEquipement") . '">';
		print '</center>';
		
		print '</form>';
	}
} else if ($id > 0 || ! empty($ref)) {
	/*
	 * Affichage en mode visu
	 */
	
	$object->fetch($id, $ref);
	if (! $id)
		$id = $object->id;
	$object->fetch_thirdparty();
	$res = $object->fetch_optionals($object->id, $extralabels);
	
	dol_htmloutput_mesg($mesg);
	
	$head = equipement_prepare_head($object);
	
	dol_fiche_head($head, 'card', $langs->trans("EquipementCard"), 0, 'equipement@equipement');
	
	// Confirmation de la suppression de l'équipement
	if ($action == 'delete') {
		$ret = $form->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteEquipement'), $langs->trans('ConfirmDeleteEquipement'), 'confirm_delete', '', 0, 1);
		if ($ret == 'html')
			print '<br>';
	}
	
	// Confirmation validation
	if ($action == 'validate') {
		$ret = $form->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ValidateEquipement'), $langs->trans('ConfirmValidateEquipement'), 'confirm_validate', '', 0, 1);
		if ($ret == 'html')
			print '<br>';
	}
	
	// Confirmation de la validation de la fiche d'intervention
	if ($action == 'modify') {
		$ret = $form->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ModifyEquipement'), $langs->trans('ConfirmModifyEquipement'), 'confirm_modify', '', 0, 1);
		if ($ret == 'html')
			print '<br>';
	}
	
	// Confirmation de la suppression d'une ligne d'intervention
	if ($action == 'ask_deleteline') {
		$ret = $form->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&line_id=' . GETPOST('line_id', 'int'), $langs->trans('DeleteEquipementLine'), $langs->trans('ConfirmDeleteEquipementLine'), 'confirm_deleteline', '', 0, 1);
		if ($ret == 'html')
			print '<br>';
	}
	
	print '<table class="border" width="100%">';
	
	// Ref
	print '<tr><td width=250px><table class="nobordernopadding" width="100%"><tr><td >' . $langs->trans("Ref") . '</td>';
	if ($action != 'editnumref' && $object->statut == 0 && $user->rights->equipement->majserial) { // si l'équipement est é l'état brouillon et l'habilition requise est active on a le droit de modifier la référence
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editnumref&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editnumref') {
		print '<form name="editnumref" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setnumref">';
		print '<input type="text" name="editnumref" value="' . $object->ref . '">';
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		$linkback = '<a href="' . DOL_URL_ROOT . '/equipement/list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	}
	print '</td></tr>';
	
	// produit
	$prod = new Product($db);
	$prod->fetch($object->fk_product);
	print '<tr><td class="fieldrequired"><table class="nobordernopadding" width="100%"><tr><td >' . $langs->trans("Product") . '</td><td align=right>';
	if (!empty($prod->description)) {
		print "<a href=# onclick=\"$('#descprod').toggle();\" >" . img_picto("", "edit_add") . "</a>";
	}
	print '</td></tr></table></td><td colspan="3">';
	print $prod->getNomUrl(1) . " : " . $prod->label . '</td></tr>';
	if (!empty($prod->description)) {
		print "<tr style='display:none' id='descprod'>";
		print '<td></td><td>' . $prod->description . '</td><tr>';
	}
	
	// Numéro de version
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("VersionNumber") . '</td>';
	if ($action != 'editnumversion' && $object->statut == 0)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editnumversion&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editnumversion') {
		print '<form name="editnumversion" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setnumversion">';
		print '<input type="text" name="numversion" value="' . $object->numversion . '">';
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		print $object->numversion;
	}
	print '</td></tr>';
	
	// quantité modifiable et visible uniquement si supérieur é 1
	if ($object->quantity > 1) {
		print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("Quantity") . '</td>';
		if ($action != 'editquantity' && $object->statut == 0)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editquantity&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
		print '</tr></table></td><td colspan="3">';
		if ($action == 'editquantity') {
			print '<form name="editquantity" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setquantity">';
			print '<input type="text" name="quantity" value="' . $object->quantity . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->quantity;
		}
		print '</td></tr>';
	}
	
	// fournisseur, lui on ne le change pas, si pas bon on supprime l'équipement
	print '<tr><td class="fieldrequired">' . $langs->trans("Fournisseur") . '</td><td>';
	if ($object->fk_soc_fourn > 0) {
		$soc = new Societe($db);
		$soc->fetch($object->fk_soc_fourn);
		print $soc->getNomUrl(1);
	}
	print '</td></tr>';
	
	// facture fournisseur
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("RefFactFourn") . '</td>';
	// la facture founisseur est modifiable ssi il y a un founisseur de sélectionné
	if ($action != 'editfactfourn' && $object->statut == 0 && $object->fk_soc_fourn > 0) {
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfactfourn&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	}
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editfactfourn') {
		print '<form name="editfactfourn" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setfactfourn">';
		// liste des factures fournisseurs disponible
		print select_factfourn($object->fk_fact_fourn, $object->fk_soc_fourn, 'fk_facture_fourn', 1, 1);
		
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		if ($object->fk_fact_fourn) {
			$factfournstatic = new FactureFournisseur($db);
			$factfournstatic->fetch($object->fk_fact_fourn);
			print $factfournstatic->getNomUrl(1) . ' - ' . dol_print_date($factfournstatic->date, 'day') . ' - ' . price($factfournstatic->total_ttc);
		}
	}
	print '</td></tr>';
	
	// Lieu de stockage
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("EntrepotStock") . '</td>';
	if ($action != 'editstock' && $object->statut == 0)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editstock&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editstock') {
		print '<form name="editstock" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setentrepot">';
		select_entrepot($object->fk_entrepot, 'fk_entrepot', 1, 1);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		if ($object->fk_entrepot > 0) {
			$entrepotStatic = new Entrepot($db);
			$entrepotStatic->fetch($object->fk_entrepot);
			print $entrepotStatic->getNomUrl(1) . " - " . $entrepotStatic->lieu . " (" . $entrepotStatic->zip . ")";
		}
	}
	print '</td></tr>';
	
	// Client
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("Client") . '</td>';
	if ($action != 'editclient' && $object->statut == 0)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editclient&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editclient') {
		print '<form name="editclient" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setclient">';
		print $form->select_company($object->fk_soc_client, 'fk_soc_client', '', 1);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		if ($object->fk_soc_client > 0) {
			$soc = new Societe($db);
			$soc->fetch($object->fk_soc_client);
			print $soc->getNomUrl(1);
		}
	}
	print '</td></tr>';
	
	// facture client
	// on autorise la saisie de la facture client SSI il y a un client de sélectionné
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("RefFactClient") . '</td>';
	if ($action != 'editfactclient' && $object->fk_soc_client && $object->statut == 0)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfactclient&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editfactclient') {
		print '<form name="editfactclient" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setfactclient">';
		print select_facture($object->fk_fact_client, $object->fk_soc_client, 'fk_fact_client', '', 1, 1);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		if ($object->fk_fact_client) {
			$facturestatic = new Facture($db);
			$facturestatic->fetch($object->fk_fact_client);
			print $facturestatic->getNomUrl(1) . ' - ' . dol_print_date($facturestatic->date, 'day') . ' - ' . price($facturestatic->total_ttc);
		}
	}
	print '</td></tr>';
	
	// Date start
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("DateoLong") . '</td>';
	if ($action != 'editdateo' && $object->statut == 0)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdateo&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editdateo') {
		print '<form name="editdateo" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setdateo">';
		print $form->select_date($object->dateo, 'dateo', 0, 0, '', "dateo");
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		print dol_print_date($object->dateo, 'day');
	}
	print '</td></tr>';
	
	// Date end
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("DateeLong") . '</td>';
	if ($action != 'editdatee' && $object->statut == 0)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdatee&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editdatee') {
		print '<form name="editdatee" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setdatee">';
		print $form->select_date($object->datee, 'datee', 0, 0, '', "datee");
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		print dol_print_date($object->datee, 'day');
	}
	print '</td></tr>';
	
	// Extrafields
	$parameters = array (
			'colspan' => ' colspan="3"' 
	);
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
	if (empty($reshook) && ! empty($extrafields->attribute_label)) {
		
		foreach ( $extrafields->attribute_label as $key => $label ) {
			$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $object->array_options["options_" . $key]);
			
			print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $label . '</td>';
			if ($action != 'ExFi' . $key && $object->statut == 0)
				print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=ExFi' . $key . '&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
			print '</tr></table></td><td colspan="3">';
			if ($action == 'ExFi' . $key) {
				print '<form name="ExFi' . $key . '" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
				print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
				print '<input type="hidden" name="action" value="setExFi' . $key . '">';
				print $extrafields->Showinputfield($key, $value);
				print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
				print '</form>';
			} else {
				print $extrafields->showOutputField($key, $value);
			}
			
			print '</td></tr>' . "\n";
		}
	}
	
	// Description (must be a textarea and not html must be allowed (used in list view)
	print '<tr><td valign="top">';
	print $form->editfieldkey("Description", 'description', $object->description, $object, $user->rights->equipement->creer, 'textarea');
	print '</td><td colspan="3">';
	print $form->editfieldval("Description", 'description', $object->description, $object, $user->rights->equipement->creer, 'textarea');
	print '</td>';
	print '</tr>';
	
	// Etat de l'équipement
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("EtatEquip") . '</td>';
	if ($action != 'editetatequip' && $object->statut == 0)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editetatequip&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editetatequip') {
		print '<form name="editetatequip" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setetatequip">';
		print select_equipement_etat($object->fk_etatequipement, 'fk_etatequipement', 1, 1);
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		if ($object->etatequiplibelle)
			print $langs->trans($object->etatequiplibelle);
	}
	
	print '</td></tr>';
	
	// Numéro de immo compta
	print '<tr><td><table class="nobordernopadding" width="100%"><tr><td>' . $langs->trans("NumImmoCompta") . '</td>';
	if ($action != 'editnumimmo' && $object->statut == 0)
		print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editnumimmo&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
	print '</tr></table></td><td colspan="3">';
	if ($action == 'editnumimmo') {
		print '<form name="editnumimmo" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="setnumimmocompta">';
		print '<input type="text" name="numimmocompta" value="' . $object->numimmocompta . '">';
		print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
		print '</form>';
	} else {
		print $object->numimmocompta;
	}
	print '</td></tr>';
	
	// Statut
	print '<tr><td>' . $langs->trans("Status") . '</td><td>' . $object->getLibStatut(4) . '</td></tr>';
	
	print "</table><br>";
	
	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB)) {
		require_once (DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
		require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
		$formcompany = new FormCompany($db);
		
		$blocname = 'contacts';
		$title = $langs->trans('ContactsAddresses');
		include (DOL_DOCUMENT_ROOT . '/core/tpl/bloc_showhide.tpl.php');
	}
	
	if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$blocname = 'notes';
		$title = $langs->trans('Notes');
		include (DOL_DOCUMENT_ROOT . '/core/tpl/bloc_showhide.tpl.php');
	}
}
print '</div>';
print "\n";

// Define confirmation messages
$formquestioncutEquipement = array (
		'text' => $langs->trans("ConfirmCut"),
		array (
				'type' => 'text',
				'name' => 'ref_new',
				'label' => $langs->trans("NewRefForCutEquipment"),
				'value' => $object->ref . " (1)",
				'size' => 24 
		),
		array (
				'type' => 'text',
				'name' => 'quantitynew',
				'label' => $langs->trans("QuantitytoCut"),
				'value' => '1',
				'size' => 5 
		),
		array (
				'type' => 'checkbox',
				'name' => 'cloneevent',
				'label' => $langs->trans("CloneContentEquipment"),
				'value' => 1 
		) 
);

// Clone confirmation
if ($action == 'cutEquipment' && empty($conf->use_javascript_ajax)) {
	print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CutEquipment'), $langs->trans('ConfirmcutEquipement', $object->ref), 'confirm_cutEquipement', $formquestioncutEquipement, 'yes', 'action-cutEquimenent', 230, 600);
}

/* Barre d'action				*/
if ($action == '') {
	print '<div class="tabsAction">';
	
	$parameters = array ();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $equipement, $action); // Note that $action and $object may have been
	                                                                                                   // modified by hook
	if (empty($reshook)) {
		// Découpage d'un lot si il y en a plus d'un
		if ($object->quantity > 1 && $user->rights->equipement->creer) {
			if (! empty($conf->use_javascript_ajax)) {
				print '<div class="inline-block divButAction"><span id="action-cutEquimenent" class="butAction">' . $langs->trans('CutSerial') . '</span></div>' . "\n";
				print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CutEquipment'), $langs->trans('ConfirmcutEquipement', $object->ref), 'confirm_cutEquipement', $formquestioncutEquipement, 'yes', 'action-cutEquimenent', 230, 600);
			} else {
				print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=cutEquipment"';
				print '>' . $langs->trans("CutSerial") . '</a>';
			}
		}
		
		// Validate
		if ($object->statut == 0 && $user->rights->equipement->creer) {
			print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=validate"';
			print '>' . $langs->trans("Valid") . '</a>';
		}
		
		// Modify
		if ($object->statut == 1 && $user->rights->equipement->creer) {
			print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=modify"';
			print '>' . $langs->trans("Modify") . '</a>';
		}
		
		// Delete
		if (($object->statut == 0 && $user->rights->equipement->creer) || $user->rights->equipement->supprimer) {
			print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete"';
			print '>' . $langs->trans('Delete') . '</a>';
		}
	}
	
	print '</div>';
	print '<br>';
	
	print '<table width="100%"><tr><td width="50%" valign="top">';
	/*
	 * Built documents
	 */
	$filename = dol_sanitizeFileName($object->ref);
	$filedir = $conf->equipement->dir_output . "/" . $object->ref;
	$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
	$genallowed = $user->rights->equipement->creer;
	$delallowed = $user->rights->equipement->supprimer;
	
	$var = true;
	
	print "<br>\n";
	$somethingshown = $formfile->show_documents('equipement', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);
	
	$somethingshown = $object->showLinkedObjectBlock();
	
	print "</td><td>";
	print "&nbsp;</td>";
	print "</tr></table>\n";
}

llxFooter();

$db->close();
?>
