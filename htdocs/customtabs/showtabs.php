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
 * \file htdocs/customtabs/showstabs.php
 * \ingroup member
 * \brief Member's type setup
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include ($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory

require_once 'core/lib/customtabs.lib.php';
require_once 'class/customtabs.class.php';
require_once 'class/customtabsgroup.class.php';

$langs->load("customtabs@customtabs");

$rowid = GETPOST('rowid', 'int');

$action = GETPOST('action', 'alpha');

// Security check
$result = restrictedArea($user, 'customtabs', $rowid, 'usergroup');

if (GETPOST('button_removefilter')) {
	$search_lastname = "";
	$search_login = "";
	$search_email = "";
	$type = "";
	$sall = "";
}

/*
 *	Actions
 */

if ($action == 'setshow' && $user->rights->customtabs->configurer) {
	$customtabs = new Complement($db);
	$customtabs->fetch(GETPOST("fk_customtabs"));
	$customtabs->setShowTabs(GETPOST("fk_customtabsgroup"), GETPOST("activate"));
}

/*
 * View
 */

llxHeader('', $langs->trans("CustomTabs"), 'EN:Module_customtabs|FR:Module_customtabs|ES:M&oacute;dulo_customtabs');

$form = new Form($db);

// Liste of complement

print_fiche_titre($langs->trans("Customtabslist"));
$customtabs = new Customtabs($db);
$customtabsGroup = new CustomtabsGroup($db);

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Label") . '</td>';
print '<td align="center">' . $langs->trans("TableName") . '</td>';
foreach ( $customtabsGroup->liste_array() as $key => $value ) {
	print '<td align="center">' . $value . '</td>';
}

print '<td align="center">' . $langs->trans("statut") . '</td>';
print "</tr>\n";

$var = True;
foreach ( $customtabs->liste_array() as $keyCompl => $value ) {
	$customtabs->fetch($keyCompl);
	
	$var = ! $var;
	print "<tr " . $bc[$var] . ">";
	print '<td>' . $customtabs->libelle . '</td>';
	print '<td>' . $customtabs->tablename . '</td>';
	
	foreach ( $customtabsGroup->liste_array() as $keyRT => $value ) {
		if ($customtabs->getShowTabs($keyRT) == 1) {
			print '<td align="center"><a href="' . $_SERVER['PHP_SELF'] . '?action=setshow&fk_customtabs=' . $keyCompl . '&fk_customtabsgroup=' . $keyRT . '&activate=0">';
			print img_picto("actif", "switch_on") . '</a></td>';
		} else {
			print '<td align="center"><a href="' . $_SERVER['PHP_SELF'] . '?action=setshow&fk_complement=' . $keyCompl . '&fk_ressourcetype=' . $keyRT . '&activate=1">';
			print img_picto("non actif", "switch_off") . '</a></td>';
		}
	}
	
	print '<td align="center">' . yn($customtabs->fk_statut) . '</td>';
	print "</tr>";
	$i ++;
}
print "</table>";

/*
 * Barre d'actions
 *
 */
print '<div class="tabsAction">';

// New type
if ($user->rights->customtabs->configurer) {
	print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=create">' . $langs->trans("NewCustomTabs") . '</a>';
}

print "</div>";

$db->close();

llxFooter();
?>
