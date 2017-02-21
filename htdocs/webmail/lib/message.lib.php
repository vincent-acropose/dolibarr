<?php
/* Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       webmail/lib/message.lib.php
 *	\brief      webmail functions
 *	\ingroup    webmail
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to shoc
 */
function message_prepare_head($object)
{
	global $langs, $conf, $user;
	$langs->load("webmail@webmail");

	$h = 0;
	$head = array();

	dol_buildpath('/webmail/message.php',1).'?id='.$obj->id;
	$head[$h][0] = dol_buildpath('/webmail/message.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if ($object->files)
	{	
		$head[$h][0] = dol_buildpath('/webmail/attachments.php',1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Attachments');
		$head[$h][2] = 'attachment';
		$h++;
	}
    return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	        head array with tabs
 */
function webmailadmin_prepare_head()
{
	global $langs, $conf, $user;
	$langs->load("webmail@webmail");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/webmail/admin/admin.php',1);
	$head[$h][1] = $langs->trans("WebmailSetup");
	$head[$h][2] = 'configuration';
	$h++;
	
	$head[$h][0] = dol_buildpath("/webmail/admin/documentation.php", 1);
	$head[$h][1] = $langs->trans("Documentation");
	$head[$h][2] = 'documentation';
	$h ++;
	$head[$h][0] = dol_buildpath("/webmail/admin/support.php", 1);
	$head[$h][1] = $langs->trans("Support");
	$head[$h][2] = 'support';
	$h ++;

	$head[$h][0] = dol_buildpath('/webmail/admin/about.php',1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	return $head;
}


?>