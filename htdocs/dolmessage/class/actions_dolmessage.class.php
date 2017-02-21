<?php

class ActionsDolMessage {

    /** Overloading the doActions function : replacing the parent's function with the one below 
     *  @param      parameters  meta datas of the hook (context, etc...) 
     *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
     *  @param      action             current action (if set). Generally create or edit or null 
     *  @return       void 
     */
    function doActions($parameters, &$object, &$action, $hookmanager) {
        global $langs, $db;
        
        if ($action == "send") {
            switch ($parameters['context']) {
                case 'propalcard':

                    if (!$_POST['addfile'] && !$_POST['removedfile'] && !$_POST['cancel']) {
                        $langs->load('mails');

                        $result = $object->fetch($_POST["id"]);
                        $result = $object->fetch_thirdparty();

                        if ($result > 0) {
                            if ($_POST['sendto']) {
                                // Le destinataire a ete fourni via le champ libre
                                $sendto = $_POST['sendto'];
                                $sendtoid = 0;
                            } elseif ($_POST['receiver'] != '-1') {
                                // Recipient was provided from combo list
                                if ($_POST['receiver'] == 'thirdparty') { // Id of third party
                                    $sendto = $object->client->email;
                                    $sendtoid = 0;
                                } else { // Id du contact
                                    $sendto = $object->client->contact_get_property($_POST['receiver'], 'email');
                                    $sendtoid = $_POST['receiver'];
                                }
                            }

                            if (dol_strlen($sendto)) {
                                $langs->load("commercial");

                                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] . '>';
                                $replyto = $_POST['replytoname'] . ' <' . $_POST['replytomail'] . '>';
                                $message = $_POST['message'];
                                $sendtocc = $_POST['sendtocc'];
                                $deliveryreceipt = $_POST['deliveryreceipt'];

                                if ($_POST['action'] == 'send') {
                                    if (dol_strlen($_POST['subject']))
                                        $subject = $_POST['subject'];
                                    else
                                        $subject = $langs->transnoentities('Propal') . ' ' . $object->ref;
                                    $actiontypecode = 'AC_PROP';
                                    $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto . ".\n";
                                    if ($message) {
                                        $actionmsg.=$langs->transnoentities('MailTopic') . ": " . $subject . "\n";
                                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody') . ":\n";
                                        $actionmsg.=$message;
                                    }
                                    $actionmsg2 = $langs->transnoentities('Action' . $actiontypecode);
                                }

                                // Create form object
                                include_once(DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php');
                                $formmail = new FormMail($db);

                                $attachedfiles = $formmail->get_attached_files();
                                $filepath = $attachedfiles['paths'];
                                $filename = $attachedfiles['names'];
                                $mimetype = $attachedfiles['mimes'];

                                // Envoi de la propal
                                require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
                                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt);

// print_r($attachedfiles); 
// exit; 

// $envelope["from"]= $from;
// $envelope["to"]  = $sendto;
// $envelope["cc"]  = $sendtocc;
// 
// $part1["type"] = TYPEMULTIPART;
// $part1["subtype"] = "mixed";
// 
// // $filename = "/tmp/imap.c.gz";
// // $fp = fopen($filename, "r");
// // $contents = fread($fp, filesize($filename));
// // fclose($fp);
// 
// $part2["type"] = TYPEAPPLICATION;
// $part2["encoding"] = ENCBINARY;
// $part2["subtype"] = "octet-stream";
// // $part2["description"] = basename($filename);
// $part2["contents.data"] = $message;
// 
// $part3["type"] = TYPETEXT;
// $part3["subtype"] = "plain";
// $part3["description"] = "description3";
// $part3["contents.data"] = "contents.data3\n\n\n\t";
// 
// $body[1] = $part1;
// $body[2] = $part2;
// $body[3] = $part3;
// 
// $final=imap_mail_compose($envelope, $body);
// 
// echo nl2br($final); 
// // imap_mail(string $to , string $subject , string $message [, string $additional_headers = NULL [, string $cc = NULL [, string $bcc = NULL [, string $rpath = NULL ]]]])
// // imap_mail($sendto , $subject ,$final ,/* string $additional_headers =*/ NULL , $sendtocc/*, string $bcc = NULL [, string $rpath = NULL ]]]]*/)
// // echo 'toto'; 
// dol_include_once('/dolmessage/class/connector/dolimap.class.php');
// global $user; 
// $dolimap = new dolimap($db, $user);
// // // if(empty($identifiid)) 
// 
// echo 'ici toto';
// 
// 	$dolimap->SetUser($user->id, 1);
// // elseif(!empty($identifiid)) 
// // 	$dolimap->SetUserGroup(8, $number);
// // 
// 
// echo 'toto'; 
// 
// 	var_dump( $dolimap->Send($sendto,$subject,$final,$sendtocc ) ); 
// 	echo 'toto'; 
// die(' here' ); 
// exit; 
                                
                                if (!$mailfile->error) {
                                 print('<script type="text/javascript">alert("Copier le mail dans le dossier imap Sent Item");</script>');                                       
                                }
                            }
                         }
                    }
                    break;
                case 'ordercard':

                    if (!$_POST['addfile'] && !$_POST['removedfile'] && !$_POST['cancel']) {
                        $langs->load('mails');

                        $result = $object->fetch($_POST["id"]);
                        $result = $object->fetch_thirdparty();

                        if ($result > 0) {
                            if ($_POST['sendto']) {
                                // Le destinataire a ete fourni via le champ libre
                                $sendto = $_POST['sendto'];
                                $sendtoid = 0;
                            } elseif ($_POST['receiver'] != '-1') {
                                // Recipient was provided from combo list
                                if ($_POST['receiver'] == 'thirdparty') { // Id of third party
                                    $sendto = $object->client->email;
                                    $sendtoid = 0;
                                } else { // Id du contact
                                    $sendto = $object->client->contact_get_property($_POST['receiver'], 'email');
                                    $sendtoid = $_POST['receiver'];
                                }
                            }

                            if (dol_strlen($sendto)) {
                                $langs->load("commercial");

                                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] . '>';
                                $replyto = $_POST['replytoname'] . ' <' . $_POST['replytomail'] . '>';
                                $message = $_POST['message'];
                                $sendtocc = $_POST['sendtocc'];
                                $deliveryreceipt = $_POST['deliveryreceipt'];

                                if ($_POST['action'] == 'send') {
                                    if (dol_strlen($_POST['subject']))
                                        $subject = $_POST['subject'];
                                    else
                                        $subject = $langs->transnoentities('Propal') . ' ' . $object->ref;
                                    $actiontypecode = 'AC_PROP';
                                    $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto . ".\n";
                                    if ($message) {
                                        $actionmsg.=$langs->transnoentities('MailTopic') . ": " . $subject . "\n";
                                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody') . ":\n";
                                        $actionmsg.=$message;
                                    }
                                    $actionmsg2 = $langs->transnoentities('Action' . $actiontypecode);
                                }

                                // Create form object
                                include_once(DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php');
                                $formmail = new FormMail($db);

                                $attachedfiles = $formmail->get_attached_files();
                                $filepath = $attachedfiles['paths'];
                                $filename = $attachedfiles['names'];
                                $mimetype = $attachedfiles['mimes'];

                                // Envoi de la propal
                                require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
                                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt);
                                
                                if (!$mailfile->error) {
                                 print('<script type="text/javascript">alert("Copier le mail dans le dossier imap Sent Item");</script>');                                       
                                }
                            }
                         }
                    }
                    break;
                case 'invoicecard':
                
// 								var_dump(__file__);
//                 exit;
                
                    if (!$_POST['addfile'] && !$_POST['removedfile'] && !$_POST['cancel']) {
                        $langs->load('mails');

                        $result = $object->fetch($_POST["id"]);
                        $result = $object->fetch_thirdparty();

                        if ($result > 0) {
                            if ($_POST['sendto']) {
                                // Le destinataire a ete fourni via le champ libre
                                $sendto = $_POST['sendto'];
                                $sendtoid = 0;
                            } elseif ($_POST['receiver'] != '-1') {
                                // Recipient was provided from combo list
                                if ($_POST['receiver'] == 'thirdparty') { // Id of third party
                                    $sendto = $object->client->email;
                                    $sendtoid = 0;
                                } else { // Id du contact
                                    $sendto = $object->client->contact_get_property($_POST['receiver'], 'email');
                                    $sendtoid = $_POST['receiver'];
                                }
                            }

                            if (dol_strlen($sendto)) {
                                $langs->load("commercial");

                                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] . '>';
                                $replyto = $_POST['replytoname'] . ' <' . $_POST['replytomail'] . '>';
                                $message = $_POST['message'];
                                $sendtocc = $_POST['sendtocc'];
                                $deliveryreceipt = $_POST['deliveryreceipt'];

                                if ($_POST['action'] == 'send') {
                                    if (dol_strlen($_POST['subject']))
                                        $subject = $_POST['subject'];
                                    else
                                        $subject = $langs->transnoentities('Propal') . ' ' . $object->ref;
                                    $actiontypecode = 'AC_PROP';
                                    $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto . ".\n";
                                    if ($message) {
                                        $actionmsg.=$langs->transnoentities('MailTopic') . ": " . $subject . "\n";
                                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody') . ":\n";
                                        $actionmsg.=$message;
                                    }
                                    $actionmsg2 = $langs->transnoentities('Action' . $actiontypecode);
                                }

                                // Create form object
                                include_once(DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php');
                                $formmail = new FormMail($db);

                                $attachedfiles = $formmail->get_attached_files();
                                $filepath = $attachedfiles['paths'];
                                $filename = $attachedfiles['names'];
                                $mimetype = $attachedfiles['mimes'];

                                // Envoi de la propal
//                                 require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
																dol_include_once('/dolmessage/class/dolCMailFile.class.php');
                                $mailfile = new dolCMailFile($object->socid, $subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt);

//                                 print_r($object);
                                exit;
//                                 if (!$mailfile->error) {
//                                  print('<script type="text/javascript">alert("Copier le mail dans le dossier imap Sent Item");</script>');                                       
//                                 }
                            }
                         }
                    }
                    break;
                case 'ordersuppliercard':
                    if (!$_POST['addfile'] && !$_POST['removedfile'] && !$_POST['cancel']) {
                        $langs->load('mails');

                        $result = $object->fetch($_POST["id"]);
                        $result = $object->fetch_thirdparty();

                        if ($result > 0) {
                            if ($_POST['sendto']) {
                                // Le destinataire a ete fourni via le champ libre
                                $sendto = $_POST['sendto'];
                                $sendtoid = 0;
                            } elseif ($_POST['receiver'] != '-1') {
                                // Recipient was provided from combo list
                                if ($_POST['receiver'] == 'thirdparty') { // Id of third party
                                    $sendto = $object->client->email;
                                    $sendtoid = 0;
                                } else { // Id du contact
                                    $sendto = $object->client->contact_get_property($_POST['receiver'], 'email');
                                    $sendtoid = $_POST['receiver'];
                                }
                            }

                            if (dol_strlen($sendto)) {
                                $langs->load("commercial");

                                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] . '>';
                                $replyto = $_POST['replytoname'] . ' <' . $_POST['replytomail'] . '>';
                                $message = $_POST['message'];
                                $sendtocc = $_POST['sendtocc'];
                                $deliveryreceipt = $_POST['deliveryreceipt'];

                                if ($_POST['action'] == 'send') {
                                    if (dol_strlen($_POST['subject']))
                                        $subject = $_POST['subject'];
                                    else
                                        $subject = $langs->transnoentities('Propal') . ' ' . $object->ref;
                                    $actiontypecode = 'AC_PROP';
                                    $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto . ".\n";
                                    if ($message) {
                                        $actionmsg.=$langs->transnoentities('MailTopic') . ": " . $subject . "\n";
                                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody') . ":\n";
                                        $actionmsg.=$message;
                                    }
                                    $actionmsg2 = $langs->transnoentities('Action' . $actiontypecode);
                                }

                                // Create form object
                                include_once(DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php');
                                $formmail = new FormMail($db);

                                $attachedfiles = $formmail->get_attached_files();
                                $filepath = $attachedfiles['paths'];
                                $filename = $attachedfiles['names'];
                                $mimetype = $attachedfiles['mimes'];

                                // Envoi de la propal
                                require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
                                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt);
                                
                                if (!$mailfile->error) {
                                 print('<script type="text/javascript">alert("Copier le mail dans le dossier imap Sent Item");</script>');                                       
                                }
                            }
                         }
                    }
                    break;
                case 'invoicesuppliercard':
                    if (!$_POST['addfile'] && !$_POST['removedfile'] && !$_POST['cancel']) {
                        $langs->load('mails');

                        $result = $object->fetch($_POST["id"]);
                        $result = $object->fetch_thirdparty();

                        if ($result > 0) {
                            if ($_POST['sendto']) {
                                // Le destinataire a ete fourni via le champ libre
                                $sendto = $_POST['sendto'];
                                $sendtoid = 0;
                            } elseif ($_POST['receiver'] != '-1') {
                                // Recipient was provided from combo list
                                if ($_POST['receiver'] == 'thirdparty') { // Id of third party
                                    $sendto = $object->client->email;
                                    $sendtoid = 0;
                                } else { // Id du contact
                                    $sendto = $object->client->contact_get_property($_POST['receiver'], 'email');
                                    $sendtoid = $_POST['receiver'];
                                }
                            }

                            if (dol_strlen($sendto)) {
                                $langs->load("commercial");

                                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] . '>';
                                $replyto = $_POST['replytoname'] . ' <' . $_POST['replytomail'] . '>';
                                $message = $_POST['message'];
                                $sendtocc = $_POST['sendtocc'];
                                $deliveryreceipt = $_POST['deliveryreceipt'];

                                if ($_POST['action'] == 'send') {
                                    if (dol_strlen($_POST['subject']))
                                        $subject = $_POST['subject'];
                                    else
                                        $subject = $langs->transnoentities('Propal') . ' ' . $object->ref;
                                    $actiontypecode = 'AC_PROP';
                                    $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto . ".\n";
                                    if ($message) {
                                        $actionmsg.=$langs->transnoentities('MailTopic') . ": " . $subject . "\n";
                                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody') . ":\n";
                                        $actionmsg.=$message;
                                    }
                                    $actionmsg2 = $langs->transnoentities('Action' . $actiontypecode);
                                }

                                // Create form object
                                include_once(DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php');
                                $formmail = new FormMail($db);

                                $attachedfiles = $formmail->get_attached_files();
                                $filepath = $attachedfiles['paths'];
                                $filename = $attachedfiles['names'];
                                $mimetype = $attachedfiles['mimes'];

                                // Envoi de la propal
                                require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
                                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt);
                                
                                if (!$mailfile->error) {
                                 print('<script type="text/javascript">alert("Copier le mail dans le dossier imap Sent Item");</script>');                                       
                                }
                            }
                         }
                    }
                    break;
            }
        }
    }

}

?>