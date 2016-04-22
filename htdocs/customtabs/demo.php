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
 * \file htdocs/customtabs/tabs/fiche.php
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

require_once 'core/lib/customtabs.lib.php';
require_once 'class/customtabs.class.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

$langs->load("customtabs@customtabs");
$langs->load("users");
$langs->load('other');
$langs->load(GETPOST("tablename") . "@ressources");

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$confirm = GETPOST('confirm', 'alpha');
$tabsid = GETPOST('tabsid', 'int');

// on r�cup�re la classe associ� au compl�ment si elle a �t� g�n�r�e, pas encore d'actualit�
// $file=DOL_DOCUMENT_ROOT.'/customtabs/compl_class/'.GETPOST("tabsid").'.class.php';
// if (file_exists($file))
// {
// $res=require_once $file;
// }

$customtabs = new CustomTabs($db);
$customtabs->fetch($tabsid);

// selon l'onglet on affiche les donn�es de l'onglet
switch ($customtabs->element) {
	case 1 :
		require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
		$object = new Societe($db);
		$object->fetch($id, $ref);
		$head = societe_prepare_head($object);
		$title = $langs->trans("ThirdParty");
		$tabsinfo = 'company';
		break;
	case 2 :
		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/contact.lib.php';
		$object = new Contact($db);
		$result = $object->fetch($id, $ref);
		$head = contact_prepare_head($object, $user);
		$title = $langs->trans("Contact");
		$tabinfo = 'contact';
		break;
	case 3 :
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
		$object = new Product($db);
		$result = $object->fetch($id, $ref);
		$head = product_prepare_head($object, $user);
		$title = $langs->trans("Product");
		$tabsinfo = 'product';
		break;
	case 4 :
		$tabinfo = 'member';
		break;
	case 5 :
		$tabinfo = 'project';
		break;
	case 6 :
		$tabinfo = 'task';
		break;
}

// selon l'onglet on affiche les donn�es de l'onglet
switch ($customtabs->elementkey) {
	case 1 :
		$objelementkey = new Societe($db);
		break;
	case 2 :
		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		$objelementkey = new Contact($db);
		break;
	case 3 :
		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
		$objelementkey = new Product($db);
		break;
	case 4 :
		$tabinfo = 'member';
		break;
	case 5 :
		$tabinfo = 'project';
		break;
	case 6 :
		$tabinfo = 'task';
		break;
}

if ($ref)
	$id = $object->id;
	
	// show or not tabs
