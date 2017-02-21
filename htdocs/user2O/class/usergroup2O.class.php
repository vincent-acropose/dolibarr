<?php
/* Copyright (c) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (c) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 *	 \file       htdocs/user/class/usergroup.class.php
 *	 \brief      File of class to manage user groups
 */

require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

/**
 *	Class to manage user groups
 */
class UserGroup2O extends UserGroup
{

	
	public $office_phone;
	public $office_fax;
	


	/**
	 *	Charge un objet group avec toutes ces caracteristiques (excpet ->members array)
	 *
	 *	@param      int		$id			id du groupe a charger
	 *	@param      string	$groupname	nom du groupe a charger
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function fetch($id='', $groupname='')
	{
		global $conf;

		$sql = "SELECT g.rowid, g.entity, g.nom as name, g.note, g.datec, g.tms as datem, g.office_ips, g.office_phone,g.office_phone_alias, g.office_fax ";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
		if ($groupname)
		{
			$sql.= " WHERE g.nom = '".$this->db->escape($groupname)."'";
		}
		else
		{
			$sql.= " WHERE g.rowid = ".$id;
		}

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;
				$this->entity = $obj->entity;
				$this->name = $obj->name;
				$this->nom = $obj->name; //Deprecated
				$this->office_phone = $obj->office_phone; //
				$this->office_phone_alias = $obj->office_phone_alias; //
				$this->office_fax = $obj->office_fax; //
				$this->office_ips  = $obj->office_ips; //
				$this->note = $obj->note;
				$this->datec = $obj->datec;
				$this->datem = $obj->datem;

				$this->members=$this->listUsersForGroup();

				// Sav current LDAP Current DN
				//$this->ldap_dn = $this->_load_ldap_dn($this->_load_ldap_info(),0);
			}
			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	function CleanGrp(){
		global $conf, $user;

		$ret=array();
// var_dump(__file__); 
		$sql = "SELECT g.rowid, g.office_ips ";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g ";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ug ON (ug.fk_usergroup = g.rowid ) ";
		$sql.= " WHERE  g.office_ips <>'' ";
// 		$sql.= " AND ug.fk_user = ".$userid;
		if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
		{
			$sql.= " AND g.entity <>'' ";
		}
		else
		{
			$sql.= " AND g.entity IN (0,".$conf->entity.")";
		}
		$sql.= " GROUP BY g.rowid ORDER BY g.nom";
//  echo $sql;

		dol_syslog(get_class($this)."::listGroupsForUser sql=".$sql,LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{

		/*echo*/		  $sql2  =" DELETE FROM ".MAIN_DB_PREFIX."usergroup_user WHERE fk_usergroup = '".$obj->rowid."' ";
				  
