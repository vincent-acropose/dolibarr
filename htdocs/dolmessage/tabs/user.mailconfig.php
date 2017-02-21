<?php
/* Copyright (C) 2014 Oscim 	<support@oscim.fr>
 * Copyright (C) 2015 Oscss-Shop Team <support@oscss-shop.fr>
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
 *   	\file       dev/skeletons/skeleton_page.php
 * 		\ingroup    mymodule othermodule1 othermodule2
 * 		\brief      This file is an example of a php page
 * 					Put here some comments
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


require_once(DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php');
require_once(DOL_DOCUMENT_ROOT . '/user/class/user.class.php');


dol_include_once('dolmessage/class/user.mailconfig.class.php');

$id = GETPOST('id', 'int');
$action = GETPOST('action');

$langs->load("companies");
$langs->load("members");
$langs->load("bills");
$langs->load("users");
$langs->load("dolmessage@dolmessage");



$fuser = new User($db);
$fuser->fetch($id);

// If user is not user read and no permission to read other users, we stop
if (($fuser->id != $user->id) && (!$user->rights->user->user->lire))
    accessforbidden();


if ($id > 0)
    if (($fuser->id != $user->id) && (!$user->rights->dolmessage->user->modifier || !$user->admin ))
        accessforbidden();





switch ($action) {

    case 'update':


        $db->begin();


        foreach (GETPOST('title') as $key => $row) {
            $Usermail = new Usermailconfig($db);
            if (!empty($row)) {
                $key2 = (($key < 1) ? 1 : ((int) $key + 1) ); // config start at 1
                $Usermail->fetch_from_user($id, $key2);

                $mailboxuserid = GETPOST("mailboxuserid");

                $title = $row;
                $imap_id = GETPOST("mailboxnumber");
                $imap_login = GETPOST("imap_login");
                $imap_password = GETPOST("imap_password");
                $imap_host = GETPOST("imap_host");
                $imap_port = GETPOST("imap_port");
                $imap_ssl = GETPOST("imap_ssl");
                $imap_ssl_novalidate_cert = GETPOST("imap_ssl_novalidate_cert");


                $Usermail->number = $imap_id[$key];
                $Usermail->title = $title;
                $Usermail->fk_user = $id;
                $Usermail->imap_login = $imap_login[$key];
                $Usermail->imap_password = $imap_password[$key];
                $Usermail->imap_host = $imap_host[$key];
                $Usermail->imap_port = $imap_port[$key];
                $Usermail->imap_ssl = $imap_ssl[$key];
                $Usermail->imap_ssl_novalidate_cert = $imap_ssl_novalidate_cert[$key];

                if ($Usermail->id > 0) {
                    $res = $Usermail->update($user);
                } else {
                    $res = $Usermail->create($user);
                }

                if ($res < 0) {
// 						$mesg = '<div class="error">' . $Usermail->error . '</div>';
                    $db->rollback();
                } else {
                    $db->commit();
                }
            }
        }


        Header("location: " . dol_buildpath('/dolmessage/tabs/user.mailconfig.php', 1) . '?id=' . $id);
        exit;
        break;

    default:

        $listing = array();



        $sql = "SELECT *, fk_user as user_id ";
        $sql.= " FROM " . MAIN_DB_PREFIX . "userwebmail";
        $sql.= " WHERE  1 ";
        $sql.= " AND fk_user = '" . $fuser->id . "' ORDER BY number ASC";
// echo $sql; 
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);

            $i = 0;
            $var = true;
            while ($i < $num) {
                $objp = $db->fetch_object($resql);
                $i++;
// 			print_r($objp);
                if ($action == 'edit') {
                    $objp->number = $objp->number . '<input type="hidden" name="mailboxnumber[]" value="' . $objp->number . '">';
                    $objp->title = '<input size="30" type="text" class="flat" name="title[]" value="' . $objp->title . '">';
                    $objp->imap_login = '<input size="30" type="text" class="flat" name="imap_login[]" value="' . $objp->imap_login . '">';
                    $objp->imap_password = '<input size="12" maxlength="32" type="password" class="flat" name="imap_password[]" value="' . $objp->imap_password . '" >';
                    $objp->imap_host = '<input size="30" type="text" class="flat" name="imap_host[]" value="' . $objp->imap_host . '">';
                    $objp->imap_port = '<input size="30" type="text" class="flat" name="imap_port[]" value="' . $objp->imap_port . '">';

                    $tmp = '<select name="imap_ssl[]" >';
                    $tmp .= '<option value="1" ' . ( ($objp->imap_ssl) ? 'selected ' : '' ) . '">Oui</option>';
                    $tmp .= '<option value="0" ' . ( (!$objp->imap_ssl) ? 'selected ' : '' ) . '">Non</option>';
                    $tmp .= '</select>';
                    $objp->imap_ssl = $tmp;

                    $tmp = '<select name="imap_ssl_novalidate_cert[]" >';
                    $tmp .= '<option value="1" ' . ( ($objp->imap_ssl_novalidate_cert) ? 'selected ' : '' ) . '">Oui</option>';
                    $tmp .= '<option value="0" ' . ( (!$objp->imap_ssl_novalidate_cert) ? 'selected ' : '' ) . '">Non</option>';
                    $tmp .= '</select>';
                    $objp->imap_ssl_novalidate_cert = $tmp;
                } else {
                    $objp->imap_password = ( ($objp->imap_password) ? preg_replace('/./i', '*', $objp->imap_password) : $langs->trans("Hidden") );
                    $objp->imap_ssl = ( ($objp->imap_ssl) ? 'Oui ' : 'Non' );
                    $objp->imap_ssl_novalidate_cert = ( ($objp->imap_ssl_novalidate_cert) ? 'Oui ' : 'Non' );
                }

                $listing[] = $objp;
            }
        }

        if ($action == 'edit') {
            $objp = new stdClass;
            $i++;
            $objp->number = $i . '<input type="hidden" name="mailboxnumber[]" value="' . $i . '">';
            $objp->title = '<input size="30" type="text" class="flat" name="title[]" value="">';
            $objp->imap_login = '<input size="30" type="text" class="flat" name="imap_login[]" value="">';
            $objp->imap_password = '<input size="12" maxlength="32" type="password" class="flat" name="imap_password[]" value="" >';
            $objp->imap_host = '<input size="30" type="text" class="flat" name="imap_host[]" value="">';
            $objp->imap_port = '<input size="30" type="text" class="flat" name="imap_port[]" value="">';

            $listing[] = $objp;
        }
}
// 			print_r($listing);
/* * *************************************************************************** */
/* Affichage fiche                                                            */
/* * *************************************************************************** */

