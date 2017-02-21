<?php
/* Copyright (C) 2012-2013	Charles-Fr Benke		<charles.fr@benke.fr>
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
 *	\file       htdocs/core/lib/message.lib.php
 *	\brief      Ensemble de fonctions de base pour le module message
 *	\ingroup    message
 */

/**
*  Return array head with list of tabs to view object informations.
*
*  @param	Object	$object		Product
*  @return	array   	        head array with tabs
*/
function dolmessageAdminPrepareHead($object=null)
{
	global $langs, $conf, $user, $db;

	$langs->load("dolmessage@dolmessage");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolmessage/admin/index.php", 1);
	$head[$h][1] = $langs->trans("Note");
	$head[$h][2] = 'info';
	$h++;

	$head[$h][0] = dol_buildpath("/dolmessage/admin/config.php", 1);
	$head[$h][1] = $langs->trans("Config");
	$head[$h][2] = 'config';
	$h++;
	
	
	$head[$h][0] = dol_buildpath("/dolmessage/admin/faq.php", 1);
	$head[$h][1] = $langs->trans("Faq");
	$head[$h][2] = 'faq';
	$h++;
	
	$head[$h][0] = dol_buildpath("/dolmessage/admin/editor.php", 1);
	$head[$h][1] = $langs->trans("Editor");
	$head[$h][2] = 'editor';
	$h++;
	
	$head[$h][0] = dol_buildpath("/dolmessage/admin/abouts.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'abouts';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
// 	complete_head_from_modules($conf,$langs,$object,$head,$h,'message');
// 
// 
// 	complete_head_from_modules($conf,$langs,$object,$head,$h,'message','remove');

	
	return $head;
}

?>
