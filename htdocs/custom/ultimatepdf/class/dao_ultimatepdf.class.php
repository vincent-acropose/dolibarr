<?php
/* Copyright (C) 2012 Regis Houssin  	  <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2013 Philippe Grand <philippe.grand@atoo-net.com>
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

/**
 *	\file       /ultimatepdf/class/dao_ultimatepdf.class.php
 *	\ingroup    ultimatepdf
 *	\brief      ultimatepdf DAO file class 
 */


/**
 *	\class      DaoUltimatepdf
 *	\brief      ultimatepdf DAO file class
 */
class DaoUltimatepdf
{
	var $db;
	var $error;
	var $errors=array();
	//! Numero de l'erreur
	var $errno = 0;

	var $id;
	var $label;
	var $description;

	var $options=array();
	var $options_json;

	var $design=array();
	var $designs=array();

    /**
	 * 	Constructor
	 * 
	 * 	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

    /**
	 *  Add object in database
	 *  
	 *  @param		user	User that create
	 * 	@return     int		>0 if OK, <0 if KO
	 */
	function create($user)
	{
		global $conf;

		// Clean parameters
		$this->label 		= trim($this->label);
		$this->description	= trim($this->description);
		$this->options_json = json_encode($this->options);
		
		dol_syslog(get_class($this)."::create ".$this->label);
		
		$this->db->begin();
		
		$now=dol_now();
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."ultimatepdf (";
		$sql.= "label";
		$sql.= ", description";
		$sql.= ", datec";
		$sql.= ", fk_user_creat";
		$sql.= ", options";
		$sql.= ", active";
		$sql.= ", entity";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->escape($this->description)."'";
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ".$user->id;
		$sql.= ", '".$this->db->escape($this->options_json)."'";
		$sql.= ", 0";
		$sql.= ", ".$conf->entity;
		$sql.= ")";
		
		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$result  = $this->db->query ($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id (MAIN_DB_PREFIX."ultimatepdf");
		
			dol_syslog(get_class($this)."::Create success id=".$this->id);
			$this->db->commit();
            return $this->id;
		}
		else
		{
			dol_syslog(get_class($this)."::Create echec ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}

    /**
	 * 	Update object in database
	 * 
	 * 	@param		user	User that create
	 * 	@return     int		>0 if OK, <0 if KO
	 */
	function update($id, $user)
	{
		global $conf;
		
		// Clean parameters
		$this->label 		= trim($this->label);
		$this->description	= trim($this->description);
		$this->options_json = json_encode($this->options);

		dol_syslog(get_class($this)."::update id=".$id." label=".$this->label);
		
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."ultimatepdf SET";
		$sql.= " label = '" . $this->db->escape($this->label) ."'";
		$sql.= ", description = '" . $this->db->escape($this->description) ."'";
		$sql.= ", options = '" . $this->db->escape($this->options_json) ."'";
		$sql.= " WHERE rowid = " . $id;
		
		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{	
			dol_syslog(get_class($this)."::Update success id=".$id);
			$this->db->commit();
            return 1;
		}
		else
		{
			dol_syslog(get_class($this)."::Update echec ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

    /**
	 * 	Delete object in database
	 * 
	 * 	@return     int		>0 if OK, <0 if KO
	 */
	function delete($id)
	{
		$error=0;
		
		$this->db->begin();
		
		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."ultimatepdf";
			$sql.= " WHERE rowid = " . $id;
			dol_syslog(get_class($this)."::Delete sql=".$sql, LOG_DEBUG);
			if ($this->db->query($sql))
			{
				
			}
			else
			{
				$error++;
				$this->error .= $this->db->lasterror();
				dol_syslog(get_class($this)."::Delete erreur -1 ".$this->error, LOG_ERR);
			}
		}
		
		if (! $error)
		{
			dol_syslog(get_class($this)."::Delete success id=".$id);
			$this->db->commit();
            return 1;
		}
		else
		{
			dol_syslog(get_class($this)."::Delete echec ".$this->error);
			$this->db->rollback();
			return -1;
		}
	}

    /**
	 * 	Fetch object from database
	 * 
	 * 	@param		id		Object id
	 * 	@return     int		>0 if OK, <0 if KO
	 */
	function fetch($id)
	{
		global $conf,$langs,$user;
		
		if (empty($id))
			$id = 1; // For avoid errors
		
		$this->design=array();
		
		$sql = "SELECT rowid, label, description, options, active";
		$sql.= " FROM ".MAIN_DB_PREFIX."ultimatepdf";
		$sql.= " WHERE rowid = ".$id;
		
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id			= $obj->rowid;
				$this->label		= $obj->label;
				$this->description 	= $obj->description;
				$this->options		= json_decode($obj->options,true);
				$this->active		= $obj->active;
				
				return 1;
			}
			else
			{
				return -2;
			}
		}
		else
		{
			return -3;
		}
	}
	
	/**
	 *    Enable/disable design
	 *    @param	id
	 *    @param	type
	 *    @param	value
	 */
	function setDesign($id, $type='active', $value)
	{
		global $conf;
		
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."ultimatepdf";
		$sql.= " SET ".$type." = ".$value;
		$sql.= " WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::setDesign sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

    /**
	 *	List of designs
	 *
	 *	@param		int		$id		
	 *	@return		void
	 */
	function getDesigns($login=0)
	{
		global $conf, $user;
		
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."ultimatepdf";
		$sql.= " ORDER by rowid";
		
		dol_syslog(get_class($this)."::getDesigns sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;

			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				
				$objectstatic = new self($this->db);
				$ret = $objectstatic->fetch($obj->rowid);

				$this->designs[$i] = $objectstatic;

				$i++;
			}
		}
	}

    /**
	 *    Verify right
	 */
	function verifyRight($id, $userid)
	{
		global $conf;

		$sql = "SELECT count(rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup_user";
		$sql.= " WHERE fk_user=".$userid;
		$sql.= " AND entity=".$entity;

		dol_syslog(get_class($this)."::verifyRight sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			return $obj->nb;
		}
	}

    /**
	 * 	Get constants values of a design
	 *
	 * 	@param	int		$design		design id
	 * 	@return array				Array of constantes
	 */
	function getDesignConfig($design)
	{
		$const=array();

		$sql = "SELECT ".$this->db->decrypt('value')." as value";
		$sql.= ", ".$this->db->decrypt('name')." as name";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE design = ".$design;

		dol_syslog(get_class($this)."::getDesignConfig sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $this->db->num_rows($resql);
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$const[$obj->name] = $obj->value;

				$i++;
			}

			return $const;
		}
	}
}
?>
