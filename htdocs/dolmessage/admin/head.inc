<?php 
/* Copyright (C) 2014		 Oscim       <oscim@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */
 
 /**
 * 	\file		admin/head.inc
* 	\ingroup	dolmessage/admin
 * 	\brief		This file is about page
 */
 
// Dolibarr Core 
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

// local module 
dol_include_once('/dolmessage/core/lib/dolmessage.admin.lib.php');
// oscimmods Required
dol_include_once('/oscimmods/core/lib/PHP_Markdown_1.0.1o/markdown.php');






// Translations
$langs->load("admin");
$langs->load("dolmessage@dolmessage");

// Access control
if (! $user->admin) {
	accessforbidden();
}




global $tab; 

/*
 * View
 */

llxHeader('', $langs->trans("Module799801Name") );

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre( $langs->trans("Module799801Name")  , $linkback);

// Configuration header
$head = dolmessageAdminPrepareHead();
dol_fiche_head($head,$tab,$langs->trans("Module799801Name"),0,'dolmessage@dolmessage');

?>