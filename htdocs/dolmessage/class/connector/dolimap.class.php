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
 
dol_include_once('/dolmessage/class/user.mailconfig.class.php');
dol_include_once('/dolmessage/class/usergroup.mailconfig.class.php');

dol_include_once('/dolmessage/class/dolmessage.class.php');
dol_include_once('/dolmessage/class/dmessage.class.php');

dol_include_once('/dolmessage/class/connector/dolimapmessage.class.php');

// dol_include_once('/dolmessage/class/connector/structuremessage.listener.php');


dol_include_once('/dolmessage/class/mimeparser-2014-04-30/rfc822_addresses.php');
dol_include_once('/dolmessage/class/mimeparser-2014-04-30/mime_parser.php');

class dolimap {

    /**
      @var object
     */
    public $db;

    /**
      @var object
     */
    public $user;
    /**
      @var object
     */
    public $group;
    /**
      @var object
     */
    public $mbox;

    /**
      @var object
     */
    public $check;

    /**
      @var list error
     */
    public $listerror = array();

    /**
     */
    public function __construct($db, $user) {
        $this->db = $db;
        $this->user = $user;


        imap_timeout(IMAP_OPENTIMEOUT, 5);
        imap_timeout(IMAP_READTIMEOUT, 15);
        imap_timeout(IMAP_WRITETIMEOUT, 5);
        imap_timeout(IMAP_CLOSETIMEOUT, 5);
    }

    /**
      @brief Open Connection
      @param string
     */
    public function Open($folder = '') {
        $this->mbox = @imap_open($this->user->imap_connector_url . $this->CleanFolder($folder), $this->user->imap_login, $this->user->imap_password);
    }

    public function Send($sendto,$subject,$final,$sendtocc ){
	  $this->Open(); 
	  
	  return imap_mail($sendto , $subject ,$final ,/* string $additional_headers =*/ NULL , $sendtocc/*, string $bcc = NULL [, string $rpath = NULL ]]]]*/);
    }
    
    
    /**
      @brief Clean param folder
      @param $folder
     */
    public function CleanFolder($folder = '') {
        return str_replace('{' . $this->user->imap_host . '}', '', $folder);
    }

    /**
      @brief Vérifie la boîte aux lettres courante
      @return object check ;
      object(stdClass)(5) {
      ["Date"]=>
      string(37) "Wed, 10 Dec 2003 17:56:54 +0100 (CET)"
      ["Driver"]=>
      string(4) "imap"
      ["Mailbox"]=>
      string(54)
      "{www.example.com:143/imap/user="foo@example.com"}INBOX"
      ["Nmsgs"]=>
      int(1)
      ["Recent"]=>
      int(0)
      }
     */
    public function Check() {
        $this->check = imap_check($this->mbox);

        return $this->check;
    }

    /**
      @brief  Lit la liste des boîtes (dir) aux lettres
      @return list mails array ;
     */
    public function ListFolder($search = '*') {

        $myMenuList = imap_getmailboxes($this->mbox, $this->user->imap_ref, $search . '*');

        foreach ($myMenuList as $key => $val) {
            $myMenuList[$key]->name = imap_utf7_decode($val->name);
        }
        return $myMenuList;
    }

    /**
      @brief search in current my box
     */
    public function CountMessage($flag) {

        $count = imap_num_msg($this->mbox);

        for ($msgno = 1; $msgno <= $count; $msgno++) {
            $headers = imap_headerinfo($this->mbox, $msgno);
            if ($headers->Unseen == 'U') {
                
            }
        }
    }

    /**
      @brief search in current my box
     */
    public function SearchMessage($flag) {

        $count = imap_num_msg($this->mbox);

        for ($msgno = 1; $msgno <= $count; $msgno++) {

            $headers = imap_headerinfo($this->mbox, $msgno);
            if ($headers->Unseen == 'U') {
                
            }
        }
    }

    /**
      @brief all list of message in current dir
      @param $page int current page
      @param $pagination int result by page
     */
    public function ListMessage($page, $pagination = 50, $group_id=0) {
        global $user;
        $indice_msgend = $this->check->Nmsgs - ($pagination * ($page - 1) );
        $indice_msgbegin = max(1, $indice_msgend - $pagination + 1);
        $mails = array_reverse(imap_fetch_overview($this->mbox, $indice_msgbegin . ':' . $indice_msgend, 0));

        $return =array(); 
        foreach ($mails as $i => $mail) {
            $dolmess = new dolmessage($this->db, $user);

            if($group_id > 0 && $dolmess->fetch(0, $mail->message_id, false, $this->user->number, $group_id) == false) {
                $dolmess->specimen();
                $dolmess->uid = $mails[$i]->uid;
            }
            elseif ( $group_id ==  0 && $dolmess->fetch(0, $mail->message_id, false, $this->user->number) == false) {
                $dolmess->specimen();
                $dolmess->uid = $mails[$i]->uid;
            }

            $mails[$i]->id = $dolmess->id;
            $mails[$i]->uid = $dolmess->uid;
            $mails[$i]->linkedObjects = $dolmess->linkedObjects;

//             print_r( $mails[$i]); 
//             $return[$i] = new StructureMessageListener($mails[$i]); 
							$return[$i] = new dolimapmessage($mails[$i]); 
        } 

        return $return;
    }

