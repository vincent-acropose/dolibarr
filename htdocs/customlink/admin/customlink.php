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
 *   	\file       htdocs/customlink/admin/customlink.php
 *		\ingroup    customlink
 *		\brief      Page to setup the module customlink (nothing to do)
 */

// Dolibarr environment
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/customlink/core/lib/customlink.lib.php';

$langs->load("admin");
$langs->load("customlink@customlink");

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST('action','alpha');


/*
 * Actions
 */

// pas d'action juste une info



/*
 * View
 */

$help_url='EN:Module_customlink|FR:Module_customlink|ES:M&oacute;dulo_customlink';

llxHeader('',$langs->trans("CustomlinkSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("CustomlinkSetup"),$linkback,'setup');


$head = customlink_admin_prepare_head();

dol_fiche_head($head, 'admin', $langs->trans("CustomLink"), 0, 'customlink@customlink');


dol_htmloutput_mesg($mesg);


print "<H2>".$langs->trans("SettingIsOnToolsMenu")."</h2><br>";
print "<H3>".$langs->trans("AccessOnToolsMenu")."</h3>";


print '<br>';


dol_fiche_end();


llxFooter();

$db->close();
?>