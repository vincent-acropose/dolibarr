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
require_once(DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php');
require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');

dol_include_once('dolmessage/class/connector/dolimap.class.php');
dol_include_once('dolmessage/class/connector/dollocalmessage.class.php');

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

//send email
$action = GETPOST('action', 'aZ', 2);

// if ($action == 'send') {
switch ($action) {

// 	break 
	
	case 'send':
    $error = 0;

    $email_from = GETPOST('from', 'alpha', 2);
    $errors_to = $email_from;
    $sendto = GETPOST('to', 'alpha', 2);
    $sendtocc = GETPOST('cc', 'alpha', 2);
    $sendtoccc = GETPOST('cci', 'alpha', 2);
    $subject = GETPOST('subject', '', 2);
    $body = GETPOST('bodyMessage', '', 2);
    $deliveryreceipt = false;
    //Check if we have to decode HTML
    if (!empty($conf->global->FCKEDITOR_ENABLE_MAILING) && dol_textishtml(dol_html_entity_decode($body, ENT_COMPAT | ENT_HTML401))) {
        $body = dol_html_entity_decode($body, ENT_COMPAT | ENT_HTML401);
    }

    // Create form object
    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
    $formmail = new FormMail($db);

    $attachedfiles = $formmail->get_attached_files();
    $filepath = $attachedfiles['paths'];
    $filename = $attachedfiles['names'];
    $mimetype = $attachedfiles['mimes'];

    if (empty($email_from)) {
        setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("DolimaillSender")), 'errors');
        $action = 'test';
        $error++;
    }
    if (empty($sendto)) {
        setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("DolimaillRecipiant")), 'errors');
        $action = 'test';
        $error++;
    }
    if (!$error) {
        // Le message est-il en html
        $msgishtml = 1; // Message is Forced to HTML




        $usersignature = $user->signature;
        // For action = test or send, we ensure that content is not html, even for signature, because this we want a test with NO html.
        if ($action == 'test' || $action == 'send') {
            $usersignature = dol_string_nohtmltag($usersignature);
        }

        $substitutionarrayfortest = array(
            '__LOGIN__' => $user->login,
            '__ID__' => 'TESTIdRecord',
            '__EMAIL__' => 'TESTEMail',
            '__LASTNAME__' => 'TESTLastname',
            '__FIRSTNAME__' => 'TESTFirstname',
            '__SIGNATURE__' => (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? $usersignature : ''),
                //'__PERSONALIZED__' => 'TESTPersonalized'	// Hiden because not used yet
        );
        complete_substitutions_array($substitutionarrayfortest, $langs);


        // Pratique les substitutions sur le sujet et message
        $subject = make_substitutions($subject, $substitutionarrayfortest);
        $body = make_substitutions($body, $substitutionarrayfortest);

        $mailfile = new CMailFile(
                $subject, $sendto, $email_from, $body, $filepath, $mimetype, $filename, $sendtocc, $sendtoccc, $deliveryreceipt, $msgishtml, $errors_to
        );

        $result = $mailfile->sendfile();

        if ($result) {
            setEventMessage($langs->trans("MailSuccessfulySent", $mailfile->getValidAddress($email_from, 2), $mailfile->getValidAddress($sendto, 2)));
            Header("location: " . dol_buildpath('/dolmessage/synchro.php', 1) . '?number=' . GETPOST('number') . '&folder=INBOX&num_page=' . GETPOST('num_page'));
        } else {
            setEventMessage($langs->trans("ResultKo") . '<br>' . $mailfile->error . ' ' . $result, 'errors');
        }

        $action = '';
    }
    break; 
    
    
    case 'reply': 
    break ; 
    
    default: 
}

/* * *************************************************
 * VIEW
 *
 * Put here all code to build page
 * ************************************************** */


llxHeader('', iconv(iconv_get_encoding($langs->trans($lbl_folder)), $character_set_client . "//TRANSLIT", $langs->trans($lbl_folder)) . ' (' . $info->Nmsgs . ') ', '');

dol_fiche_head(message_prepare_head(), 'dashboard', $langs->trans("Webmail"), 0, 'mailbox@dolmessage');


//// Send mail
//
//$mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1);


dol_include_once('/dolmessage/tpl/compose.tpl');


// End of page
llxFooter();
$db->close();
?>