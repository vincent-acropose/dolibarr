<?php

/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2013-2014 Philippe Grand       <philippe.grand@atoo-net.com>
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
 * 		\defgroup   financial     Module UltimateLine
 *      \brief      Add special service line after product line 
 *      \file       core/modules/modUltimateLine.class.php
 *      \ingroup    UltimateLine
 *      \brief      Description and activation file for module UltimateLine
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 * 		\class      modUltimateLine
 *      \brief      Description and activation class for module UltimateLine
 */
class modUltimateLine extends DolibarrModules
{

    /**
	 *	Constructor.
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		global $langs;
		$this->db = $db ;
        $this->numero = 300000;
        // Key text used to identify module (for permissions, menus, etc...)

        $this->family = "financial";
        $this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = $langs->trans("Module300000Desc");
		// Can be enabled / disabled only in the main company with superadmin account
        $this->core_enabled = 0;
        $this->version = '3.5.x';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->special = 0;
        // Name of image file used for this module.
        $this->picto = 'ultimateline@ultimateline';

        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array('triggers' => 1);

        // Data directories to create when module is enabled.
        $this->dirs = array();
        $r = 0;

        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        $this->config_page_url = array('settings.php@ultimateline');

        // Dependencies
        $this->depends = array('modService', 'modFacture');  // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array(); // List of modules id to disable if this one is disabled
        $this->phpmin = array(5, 0);     // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3, 0); // Minimum version of Dolibarr required by module
        $this->langfiles = array('ultimateline@ultimateline');

        $this->tabs = array();

        // Dictionnaries
        $this->dictionnaries = array();  

        // Boxes
        // Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        $this->boxes = array();   // List of boxes
        $r = 0;

        // Permissions
        $this->rights = array();  // Permission array used by this module
        $this->rights_class = 'ultimateline';
        $this->const = array();
       
    }

    /**
     * 		Function called when module is enabled.
     * 		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * 		It also creates data directories.
     *      @return     int             1 if OK, 0 if KO
     */
    function init()
    {
        $sql = array();

        $result = $this->load_tables();

        return $this->_init($sql);
    }

    /**
     * 		Function called when module is disabled.
     *      Remove from database constants, boxes and permissions from Dolibarr database.
     * 		Data directories are not deleted.
     *      @return     int             1 if OK, 0 if KO
     */
    function remove()
    {
        $sql = array();

        return $this->_remove($sql);
    }

    /**
     * 		\brief		Create tables, keys and data required by module
     * 					Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * 					and create data commands must be stored in directory /mymodule/sql/
     * 					This function is called by this->init.
     * 		\return		int		<=0 if KO, >0 if OK
     */
    function load_tables()
    {
        return $this->_load_tables('/ultimateline/sql/');
    }   
}

?>
