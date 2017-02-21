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
// Change this following line to use the correct relative path from htdocs (do not remove DOL_DOCUMENT_ROOT)
//require_once(DOL_DOCUMENT_ROOT."/../dev/skeleton/skeleton_class.class.php");
// require_once(DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php');
// require_once(DOL_DOCUMENT_ROOT . '/user/class/user.class.php');
// require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
// require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
// require_once(DOL_DOCUMENT_ROOT."/core/lib/agenda.lib.php");
if ($conf->projet->enabled)
    require_once(DOL_DOCUMENT_ROOT . "/core/lib/project.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/contact.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");

require_once(DOL_DOCUMENT_ROOT . "/core/lib/agenda.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");


dol_include_once('dolmessage/class/connector/dolimap.class.php');
dol_include_once('dolmessage/class/connector/dollocalmessage.class.php');
dol_include_once('dolmessage/class/user.mailconfig.class.php');
dol_include_once('dolmessage/core/lib/message.lib.php');


$id = GETPOST('id', 'int');
$action = GETPOST('action');

$langs->load("companies");
$langs->load("members");
$langs->load("bills");
$langs->load("users");
$langs->load("dolmessage@dolmessage");





$object = new Project($db);
$extrafields = new ExtraFields($db);
$object->fetch($id,$ref);
if ($object->id > 0)
{
	$object->fetch_thirdparty();
}



$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST("page", 'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield)
    $sortfield = "row_id";
if (!$sortorder)
    $sortorder = "ASC";

$limit = $conf->liste_limit;



$dolimap = new dolimap($db, $user);
$dolimap->SetUser($user->id);

$dolimap->Open();
$mbox = $dolimap->GetImap();

/* * *************************************************************************** */
/* Affichage fiche                                                            */
/* * *************************************************************************** */

llxHeader();


$head = project_prepare_head($object);
$title = $langs->trans("User");
dol_fiche_head($head, 'TabMessage2', $title, 0, 'user');

if ($msg)
    print '<div class="error">' . $msg . '</div>';

$sql = "SELECT m.row_id, message_id, message_uid, m.datec, m.recent, m.unseen, m.flagged, m.answered, m.joint , fk_source as socid, user_id , ua.login as loginauthor  ";
$sql.= " FROM " . MAIN_DB_PREFIX . "message m";
$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ua ON( m.user_id = ua.rowid) ";
$sql.= " JOIN  " . MAIN_DB_PREFIX . "element_element ee ON(targettype = 'dolmessage' AND fk_target = m.row_id AND sourcetype='project' AND fk_source ='" . $id . "' )  ";

$sql.= " WHERE  1 ";

$sql.= $db->order($sortfield, $sortorder);
$sql.= $db->plimit($limit + 1, $offset);

echo '<table width="100%" class="listingEmail">';

// Lignes des titres
print "<thead><tr class=\"liste_titre\">";
// print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref",$param,"","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Date"), $_SERVER["PHP_SELF"], "datec", $param, "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Subject"), $_SERVER["PHP_SELF"], "p.ref", $param, "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Tiers"), $_SERVER["PHP_SELF"], "p.ref", $param, "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("User"), $_SERVER["PHP_SELF"], "p.ref", $param, "", "", $sortfield, $sortorder);

print "</thead></tr>\n<tbody>";


$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);

    $i = 0;
    $var = true;
    while ($i < min($num, $limit)) {
        $objp = $db->fetch_object($resql);

        $Mess = LoadMessage($objp->row_id, $objp->message_uid, $dolimap);

        if ($i % 2 == 0)
            print '<tr class="pair">';
        else
            print '<tr class="impair">';
// 			echo '<td><a href="'.dol_buildpath('/dolmessage/info.php', 1).'?id='.$Mess->GetId().'" >'.$userstatic->getLoginUrl(1).'</a></td>';

        if (date('d/m/Y') == date("d/m/Y", strtotime($objp->datec))) { /* only hours if day is the same */
            $shortDate = date("H:i", strtotime($objp->datec));
        } elseif (date('Y') != date("Y", strtotime($objp->datec))) { /* if not this year */
            $shortDate = date("d M. Y", strtotime($objp->datec));
        } else { /* if year is implicite */
            $shortDate = date("d M.", strtotime($objp->datec));
        }
// 			echo '<td>'.$objp->row_id.'</td>';
        echo '<td class="hiddenInfo"><span>'.date("d/m/Y H:i", strtotime($objp->datec)).'</span>' . $shortDate. '</td>';
        echo '<td><a class="linkSubject" href="' . dol_buildpath('/dolmessage/fiche.php', 1) . '?id=' . $Mess->GetId() . '" >' . $Mess->GetSubject() . '</a></td>';
        echo '<td>';
        foreach ($Mess->GetLinked() as $type => $list)
            foreach ($list as $obj)
                if ($type == 'projet')
                    print $obj->getNomUrl(1, '', 16);
        echo '</td>';

        $userstatic = new User($db);
        $userstatic->id = $objp->user_id;
        $userstatic->login = $objp->loginauthor;

        echo '<td>' . $userstatic->getLoginUrl(1) . '</td>';

        echo '</tr>';
        $i++;
    }
}

$db->close();

llxFooter();
?></tbody></table>
