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
 * \file htdocs/customtabs/fiche.php
 * \ingroup member
 * \brief complement fiche
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include ($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory

require_once 'core/lib/customtabs.lib.php';
require_once 'class/customtabs.class.php';

$langs->load("customtabs@customtabs");

$rowid = GETPOST('rowid', 'int');
$action = GETPOST('action', 'alpha');

// Security check
$result = restrictedArea($user, 'customtabs', $rowid, '');

/*
 *	Actions
 */
if ($action == 'add' && $user->rights->customtabs->configurer) {
	$customtabs = new Customtabs($db);
	
	$libelle = GETPOST("libelle");
	// libellé de l'onglet obligatoire
	if (empty($libelle)) {
		$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
		$action = 'create';
	}
	
	$tablename = GETPOST("tablename");
	// le nom de la table est obligatoire
	if (empty($tablename)) {
		$mesg .= " " . $langs->trans("ErrorFieldRequired", $langs->transnoentities("tablename"));
		$action = 'create';
	} else {
		// on controle que la table ,'existe pas déjà
		$customtabs->fetch(0, $tablename);
		if ($customtabs->rowid > 0) {
			$mesg .= " " . $langs->trans("ErrorTableAllreadyExist", $langs->transnoentities("tablename"));
			$action = 'create';
		}
	}
	$fk_parent = GETPOST("fk_parent");
	if ($fk_parent == - 1)
		$fk_parent = 0;
		
		// si on peu toujours créer un onglet (pas d'erreur)
	if ($action == 'add') {
		if ($_POST["button"] != $langs->trans("Cancel")) {
			$customtabs->label = trim($libelle); // non de l'onglet
			$customtabs->tablename = trim($tablename); // nom de la table associé
			$customtabs->element = trim($_POST["element"]); // éléments auquel est associé l'onglet
			$customtabs->mode = trim($_POST["mode"]); // différent type d'affichage
			$customtabs->files = 0; // par défaut on ne sélectionne pas la présence d'une ged
			$customtabs->fk_parent = trim($fk_parent); // parent (si sous-onglet)
			
			$id = $customtabs->create($user->id);
			
			if ($customtabs->tablename && $id > 0) { // la saisie du nom de la table est obligatoire sinon on ne crée pas la table
			  
				// définition de la table à créer
				$table = MAIN_DB_PREFIX . "cust_" . $customtabs->tablename . '_extrafields';
				$fields = array (
						'rowid' => array (
								'type' => 'int',
								'value' => '11',
								'null' => 'not null',
								'extra' => 'AUTO_INCREMENT' 
						),
						'tms' => array (
								'type' => 'timestamp',
								'attribute' => 'on update CURRENT_TIMESTAMP',
								'default' => 'CURRENT_TIMESTAMP',
								'null' => 'not null',
								'extra' => 'ON UPDATE CURRENT_TIMESTAMP' 
						),
						'fk_element' => array (
								'type' => 'int',
								'value' => '11',
								'null' => 'not null' 
						), // clé de l'élément
						'fk_customtabs_parent' => array (
								'type' => 'int',
								'value' => '11',
								'null' => 'not null' 
						),
						'fk_object' => array (
								'type' => 'int',
								'value' => '11',
								'null' => 'not null' 
						),
						'import_key' => array (
								'type' => 'varchar',
								'value' => '14',
								'default' => 'NULL',
								'null' => 'null' 
						) 
				);
				$result = $db->DDLCreateTable($table, $fields, 'rowid', 'InnoDB');
				
				header("Location: " . $_SERVER["PHP_SELF"]);
				exit();
			} else {
				$mesg = $customtabs->error;
				$action = 'create';
			}
		}
	}
}

if ($action == 'update' && $user->rights->customtabs->configurer) {
	$customtabs = new Customtabs($db);
	$customtabs->rowid = $rowid;
	$customtabs->label = trim($_POST["libelle"]);
	$customtabs->element = trim($_POST["element"]);
	$customtabs->elementkey = trim($_POST["elementkey"]);
	$customtabs->mode = trim($_POST["mode"]);
	$customtabs->files = trim($_POST["files"]);
	$customtabs->fk_statut = trim($_POST["fk_statut"]);
	$customtabs->fk_parent = trim($_POST["fk_parent"]);
	
	$customtabs->update($user);
	
	header("Location: " . $_SERVER["PHP_SELF"] . "?rowid=" . $_POST["rowid"]);
	exit();
}

if ($action == 'delete' && $user->rights->customtabs->configurer) {
	// you delete customtabs but not the table created
	$customtabs = new Customtabs($db);
	$customtabs->delete($rowid);
	header("Location: " . $_SERVER["PHP_SELF"]);
	exit();
}

if ($action == 'setshow' && $user->rights->customtabs->configurer) {
	$customtabs = new Customtabs($db);
	$customtabs->fetch($rowid);
	$customtabs->setShowTabs(GETPOST("fk_ressourcetype"), GETPOST("activate"));
	// show list mode
	$rowid = '';
}
if ($action == 'importation' && $user->rights->customtabs->configurer) {
	if (GETPOST("importexport")) {
		$customtabs = new Customtabs($db);
		$result = $customtabs->importlist(GETPOST("importexport"));
		
		if ($result < 0) {
			setEventMessage($customtabs->error, 'errors');
		} else {
			$rowid = $result;
			$customtabs->fetch($rowid);
		}
	}
	
	header("Location:card.php");
	exit();
}
/*
 * View
 */

llxHeader('', $langs->trans("CustomTabs"), 'EN:Module_customtabs|FR:Module_customtabs|ES:M&oacute;dulo_customtabs');

$form = new Form($db);

/* ************************************************************************** */
/*                                                                            */
/* Creation d'un customtabs                                                   */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'create') {
	$form = new Form($db);
	
	print_fiche_titre($langs->trans("NewCustomtabs"));
	
	if ($mesg)
		print '<div class="error">' . $mesg . '</div>';
	
	print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';
	
	print '<table class="border" width="100%">';
	print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td><input type="text" name="libelle" size="40"></td></tr>';
	print '<tr><td class="fieldrequired">' . $langs->trans("TableName") . '</td><td><input type="text" name="tablename" size="40"></td></tr>';
	
	print '<tr><td>' . $langs->trans("Element") . '</td><td>';
	print $form->selectarray("element", elementarray(), 0, 0);
	print '</td></tr>';
	
	print '<tr><td>' . $langs->trans("ModeCustomTabs") . '</td><td>';
	print $form->selectarray("mode", modearray(), 0, 0);
	print '</td></tr>';
	
	// Liste des parents
	$customtabs = new Customtabs($db);
	print '<tr><td >' . $langs->trans("Parent") . "</td><td>\n";
	print $customtabs->selectparent(0, "fk_parent", null);
	print "</td>\n";
	
	print "</table>\n";
	
	print '<br>';
	print '<center><input type="submit" name="button" class="button" value="' . $langs->trans("Add") . '"> &nbsp; &nbsp; ';
	print '<input type="submit" name="button" class="button" value="' . $langs->trans("Cancel") . '"></center>';
	
	print "</form>\n";
}

