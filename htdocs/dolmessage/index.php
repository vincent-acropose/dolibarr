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

require_once(DOL_DOCUMENT_ROOT . "/core/lib/agenda.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");


dol_include_once('/user/class/usergroup.class.php');


dol_include_once('dolmessage/class/connector/dolimap.class.php');
dol_include_once('dolmessage/class/connector/dollocalmessage.class.php');
dol_include_once('dolmessage/class/dolmessage.class.php');

dol_include_once('dolmessage/core/lib/message.lib.php');


// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("dolimail@dolimail");
$langs->load("agenda");


if (!function_exists('imap_timeout')) {
    print '<center class="alert" style="color:red"> les fonctions imap (php-imap) sont requise pour ce module </center>';
    exit;
}

error_reporting(E_ALL); 



global $conf;
if (isset($conf->global->PAGINATION_WEBMAIL) && $conf->global->PAGINATION_WEBMAIL)
    $pagination = $conf->global->PAGINATION_WEBMAIL;
else
    $pagination = 50;

if (empty($_GET['num_page']))
    $_GET['num_page'] = 1;

// Get parameters
$id = GETPOST('id', 'int');
$number = GETPOST('number', 'int');
$action = GETPOST('action', 'alpha');
$folder = urldecode(GETPOST('folder', 'alpha'));
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

// Protection if external user
if ($user->societe_id > 0) {
    //accessforbidden();
}

$dolimap = new dolimap($db, $user);
if(empty($identifiid)) 
	$dolimap->SetUser($user->id, $number);
elseif(!empty($identifiid)) 
	$dolimap->SetUserGroup($identifiid, $number);
// $dolimap->Open($folder);
// $mbox = $dolimap->GetImap();
$form = new Form($db);



$array =array(); 
$UserGroup = new UserGroup($db);
foreach($UserGroup->listGroupsForUser($user->id) as $row)
	$array[]=$row->id;


/* * *************************************************
 * VIEW
 *
 * Put here all code to build page
 * ************************************************** */

// llxHeader('', iconv(iconv_get_encoding($langs->trans($lbl_folder)), $character_set_client . "//TRANSLIT", $langs->trans($lbl_folder)) . ' (' . $info->Nmsgs . ') ', '');
llxHeader('', 'Dolibarr Webmail', '');
dol_fiche_head(message_prepare_head(), 'dashboard', $langs->trans("Webmail"), 0, 'mailbox@dolmessage');





$sql = "SELECT row_id, number,  message_id, message_uid, datec, recent, unseen, flagged, answered, joint  ";
$sql.= " FROM " . MAIN_DB_PREFIX . "message";
$sql.= " WHERE  1 ";
$sql.= " AND (user_id = '" . $user->id . "' ";
$sql.= " OR usergroup_id  IN( ".implode(',',$array)." ) ) ";
$sql.= "  AND entity IN (" . getEntity('dolmessage', 1) . ")";
$sql.= $db->order($sortfield, $sortorder);
$sql.= $db->plimit($limit + 1, $offset);

// echo $sql; 
echo '<table width="100%" class="listingEmail">';

// Lignes des titres
$param=''; 
print "<thead><tr class=\"liste_titre\">";
// print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref",$param,"","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Date"), $_SERVER["PHP_SELF"], "datec", $param, "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Subject"), $_SERVER["PHP_SELF"], "p.ref", $param, "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Tiers"), $_SERVER["PHP_SELF"], "p.ref", $param, "", "", $sortfield, $sortorder);

print "</tr></thead><tbody>\n";


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

        echo '<td class="hiddenInfo"><span>'.date("d/m/Y H:i", strtotime($Mess->GetDate())).'</span>' . $Mess->GetDate(true). '</td>';
        
        
        $dolmessage = new dolmessage($db);
        $dolmessage->id = $Mess->GetId()  ; 
        $dolmessage->message_id = $Mess->GetSubject();
        
        echo '<td>'.$dolmessage->getNomUrl(0, '',0, 'number=' . $objp->number ).'</td>';
        echo '<td>';
        foreach ($Mess->GetLinked() as $type => $list)
            foreach ($list as $obj)
                if ($type == 'societe')
                    print $obj->getNomUrl(1, '', 16);
        echo '</td></tr>';
        $i++;
    }
}

// End of page
llxFooter();
$db->close();
?></tbody></table>