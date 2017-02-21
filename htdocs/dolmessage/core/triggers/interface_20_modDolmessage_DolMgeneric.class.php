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
class InterfaceDolMGeneric
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


    	if (empty($conf->entity)) $conf->entity = $entity;  // forcing of the entity if it's not defined (ex: in login form)

		if ($action == 'PROPAL_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("propal");
            $langs->load("agenda");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProposalSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("ProposalSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
		}
		elseif ($action == 'ORDER_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("orders");
            $langs->load("agenda");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("OrderSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
		}
		elseif ($action == 'BILL_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("InvoiceSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
		}
		elseif ($action == 'FICHINTER_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("interventions");
            $langs->load("agenda");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionSentByEMail",$object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionSentByEMail",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            // Parameters $object->sendotid defined by caller
            //$object->sendtoid=0;
            $ok=1;
        }
		elseif ($action == 'SHIPPING_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("sendings");
            $langs->load("agenda");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ShippingSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("ShippingSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
		}
		elseif ($action == 'ORDER_SUPPLIER_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");
            $langs->load("orders");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierOrderSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendotid defined by caller
            //$object->sendtoid=0;
            $ok=1;
        }
		elseif ($action == 'BILL_SUPPLIER_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");
            $langs->load("orders");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierInvoiceSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierInvoiceSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
        }
        
        
        
		// Add entry in event table
        if ($ok)
        {
			$now=dol_now();

			if(isset($_SESSION['listofnames']))
			{
				$attachs=$_SESSION['listofnames'];
				if($attachs && strpos($action,'SENTBYMAIL'))
				{
					 $object->actionmsg.="\n".$langs->transnoentities("AttachedFiles").': '.$attachs;
				}
			}

            require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
            require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$contactforaction=new Contact($this->db);
            $societeforaction=new Societe($this->db);
            if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
            if ($object->socid > 0)    $societeforaction->fetch($object->socid);

			// Insertion action
			require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code   = $object->actiontypecode;		// code of parent table llx_c_actioncomm (will be deprecated)
			$actioncomm->code='AC_'.$action;
			$actioncomm->label       = $object->actionmsg2;
			$actioncomm->note        = ''; ///$object->actionmsg;
			$actioncomm->datep       = $now;
			$actioncomm->datef       = $now;
			$actioncomm->durationp   = 0;
			$actioncomm->punctual    = 1;
			$actioncomm->percentage  = -1;   // Not applicable
			$actioncomm->contact     = $contactforaction;
			$actioncomm->societe     = $societeforaction;
			$actioncomm->author      = $user;   // User saving action
			$actioncomm->usertodo    = $user;	// User action is assigned to (owner of action)
			$actioncomm->userdone    = $user;	// User doing action (deprecated, not used anymore)

			$actioncomm->fk_element  = $object->id;
			$actioncomm->elementtype = $object->element;

			$ret=$actioncomm->add($user);       // User qui saisit l'action
			
			
			
			if ($ret > 0)
			{
				$_SESSION['LAST_ACTION_CREATED'] = $ret;
				
				
// 				print_r($object); 
// 				exit; 
				
				
				// ajout des element joint 
				require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
				$langs->load('link');
				$link = new Link($this->db);
				$link->id = $ret;//GETPOST('linkid', 'int');
				$f = $link->fetch();
				if ($f)
				{
					$link->url = GETPOST('link', 'alpha');
					if (substr($link->url, 0, 7) != 'http://' && substr($link->url, 0, 8) != 'https://')
					{
						$link->url = 'http://' . $link->url;
					}
					$link->label = GETPOST('label', 'alpha');
					$res = $link->update($user);
					if (!$res)
					{
						setEventMessage($langs->trans("ErrorFailedToUpdateLink", $link->label));
					}
				}
				else
				{
					//error fetching
				}
				
				
				
				
				return 1;
			}
			else
			{
                $error ="Failed to insert event : ".$actioncomm->error." ".join(',',$actioncomm->errors);
                $this->error=$error;
                $this->errors=$actioncomm->errors;

                dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
                return -1;
			}
		}
		
		
		
		return 0;
    }

}
