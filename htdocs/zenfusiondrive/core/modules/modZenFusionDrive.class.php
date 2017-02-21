<?php
/*
 * ZenFusion Drive - A Google Drive module for Dolibarr
 * Copyright (C) 2013       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014-2015  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \defgroup zenfusiondrive Module Zenfusion Drive
 * \brief Zenfusion Drive module for Dolibarr
 *
 * Allows contacts sync between Dolibarr and Google Drive
 * using the Google Drive API.
 *
 */

/**
 * \file core/modules/modZenFusionDrive.class.php
 * \brief Zenfusion Drive module
 *
 * Declares and initializes the Zenfusion Drive module in Dolibarr
 *
 * \ingroup zenfusiondrive
 * \authors Cédric Salvador <csalvador@gpcsolutions.fr>
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";
dol_include_once('/zenfusionoauth/inc/oauth.inc.php');
dol_include_once('/zenfusionoauth/lib/scopes.lib.php');

/**
 * \class modZenFusionDrive
 * \brief Describes and activates Zenfusion Drive module
 */
class modZenFusionDrive extends DolibarrModules
{

    /**
     * 	Constructor. Define names, constants, directories, boxes, permissions
     *
     * 	@param	DoliDB		$db	Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->numero = 105004;
        $this->rights_class = 'zenfusiondrive';
        $this->family = "other";
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Sync with Google Drive™";
        $this->version = '2.1.2';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->special = 1;
        $this->picto = 'drive@zenfusiondrive';
        $this->module_parts = array(
            //'triggers' => 1,
            'hooks' => array('formfile')
        );
        $this->dirs = array();
        // FIXME: set to conf.php once the database backup feature is stabilized
        $this->config_page_url = array("about.php@zenfusiondrive");
        $this->depends = array("modZenFusionOAuth");
        $this->requiredby = array();
        $this->phpmin = array(5, 3);
        $this->need_dolibarr_version = array(3, 2);
        $this->langfiles = array("zenfusiondrive@zenfusiondrive");
        $this->const = array();
        $this->tabs = array();
        $this->boxes = array();
        $this->rights = array();
        $this->rights[0][0] = 7456812;
        $this->rights[0][1] = 'Use ZenFusionDrive';
        $this->rights[0][3] = 0;
        $this->rights[0][4] = 'use';
        $this->menus = array();
    }

    /**
     * \brief Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories.
     * \return int 1 if OK, 0 if KO
     */
    public function init()
    {
        global $langs;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
        $dolibarr_version = versiondolibarrarray();

        // We set the scope we need
        $sql = array();
        $this->load_tables();

        if (function_exists('curl_init')) {
            addScope(GOOGLE_DRIVE_SCOPE);
            $this->_init($sql);
        } else {
            // FIXME: duplicated code. Factorize me!
            $mesg = $langs->trans("MissingCURL");
            if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
                setEventMessages($mesg, '', 'errors');
            } elseif (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3)) { // DOL_VERSION >= 3.3
                setEventMessage($mesg, 'errors');
            } else {
                $mesg = urlencode($mesg);
                $msg = '&mesg=' . $mesg;
            }
            header("Location: modules.php?mode=interfaces" . $msg);
            exit;
        }
        // OK
        return 1;
    }

    /**
     * \brief Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted.
     * \return int 1 if OK, 0 if KO
     */
    public function remove()
    {
        $sql = array();
        removeScope(GOOGLE_DRIVE_SCOPE);

        return $this->_remove($sql);
    }

    /**
     * \brief Create tables, keys and data required by module
     * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     *  and create data commands must be stored in directory /mymodule/sql/
     * This function is called by this->init.
     * \return int <=0 if KO, >0 if OK
     */
    public function load_tables()
    {
        return $this->_load_tables('/zenfusiondrive/sql/');
    }
}
