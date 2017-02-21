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
 *	\file       htdocs/core/lib/message.lib.php
 *	\brief      Ensemble de fonctions de base pour le module message
 *	\ingroup    message
 */

/**
*  Return array head with list of tabs to view object informations.
*
*  @param	Object	$object		Product
*  @return	array   	        head array with tabs
*/
function message_prepare_head($object=null)
{
	global $langs, $conf, $user, $db;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/dolmessage/index.php",1);
	$head[$h][1] = $langs->trans('MessageDashboard');
	$head[$h][2] = 'dashboard';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'message');


	dol_include_once('/user/class/usergroup.class.php');
	
	$array =array(); 
	$UserGroup = new UserGroup($db);
	foreach($UserGroup->listGroupsForUser($user->id) as $row)
		$array[]=$row->id;
	/**
		@brief webmail associted group 
	*/
	$sql = "SELECT * ";
	$sql.= " FROM ".MAIN_DB_PREFIX."usergroupwebmail";
	$sql.= " WHERE  1 ";
	$sql.= " AND fk_usergroup IN (".implode(',',$array).") ";
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		$i = 0;
		$var=true;
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			$head[$h][0] = dol_buildpath("/dolmessage/synchro.php",1).'?identifiid='.$objp->fk_usergroup.'&number='.$objp->number;
			$head[$h][1] = $objp->title;
			$head[$h][2] = 'synchro'.$objp->number;
			$h++;

			$i++;
		}
	}
	
	
	
	/**
		@brief webmail associted user 
	*/
	$sql = "SELECT * ";
	$sql.= " FROM ".MAIN_DB_PREFIX."userwebmail";
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

			$head[$h][0] = dol_buildpath("/dolmessage/synchro.php",1).'?number='.$objp->number;
			$head[$h][1] = $objp->title;
			$head[$h][2] = 'synchro'.$objp->number;
			$h++;

			$i++;
		}
	}
	
	
	complete_head_from_modules($conf,$langs,$object,$head,$h,'message','remove');

// 	$head[$h][0] = dol_buildpath("/dolmessage/info.php?uid=".$obj->$object ,1);
// 	$head[$h][1] = $langs->trans('MessageInfo');
// 	$head[$h][2] = 'info';
// 	$h++;
// 	
// 	$head[$h][0] = dol_buildpath("/dolmessage/attachment.php?uid=".$obj->$object ,1);
// 	$head[$h][1] = $langs->trans('MessageAttachment');
// 	$head[$h][2] = 'attachment';
// 	$h++;
	
	return $head;
}



/**
	@brief load message based on id or uid 
		if uid  also is imap process 
		if id also is local table + file 
*/
function LoadMessage($id, $uid, $dolimap){
	global $db, $user, $conf; 

		// local 
	if( /*($id > 0 && $uid <=0) ||*/ $id > 0){
		$DolMsg = new dolmessage($db, $user);
		$r = $DolMsg->fetch($id, '', true);

		$uid = $DolMsg->uid;
		
		$DolMsg->fetchObjectLinked($DolMsg->id, $DolMsg->element, $DolMsg->id, $DolMsg->element, 'OR');
		
// 			print_r($DolMsg);

		foreach($DolMsg->linkedObjects as $type=>$list) {
			foreach($list as $obj){
				if($type == 'societe') {

					$societe = $obj;
					
					$upload_dir = $conf->societe->multidir_output[$societe->entity] . "/" . $societe->id ;
					
					if(!file_exists($upload_dir))
						dol_mkdir($upload_dir);

					$upload_dir .= '/message/' ;
					if(!file_exists($upload_dir))
						dol_mkdir($upload_dir);
				}
			}
		}

		$DolLocal = new dollocalmessage($uid);
		$DolLocal->LoadLocal($upload_dir, $DolMsg->message_id);
		
		$Message = $DolLocal;

		
		$Message->SetDate($DolMsg->datec);
		
		$Message->SetId($DolMsg->id);
		
		$Message->SetPath( $upload_dir. $DolMsg->message_id);
		
		$Message->SetLinked($DolMsg->linkedObjects); 
	}
	elseif( $uid > 0 /*&& $id <=0*/ ){
		$Message = $dolimap->GetMessage( $uid );
	}
	
	
	return $Message; 
}



?>
