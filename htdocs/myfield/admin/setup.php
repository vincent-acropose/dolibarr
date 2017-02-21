<?php
/* Copyright (C) 2015	  Charlie BENKE	 <charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/myfield/admin/setup.php
 *  \ingroup    myfield
 *  \brief      Page d'administration-configuration du module myfield
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory

require_once("../core/lib/myfield.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("admin");
$langs->load("other");
$langs->load("myfield@myfield");

// Security check
if (! $user->admin || $user->design) accessforbidden();

$action = GETPOST('action','alpha');


/*
 * Actions
 */

if ($action == 'setcontextview')
{
	// save the setting
	dolibarr_set_const($db, "MYFIELD_CONTEXT_VIEW", GETPOST('value','int'),'chaine',0,'',$conf->entity);
	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}

$form = new Form($db);

/*
 * View
 */

$page_name = $langs->trans("MyFieldSetup");
llxHeader('', $page_name);

$head = myfield_admin_prepare_head();


dol_fiche_head($head, 'setup', $langs->trans("myfield"), 0, "myfield@myfield");

print_titre($langs->trans("MyFieldSettingValue"));

dol_htmloutput_mesg($mesg);

print "<H2>".$langs->trans("SettingIsOnToolsMenu")."</h2><br>";
print "<H3>".$langs->trans("AccessOnToolsMenu")."</h3>";


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
print '<td align=left>'.$langs->trans("EnableContextView").'</td>';
print '<td align=left>'.$langs->trans("InfoEnableContextView").'</td>';
print '<td align=left >';
if ($conf->global->MYFIELD_CONTEXT_VIEW =="1")
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setcontextview&amp;value=0">'.img_picto($langs->trans("Activated"),'switch_on').'</a>';
else
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setcontextview&amp;value=1">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
print '</td>';
print '</table>';

dol_fiche_end();

// Footer
llxFooter();
// Close database handler
$db->close();
?>