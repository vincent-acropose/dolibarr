<?php
/* Copyright (C) 2013 Florian HENRY  <florian.henry@open-concept.pro>
 * Copyright (C) 2014 Philippe GRAND <philippe.grand@atoo-net.com>
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
 *  \file       ultimatepdf/class/ordermergedpdf.class.php
 *  \ingroup    ultimatepdf
 *  \brief      This file is an CRUD class file (Create/Read/Update/Delete)
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *	Put here description of your class
 */
class Ordermergedpdf extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='order_merged_pdf';			//!< Id that identify managed objects
	var $table_element='order_merged_pdf';		//!< Name of table without prefix where object is stored

    var $id;
    
	var $fk_commande;
	var $file_name;
	var $fk_user_author;
	var $fk_user_mod;
	var $datec='';
	var $tms='';
	var $import_key;
	var $lang;
	
	var $lines=array();

    


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
        
		if (isset($this->fk_commande)) $this->fk_commande=trim($this->fk_commande);
		if (isset($this->file_name)) $this->file_name=trim($this->file_name);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);
		if (isset($this->lang)) $this->lang=trim($this->lang);
		if (isset($this->import_key)) $this->import_key=trim($this->import_key);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."order_merged_pdf(";
		
		$sql.= "fk_commande,";
		$sql.= "file_name,";
		$sql.= "fk_user_author,";
		$sql.= "fk_user_mod,";
		$sql.= "datec";

		
        $sql.= ") VALUES (";
        
		$sql.= " ".(! isset($this->fk_commande)?'NULL':"'".$this->fk_commande."'").",";
		$sql.= " ".(! isset($this->file_name)?'NULL':"'".$this->db->escape($this->file_name)."'").",";
		$sql.= " ".$user->id.",";
		$sql.= " ".$user->id.",";
		$sql.= " '".$this->db->idate(dol_now())."'";

        
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."order_merged_pdf");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
			}
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


    /**
     *  Load object in memory from the database
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs,$conf;
    	
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " t.fk_commande,";
		$sql.= " t.file_name,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.datec,";
		$sql.= " t.tms,";
		$sql.= " t.import_key";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."order_merged_pdf as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                
				$this->fk_commande = $obj->fk_commande;
				$this->file_name = $obj->file_name;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->import_key = $obj->import_key;

                
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
     *  Load object in memory from the database
     *
     *  @param	int		$id    Id object
     *  @param	string	$lang  lang string id
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch_by_order($order_id, $lang='')
    {
    	global $langs,$conf;
    	
    	$sql = "SELECT";
    	$sql.= " t.rowid,";
    
    	$sql.= " t.fk_commande,";
    	$sql.= " t.file_name,";
    	$sql.= " t.fk_user_author,";
    	$sql.= " t.fk_user_mod,";
    	$sql.= " t.datec,";
    	$sql.= " t.tms,";
    	$sql.= " t.import_key";
    
    
    	$sql.= " FROM ".MAIN_DB_PREFIX."order_merged_pdf as t";
    	$sql.= " WHERE t.fk_commande = ".$order_id;
    
    	dol_syslog(get_class($this)."::fetch_by_order sql=".$sql, LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		if ($this->db->num_rows($resql))
    		{
    			while($obj = $this->db->fetch_object($resql)) {
    
    				$line = new OrdermergedpdforderLine();
    				
	    			$line->id    = $obj->rowid;
	    
	    			$line->fk_commande = $obj->fk_commande;
	    			$line->file_name = $obj->file_name;
	    			$line->fk_user_author = $obj->fk_user_author;
	    			$line->fk_user_mod = $obj->fk_user_mod;
	    			$line->datec = $this->db->jdate($obj->datec);
	    			$line->tms = $this->db->jdate($obj->tms);
	    			$line->import_key = $obj->import_key;
	    			$this->lines[$obj->file_name]=$line;
			
    			}
    
    
    		}
    		$this->db->free($resql);
    
    		return 1;
    	}
    	else
    	{
    		$this->error="Error ".$this->db->lasterror();
    		dol_syslog(get_class($this)."::fetch_by_order ".$this->error, LOG_ERR);
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
        
		if (isset($this->fk_commande)) $this->fk_commande=trim($this->fk_commande);
		if (isset($this->file_name)) $this->file_name=trim($this->file_name);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);
		if (isset($this->lang)) $this->lang=trim($this->lang);
		

        

		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."order_merged_pdf SET";
        
		$sql.= " fk_commande=".(isset($this->fk_commande)?$this->fk_commande:"null").",";
		$sql.= " file_name=".(isset($this->file_name)?"'".$this->db->escape($this->file_name)."'":"null").",";
		$sql.= " fk_user_mod=".$user->id;

        
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
	    	}
		}

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

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action calls a trigger.

		        //// Call triggers
		        //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
			}
		}

		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."order_merged_pdf";
    		$sql.= " WHERE rowid=".$this->id;

    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

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
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that deletes
	 *	@param  int		$order_id	 order_id
	 *  @param  string	$lang_id	 language
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete_by_order($user, $order_id, $lang_id='',  $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
	
		$this->db->begin();
	
		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
	
				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}
	
		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."order_merged_pdf";
			$sql.= " WHERE fk_commande=".$order_id;
	
			dol_syslog(get_class($this)."::delete sql=".$sql);
			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}
	
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
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new Ordermergedpdf($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{


		}

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
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
		
		$this->fk_commande='';
		$this->file_name='';
		$this->fk_user_author='';
		$this->fk_user_mod='';
		$this->datec='';
		$this->tms='';
		$this->import_key='';

		
	}

}

class OrdermergedpdforderLine{
	var $id;
	
	var $fk_commande;
	var $file_name;
	var $fk_user_author;
	var $fk_user_mod;
	var $datec='';
	var $tms='';
	var $import_key;

	function __construct() {
		return 1;
	}

}
