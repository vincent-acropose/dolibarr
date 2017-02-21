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
 *   \file       htdocs/admin/calling.php
 *   \ingroup    calling
 *   \brief      Page to setup module clicktodial
 *	\author     Oscim <mail oscim@users.sourceforge.net>
 *	\version    $Id: calling.php,v 1.56 2012/06/10 15:28:01 oscim Exp $
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
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once "../lib/lib.calling.admin.php";

$langs->load("admin");
$langs->load("calling@calling");

if (!$user->admin)
  accessforbidden();




/**
	@remarks Config value conf
*/
if (GETPOST("action") == 'setconf')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated



	dolibarr_set_const($db, "CALLING_LOGS_IN_ACTION",GETPOST("calling_logs_in_action"),'chaine',0,'',$conf->entity);
	
	if($conf->global->CALLING_LOGS_IN_ACTION == "yes") {
	
		dolibarr_set_const($db, "CALLING_LOGS_IN_ACTION_INCOMING",GETPOST("calling_logs_in_action_incoming"),'chaine',0,'',$conf->entity);
		dolibarr_set_const($db, "CALLING_LOGS_IN_ACTION_INCOMING_INTERNAL",GETPOST("calling_logs_in_action_incoming_internal"),'chaine',0,'',$conf->entity);
		dolibarr_set_const($db, "CALLING_LOGS_IN_ACTION_INCOMING_TXTLABEL",GETPOST("calling_logs_in_action_incoming_label"),'chaine',0,'',$conf->entity);
		
		
		dolibarr_set_const($db, "CALLING_LOGS_IN_ACTION_INCOMING_TXTDESC",GETPOST("calling_logs_in_action_incoming_desc"),'chaine',0,'',$conf->entity);
				
		
		dolibarr_set_const($db, "CALLING_LOGS_IN_ACTION_OUTGOING",GETPOST("calling_logs_in_action_outgoing"),'chaine',0,'',$conf->entity);
		dolibarr_set_const($db, "CALLING_LOGS_IN_ACTION_OUTGOING_INTERNAL",GETPOST("calling_logs_in_action_outgoing_internal"),'chaine',0,'',$conf->entity);
		dolibarr_set_const($db, "CALLING_LOGS_IN_ACTION_OUTGOING_TXTLABEL",GETPOST("calling_logs_in_action_outgoing_label"),'chaine',0,'',$conf->entity);
		
		
		dolibarr_set_const($db, "CALLING_LOGS_IN_ACTION_OUTGOING_TXTDESC",GETPOST("calling_logs_in_action_outgoing_desc"),'chaine',0,'',$conf->entity);
	}
	


}





/*
 *
 *
 */

$wikihelp='EN:Module_ClickToDial_En|FR:Module_ClickToDial|ES:Módulo_ClickTodial_Es';


$html=new Form($db);
llxHeader('',$langs->trans("ClickToDialSetup"),$wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("CallingSetup"),$linkback);

// Configuration header
$head = callingAdminPrepareHead();
dol_fiche_head($head,'mod_calendar',$langs->trans("Module66Name"),0,'calling@calling');



print $langs->trans("CallingDesc")."<br>\n";


if ($mesg) print '<br>'.$mesg;

