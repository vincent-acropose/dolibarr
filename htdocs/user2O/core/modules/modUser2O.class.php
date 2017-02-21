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
 *	\defgroup   user2O      Module user2O
 *	\brief      Module pour gerer l'appel automatique
 */

/**
 *	\file       htdocs/includes/modules/moduser2O.class.php
 *	\ingroup    user2O
 *	\brief      Fichier de description et activation du module de click to Dial
 *	\author     Oscim <mail oscim@users.sourceforge.net>
 *	\version    $Id: moduser2O.php,v 1.56 2012/06/10 15:28:01 oscim Exp $
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 \class      moduser2O
 \brief      Classe de description et activation du module de Click to Dial
 */

class modUser2O extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function __construct($DB)
	{
		$this->db = $DB ;
		$this->numero = 6662 ;

		$this->family = "base";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Groupe d'utilisateur by Oscim";

		$this->version = '1.0';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='generic';

		// Data directories to create when module is enabled
		$this->dirs = array("/user2O");

		$this->phpmin = array(5, 3);

		$this->need_dolibarr_version = array(3, 5);
		
		// Dependencies
		$this->depends = array('user');
		$this->requiredby = array();

		$this->conflictwith = array();
		$this->langfiles = array("user2O","users");

		// tabs
		$this->tabs = array(
// 				    'thirdparty:user2O:@user2O:/user2O/tab/user2O_societe.php?socid=__ID__',
// 				    'contact:user2O:@user2O:/user2O/tab/user2O_contact.php?id=__ID__',
				    );

		$this->module_parts = array(
								'triggers' => 1,
		            );
            
		// Config pages
		$this->config_page_url = array(DOL_URL_ROOT."/user2O/admin/index.php");

		// Constantes
		$this->const = array();


		$r=0;
// 		$this->const[$r][0] = "CALLING_ADDON";
// 		$this->const[$r][1] = "chaine";
// 		$this->const[$r][2] = "api_ovh";
// 		$this->const[$r][3] = "Nom de l'api utilisé";
// 		$this->const[$r][4] = 0;
// 		$this->const[$r][5] = 1; // supprime la constante à la désactivation du module

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'user2O';


		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;
// 		$this->menu[$r]=array('fk_menu'=>0,
// 													'type'=>'top',
// 													'titre'=>'Telephonie',
// 													'mainmenu'=>'user2O',
// 													'leftmenu'=>'1',		// Use 1 if you also want to add left menu entries using this descriptor.
// 													'url'=>'/comm/action/listactions.php?sortfield=a.datep&sortorder=desc&begin=&',
// 													'langs'=>'agenda',
// 													'position'=>100,
// 													'perms'=>'$user->rights->agenda->myactions->read',
// 													'enabled'=>'$conf->agenda->enabled',
// 													'target'=>'',
// 													'user'=>2);
// 		$r++;
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
			
			copy( substr(dirname(__FILE__),0,-7).'/triggers/interface_20_modUser2O_GroupDynamic.class.php', DOL_DOCUMENT_ROOT .'/core/triggers/interface_20_modUser2O_GroupDynamic.class.php' ); 
			
			
			copy( substr(dirname(__FILE__),0,-12).'/install/rewrite/user_group_htaccess.txt', DOL_DOCUMENT_ROOT .'/user/group/.htaccess' ); 
			

			$this->load_tables();

			return $this->_init($sql);
		}

    /**
     *      \brief      Function called when module is disabled.
     *                  Remove from database constants, boxes and permissions from Dolibarr database.
     *                  Data directories are not deleted.
     *      \return     int             1 if OK, 0 if KO
     */
		function remove()
		{
			$sql = array();

			unlink(DOL_DOCUMENT_ROOT .'/core/triggers/interface_20_modUser2O_GroupDynamic.class.php'); 
			
			unlink(DOL_DOCUMENT_ROOT .'/user/group/.htaccess'); 
			
			return $this->_remove($sql);
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
        return $this->_load_tables('/user2O/sql/');
    }

}
?>
