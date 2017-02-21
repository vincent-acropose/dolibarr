<?php
/* Copyright (C) 2015		Charlie BENKE		<charlie@patas-monkey.com>
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
 *	\file       htdocs/myfield/class/myfield.class.php
 *	\ingroup    base
 *	\brief      File of class to manage field visibility
 */


/**
 *	Class to manage categories
 */
class Myfield extends CommonObject
{
	public $element='myfield';
	public $table_element='myfield';

	var $label;
	var $context;
	var $author;
	var $active;
	var $color;
	var $initvalue;
	var $replacement;
	var $compulsory;
	var $sizefield;
	var $formatfield;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}



	/**
	 * 	Load field into memory from database
	 *
	 * 	@param		int		$rowid	code of the field
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		global $conf;

		$sql = "SELECT rowid, label, context, author, active, color, initvalue,";
		$sql.= " replacement, compulsory, sizefield, formatfield";
		$sql.= " FROM ".MAIN_DB_PREFIX."myfield";
		$sql.= " WHERE rowid = ".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);

				$this->rowid		= $res['rowid'];
				$this->label		= $res['label'];
				$this->context		= $res['context'];
				$this->author		= $res['author'];
				$this->active		= $res['active'];
				$this->color		= $res['color'];
				$this->initvalue	= $res['initvalue'];
				$this->replacement	= $res['replacement'];
				$this->compulsory	= $res['compulsory'];
				$this->sizefield	= $res['sizefield'];
				$this->formatfield	= $res['formatfield'];

				$this->db->free($resql);

				return 1;
			}
			else
				return 0;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Add mylist into database
	 *
	 * 	@param	User	$user		Object user
	 * 	@return	int 				-1 : erreur SQL

	 */
	function create($user='')
	{
		global $conf, $langs, $user;
		$langs->load('myfield@myfield');

		$error=0;

		$this->label=trim($this->label);
		$this->context=trim($this->context);
		$this->initvalue=trim($this->initvalue);
		$this->replacement=trim($this->replacement);
		$this->compulsory=($this->compulsory=='yes'?'true':'false');
		$this->sizefield=trim($this->sizefield);
		$this->formatfield=trim($this->formatfield);

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."myfield (";
		$sql.= " label,";
		$sql.= " context,";
		$sql.= " author,";
		$sql.= " active,";
		$sql.= " color,";
		$sql.= " initvalue,";
		$sql.= " replacement,";
		$sql.= " compulsory,";
		$sql.= " sizefield,";
		$sql.= " formatfield";
		$sql.= ") VALUES (";
		$sql.= " '".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->escape($this->context)."'";
		$sql.= ", '".$this->db->escape($this->author)."'";
		$sql.= ", ".$this->active;
		$sql.= ", '".$this->db->escape($this->color)."'";
		$sql.= ", '".$this->db->escape($this->initvalue)."'";
		$sql.= ", '".$this->db->escape($this->replacement)."'";
		$sql.= ", ".$this->db->escape($this->compulsory);
		if ($this->sizefield)
			$sql.= ", ".$this->db->escape($this->sizefield);
		else
			$sql.= ", null";
		$sql.= ", '".$this->db->escape($this->formatfield)."'";

		$sql.= ")";

		dol_syslog(get_class($this).'::create sql='.$sql);
		if ($this->db->query($sql))
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."myfield");
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::create error ".$this->error." sql=".$sql, LOG_ERR);
			$this->db->rollback();
			return 0;
		}
	}

	/**
	 * 	Update myfield
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 *          					-2 : invalid category
	 */
	function update($user='')
	{
		global $conf, $langs;
		$this->db->begin();

		$error=0;

		// si il y a un onglet on fait de meme 
		$sql = "UPDATE ".MAIN_DB_PREFIX."myfield";
		$sql.= " SET label = '".$this->db->escape($this->label)."'";
		$sql.= ", context ='".$this->db->escape($this->context)."'";
		$sql.= ", author ='".$this->db->escape($this->author)."'";
		$sql.= ", active =".$this->db->escape($this->active);
		$sql.= ", color ='".$this->db->escape($this->color)."'";
		$sql.= ", initvalue = '".$this->db->escape($this->initvalue)."'";
		$sql.= ", replacement = '".$this->db->escape($this->replacement)."'";

		$sql.= ", compulsory =".$this->db->escape($this->compulsory);
		if ($this->sizefield)
			$sql.= ", sizefield =".$this->db->escape($this->sizefield);
		else
			$sql.= ", sizefield =null";
		$sql.= ", formatfield ='".$this->db->escape($this->formatfield)."'";

		$sql.= " WHERE rowid =".$this->rowid;
		//print $sql;
		dol_syslog(get_class($this)."::update sql=".$sql);
		
		if ($this->db->query($sql))
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Delete a field from database
	 *
	 * 	@param	User	$user		Object user that ask to delete
	 *	@return	void
	 */
	function delete($rowid)
	{
		global $conf, $langs;

		$error=0;

		dol_syslog(get_class($this)."::delete");

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."myfield";
		$sql .= " WHERE rowid = ".$rowid;

		if (!$this->db->query($sql))
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}
	}

	/**
	 * 	Retourne toutes les champs dans un tableau
	 *
	 *	@return	array					Tableau d'objet list
	 */
	function get_all_myfield($context='')
	{
		global $conf;
		
		$sql = "SELECT rowid, label, context, author, color, active, initvalue,";
		$sql.= " replacement, compulsory, sizefield, formatfield";
		$sql.= " FROM ".MAIN_DB_PREFIX."myfield";
		
		// si il y a des context de précisé
		if ($context)
		{
			$sql.= " WHERE context=''";
			$tblcontext=explode(":", $context);
			if ($conf->global->MYFIELD_CONTEXT_VIEW =="1")
				var_dump($tblcontext);
			foreach ($tblcontext as $contextwhere)
				$sql .= " OR context like '%".$contextwhere."%' "; ;
		}
		//print $sql;
		$res = $this->db->query($sql);
		if ($res)
		{
			$cats = array ();
			while ($rec = $this->db->fetch_array($res))
			{
				$cat = array ();
				$cat['rowid']		= $rec['rowid'];
				$cat['label']		= $rec['label'];
				$cat['context']		= $rec['context'];
				$cat['author']		= $rec['author'];
				$cat['active']		= $rec['active'];
				$cat['color']		= $rec['color'];
				$cat['initvalue']	= $rec['initvalue'];
				$cat['replacement']	= $rec['replacement'];
				$cat['compulsory']	= $rec['compulsory'];
				$cat['sizefield']	= $rec['sizefield'];
				$cat['formatfield']	= $rec['formatfield'];
				$cats[$rec['rowid']] = $cat;
			}
			return $cats;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	function getUserSpecialsRights($myFielId, $user) 
	{
		$array_return = array();

		if (!empty($user->id)) {
			//If user is admin he get all rights by default
			if ($user->admin) {
				$array_return = array('read'=>1, 'write'=>1);
			} else {
				require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
				$usr_group = new UserGroup($this->db);
				$group_array=$usr_group->listGroupsForUser($user->id);
				if (is_array($group_array) && count($group_array)>0) {

					$sql = 'SELECT rights FROM '.MAIN_DB_PREFIX.'myfield_usergroup_rights WHERE fk_myfield='.$myFielId;
					$sql .= ' AND fk_usergroup IN ('.implode(", ", array_keys($group_array)).')';

					dol_syslog(get_class($this).'::getUserSpecialsRights sql='.$sql);
					$resql=$this->db->query($sql);
					if ($resql)
					{
						$array_return=array('read'=>0, 'write'=>0);
						$nump = $this->db->num_rows($resql);
						if ($nump)
						{
							$array_return['read']=1;
							while ($obj = $this->db->fetch_object($resql)) //User in in group that allow write
								if ($obj->rights=='U' ) $array_return['write']=1;
						}
						$this->db->free($resql);
					}
					else
						print $this->db->error();
				}
				else	// no usergroup : open bar
					$array_return = array('read'=>1, 'write'=>1);
			}
		}
// print_r($array_return);
		return $array_return;
	}
}
?>