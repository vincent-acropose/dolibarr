<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
    	\file       dev/skeletons/skeleton_page.php
		\ingroup    mymodule othermodule1 othermodule2
		\brief      This file is an example of a php page
		\version    $Id: index.php,v 1.3 2010-05-11 22:21:37 jean Exp $
		\author		Put author name here
		\remarks	Put here some comments
*/

require("./pre.inc.php");
dol_include_once("/importateur/class/D_importateur.class.php");

$langs->load("companies");
$langs->load("other");
$langs->load("importateur@importateur");

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

//dol_syslog("vars ".print_r($HTTP_POST_VARS,true), LOG_DEBUG);
$typeimport = GETPOST('type','alpha', 0);
$importlibs = array('T'=>'ImportThirtdparty', 'C'=>'ImportContact', 'A'=>'ImportActions', 'P'=>'ImportProduct', 'TF'=>'Importtarif', 'S'=>'ImportStock');
$lib_import = ($typeimport)?$importlibs["$typeimport"]:"";
$action = GETPOST('action','alpha');
dol_syslog(__FILE__.":: type ".$lib_import."  ". $typeimport." ".$action, LOG_DEBUG);

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

// imports par fichiers
if ($action == 'ImportFile')
{

 // gestion des imports
	$nom_fic = $_FILES["fichier"]["name"];
	$newfic = DOL_DATA_ROOT.'/'.$nom_fic; // dans le rep de travail
	$temp_fic = $_FILES["fichier"]["tmp_name"];
	if ( move_uploaded_file($temp_fic, $newfic)) $res = "ok"; 
	else $res="ko";

	$D_import = new D_importateur($db, $newfic,  $user);

	//recup des variables
	$firstline = $_POST["firstline"];
	$actionverif = $_POST["verifier"];
	$actionimport = $_POST["importer"];
	//$objet = isset($_POST["type"])?$_POST["type"]:'';
	$typeaction = isset($_POST["Dolitype"])?$_POST["Dolitype"]:'';
	$msg_action = '';
//$msg_action = $actionimport . " -- " . $typeaction;
	if ($firstline == "on") $D_import->firstline = 1;
	$object = $importlibs["$typeimport"] ;
	if ($actionverif =="on") 
	{
		$imp_result = $D_import->validate_import($importlibs["$typeimport"]);
		
		if ($imp_result < 0 ) $mesg = "erreur de validation ".$D_import->error; 	
	}
	else $imp_result = '';
	if ($actionimport =="on" && !$imp_result  ) 
	{
		$mesg .="Import ".$typeimport."  ".$typeaction. " ".$importlibs["$$typeimport"]." ";
		$imp_result = $D_import->import2Dolibarr($importlibs["$typeimport"], $typeaction);
		if ($imp_result < 0) $mesg = "erreur import Dolibarr ".$D_import->error; 	
	}
	
	$mesg .= "action = ".$typeaction.' objet= '.$typeimport.' action '.$action;


}


/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/

llxHeader('Importateur');
//print_r($HTTP_POST_VARS);

$form=new Form($db);

print_fiche_titre($langs->trans("Imports") );
// Put here content of your page
if ($mesg) print '<p>'.$mesg.'</p>';
if ($D_import->error) print '<p>'.$D_import->error.'</p>';
if ($msg_action) print "<p>$msg_action</p>";
// ...
print '<table class="notopnoleftnoright" width="75%" >';
print '<tr>';
print '<td width="20%">'.$langs->trans("ImportFileName").'</td>';
print '<td colspan="2"><form action="index.php" method="POST" enctype="multipart/form-data">';
print '<input type="file" name="fichier" size="40">';
print '<input type="hidden" name="action" value="ImportFile">';
print '</td></tr>';
print '<tr><td width="20%">&nbsp;</td><td>';
if (empty($typeimport) || (! $lib_import)) {
	print '<select name="type">';
	print '<option value="P">Produits</option>';
	print '<option value="TF">Prix Fournisseur</option>';
	print '<option value="S">Stock Produits</option>';
	print '<option value="T">Tiers</option>';
	print '<option value="C">Contacts</option>';
	print '<option value="A">Actions</option>';
	print '</select>';
}
else {
	print '<br / >'.$langs->trans($lib_import). '<br />';
	print '<input type="hidden" name="type" value="'.$typeimport.'">';
	
}
print '</td></tr>';	
print '<tr><td width="20%">&nbsp;</td><td><input type="checkbox" name="firstline" checked></td><td>'.$langs->trans("ImportFirstLine").'</td></tr>';
print '<tr><td width="20%">&nbsp;</td><td><input type="checkbox" name="verifier"></td><td>'.$langs->trans("ImportVerif").'</td></tr>';
print '<tr><td width="20%">&nbsp;</td><td><input type="checkbox" name="importer"></td><td>'.$langs->trans("ImportFile").'</td>';
print '<td width="40%"><input type="Radio" name ="Dolitype" value="C" >'.$langs->trans("ImportCreate").'<br/><input type="Radio" name ="Dolitype" value="M" >'.$langs->trans("ImportMaj").'<br/><input type="Radio" name ="Dolitype" value="D" >'.$langs->trans("ImportDel").'<br/><br/></td></tr>';
print'<tr><td colspan="3" align="center"><input type="submit" value="Envoyer">';
print '</td></form></tr>';
print '</table>';

print '<br/>';
if ($D_import->process_msg) print '<p>'.nl2br($D_import->process_msg).'</p>';

// End of page
$db->close();
llxFooter('$Date: 2010-05-11 22:21:37 $ - $Revision: 1.3 $');
?>