				   $this->db->query($sql2);

			}

			$this->db->free($result);

			return $ret;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::listGroupsForUser ".$this->error, LOG_ERR);
			return -1;
		}
		
	}
	
	
	/**
	 * 	Return array of groups objects for a particular user
	 *
	 *	@param		int		$userid 	User id to search
	 * 	@return		array     			Array of groups objects
	 */
	function listGroups2OForUser($userid)
	{
		global $conf, $user;

		$ret=array();

		$sql = "SELECT g.rowid, ug.entity as usergroup_entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g,";
		$sql.= " ".MAIN_DB_PREFIX."usergroup_user as ug";
		$sql.= " WHERE ug.fk_usergroup = g.rowid AND g.office_ips  <>''";
		$sql.= " AND ug.fk_user = ".$userid;
		if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
		{
			$sql.= " AND g.entity IS NOT NULL";
		}
		else
		{
			$sql.= " AND g.entity IN (0,".$conf->entity.")";
		}
		$sql.= " ORDER BY g.nom";

		dol_syslog(get_class($this)."::listGroupsForUser sql=".$sql,LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{
				if (! array_key_exists($obj->rowid, $ret))
				{
					$newgroup=new UserGroup($this->db);
					$newgroup->fetch($obj->rowid);
					$ret[$obj->rowid]=$newgroup;
				}

				$ret[$obj->rowid]->usergroup_entity[]=$obj->usergroup_entity;
			}

			$this->db->free($result);

			return $ret;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::listGroupsForUser ".$this->error, LOG_ERR);
			return -1;
		}
	}
	
	
	/**
	 *	Charge un objet group avec toutes ces caracteristiques (excpet ->members array)
	 *
	 *	@param      int		$id			id du groupe a charger
	 *	@param      string	$groupname	nom du groupe a charger
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function fetchbyips($ip)
	{
		global $conf;

		
		$sql = "SELECT g.rowid, g.entity, g.nom as name, g.note, g.datec, g.tms as datem, g.office_ips, g.office_phone, g.office_phone_alias,g.office_fax ";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
// 		g.office_ips LIKE  '%".$ip.",%' OR
		$sql.= " WHERE  g.office_ips <>'' "; //= '".$ip."' OR g.office_ips ='".$ip."' ";
// echo $sql; 
		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result); 
			if ($num)
			{
			
					while ($i < $num)
    { 
				$i++; 
				$obj = $this->db->fetch_object($result);

				if( preg_match('#[a-z]*$#i',$obj->office_ips) )
					$dynip = gethostbyname($obj->office_ips); 
				else 
					$dynip = $obj->office_ips;
					

				if($ip == $dynip){
					$this->id = $obj->rowid;
					$this->ref = $obj->rowid;
					$this->entity = $obj->entity;
					$this->name = $obj->name;
					$this->nom = $obj->name; //Deprecated
					$this->office_phone = $obj->office_phone; //
					$this->office_phone_alias = $obj->office_phone_alias; //
					$this->office_fax = $obj->office_fax; //
					$this->office_ips  = $obj->office_ips; //
					$this->note = $obj->note;
					$this->datec = $obj->datec;
					$this->datem = $obj->datem;

					return 1; 
				}
			}
				
				// Sav current LDAP Current DN
				//$this->ldap_dn = $this->_load_ldap_dn($this->_load_ldap_info(),0);
			}
			$this->db->free($result);
			return -1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}
	
	/**
	 *	Create group into database
	 *
	 *	@param		int		$notrigger	0=triggers enabled, 1=triggers disabled
	 *	@return     int					<0 if KO, >=0 if OK
	 */
	function create($notrigger=0)
	{
		global $user, $conf, $langs;

		$error=0;
		$now=dol_now();

		if (! isset($this->entity)) $this->entity=$conf->entity;	// If not defined, we use default value

		$entity=$this->entity;
		if (! empty($conf->multicompany->enabled) && $conf->entity == 1) $entity=$this->entity;


			
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."usergroup (";
		$sql.= "datec";
		$sql.= ", nom";
		$sql.= ", office_phone";
		$sql.= ", office_phone_alias";
		$sql.= ", office_fax";
		$sql.= ", office_ips";
		$sql.= ", entity";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->idate($now)."'";
		$sql.= ",'".$this->db->escape($this->nom)."'";
		$sql.= ",'".$this->db->escape($this->office_phone)."'";
		$sql.= ",'".$this->db->escape($this->office_phone_alias)."'";
		$sql.= ",'".$this->db->escape($this->office_fax)."'";
		$sql.= ",'".$this->db->escape($this->office_ips)."'";
		$sql.= ",".$this->db->escape($entity);
		$sql.= ")";

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."usergroup");

			if ($this->update(1) < 0) return -2;

			if (! $notrigger)
			{
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('GROUP_CREATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers
			}

			return $this->id;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::create ".$this->error,LOG_ERR);
			return -1;
		}
	}

	/**
	 *		Update group into database
	 *
	 *      @param      int		$notrigger	    0=triggers enabled, 1=triggers disabled
	 *    	@return     int						<0 if KO, >=0 if OK
	 */
	function update($notrigger=0)
	{
		global $user, $conf, $langs;

		$error=0;

		$entity=$conf->entity;
		if(! empty($conf->multicompany->enabled) && $conf->entity == 1)
		{
			$entity=$this->entity;
		}
		
// 		$this->office_ips = trim($this->office_ips);
// 		if(substr($this->office_ips,-1) !=',')
// 			$this->office_ips.=',';
			
			
		$sql = "UPDATE ".MAIN_DB_PREFIX."usergroup SET ";
		$sql.= " nom = '" . $this->db->escape($this->nom) . "'";
		$sql.= ", office_phone = '" . $this->db->escape($this->office_phone) . "'";
		$sql.= ", office_phone_alias = '" . $this->db->escape($this->office_phone_alias) . "'";
		$sql.= ", office_fax = '" . $this->db->escape($this->office_fax) . "'";
		$sql.= ", office_ips = '" . $this->db->escape($this->office_ips) . "'";
		$sql.= ", entity = " . $this->db->escape($entity);
		$sql.= ", note = '" . $this->db->escape($this->note) . "'";
		$sql.= " WHERE rowid = " . $this->id;

		dol_syslog(get_class($this)."::update sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if (! $notrigger)
			{
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('GROUP_MODIFY',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers
			}

			if (! $error) return 1;
			else return -$error;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}




	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
	 */
	function initAsSpecimen()
	{
		global $conf, $user, $langs;

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;

		$this->nom='DOLIBARR GROUP SPECIMEN';
		$this->office_phone='';
		$this->office_phone_alias='';
		$this->office_fax='';
		$this->office_ips='';
		$this->note='This is a note';
		$this->datec=time();
		$this->datem=time();
		$this->members=array($user->id);	// Members of this group is just me
	}
}