    /**
      @brief Get obect Imap Connection opened
      @return $this->mbox;
     */
    public function GetImap() {
        return $this->mbox;
    }

    /**
      @brief Close Connection
     */
    public function Close() {
        imap_close($this->mbox);
    }

    /**
      @brief List of errors
     */
    public function ListErrors() {
        foreach (imap_errors() as $row)
            $this->listerror[] = $row;

        return $this->listerror;
    }

    /**
      @brief
      @param uid int uid repsonse in imap protocol
     */
    public function GetMessage($uid) {

        $headerText = imap_fetchHeader($this->mbox, $uid, FT_UID);
        $header = imap_rfc822_parse_headers($headerText);

        $message = $this->getmsg($uid);
        $message->SetHeader($header);
        $message->SetAttach($this->getAttachments($uid));

        return $message;
    }

    /**
      @brief load data user
      @param $id int user id
      @param $number int indice of config for this user
      @return $user
     */
    public function SetUser($id, $number = 1) {
        if ($number <= 0)
            $number = 1;

        $mailboxconfig = new Usermailconfig($this->db);
        $mailboxconfig->fetch_from_user($id, $number);

        $this->user->number = $number; 
        $this->user->imap_login = $mailboxconfig->imap_login;
        $this->user->imap_password = $mailboxconfig->imap_password;
        $this->user->imap_host = $mailboxconfig->imap_host;
        $this->user->imap_port = $mailboxconfig->imap_port;
        $this->user->imap_ssl = $mailboxconfig->imap_ssl;
        $this->user->imap_ssl_novalidate_cert = $mailboxconfig->imap_ssl_novalidate_cert;
        $this->user->imap_ref = $mailboxconfig->get_ref();
        $this->user->imap_connector_url = $mailboxconfig->get_connector_url();

        return $this->user;
    }

    /**
      @brief load data user
      @param $id int user id
      @param $number int indice of config for this user
      @return $user
     */
    public function SetUserGroup($id, $number = 1) {
        if ($number <= 0)
            $number = 1;

        $mailboxconfig = new UserGroupmailconfig($this->db);
        $mailboxconfig->fetch_from_usergroup($id, $number);

        $this->user->number = $number; 
        $this->user->group = 1; 
        $this->user->imap_login = $mailboxconfig->imap_login;
        $this->user->imap_password = $mailboxconfig->imap_password;
        $this->user->imap_host = $mailboxconfig->imap_host;
        $this->user->imap_port = $mailboxconfig->imap_port;
        $this->user->imap_ssl = $mailboxconfig->imap_ssl;
        $this->user->imap_ssl_novalidate_cert = $mailboxconfig->imap_ssl_novalidate_cert;
        $this->user->imap_ref = $mailboxconfig->get_ref();
        $this->user->imap_connector_url = $mailboxconfig->get_connector_url();

        return $this->user;
    }
    /**
      @brief return data user
      @return $user
     */
    public function GetUser() {
        return $this->user;
    }

    public function Delete($uid) {
        return imap_delete($this->mbox, $uid, FT_UID);
    }

    /**
     * Récupère les pièces d'un mail donné
     * @param integer $jk numéro du mail
     * @return array type, filename, pos
     */
    public function getAttachments($uid) {
        $structure = imap_fetchstructure($this->mbox, $uid, FT_UID);

        $parts = $this->getParts($structure);
        $fpos = 2;
        $attachments = array();

        if ($parts && count($parts)) {
            //var_dump($parts);
            for ($i = 1; $i <= count($parts); $i++) {
                $part = $parts[$i];
                if ($part->ifdisposition && (strtolower($part->disposition) == "attachment" || strtolower($part->disposition) == "inline" )) {
                    $ext = $part->subtype;

                    $filename = ($part->dparameters[0]->value) ? $part->dparameters[0]->value : $part->parameters[0]->value;
                    $filename = imap_utf8($filename);
                    $attachments[$part->id] = new dattachment($part->type, $filename, $part->encoding, $this->getAttachment($uid, $fpos, $part->type));
                }
                $fpos++;
            }
        }

        return $attachments;
    }