llxHeader();

$form = new Form($db);

if ($id) {


    $head = user_prepare_head($fuser);

    $title = $langs->trans("User");
    dol_fiche_head($head, 'Usermail', $title, 0, 'user');

    if ($msg)
        print '<div class="error">' . $msg . '</div>';

    print '<form method="post" action="' . dol_buildpath('/dolmessage/tabs/user.mailconfig.php', 1) . '">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="id" value="' . $id . '">';
    print '<input type="hidden" name="mailboxuserid" value="' . $fuser->id . '">';
    print '<input type="hidden" name="action" value="update">';


    /*
      $resql = $db->query($sql);
      if ($resql)
      {

      $i = 0;
      $var=true; */


    foreach ($listing as $objp) {

// 		$fuser->id = $objp->id;
        $fuser->number = $objp->number;
        $fuser->title = $objp->title;
        $fuser->imap_login = $objp->imap_login;
        $fuser->imap_password = $objp->imap_password;
        $fuser->imap_host = $objp->imap_host;
        $fuser->imap_port = $objp->imap_port;
        $fuser->imap_ssl = $objp->imap_ssl;
        $fuser->imap_ssl_novalidate_cert = $objp->imap_ssl_novalidate_cert;


        print '<table class="border" width="100%">';

        // Reference
        print '<tr><td width="20%">' . $langs->trans('Ref') . '</td>';
        print '<td colspan="3">';
        if ($i == 0)
            print $form->showrefnav($fuser, 'id', '', $user->rights->user->user->lire || $user->admin);
        print '</td>';
        print '</tr>';

        print '<tr><td>' . $langs->trans("DolMessageUserMailBoxNumber") . '</td><td class="valeur" colspan="3">' . $fuser->number . '</td></tr>';
        print '<tr><td>' . $langs->trans("Lastname") . ' ' . $langs->trans("Firstname") . '</td><td class="valeur" colspan="3">' . $fuser->nom . '&nbsp;' . $fuser->prenom . '&nbsp;</td></tr>';
        print '<tr><td>' . $langs->trans("DolMessageUserMailBoxName") . '</td><td class="valeur" colspan="3">';
        print $fuser->title . '&nbsp;';
        print '</td></tr>';
        print '<tr><td>' . $langs->trans("IMAP Login") . '</td><td class="valeur" colspan="3">';
        print $fuser->imap_login . '&nbsp;';
        print '</td></tr>';
        print '<tr><td>' . $langs->trans("IMAP Password") . '</td><td class="valeur" colspan="3">';
        print $fuser->imap_password;
        print '</td></tr>';
        print '<tr><td>' . $langs->trans("IMAP Server") . '</td><td class="valeur" colspan="3">';
        print $fuser->imap_host . '&nbsp;';
        print '</td></tr>';
        print '<tr><td>' . $langs->trans("IMAP Port") . '</td><td class="valeur" colspan="3">';
        print $fuser->imap_port . '&nbsp;';
        print '</td></tr>';
        print '<tr><td>' . $langs->trans("IMAP SSL") . '</td><td class="valeur" colspan="3">';
        echo $fuser->imap_ssl;
        print '</td></tr>';
        print '<tr><td>' . $langs->trans("IMAP SSL NOVALIDATE CERT") . '</td><td class="valeur" colspan="3">';
        echo $fuser->imap_ssl_novalidate_cert;
        print '</td></tr>';
        print "</table>";

        $i++;
    }
// }



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
    print '<div class="tabsAction">';

    if (/* ($user->rights->configuration->modifier || $user->admin) && */ $action != 'edit') {
        print "<a class=\"butAction\" href=\"?id=" . $id . "&amp;action=edit\">" . $langs->trans('Modify') . "</a>";
    }

    print "</div>";
}

$db->close();

llxFooter();
?>
