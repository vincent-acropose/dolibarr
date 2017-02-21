<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \file       htdocs/core/triggers/interface_20_all_Logevents.class.php
 *  \ingroup    core
 *  \brief      Trigger file for
 */


/**
 *  Class of triggers for security events
 */
class InterfaceGroupDynamic
{
    var $db;
    var $error;

    var $date;
    var $duree;
    var $texte;
    var $desc;

    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "module";
        $this->description = "Triggers of this module allows to add security event records inside Dolibarr.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->picto = 'technic';
    }

    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @param  string		$entity     Value for instance of data (Always 1 except if module MultiCompany is installed)
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
    function run_trigger($action,$object,$user,$langs,$conf,$entity=1)
    {


    	$key='MAIN_LOGEVENTS_'.$action;
// trigger_error($action); 
// return -1; 
    	if (empty($conf->entity)) $conf->entity = $entity;  // forcing of the entity if it's not defined (ex: in login form)

        $this->date=dol_now();
        $this->duree=0;

        // Actions
        if ($action == 'USER_LOGIN')
        {


					dol_include_once('/user2O/class/usergroup2O.class.php');
					

					
					$grp = new UserGroup2O($this->db);
					$grp_res= $grp->fetchbyips($_SERVER['REMOTE_ADDR']); 

					if( $grp_res ){
						$result=$user->SetInGroup( $grp->id, (! empty($conf->multicompany->transverse_mode)? $conf->entity : $grp->entity));

					}
        }

        if ($action == 'USER_LOGOUT')
        {

					dol_include_once('/user2O/class/usergroup2O.class.php');

					$grp = new UserGroup2O($this->db);
					$grp_res= $grp->fetchbyips($_SERVER['REMOTE_ADDR']); 

					if( $grp_res ){
						$result=$user->RemoveFromGroup($grp->id, (! empty($conf->multicompany->transverse_mode)? $conf->entity : $grp->entity) );
					}
        }


		return 0;
    }

}
