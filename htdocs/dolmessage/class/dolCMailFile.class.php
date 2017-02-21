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
 
 
dol_include_once('/dolmessage/class/connector/dolimap.class.php');
dol_include_once('/dolmessage/class/user.mailconfig.class.php');
dol_include_once('/core/class/CMailFile.class.php');


class dolCMailFile
	Extends CMailFile {
	
	
	public function __construct($socid, $subject,$to,$from,$msg, $filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(), $addr_cc="",$addr_bcc="",$deliveryreceipt=0,$msgishtml=0,$errors_to='',$css=''){
		global $db, $user, $conf; 
		
		
// 		$conf->global->MAIN_MAIL_SENDMODE = 'imap';
// 		parent::__construct($subject,$to,$from,$msg,$filename_list,$mimetype_list,$mimefilename_list, $addr_cc,$addr_bcc,$deliveryreceipt,$msgishtml,$errors_to,$css);

		$dmy=date("r"); 
		$message_id = '<'.md5($subject.$msg).'@dolibarr.'.str_replace(array('http://', 'https://', '/'), array('','','.'), DOL_MAIN_URL_ROOT )  . '>'; 

		// Send 
		$envelope["from"]= $from;
		$envelope["to"]  = $to;
		$envelope["cc"]  = $addr_cc;
		$envelope["bcc"]  = $addr_bcc;
		$envelope["date"]  = $dmy;
		$envelope["subject"]  = $subject;
		$envelope["message_id"]  = $message_id;
		
		$parts = array();

		$part1["type"] = TYPEMULTIPART;
		$part1["subtype"] = "mixed";
		$parts[] = $part1;

		$part2 = array();
		$part2["type"] = TYPETEXT;
		$part2["subtype"] = 'html';
		$part2["charset"] = 'UTF-8';
		$part2["description"] = 'html';
		$part2["contents.data"] = nl2br($msg)."\n\n\n\t";;
		$parts[] = $part2;
		

		
		
		for($i=0; $i < count($filename_list) ;  $i++){
				$filename = $mimefilename_list[$i];
				$filetype = $mimetype_list[$i];

				$part2 = array();
				$part2["type"] = TYPEAPPLICATION;
// 				$part2["subtype"] = substr($filetype, strpos($filetype, '/')+1 );
				$part2["encoding"] = ENCBASE64;
				$part2["description"] = $filename;
				$part2['disposition.type'] = 'attachment';
				$part2['disposition'] = array ('filename' => $filename);
				$part2['type.parameters'] = array('name' => $filename);
				$part2["contents.data"] = base64_encode(file_get_contents($filename_list[$i]));

				$parts[] = $part2;
		}


// 		$part2 = array();
// 		$part2["type"] = TYPETEXT;
// 		$part2["subtype"] = 'plain';
// 		$part2["charset"] = 'UTF-8';
// 		$part2["description"] = 'text';
// 		$part2["contents.data"] = strip_tags($msg)."\n\n\n\t";;
// 		$parts[] = $part2;

		
		$i=0;
		foreach($parts as $part){
			$body[$i] = $part;
			$i++;
		}

		$reel= imap_mail_compose($envelope, $body);
		

		
		// Send Email 
// 		$res = imap_mail ( $to , $subject , $reel , NULL ,$addr_cc, $addr_bcc, $deliveryreceipt );
			
			
		// Copy in Inbox 
		$dolimap = new dolimap($db, $user);
		$dolimap->SetUser($user->id, $number);
		$dolimap->Open('Sent');
		$mbox = $dolimap->GetImap();

		// copy in INBOX Sent 
		$res = imap_append($mbox, $dolimap->user->imap_connector_url.'Sent', $reel, "\\Seen");

		$societe = new Societe($db );
		$societe->fetch($socid);

		$upload_dir = $conf->societe->multidir_output[$societe->entity] . "/" . $societe->id ;
		
		if(!file_exists($upload_dir))
			dol_mkdir($upload_dir);

		$upload_dir .= '/message/' ;
		
		if(!file_exists($upload_dir))
			dol_mkdir($upload_dir);

		// copy in local 
		if(!file_exists($upload_dir .'/'. $message_id)) {
// 				echo $upload_dir .$message_id; 
				$localmail = fopen($upload_dir .'/'. $message_id, "w");
				fwrite($localmail,$reel);
				fclose($localmail);
		}
		
		
		$dolmess = new dolmessage($db, $user);
		
		preg_match('#([0-9]{1,2}).([a-z]{3}).([0-9]{4}).([0-9]{2}):([0-9]{2}):([0-9]{2})#i',  substr($dmy,4), $match);
		

		 $search = 'SUBJECT "'.addslashes($subject).'" SINCE "'.$match[1].'-'.$match[2].'-'.$match[3].'"'; 
		$uids   = imap_search($mbox, $search, SE_UID);

		$uid = $uids[sizeof($uids)-1]; 

// 		$message =  imap_fetchbody($mbox,$uid,"",FT_UID);
// 		$headers = imap_headerinfo($mbox,imap_msgno($mbox,$uid));


		if( $dolmess->fetch(0, $message_id ) == false  ) {
				$dolmess->message_id = $message_id; 
				$dolmess->uid = $uid; 
				
			for($i=1; $i<= 12; $i++)
				if( date('M',mktime(1, 1, 1, $i )) == $match[2])
					$m = $i; 
				
				$dolmess->datec = date('Y-m-d H:i:s', mktime($match[4], $match[5], $match[6], $m, $match[1] , $match[3] )); 
				
				$dolmess->recent = 0;//$this->header->Recent; 
				$dolmess->unseen = 0;//$this->header->Unseen; 
				$dolmess->flagged = 0;//$this->header->Flagged; 
				$dolmess->answered = 0;//$this->header->Answered; 
// 				
				$dolmess->joint = 1; //(count($linked_objects)>0 ? 1 : 0);
				
				$dolmess->linked_objects = array(); //$linked_objects; 
				

				$dolmess->create();
		}
		
		
// 		exit;
		$dolimap->Close(); 
	}
}

?>