    /**
     * Récupère la contenu de la pièce jointe par rapport a sa position dans un mail donné
     * @param integer $jk numéro du mail
     * @param integer $fpos position de la pièce jointe
     * @param integer $type type de la pièce jointe
     * @return mixed data
     */
    public function getAttachment($jk, $fpos, $type) {
        $mege = imap_fetchbody($this->mbox, $jk, $fpos, FT_UID);

        $data = $this->getDecodeValue($mege, $type);

        return $data;
    }

    /**
     * Récupère les parties d'un message
     * @param object $structure structure du message
     * @return object|boolean parties du message|false en cas d'erreur
     */
    public function getParts($structure) {
        return isset($structure->parts) ? $structure->parts : false;
    }

    /**
     * Décode le contenu du message
     * @param string $message message
     * @param integer $coding type de contenu
     * @return message décodé
     * */
    private function getDecodeValue($message, $coding) {
        switch ($coding) {
            case 0: //text
            case 1: //multipart
                $message = imap_8bit($message);
                break;
            case 2: //message
                $message = imap_binary($message);
                break;
            case 3: //application
            case 5: //image
            case 6: //video
            case 7: //other
                $message = imap_base64($message);
                break;
            case 4: //audio
                $message = imap_qprint($message);
                break;
        }

        return $message;
    }

    // private 

    /**
     */
    private function getmsg($uid) {
        $htmlmsg = '';
        $plainmsg = '';
        $charset = '';
        $attachments = array();

        // add code here to get date, from, to, cc, subject...
        // BODY
        $s = imap_fetchstructure($this->mbox, $uid, FT_UID);

        if (!$s->parts)  // simple
            list($charset, $htmlmsg, $plainmsg, $attachments) = $this->getpart($uid, $s, 0, $charset, $htmlmsg, $plainmsg, $attachments);
        else {  // multipart: cycle through each part
            foreach ($s->parts as $partno0 => $p) {
                list($charset, $htmlmsg, $plainmsg, $attachments) = $this->getpart($uid, $p, $partno0 + 1, $charset, $htmlmsg, $plainmsg, $attachments);
            }
        }

        $tmp = new dolimapmessage( new stdclass ); //$uid, $charset, $htmlmsg, $plainmsg, $attachments);
        
        $tmp->SetUid($uid); 
        $tmp->SetAttach($attachments); 
        $tmp->SetCharset($charset); 
        $tmp->SetContent($htmlmsg, $plainmsg);
        return $tmp; 
    }

    /**
     */
    function getpart($mid, $p, $partno, $charset, $htmlmsg, $plainmsg, $attachments) {


        $data = ($partno) ?
                imap_fetchbody($this->mbox, $mid, $partno, FT_UID) : // multipart
                imap_body($this->mbox, $mid, FT_UID);  // simple

        $data = $this->getDecodeValue($data, $p->encoding);

        // PARAMETERS
        // get all parameters, like charset, filenames of attachments, etc.
        $params = array();
        if ($p->parameters)
            foreach ($p->parameters as $x)
                $params[strtolower($x->attribute)] = $x->value;
        if ($p->dparameters)
            foreach ($p->dparameters as $x)
                $params[strtolower($x->attribute)] = $x->value;


        // ATTACHMENT
        // Any part with a filename is an attachment,
        // so an attached text file (type 0) is not mistaken as the message.
        if ($params['filename'] || $params['name']) {
            // filename may be given as 'Filename' or 'Name' or both
            $filename = ($params['filename']) ? $params['filename'] : $params['name'];
            // filename may be encoded, so see imap_mime_header_decode()
            $attachments['file'.$filename] = $data;  // this is a problem if two files have same name
        }

        // TEXT
        if ($p->type == 0 && $data) {

            // Messages may be split in different parts because of inline attachments,
            // so append parts together with blank row.
            if (strtolower($p->subtype) == 'plain')
                $plainmsg .= trim($data);
            else
                $htmlmsg .= $data;
            $charset = (!isset($params['charset']) ? 'ISO-8859-1' : $params['charset']);  // assume all parts are same charset
        }
        // EMBEDDED MESSAGE
        // Many bounce notifications embed the original message as type 2,
        // but AOL uses type 1 (multipart), which is not handled here.
        // There are no PHP functions to parse embedded messages,
        // so this just appends the raw source to the main message.
        elseif ($p->type == 2 && $data) {
            $plainmsg .= $data;
        }

        // SUBPART RECURSION
        if ($p->parts) {
            foreach ($p->parts as $partno0 => $p2) {
                list($charset, $htmlmsg, $plainmsg, $attachments) = $this->getpart($mid, $p2, $partno . '.' . ($partno0 + 1), $charset, $htmlmsg, $plainmsg, $attachments);
            }
        }

        return array($charset, $htmlmsg, $plainmsg, $attachments);
    }

}



?>
