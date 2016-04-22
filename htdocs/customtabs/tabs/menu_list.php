<?php
/* Copyright (C) 2014-2015	Charlie BENKE	<charlie@patas-monkey.com>
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
 * \file htdocs/customtabs/tabs/menu_list.php
 * \ingroup customtabs
 * \brief Page of customtab mode list
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include ($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once DOL_DOCUMENT_ROOT . '/customtabs/core/lib/customtabs.lib.php';
require_once DOL_DOCUMENT_ROOT . '/customtabs/class/customtabs.class.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

$langs->load("customtabs@customtabs");
$langs->load("users");
$langs->load('other');

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');
if ($socid && ! $id)
	$id = $socid;

$ref = GETPOST('ref', 'alpha');

$confirm = GETPOST('confirm', 'alpha');
$tabsidmenu = GETPOST('tabsid', 'int');

// on récupère la classe associé au complément si elle a été générée, pas encore d'actualité
// $file=DOL_DOCUMENT_ROOT.'/customtabs/compl_class/'.GETPOST("tabsid").'.class.php';
// if (file_exists($file))
// {
// $res=require_once $file;
// }

$form = new Form($db);

$customtabsSsMenu = new CustomTabs($db);
$customtabsSsMenu->fetch($tabsidmenu);
$tabsid = $customtabsSsMenu->fk_parent;

$object = $customtabsSsMenu->element_setting();
$result = $object->fetch($id, $ref);

// customtabs du menu
$customtabs = new CustomTabs($db);
$customtabs->fetch($tabsid);

if ($ref)
	$id = $object->id;
	
	// Determine user rights according type of ressource
$user_specials_rights = $customtabsSsMenu->getUserSpecialsRights($user);

$errmsg = '';
$errmsgs = array ();

// if something wrong in Load member and extrafields
if ($result < 0) {
	dol_print_error($db, $object->error);
	exit();
}

if (($action == 'modify') && $user_specials_rights['edit'] != 1) {
	accessforbidden();
}
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($customtabs->table_element);
// fetch spécial liste
$res = $customtabs->fetch_optionals($id, $extralabels);

$extrafieldsSsMenu = new ExtraFields($db);
$extralabels = $extrafieldsSsMenu->fetch_name_optionals_label($customtabsSsMenu->table_element);

$res = $customtabsSsMenu->fetch_optionalslist($id, $extralabels);
/*
 * 	Actions
 */

// pas de gestion de fichiers en mode liste

if ($action == 'setextrafields' && $user_specials_rights['edit']) {
	if ($result > 0) {
		$extralabels = $extrafieldsSsMenu->fetch_name_optionals_label($customtabsSsMenu->table_element);
		$customtabs->id = $id; // on utilise l'id de l'enregistrement
		$ret = $extrafieldsSsMenu->setOptionalsFromPost($extralabels, $customtabsSsMenu);
		$rescust = $customtabsSsMenu->editExtraFields_line($id, GETPOST('linerowid'));
		if ($rescust < 0) {
			$error ++;
			if ($error)
				$action = 'addline';
		} else {
			$action = '';
			header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/menu_list.php?tabsid=' . $tabsidmenu . '&id=' . $id);
			exit();
		}
	}
}
if ($action == 'addextrafields' && $user_specials_rights['create']) {
	if ($result > 0) {
		$nblineAdd = GETPOST("nblineAdd");
		$extralabels = $extrafieldsSsMenu->fetch_name_optionals_label($customtabsSsMenu->table_element);
		
		$error = 0;
		
		$myarray = array ();
		foreach ( $extralabels as $key => $value )
			$myarray[$key] = $value;
		
		for($i = 1; $i <= $nblineAdd; $i ++) {
			$ret = $customtabsSsMenu->setOptionalsFromPost($myarray, $customtabsSsMenu, '', $i);
			$rescust = $customtabsSsMenu->insertExtraFields_line($id, $i);
			if ($rescust < 0) {
				$error ++;
				if ($error)
					$action = 'addline';
			}
		}
		if ($error == 0) {
			$action = '';
			header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/menu_list.php?tabsid=' . $tabsidmenu . '&id=' . $id);
			exit();
		}
	}
}
if ($action == 'delextrafields' && $user_specials_rights['delete']) {
	if ($result > 0) {
		$result = $customtabsSsMenu->deleteExtraFields_line(GETPOST('linerowid'));
		$action = '';
		header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/menu_list.php?tabsid=' . $tabsidmenu . '&id=' . $id);
		exit();
	}
}

/*
 * View
 */

/**
 * ******************************************
 *
 * Liste en Visualisation
 *
 * ******************************************
 */

