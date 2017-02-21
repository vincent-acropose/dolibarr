<?php
/* Copyright (C) 2015 Charlie BENKE  <charlie@patas-monkey.com>
 * Module pour gerer le trigger sur les extrafields ligne de pièces pour déterminer un prix
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
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");

/**
 * 		\class      modMyModule
 *      \brief      Description and activation class for module MyModule
 */
class modmyfield extends DolibarrModules
{
	/**
	 *   \brief      Constructor. Define names, constants, directories, boxes, permissions
	 *   \param      DB      Database handler
	 */
	function __construct($db)
	{
		
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 160211;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'myfield';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Gestion des champs sur les onglets";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '3.7.+1.0.5';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='myfield.png@myfield';

		// Defined if the directory /mymodule/inc/triggers/ contains triggers or not
		$this->module_parts = array(
			'hooks' => array('globalcard','membercard','membertypecard','categorycard','commcard','propalcard','actioncard','agenda','mailingcard','ordercard','invoicecard','paiementcard','tripsandexpensescard','doncard','externalbalance','salarycard','taxvatcard','contactcard','contractcard','expeditioncard','interventioncard','suppliercard','ordersuppliercard','orderstoinvoicesupplier','invoicesuppliercard','paymentsupplier','deliverycard','productcard','pricesuppliercard','productstatsorder','productstatssupplyorder','productstatscontract','productstatsinvoice','productstatssupplyinvoice','productstatspropal','warehousecard','projectcard','projecttaskcard','projecttaskcard','resource_card','element_resource','agendathirdparty','salesrepresentativescard','consumptionthirdparty','infothirdparty','thirdpartycard','usercard','passwordforgottenpage')
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();
		$r=0;


		// Dependencies
		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,4);	// Minimum version of Dolibarr required by module

		$this->langfiles = array("myfield@myfield");

		// Config pages
		$this->config_page_url = array("setup.php@myfield");

		// Constants
		$this->const = array();			// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')

		// Array to add new pages in new tabs
		$this->tabs = array();

		// Boxes
		$this->boxes = array();			// List of boxes

		// Permissions
		$this->rights = array();
		$this->rights_class = 'myfield';
		$r=0;

		$r++;
		$this->rights[$r][0] = 1602111; // id de la permission
		$this->rights[$r][1] = "Lire les champs "; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 1602112; // id de la permission
		$this->rights[$r][1] = "Administrer les champs"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'setup';

		$r++;
		$this->rights[$r][0] = 1602113; // id de la permission
		$this->rights[$r][1] = "Créer les champs "; // libelle de la permission
		$this->rights[$r][2] = 'c'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 1602114; // id de la permission
		$this->rights[$r][1] = "Supprimer les champs"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';
		

		// Left-Menu of myField module
		$r=0;
		if ($this->no_topmenu())
		{
			$this->menu[$r]=array(	'fk_menu'=>0,
						'type'=>'top',
						'titre'=>'PatasTools',
						'mainmenu'=>'patastools',
						'leftmenu'=>'myfield',
						'url'=>'/myfield/core/patastools.php?mainmenu=patastools&leftmenu=mylist',
						'langs'=>'myfield@myfield',	
						'position'=>100,
						'enabled'=>'1',
						'perms'=>'$user->rights->myfield->lire',
						'target'=>'',
						'user'=>0);
			$r++; //1
		} 
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools',
					'type'=>'left',	
					'titre'=>'myfield',
					'mainmenu'=>'patastools',
					'leftmenu'=>'myfield',
					'url'=>'/myfield/index.php',
					'langs'=>'myfield@myfield',
					'position'=>110,
					'enabled'=>'1',
					'perms'=>'$user->rights->myfield->lire',
					'target'=>'',
					'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=myfield',
					'type'=>'left',
					'titre'=>'NewField',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/myfield/card.php?action=create',
					'langs'=>'myfield@myfield',
					'position'=>110,
					'enabled'=>'1',
					'perms'=>'$user->rights->myfield->setup',
					'target'=>'',
					'user'=>2);	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=myfield',
					'type'=>'left',
					'titre'=>'MyfieldList',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/myfield/list.php',
					'langs'=>'myfield@myfield',
					'position'=>110,
					'enabled'=>'1',
					'perms'=>'$user->rights->myfield->setup',
					'target'=>'',
					'user'=>2);	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=myfield',
					'type'=>'left',
					'titre'=>'GroupRight',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/myfield/groupright.php',
					'langs'=>'myfield@myfield',
					'position'=>110,
					'enabled'=>'$user->rights->myfield->setup',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);	
	}


	/**
	 *		\brief      Function called when module is enabled.
	 *					The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *					It also creates data directories.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

		$result=$this->load_tables();
		
		return $this->_init($sql,$options);
	}

	/**
	 *		\brief		Function called when module is disabled.
	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
	 *					Data directories are not deleted.
	 *      \return     int             1 if OK, 0 if KO
	 */
	function remove()
	{
		$sql = array();
		return $this->_remove($sql);
	}
	
	function load_tables()
	{
		return $this->_load_tables('/myfield/sql/');
	}
	
	/*  Is the top menu already exist */
	function no_topmenu()
	{
		// gestion de la position du menu
		$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."menu";
		$sql.=" WHERE mainmenu ='patastools'";
		//$sql.=" AND module ='patastools'";
		$sql.=" AND type = 'top'";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			// il y a un top menu on renvoie 0 : pas besoin d'en créer un nouveau
			if ($this->db->num_rows($resql) > 0)
				return 0;
		}
		// pas de top menu on renvoie 1
		return 1;
	}
}
?>