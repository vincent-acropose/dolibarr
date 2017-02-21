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
$res = false;
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



dol_include_once('/dolmessage/class/connector/dolimap.class.php');
dol_include_once('dolmessage/class/connector/dollocalmessage.class.php');
dol_include_once('/dolmessage/class/user.mailconfig.class.php');
dol_include_once('/dolmessage/core/lib/message.lib.php');





$langs->load("companies");
$langs->load("other");
$langs->load("dolimail@dolimail");

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
if (empty($identifiid))
    $dolimap->SetUser($user->id, $number);
elseif (!empty($identifiid))
    $dolimap->SetUserGroup($identifiid, $number);

// $dolimap->Open();
// $mbox = $dolimap->GetImap();



/* * *************************************************
 * VIEW
 *
 * Put here all code to build page
 * ************************************************** */
// top_httphead();

switch ($action) {
    case 'imap':


        $dolimap->Open($folder);
        $mbox = $dolimap->GetImap();

        if (FALSE === $mbox) {
            foreach ($dolimap->ListErrors() as $row)
                $err .= $row;
        } else {
            $Message = $dolimap->GetMessage(GETPOST('uid', 'int'));
        }

        dol_include_once('/dolmessage/tpl/info.message.tpl');

        exit;
        break;

    case 'local':

        $id = GETPOST('id', 'int');


//         $dolimap = new dolimap($db, $user);
//         $dolimap->SetUser($user->id, $number);
//         $dolimap->Open();
//         $mbox = $dolimap->GetImap();

        $DolMsg = new dolmessage($db, $user);
        $DolMsg->fetch($id, '', true, $identifiid);

        $uid = $DolMsg->uid;

        $DolMsg->fetchObjectLinked($DolMsg->id, $DolMsg->element, $DolMsg->id, $DolMsg->element, 'OR');

        foreach ($DolMsg->linkedObjects as $type => $list) {
            foreach ($list as $obj) {
                if ($type == 'societe') {

                    $societe = $obj;

                    $upload_dir = $conf->societe->multidir_output[$societe->entity] . "/" . $societe->id;

                    if (!file_exists($upload_dir))
                        dol_mkdir($upload_dir);

                    $upload_dir .= '/message/';
                    if (!file_exists($upload_dir))
                        dol_mkdir($upload_dir);
                }
            }
        }

        $DolLocal = new dollocalmessage($uid);
        $DolLocal->LoadLocal($upload_dir, $DolMsg->message_id);


        $Message = $DolLocal;
        $Message->SetId($DolMsg->id);

        dol_include_once('/dolmessage/tpl/info.message.tpl');

        exit;
        break;


    case 'attachment':

        if ($user->societe_id > 0) {
            //accessforbidden();
        }

        // if local message 
        if ($id > 0) {
            $DolMsg = new dolmessage($db);
            $DolMsg->fetch($id);

            $uid = $DolMsg->uid;

            $DolMsg->fetchObjectLinked($DolMsg->id, $DolMsg->element, $DolMsg->id, $DolMsg->element, 'OR');

            foreach ($DolMsg->linkedObjects as $type => $list) {
                foreach ($list as $obj) {
                    if ($type == 'societe') {
                        $societe = $obj;
                        $upload_dir = $conf->societe->multidir_output[$societe->entity] . "/" . $societe->id;

                        if (!file_exists($upload_dir))
                            dol_mkdir($upload_dir);


                        $upload_dir .= '/message/';

                        if (!file_exists($upload_dir))
                            dol_mkdir($upload_dir);
                    }
                }
            }


            $DolLocal = new dollocalmessage($uid);
            $DolLocal->LoadLocal($upload_dir, $DolMsg->message_id);

            $Message = $DolLocal;

// 			var_dump($uid);
        }
        else {
            $dolimap->Open($folder);
            $mbox = $dolimap->GetImap();

            if (FALSE === $mbox) {
                foreach ($dolimap->ListErrors() as $row)
                    $err .= $row;
            } else {
                $Message = $dolimap->GetMessage($uid);
            }
        }

        $i = 0;
        foreach ($Message->GetAttach() as $att_name => $obj) {

            if ($i == GETPOST('attach', 'int')) {

                $tabfile = explode('.', $att_name);
                $extension = $tabfile[sizeof($tabfile) - 1];
                switch ($obj->GetApplicationType()) {
                    case '2': // .eml ????

                        echo base64_decode($obj->GetData());
                        break;
                    case 'jpg':
                    case 'png':
                    case 'gif':
                        header('Content-type: image/' . $extension);
                        echo $obj->GetData();
                        break;
                    case 'pdf':
                        header('Content-Type: application/pdf');
                        //header('Content-Disposition: inline; filename="' . $att_name . '"');
                        header('Cache-Control: private, max-age=0, must-revalidate');
                        header('Pragma: public');
// 								ini_set('zlib.output_compression','0');

                        echo $obj->GetData();
                        break;
                    default:
                        header('Content-Type: application/txt');
                        //header('Content-Disposition: inline; filename="' . $att_name . '"');
                        header('Cache-Control: private, max-age=0, must-revalidate');
                        header('Pragma: public');
                        echo $obj->GetData();
                }
            }

            $i++;
        }
        exit;
        break;



    case 'attachlink':
        if (!empty($_GET['reference_' . $_GET['num_ligne']])) {

            $socid = GETPOST('socid', 'int');
            $return_arr = array();
// var_dump(__line__);
            $reference = $_GET['reference_' . $_GET['num_ligne']] ? $_GET['reference_' . $_GET['num_ligne']] : '';
            // Recherche parmis les societes
            $sql = "SELECT s.rowid as rowid, CONCAT_WS(' - ', s.code_client, s.code_fournisseur) as reference, s.nom as nom_societe, 'societe' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "societe as s";
            $sql.= " WHERE 1 ";
            if ($socid > 0)
                $sql .= " AND s.rowid = '" . $socid . "' ";
            else
                $sql.=" AND (s.code_client LIKE '%" . $db->escape($reference) . "%' OR s.nom LIKE '%" . $db->escape($reference) . "%' OR s.code_fournisseur LIKE '%" . $db->escape($reference) . "%' OR s.email LIKE '%" . $db->escape($reference) . "%'  ) ";
            $sql .= " UNION ";
            $sql .= "SELECT p.rowid as rowid, p.name as reference, s.nom as nom_societe, 'contact' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "socpeople as p";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=p.fk_soc";
            $sql.= " WHERE ";
            $sql.=" (p.email LIKE '%" . $db->escape($reference) . "%' OR p.name LIKE '%" . $db->escape($reference) . "%' )";
            // Recherche parmis les projets
            $sql .= " UNION ";
            $sql .= "SELECT p.rowid as rowid, CONCAT_WS(' - ', p.ref, p.title) as reference, s.nom as nom_societe, 'projet' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "projet as p";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=p.fk_soc";
            if ($socid > 0)
                $sql .= " AND s.rowid = '" . $socid . "' ";
            $sql.= " WHERE ";
            $sql.=" (p.ref LIKE '%" . $db->escape($reference) . "%' OR p.title LIKE '%" . $db->escape($reference) . "%')";
            $sql .= " UNION ";
            // Recherche parmis les propales clients
            $sql .= "SELECT p.rowid as rowid, CONCAT_WS(' - ', p.ref, p.ref_client) as reference, s.nom as nom_societe, 'propal' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "propal as p";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=p.fk_soc";
            if ($socid > 0)
                $sql .= " AND s.rowid = '" . $socid . "' ";
            $sql.= " WHERE ";
            $sql.=" (p.ref LIKE '%" . $db->escape($reference) . "%' OR p.ref_client LIKE '%" . $db->escape($reference) . "%')";
            $sql .= " UNION ";
            // Recherche parmis les commandes clients
            $sql .= "SELECT c.rowid as rowid, CONCAT_WS(' - ', c.ref, c.ref_client) as reference, s.nom as nom_societe, 'order' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "commande as c";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=c.fk_soc";
            if ($socid > 0)
                $sql .= " AND s.rowid = '" . $socid . "' ";
            $sql.= " WHERE ";
            $sql.=" (c.ref LIKE '%" . $db->escape($reference) . "%' OR c.ref_client LIKE '%" . $db->escape($reference) . "%')";
            $sql .= " UNION ";
            // Recherche parmis les factures clients
            $sql .= "SELECT f.rowid as rowid, CONCAT_WS(' - ', f.facnumber, f.ref_client) as reference, s.nom as nom_societe, 'invoice' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "facture as f";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=f.fk_soc";
            if ($socid > 0)
                $sql .= " AND s.rowid = '" . $socid . "' ";
            $sql.= " WHERE ";
            $sql.=" (f.facnumber LIKE '%" . $db->escape($reference) . "%' OR f.ref_client LIKE '%" . $db->escape($reference) . "%')";
            $sql .= " UNION ";
            // Recherche parmis les commandes fournisseurs
            $sql .= "SELECT c.rowid as rowid, CONCAT_WS(' - ', c.ref, c.ref_supplier) as reference, s.nom as nom_societe, 'order_supplier' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "commande_fournisseur as c";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=c.fk_soc";
            if ($socid > 0)
                $sql .= " AND s.rowid = '" . $socid . "' ";
            $sql.= " WHERE ";
            $sql.=" (c.ref LIKE '%" . $db->escape($reference) . "%' OR c.ref_supplier LIKE '%" . $db->escape($reference) . "%')";
            $sql .= " UNION ";
            // Recherche parmis les factures fournisseurs
            $sql .= "SELECT f.rowid as rowid, f.facnumber as reference, s.nom as nom_societe, 'invoice_supplier' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=f.fk_soc";
            if ($socid > 0)
                $sql .= " AND s.rowid = '" . $socid . "' ";
            $sql.= " WHERE ";
            $sql.=" (f.facnumber LIKE '%" . $db->escape($reference) . "%')";
// 				$sql.= " ORDER BY reference, nom_societe";
// 					echo $sql ; 
// 					exit;
            $sql.= $db->plimit(50); // Avoid pb with bad criteria

            $resql = $db->query($sql);

            if ($resql) {
                while ($row = $db->fetch_array($resql)) {
// 							print_r($row);
                    if (!empty($row['fk_socid'])) {
                        $row_array['label'] = $row['type_element'] . ' ' . $row['reference'] . ' : ' . $row['nom_societe'];
                        $row_array['value'] = $row['reference'];
                        $row_array['reference_rowid_' . $_GET['num_ligne']] = $row['rowid'];
                        $row_array['reference_type_element_' . $_GET['num_ligne']] = $row['type_element'];
                        $row_array['reference_fk_socid_' . $_GET['num_ligne']] = $row['fk_socid'];

                        array_push($return_arr, $row_array);
                    } else {
                        $row_array['label'] = $row['type_element'] . ' ' . $row['reference'] . ' : ' . $row['nom_societe'];
                        $row_array['value'] = $row['reference'];
                        $row_array['reference_rowid_' . $_GET['num_ligne']] = $row['rowid'];
                        $row_array['reference_type_element_' . $_GET['num_ligne']] = $row['type_element'];
                        $row_array['reference_fk_socid_' . $_GET['num_ligne']] = $row['fk_socid'];

                        array_push($return_arr, $row_array);
                    }
                }
            }

            header('Content-Type: application/json');
            echo json_encode($return_arr);
        }

        break;


    case 'ownerlink':
// 	default:
// 

        if (!empty($_GET['reference_' . $_GET['num_ligne']])) {
            $return_arr = array();
// var_dump(__line__);
            $reference = $_GET['reference_' . $_GET['num_ligne']] ? $_GET['reference_' . $_GET['num_ligne']] : '';
            // Recherche parmis les societes
            $sql = "SELECT s.rowid as rowid, CONCAT_WS(' - ', s.code_client, s.code_fournisseur) as reference, s.nom as nom_societe, 'societe' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "societe as s";
            $sql.= " WHERE ";
            $sql.=" (s.code_client LIKE '%" . $db->escape($reference) . "%' OR s.nom LIKE '%" . $db->escape($reference) . "%' OR s.code_fournisseur LIKE '%" . $db->escape($reference) . "%' OR s.email LIKE '%" . $db->escape($reference) . "%'  )";
            $sql .= " UNION ";
            $sql .= "SELECT p.rowid as rowid, p.firstname as reference, s.nom as nom_societe, 'contact' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "socpeople as p";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=p.fk_soc";
            $sql.= " WHERE ";
            $sql.=" (p.email LIKE '%" . $db->escape($reference) . "%' OR p.firstname LIKE '%" . $db->escape($reference) . "%' )";
            
            $sql .= " UNION ";
            $sql .= "SELECT pr.rowid as rowid, pr.ref, pr.title, 'projet' as type_element, s.rowid as fk_socid ";
            $sql.= " FROM " . MAIN_DB_PREFIX . "projet as pr";
            $sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s on pr.fk_soc = s.rowid";

            $sql.= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet_customfields pcf ON (pcf.fk_projet = pr.rowid)';
            $sql.= ' LEFT JOIN cg_etapes cge ON (pcf.etapeprojet_numetape_descriptionetape = cge.rowid)';
            $sql.= " WHERE ";
            $sql.=" (pr.title LIKE '%" . $db->escape($reference) . "%' OR pr.description LIKE '%" . $db->escape($reference) . "%' )";


// 					echo $sql ; 
// 					exit;
            $sql.= $db->plimit(50); // Avoid pb with bad criteria

            $resql = $db->query($sql);

            if ($resql) {
                while ($row = $db->fetch_array($resql)) {
                    $row_array['label'] = $row['type_element'] . ' ' . $row['reference'] . ' : ' . $row['nom_societe'];
                    $row_array['value'] = $row['reference'];
                    $row_array['reference_rowid_' . $_GET['num_ligne']] = $row['rowid'];
                    $row_array['reference_type_element_' . $_GET['num_ligne']] = $row['type_element'];
                    $row_array['reference_fk_socid_' . $_GET['num_ligne']] = $row['fk_socid'];

                    array_push($return_arr, $row_array);
                }
            }

            header('Content-Type: application/json');
            echo json_encode($return_arr);
        }
        break;
}

$db->close();
?>