/* ************************************************************************** */
/*                                                                            */
/* Visualisation / Edition de la fiche                                        */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0) {
	if ($action != 'edit') {
		$customtabs = new CustomTabs($db);
		$customtabs->fetch($rowid);
		
		$head = customtabs_prepare_head($customtabs);
		dol_fiche_head($head, 'general', $langs->trans("CustomTabs"), 0, 'customtabs@customtabs');
		
		print '<table class="border" width="100%">';
		
		$linkback = '<a href="' . DOL_URL_ROOT . '/customtabs/card.php">' . $langs->trans("BackToList") . '</a>';
		
		// Ref
		print '<tr><td width="15%">' . $langs->trans("Ref") . '</td>';
		print '<td>';
		print $form->showrefnav($customtabs, 'rowid', $linkback, 1, 'rowid', 'rowid', '');
		print '</td></tr>';
		
		// Label
		print '<tr><td width="15%">' . $langs->trans("Label") . '</td><td>' . $customtabs->libelle . '</td></tr>';
		// tablename
		print '<tr><td width="15%">' . $langs->trans("TableName") . '</td><td>llx_cust_' . $customtabs->tablename . '_extrafields</td></tr>';
		
		// element
		print '<tr><td>' . $langs->trans("Element") . '</td><td>';
		$tblelement = elementarray();
		print $tblelement[$customtabs->element];
		print '</tr>';
		
		print '<tr><td>' . $langs->trans("ModeCustomTabs") . '</td><td>';
		print getmodelib($customtabs->mode);
		print '</tr>';
		
		print '<tr><td>' . $langs->trans("FichierGED") . '</td><td>';
		print yn($customtabs->files);
		print '</tr>';
		
		print '<tr><td>' . $langs->trans("Parent") . '</td><td>';
		print $customtabs->parentname;
		print '</td></tr>';
		
		print '<tr><td>' . $langs->trans("ActiveStatut") . '</td><td>';
		print yn($customtabs->fk_statut);
		print '</tr>';
		
		print '</table>';
		
		print '</div>';
		
		/*
		 * Barre d'actions
		 *
		 */
		print '<div class="tabsAction">';
		
		// Edit
		if ($user->rights->customtabs->configurer) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;rowid=' . $customtabs->rowid . '">' . $langs->trans("Modify") . '</a>';
		}
		
		// Delete
		if ($user->rights->customtabs->configurer) {
			print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=delete&rowid=' . $customtabs->rowid . '">' . $langs->trans("DeleteTabs") . '</a>';
		}
		
		// Import Export de l'onglet
		if ($user->rights->customtabs->export) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=import&rowid=' . $customtabs->rowid . '">' . $langs->trans("ImportExportTabs") . '</a>';
		}
		
		print "</div>";
	}
	
	if ($action == 'edit') {
		$form = new Form($db);
		
		$customtabs = new Customtabs($db);
		$customtabs->rowid = $rowid;
		$customtabs->fetch($rowid);
		
		$h = 0;
		
		$head[$h][0] = $_SERVER["PHP_SELF"] . '?rowid=' . $customtabs->rowid;
		$head[$h][1] = $langs->trans("TabsCard");
		$head[$h][2] = 'general';
		$h ++;
		
		dol_fiche_head($head, 'general', $langs->trans("Customtabs"), 0, 'customtabs@customtabs');
		
		print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="rowid" value="' . $rowid . '">';
		print '<input type="hidden" name="action" value="update">';
		print '<table class="border" width="100%">';
		
		print '<tr><td width="15%">' . $langs->trans("Ref") . '</td><td>' . $customtabs->rowid . '</td></tr>';
		
		print '<tr><td>' . $langs->trans("Label") . '</td><td><input type="text" name="libelle" size="40" value="' . $customtabs->libelle . '"></td></tr>';
		print '<tr><td width="15%">' . $langs->trans("TableName") . '</td><td>llx_cust_' . $customtabs->tablename . '_extrafields</td></tr>';
		
		print '<tr><td >' . $langs->trans("Element") . "</td><td>\n";
		print $form->selectarray("element", elementarray(), $customtabs->element);
		print "</td>\n";
		
		print '<tr><td >' . $langs->trans("ModeCustomTabs") . "</td><td>\n";
		print $form->selectarray("mode", modearray(), $customtabs->mode);
		print "</td>\n";
		print '<tr><td>' . $langs->trans("fichierGED") . '</td><td>';
		if ($customtabs->mode == 1)
			print $form->selectyesno("files", $customtabs->files, 1);
		else {
			print yn($customtabs->files) . ", " . $langs->trans("NoGEDInListMode"); // en mode liste, pas de ged
			print '<input type="hidden" name="files" value="0">';
		}
		print '</td></tr>';
		
		// Liste des parents possible
		print '<tr><td >' . $langs->trans("Parent") . "</td><td>\n";
		print $customtabs->selectparent($customtabs->fk_parent, "fk_parent", $customtabs->rowid);
		print "</td>\n";
		
		print '<tr><td>' . $langs->trans("ActiveStatut") . '</td><td>';
		print $form->selectyesno("fk_statut", $customtabs->fk_statut, 1);
		print '</td></tr>';
		
		print '</table>';
		
		print '<center><input type="submit" class="button" value="' . $langs->trans("Save") . '"> &nbsp; &nbsp;';
		print '<input type="submit" name="button" class="button" value="' . $langs->trans("Cancel") . '"></center>';
		
		print "</form>";
	}
}

/* ************************************************************************** */
/*                                                                            */
/* Importation / d'un customTabs	                                          */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'import') {
	/*
	 * Import/export customtabs
	 */
	print_fiche_titre($langs->trans("ImportCustomTabs"));
	
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="importation">';
	print '<input type="hidden" name="code" value="' . GETPOST("code") . '">';
	print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	
	print '<table class="border" width="100%">';
	
	print '<tr><td><span class="fieldrequired">' . $langs->trans("FillImportExportData") . '</span></td></tr>';
	print '<td><textarea name=importexport cols=132 rows=20>';
	if ($rowid)
		print $customtabs->getexporttable($rowid);
	print '</textarea></td></tr>';
	print '</table>';
	print '<br><center>';
	print '<input type="submit" class="button" value="' . $langs->trans("LaunchImport") . '">';
	print '</center>';
	print '</form>';
}

llxFooter();
$db->close();

?>
