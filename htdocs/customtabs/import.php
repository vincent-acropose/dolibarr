<?php
/* Copyright (C) 2015	Charles-Fr BENKE	<charles.fr@benke.fr>
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
 * \file htdocs/customtabs/import.php
 * \ingroup customtabs
 * \brief Page to setup import of tabs
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include ($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory

require_once 'core/lib/customtabs.lib.php';
require_once 'class/customtabs.class.php';

$langs->load("customtabs@customtabs");
$langs->load("admin");

$rowid = GETPOST('rowid', 'int');

$form = new Form($db);

$action = GETPOST('action', 'alpha');

$elementtype = "cust_" . $customtabs->tablename;

if (! $user->rights->customtabs->configurer)
	accessforbidden();

$customtabs = new Customtabs($db);
$customtabs->fetch($rowid);

$exportenabled = $customtabs->exportenabled;
$importenabled = $customtabs->importenabled;

/*
 * Actions
 */
if ($action == 'ExportCSVEnabled' && $user->rights->customtabs->configurer) {
	$exportenabled = GETPOST('value');
	$customtabs->setExport($exportenabled, $user);
	$mesg = "<font class='ok'>" . $langs->trans("ExportSettingSaved") . "</font>";
}

if ($action == 'ImportCSVEnabled' && $user->rights->customtabs->configurer) {
	$importenabled = GETPOST('value');
	$customtabs->setImport($importenabled, $user);
	$mesg = "<font class='ok'>" . $langs->trans("ImportSettingSaved") . "</font>";
}

if ($action == 'update' && $user->rights->customtabs->configurer) {
	
	$customtabs->colnameline = GETPOST('ColNameLine');
	$customtabs->csvseparator = GETPOST('CSVSeparator');
	$customtabs->csvenclosure = GETPOST('CSVEnclosure');
	$customtabs->colnamebased = GETPOST('colnamebased');
	
	$customtabs->updateImport($user);
	$mesg = "<font class='ok'>" . $langs->trans("ImportSettingSaved") . "</font>";
}

/*
 * View
 */

$help_url = 'EN:Module_CustomTabs|FR:Module_CustomTabs|ES:M&oacute;dulo_CustomTabs';
llxHeader('', $langs->trans("TabsImport"), $help_url);

$head = customtabs_prepare_head($customtabs);

dol_fiche_head($head, 'import', $langs->trans("Customtabs"), 0, 'user');

print $langs->trans("DefineHereCustomTabsImportSetting") . '<br>' . "\n";
print '<br>';

dol_htmloutput_mesg($mesg, '', 'ok');

// Load attribute_label
print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="rowid" value="' . $rowid . '">';
print '<input type="hidden" name="action" value="update">';

print '<table class="border" width="100%">';

$linkback = '<a href="' . DOL_URL_ROOT . '/customtabs/list.php">' . $langs->trans("BackToList") . '</a>';

// Ref
print '<tr><td width="15%">' . $langs->trans("Ref") . '</td>';
print '<td>';
print $form->showrefnav($customtabs, 'rowid', $linkback, 1, 'rowid', 'rowid', '');
print '</td></tr>';
// Label
print '<tr><td width="15%">' . $langs->trans("Label") . '</td><td>' . $customtabs->libelle . '</td></tr>';
// element
print '<tr><td>' . $langs->trans("Element") . '</td><td>';
$tblelement = elementarray();
print $tblelement[$customtabs->element];
print '</tr>';

// tablename
print '<tr><td width="15%">' . $langs->trans("TableName") . '</td><td>llx_cust_' . $customtabs->tablename . '_extrafields</td></tr>';

// Label
print '<tr><td width="15%">' . $langs->trans("Label") . '</td><td>' . $customtabs->libelle . '</td></tr>';

print '<tr><td>' . $langs->trans("ModeCustomTabs") . '</td><td>';
print getmodelib($customtabs->mode);
print '</tr>';
print '</table><br><br>';

print "<table summary='listofattributes' class='noborder' width='100%' >";

print '<tr class="liste_titre">';
print '<td width=250px>' . $langs->trans("ImportSetting") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '</tr>';
print '<tr >';
print '<td width=20%  align=left>' . $langs->trans("ImportCSVEnabled") . '</td>';
print '<td  align=left>';
if ($importenabled == 1)
	print '<a href="' . $_SERVER["PHP_SELF"] . '?rowid=' . $rowid . '&action=ImportCSVEnabled&value=0">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
else
	print '<a href="' . $_SERVER["PHP_SELF"] . '?rowid=' . $rowid . '&action=ImportCSVEnabled&value=1">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';

print '</td></tr>' . "\n";
print '<tr><td >' . $langs->trans("ColNameLine") . '</td>';
print '<td >';
print '<input type=text size=2 name="ColNameLine" value="' . $customtabs->colnameline . '" >';
print '</td></tr>';

print '<tr><td >' . $langs->trans("CSVSeparator") . '</td>';
print '<td >';
print '<input type=text size=2 name="CSVSeparator" value="' . $customtabs->csvseparator . '" >';
print '</td></tr>';

print '<tr><td >' . $langs->trans("CSVEnclosure") . '</td>';
print '<td >';
print "<input type=text size=2 name='CSVEnclosure' value='" . $customtabs->csvenclosure . "' >";
print '</td></tr>';

print '<tr >';
print '<td width=20%  align=left>' . $langs->trans("ColNameBased") . '</td>';
print '<td  align=left>';
$tblArrychoice = array (
		$langs->trans("LabelColBased"),
		$langs->trans("FieldColBased"),
		$langs->trans("OrderColBased") 
);
print $form->selectarray("colnamebased", $tblArrychoice, $customtabs->colnamebased, 0);
print '</td></tr>' . "\n";
print '</table>';

// Buttons
print '<div class="tabsAction">';
print '<center><input type="submit" class="button" value="' . $langs->trans("Save") . '"> &nbsp; &nbsp;';
print "</div>";

print "</form>";
print '<br><br>';
print "<table summary='listofattributes' class='noborder' width='100%'>";
print '<tr class="liste_titre">';
print '<td width=250px>' . $langs->trans("ExportSetting") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '</tr>';
print '<tr >';
print '<td width=20%  align=left>' . $langs->trans("ExportCSVEnabled") . '</td>';
print '<td  align=left>';
if ($exportenabled == 1)
	print '<a href="' . $_SERVER["PHP_SELF"] . '?rowid=' . $rowid . '&action=ExportCSVEnabled&value=0">' . img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
else
	print '<a href="' . $_SERVER["PHP_SELF"] . '?rowid=' . $rowid . '&action=ExportCSVEnabled&value=1">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';

print '</td></tr>' . "\n";
print '</table>';
dol_fiche_end();

llxFooter();

$db->close();
?>
