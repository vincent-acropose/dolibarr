<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This file is an example to follow to add your own email selector inside
 * the Dolibarr email tool.
 * Follow instructions given in README file to know what to change to build
 * your own emailing list selector.
 * Code that need to be changed in this file are marked by "CHANGE THIS" tag.
 */

/**
 *    	\file       htdocs/core/modules/mailings/example.modules.php
 *		\ingroup    mailing
 *		\brief      Example file to provide a list of recipients for mailing module
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


// CHANGE THIS: Class name must be called mailing_xxx with xxx=name of your selector

/**
	    \class      mailing_example
		\brief      Class to manage a list of personalised recipients for mailing feature
*/
class mailing_societes extends MailingTargets
{
    // CHANGE THIS: Put here a name not already used
    var $name='societes';
    // CHANGE THIS: Put here a description of your selector module.
    // This label is used if no translation is found for key MailingModuleDescXXX where XXX=name is found
    var $desc='Contacts de tiers par tiers spécifique';
	// CHANGE THIS: Set to 1 if selector is available for admin users only
    var $require_admin=0;

    var $require_module=array();
    var $picto='';
    var $db;


    // CHANGE THIS: Constructor name must be called mailing_xxx with xxx=name of your selector
	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
    function __construct($db)
    {
        $this->db=$db;
    }


    /**
     *  This is the main function that returns the array of emails
     *
     *  @param	int		$mailing_id    	Id of mailing. No need to use it.
     *  @param  array	$filtersarray   If you used the formFilter function. Empty otherwise.
     *  @return int           			<0 if error, number of emails added if ok
     */
    function add_to_target($mailing_id,$filtersarray=array())
    {
    	global $conf, $langs;
    	
        $target = array();
		
		/*echo '<pre>';
		print_r($_REQUEST);
		echo '</pre>';exit;*/
		
	    // CHANGE THIS
	    // ----- Your code start here -----
	   	$sql = "SELECT c.rowid as id, c.email as email, c.rowid as fk_contact,";
		$sql.= " c.lastname, c.firstname, c.civilite as civility_id,";
		$sql.= " s.nom as companyname";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
		$sql.= " WHERE c.entity IN (".getEntity('societe', 1).")";
		$sql.= " AND c.email <> ''";
		$sql.= " AND c.no_email = 0";
		$sql.= " AND c.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
		foreach($filtersarray as $key)
		{
			if ($key == 'thirdparty') $sql.= " AND s.rowid=".$_REQUEST['thirdparty'];
		}
		$sql.= " ORDER BY c.email";
		
		// Stocke destinataires dans cibles
		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			$j = 0;

			dol_syslog(get_class($this)."::add_to_target mailing ".$num." targets found");

			$old = '';
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				if ($old <> $obj->email)
				{
					$cibles[$j] = array(
                    		'email' => $obj->email,
                    		'fk_contact' => $obj->fk_contact,
                    		'lastname' => $obj->lastname,
                    		'firstname' => $obj->firstname,
                    		'other' =>
                                ($langs->transnoentities("ThirdParty").'='.$obj->companyname).';'.
                                ($langs->transnoentities("UserTitle").'='.($obj->civilite_id?$langs->transnoentities("Civility".$obj->civilite_id):'')),
                            'source_url' => $this->url($obj->id),
                            'source_id' => $obj->id,
                            'source_type' => 'contact'
					);
					$old = $obj->email;
					$j++;
				}

				$i++;
			}
		}
		else
		{
			dol_syslog($this->db->error());
			$this->error=$this->db->error();
			return -1;
		}

		return parent::add_to_target($mailing_id, $cibles);
    }


    /**
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		array		Array with SQL requests
	 */
	function getSqlArrayForStats()
	{
	    // CHANGE THIS: Optionnal

		//var $statssql=array();
        //$this->statssql[0]="SELECT field1 as label, count(distinct(email)) as nb FROM mytable WHERE email IS NOT NULL";
		return array();
	}


    /**
     *	Return here number of distinct emails returned by your selector.
     *	For example if this selector is used to extract 500 different
     *	emails from a text file, this function must return 500.
     *
     *  @param	string	$sql		Requete sql de comptage
     *	@return		int
     */
    function getNbOfRecipients($sql='')
    {
	    // CHANGE THIS: Optionnal

        // Example: return parent::getNbOfRecipients("SELECT count(*) as nb from dolibarr_table");
        // Example: return 500;
        global $conf;

		$sql  = "SELECT count(distinct(c.email)) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
		$sql.= " WHERE c.entity IN (".getEntity('societe', 1).")";
		$sql.= " AND c.email != ''"; // Note that null != '' is false
		$sql.= " AND c.no_email = 0";

		// La requete doit retourner un champ "nb" pour etre comprise
		// par parent::getNbOfRecipients
		return parent::getNbOfRecipients($sql);
    }

    /**
     *  This is to add a form filter to provide variant of selector
     *	If used, the HTML select must be called "filter"
     *
     *  @return     string      A html select zone
     */
    function formFilter()
    {
	    // CHANGE THIS: Optionnal

        global $langs;
		$langs->load("companies");
		$langs->load("commercial");
		$langs->load("suppliers");

		$s='';
		$s.='<select name="thirdparty" class="flat">';
		// Add prospect of a particular level
		$sql = "SELECT rowid, nom";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " ORDER BY nom";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num) $s.='<option value="all">&nbsp;</option>';
			else $s.='<option value="all">'.$langs->trans("ThirdPartyAllShort").'</option>';

			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$s.='<option value="'.$obj->rowid.'">'.$obj->nom.'</option>';
				$i++;
			}
		}
		$s.='</select>';
		return $s;
    }


    /**
     *  Can include an URL link on each record provided by selector
     *	shown on target page.
     *
     *  @param	int		$id		ID
     *  @return string      	Url link
     */
    function url($id)
    {
	    // CHANGE THIS: Optionnal

        return '';
    }

}

