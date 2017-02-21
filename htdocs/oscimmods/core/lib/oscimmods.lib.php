<?php
/*  Copyright (C) 2012		 Oscim					       <oscim@users.sourceforge.net>
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
 *	\file       htdocs/core/lib/oscimmods.lib.php
 *	\brief      Ensemble de fonctions de base pour le module message
 *	\ingroup    message
 */




function OscimModsAdminPrepareHead() {
	global $langs, $conf;

	$langs->load("oscimmods@oscimmods");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/oscimmods/admin/index.php", 1);
	$head[$h][1] = $langs->trans("Note");
	$head[$h][2] = 'info';
	$h++;

// 	$head[$h][0] = dol_buildpath("/oscimmods/admin/config.php", 1);
// 	$head[$h][1] = $langs->trans("Config");
// 	$head[$h][2] = 'config';
// 	$h++;
	
	
// 	$head[$h][0] = dol_buildpath("/oscimmods/admin/faq.php", 1);
// 	$head[$h][1] = $langs->trans("Faq");
// 	$head[$h][2] = 'faq';
// 	$h++;
	
	$head[$h][0] = dol_buildpath("/oscimmods/admin/editor.php", 1);
	$head[$h][1] = $langs->trans("Editor");
	$head[$h][2] = 'editor';
	$h++;
	
	$head[$h][0] = dol_buildpath("/oscimmods/admin/abouts.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

// 	complete_head_from_modules($conf, $langs, $object, $head, $h, 'oscimmods');

// 	complete_head_from_modules($conf, $langs, $object, $head, $h, 'oscimmods', 'remove');

	return $head;
}
?>
