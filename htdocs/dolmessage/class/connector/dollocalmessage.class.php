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

dol_include_once('/dolmessage/class/dolmessage.class.php');
dol_include_once('/dolmessage/class/dmessage.class.php');

dol_include_once('/dolmessage/class/connector/structuremessage.listener.php');

dol_include_once('/dolmessage/class/mimeparser-2014-04-30/rfc822_addresses.php');
dol_include_once('/dolmessage/class/mimeparser-2014-04-30/mime_parser.php');

class dollocalmessage Extends DMessage {

    /**
      @brief
     */
    public function __construct($uid = 0, $charset = '', $htmlmsg = '', $plainmsg = '', $attachments = array()) {
        $this->SetUid($uid);
        $this->SetAttach($attachments);
        $this->SetCharset($charset);
        $this->SetContent($htmlmsg, $plainmsg);
        return $this;
    }

    public function SetPath($path) {
        $this->path = $path;
    }

    public function GetPath() {
        return $this->path;
    }

    /**
      @brief
      @param $objMessg object
      @return result
     */
    public function delete($group_id, $number = 1, $objMessg) {

        global $db, $user, $conf;

        $r = false;
        $dolmess = new dolmessage($db, $user);

        if ($dolmess->fetch($objMessg->GetId(), 0, true) != false) {




            $r = $dolmess->delete($group_id, $number, $objMessg->GetId());



            if ($r) {
                foreach ($objMessg->GetLinked() as $type => $list) {
                    foreach ($list as $obj) {
                        if ($type == 'societe') {
                            $societe = $obj;

                            $upload_dir = $conf->societe->multidir_output[$societe->entity] . "/" . $societe->id;

                            if (file_exists($upload_dir))
                                $r = unlink($upload_dir . $objMessg->message_id);
                        } else {
                            $type = ($type=='project')?'projet':$type;   
                            $upload_dir = $conf->$type->dir_output   . "/" . $obj->ref. "/message";

                            if (file_exists($upload_dir))
                                $r = unlink($upload_dir . $objMessg->message_id);
                        }
                    }
                }
            }
        }

        return $r;
    }

    /**
      @param $cid int customers id
     */
    public function LoadLocal($path, $message_id) {


        $this->header = new stdClass();


        $mime = new mime_parser_class;
// 	$mime2 
        /*
         * Set to 0 for parsing a single message file
         * Set to 1 for parsing multiple messages in a single file in the mbox format
         */
        $mime->mbox = 0;

        /*
         * Set to 0 for not decoding the message bodies
         */
        $mime->decode_bodies = 1;

        /*
         * Set to 0 to make syntax errors make the decoding fail
         */
        $mime->ignore_syntax_errors = 1;

        $mime->extract_addresses = 0;


        $parameters = array(
            'File' => $path . $message_id,
            /* Read a message from a string instead of a file */
            /* 'Data'=>'My message data string',              */

            /* Save the message body parts to a directory     */
            /* 'SaveBody'=>'/tmp',                            */

            /* Do not retrieve or save message body parts     */
            'SkipBody' => 0,
        );

        if (!$mime->Decode($parameters, $decoded))
            return 'MIME message decoding error: ' . $mime->error . ' at position ' . $mime->error_position . "\n";
        else {
            $attach = array();
            for ($message = 0; $message < count($decoded); $message++) {

                if ($mime->Analyze($decoded[$message], $results)) {

                    // loop forwarded message
                    if (preg_match('#Content-Type:.*multipart/alternative#', $results['Data'], $match)) {

                        $mime->ResetParserState();
                        if (!$mime->Decode(array('Data' => $results['Data']), $decoded))
                            echo 'MIME message decoding error: ' . $mime->error . ' at position ' . $mime->error_position . "\n";
                        else
                            $results = array();
                        $mime->Analyze($decoded[0], $results);
                    }

                    $m = 1;
                    preg_match('#([a-z]{3}),.([0-9]{1,2}).([a-z]{3}).([0-9]{4}).([0-9]{2}):([0-9]{2}):([0-9]{2})#i', $results['Date'], $match);

                    for ($i = 1; $i <= 12; $i++)
                        if (date('M', mktime(1, 1, 1, $i)) == $match[3])
                            $m = $i;

                    $this->header->date = date('d/m/Y H:i:s', mktime($match[5], $match[6], $match[7], $m, $match[2], $match[4]));
//                     $this->header->from_name = $results['From'][0]['name'];
//                     $this->header->from_mail = $results['From'][0]['address'];

                    $this->SetRecipient('from', @$results['From'][0]['name'] . ' <' . $results['From'][0]['address'] . '>');

                    if (empty($results['Encoding']))
                        $results['Encoding'] = 'iso-8859-1';

//                     $this->header->subject = iconv(strtoupper($results['Encoding']), "UTF-8//TRANSLIT", $results['Subject']);
                    ; //$results['Subject'];
                    if (isset($results['Subject']))
                        $this->SetSubject(iconv(strtoupper($results['Encoding']), "UTF-8//TRANSLIT", $results['Subject']));



                    if ($results['Type'] == 'text')
                        $this->plainmsg = nl2br(iconv(strtoupper($results['Encoding']), "UTF-8//TRANSLIT", $results['Data'])); // ,$results['Alternative'][0]['Data'] ) );
                    else
                        $this->htmlmsg = iconv(strtoupper($results['Encoding']), "UTF-8//TRANSLIT", $results['Data']);

                    if (isset($results['Attachments']) && is_array($results['Attachments'])) {
                        foreach ($results['Attachments'] as $key => $row)
//                             $this->attachments
                            $attach[@$row['FileName']] = new dattachment($row['Type'], $results['Encoding'], '', $row['Data']);
                        $this->SetAttach($attach);
                    }
                } else
                    echo 'MIME message analyse error: ' . $mime->error . "\n";
            }

            for ($warning = 0, Reset($mime->warnings); $warning < count($mime->warnings); Next($mime->warnings), $warning++) {
                $w = Key($mime->warnings);
                echo 'Warning: ', $mime->warnings[$w], ' at position ', $w, "\n";
            }




// 			$this->SetRecipient('from', $header->fromaddress ); 
// 			$this->SetRecipient('to', $header->senderaddress );  
        }
    }

    /**
      @brief define current id
      @param $id int value of row in lcoal db
      @return $this->id
     */
    public function SetId($id) {
        return $this->id = $id;
    }

    /**
      @brief
     */
    public function SetHeader($header) {

// 		$this->header = new stdClass();
// 		// fix 
// // 		$this->header->subject =  trim(preg_replace('/<.*>|"/', '', @iconv_mime_decode(imap_utf8($header->subject)))) ;
// 		// fix
// // 		$from = $header->from;
// // 		$this->header->from_name = @iconv_mime_decode(imap_utf8($from[0]->personal));
// // 		$this->header->from_mail = $from[0]->mailbox . "@" . $from[0]->host; 
// 		
// 		return $this->header; 
    }

}

?>
