<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \file       htdocs/core/triggers/interface_90_all_Demo.class.php
 *  \ingroup    core
 *  \brief      Fichier de demo de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */


/**
 *  Class of triggers for demo module
 */
class InterfaceAddRelanceEvent
{
    var $db;
    
    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "akteos";
        $this->description = "Triggers to add event into Agenda when Resend date on proposal is set";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
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

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
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
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
    
 
        // Proposals
        if ($action == 'PROPAL_MODIFY')
        {
        	$now=dol_now();
        	
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            //dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". object=".var_export($object,true));
            if (!empty($object->array_options['options_pr_date_relance'])) {
            	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". date relance=".$object->array_options['options_pr_date_relance']);
                      	
            	
            	require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
            	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
            	$societeforaction=new Societe($this->db);
            	if ($object->socid > 0)    $societeforaction->fetch($object->socid);
            	
            	// Insertion action
            	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
            	
            	//Find if this event already exits
            	$sql = "SELECT id FROM ".MAIN_DB_PREFIX."actioncomm WHERE code='AC_OTH' "; 
            	$sql .= " AND fk_element=".$object->id;
            	$sql .= " AND elementtype='".$object->element."' AND label='Relance Proposition Commerciale' ";
            	$sql .= " AND datep='".$this->db->idate($object->array_options['options_pr_date_relance'])."'";
            	
            	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__." sql=".$sql);
            	
            	$resql=$this->db->query($sql);
            	if ($resql && $this->db->num_rows($resql)==0) {
            	
            		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__." => No row found then do event insert");
            	
	            	$actioncomm = new ActionComm($this->db);
	            	$actioncomm->type_code   = 'AC_OTH';		// code of parent table llx_c_actioncomm (will be deprecated)
	            	//$actioncomm->code='AC_OTH';
	            	$actioncomm->label       = 'Relance Proposition Commerciale';
	            	$actioncomm->note        = '';
	            	$actioncomm->datep       = $object->array_options['options_pr_date_relance'];
	            	$actioncomm->datef       = $object->array_options['options_pr_date_relance'];
	            	$actioncomm->durationp   = 0;
	            	$actioncomm->punctual    = 1;
	            	$actioncomm->percentage  = -1;   // Not applicable
	            	$actioncomm->societe     = $societeforaction;
	            	$actioncomm->author      = $user;   // User saving action
	            	//$actioncomm->usertodo  = $user;	// User affected to action
	            	$actioncomm->userdone    = $user;	// User doing action
	            	
	            	$actioncomm->fk_element  = $object->id;
	            	$actioncomm->elementtype = $object->element;
	            	
	            	$ret=$actioncomm->add($user);       // User qui saisit l'action
	            	
	            	if ($ret < 0) {
	            		$error ="Failed to insert : ".$actioncomm->error." ";
	            		$this->error=$error;
	            	
	            		dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
	            		return -1;
	            	}
            	}
            }
            
        }
       
       

		return 0;
    }

}
?>
