<?php
/*
 * ZenFusion Drive - A Google Drive module for Dolibarr
 * Copyright (C) 2013   Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014   Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * \file lib/admin.lib.php
 * \ingroup zenfusiondrive
 * \brief Module functions library
 */

/**
 * Display tabs in module admin page
 *
 * @return array
 */
function zfPrepareHead()
{
    global $langs, $conf;
    $h = 0;
    $head = array();
    // Database backup feature is experimental
    if ($conf->global->MAIN_FEATURES_LEVEL >= 1) {
        $head[$h][0] = dol_buildpath("/zenfusiondrive/admin/conf.php", 1);
        $head[$h][1] = $langs->trans("Config");
        $head[$h][2] = 'conf';
        $h++;
    }

    if ($conf->global->ZF_SUPPORT) {
        $head[$h][0] = dol_buildpath("/zenfusiondrive/admin/support.php", 1);
        $head[$h][1] = $langs->trans("HelpCenter");
        $head[$h][2] = 'help';
        $h ++;
    }

    $head[$h][0] = dol_buildpath("/zenfusiondrive/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';

    return $head;
}

/**
 * Create a select with all the oauth registered users not already selected as drive users
 *
 * @param string $htmlname  Name of the returned HTML element
 * @param int    $showempty Add an empty entry
 *
 * @return string
 */
function selectOauthUsers($htmlname = 'userid', $showempty = 0)
{
    global $db, $langs, $conf;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
    $dolibarr_version = versiondolibarrarray();

    $subrequest = 'SELECT userid FROM ' . MAIN_DB_PREFIX . 'zenfusion_drive';

    // Dolibarr compat
    if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 4) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.4
        $name = 'u.lastname';
    } else {
        $name = 'u.name';
    }

    $sql = 'SELECT u.rowid, u.firstname, ' . $name . ', u.login FROM ';
    $sql .= MAIN_DB_PREFIX . 'zenfusion_oauth as zo LEFT JOIN ';
    $sql .= MAIN_DB_PREFIX . 'user as u ON zo.rowid = u.rowid';
    $sql .= ' WHERE u.entity IN (0, ' . $conf->entity . ')';
    $sql .= ' AND u.statut > 0';
    $sql .= ' AND u.rowid NOT IN (' . $subrequest . ')';
    $txt = 'ZenFusionDrive admin.lib::select_oauthusers sql = ' . $sql;
    dol_syslog($txt);
    $resql = $db->query($sql);
    if ($resql) {
        $select = '<select class="flat" name="'. $htmlname . '">';
        if ($showempty) {
            $option = '<option value="0">&nbsp;</option>';
            $select .= $option;
        }
        $num = $db->num_rows($resql);
        if ($num > 0) {
            $i = 0;
            $userstatic=new User($db);
            while ($i < $num) {
                $obj = $db->fetch_object($resql);

                // Dolibarr compat
                if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 4) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.4
                    $userstatic->firstname = $obj->firstname;
                    $userstatic->lastname  = $obj->lastname;
                } else {
                    $userstatic->prenom = $obj->firstname;
                    $userstatic->nom = $obj->name;
                }

                $userstatic->id = $obj->rowid;

                $option = '<option value="'. $obj->rowid . '">';
                $option .= $userstatic->getFullName($langs);

                if ($conf->global->MAIN_SHOW_LOGIN) {
                    $option .= ' (' . $obj->login . ')';
                }
                $option .= '</option>';
                $select .= $option;
                $i++;
            }
        }
        $select .= '</select>';

        return $select;
    } else {
        dol_print_error($db);
        return '';
    }
}

/**
 * Create an array containing the registered drive users
 *
 * @return User[]
 */
function selectDriveUsers()
{
    global $db, $conf;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
    $dolibarr_version = versiondolibarrarray();

    // Dolibarr compat
    if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 4) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.4
        $name = 'u.lastname';
    } else {
        $name = 'u.name';
    }

    $sql = 'SELECT zd.rowid, u.rowid as oauthid, ';
    $sql .= 'u.firstname, ' . $name . ', u.login FROM ';
    $sql .= MAIN_DB_PREFIX . 'zenfusion_drive as zd LEFT JOIN ';
    $sql .= MAIN_DB_PREFIX . 'user as u ON zd.userid = u.rowid';
    $sql .= ' WHERE u.entity IN (0, ' . $conf->entity . ')';
    $txt = 'ZenFusionDrive admin.lib::SelectDriveUsers sql = ' . $sql;
    dol_syslog($txt);
    $resql = $db->query($sql);
    $result = array();
    if ($resql && $db->num_rows($resql) > 0) {
        while (($obj = $db->fetch_object($resql))) {
            $userstatic = new User($db);
            $userstatic->id = $obj->rowid;
            $userstatic->oauthid = $obj->oauthid;

            // Dolibarr compat
            if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 4) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.4
                $userstatic->firstname = $obj->firstname;
                $userstatic->lastname  = $obj->lastname;
            } else {
                $userstatic->prenom = $obj->firstname;
                $userstatic->nom = $obj->name;
            }
            $result[] = $userstatic;
        }
    }

    return $result;
}