if ($customtabs->getShowCustomtabs($user->id)) {
	
	// Determine user rights according type of ressource
	$user_specials_rights = $customtabs->getUserSpecialsRights($user);
	
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
	$res = $customtabs->fetch_optionals($id, $extralabels);
	
	/*
	 * 	Actions
	 */
	$upload_dir = $conf->customtabs->dir_output . "/" . $tabsid . '/' . $id;
	
	$modulepart = 'customtabs';
	$file = DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
	if (file_exists($file))
		include_once $file;
		
		// Suppression fichier
	if ($action == 'confirm_deletefile' && $confirm == 'yes' && $user_specials_rights['edit']) {
		$langs->load("other");
		$file = $upload_dir . "/" . GETPOST('urlfile'); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
		$ret = dol_delete_file($file);
		if ($ret)
			setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
		else
			setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
		header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/card.php?tabsid=' . $tabsid . '&id=' . $id);
		exit();
	}
	
	if ($action == 'setextrafields' && $user->rights->customtabs->creer && $user_specials_rights['edit']) {
		if ($result > 0) {
			$extralabels = $extrafields->fetch_name_optionals_label($customtabs->table_element);
			$customtabs->id = $id; // on utilise l'id de l'enregistrement
			$ret = $extrafields->setOptionalsFromPost($extralabels, $customtabs);
			$result = $customtabs->insertExtraFields();
			header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/card.php?tabsid=' . $tabsid . '&id=' . $id);
		}
	}
	
	/*
	 * View
	 */
	
	$form = new Form($db);
	
	$help_url = 'EN:Module_customtabs|FR:Module_customtabs|ES:M&oacute;dulo_customtabs';
	llxHeader('', $langs->trans("CustomTabs"), $help_url);
	
	/**
	 * ******************************************
	 *
	 * Fiche en mode edition
	 *
	 * ******************************************
	 */
	
	dol_fiche_head($head, "customtabs_" . $tabsid, $title, 0, $tabsinfo);
	
	dol_htmloutput_errors($errmsg, $errmsgs);
	dol_htmloutput_mesg($mesg);
	
	if ($backtopage)
		print '<input type="hidden" name="backtopage" value="' . ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]) . '">';
	
	print '<table class="border" width="100%">';
	
	// Ref
	print '<tr><td width="20%">' . $langs->trans("Ref") . '</td>';
	print '<td class="valeur" colspan="2">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid=' . $tabsid);
	print '</td></tr>';
	
	print '</table><br>';
	
	// Extrafields
	if (! empty($extrafields->attribute_label)) {
		print "<form method=post action='" . $_SERVER["PHP_SELF"] . "'>";
		print '<input type="hidden" name="action" value="setextrafields">';
		print '<input type="hidden" name="tabsid" value="' . $tabsid . '">';
		print '<input type="hidden" name="id" value="' . $id . '">';
		
		// gestion des templates
		if ($customtabs->template) {
			$template = $customtabs->template;
			$customtabs->id = $id; // on utilise l'id de l'enregistrement
			foreach ( $extrafields->attribute_label as $key => $label ) {
				$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $customtabs->array_options["options_" . $key]);
				
				// gestion du multilingue
				if ($langs->trans($key) != $key)
					$label = $langs->trans($key);
				
				$template = str_replace("#LABEL-" . $key . "#", $label, $template);
				
				if ($action == "modify" && $user->rights->customtabs->creer && $user_specials_rights['edit'])
					$fields = $extrafields->showInputField($key, $value);
				else
					$fields = $extrafields->showOutputField($key, $value);
				$template = str_replace("#FIELD-" . $key . "#", $fields, $template);
			}
			
			// gestion de l'�l�mentkey si il est actif
			
			print $template . "\n";
		} else {
			print '<table class="border" width="100%">';
			print '<tr class="liste_titre">';
			print '<th colspan="4">' . $langs->trans("TabsFields") . '</th></tr>';
			// gestion de l'�l�mentkey si il est actif
			
			$customtabs->id = $id; // on utilise l'id de l'enregistrement
			foreach ( $extrafields->attribute_label as $key => $label ) {
				
				$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $customtabs->array_options["options_" . $key]);
				// gestion du multilingue
				if ($langs->trans($key) != $key)
					$label = $langs->trans($label);
				print '<tr><td width=25% nowrap>' . $label . '</td>';
				print '<td colspan="3">';
				if ($action == "modify" && $user->rights->customtabs->creer && $user_specials_rights['edit'])
					print $extrafields->showInputField($key, $value);
				else
					print $extrafields->showOutputField($key, $value);
				print '</td></tr>' . "\n";
			}
			print "</table>";
		}
		
		/*
		 * Barre d'actions Extrafields
		 */
		print '<div class="tabsAction">';
		// Validate
		if ($action == "modify" && $user->rights->customtabs->creer && $user_specials_rights['edit']) {
			print '<input type="submit" class="butAction" value="' . $langs->trans("Valid") . '">';
		}
		
		// Modify
		if ($action != "modify" && $user->rights->customtabs->creer && $user_specials_rights['edit']) {
			print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?tabsid=' . $tabsid . '&id=' . $id . '&action=modify"';
			print '>' . $langs->trans("Modify") . '</a>';
		}
		
		print '</div>';
		
		print "</form>";
	}
	
	if ($customtabs->files && file_exists($file)) {
		// files associated
		
		if ($action == 'delete' && $user_specials_rigts['edit']) {
			$ret = $form->form_confirm(DOL_URL_ROOT . '/customtabs/tabs/card.php?tabsid=' . $tabsid . '&id=' . $id . '&urlfile=' . urlencode(GETPOST("urlfile")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
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
		$formfile->form_attach_new_file(DOL_URL_ROOT . '/customtabs/tabs/card.php?tabsid=' . $tabsid . '&id=' . $id, '', 0, 0, $user->rights->customtabs->creer, 50, $object);
		
		$formfile->list_of_documents($filearray, $object, 'customtabs', '&tabsid=' . $tabsid . '&id=' . $id, 0, $tabsid . '/' . $id . '/');
	}
	
	print "<br>";
	// on g�re les sous-menus si il y en a
	$head = customtabs_prepare_head_menu($object, $tabsid);
	if (! empty($head))
		dol_fiche_head($head, "customtabs_" . $tabsid, $customtabs->libelle, 0, $tabsinfo);
} else { // restricted area
	
	$errmsg = $langs->trans("RestrictedCustomTabs");
	$help_url = 'EN:Module_customtabs|FR:Module_customtabs|ES:M&oacute;dulo_customtabs';
	llxHeader('', $langs->trans("CustomTabs"), $help_url);
	
	/**
	 * ******************************************
	 *
	 * Fiche en mode restreinte
	 *
	 * ******************************************
	 */
	$form = new Form($db);
	
	dol_fiche_head($head, "customtabs_" . $tabsid, $title, 0, $tabsinfo);
	
	dol_htmloutput_errors($errmsg, $errmsgs);
	dol_htmloutput_mesg($mesg);
	
	if ($backtopage)
		print '<input type="hidden" name="backtopage" value="' . ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]) . '">';
	
	print '<table class="border" width="100%">';
	
	// Ref
	print '<tr><td width="20%">' . $langs->trans("Ref") . '</td>';
	print '<td class="valeur" colspan="2">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid=' . $tabsid);
	print '</td></tr>';
	
	print '</table><br>';
}

llxFooter();

$db->close();

?>
