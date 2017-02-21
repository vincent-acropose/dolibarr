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
require_once DOL_DOCUMENT_ROOT.'/mylist/core/lib/mylist.lib.php';

$langs->load("admin");
$langs->load("mylist@mylist");

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST('action','alpha');


/*
 * Actions
 */

if ($action == 'setvalue')
{
	// save the setting
	dolibarr_set_const($db, "MYLIST_NB_ROWS", GETPOST('nbrows','int'),'chaine',0,'',$conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}
if ($action == 'setdefaultother')
{
	// save the setting
	dolibarr_set_const($db, "MAIN_USE_JQUERY_DATATABLES", GETPOST('value','int'),'chaine',0,'',$conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}


// Get setting 
$nbrows=$conf->global->MYLIST_NB_ROWS;
if ($nbrows=="")
{
	$nbrows=25;
	dolibarr_set_const($db, "MYLIST_NB_ROWS", $nbrows,'chaine',0,'',$conf->entity);
}

/*
 * View
 */

$help_url='EN:Module_mylist|FR:Module_mylist|ES:M&oacute;dulo_mylist';

llxHeader('',$langs->trans("MylistSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MylistSetup"),$linkback,'setup');


$head = mylist_admin_prepare_head();

dol_fiche_head($head, 'admin', $langs->trans("myList"), 0, 'mylist@mylist');


dol_htmloutput_mesg($mesg);

// la sélection des status à suivre dans le process commercial
print '<br>';
print_titre($langs->trans("GeneralSetting"));
print '<br>';


print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td width="200px">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td nowrap >'.$langs->trans("Value").'</td>';
print '</tr>'."\n";
print '<tr >';
print '<td align=left>'.$langs->trans("NumberRowsInmyList").'</td>';
print '<td align=left>'.$langs->trans("InfoNumberRowsInmyList").'</td>';
print '<td  align=left>';
print '<form method="post" action="admin.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';
print '<input type=text value="'.$nbrows.'" name=nbrows>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td>';
print '</tr>'."\n";
print '<tr >';
print '<td align=left>'.$langs->trans("EnableDatatables").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableDatatables").'</td>';
print '<td align=left >';
if ($conf->global->MAIN_USE_JQUERY_DATATABLES =="1")
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdefaultother&amp;value=0">'.img_picto($langs->trans("Activated"),'switch_on').'</a>';
else
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdefaultother&amp;value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
print '</td>';
print '</table>';
// Boutons d'action

print '<br>';


dol_fiche_end();


llxFooter();

$db->close();
?>