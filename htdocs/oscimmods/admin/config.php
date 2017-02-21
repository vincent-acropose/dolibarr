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
 * 	\file		admin/about.php
* 	\ingroup	carddav
 * 	\brief		This file is about page
 */
$res = 0;
if (!$res && file_exists("../main.inc.php"))
    $res = @include("../main.inc.php");
if (!$res && file_exists("../../main.inc.php"))
    $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php"))
    $res = @include("../../../main.inc.php");
if (!$res && file_exists("../../../../main.inc.php"))
    $res = @include("../../../../main.inc.php");
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
    $res = @include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res && file_exists("../../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res)
    die("Include of main fails");
// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";


dol_include_once('/carddav/core/lib/carddav.lib.php');


// Translations
$langs->load("admin");
$langs->load("carddav@carddav");

// Access control
if (! $user->admin) {
	accessforbidden();
}

/*
 * Actions
 */


/**
	@remarks Config value conf
*/
if (GETPOST("action") == 'setconf')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "CARDDAV_PUT_SERVER",GETPOST("carddav_put_server"),'int',0,'',$conf->entity);
	dolibarr_set_const($db, "CARDDAV_CREATE_SERVER",GETPOST("carddav_create_server"),'int',0,'',$conf->entity);
	dolibarr_set_const($db, "CARDDAV_DELETE_SERVER",GETPOST("carddav_delete_server"),'int',0,'',$conf->entity);
	
	dolibarr_set_const($db, "CARDDAV_FICHE_TYPE",GETPOST("carddav_fichetype"),'chaine',0,'',$conf->entity);
	
	
	Header("location: ".dol_buildpath('/carddav/admin/config.php', 1)  );
	exit;
}

/*
 * View
 */
$page_name = "CardDavEditor";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("Module98365Name"), $linkback);

// Configuration header
$head = OscimModsAdminPrepareHead();
dol_fiche_head($head,'config',$langs->trans("Module98365Name"),0,'carddav@carddav');


if ($mesg) print '<br>'.$mesg;

print '<br>';
print '<form method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setconf">';

$var=true;




print '<br />';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td width="100">'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Reglage").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print "</tr>\n";

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td valign="top">'.$langs->trans("carddavPutOnServer").'</td>';
	print '<td>'.
						'<select name="carddav_put_server">'.
							'<option value="0">'.$langs->trans("carddavPutserverOff").'</option>'.
							'<option value="1" '.(($conf->global->CARDDAV_PUT_SERVER == 1)? 'selected="selected"' : '' ).'>'.$langs->trans("carddavPutserverOn").'</option>'.
							
						'</select>'.
				'</td>';
	print '<td>'.$langs->trans("carddavPutOnServerDetail").'</td>';
	print '</tr>';
	
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td valign="top">'.$langs->trans("carddavCreateOnServer").'</td>';
	print '<td>'.
						'<select name="carddav_create_server">'.
							'<option value="0">'.$langs->trans("carddavCreateserverOff").'</option>'.
							'<option value="1" '.(($conf->global->CARDDAV_CREATE_SERVER == 1)? 'selected="selected"' : '' ).'>'.$langs->trans("carddavCreateserverOn").'</option>'.
							
						'</select>'.
				'</td>';
	print '<td>'.$langs->trans("carddavCreateOnServerDetail").'</td>';
	print '</tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td valign="top">'.$langs->trans("carddavDeleteOnServer").'</td>';
	print '<td>'.
						'<select name="carddav_delete_server">'.
							'<option value="0">'.$langs->trans("carddavDeleteserverOff").'</option>'.
							'<option value="1" '.(($conf->global->CARDDAV_DELETE_SERVER == 1)? 'selected="selected"' : '' ).'>'.$langs->trans("carddavDeleteserverOn").'</option>'.
							
						'</select>'.
				'</td>';
	print '<td>'.$langs->trans("carddavDeleteOnServerDetail").'</td>';
	print '</tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td valign="top">'.$langs->trans("carddavFicheInDol").'</td>';
	print '<td>'.
						'<select name="carddav_fichetype">'.
							'<option value="Contact">'.$langs->trans("carddavFicheInDolContact").'</option>'.
							'<option value="Societe" '.(($conf->global->CARDDAV_FICHE_TYPE == 'Societe')? 'selected="selected"' : '' ).'>'.$langs->trans("carddavFicheInDolSoc").'</option>'.
						'</select>'.
				'</td>';
	print '<td>'.$langs->trans("carddavFicheInDolDetail").'</td>';
	print '</tr>';
	
	
	print '<tr '.$bc[$var].'><td colspan="2" align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</td></tr>\n";


print '</form>';


llxFooter();

$db->close();