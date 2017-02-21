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



dol_include_once('dolmessage/class/connector/dolimap.class.php');
dol_include_once('dolmessage/class/connector/dollocalmessage.class.php');
dol_include_once('dolmessage/core/lib/message.lib.php');




$langs->load("companies");
$langs->load("other");
$langs->load("dolmessage@dolmessage");

// Get parameters
$id = GETPOST('id', 'int');
$uid = GETPOST('uid', 'int');
$action = GETPOST('action', 'alpha');
$folder = urldecode(GETPOST('folder', 'alpha'));
$number = GETPOST('number', 'int');
$identifiid = GETPOST('identifiid', 'alpha');
// Protection if external user
if ($user->societe_id > 0) {
    //accessforbidden();
}

$dolimap = new dolimap($db, $user);
// $dolimap->SetUser($user->id, $number);
if(empty($identifiid)) 
	$dolimap->SetUser($user->id, $number);
elseif(!empty($identifiid)) 
	$dolimap->SetUserGroup($identifiid, $number);
$form = new Form($db);

$dolmessage = new dolmessage($db ); 

switch ($action) {

    case 'linkattach':
        $dolimap->Open();
        $mbox = $dolimap->GetImap();

        if (FALSE === $mbox) {
            foreach ($dolimap->ListErrors() as $row)
                $err .= $row;
        } else {
            $Message = LoadMessage($id, $uid, $dolimap);
        }

        switch (GETPOST('reference_type_element', 'alpha')) {
//             case 'order_supplier':
//                 $class = 'CommandeFournisseur';
// 
//                 require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_order/modules_commandefournisseur.php';
//                 require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
// 
//                 $reference_rowid = GETPOST('reference_rowid', 'int');
// 
//                 $obj = new $class($db);
// 
//                 $obj->fetch($reference_rowid);
// 
//                 $comfournref = dol_sanitizeFileName($obj->ref);
//                 $relativepath = $comfournref . '/' . $comfournref . '.pdf';
//                 $filedir = $conf->fournisseur->dir_output . '/commande/' . $comfournref;
//                 break;
// 
//             case 'invoice_supplier':
//                 $class = 'FactureFournisseur';
// 
//                 require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
//                 require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
// 
//                 $reference_rowid = GETPOST('reference_rowid', 'int');
// 
//                 $obj = new $class($db);
// 
//                 $obj->fetch($reference_rowid);
// 
//                 $comfournref = dol_sanitizeFileName($obj->ref);
//                 $relativepath = $comfournref . '/' . $comfournref . '.pdf';
//                 $filedir = $conf->fournisseur->dir_output . '/commande/' . $comfournref;
//                 break;

            case 'societe':
                $societe = new Societe($db);
                $societe->fetch(GETPOST('reference_fk_socid', 'int'));

                $filedir = $conf->societe->multidir_output[$societe->entity] . "/" . $societe->id;
                break;

            case 'contact':

                require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';


                $contact = new Contact($db);
                $contact->fetch(GETPOST('reference_fk_socid', 'int'));

                $filedir = $conf->contact->multidir_output[$contact->entity] . "/" . $contact->id;
                break;
        }


        $i = 1;
        foreach ($Message->GetAttach() as $att_name => $obj) {
            if (GETPOST('reference_attach_num', 'int') == $i) {
                $clean = dol_sanitizeFileName($att_name);
                $f = fopen($filedir . $clean, 'a');

                $localmail = fopen($filedir . '/' . $clean, "w");
                fwrite($localmail, $obj->GetData());
                fclose($localmail);
            }
            $i++;
        }

        $dolmessage->id = $id; 
        ;
//         Header("location: " . dol_buildpath('/dolmessage/info.php', 1) . '?id=' . $id . '&uid=' . $uid);
        Header("location: " . $dolmessage->getNomUrl(0,'',0,'&uid=' . $uid.'&identifiid='.GETPOST('identifiid') ) );
        exit;
		break;

    case 'confirm_delete':
			if( (int)$uid > 0 ) {
				$dolimap->Open();
				$mbox = $dolimap->GetImap();
				
				if ($mbox === false) {
					foreach ($dolimap->ListErrors() as $row)
						$err .= $row;
				}
			} 

			$Message = LoadMessage($id, $uid, $dolimap);

			if(get_class($Message) == 'dollocalmessage'){
					$r = $Message->delete(GETPOST('identifiid'),$number,$Message);
			}
			elseif(get_class($Message) == 'dolimapmessage') {
							$r = $Message->delete($Message,$number, $dolimap);
			}

			Header("location: " . dol_buildpath('/dolmessage/index.php', 1) . '?number=' . GETPOST('number').'&identifiid='.GETPOST('identifiid'));
			exit;
		break;

    default:
        $dolimap->Open($folder);
        $mbox = $dolimap->GetImap();

        if ($mbox === false) {
            foreach ($dolimap->ListErrors() as $row)
                $err .= $row;
        } else {

            $menus = $dolimap->ListFolder();
            sort($menus);
            $Message = LoadMessage($id, $uid, $dolimap);
        }
        

}


/* * *************************************************
 * VIEW
 *
 * Put here all code to build page
 * ************************************************** */

llxHeader('', 'Dolibarr Webmail', '');



// Normal display 
dol_fiche_head(message_prepare_head(), 'info', $langs->trans("Webmail"), 0, 'mailbox@dolmessage');

dol_include_once('/dolmessage/tpl/info.display.tpl');

// End of page
llxFooter();
$db->close();
?>