print '<br>';
print '<form method="post" action="mod_calendar.php">';
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
	print '<td valign="top">'.$langs->trans("CallingLogInAction").'</td>';
	print '<td>'.
						'<select name="calling_logs_in_action">'.
							'<option value="no">'.$langs->trans("CallingLogInActionNo").'</option>'.
							'<option value="yes" '.(($conf->global->CALLING_LOGS_IN_ACTION == "yes")? 'selected="selected"' : '' ).'>'.$langs->trans("CallingLogInActionYes").'</option>'.
						'</select>'.
				'</td>';
	print '<td>'.$langs->trans("CallingLogInActionDetail").'</td>';
	print '</tr>';
	print '</table>';

	if($conf->global->CALLING_LOGS_IN_ACTION == "yes") {
		
		print '<h3>'.$langs->trans("CallingHeadingIncomingCalls").'</h3>';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td width="100">'.$langs->trans("Name").'</td>';
		print '<td>'.$langs->trans("Reglage").'</td>';
		print '<td>'.$langs->trans("Description").'</td>';
		print "</tr>\n";

		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td valign="top">'.$langs->trans("CallingLogInActionIncoming").'</td>';
		print '<td>'.
							'<select name="calling_logs_in_action_incoming">'.
								'<option value="no">'.$langs->trans("CallingLogInActionNo").'</option>'.
								'<option value="yes" '.(($conf->global->CALLING_LOGS_IN_ACTION_INCOMING == "yes")? 'selected="selected"' : '' ).'>'.$langs->trans("CallingLogInActionYes").'</option>'.
							'</select>'.
					'</td>';
		print '<td>'.$langs->trans("CallingLogInActionIncomingDetail").'</td>';
		print '</tr>';
		
		
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td valign="top">'.$langs->trans("CallingLogInActionIncomingInternal").'</td>';
		print '<td>'.
							'<select name="calling_logs_in_action_incoming_internal">'.
								'<option value="no">'.$langs->trans("CallingLogInActionNo").'</option>'.
								'<option value="yes" '.(($conf->global->CALLING_LOGS_IN_ACTION_INCOMING_INTERNAL == "yes")? 'selected="selected"' : '' ).'>'.$langs->trans("CallingLogInActionYes").'</option>'.
							'</select>'.
					'</td>';
		print '<td>'.$langs->trans("CallingLogInActionIncomingInternalDetail").'</td>';
		print '</tr>';
		
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td valign="top">'.$langs->trans("CallingLogInActionIncomingTxtLabel").'</td>';
		print '<td>'.
							'<textarea name="calling_logs_in_action_incoming_label" rows="2" cols="60">'.$conf->global->CALLING_LOGS_IN_ACTION_INCOMING_TXTLABEL.'</textarea>'.
					'</td>';
		print '<td>'.$langs->trans("CallingLogInActionIncomingInternalTxtLabelDetail").'</td>';
		print '</tr>';
		
				$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td valign="top">'.$langs->trans("CallingLogInActionIncomingTxtDesc").'</td>';
		print '<td>'.
							'<textarea name="calling_logs_in_action_incoming_desc" rows="3" cols="60">'.$conf->global->CALLING_LOGS_IN_ACTION_INCOMING_TXTDESC.'</textarea>'.
					'</td>';
		print '<td>'.$langs->trans("CallingLogInActionIncomingInternalTxtDescDetail").'</td>';
		print '</tr>';
		print '</table>';
		
		
		
		
		print '<h3>'.$langs->trans("CallingHeadingOutgoingCalls").'</h3>';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td width="100">'.$langs->trans("Name").'</td>';
		print '<td>'.$langs->trans("Reglage").'</td>';
		print '<td>'.$langs->trans("Description").'</td>';
		print "</tr>\n";
		
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td valign="top">'.$langs->trans("CallingLogInActionOutgoing").'</td>';
		print '<td>'.
							'<select name="calling_logs_in_action_outgoing">'.
								'<option value="no">'.$langs->trans("CallingLogInActionNo").'</option>'.
								'<option value="yes" '.(($conf->global->CALLING_LOGS_IN_ACTION_OUTGOING == "yes")? 'selected="selected"' : '' ).'>'.$langs->trans("CallingLogInActionYes").'</option>'.
							'</select>'.
					'</td>';
		print '<td>'.$langs->trans("CallingLogInActionOutgoingDetail").'</td>';
		
		
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td valign="top">'.$langs->trans("CallingLogInActionOutgoingInternal").'</td>';
		print '<td>'.
							'<select name="calling_logs_in_action_outgoing_internal">'.
								'<option value="no">'.$langs->trans("CallingLogInActionNo").'</option>'.
								'<option value="yes" '.(($conf->global->CALLING_LOGS_IN_ACTION_OUTGOING_INTERNAL == "yes")? 'selected="selected"' : '' ).'>'.$langs->trans("CallingLogInActionYes").'</option>'.
							'</select>'.
					'</td>';
		print '<td>'.$langs->trans("CallingLogInActionOutgoingInternalDetail").'</td>';
		print '</tr>';
		
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td valign="top">'.$langs->trans("CallingLogInActionOutgoingTxtLabel").'</td>';
		print '<td>'.
							'<textarea name="calling_logs_in_action_outgoing_label" rows="2" cols="60">'.$conf->global->CALLING_LOGS_IN_ACTION_OUTGOING_TXTLABEL.'</textarea>'.
					'</td>';
		print '<td>'.$langs->trans("CallingLogInActionOutgoingTxtLabelDetail").'</td>';
		print '</tr>';
		
				$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td valign="top">'.$langs->trans("CallingLogInActionOutgoingTxtDesc").'</td>';
		print '<td>'.
							'<textarea name="calling_logs_in_action_outgoing_desc" rows="3" cols="60">'.$conf->global->CALLING_LOGS_IN_ACTION_OUTGOING_TXTDESC.'</textarea>'.
					'</td>';
		print '<td>'.$langs->trans("CallingLogInActionOutgoingInternalTxtDescDetail").'</td>';
		print '</tr>';
		
		
		print '</table>';

	}

// print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<tr '.$bc[$var].'><td colspan="2" align="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';


$db->close();

llxFooter('$Date: 2011/07/31 22:23:24 $ - $Revision: 1.24 $');
?>
