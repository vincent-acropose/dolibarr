<?php
/* Copyright (C) 2014	Juanjo Menent  <jmenent@2byte.es>
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
 *  \file       webmail/core/triggers/interface_01_modWebmail_Webmail.class.php
 *  \ingroup    webmail
 *  \brief      Trigger file for webmail module
 */

/**
 *  Class of triggered functions for webmail module
 */
class InterfaceWebmail
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
        $this->family = "agenda";
        $this->description = "Triggers of this module add actions in agenda according to setup made in agenda setup.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->picto = 'action';
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
     *      Following properties must be filled:
     *      $object->actiontypecode (translation action code: AC_OTH, ...)
     *      $object->actionmsg (note, long text)
     *      $object->actionmsg2 (label, short text)
     *      $object->sendtoid (id of contact)
     *      $object->socid
     *      Optionnal:
     *      $object->fk_element
     *      $object->elementtype
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
		global $conf;
		
        if (empty($conf->webmail->enabled)) return 0;     // Module not active, we do nothing
		
        dol_include_once("/webmail/class/message.class.php");
        global $db;
        
		$ok=0;

		if($action=='CONTACT_CREATE')
		{
			
			$mail = new Message($db);
			
			$sender=$object->email;
			if(!empty($sender)){
			
				$sql ="SELECT rowid";
				$sql.=" FROM ".MAIN_DB_PREFIX."webmail_mail";
				$sql.=" WHERE `from` LIKE'%".$sender."%' OR `to` LIKE '%".$sender."%'";
				$sql.=" AND fk_contact=0";
				$sql.=" AND entity=".$conf->entity;
	
				$resql = $db->query($sql);
				
				if ($resql)
				{
					$i=0;
					$num = $db->num_rows($resql);
					while ($i < $num)
					{
						$objp = $db->fetch_object($resql);
					
						$mail->fetch($objp->rowid);
						$mail->set_contact($object->id,$object->socid);
						$i++;
					}
				}
			}
			
		}
		
		
        if (substr($action, -10) == 'SENTBYMAIL')
        {
        	
        	$subject = GETPOST('subject','alpha');
        	$message = GETPOST('message');
        	$from = GETPOST('fromname','alpha')." <".GETPOST('frommail','alpha').">";
        	
			//$sendtosocid=0;
			if (method_exists($object,"fetch_thirdparty") && $object->element != 'societe')
			{
				$object->fetch_thirdparty();
				$thirdparty=$object->thirdparty;
			}
			else if ($object->element == 'societe')
			{
				$thirdparty=$object;
			}

		
			if ($_POST['receiver'] != '-1')
			{
				if ($_POST['receiver'] == 'thirdparty') // Id of third party
				{
					$sendto = $thirdparty->name." <".$thirdparty->email.">";
				}
				else
				{
					$sendto= $thirdparty->contact_get_property(GETPOST('receiver','alpha'),'email');
				}
			}
        	
			
        	
			$clsmessage = new Message($db);
										
			$clsmessage->fk_user=trim($user->id);

			$clsmessage->subject=trim($subject);
			$clsmessage->body=trim($message);
			$clsmessage->is_outbox=1;
			$clsmessage->state_sent=1;
			$clsmessage->state_new=0;
			$clsmessage->state_reply=0;
			$clsmessage->state_forward=0;
			$clsmessage->state_wait=0;
			$clsmessage->state_spam=0;
			$clsmessage->id_correo=0;
			$clsmessage->from=trim($from);
			$clsmessage->to=trim($sendto);
			$clsmessage->cc=trim($sendtocc);
			$clsmessage->bcc=trim($sendtobcc);
			$clsmessage->datetime=dol_now();
			$clsmessage->uidl=dol_now();
					
			$clsmessage->create($user);
        	
        }

        // Add entry in event table
        if ($ok)
        {
			$now=dol_now();

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
			$actioncomm->note        = $object->actionmsg;
			$actioncomm->datep       = $now;
			$actioncomm->datef       = $now;
			$actioncomm->durationp   = 0;
			$actioncomm->punctual    = 1;
			$actioncomm->percentage  = -1;   // Not applicable
			$actioncomm->contact     = $contactforaction;
			$actioncomm->societe     = $societeforaction;
			$actioncomm->author      = $user;   // User saving action
			//$actioncomm->usertodo  = $user;	// User affected to action
			$actioncomm->userdone    = $user;	// User doing action

			$actioncomm->fk_element  = $object->id;
			$actioncomm->elementtype = $object->element;

			$ret=$actioncomm->add($user);       // User qui saisit l'action
			if ($ret > 0)
			{
				$_SESSION['LAST_ACTION_CREATED'] = $ret;
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
?>
