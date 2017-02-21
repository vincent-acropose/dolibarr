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

dol_include_once('dolmessage/class/emailgenerator.class.php');


$langs->load("companies");
$langs->load("other");
$langs->load("dolmessage@dolmessage");

// Get parameters
$model = GETPOST('model', 'alpha');
$returnurl = urldecode(GETPOST('param', 'alpha'));
$params=explode('/', $returnurl); 
// Protection if external user
if ($user->societe_id > 0) {
    //accessforbidden();
}

list($ofile, $oparam)=explode('?', $params[3]);
// print_r($params); 

// var_dump($ofile, $oparam); 

$Param = array(); 

foreach(explode('&', $oparam) as $row)
  list($key, $value)=explode('=', $row);
  $Param[$key]=$value;  
$outputlangs = $langs;

switch($ofile){
  case 'propal.php':
	dol_include_once('comm/propal/class/propal.class.php');
	
	$object = new Propal($db); 
	$object->fetch( $Param['id'] ); 
	
  break; 
  
  
}

header('Content-Type: text/html; charset=utf-8');
// $content = file_get_contents(DOL_DOCUMENT_ROOT. '/dolmessage/templates/'.$model); 

$emailgenerator = new  emailgenerator($db); 
// 
$content =  $emailgenerator->write_file($object,$outputlangs,'/dolmessage/templates/'.$model/*,$hidedetails=0,$hidedesc=0,$hideref=0*/);

echo ($content); 
// echo base64_encode($content); 
// var_dump($e); 
// print_r($emailgenerator); 


// var_dump(__file__); 

$db->close();
?>