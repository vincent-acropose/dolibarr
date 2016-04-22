<?php
/* Copyright (C) 2014     Charles-Fr BENKE <charles.fr@benke.fr>
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
 * \file htdocs/custom-tabs/admin/custom-tabs.php
 * \ingroup cusqtomtabs
 * \brief Page to setup the module cusqtomtabs
 */

// Dolibarr environment
$res = 0;
if (! $res && file_exists("../../main.inc.php"))
	$res = @include ("../../main.inc.php"); // For root directory
if (! $res && file_exists("../../../main.inc.php"))
	$res = @include ("../../../main.inc.php"); // For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/customtabs/core/lib/customtabs.lib.php';

$langs->load("admin");
$langs->load("customtabs@customtabs");

if (! $user->admin)
	accessforbidden();

$type = array (
		'yesno',
		'texte',
		'chaine' 
);

$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

// pas d'action juste une info

/*
 * View
 */

$help_url = 'EN:Module_custom-tabs|FR:Module_custom-tabs|ES:M&oacute;dulo_custom-tabs';

llxHeader('', $langs->trans("CustomtabsSetup"), $help_url);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("CustomtabsSetup"), $linkback, 'setup');

$head = customtabs_admin_prepare_head();

dol_fiche_head($head, 'admin', $langs->trans("CustomTabs"), 0, 'customtabs@customtabs');

dol_htmloutput_mesg($mesg);

print "<H2>" . $langs->trans("SettingIsOnToolsMenu") . "</h2><br>";
print "<H3>" . $langs->trans("AccessOnToolsMenu") . "</h3>";

print '<br>';

dol_fiche_end();

llxFooter();

$db->close();
?>