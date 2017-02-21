<?php
/* Copyright (C) 2014		Juanjo Menent			<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       webmail/class/message.class.php
 *  \ingroup    webmail
 *  \brief      This file is a CRUD class file (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
dol_include_once('/webmail/lib/webmail.lib.php');


/**
 *	Put here description of your class
 */
class Message extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='message';				//!< Id that identify managed objects
	var $table_element='webmailmail';	//!< Name of table without prefix where object is stored

    var $id;
    
	var $fk_user;
	var $fk_soc;
	var $fk_contact;
	var $uidl;
	var $datetime='';
	var $size;
	var $subject;
	var $body;
	var $state_new;
	var $state_reply;
	var $state_forward;
	var $state_wait;
	var $state_spam;
	var $id_correo;
	var $is_outbox;
	var $state_sent;
	var $state_error;
	var $state_crt;
	var $state_archiv;
	var $priority;
	var $sensitivity;
	var $from;
	var $to;
	var $cc;
	var $bcc;
	var $files;

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }


    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        
		if (isset($this->fk_user)) $this->fk_user=trim($this->fk_user);
		if (isset($this->uidl)) $this->uidl=trim($this->uidl);
		if (isset($this->size)) $this->size=trim($this->size);
		if (isset($this->subject)) $this->subject=trim($this->subject);
		if (isset($this->body)) $this->body=trim($this->body);
		if (isset($this->state_new)) $this->state_new=trim($this->state_new);
		if (isset($this->state_reply)) $this->state_reply=trim($this->state_reply);
		if (isset($this->state_forward)) $this->state_forward=trim($this->state_forward);
		if (isset($this->state_wait)) $this->state_wait=trim($this->state_wait);
		if (isset($this->state_spam)) $this->state_spam=trim($this->state_spam);
		if (isset($this->id_correo)) $this->id_correo=trim($this->id_correo);
		if (isset($this->is_outbox)) $this->is_outbox=trim($this->is_outbox);
		if (isset($this->state_sent)) $this->state_sent=trim($this->state_sent);
		if (isset($this->state_error)) $this->state_error=trim($this->state_error);
		if (isset($this->state_crt)) $this->state_crt=trim($this->state_crt);
		if (isset($this->priority)) $this->priority=trim($this->priority);
		if (isset($this->sensitivity)) $this->sensitivity=trim($this->sensitivity);
		if (isset($this->from)) $this->from=trim($this->from);
		if (isset($this->to)) $this->to=trim($this->to);
		if (isset($this->cc)) $this->cc=trim($this->cc);
		if (isset($this->bcc)) $this->bcc=trim($this->bcc);
		if (isset($this->files)) $this->files=trim($this->files);

		// Check parameters
		// Put here code to add control on parameters values
		if ($this->is_outbox)
		{
			$search=$this->to;
		}
		else
		{
			$search=$this->from;
		}
		
		$typemail = search_sender($search);
		
		if(is_array($typemail))
		{	
			switch ($typemail['type']) 
			{
    			case "Third":
    				$this->fk_soc = $typemail['id'];
       				break;
    			case "Contact":
    				$this->fk_contact= $typemail['id'];
    				if ($typemail['fk_soc']) 
    				{
    					$this->fk_soc=$typemail['fk_soc'];
    				}
    				break;
			}
		}
	
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."webmail_mail(";
		
		$sql.= "fk_user,";
		$sql.= "entity,";
		if($this->fk_soc) $sql.= "fk_soc,";
		if($this->fk_contact) $sql.= "fk_contact,";
		$sql.= "uidl,";
		$sql.= "datetime,";
		$sql.= "size,";
		$sql.= "subject,";
		$sql.= "body,";
		$sql.= "state_new,";
		$sql.= "state_reply,";
		$sql.= "state_forward,";
		$sql.= "state_wait,";
		$sql.= "state_spam,";
		$sql.= "id_correo,";
		$sql.= "is_outbox,";
		$sql.= "state_sent,";
		$sql.= "state_error,";
		$sql.= "state_crt,";
		$sql.= "priority,";
		$sql.= "sensitivity,";
		$sql.= "`from`,";
		$sql.= "`to`,";
		$sql.= "cc,";
		$sql.= "bcc,";
		$sql.= "files";
		
        $sql.= ") VALUES (";
        
		$sql.= " ".(! isset($this->fk_user)?'':"'".$this->fk_user."'").",";
		$sql.= " '".$conf->entity."',";
		if($this->fk_soc) $sql.= " ".(! isset($this->fk_soc)?'':"'".$this->fk_soc."'").",";
		if($this->fk_contact) $sql.= " ".(! isset($this->fk_contact)?'':"'".$this->fk_contact."'").",";
		$sql.= " ".(! isset($this->uidl)?'-':"'".$this->db->escape($this->uidl)."'").",";
		$sql.= " ".(! isset($this->datetime) || dol_strlen($this->datetime)==0?'0':$this->db->idate($this->datetime)).",";
		$sql.= " ".(! isset($this->size)?'0':"'".$this->size."'").",";
		$sql.= " ".(! isset($this->subject)?'':"'".$this->db->escape($this->subject)."'").",";
		$sql.= " ".(! isset($this->body)?'':"'".$this->db->escape($this->body)."'").",";
		$sql.= " ".(! isset($this->state_new)?'0':"'".$this->state_new."'").",";
		$sql.= " ".(! isset($this->state_reply)?'0':"'".$this->state_reply."'").",";
		$sql.= " ".(! isset($this->state_forward)?'0':"'".$this->state_forward."'").",";
		$sql.= " ".(! isset($this->state_wait)?'0':"'".$this->state_wait."'").",";
		$sql.= " ".(! isset($this->state_spam)?'0':"'".$this->state_spam."'").",";
		$sql.= " ".(! isset($this->id_correo)?'0':"'".$this->id_correo."'").",";
		$sql.= " ".(! isset($this->is_outbox)?'0':"'".$this->is_outbox."'").",";
		$sql.= " ".(! isset($this->state_sent)?'0':"'".$this->state_sent."'").",";
		$sql.= " ".(! isset($this->state_error)?'0':"'".$this->db->escape($this->state_error)."'").",";
		$sql.= " ".(! isset($this->state_crt)?'0':"'".$this->state_crt."'").",";
		$sql.= " ".(! isset($this->priority)?'0':"'".$this->priority."'").",";
		$sql.= " ".(! isset($this->sensitivity)?'0':"'".$this->sensitivity."'").",";
		$sql.= " ".(! isset($this->from)?'':"'".$this->db->escape($this->from)."'").",";
		$sql.= " ".(! isset($this->to)?'':"'".$this->db->escape($this->to)."'").",";
		$sql.= " ".(! isset($this->cc)?'':"'".$this->db->escape($this->cc)."'").",";
		$sql.= " ".(! isset($this->bcc)?'':"'".$this->db->escape($this->bcc)."'").",";
		$sql.= " ".(! isset($this->files)?'0':"'".$this->files."'");
        
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."webmail_mail");

        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }

    function movefilestooutbox($files)
    {
    	global $conf, $user;
    	
    	$n=sizeof($files['names']);
    	if($n)
    	{
    		$dir = $conf->webmail->dir_output."/outbox/".$user->id."/".$this->uidl;
    		
    		dol_mkdir($dir);
    		$i=0;
    		while ($i<$n)
    		{
    			$temp=strpos($files['paths'][$i], 'temp');
    			if($temp)
    			{
    				dol_move($files['paths'][$i], $dir."/".$files['names'][$i]);
    			}
    			else
    			{
    				dol_copy($files['paths'][$i], $dir."/".$files['names'][$i]);
    			}
    			$i++;
    		}
    			
    	}
   	}

    /**
     *  Load object in memory from the database
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " t.fk_user,";
		$sql.= " t.fk_soc,";
		$sql.= " t.fk_contact,";
		$sql.= " t.uidl,";
		$sql.= " t.datetime,";
		$sql.= " t.size,";
		$sql.= " t.subject,";
		$sql.= " t.body,";
		$sql.= " t.state_new,";
		$sql.= " t.state_reply,";
		$sql.= " t.state_forward,";
		$sql.= " t.state_wait,";
		$sql.= " t.state_spam,";
		$sql.= " t.id_correo,";
		$sql.= " t.is_outbox,";
		$sql.= " t.state_sent,";
		$sql.= " t.state_error,";
		$sql.= " t.state_crt,";
		$sql.= " t.priority,";
		$sql.= " t.sensitivity,";
		$sql.= " t.from,";
		$sql.= " t.to,";
		$sql.= " t.cc,";
		$sql.= " t.bcc,";
		$sql.= " t.files";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."webmail_mail as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                
				$this->fk_user = $obj->fk_user;
				$this->fk_soc = $obj->fk_soc;
				$this->fk_contact = $obj->fk_contact;
				$this->uidl = $obj->uidl;
				$this->datetime = $this->db->jdate($obj->datetime);
				$this->size = $obj->size;
				$this->subject = $obj->subject;
				$this->body = $obj->body;
				$this->state_new = $obj->state_new;
				$this->state_reply = $obj->state_reply;
				$this->state_forward = $obj->state_forward;
				$this->state_wait = $obj->state_wait;
				$this->state_spam = $obj->state_spam;
				$this->id_correo = $obj->id_correo;
				$this->is_outbox = $obj->is_outbox;
				$this->state_sent = $obj->state_sent;
				$this->state_error = $obj->state_error;
				$this->state_crt = $obj->state_crt;
				$this->state_archiv = $obj->state_archiv;
				$this->priority = $obj->priority;
				$this->sensitivity = $obj->sensitivity;
				$this->from = $obj->from;
				$this->to = $obj->to;
				$this->cc = $obj->cc;
				$this->bcc = $obj->bcc;
				$this->files = $obj->files;

                
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *  Update object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        
		if (isset($this->fk_user)) $this->fk_user=trim($this->fk_user);
		if (isset($this->uidl)) $this->uidl=trim($this->uidl);
		if (isset($this->size)) $this->size=trim($this->size);
		if (isset($this->subject)) $this->subject=trim($this->subject);
		if (isset($this->body)) $this->body=trim($this->body);
		if (isset($this->state_new)) $this->state_new=trim($this->state_new);
		if (isset($this->state_reply)) $this->state_reply=trim($this->state_reply);
		if (isset($this->state_forward)) $this->state_forward=trim($this->state_forward);
		if (isset($this->state_wait)) $this->state_wait=trim($this->state_wait);
		if (isset($this->state_spam)) $this->state_spam=trim($this->state_spam);
		if (isset($this->id_correo)) $this->id_correo=trim($this->id_correo);
		if (isset($this->is_outbox)) $this->is_outbox=trim($this->is_outbox);
		if (isset($this->state_sent)) $this->state_sent=trim($this->state_sent);
		if (isset($this->state_error)) $this->state_error=trim($this->state_error);
		if (isset($this->state_crt)) $this->state_crt=trim($this->state_crt);
		if (isset($this->priority)) $this->priority=trim($this->priority);
		if (isset($this->sensitivity)) $this->sensitivity=trim($this->sensitivity);
		if (isset($this->from)) $this->from=trim($this->from);
		if (isset($this->to)) $this->to=trim($this->to);
		if (isset($this->cc)) $this->cc=trim($this->cc);
		if (isset($this->bcc)) $this->bcc=trim($this->bcc);
		if (isset($this->files)) $this->files=trim($this->files);

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."webmail_mail SET";
        
		$sql.= " fk_user=".(isset($this->fk_user)?$this->fk_user:"null").",";
		$sql.= " uidl=".(isset($this->uidl)?"'".$this->db->escape($this->uidl)."'":"null").",";
		$sql.= " datetime=".(dol_strlen($this->datetime)!=0 ? "'".$this->db->idate($this->datetime)."'" : 'null').",";
		$sql.= " size=".(isset($this->size)?$this->size:"null").",";
		$sql.= " subject=".(isset($this->subject)?"'".$this->db->escape($this->subject)."'":"null").",";
		$sql.= " body=".(isset($this->body)?"'".$this->db->escape($this->body)."'":"null").",";
		$sql.= " state_new=".(isset($this->state_new)?$this->state_new:"null").",";
		$sql.= " state_reply=".(isset($this->state_reply)?$this->state_reply:"null").",";
		$sql.= " state_forward=".(isset($this->state_forward)?$this->state_forward:"null").",";
		$sql.= " state_wait=".(isset($this->state_wait)?$this->state_wait:"null").",";
		$sql.= " state_spam=".(isset($this->state_spam)?$this->state_spam:"null").",";
		$sql.= " id_correo=".(isset($this->id_correo)?$this->id_correo:"null").",";
		$sql.= " is_outbox=".(isset($this->is_outbox)?$this->is_outbox:"null").",";
		$sql.= " state_sent=".(isset($this->state_sent)?$this->state_sent:"null").",";
		$sql.= " state_error=".(isset($this->state_error)?"'".$this->db->escape($this->state_error)."'":"null").",";
		$sql.= " state_crt=".(isset($this->state_crt)?$this->state_crt:"null").",";
		$sql.= " priority=".(isset($this->priority)?$this->priority:"null").",";
		$sql.= " sensitivity=".(isset($this->sensitivity)?$this->sensitivity:"null").",";
		$sql.= " from=".(isset($this->from)?"'".$this->db->escape($this->from)."'":"null").",";
		$sql.= " to=".(isset($this->to)?"'".$this->db->escape($this->to)."'":"null").",";
		$sql.= " cc=".(isset($this->cc)?"'".$this->db->escape($this->cc)."'":"null").",";
		$sql.= " bcc=".(isset($this->bcc)?"'".$this->db->escape($this->bcc)."'":"null").",";
		$sql.= " files=".(isset($this->files)?$this->files:"null")."";

        
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }


 	/**
	 *  Delete object in database
	 *
     *	@param  User	$user        User that deletes
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

    	$sql = "DELETE FROM ".MAIN_DB_PREFIX."webmail_mail";
    	$sql.= " WHERE rowid=".$this->id;

    	dol_syslog(get_class($this)."::delete sql=".$sql);
    	$resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				
			$email=$this->fk_user."/".$this->uidl;
			$fext=getDefault("exts/emailext",".eml").getDefault("exts/gzipext",".gz");
			
			if ($object->is_outbox)
			{
				$file = $conf->webmail->dir_output."/outbox/".$email.$fext;
			}
			else
			{
				$file = $conf->webmail->dir_output."/inbox/".$email.$fext;
			}
			
			if(file_exists($file))
			{
				dol_delete_file($file);
			}
						
			$this->db->commit();
			return 1;
		}
	}

   	/**
     *     Delete objects in database
     *      @param      User	$user        	User that create
     *      @param      Array	$toPrint	    Array with products to print
     *      @return     int         			<0 if KO, Id of created object if OK
     */
    function multidelete($user, $toDelete)
    {
    	global $conf, $langs;
		$error=0;
		require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
		$product = new Product($this->db);
    	$i=0;
    	foreach ($toDelete as $messageid)
		{
			
			$this->id = $messageid;
			
			
			$res = $this->delete($user);
			if ($res!=1)
			{
				$error++;
			}
        	else
        	{
				$i++;
        	}
			
    	}
    	if (! $error)
		{
			return $i;

		}
		else
		{
			return -1;
		}
	
    }
    
	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->fk_user='';
		$this->uidl='';
		$this->datetime='';
		$this->size='';
		$this->subject='';
		$this->body='';
		$this->state_new='';
		$this->state_reply='';
		$this->state_forward='';
		$this->state_wait='';
		$this->state_spam='';
		$this->id_correo='';
		$this->is_outbox='';
		$this->state_sent='';
		$this->state_error='';
		$this->state_crt='';
		$this->priority='';
		$this->sensitivity='';
		$this->from='';
		$this->to='';
		$this->cc='';
		$this->bcc='';
		$this->files='';

		
	}
	
	/**
	 * Set spam status
	 * @param number $mode
	 * 
	 * @return number
	 */
    function set_spam($mode=0)
    {
    	global $user;
    	
        $error=0;

        if (! $user->rights->webmail->reply)
        {
            $this->error='Permission denied';
            dol_syslog(get_class($this)."::set_spam ".$this->error, LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."webmail_mail";
        $sql.= " SET state_spam = '".$mode."'";
        if ($mode==1)
        {
        	$sql.=", state_new='0'";
        }
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_spam() sql=".$sql);
        $resql=$this->db->query($sql);
        if (! $resql)
        {
            dol_syslog(get_class($this)."::set_spam failed - sql=".$sql, LOG_ERR);
            dol_print_error($this->db);
            $error++;
        }

        if (! $error)
        {          
            $this->state_spam = $mode;
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            return -1;
        }
    }
    
	/**
	 * Set read status
	 * @param number $mode
	 * 
	 * @return number
	 */
    function set_read($mode=0)
    {
    	global $user;
    	
        $error=0;

        if (! $user->rights->webmail->reply)
        {
            $this->error='Permission denied';
            dol_syslog(get_class($this)."::set_spam ".$this->error, LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."webmail_mail";
        $sql.= " SET state_new = '0'";
        if ($mode==1)
        {
        	$sql.=", state_new='1'";
        }
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_read() sql=".$sql);
        $resql=$this->db->query($sql);
        if (! $resql)
        {
            dol_syslog(get_class($this)."::set_read failed - sql=".$sql, LOG_ERR);
            dol_print_error($this->db);
            $error++;
        }

        if (! $error)
        {          
            $this->state_new = $mode;
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            return -1;
        }
    }
    
	/**
	 * Set contact to mail
	 * @param number $mode
	 * 
	 * @return number
	 */
    function set_contact($contactid,$socid=0)
    {
    	global $user;
    	
    	if($socid<0) $socid=0;
    	
        $error=0;

        if (! $user->rights->webmail->reply)
        {
            $this->error='Permission denied';
            dol_syslog(get_class($this)."::set_contact ".$this->error, LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."webmail_mail";
        $sql.= " SET fk_contact = '".$contactid."'";
        if ($socid)
        {
        	$sql.=", fk_soc='".$socid."'";
        }
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_contact() sql=".$sql);
        $resql=$this->db->query($sql);
        if (! $resql)
        {
            dol_syslog(get_class($this)."::set_contact failed - sql=".$sql, LOG_ERR);
            dol_print_error($this->db);
            $error++;
        }

        if (! $error)
        {          
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            return -1;
        }
    }
    
	/**
	 * Set reply status
	 * @param number $mode
	 * 
	 * @return number
	 */
    function set_reply($mode=0)
    {
    	global $user;
    	
        $error=0;

        if (! $user->rights->webmail->reply)
        {
            $this->error='Permission denied';
            dol_syslog(get_class($this)."::set_reply ".$this->error, LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."webmail_mail";
        $sql.= " SET state_reply = '0'";
        if ($mode==1)
        {
        	$sql.=", state_reply='1'";
        }
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_reply() sql=".$sql);
        $resql=$this->db->query($sql);
        if (! $resql)
        {
            dol_syslog(get_class($this)."::set_reply failed - sql=".$sql, LOG_ERR);
            dol_print_error($this->db);
            $error++;
        }

        if (! $error)
        {          
            $this->state_reply = $mode;
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            return -1;
        }
    }
    
    /**
	 * Set archived status
	 * @param number $mode
	 * 
	 * @return number
	 */
 	function set_archiv($mode=0)
    {
    	global $user;
    	
        $error=0;

        if (! $user->rights->webmail->reply)
        {
            $this->error='Permission denied';
            dol_syslog(get_class($this)."::set_archiv ".$this->error, LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."webmail_mail";
        $sql.= " SET state_archiv = '".$mode."'";
        if ($mode==1)
        {
        	$sql.=", state_new='0'";
        }
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_archiv() sql=".$sql);
        $resql=$this->db->query($sql);
        if (! $resql)
        {
            dol_syslog(get_class($this)."::set_archiv failed - sql=".$sql, LOG_ERR);
            dol_print_error($this->db);
            $error++;
        }

        if (! $error)
        {          
            $this->state_archiv = $mode;
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            return -1;
        }
    }
	
  	/**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int			$withpicto      Add picto into link
     *	@param      int			$option         Where point the link (0=> main card, 1,2 => shipment)
     *	@param      int			$max          	Max length to show
     *	@param      int			$short			Use short labels
     *	@return     string          			String with URL
     */
    function getNomUrl($withpicto=0,$option=0,$max=0,$short=0)
    {
        global $conf, $langs;

        $result='';
		$url = dol_buildpath('/webmail/message.php',1).'?id='.$this->id;

        if ($short) return $url;

        $linkstart = '<a href="'.$url.'">';
        $linkend='</a>';

        $picto='email';
        $label=$langs->trans("ShowEmail").': '.$this->subject;

        if ($withpicto) $result.=($linkstart.img_object($label,$picto).$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$linkstart.$this->subject.$linkend;
        return $result;
    }
    
	function LibStatut($statut)
	{
		global $langs;
		$langs->load("webmail@webmail");
		
		if ($statut==1)//Leido
		{
			return img_picto($langs->trans('MailStatus'.$statut),'statut8').' '.$langs->trans("MailStatus".$statut);
		}
		if ($statut==0)//No leido
		{
			return img_picto($langs->trans('MailStatus'.$statut),'statut6').' '.$langs->trans("MailStatus".$statut);
		}
		if ($statut==2)//Reply
		{
			return img_picto($langs->trans('MailStatus'.$statut),'statut4').' '.$langs->trans("MailStatus".$statut);
		}
		if ($statut==3)//Spam
		{
			return img_picto($langs->trans('MailStatus'.$statut),'statut5').' '.$langs->trans("MailStatus".$statut);
		}
		
		if ($statut==4)//Send
		{
			return img_picto($langs->trans('MailStatus'.$statut),'statut4').' '.$langs->trans("MailStatus".$statut);
		}
	}
}
