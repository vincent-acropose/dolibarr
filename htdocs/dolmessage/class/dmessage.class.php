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
  Support class for other driver message
 */
Class DMessage {

    /**
      @var int
     */
    protected $id = 0;

    /**
      @var int
     */
    protected $uid = 0;

    /**
      @var string
     */
    protected $charset = '';

    /**
      @var string
     */
    protected $htmlmsg = '';

    /**
      @var
     */
    protected $plainmsg = '';

    /**
      @var
     */
    protected $attachments = array();

    /**
      @var object
     */
    protected $header;
	public $from; 
	protected $to; 
	protected $subject; 
	protected $size; 
	protected $sizeunit; 

	protected $recent; 
	protected $answered; 
	protected $flagged; 
	protected $deleted; 
	protected $unseen; 
	
	
	protected $linkedObject; 
    /**
      @var from
     */
//     protected $from_name;

    /**
      @var from
     */
//     protected $from_mail;

    /**
      @var date current local display
     */
    protected $date;

    /**
      @brief
     */
    public function GetHeader() {
        return $this->header;
    }

    /**
      @brief
     */
    public function GetId() {
        return $this->id;
    }
    /**
      @brief
     */
    public function SetId($id) {
        $this->id=$id;
    }
    /**
      @brief
     */
    public function SetUid($uid) {
        $this->uid = $uid; 
    }
    
    /**
      @brief
     */
    public function GetUid() {
        return $this->uid;
    }

	/**
      @brief
	*/
    public function SetLinked( $obj ) {
        return $this->linkedObject = $obj; ;
    }
    
	/**
      @brief
	*/
    public function GetLinked() {
        return $this->linkedObject;
    }
    
    /**
      @brief
     */
    public function GetAnswered() {
        return $this->answered;
    }
    
    /**
      @brief
     */
    public function GetUnseen() {
        return $this->unseen;
    }
    /**
      @brief
     */
    public function GetFlagged() {
        return $this->flagged;
    }
    /**
      @brief
     */
    public function GetDeleted() {
        return $this->deleted;
    }
    
    /**
      @brief
     */
//     public function GetDate() {
//             return $this->header->date;
//     }

	/**
	*/
	public function SetSubject($_subject){
		return $this->subject = $_subject;// ;
	}
	
	/**
	*/
	public function GetSubject(){
		return $this->subject;
	}
	
	public function SetContent( $html, $plain){
	  $this->htmlmsg = $html; 
	  $this->plainmsg = $plain; 
	}
	/**
	  @param $_type string from, to, reply
	  @param $_string "dlfldflk"<lsklsdk@lskdlk.lan>
	*/
	public function SetRecipient($_type='from', $_string){
		$this->$_type = new StructureContactListener($_string); 
	}
	
	/**
	*/
	public function GetFromName(){
		return $this->from->name;
	}
	
	/**
	*/
	public function GetFromMail(){
		return $this->from->email;
	}
	
	/**
	*/
	public function GetToName(){
		return $this->to->name;
	}
	
	/**
	*/
	public function GetToMail(){
		return $this->to->email;
	}
	
	/**
	*/
	public function SetDate($_date){
	  $this->date = $_date; 
	}
	
	/**
	*/
	public function GetDate($short=false){
		if($short){
			if (date('d/m/Y')==date("d/m/Y", strtotime($this->date))){  /* only hours if day is the same */
					$shortDate= date("H:i", strtotime($this->date));
			} elseif(date('Y')!=date("Y", strtotime($this->date))) {    /* if not this year*/
					$shortDate= date("d M. Y", strtotime($this->date));
			} else {                                                    /* if year is implicite */
					$shortDate= date("d M.", strtotime($this->date));
			}
			return $shortDate; 
		}
		
		
			return $this->date;
		
	}
	
	/**
	*/
	public function GetSize($unit=true){
		return number_format($this->size,1 ) . (($unit)? $this->sizeunit : '');
	}
    /**
      NOT USE (server not having locales installed) target is to provide a format for date() function
     */
//     public function getDateFormat() {
//         $location = setlocale(LC_ALL, 'fr_FR@euro', 'fr_FR', 'fr');
// //         var_dump($location);
// //         die();
//         $patterns = array(
//             '11-21-99',
//             '11/21/99',
//             '21/11/99',
//             '99-11-21',
//             '11-21-1999',
//             '11/21/1999',
//             '21/11/1999',
//             '1999-11-21'
//         );
//         $replacements = array('m-d-y', 'm/d/y', 'd/m/y', 'y-m-d', 'm-d-Y', 'm/d/Y', 'd/m/Y', 'Y-m-d');
//         $date = new DateTime();
//         $date->setDate(1999, 11, 21);
//         return str_replace($patterns, $replacements, strftime('%x', $date->getTimestamp()));
//     }

    /**
      @brief
     */
//     public function GetSubject() {
//         return trim(utf8_encode(@iconv_mime_decode(imap_utf8($this->header->subject)))) ;
//     }

    /**
      @brief
     */
//     public function GetFromName() {
//         return $this->header->from_name;
//     }

    /**
      @brief
     */
//     public function GetFromMail() {
//         return $this->header->from_mail;
//     }

    /**
      @brief
     */
//     public function GetToMail() {
// //     var_dump($this->header); 
// 				if(preg_match('#=?.*?=#i', $this->header->toaddress)) {
// // 				var_dump($this->header->toaddress); 
// 					$element = imap_mime_header_decode( $this->header->toaddress );
// 					return trim($element[0]->text) . ' '. htmlspecialchars($element[1]->text );
// 				}
// 				else
// 					return $this->header->toaddress;
//     }

	public function SetCharset($_charset){
	  $this->charset =  $_charset; 
	}
    /**
      @brief
     */
    public function GetCharset() {
        return $this->charset;
    }

    /**
      @brief
     */
    public function GetHtmlMsg() {
        return str_replace(array('=3D"',"=\r","=\n",'=09'), array('="','','',''),html_entity_decode($this->htmlmsg));
    }

    /**
      @brief
     */
    public function GetPlainMsg() {
        return $this->plainmsg;
    }

    /**
      @brief
     */
    public function SetAttach($attach = array()) {
        return $this->attachments = $attach;
    }

    /**
      @brief
     */
    public function GetAttach() {
        return $this->attachments;
    }

}

Class dattachment {

    /**
     */
    public $name;

    /**
     */
    public $appType;

    /**
     */
    public $encoding;

    /**
     */
    public $data;

    public function __construct($type, $name, $encoding, $data) {
        $this->appType = $type;
        $this->name = $name;
        $this->encoding = $encoding;

        if (trim($this->encoding) == 'base64')
            $this->data = base64_decode($data);
        else
            $this->data = $data;
    }

    public function GetName() {
        return $this->name;
    }

    public function GetApplicationType() {
        return $this->appType;
    }

    public function GetEncoding() {
        return $this->encoding;
    }

    public function GetData() {
        return $this->data;
    }

}

?>
