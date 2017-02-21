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
$uid = GETPOST('uid', 'int') ;
$action = GETPOST('action', 'alpha');

// Protection if external user
if ($user->societe_id > 0) {
    //accessforbidden();
}

$dolimap = new dolimap($db, $user);
if(empty($identifiid)) 
	$dolimap->SetUser($user->id, $number);
elseif(!empty($identifiid)) 
	$dolimap->SetUserGroup($identifiid, $number);


// } else {

		// if local message 
		if($id > 0){
			$DolMsg = new dolmessage($db);
			$DolMsg->fetch($id);
			
			$uid = $DolMsg->uid;
			
			$DolMsg->fetchObjectLinked($DolMsg->id, $DolMsg->element, $DolMsg->id, $DolMsg->element, 'OR');
			
			
			foreach($DolMsg->linkedObjects as $type=>$list) {
				foreach($list as $obj){
					if($type == 'societe') {

						$societe = $obj;
						
						$upload_dir = $conf->societe->multidir_output[$societe->entity] . "/" . $societe->id ;
						
						if(!file_exists($upload_dir))
							dol_mkdir($upload_dir);
							
							
						$upload_dir .= '/message/' ;
			// 			$courrier_dir = $conf->societe->multidir_output[$societe->entity] . "/courrier/" . get_exdir($societe->id);
				
						if(!file_exists($upload_dir))
							dol_mkdir($upload_dir);
					}
				}
			}

			$DolLocal = new dollocalmessage($uid);
			$DolLocal->LoadLocal($upload_dir, $DolMsg->message_id);
			
			$Message = $DolLocal;
			$Message->SetId($id);

		}
		else {
			$dolimap->Open();
			$mbox = $dolimap->GetImap();

			if (FALSE === $mbox) {
				foreach( $dolimap->ListErrors() as $row )
							$err .= $row;
			} 
			else 
						
			$Message = $dolimap->GetMessage( $uid );
    }
// }

	
/* * *************************************************
 * VIEW
 *
 * Put here all code to build page
 * ************************************************** */

llxHeader('', 'Dolibarr Webmail', '');

dol_fiche_head( message_prepare_head( ) , 'attachment', $langs->trans("Webmail"), 0, 'mailbox@dolmessage');


dol_include_once('/dolmessage/tpl/attachment.display.tpl');


// End of page
llxFooter();
$db->close();
?>