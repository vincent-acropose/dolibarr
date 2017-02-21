<?php
/* Copyright (C) 2014		 Oscim					       <oscim@users.sourceforge.net>
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
    
    
dol_include_once('carddav/core/lib/carddav.lib.php');
dol_include_once('carddav/class/carddav.class.php');
dol_include_once('carddav/class/user.carddav.class.php');

$langs->load("carddav@carddav");


$id = GETPOST('id', 'int');
$action=GETPOST('action','alpha');




switch($action){
	default: 
		$result = array(); 
		
		$sql = "SELECT * ";
		$sql.= " FROM ".MAIN_DB_PREFIX."user_carddav";
		$sql.= " WHERE  1 ";
		$sql.= " AND fk_user = '".$user->id."' ";
		

		$resql = $db->query($sql);
		if ($resql)
		{
			 $num = $db->num_rows($resql);
			$i = 0;
			$var=true;
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
		
				$user_carddav = new Usercarddavconfig($db);
				$user_carddav->fetch($objp->rowid);

				
				// count 
				$sql1 = "SELECT count(*) as total  ";
				$sql1.= " FROM ".MAIN_DB_PREFIX."carddav_link";
				$sql1.= " WHERE  1 ";
				$sql1.= " AND fk_user_carddav = '".$objp->rowid."' GROUP BY fk_user_carddav ";
				$resql1 = $db->query($sql1);
				$objp1 = $db->fetch_object($resql1);
				
				$user_carddav->count_rows = $objp1->total;
				$result[] = $user_carddav;
				$i++;
			}
		}
}

/* * *************************************************
 * VIEW
 *
 * Put here all code to build page
 * ************************************************** */

llxHeader('', iconv(iconv_get_encoding($langs->trans($lbl_folder)), $character_set_client."//TRANSLIT" , $langs->trans($lbl_folder)) . ' (' . $info->Nmsgs . ') ', '');

dol_fiche_head( user_carddav_prepare_head() , 'dashboard', $langs->trans("Module98365Name"), 0, 'carddav@carddav');

dol_include_once('/carddav/tpl/index.dashboard.tpl');

?>
