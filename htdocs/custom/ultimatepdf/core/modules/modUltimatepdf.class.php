<?php
/* Copyright (C) 2010-2011 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Philippe Grand <philippe.grand@atoo-net.com>
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
 *		\defgroup   ultimatepdf     Module ultimatepdf
 *		\brief      Pdf Designs management
 *		\file       ultimatepdf/core/modules/modUltimatepdf.class.php
 *		\ingroup    ultimatepdf
 *		\brief      Fichier de description et activation du module Ultimatepdf
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");
require_once(DOL_DOCUMENT_ROOT ."/core/lib/json.lib.php");


/**
 *	\class      modultimatepdf
 *	\brief      Classe de description et activation du module Ultimatepdf
 */
class modUltimatepdf extends DolibarrModules
{

	/**
	 *	Constructor.
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		global $langs, $conf;
		
		$this->db = $db ;
		$this->numero = 300100 ;

		$this->family = "technic";
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = $langs->trans("Module300100Desc"); //"Pdf Models management";
		// Can be enabled / disabled only in the main company with superadmin account
		$this->core_enabled = 0;
		$this->version = '3.5-3.6';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='ultimatepdf@ultimatepdf';

		// Data directories to create when module is enabled
		$this->dirs = array("/ultimatepdf/otherlogo");

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = 'ultimatepdf.php@ultimatepdf';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
									'models' => 1,
									'css' => '/ultimatepdf/css/ultimatepdf.css.php',
									'hooks' => array('propalcard','ordercard','invoicecard','toprightmenu','pdfgeneration'));

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,2);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,2);	// Minimum version of Dolibarr required by module
		$this->langfiles = array('ultimatepdf@ultimatepdf');

		// Constants
		// List of particular constants to add when module is enabled
		$this->const=array(0=>array('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS','chaine','0','Hide product details within documents',1,'current',1),
		1=>array('MAIN_GENERATE_DOCUMENTS_HIDE_DESC','chaine','0','Hide product description within documents',1,'current',1), 
		2=>array('MAIN_GENERATE_DOCUMENTS_HIDE_REF','chaine','0','Hide reference within documents',1,'current',1), 
		3=>array('MAIN_TVAINTRA_NOT_IN_ADDRESS','chaine','0','Hide tva within documents',1,'current',1), 
		4=>array('ULTIMATE_SHOW_HIDE_PUHT','chaine','0','Hide puht within documents',1,'current',1),
		5=>array('ULTIMATE_SHOW_HIDE_QTY','chaine','0','Hide qty within documents',1,'current',1),
		6=>array('ULTIMATE_SHOW_HIDE_THT','chaine','0','Hide tht within documents',1,'current',1),
		7=>array('MAIN_DISPLAY_FOLD_MARK','chaine','0','Show by default fold mark within documents',1,'current',1),
		8=>array('ULTIMATE_GENERATE_PROPOSALS_WITH_PICTURE','chaine','0','Show by default photos within proposals documents',1,'current',1),
		9=>array('ULTIMATE_GENERATE_ORDERS_WITH_PICTURE','chaine','0','Show by default photos within orders documents',1,'current',1),
		10=>array('ULTIMATE_GENERATE_INVOICES_WITH_PICTURE','chaine','0','Show by default photos within invoices documents',1,'current',1),
		11=>array('MAIN_PDF_DONOTREPEAT_HEAD','chaine','0','Do not repeat head within documents',1,'current',1),
		12=>array('MAIN_PDF_ADDALSOTARGETDETAILS','chaine','0','Add address details within documents',1,'current',1),
		13=>array('MAIN_PDF_DASH_BETWEEN_LINES','chaine','0','Add dash between lines within documents',1,'current',1),
		14=>array('MAIN_PDF_FORCE_FONT','chaine','0','Add choice of font',1,'current',1),
		15=>array('ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT','chaine','0','Add choice of VAT or not',1,'current',1),
		16=>array('ULTIMATE_DASH_DOTTED','chaine','0','Add choice of dash dotted or not',1,'current',1),
		17=>array('ULTIMATE_BGCOLOR_COLOR','chaine','0','Add choice of background color',1,'current',1),
		18=>array('ULTIMATE_BORDERCOLOR_COLOR','chaine','0','Add choice of border color',1,'current',1),
		19=>array('ULTIMATE_TEXTCOLOR_COLOR','chaine','0','Add choice of text color',1,'current',1),
		20=>array('MAIN_PDF_FREETEXT_HEIGHT','chaine','0','Add set of freetext height',1,'current',1),
		21=>array('ULTIMATE_INVERT_SENDER_RECIPIENT','chaine','0','Add set for invert sender and recipient',1,'current',1),
		22=>array('ULTIMATE_PDF_MARGIN_LEFT','chaine','0','Add set of pdf margin left',1,'current',1),
		23=>array('ULTIMATE_PDF_MARGIN_RIGHT','chaine','0','Add set of pdf margin right',1,'current',1),
		24=>array('ULTIMATE_PDF_MARGIN_TOP','chaine','0','Add set of pdf margin top',1,'current',1),
		25=>array('ULTIMATE_PDF_MARGIN_BOTTOM','chaine','0','Add set of pdf margin bottom',1,'current',1));
		
		// Dictionnaries
		if (! isset($conf->ultimatepdf->enabled)) {
			$conf->ultimatepdf = (object) array();
			$conf->ultimatepdf->enabled=0; // This is to avoid warnings
		}
		$this->dictionaries=$this->dictionnaries;
		$this->dictionaries=array(
			'langs'=>'ultimatepdf@ultimatepdf', 
			'tabname'=>array(
				MAIN_DB_PREFIX.'c_ultimatepdf_line'
			),
			'tablib'=>array(
				'UltimatepdfLine'
			),
			// Request to select fields
			'tabsql'=>array(
				'SELECT ul.rowid as rowid, ul.code, ul.label, ul.description, ul.active FROM '.MAIN_DB_PREFIX.'c_ultimatepdf_line as ul'
			),
			// Sort order
			'tabsqlsort'=>array(
				'code ASC'
			),
			// List of fields (result of select to show dictionnary)
			// Nom des champs en resultat de select pour affichage du dictionnaire;
			'tabfield'=>array(
				'code,label,description'
			),
			// List of fields (list of fields to edit a record)
			// Nom des champs d'edition pour modification d'un enregistrement
			'tabfieldvalue'=>array(
				'code,label,description'
			),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array(
				'code,label,description'
			),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array(
				'rowid'
			),
			// Condition to show each dictionnary
			'tabcond'=>array(
				'$conf->ultimatepdf->enabled'
			)
		);		

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights_class = 'ultimatepdf'; 	// Key text used to identify module (for permissions, menus, etc...)
		$this->rights = array();
		$r=0;

		$r++;
		$this->rights[$r][0] = 300101;
		$this->rights[$r][1] = 'Consulter les infos du modele';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = 300102;
		$this->rights[$r][1] = 'Modifier la fiche du modele';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';

		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

	}


	/**
     *		Function called when module is enabled.
     *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *		It also creates data directories.
     *
	 *      @return     int             1 if OK, 0 if KO
     */
	function init()
	{
		$sql = array();

		$result=$this->load_tables();

		$result=$this->setFirstDesign();

		return $this->_init($sql);
	}

