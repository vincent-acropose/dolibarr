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

// /*
// 
// class StructureMessageListener {
// 
// 	protected $subject; 
// 	protected $from; 
// 	protected $to; 
// 	protected $date; 
// 	protected $message_id; 
// 	protected $references; 
// 	protected $in_reply_to; 
// 	protected $size; 
// 	protected $sizeunit; 
// // 	protected $subject; 
// // 	protected $subject; 
// // 	protected $subject; 
// // 	protected $subject; 
// 	
// 	
// 	
// 
// 	public function __construct($object){
// 		
// 		$this->uid = $object->uid; 
// 		$this->message_id = $object->message_id; 
// 		$this->references = $object->references; 
// 		$this->in_reply_to = $object->in_reply_to; 
// 		$this->msgno = $object->msgno; 
// 		$this->date = $object->date; 
// 		$this->linkedObjects = $object->linkedObjects; 
// 		$this->recent = $object->recent; 
// 		$this->flagged = $object->flagged; 
// 		$this->answered = $object->answered; 
// 		$this->deleted = $object->deleted; 
// 		$this->seen = $object->seen; 
// 		
// 		if ($object->size < 1024) {
// 				$this->size  = $object->size; 
// 				$this->sizeunit='&nbsp;o';
// 		} else if ($object->size / 1024 > 1024) {
// 				$this->size = $object->size / 1024 / 1024;
// 				$this->sizeunit= '&nbsp;Mo';
// 		} else {
// 				$this->size = $object->size / 1024;
// 				$this->sizeunit= '&nbsp;Ko';
// 		}
// 
// 		
// 		
// 		$this->subject = trim(utf8_encode(@iconv_mime_decode(imap_utf8($object->subject)))); 
// 		
// 		$n = new StructureContactListener($object->from); 
// 		$this->from = $n; 
// 		
// 		
// 		$n = new StructureContactListener($object->to); 
// 		$this->to = $n; 
// 	}
// 	
// 	/**
// 	*/
// 	public function GetSubject(){
// 		return $this->subject;
// 	}
// 	
// 	/**
// 	*/
// 	public function GetFromName(){
// 		return $this->from->name;
// 	}
// 	
// 	/**
// 	*/
// 	public function GetFromMail(){
// 		return $this->from->email;
// 	}
// 	
// 	/**
// 	*/
// 	public function GetToName(){
// 		return $this->to->name;
// 	}
// 	
// 	/**
// 	*/
// 	public function GetToMail(){
// 		return $this->to->email;
// 	}
// 	
// 	/**
// 	*/
// 	public function GetDate($short=false){
// 		if($short){
// 			if (date('d/m/Y')==date("d/m/Y", strtotime($this->date))){  /* only hours if day is the same */
// 					$shortDate= date("H:i", strtotime($this->date));
// 			} elseif(date('Y')!=date("Y", strtotime($this->date))) {    /* if not this year*/
// 					$shortDate= date("d M. Y", strtotime($this->date));
// 			} else {                                                    /* if year is implicite */
// 					$shortDate= date("d M.", strtotime($this->date));
// 			}
// 			return $shortDate; 
// 		}
// 		
// 		
// 			return $this->date;
// 		
// 	}
// 	
// 	/**
// 	*/
// 	public function GetSize($unit=true){
// 		return number_format($this->size,1 ) . (($unit)? $this->sizeunit : '');
// 	}
// }*/


/**
*/
Class StructureContactListener {

	/**
	*/
	public $name; 
	/**
	*/
	public $email; 
	
	/**
	*/
	public function __construct( $string ){
		$matches = null;
		$returnValue = preg_match('/<(.[^>]*)>/', $string, $matches);
		$Email= trim($matches[1]);
		if($Email==""){
				$Email = $string;
		}
		$this->email = $Email;
		$this->name = trim(preg_replace('/<.*>|"/', '', @iconv_mime_decode(imap_utf8($string))));
	}
	
	/**
	*/
	public function GetEmail(){
		return $this->email; 
	}
	
	/**
	*/
	public function GetName(){
		return $this->name; 
	}
}

?>
