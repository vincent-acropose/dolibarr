<?php
/* Copyright (C) 2012-2013  Raphaël Doursenaud 	<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014		Juanjo Menent 		<jmenent@2byte.es>
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
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/documentation.php
 * 	\ingroup	webmail
 * 	\brief		Documentation page
 */
// Dolibarr environment
$res=@include("../../main.inc.php");                                // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/message.lib.php';
//require_once '../core/modules/modAccountingExport.class.php';

// Translations
$langs->load("admin");
$langs->load("help");
$langs->load("webmail@webmail");

// Access control
if (! $user->admin)
{
    accessforbidden();
}

/*
 * View
 */
$page_name = "WebMailDocumentation";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = webmailadmin_prepare_head();
//dol_fiche_head($head, 'documentation', $langs->trans("Module105000Name"), 0,"accountingexport@accountingexport");

dol_fiche_head($head, 'documentation', $langs->trans("Webmail"), 0, 'webmail@webmail');

// Page
echo '<iframe src="../doc/index.html" seamless height="1050px" width="100%"></iframe>';

llxFooter();

$db->close();
