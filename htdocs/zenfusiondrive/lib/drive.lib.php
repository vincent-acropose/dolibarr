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

use \zenfusion\oauth\TokenStorage;
use \zenfusion\oauth\Token;
use \zenfusion\oauth\Oauth2Client;

dol_include_once('/zenfusionoauth/class/Oauth2Client.class.php');
dol_include_once('/zenfusionoauth/class/TokenStorage.class.php');
dol_include_once('/zenfusionoauth/lib/google-api-php-client/src/Google/Service/Drive.php');

/**
 * Upload a file to Drive using the Google API client
 *
 * @param Token $token           The oauth access token
 * @param string $file            Name of the file to upload
 * @param string $parent_id       Id of the parent element
 * @param string $filedescription Drive file description
 *
 * @return int status
 */
function uploadToDrive($token, $file, $parent_id = null, $filedescription = null)
{
    global $langs;

    require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
    $dolibarr_version = versiondolibarrarray();

    $client = new Oauth2Client();
    try {
        $client->setAccessToken($token->getTokenBundle());

    } catch (Google_Auth_Exception $e) {
        $langs->load('zenfusiondrive@zenfusiondrive');
        $mesg = $langs->trans('InvalidTokenError');
        dol_syslog($e->getMessage(), LOG_ERR);
        dol_syslog('Token invalid or NULL', LOG_ERR);
        // FIXME: duplicated code. Factorize me!
        if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
            setEventMessages($mesg, '', 'errors');
        } elseif ($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) { // DOL_VERSION >= 3.3
            setEventMessage($mesg, 'errors');
        }
    }
    $client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
    $service = new Google_Service_Drive($client);
    $drivefile = new Google_Service_Drive_DriveFile();
    $drivefile->setTitle(basename($file));
    if ($filedescription !== null) {
        $drivefile->setDescription($filedescription);
    }

    // Use folderId from Google Picker
    if ($parent_id !== null) {
        $parent = new Google_Service_Drive_ParentReference();
        $parent->setId($parent_id);
        $drivefile->setParents(array($parent));
    }

    // MIME type management
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file);
    $drivefile->setMimeType($mimeType);

    $data = file_get_contents($file);
    $optParams = array(
        'data' => $data,
        'mimeType' => $mimeType,
        'uploadType' => 'media' // Will upload an empty file if not there
    );

    try {

        $createdFile = $service->files->insert(
            $drivefile,
            $optParams
        );
    } catch (Exception $e) {
        dol_syslog($e->getMessage(), LOG_ERR);
    }
    if ($createdFile) {
        dol_syslog($langs->trans('ObjectUploadSuccess'), LOG_INFO);
        return 1;
    } else {
        dol_syslog($langs->trans('ObjectUploadFailure'), LOG_ERR);
        return -1;
    }
}

/**
 * Saves the database on each registered user's Google Drive
 *
 * @param string $driveusersids Dolibarr user Ids separated by ","
 *
 * @return int status
 */
function saveDatabaseToDrive($driveusersids)
{
    global $db, $conf, $langs;
    $langs->load('zenfusiondrive@zenfusiondrive');
    $prefix = $conf->db->type;
    $host = $conf->db->host;
    $timestamp = strftime("%Y%m%d%H%M");
    $file = $prefix . '_' . $host . '_' . $timestamp . '.sql';
    $dbdump = backupTables($file);
    if ($dbdump) {
        $tokens = TokenStorage::getAllTokens(
            $db,
            GOOGLE_DRIVE_SCOPE,
            'rowid IN(' . $driveusersids . ')'
        );
        $dir = DOL_DATA_ROOT . '/admin/backup';
        $error = 0;
        foreach ($tokens as $tokenstorage) {
            $res = uploadToDrive(
                $tokenstorage->token,
                $dir . '/' . $file,
                $langs->trans('DatabaseSave')
            );
            if (!$res) {
                $error++;
            }
        }
        if ($error) {
            dol_syslog($langs->trans('SaveDBError'), LOG_ERR);

            return -1;
        } else {
            dol_syslog($langs->trans('SaveDBSuccess'), LOG_INFO);

            return 1;
        }
    } else {
        dol_syslog($langs->trans('SaveDBError'), LOG_ERR);

        return -1;
    }
}

// TODO: PostgreSQL compatibility
/**
 * Dumps the database
 *
 * @param string       $file   Dump filename
 * @param string|array $tables Data tables to dump
 *
 * @return int status
 */
