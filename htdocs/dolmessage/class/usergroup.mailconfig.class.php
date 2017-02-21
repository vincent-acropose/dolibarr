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
 *  \file       dev/skeletons/usergroupwebmail.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 * 				Initialy built by build_class_from_table on 2013-03-25 22:50
 */


/**
 * 	Put here description of your class
 */
class UserGroupmailconfig
{

    public $db;       //!< To store db handler
    /**
			@var 
    */
    public $error;       //!< To return error code (or message)
    /**
			@var 
    */
    public $errors = array();    //!< To return several error codes (or messages)
    /**
			@var 
    */
    public $id;
    /**
			@var 
    */
    public $imap_login;
    /**
			@var 
    */
    public $imap_password;
    /**
			@var 
    */
    public $imap_host;
    /**
			@var 
    */
    public $imap_port;
    /**
			@var 
    */
    public $imap_ssl;
    /**
			@var 
    */
    public $imap_ssl_novalidate_cert;
    /**
			@var 
    */
    public $fk_usergroup;
    /**
			@var 
    */
    public $number = 1;
    /**
			@var 
    */
    public $title = '';
    
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
    function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        if (isset($this->imap_login))
                $this->imap_login = trim($this->imap_login);
        if (isset($this->imap_password))
                $this->imap_password = trim($this->imap_password);
        if (isset($this->imap_host))
                $this->imap_host = trim($this->imap_host);
        if (isset($this->imap_port))
                $this->imap_port = trim($this->imap_port);
        if (isset($this->imap_ssl))
                $this->imap_ssl = trim($this->imap_ssl);
        if (isset($this->$imap_ssl_novalidate_cert))
                $this->$imap_ssl_novalidate_cert = trim($this->$imap_ssl_novalidate_cert);
        if (isset($this->fk_usergroup)) $this->fk_usergroup = trim($this->fk_usergroup);



        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "usergroupwebmail(";
				$sql.= "number,";
				$sql.= "title,";
        $sql.= "imap_login,";
        $sql.= "imap_password,";
        $sql.= "imap_host,";
        $sql.= "imap_port,";
        $sql.= "imap_ssl,";
        $sql.= "imap_ssl_novalidate_cert,";
        $sql.= "fk_usergroup";
        $sql.= ") VALUES (";
        $sql.= " " . (!isset($this->number) ? '1' : "'" . (int)$this->number . "'") . ",";
        $sql.= " " . (!isset($this->title) ? 'NULL' : "'" . $this->db->escape($this->title) . "'") . ",";
        $sql.= " " . (!isset($this->imap_login) ? 'NULL' : "'" . $this->db->escape($this->imap_login) . "'") . ",";
        $sql.= " " . (!isset($this->imap_password) ? 'NULL' : "'" . $this->db->escape($this->imap_password) . "'") . ",";
        $sql.= " " . (!isset($this->imap_host) ? 'NULL' : "'" . $this->db->escape($this->imap_host) . "'") . ",";
        $sql.= " " . (!isset($this->imap_port) ? 'NULL' : "'" . $this->db->escape($this->imap_port) . "'") . ",";
        $sql.= " " . (!isset($this->imap_ssl) ? '0' : "'" . $this->db->escape($this->imap_ssl) . "'") . ",";
        $sql.= " " . (!isset($this->imap_ssl_novalidate_cert) ? '0' : "'" . $this->db->escape($this->imap_ssl_novalidate_cert) . "'") . ",";
        $sql.= " " . (!isset($this->fk_usergroup) ? 'NULL' : "'" . $this->fk_usergroup . "'") . "";

// echo 
        $sql.= ")";
//  echo $sql; 
        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql)
        {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "usergroupwebmail");

            if (!$notrigger)
            {
            }
        }


        if ($error)
        {
            foreach ($this->errors as $errmsg)
            {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
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
     *  @param	int		$fk_usergroup    id of user to load
     *  @param int $number indice 
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch_from_usergroup($fk_usergroup, $number=1)
    {
        global $langs;
        $sql = "SELECT";
        $sql.= " t.rowid,";
				$sql.= " t.number,";
				$sql.= " t.title,";
        $sql.= " t.imap_login,";
        $sql.= " t.imap_password,";
        $sql.= " t.imap_host,";
        $sql.= " t.imap_port,";
        $sql.= " t.imap_ssl,";
        $sql.= " t.imap_ssl_novalidate_cert,";
        $sql.= " t.fk_usergroup";


        $sql.= " FROM " . MAIN_DB_PREFIX . "usergroupwebmail as t";
        $sql.= " WHERE t.fk_usergroup = '" . (int)$fk_usergroup."' AND t.number= '".(int)$number."' ";
// echo $sql; 
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
								$this->number= $obj->number;
								
								$this->title= $obj->title;
                $this->imap_login = $obj->imap_login;
                $this->imap_password = $obj->imap_password;
                $this->imap_host = $obj->imap_host;
                $this->imap_port = $obj->imap_port;
                $this->imap_ssl = $obj->imap_ssl;
                $this->imap_ssl_novalidate_cert = $obj->imap_ssl_novalidate_cert;
                $this->fk_usergroup = $obj->fk_usergroup;
                
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch_from_user " . $this->error,
                                 LOG_ERR);
            return -1;
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

        $sql.= " t.imap_login,";
        $sql.= " t.imap_password,";
        $sql.= " t.imap_host,";
        $sql.= " t.imap_port,";
        $sql.= " t.imap_ssl,";
        $sql.= " t.imap_ssl_novalidate_cert,";
        $sql.= " t.fk_usergroup";


        $sql.= " FROM " . MAIN_DB_PREFIX . "usergroupwebmail as t";
        $sql.= " WHERE t.rowid = " . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;

                $this->imap_login = $obj->imap_login;
                $this->imap_password = $obj->imap_password;
                $this->imap_host = $obj->imap_host;
                $this->imap_port = $obj->imap_port;
                $this->imap_ssl = $obj->imap_ssl;
                $this->imap_ssl_novalidate_cert = $obj->imap_ssl_novalidate_cert;
                $this->fk_usergroup = $obj->fk_usergroup;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
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
    function update($user = 0, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        if (isset($this->imap_login)) $this->imap_login = trim($this->imap_login);
        if (isset($this->imap_password)) $this->imap_password = trim($this->imap_password);
        if (isset($this->imap_host)) $this->imap_host = trim($this->imap_host);
        if (isset($this->imap_port)) $this->imap_port = trim($this->imap_port);
        if (isset($this->imap_ssl)) $this->imap_ssl = trim($this->imap_ssl);
        if (isset($this->imap_ssl_novalidate_cert)) $this->imap_ssl_novalidate_cert = trim($this->imap_ssl_novalidate_cert);
        if (isset($this->fk_usergroup)) $this->fk_usergroup = trim($this->fk_usergroup);




        $sql = "UPDATE " . MAIN_DB_PREFIX . "usergroupwebmail SET";
				
				$sql.= " title=" . (isset($this->title) ? "'" . $this->db->escape($this->title) . "'" : "null") . ",";
        $sql.= " imap_login=" . (isset($this->imap_login) ? "'" . $this->db->escape($this->imap_login) . "'" : "null") . ",";
        $sql.= " imap_password=" . (isset($this->imap_password) ? "'" . $this->db->escape($this->imap_password) . "'" : "null") . ",";
        $sql.= " imap_host=" . (isset($this->imap_host) ? "'" . $this->db->escape($this->imap_host) . "'" : "null") . ",";
        $sql.= " imap_port=" . (isset($this->imap_port) ? "'" . $this->db->escape($this->imap_port) . "'" : "null") . ",";
        $sql.= " imap_ssl=" . (isset($this->imap_ssl) ? "'" . $this->db->escape($this->imap_ssl) . "'" : "null") . ",";
        $sql.= " imap_ssl_novalidate_cert=" . (isset($this->imap_ssl_novalidate_cert)  ? "'" . $this->db->escape($this->imap_ssl_novalidate_cert) . "'" : "null") . ",";
        $sql.= " fk_usergroup=" . (isset($this->fk_usergroup) ? $this->fk_usergroup : "null") . "";
        $sql.= " WHERE rowid='" . $this->id."' and number='".$this->number."' ";
// echo $sql; 
        $this->db->begin();

        dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql)
        {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error)
        {
            if (!$notrigger)
            {
            }
        }


        if ($error)
        {
            foreach ($this->errors as $errmsg)
            {
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
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
     * 	@param  User	$user        User that deletes
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return	int					 <0 if KO, >0 if OK
     */
    function delete($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $this->db->begin();

        if (!$error)
        {
            if (!$notrigger)
            {
            }
        }

        if (!$error)
        {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "usergroupwebmail";
            $sql.= " WHERE rowid=" . $this->id;

            dol_syslog(get_class($this) . "::delete sql=" . $sql);
            $resql = $this->db->query($sql);
            if (!$resql)
            {
                $error++;
                $this->errors[] = "Error " . $this->db->lasterror();
            }
        }

        if ($error)
        {
            foreach ($this->errors as $errmsg)
            {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error.=($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        }
        else
        {
            $this->db->commit();
            return 1;
        }
    }

    /**
     * 	Load an object from its id and create a new one in database
     *
     * 	@param	int		$fromid     Id of object to clone
     * 	@return	int					New id of clone
     */
    function createFromClone($fromid)
    {
        global $user, $langs;

        $error = 0;

        $object = new Usermailboxconfig($this->db);

        $this->db->begin();


        $object->fetch($fromid);
        $object->id = 0;
        $object->statut = 0;


        $result = $object->create($user);


        if ($result < 0)
        {
            $this->error = $object->error;
            $error++;
        }

        if (!$error)
        {
            
        }

        if (!$error)
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
     * 	Initialise object with example values
     * 	Id must be 0 if object instance is a specimen
     *
     * 	@return	void
     */
    function initAsSpecimen(){
        $this->id = 0;

        $this->imap_login = '';
        $this->imap_password = '';
        $this->imap_host = '';
        $this->imap_port = '';
        $this->imap_ssl = '';
        $this->imap_ssl_novalidate_cert = '';
        $this->fk_usergroup = '';
    }

    /**
			@brief 
    */
    function get_ref(){
        return "{" . $this->imap_host . "}";
    }

    
    /**
			@brief 
    */
    function get_connector_url(){
        $imap_connector_url = '{' . $this->imap_host . ':' . $this->imap_port;
        if ($this->imap_ssl)
        {
            if ($this->imap_ssl_novalidate_cert)
            {
                $imap_connector_url .= '/ssl/novalidate-cert';
            }
            else
            {
                $imap_connector_url .= '/ssl';
            }
        }
        $imap_connector_url .= '}';

        return $imap_connector_url;
    }

}

?>
