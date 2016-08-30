<?php
/* Copyright (C) 2013-2014	Charles-FR BENKE	<charles.fr@benke.fr>
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
 *	\defgroup   factory     Module gestion de la fabrication
 *	\brief      Module pour gerer les process de fabrication
 *	\file       htdocs/factory/core/modules/modFactory.class.php
 *	\ingroup    factory
 *	\brief      Fichier de description et activation du module factory
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Classe de description et activation du module Propale
 */
class modRestock extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 160320;

		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion du r&eacute;approvisionnement";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '3.5.+1.0.3';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='restock@restock';


		// Dependancies
		$this->depends = array();
		$this->requiredby = array();
		$this->config_page_url = array("restock.php@restock");
		$this->langfiles = array("propal","order","project","companies","products","restock@restock");

		$this->need_dolibarr_version = array(3, 4);

		// Constants
		$this->const = array();
		$r=0;

		// Permissions
		$this->rights = array();
		$this->rights_class = 'restock';
		$r=0;

		$r++;
		$this->rights[$r][0] = 160321; // id de la permission
		$this->rights[$r][1] = 'Lire le déroulement des propositions commerciales'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';
		$r++;


		// Restock Feature
		$r=0;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=product',
					'type'=>'left',
					'titre'=>'Restock',
					'mainmenu'=>'',
					'leftmenu'=>'',
					'url'=>'/restock/restock.php',
					'langs'=>'restock@restock',
					'position'=>100,
					'enabled'=>'1',
					'perms'=>'1',
					'target'=>'',
					'user'=>2);
		$r++;

		// additional tabs
		$this->tabs = array(
			  'product:+restock:RestockProduct:@Produit:/restock/restockProduct.php?id=__ID__'
			, 'order:+restock:RestockOrder:@order:/restock/restockCmdClient.php?id=__ID__'
		);


		// Exports
		//--------
		$r=0;

	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();
		
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
}
?>