	/**
	 *		Function called when module is disabled.
 	 *      Remove from database constants, boxes and permissions from Dolibarr database.
 	 *		Data directories are not deleted.
 	 *
	 *      @return     int             1 if OK, 0 if KO
 	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}

	/**
	 *		Create tables and keys required by module
	 *		This function is called by this->init.
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/ultimatepdf/sql/');
	}

	 /**
	*	Set the first design
	*
	*	@return void
	*/
	function setFirstDesign()
	{
		global $user, $langs;

		$langs->load('ultimatepdf@ultimatepdf');

		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'ultimatepdf';
		$res = $this->db->query($sql);
		if ($res) $num = $this->db->fetch_array($res);
		else dol_print_error($this->db);

		if (empty($num[0]))
		{
			$this->db->begin();

			$now = dol_now();
			$optionarray =  json_encode(array('bgcolor'=>'aad4ff','bordercolor'=>'003f7f','textcolor'=>'191919','dashdotted'=>'8, 2','withref'=>'no','withoutvat'=>'no'));

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'ultimatepdf (';
			$sql.= 'label';
			$sql.= ', description';
			$sql.= ', options';
			$sql.= ', datec';
			$sql.= ', fk_user_creat';
			$sql.= ') VALUES (';
			$sql.= '"'.$langs->trans("MasterDesign").'"';
			$sql.= ', "'.$langs->trans("MasterDesignDesc").'"';
			$sql.= ", '".$optionarray."'";
			$sql.= ', "'.$this->db->idate($now).'"';
			$sql.= ', '.$user->id;
			$sql.= ')';

			if ($this->db->query($sql))
			{
				// par défaut le premier design est sélectionné
				dolibarr_set_const($this->db, "ULTIMATE_DESIGN", 1,'chaine',0,'',$conf->entity);
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			return 0;
		}
	}
}
?>
