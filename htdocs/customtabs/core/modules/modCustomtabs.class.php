<?php
/* Copyright (C) 2014-2015	Charlie BENKE	<charlie@patas-monkey.com>
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
 * \defgroup member customtabs
 * \brief Module to manage Customtabs
 * \file htdocs/customtabs/core/modules/modCustomtabs.class.php
 * \ingroup PatasTools
 * \brief File descriptor or module customtabs
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * \class modCustomtabs
 * \brief Classe de description et activation du module customTabs
 */
class modCustomtabs extends DolibarrModules
{
	/**
	 * Constructor.
	 * Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	function __construct($db) {
		global $conf, $langs;
		
		$langs->load('customtabs@customtabs');
		
		$this->db = $db;
		$this->numero = 160001;
		$this->rights_class = 'customtabs';
		$this->family = "technic";
		
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Création d'onglets personnalisés";
		
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '3.8.+1.3.0';
		
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->special = 0;
		$this->picto = 'customtabs@customtabs';
		
		// Data directories to create when module is enabled
		$this->dirs = array (
				"/customtabs/temp" 
		);
		
		// Config pages
		$this->config_page_url = array (
				"customtabs.php@customtabs" 
		);
		
		// Dependencies
		$this->depends = array ();
		$this->requiredby = array ();
		$this->conflictwith = array ();
		$this->langfiles = array (
				"customtabs@customtabs",
				"companies" 
		);
		
		// Constantes
		$this->const = array ();
		
		// hook pour la recherche
		$this->module_parts = array (
				'hooks' => array (
						'odtgeneration' 
				), // used for list ODT substitutions
				'css' => '/customtabs/css/patastools.css', // Set this to relative path of css if module has its own css file
				'substitutions' => 1 
		) // used for card ODT substitutions
;
		
		// Permissions
		$this->rights = array ();
		$this->rights_class = 'customtabs';
		$r = 0;
		
		// $this->rights[$r][0] Id permission (unique tous modules confondus)
		// $this->rights[$r][1] Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
		// $this->rights[$r][2] Non utilise
		// $this->rights[$r][3] 1=Permis par defaut, 0=Non permis par defaut
		// $this->rights[$r][4] Niveau 1 pour nommer permission dans code
		// $this->rights[$r][5] Niveau 2 pour nommer permission dans code
		
		$r ++;
		$this->rights[$r][0] = 1600011;
		$this->rights[$r][1] = 'Read customtabs card';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';
		
		$r ++;
		$this->rights[$r][0] = 1600012;
		$this->rights[$r][1] = 'Create/modify customtabs (need also user module permissions if member linked to a user)';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';
		
		$r ++;
		$this->rights[$r][0] = 1600014;
		$this->rights[$r][1] = 'Remove customtabs';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';
		
		$r ++;
		$this->rights[$r][0] = 1600016;
		$this->rights[$r][1] = 'Export customtabs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';
		
		$r ++;
		$this->rights[$r][0] = 1600017;
		$this->rights[$r][1] = 'Import customtabs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'import';
		
		$r ++;
		$this->rights[$r][0] = 1600015;
		$this->rights[$r][1] = 'Setup types and attributes of custom-parc';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'configurer';
		
		// Left-Menu of customtabs module
		$r = 0;
		if ($this->no_topmenu()) {
			$this->menu[$r] = array (
					'fk_menu' => '',
					'type' => 'top',
					'titre' => 'PatasTools',
					'mainmenu' => 'patastools',
					'leftmenu' => 'customlink',
					'url' => '/customtabs/core/patastools.php?mainmenu=patastools&leftmenu=customtabs',
					'langs' => 'customtabs@customtabs',
					'position' => 100,
					'enabled' => '1',
					'perms' => '$user->rights->customtabs->lire',
					'target' => '',
					'user' => 0 
			);
			
			$r ++; // 1
		}
		
		$this->menu[$r] = array (
				'fk_menu' => 'fk_mainmenu=patastools',
				'type' => 'left',
				'titre' => 'CustomTabs',
				'mainmenu' => 'patastools',
				'leftmenu' => 'customtabs',
				'url' => '/customtabs/list.php',
				'langs' => 'customtabs@customtabs',
				'position' => 130,
				'enabled' => '1',
				'perms' => '$user->rights->customtabs->lire',
				'target' => '',
				'user' => 2 
		);
		$r ++;
		$this->menu[$r] = array (
				'fk_menu' => 'fk_mainmenu=patastools,fk_leftmenu=customtabs',
				'type' => 'left',
				'titre' => 'NewTabs',
				'mainmenu' => '',
				'leftmenu' => '',
				'url' => '/customtabs/card.php?action=create',
				'langs' => 'customtabs@customtabs',
				'position' => 131,
				'enabled' => '1',
				'perms' => '$user->rights->customtabs->lire',
				'target' => '',
				'user' => 2 
		);
		$r ++;
		$this->menu[$r] = array (
				'fk_menu' => 'fk_mainmenu=patastools,fk_leftmenu=customtabs',
				'type' => 'left',
				'titre' => 'UserGroupTabsRight',
				'mainmenu' => '',
				'leftmenu' => '',
				'url' => '/customtabs/grouptabsright.php',
				'langs' => 'customtabs@customtabs',
				'position' => 132,
				'enabled' => '1',
				'perms' => '$user->rights->customtabs->configurer',
				'target' => '',
				'user' => 2 
		);
		$r ++;
		$this->menu[$r] = array (
				'fk_menu' => 'fk_mainmenu=patastools,fk_leftmenu=customtabs',
				'type' => 'left',
				'titre' => 'Dictionarys',
				'mainmenu' => '',
				'leftmenu' => '',
				'url' => '/customtabs/dictionary.php',
				'langs' => 'customtabs@customtabs',
				'position' => 133,
				'enabled' => '1',
				'perms' => '$user->rights->customtabs->lire',
				'target' => '',
				'user' => 2 
		);
		$r ++;
		$this->menu[$r] = array (
				'fk_menu' => 'fk_mainmenu=patastools,fk_leftmenu=customtabs',
				'type' => 'left',
				'titre' => 'ImportCustomTabs',
				'mainmenu' => '',
				'leftmenu' => '',
				'url' => '/customtabs/card.php?action=import',
				'langs' => 'customtabs@customtabs',
				'position' => 134,
				'enabled' => '1',
				'perms' => '$user->rights->customtabs->export',
				'target' => '',
				'user' => 2 
		);
		
		// dictionnarys
		$this->dictionnaries = array ();
	}
	
	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	function init($options = '') {
		$sql = array ();
		
		$result = $this->load_tables();
		
		return $this->_init($sql, $options);
	}
	
	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	function remove($options = '') {
		$sql = array ();
		
		return $this->_remove($sql, $options);
	}
	
	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /mymodule/sql/
	 * This function is called by this->init.
	 *
	 * @return int <=0 if KO, >0 if OK
	 */
	function load_tables() {
		return $this->_load_tables('/customtabs/sql/');
	}
	
	/*  Is the top menu already exist */
	function no_topmenu() {
		// gestion de la position du menu
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "menu";
		$sql .= " WHERE mainmenu ='patastools'";
		// $sql.=" AND module ='patastools'";
		$sql .= " AND type = 'top'";
		$resql = $this->db->query($sql);
		if ($resql) {
			// il y a un top menu on renvoie 0 : pas besoin d'en créer un nouveau
			if ($this->db->num_rows($resql) > 0)
				return 0;
		}
		// pas de top menu on renvoie 1
		return 1;
	}
}
?>