dol_htmloutput_errors($errmsg, $errmsgs);
dol_htmloutput_mesg($mesg);

$customtabs->tabs_head_element($tabsid);

// Extrafields
if (! empty($extrafields->attribute_label)) {
	// gestion des templates
	if ($customtabs->template) {
		$template = $customtabs->template;
		$customtabs->id = $id; // on utilise l'id de l'enregistrement
		foreach ( $extrafields->attribute_label as $key => $label ) {
			
			$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $customtabs->array_options["options_" . $key]);
			
			// gestion du multilingue, attention au cas de traduction foireuse
			if ($langs->trans($key) != $key && $langs->trans($key) != 'ErrorBadValueForParamNotAString')
				$label = $langs->trans($key);
			
			$template = str_replace("#LABEL-" . $key . "#", $label, $template);
			$fields = $extrafields->showOutputField($key, $value);
			$template = str_replace("#FIELD-" . $key . "#", $fields, $template);
		}
		print $template . "\n";
	} else {
		print '<table class="border" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="4">' . $langs->trans("TabsFieldsView") . '</th>';
		$customtabs->id = $id; // on utilise l'id de l'enregistrement
		foreach ( $extrafields->attribute_label as $key => $label ) {
			
			$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $customtabs->array_options["options_" . $key]);
			
			// gestion du multilingue, attention au cas de traduction foireuse
			if ($langs->trans($key) != $key && $langs->trans($key) != 'ErrorBadValueForParamNotAString')
				$label = $langs->trans($label);
			print '<tr><td width=25% nowrap>' . $label . '</td>';
			print '<td colspan="3">';
			print $extrafields->showOutputField($key, $value);
			print '</td></tr>' . "\n";
		}
	}
	print "</table>";
}

print "<br>";

// on gère les sous-menus si il y en a
$head = customtabs_prepare_head_menu($object, $tabsid);

if ($customtabs->element == 'thirdparty')
	$icontabs = "company";
else
	$icontabs = $customtabs->element;
	
	// dol_fiche_head($head, GETPOST("tablename"), $complement->libelle, 0, 'user');
