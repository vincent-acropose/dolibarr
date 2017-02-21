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
 *   	\file       webmail/tabs/user_webmailconf.php
 *		\ingroup    webmail
 */

$res=@include("../../main.inc.php");								// For root directory
if (! $res) $res=@include("../../../main.inc.php");					// For "custom" directory


require_once(DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php');
require_once(DOL_DOCUMENT_ROOT . '/user/class/user.class.php');
dol_include_once("/webmail/class/userconfig.class.php");


$id = GETPOST('id', 'int');
$commid =  GETPOST('commid', 'int');
$delcommid = GETPOST('delcommid', 'int');
$action = GETPOST('action');

$langs->load("companies");
$langs->load("members");
$langs->load("bills");
$langs->load("users");
$langs->load("dolimail@dolimail");

$fuser = new User($db);
$fuser->fetch($id);

$mailconfig = new Userconfig($db);
$mailconfig->fetch_from_user($id);

// If user is not user read and no permission to read other users, we stop
if (($fuser->id != $user->id) && (!$user->rights->user->user->lire))
    accessforbidden();

// Security check
$socid = 0;
if ($user->societe_id > 0)
    $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');

if ($user->id == $id)
    $feature2 = ''; // A user can always read its own card
$result = restrictedArea($user, 'user', $id, '&user', $feature2);


/* * *************************************************************************** */
/*                     Actions                                                */
/* * *************************************************************************** */

if ($action == 'update' && ($user->rights->webmail->config || $user->admin) && !GETPOST("cancel")) {
    $db->begin();

    //$mailconfig->id = GETPOST("mailboxuserid");
    $mailconfig->fk_user = GETPOST("id");
    $mailconfig->login = GETPOST("login");
    $mailconfig->password = GETPOST("password");
    $mailconfig->safemail = GETPOST("safemail");
    $mailconfig->dayssafe = GETPOST("dayssafe");

    if ($mailconfig->id > 0)
        $res = $mailconfig->update($user);
    else
        $res = $mailconfig->create($user);

    if ($res < 0) {
        $mesg = '<div class="error">' . $adh->error . '</div>';
        $db->rollback();
    } else {
        $db->commit();
    }
}


if($id && $commid)
{
	$action = 'add';

	if ($user->rights->webmail->config || $user->admin)
	{
 		$mailconfig->fk_user = $id;
		$mailconfig->add_userviewer($commid);

		header("Location: user_webmailconf.php?id=".$id);
		exit;
	}
	else
	{
		header("Location: user_webmailconf.php?id=".$id);
		exit;
	}
}

if($id && $delcommid)
{
	$action = 'delete';

	if ($user->rights->webmail->config || $user->admin)
	{
		$mailconfig->fk_user = $id;
		$mailconfig->del_userviewer($delcommid);

		header("Location: user_webmailconf.php?id=".$id);
		exit;
	}
	else
	{
		header("Location: user_webmailconf.php?id=".$id);
		exit;
	}
}



/* * *************************************************************************** */
/* Affichage fiche                                                            */
/* * *************************************************************************** */

llxHeader();

$form = new Form($db);

if ($id && $action!="setusersview")
{
    $head = user_prepare_head($fuser);
    $title = $langs->trans("User");
    dol_fiche_head($head, 'mailconfig', $title, 0, 'user');

    if ($msg)
    {
        print '<div class="error">' . $msg . '</div>';
    }
    
    print "<form method=\"post\" action=".$_SERVER['PHP_SELF'].">";
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="id" value="' . $id . '">';
    print '<input type="hidden" name="mailboxuserid" value="' . $mailconfig->id . '">';
    print '<input type="hidden" name="action" value="update">';

    print '<table class="border" width="100%">';

    // Reference
    print '<tr><td width="20%">' . $langs->trans('Ref') . '</td>';
    print '<td colspan="3">';
    print $form->showrefnav($fuser, 'id', '', $user->rights->user->user->lire || $user->admin);
    print '</td>';
    print '</tr>';

    // Nom
    print '<tr><td>' . $langs->trans("Lastname") . ' ' . $langs->trans("Firstname") . '</td><td class="valeur" colspan="3">' . strtoupper($fuser->lastname) . '&nbsp;' . ucfirst($fuser->firstname) . '&nbsp;</td></tr>';

    // Login
    print '<tr><td>' . $langs->trans("Login") . '</td><td class="valeur" colspan="3">';
    if ($action == 'edit')
    {
        print '<input size="30" type="text" class="flat" name="login" value="'.($mailconfig->login?$mailconfig->login:"").'">';
    }
    else
        print $mailconfig->login . '&nbsp;';
    print '</td></tr>';
    
    // Password
    print '<tr><td>' . $langs->trans("Password") . '</td><td class="valeur" colspan="3">';
    if ($action == 'edit')
    {
        print '<input size="12" maxlength="32" type="password" class="flat" name="password" value="'.($mailconfig->password?$mailconfig->password:"").'">';
    }
    else
   {
		print preg_replace('/./i', '*', $mailconfig->password);   
    }
    print '</td></tr>';
    
    // Delete Mail from server
	print '<tr><td>'.$langs->trans('DeleteMailServer').'</td>';
	print '<td>';
	if ($action=='edit')
	{
	print $form->selectyesno('safemail',($mailconfig->safemail?$mailconfig->safemail:0),1); //Yes by default
	}
	else
	{
		print yn(($mailconfig->safemail?$mailconfig->safemail:0));
	}
	print '</td></tr>';
	
	// Days to safe mails into server
    print '<tr><td>' . $langs->trans("SafeDays") . '</td><td class="valeur" colspan="3">';
    if ($action == 'edit')
    {
        print '<input size="4" maxlength="3" class="flat" name="dayssafe" value="' . ($mailconfig->dayssafe?$mailconfig->dayssafe:10) . '">';
    }
    else
   	{
		print ($mailconfig->dayssafe?$mailconfig->dayssafe:10);   
    }
    print ' '.$langs->trans("days").'</td></tr>';
	
    //Users
	print '<tr><td>';
	print '<table width="100%" class="nobordernopadding"><tr><td>';
	print $langs->trans('UsersViews');
	print '<td align="right">';
	if ($user->rights->webmail->config || $user->admin)
		print '<a href="user_webmailconf.php?id='.$id.'&amp;action=setusersview">'.img_edit().'</a>';
	else
		print '&nbsp;';
	
	print '</td></tr></table>';
	print '</td>';
	print '<td colspan="3">';

	$sql = "SELECT u.rowid, u.lastname, u.firstname";
	$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql .= " , ".MAIN_DB_PREFIX."webmail_users_view as sc";
	$sql .= " WHERE sc.fk_user =".$fuser->id;
	$sql .= " AND sc.fk_user_view = u.rowid";
	$sql .= " ORDER BY u.lastname ASC ";
	dol_syslog('webmail/user_webmailconf.php::list salesman sql = '.$sql,LOG_DEBUG);
	
	$resql = $db->query($sql);
	if ($resql)
	{
		
		$num = $db->num_rows($resql);
		
		if ($num > 0)
		{
			$userstatic=new User($db);
			$i=0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$userstatic->id=$obj->rowid;
				$userstatic->lastname=$obj->lastname;
				$userstatic->firstname=$obj->firstname;
				print $userstatic->getNomUrl(1);
				$i++;
				if ($i < $num) print ', ';
	
			}
		}
		$db->free($resql);
	}
	else print $langs->trans("NoUsersAffected");
	print '</td></tr>';
    print "</table>";

    if ($action == 'edit') {
        print '<center><br>';
        print '<input type="submit" class="button" name="update" value="' . $langs->trans("Save") . '">';
        print '&nbsp; &nbsp;';
        print '<input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
        print '</center>';
    }

    print "</form>\n";


    /*
     * Actions
     */
    print '</div>';
    dol_fiche_end();
     
    print '<div class="tabsAction">';

    if (($user->rights->webmail->config || $user->admin) && $action != 'edit') 
    {
         print "<a class=\"butAction\" href=\"user_webmailconf.php?id=" . $id . "&amp;action=edit\">" . $langs->trans('Modify') . "</a>";
    }

    print "</div>";
}
elseif ($action=="setusersview")
{
	$head = user_prepare_head($fuser);
    $title = $langs->trans("User");
    dol_fiche_head($head, 'mailconfig', $title, 0, 'user');

    if ($msg)
        print '<div class="error">' . $msg . '</div>';

    print "<form method=\"post\" action=".$_SERVER['PHP_SELF'].">";
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="id" value="' . $id . '">';
    print '<input type="hidden" name="mailboxuserid" value="' . $mailconfig->id . '">';
    print '<input type="hidden" name="action" value="update">';

    print '<table class="border" width="100%">';

    // Reference
    print '<tr><td width="20%">' . $langs->trans('Ref') . '</td>';
    print '<td colspan="3">';
    print $form->showrefnav($fuser, 'id', '', $user->rights->user->user->lire || $user->admin);
    print '</td>';
    print '</tr>';

    // Nom
    print '<tr><td>' . $langs->trans("Lastname") . ' ' . $langs->trans("Firstname") . '</td><td class="valeur" colspan="3">' . strtoupper($fuser->lastname) . '&nbsp;' . ucfirst($fuser->firstname) . '&nbsp;</td></tr>';

    // Login
    print '<tr><td>' . $langs->trans("Login") . '</td><td class="valeur" colspan="3">';
    if ($action == 'edit')
        print '<input size="30" type="text" class="flat" name="login" value="' . $mailconfig->login . '">';
    else
        print $mailconfig->login . '&nbsp;';
    print '</td></tr>';
    // Password
    print '<tr><td>' . $langs->trans("Password") . '</td><td class="valeur" colspan="3">';
    if ($action == 'edit') {
        print '<input size="12" maxlength="32" type="password" class="flat" name="password" value="' . $mailconfig->password . '">';
    } else {
        
            print preg_replace('/./i', '*', $mailconfig->password);
        
    }
    print '</td></tr>';
    
    // Liste usersview
	print '<tr><td valign="top">'.$langs->trans("UsersViews").'</td>';
	print '<td colspan="3">';

	$sql = "SELECT u.rowid, u.lastname, u.firstname";
	$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql .= " , ".MAIN_DB_PREFIX."webmail_users_view as sc";
	$sql .= " WHERE sc.fk_user =".$fuser->id;
	$sql .= " AND sc.fk_user_view = u.rowid";
	$sql .= " ORDER BY u.lastname ASC ";
	dol_syslog('webmail/user_webmailconf.php::list salesman sql = '.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">';
			print img_object($langs->trans("ShowUser"),"user").' ';
			print dolGetFirstLastname($obj->firstname, $obj->lastname)."\n";
			print '</a>&nbsp;';
			if ($user->rights->societe->creer)
			{
			    print '<a href="user_webmailconf.php?id='.$id.'&amp;delcommid='.$obj->rowid.'">';
			    print img_delete();
			    print '</a>';
			}
			print '<br>';
			$i++;
		}

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
	if($i == 0) { print $langs->trans("NoUsersAffected"); }

	print "</td></tr>";

	print '</table>';
	print "</div>\n";
	
	if ($user->rights->webmail->config || $user->admin)
	{
		/*
		 * Liste
		 *
		 */

		$langs->load("users");
		$title=$langs->trans("ListOfUsers");

		$sql = "SELECT u.rowid, u.lastname, u.firstname, u.login";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
		if (! empty($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND u.statut<>0"; 
		$sql.=" AND u.rowid<>".$id;
		$sql.= " ORDER BY u.lastname ASC ";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			print_titre($title);

			// Lignes des titres
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Name").'</td>';
			print '<td>'.$langs->trans("Login").'</td>';
			print '<td>&nbsp;</td>';
			print "</tr>\n";

			$var=True;

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var]."><td>";
				print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">';
				print img_object($langs->trans("ShowUser"),"user").' ';
				print dolGetFirstLastname($obj->firstname, $obj->lastname)."\n";
				print '</a>';
				print '</td><td>'.$obj->login.'</td>';
				print '<td><a href="user_webmailconf.php?id='.$id.'&amp;commid='.$obj->rowid.'">'.$langs->trans("Add").'</a></td>';

				print '</tr>'."\n";
				$i++;
			}

			print "</table>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}
	
}

$db->close();

llxFooter();
?>
