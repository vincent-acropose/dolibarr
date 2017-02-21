<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2012		 Oscim					       <oscim@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\defgroup   calling      Module calling
 *	\brief      Module pour gerer l'appel automatique
 */

/**
 *	\file       htdocs/includes/modules/modCalling.class.php
 *	\ingroup    calling
 *	\brief      Fichier de description et activation du module de click to Dial
 *	\author     Oscim <mail oscim@users.sourceforge.net>
 *	\version    $Id: modCalling.php,v 1.56 2012/06/10 15:28:01 oscim Exp $
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 \class      modCalling
 \brief      Classe de description et activation du module de Click to Dial
 */

class modoscimmods extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modoscimmods($DB)
	{
		$this->db = $DB ;
		$this->numero = 98366 ;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "OscimMods is collection lib/class/ressource for other modules devellopped by oscim";

		$this->version = '0.1.1';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 1;
		$this->picto='oscimmods.png@oscimmods';

		// Data directories to create when module is enabled
		$this->dirs = array("/oscimmods");
		
		$this->phpmin = array(5, 3);

		$this->need_dolibarr_version = array(3, 5);
		
		// Dependencies
		$this->depends = array();
		$this->requiredby = array();

		$this->conflictwith = array();
		$this->langfiles = array("oscimmods@oscimmods","users");

		// Defined all module parts (triggers, login, substitutions, menus, etc...) (0=disable,1=enable)
		$this->module_parts = array('triggers' => 1);
   
		// tabs
		$this->tabs = array();

		// Config pages
		$this->config_page_url = array(DOL_URL_ROOT."/oscimmods/admin/index.php");

		// Constantes
		$this->const = array();

		global $langs; 
		
		$r=0;
		$this->const[$r][0] = "OSCIMMODS_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "";
		$this->const[$r][3] = "OscimMods addon active";
		$this->const[$r][4] = 1;
		$this->const[$r][5] = 'current';
		$this->const[$r][6] = 1; // supprime la constante à la désactivation du module

		
		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'oscimmods';


		// Main menu entries
		$this->menu = array();			// List of menus to add

		$r++;
	}

    /**
     *      \brief      Function called when module is enabled.
     *                  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *                  It also creates data directories.
     *      \return     int             1 if OK, 0 if KO
     */
		function init()
		{
			global $conf;

			$sql = array();

		$this->load_tables();

			return $this->_init($sql);
		}

    /**
     *      \brief      Function called when module is disabled.
     *                  Remove from database constants, boxes and permissions from Dolibarr database.
     *                  Data directories are not deleted.
     *      \return     int             1 if OK, 0 if KO
     */
		function remove($options = '')
		{
		global $conf;
		
		$sql = array(
		);

		return $this->_remove($sql, $options);
		}


    /**
     * 		Create tables, keys and data required by module
     * 		Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * 		and create data commands must be stored in directory /mymodule/sql/
     * 		This function is called by this->init
     *
     * 		@return		int		<=0 if KO, >0 if OK
     */
    function load_tables() {
        return $this->_load_tables('/oscimmods/sql/');
    }

}
?>
