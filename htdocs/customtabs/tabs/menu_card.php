<?php
/* Copyright (C) 2014	Charles-Fr BENKE	<charles.fr@benke.fr>
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
 * \file htdocs/customtabs/tabs/card.php
 * \ingroup ressources
 * \brief Page of tabs
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

// on r�cup�re la classe associ� au compl�ment si elle a �t� g�n�r�e
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
// fetch sp�cial liste
$res = $customtabs->fetch_optionals($id, $extrafields);

$extrafieldsSsMenu = new ExtraFields($db);
$extralabels = $extrafieldsSsMenu->fetch_name_optionals_label($customtabsSsMenu->table_element);

$res = $customtabsSsMenu->fetch_optionals($id, $extrafieldsSsMenu);

/*
 * 	Actions
 */
$upload_dir = $conf->customtabs->dir_output . "/" . $tabsidmenu . '/' . $id;

// la gestion de la redirection est pourrit, donc on finte
$object->id = $id . '&tabsid=' . $tabsid;
$modulepart = 'customtabs';
$file = DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
if (file_exists($file))
	include_once $file;
	// on remet � la normale
$object->id = $id;

// Suppression fichier
if ($action == 'confirm_deletefile' && $confirm == 'yes' && $user_specials_rights['edit']) {
	$langs->load("other");
	$file = $upload_dir . "/" . GETPOST('urlfile'); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	$ret = dol_delete_file($file);
	if ($ret)
		setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	else
		setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/menu_card.php?tabsid=' . $tabsidmenu . '&id=' . $id);
	exit();
}

if ($action == 'setextrafields' && $user_specials_rights['edit']) {
	if ($result > 0) {
		$extralabels = $extrafieldsSsMenu->fetch_name_optionals_label($customtabsSsMenu->table_element);
		$customtabsSsMenu->id = $id; // on utilise l'id de l'enregistrement
		$ret = $extrafieldsSsMenu->setOptionalsFromPost($extralabels, $customtabsSsMenu);
		$rescust = $customtabsSsMenu->insertExtraFields();
		if ($rescust < 0) {
			$error ++;
			if ($error)
				$action = 'modify';
		} else
			header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/menu_card.php?tabsid=' . $tabsidmenu . '&id=' . $id);
	}
}

/*
 * View
 */

/**
 * ******************************************
 *
 * Fiche en mode edition
 *
 * ******************************************
 */

dol_htmloutput_errors($errmsg, $errmsgs);
dol_htmloutput_mesg($mesg);

$customtabs->tabs_head_element($tabsid);

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

// on g�re les sous-menus si il y en a
$head = customtabs_prepare_head_menu($object, $tabsid);

// dol_fiche_head($head, GETPOST("tablename"), $complement->libelle, 0, 'user');
if ($customtabs->element == 'thirdparty')
	$icontabs = "company";
else
	$icontabs = $customtabs->element;

dol_fiche_head($head, "customtabs_" . $tabsidmenu, $customtabs->libelle, 0, $icontabs);
// Extrafields
if (! empty($extrafieldsSsMenu->attribute_label)) {
	print "<form method=post action='" . $_SERVER["PHP_SELF"] . "'>";
	print '<input type="hidden" name="action" value="setextrafields">';
	print '<input type="hidden" name="tabsid" value="' . $tabsidmenu . '">';
	print '<input type="hidden" name="id" value="' . $id . '">';
	
	// gestion des templates
	if ($customtabsSsMenu->template) {
		$template = $customtabsSsMenu->template;
		$customtabsSsMenu->id = $id; // on utilise l'id de l'enregistrement
		foreach ( $extrafieldsSsMenu->attribute_label as $key => $label ) {
			$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $customtabsSsMenu->array_options["options_" . $key]);
			
			// gestion du multilingue
			if ($langs->trans($key) != $key)
				$label = $langs->trans($key);
			
			$template = str_replace("#LABEL-" . $key . "#", $label, $template);
			
			if ($action == "modify" && $user_specials_rights['edit'])
				$fields = $extrafieldsSsMenu->showInputField($key, $value);
			else
				$fields = $extrafieldsSsMenu->showOutputField($key, $value);
			$template = str_replace("#FIELD-" . $key . "#", $fields, $template);
		}
		print $template . "\n";
	} else {
		print '<table class="border" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="4">' . $langs->trans("TabsFields") . '</th>';
		$customtabsSsMenu->id = $id; // on utilise l'id de l'enregistrement
		foreach ( $extrafieldsSsMenu->attribute_label as $key => $label ) {
			$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $customtabsSsMenu->array_options["options_" . $key]);
			// gestion du multilingue
			if ($langs->trans($key) != $key)
				$label = $langs->trans($label);
			print '<tr><td width=25% nowrap>' . $label . '</td>';
			print '<td colspan="3">';
			if ($action == "modify" && $user_specials_rights['edit'])
				print $extrafieldsSsMenu->showInputField($key, $value);
			else
				print $extrafieldsSsMenu->showOutputField($key, $value);
			print '</td></tr>' . "\n";
		}
	}
	
	print "</table>";
	/*
	 * Barre d'actions Extrafields
	 */
	print '<div class="tabsAction">';
	// Validate
	if ($action == "modify" && $user_specials_rights['edit']) {
		print '<input type="submit" class="butAction" value="' . $langs->trans("Valid") . '">';
	}
	
	// Modify
	if ($action != "modify" && $user_specials_rights['edit']) {
		print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?tabsid=' . $tabsidmenu . '&id=' . $id . '&action=modify"';
		print '>' . $langs->trans("Modify") . '</a>';
	}
	
	print '</div>';
	
	print "</form>";
}

if ($customtabsSsMenu->files && file_exists($file)) {
	// files associated
	// print '<br>';
	
	if ($action == 'delete' && $user_specials_rights['edit']) {
		$ret = $form->form_confirm(DOL_URL_ROOT . '/customtabs/tabs/menu_card.php?tabsid=' . $tabsidmenu . '&id=' . $id . '&urlfile=' . urlencode(GETPOST("urlfile")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
		if ($ret == 'html')
			print '<br>';
	}
	// $upload_dir = $conf->ressources->dir_output . "/" . get_exdir($rowid,2,0,1) . '/' . $rowid;
	
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ( $filearray as $key => $file ) {
		$totalsize += $file['size'];
	}
	
	// List of document
	$formfile = new FormFile($db);
	$formfile->form_attach_new_file(DOL_URL_ROOT . '/customtabs/tabs/menu_card.php?tabsid=' . $tabsidmenu . '&id=' . $id, '', 0, 0, $user_specials_rights['edit'], 50, $object);
	
	$formfile->list_of_documents($filearray, $object, 'customtabs', '&tabsid=' . $tabsidmenu . '&id=' . $id, 0, $tabsid . '/' . $id . '/');
}
llxFooter();

$db->close();

?>
