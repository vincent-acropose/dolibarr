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
// Change this following line to use the correct relative path (../, ../../, etc)
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




dol_include_once('/dolmessage/class/connector/dolimap.class.php');
dol_include_once('dolmessage/class/connector/dollocalmessage.class.php');

dol_include_once('/dolmessage/class/actioncomm.dolmessage.class.php');


dol_include_once('/dolmessage/core/lib/message.lib.php');


// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("dolmessage@dolmessage");
$langs->load("agenda");

global $conf;
if ($conf->global->PAGINATION_WEBMAIL)
    $pagination = $conf->global->PAGINATION_WEBMAIL;
else
    $pagination = 50;

if (empty($_GET['num_page']))
    $_GET['num_page'] = 1;

// set default folder 
if (GETPOST('folder') == '') {
    Header("location: " . dol_buildpath('/dolmessage/synchro.php', 1) . '?number=' . GETPOST('number') . '&folder=INBOX&num_page=' . GETPOST('num_page') . '&identifiid=' . GETPOST('identifiid'));
}


// Get parameters
$id = GETPOST('id', 'int');
$uid = GETPOST('uid', 'int');
$number = GETPOST('number', 'int');
$action = GETPOST('action', 'alpha');
$folder = urldecode(GETPOST('folder', 'alpha'));
$identifiid = GETPOST('identifiid', 'alpha');


// Protection if external user
if ($user->societe_id > 0) {
    //accessforbidden();
}



$dolimap = new dolimap($db, $user);
if (empty($identifiid))
    $dolimap->SetUser($user->id, $number);
elseif (!empty($identifiid))
    $dolimap->SetUserGroup($identifiid, $number);


$form = new Form($db);


$message = '';


switch ($action) {

    default :


        if (GETPOST('reference_mail_uid') && GETPOST('reference_rowid') && GETPOST('reference_type_element')) {

            $type_element = GETPOST('reference_type_element');
            $identifiid = 0;
            if (GETPOST('identifiid') > 0)
                $identifiid = GETPOST('identifiid');

// 			$number = 0; 
//             if(GETPOST('identifiid') > 0 )
// 				$identifiid = GETPOST('identifiid');


            $uid = GETPOST('reference_mail_uid');
            $linkedobject = array();
            $dolimap->Open(GETPOST('folder'));
            $mbox = $dolimap->GetImap();



            if (FALSE === $mbox) {
                foreach ($dolimap->ListErrors() as $row)
                    $err .= $row;
            } else {
                $Message = $dolimap->GetMessage($uid);
            }

//             $header = $Message->GetHeader();
            $message_id = $Message->GetMessageId();

            if (GETPOST('reference_fk_socid', 'int') > 0) {
                $societe = new Societe($db);
                $societe->fetch(GETPOST('reference_fk_socid', 'int'));


                $upload_dir = $conf->societe->multidir_output[$societe->entity] . "/" . $societe->id;


                if (!file_exists($upload_dir))
                    dol_mkdir($upload_dir);


                $upload_dir .= '/message/';

                if (!file_exists($upload_dir))
                    dol_mkdir($upload_dir);


                $linkedobject['societe'] = $societe->id;
            }


            $DolMessage = $Message->CopyMessage($db, $mbox, $uid, $message_id, $upload_dir, $linkedobject, $number, $identifiid);




            if (!empty($type_element)) {
                $upload_dir = $conf->$type_element->dir_output . "/" . GETPOST('reference');
                if ($type_element != 'societe') {
                    // rattrapage nom
                    $type_element = ($type_element == 'projet') ? 'project' : $type_element;
                    $db->query("INSERT INTO `" . MAIN_DB_PREFIX . "element_element` ( `fk_source`, `sourcetype`, `fk_target`, `targettype`) VALUES ( '" . GETPOST('reference_rowid') . "', '$type_element', '" . $DolMessage->id . "', 'dolmessage')");
                }
                if (!file_exists($upload_dir))
                    dol_mkdir($upload_dir);


                $upload_dir .= '/message/';
                if (!file_exists($upload_dir))
                    dol_mkdir($upload_dir);
                $Message->CopyFile($mbox, $uid, $message_id, $upload_dir);
            }



            $ActionCommDolMessage = new ActionCommDolMessage($db);
            $ActionCommDolMessage->SetEvent($user, $societe, $DolMessage, $Message);

            Header("location: " . dol_buildpath('/dolmessage/synchro.php', 1) . '?number=' . GETPOST('number') . '&folder=' . GETPOST('folder') . '&num_page=' . GETPOST('num_page') . '&identifiid=' . GETPOST('identifiid'));
            exit;
        } else {

            $mbox = FALSE;
            if ($folder != '{:}')
                $dolimap->Open($folder);
            $mbox = $dolimap->GetImap();

            if ($mbox === FALSE) {
                $info = FALSE;
                foreach ($dolimap->ListErrors() as $row)
                    $message .= $row . "<br>";
            } else {
                $info = $dolimap->Check();
                if (FALSE !== $info) {

                    $mails = $dolimap->ListMessage(GETPOST('num_page'), $pagination, $identifiid);

                    $menus = $dolimap->ListFolder();

                    sort($menus);
                } else {
                    foreach ($dolimap->ListErrors() as $row)
                        $message .= $row . "<br>";
                }
            }

            $lbl_folder = array_reverse(explode('/', $folder));
            $lbl_folder = str_replace($user->mailbox_imap_ref, '', str_replace('INBOX.', '', $lbl_folder[0]));
        }
}



/* * *************************************************
 * VIEW
 *
 * Put here all code to build page
 * ************************************************** */

llxHeader('', iconv(iconv_get_encoding($langs->trans($lbl_folder)), $character_set_client . "//TRANSLIT", $langs->trans(preg_replace('/\\{.*\\}/', '', utf8_encode($lbl_folder)))) . ' (' . $info->Nmsgs . ') ', '');


dol_fiche_head(message_prepare_head(), 'synchro', $langs->trans("Webmail"), 0, 'mailbox@dolmessage');

if ($message) {
    print $message . "<br>";
}

dol_include_once('/dolmessage/tpl/synchro.list.tpl');


$dolimap->Close();
// End of page
llxFooter();

$db->close();
?>