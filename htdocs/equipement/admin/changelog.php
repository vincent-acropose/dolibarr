<?php
/* Copyright (C) 2015-2016	  Charlie BENKE	 <charlie@patas-monkey.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * equipement
 * \file htdocs/equipement/admin/changelog.php
 * \ingroup factory
 * \brief about page
 */

// Dolibarr environment
$res = 0;
if (! $res && file_exists("../../main.inc.php"))
	$res = @include ("../../main.inc.php"); // For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res = @include ("../../../main.inc.php"); // For "custom" directory
		                                           
// Libraries
dol_include_once("/equipement/core/lib/equipement.lib.php");

// Translations
$langs->load("equipement@equipement");

// Access control
if (! $user->admin)
	accessforbidden();
	
	/*
 * View
 */
$page_name = $langs->trans("About");
llxHeader('', $page_name);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("EquipementSetup"), $linkback, 'setup');

// Configuration header
$head = equipement_admin_prepare_head();
dol_fiche_head($head, 'changelog', $langs->trans("Equipement"), 0, "equipement@equipement");

// About page goes here
print '<br>';

print_titre($langs->trans("Changelog"));
print '<br>';
print nl2br(file_get_contents('../ChangeLog.txt'));

llxFooter();
$db->close();
?>