function backupTables($file, $tables = '*')
{
    global $db, $langs;
    global $errormsg;

    $outputdir  = DOL_DATA_ROOT . '/admin/backup';
    $outputfile = $outputdir . '/' . $file;

    if ($db->type == 'pgsql') {
        exec('pg_dump ' . $db->database_name . ' > ' . $outputfile);
    } else {
        // Set to UTF-8
        $db->query('SET NAMES utf8');
        $db->query('SET CHARACTER SET utf8');

        //get all of the tables
        if ($tables == '*') {
            $tables = array();
            $sql = 'SHOW FULL TABLES WHERE Table_type = "BASE TABLE"';
            if ($db->type == 'pgsql') {
                $sql = 'SELECT table_name FROM information_schema.tables';
            }
            $result = $db->query($sql);
            while (($row = $db->fetch_row($result))) {
                $tables[] = $row[0];
            }
        } else {
            $tables = is_array($tables) ? $tables : explode(',', $tables);
        }

        //cycle through
        $handle = fopen($outputfile, 'w+');
        if (fwrite($handle, '') === false) {
            $langs->load("errors");
            dol_syslog("Failed to open file " . $outputfile, LOG_ERR);
            $errormsg=$langs->trans("ErrorFailedToWriteInDir");

            return -1;
        }

        // Print headers and global mysql config vars
        $sqlhead = '';
        $sqlhead .= "-- " . $db->getLabel() . " dump via php
    --
    -- Host: " . $db->db->host_info . "    Database: " . $db->database_name."
    -- ------------------------------------------------------
    -- Server version	" . $db->db->server_info . "

    /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
    /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
    /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
    /*!40101 SET NAMES utf8 */;

    ";

        if ($db->type == 'mysql' || $db->type == 'mysqli') {
            $sqlhead .= "SET FOREIGN_KEY_CHECKS=0;\n";
            $sqlhead .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n";
        }
        //if (GETPOST("nobin_use_transaction")) $sqlhead .= "SET AUTOCOMMIT=0;\nSTART TRANSACTION;\n";

        fwrite($handle, $sqlhead);

        $ignore = '';
        //if (GETPOST("nobin_sql_ignore")) $ignore = 'IGNORE ';
        $delayed = '';
        //if (GETPOST("nobin_delayed")) $delayed = 'DELAYED ';

        // Process each table and print their definition + their datas
        foreach ($tables as $table) {
            // Saving the table structure
            fwrite(
                $handle,
                "\n--\n-- Table structure for table `" . $table . "`\n--\n"
            );
            fwrite(
                $handle,
                "DROP TABLE IF EXISTS `".$table."`;\n"
            ); // Dropping table if exists prior to re create it
            $resqldrop = $db->query('SHOW CREATE TABLE ' . $table);
            $row2 = $db->fetch_row($resqldrop);
            fwrite($handle, $row2[1] . ";\n");
            //fwrite($handle, "/*!40101 SET character_set_client = @saved_cs_client */;\n\n");

            // Dumping the data (locking the table and disabling the keys check while doing the process)
            fwrite(
                $handle,
                "\n--\n-- Dumping data for table `" . $table."`\n--\n"
            );
            fwrite(
                $handle,
                "LOCK TABLES `" . $table . "` WRITE;\n"
            ); // Lock the table before inserting data (when the data will be imported back)
            fwrite($handle, "ALTER TABLE `" . $table . "` DISABLE KEYS;\n");

            $sql = 'SELECT * FROM ' . $table;
            $result = $db->query($sql);
            $num_fields = $db->num_rows($result);
            while (($row = $db->fetch_row($result))) {
                // For each row of data we print a line of INSERT
                $txt = 'INSERT ' . $delayed . $ignore . 'INTO `';
                $txt .= $table . '` VALUES (';
                fwrite($handle, $txt);
                $columns = count($row);
                for ($j=0; $j < $columns; $j++) {
                    /* Processing each columns of the row to ensure that we correctly save the value
                    (eg: add quotes for string - in fact we add quotes for everything, it's easier) */
                    if ($row[$j] == null and !is_string($row[$j])) {
                        // IMPORTANT: if the field is NULL we set it NULL
                        $row[$j] = 'NULL';
                    } elseif (is_string($row[$j]) and $row[$j] == '') {
                        // if it's an empty string, we set it as an empty string
                        $row[$j] = "''";
                    } elseif (is_numeric($row[$j])
                        and !strcmp($row[$j], $row[$j]+0)
                    ) {
                        /*
                         * Test if it's a numeric type and the numeric version ($nb+0) == string version
                         * (eg: if we have 01, it's probably not a number but rather a string, else it would not have any leading 0)
                         * if it's a number, we return it as-is
                         */
                        $row[$j] = $row[$j];
                    } else { // else for all other cases we escape the value and put quotes around
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = preg_replace("#\n#", "\\n", $row[$j]);
                        $row[$j] = "'" . $row[$j] . "'";
                    }
                }
                fwrite($handle, implode(',', $row) . ");\n");
            }
            fwrite($handle, "ALTER TABLE `".$table."` ENABLE KEYS;\n"); // Enabling back the keys/index checking
            fwrite($handle, "UNLOCK TABLES;\n"); // Unlocking the table
            fwrite($handle, "\n\n\n");
        }

        /* Backup Procedure structure*/
        if ($db->type == 'mysql' || $db->type == 'mysqli') {
            $sqlhead .= "SET FOREIGN_KEY_CHECKS=1;\n";
        }
        // Write the footer (restore the previous database settings)
        $sqlfooter = "\n\n";
        //if (GETPOST("nobin_use_transaction")) $sqlfooter .= "COMMIT;\n";
        //if (GETPOST("nobin_disable_fk")) $sqlfooter .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $sqlfooter .= "\n\n-- Dump completed on " . date('Y-m-d G-i-s');
        fwrite($handle, $sqlfooter);

        fclose($handle);
    }
    return 1;
}