dol_fiche_head($head, "customtabs_" . $tabsidmenu, $customtabs->libelle, 0, $icontabs);
// Extrafields
if (! empty($extrafieldsSsMenu->attribute_label)) {
	
	/*
	 * Barre d'actions Extrafields
	 */
	// Modify
	if ($action != "addline" && $action != "edit" && $user_specials_rights['create']) {
		print '<div class="tabsAction" style="align:right;">';
		print "<form method=post action='" . $_SERVER["PHP_SELF"] . "'>";
		print '<input type="hidden" name="action" value="addline">';
		print '<input type="hidden" name="tabsid" value="' . $tabsid . '">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<input type="text" name=nblineAdd value=1 size=1>';
		print '<input type="submit" class="butAction" value="' . $langs->trans("AddLine") . '">';
		
		print "</form>";
		print '</div>';
	}
	
	// var_dump($object->array_options);
	print "<form method=post action='" . $_SERVER["PHP_SELF"] . "'>";
	print '<input type="hidden" name="action" value="setextrafields">';
	print '<input type="hidden" name="tabsid" value="' . $tabsidmenu . '">';
	print '<input type="hidden" name="id" value="' . $id . '">';
	// gestion des templates
	if ($customtabsSsMenu->template) {
		// pas d'entete de colonne, on travaille par bloc de divs
		print '<div>';
		// boucle sur les lignes
		foreach ( $customtabsSsMenu->array_options as $rowidExtrafields => $lineExtrafields ) {
			print '<div class="boxstats">';
			$template = $customtabsSsMenu->template;
			foreach ( $extrafieldsSsMenu->attribute_label as $key => $label ) {
				// gestion du multilingue pour la description
				if ($langs->trans($key) != $key)
					$label = $langs->trans($key);
					// pour l'affichage de la description du champ
				$template = str_replace("#LABEL-" . $key . "#", $label, $template);
				
				$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $lineExtrafields["options_" . $key]);
				if ($action == "modify" && $user->rights->customtabs->creer && $user_specials_rights['edit'])
					$fields = $extrafieldsSsMenu->showInputField($key, $value);
				else
					$fields = $extrafieldsSsMenu->showOutputField($key, $value);
				$template = str_replace("#FIELD-" . $key . "#", $fields, $template);
			}
			
			// on affiche enfin le template
			print $template . "\n";
			print '</div>';
		}
		print '</div>';
	} else {
		print '<table id="listtable" class="noborder" width="100%">';
		if ($customtabsSsMenu->array_options) {
			print "<thead>\n";
			print '<tr>';
			foreach ( $extrafieldsSsMenu->attribute_label as $key => $label ) {
				// gestion du multilingue
				if ($langs->trans($key) != $key)
					$label = $langs->trans($key);
				print '<th width=42px nowrap>' . $label . '</th>';
			}
			print '<th nowrap></th>';
			print '</tr>';
			print "</thead>\n";
		}
		print "<tbody>\n";
		// boucle sur les lignes
		foreach ( $customtabsSsMenu->array_options as $rowidExtrafields => $lineExtrafields ) {
			print '<tr>';
			if ($action == 'edit' && $rowidExtrafields == GETPOST('linerowid')) {
				print '<input type="hidden" name="linerowid" value="' . GETPOST('linerowid') . '">';
				foreach ( $extrafieldsSsMenu->attribute_label as $key => $label ) {
					print '<td>';
					$value = $lineExtrafields["options_" . $key];
					print $extrafieldsSsMenu->showinputField($key, $value);
					print '</td>';
				}
				print '<td><input type="submit" class="butAction" value="' . $langs->trans("Valid") . '"></td>';
			} else {
				foreach ( $extrafieldsSsMenu->attribute_label as $key => $label ) {
					print '<td>';
					$value = $lineExtrafields["options_" . $key];
					print $extrafieldsSsMenu->showOutputField($key, $value);
					print '</td>';
				}
				
				print '<td align="right">';
				if ($user_specials_rights['edit']) {
					print '<a href="' . $_SERVER["PHP_SELF"] . '?action=edit&tabsid=' . $tabsidmenu . '&id=' . $id . '&linerowid=' . $rowidExtrafields . '">' . img_edit() . '</a>';
				}
				if ($user_specials_rights['delete']) {
					print '&nbsp; <a href="' . $_SERVER["PHP_SELF"] . '?action=delextrafields&tabsid=' . $tabsidmenu . '&id=' . $id . '&linerowid=' . $rowidExtrafields . '">' . img_delete() . "</a>\n";
				}
				print '</td>';
			}
			print '</tr>';
		}
		print "</tbody>\n";
		print "</table>";
	}
	print "</form>";
	
	if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES)) {
		print "\n";
		print '<script type="text/javascript">' . "\n";
		print 'jQuery(document).ready(function() {' . "\n";
		print 'jQuery("#listtable").dataTable( {' . "\n";
		print '"sDom": \'C<"clear">flrtip\',' . "\n";
		print '"oColVis": {"buttonText": "' . $langs->trans('showhidecols') . '" },' . "\n";
		print '"bPaginate": true,' . "\n";
		print '"bFilter": true	,' . "\n";
		print '"sPaginationType": "full_numbers",' . "\n";
		print '"bJQueryUI": false,' . "\n";
		print '"oLanguage": {"sUrl": "' . DOL_URL_ROOT . "/customtabs/" . $langs->trans('datatabledict') . '" },' . "\n";
		// print '"iDisplayLength": '.$conf->global->MYLIST_NB_ROWS.','."\n";
		print '"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],' . "\n";
		print '"bSort": true,' . "\n";
		print '} );' . "\n";
		print '});' . "\n";
		print "\n";
		print '</script>' . "\n";
	}
	
	// section d'ajout
	if ($action == "addline" && $user_specials_rights['create']) {
		$nblineAdd = GETPOST("nblineAdd");
		print "<br><br>";
		print "<form method=post action='" . $_SERVER["PHP_SELF"] . "'>";
		print '<input type="hidden" name="action" value="addextrafields">';
		print '<input type="hidden" name="tabsid" value="' . $tabsidmenu . '">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<input type="hidden" name="nblineAdd" value="' . $nblineAdd . '">';
		print '<table class="border" width="100%">';
		print '<tr class="liste_titre">';
		foreach ( $extrafieldsSsMenu->attribute_label as $key => $label ) {
			// gestion du multilingue
			if ($langs->trans($key) != $key)
				$label = $langs->trans($key);
			print '<th nowrap>' . $label . '</th>';
		}
		print '<th nowrap></th>';
		print '</tr>';
		for($i = 1; $i <= $nblineAdd; $i ++) {
			print '<tr >';
			foreach ( $extrafieldsSsMenu->attribute_label as $key => $label ) {
				print '<td >';
				print $extrafieldsSsMenu->showInputField($key, '', $i);
				print '</td>' . "\n";
			}
			print '</tr >';
		}
		print '</table>';
		print '<div class="tabsAction" style="align:right;">';
		print '<input type="submit" class="butAction" value="' . $langs->trans("Valid") . '">';
		print '</div>';
		print "</form>";
	}
}

llxFooter();

$db->close();
?>