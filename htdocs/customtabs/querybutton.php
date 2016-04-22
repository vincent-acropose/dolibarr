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
 * \file htdocs/adherents/admin/adherent_extrafields.php
 * \ingroup member
 * \brief Page to setup extra fields of group members
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

$customtabs = new Customtabs($db);
$customtabs->fetch($rowid);

$elementtype = "cust_" . $customtabs->tablename;

if (! $user->admin)
	accessforbidden();
	
	/*
 * Actions
 */

if ($action == 'update' && $user->rights->ressources->configurer) {
	$customtabs = new Complement($db);
	$customtabs->fetch($rowid);
	$customtabs->button = GETPOST('button');
	
	$customtabs->updatebutton($user);
	
	header("Location: " . $_SERVER["PHP_SELF"] . "?rowid=" . $rowid);
	exit();
}

/*
 * View
 */

$textobject = $langs->transnoentitiesnoconv("Members");

$help_url = 'EN:Module_RH|FR:Module_RH|ES:M&oacute;dulo_RH';
llxHeader('', $langs->trans("CustomtabsButtonSetup"), $help_url);

$head = customtabs_prepare_head($customtabs);

dol_fiche_head($head, 'button', $langs->trans("Customtabs"), 0, 'user');

print $langs->trans("DefineHereCustomTabsButton", $textobject) . '<br>' . "\n";
print '<br>';

dol_htmloutput_errors($mesg);

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

// tablename
print '<tr><td width="15%">' . $langs->trans("tablename") . '</td><td>' . $customtabs->tablename . '</td></tr>';

// Label
print '<tr><td width="15%">' . $langs->trans("Label") . '</td><td>' . $customtabs->libelle . '</td></tr>';
print '<tr><td colspan=2>' . $langs->trans("Button") . '</td></tr>';
print '<tr><td colspan=2>';
print '<textarea name="querybutton" wrap="soft" cols="80" rows=10>' . $customtabs->querybutton . '</textarea>';
print '</td></tr>';

print '</table>';

dol_fiche_end();
// Buttons
print '<div class="tabsAction">';
print '<center><input type="submit" class="button" value="' . $langs->trans("Save") . '"> &nbsp; &nbsp;';
print "</div>";
print "</form>";

llxFooter();

$db->close();
?>
