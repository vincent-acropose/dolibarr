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
 
// dol_include_once('/dolmessage/class/user.mailconfig.class.php');
// dol_include_once('/dolmessage/class/usergroup.mailconfig.class.php');

// dol_include_once('/dolmessage/class/dolmessage.class.php');
dol_include_once('/dolmessage/class/dmessage.class.php');
dol_include_once('/dolmessage/class/connector/structuremessage.listener.php');



// dol_include_once('/dolmessage/class/mimeparser-2014-04-30/rfc822_addresses.php');
// dol_include_once('/dolmessage/class/mimeparser-2014-04-30/mime_parser.php');




class dolimapmessage 
	Extends DMessage {


	protected $message_id; 
	protected $references; 
	protected $in_reply_to; 

	
	

	public function __construct($object){
		
		$this->message_id = $object->message_id; 
		$this->references = $object->references; 
		$this->in_reply_to = $object->in_reply_to; 
		$this->msgno = $object->msgno; 
		$this->recent = $object->recent; 
		$this->flagged = $object->flagged; 
		$this->answered = $object->answered; 
		$this->deleted = $object->deleted; 
		$this->unseen = !$object->seen; 
		
		if ($object->size < 1024) {
				$this->size  = $object->size; 
				$this->sizeunit='&nbsp;o';
		} else if ($object->size / 1024 > 1024) {
				$this->size = $object->size / 1024 / 1024;
				$this->sizeunit= '&nbsp;Mo';
		} else {
				$this->size = $object->size / 1024;
				$this->sizeunit= '&nbsp;Ko';
		}

		if($object->id > 0) 
			$this->SetId($object->id); 
		
		$this->SetUid($object->uid); 
		
		$this->SetDate($object->date); 
		
		$this->SetSubject( trim(utf8_encode(@iconv_mime_decode(imap_utf8($object->subject)))) ); 
		
		$this->SetRecipient('from', $object->from); 

		$this->SetRecipient('to', $object->to); 
		
		$this->SetLinked($object->linkedObjects); 
// print_r($this); 
	}

	/**
		@brief
	*/
	public function GetMessageId(){	
		return $this->message_id; 
	}
	
	/**
		@brief
	*/
	public function SetMessageId($_message_id){	
		$this->message_id = $_message_id; 
	}
	
	/**
		@brief
	*/
	public function SetHeader($header) {
	
		$this->SetDate($header->date); 
		$this->SetMessageId($header->message_id); 
		$this->SetSubject( trim(utf8_encode(@iconv_mime_decode(imap_utf8($header->subject)))) ); 
		$this->SetRecipient('from', $header->fromaddress ); 
		$this->SetRecipient('to', $header->senderaddress );  
		$this->SetRecipient('reply', $header->reply_toaddress ); 
	}

	/**
		@brief
		@param $objMessg object 
		@param $dolimap object 
		@return result 
	*/
	public function delete($objMessg,$number=1, $dolimap){

		$r = $dolimap->Delete($objMessg->GetUid()); 
		
		return $r; 
	}
	
	
	/**
		 @brief Copy to local filesystem
		 @param $mbox ressource
		 @param uid int 
		 @param message_id string
		 @param path string pathfile
		 @param $linkedObjects array list of lin for other ressource
	*/
	public function CopyMessage($db, $mbox,$uid, $message_id, $path, $linkedObjects=array(), $number, $identifiid){
		global $user; 
		
		
		$dolmess = new dolmessage($db, $user);
// 		$message =  imap_fetchbody($mbox,$uid,"",FT_UID);
		
		$message =  $this->CopyFile($mbox,$uid, $message_id, $path) ; 
		
		$headers = imap_headerinfo($mbox,imap_msgno($mbox,$uid));
// 		$this->SetHeader($header); 

		if( $dolmess->fetch(0, $message_id , false, $number,  $identifiid) == false  ) {
				$dolmess->message_id = $message_id; 
				$dolmess->uid = $uid; 
				$dolmess->datec = $this->ConvertDate($headers->Date,'Y-m-d H:i:s'); 
				$dolmess->number =$number; 
				
				$dolmess->recent = $headers->Recent; 
				$dolmess->unseen = $headers->Unseen; 
				$dolmess->flagged = $headers->Flagged; 
				$dolmess->answered = $headers->Answered; 
				
				$dolmess->joint = (count($linkedObjects)>0 ? 1 : 0);
				
				$dolmess->linkedObjects = $linkedObjects; 


				$res =$dolmess->create($identifiid);
		}
		else {
// 				$dolmess->message_id = $message_id; 
// 				$dolmess->uid = $uid; 
				
				$dolmess->recent = $headers->Recent; 
				$dolmess->unseen = $headers->Unseen; 
				$dolmess->flagged = $headers->Flagged; 
				$dolmess->answered = $headers->Answered; 
				
				$dolmess->linkedObjects = $linkedObjects; 
				

				$res =$dolmess->update($user);
		}
		

// 		if(!empty($path) && !file_exists($path .'/'. $message_id)) {
// 				
// 				$localmail = fopen($path .'/'. $message_id, "w");
// 				fwrite($localmail,$message);
// 				fclose($localmail);
// 				
// 				// 		imap_mail_move($srcstream,$overview->msgno,'Forwarded');
// 		}

		return $dolmess; 
	}
	
	
	public function CopyFile($mbox,$uid, $message_id, $path) {
	
		$message =  imap_fetchbody($mbox,$uid,"",FT_UID);
		
		
		if(!empty($path) && !file_exists($path .'/'. $message_id)) {
				
				$localmail = fopen($path .'/'. $message_id, "w");
				fwrite($localmail,$message);
				fclose($localmail);
				// 		imap_mail_move($srcstream,$overview->msgno,'Forwarded');
		}
		
		return $message; 
	}
	
	
	/**
		@brief Fix flag for this one message
		@param $mbox current ressource id 
		@param $flag array( string \Seen, \Answered, \Flagged, \Deleted,  \Draft )
	*/
	public function SetFlag($mbox, $flag = array() ){
		return imap_setflag_full($mbox, "2,5", implode(' \\', $flag) );
	}

	
	 /**
		@brief convert date 
		@param date internationnal 21-Jul-2014 04:28:30 +0200
		@param format for return 
		@return $date 
 */
 public function ConvertDate($date, $format = 'd/m/Y H:i:s'){
		$m = 1; 
		preg_match('#([0-9]{1,2}).([a-z]{3}).([0-9]{4}).([0-9]{2}):([0-9]{2}):([0-9]{2})#i', $date, $match);

		for($i=1; $i<= 12; $i++)
			if( date('M',mktime(1, 1, 1, $i )) == $match[2])
				$m = $i; 

		$convert = date($format,mktime($match[4], $match[5], $match[6], $m, $match[1] , $match[3] ) ); 
 
	return $convert;
 }
 
}

?>
