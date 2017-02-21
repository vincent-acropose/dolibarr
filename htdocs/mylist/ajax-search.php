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

$action = GETPOST('action', 'alpha');

// var_dump(__file__); 
switch($action){
    case 'magasin':
            $return_arr = array();

            $reference = $_GET['q'];
            // Recherche parmis les societes
            $sql = "SELECT s.id as rowid,  s.ville ";
            $sql.= " FROM cg_magasins as s";
            $sql.= " WHERE ";
            $sql.=" (ville LIKE '%" . $db->escape($reference) . "%'   )";
          

// 					echo $sql ; 
// 					exit;
            $sql.= $db->plimit(50); // Avoid pb with bad criteria

            $resql = $db->query($sql);

            if ($resql) {
                while ($row = $db->fetch_array($resql)) {
                    $row_array['label'] = $row['ville'];
                    $row_array['value'] = $row['ville'];
                    $row_array['fk_magid' ] = $row['rowid'];

                    array_push($return_arr, $row_array);
                }
            }

            header('Content-Type: application/json');
            echo json_encode($return_arr);
		break;
    case 'societe':
            $return_arr = array();

            $reference = $_GET['q'];
            // Recherche parmis les societes
            $sql = "SELECT s.rowid as rowid, CONCAT_WS(' - ', s.code_client, s.code_fournisseur) as reference, s.nom as nom_societe, 'societe' as type_element, s.rowid as fk_socid";
            $sql.= " FROM " . MAIN_DB_PREFIX . "societe as s";
            $sql.= " WHERE ";
            $sql.=" (s.code_client LIKE '%" . $db->escape($reference) . "%' OR s.nom LIKE '%" . $db->escape($reference) . "%' OR s.code_fournisseur LIKE '%" . $db->escape($reference) . "%' OR s.email LIKE '%" . $db->escape($reference) . "%'  )";
          

// 					echo $sql ; 
// 					exit;
            $sql.= $db->plimit(50); // Avoid pb with bad criteria

            $resql = $db->query($sql);

            if ($resql) {
                while ($row = $db->fetch_array($resql)) {
                    $row_array['label'] = $row['nom_societe'];
                    $row_array['value'] = $row['reference'];
//                     $row_array['reference_rowid_' . $_GET['num_ligne']] = $row['rowid'];
//                     $row_array['reference_type_element_' . $_GET['num_ligne']] = $row['type_element'];
                    $row_array['fk_socid' ] = $row['fk_socid'];

                    array_push($return_arr, $row_array);
                }
            }

            header('Content-Type: application/json');
            echo json_encode($return_arr);
		break;
}

$db->close();


?>
