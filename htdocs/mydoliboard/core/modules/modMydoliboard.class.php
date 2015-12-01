<?php
/* Copyright (C) 2013-2014		Charles-fr Benke	<charles.fr@benke.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *  \defgroup   patasTools     Module Mydoliboard
 *	\brief      Module to Manage Dolibarr personalised dashboard
 *  \file       htdocs/mydoliboard/core/modules/modMydoliboard.class.php
 *	\ingroup    patasTools
 *	\brief      Fichier de description et activation du module Mydoliboard
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *	\class	modMydoliboard
 *	\brief	Classe de description et activation du module myDoliboard
 */
class modMydoliboard extends DolibarrModules
{
	/**
	*   Constructor. Define names, constants, directories, boxes, permissions
	*
	*   @param	DoliDB		$db      Database handler
	*/
	function __construct($db)
	{
		global $conf, $langs;

		$langs->load('mydoliboard@mydoliboard');

		$this->db = $db;
		$this->numero = 160000;

		$this->family = "technic";

		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "G&eacute;n&eacute;rateur de tableau de bord personnalis&eacute;es";
		
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '3.6.+1.2.1';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='list';


		$this->module_parts = array(
			'css' => '/mydoliboard/css/patastools.css',       // Set this to relative path of css if module has its own css file
		);

		// Data directories to create when module is enabled
		$this->dirs = array("/mydoliboard/temp");

		// Config pages
		$this->config_page_url = array("admin.php@mydoliboard");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("mydoliboard@mydoliboard");

		// Constants
		$this->const = array();
		$r=0;

		// Permissions
		$this->rights = array();
		$this->rights_class = 'mydoliboard';
		$r=0;

		$this->rights[$r][0] = 1600001; // id de la permission
		$this->rights[$r][1] = "Lire les tableaux personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 1600002; // id de la permission
		$this->rights[$r][1] = "Administrer les tableaux personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'setup';

		$r++;
		$this->rights[$r][0] = 1600003; // id de la permission
		$this->rights[$r][1] = "Modifier les tableaux personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'c'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 1600004; // id de la permission
		$this->rights[$r][1] = "Supprimer les tableaux personnalis&eacute;es"; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		// Left-Menu of myDoliboard module
		$r=0;
		if ($this->no_topmenu())
		{
			$this->menu[$r]=array(	'fk_menu'=>0,
						'type'=>'top',	
						'titre'=>'PatasTools',
						'mainmenu'=>'patastools',
						'leftmenu'=>'mydoliboard',
						'url'=>'/mydoliboard/core/patastools.php?mainmenu=patastools&leftmenu=mydoliboard',
						'langs'=>'mydoliboard@mydoliboard',
						'position'=>100,
						'enabled'=>'1',
						'perms'=>'$user->rights->mydoliboard->lire',
						'target'=>'',
						'user'=>0);
	
			$r++; //1
		}
		
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools',
					'type'=>'left',	
					'titre'=>'Mydoliboard',
					'mainmenu'=>'patastools',
					'leftmenu'=>'mydoliboard',
					'url'=>'/mydoliboard/index.php',
					'langs'=>'mydoliboard@mydoliboard',
					'position'=>120,
					'enabled'=>'1',
					'perms'=>'$user->rights->mydoliboard->lire',
					'target'=>'',
					'user'=>2);
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mydoliboard',
					'type'=>'left',
					'titre'=>'NewPage',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/mydoliboard/fiche.php?action=create',
					'langs'=>'mydoliboard@mydoliboard',
					'position'=>121,
					'enabled'=>'1',
					'perms'=>'$user->rights->mydoliboard->setup',
					'target'=>'',
					'user'=>2);	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mydoliboard',
					'type'=>'left',
					'titre'=>'ListOfPage',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/mydoliboard/liste.php',
					'langs'=>'mydoliboard@mydoliboard',
					'position'=>122,
					'enabled'=>'1',
					'perms'=>'$user->rights->mydoliboard->setup',
					'target'=>'',
					'user'=>2);	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mydoliboard',
					'type'=>'left',
					'titre'=>'NewBoard',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/mydoliboard/board.php?action=create',
					'langs'=>'mydoliboard@mydoliboard',
					'position'=>123,
					'enabled'=>'1',
					'perms'=>'$user->rights->mydoliboard->setup',
					'target'=>'',
					'user'=>2);	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mydoliboard',
					'type'=>'left',
					'titre'=>'ListOfBoard',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/mydoliboard/listeboard.php',
					'langs'=>'mydoliboard@mydoliboard',
					'position'=>124,
					'enabled'=>'1',
					'perms'=>'$user->rights->mydoliboard->setup',
					'target'=>'',
					'user'=>2);	
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=patastools,fk_leftmenu=mydoliboard',
					'type'=>'left',
					'titre'=>'ImportBoard',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/mydoliboard/fiche.php?action=importexport',
					'langs'=>'mydoliboard@mydoliboard',
					'position'=>125,
					'enabled'=>'$user->rights->mydoliboard->setup',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);	
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *		@param      string	$options    Options when enabling module ('', 'noboxes')
	 *		@return     int             	1 if OK, 0 if KO
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
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

	/**
	 *		Create tables, keys and data required by module
	 * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 		and create data commands must be stored in directory /mymodule/sql/
	 *		This function is called by this->init.
	 *
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/mydoliboard/sql/');
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
