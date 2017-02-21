<?php
/*
 * ZenFusion Drive - A Google Drive module for Dolibarr
 * Copyright (C) 2013       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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

$res = 0;
if (! $res && file_exists('../main.inc.php')) {
        $res = @include_once('../main.inc.php');
}
// from custom dolibarr install
if (! $res && file_exists('../../main.inc.php')) {
        $res = @include_once('../../main.inc.php');
}
if (! $res) {
    die("Main include failed");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once './lib/drive.lib.php';
dol_include_once('/zenfusionoauth/class/TokenStorage.class.php');

use \zenfusion\oauth\TokenStorage;
use \zenfusion\oauth\Oauth2Client;

global $user, $db, $langs, $conf;

$dolibarr_version = versiondolibarrarray();

$id = GETPOST('id', 'alpha');
$element = GETPOST('element', 'alpha');
$ref = GETPOST('ref', 'alpha');
$mode = GETPOST('mode', 'alpha');
$objectid = GETPOST('objectid', 'int');

if (empty($ref) || empty($id) || empty($element) ||
   (!$user->rights->zenfusiondrive->use && !$user->admin)) {
    accessforbidden();
} else {

    $client = new Oauth2Client();
    $token = TokenStorage::getUserToken($db, $user);
    try {
        $client->setAccessToken($token->token->getTokenBundle());

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

    $service = new Google_Service_Drive($client);
    try {
        $file = $service->files->get($id);
        $link = $file['alternateLink']; //used in link mode
        if ($file['downloadUrl']) {
            // Get the file
            $url = $file['downloadUrl'];
        } elseif ($file['exportLinks']) {
            // Get a PDF version for Google Documents
            $url = $file['exportLinks']['application/pdf'];
        } elseif (!($mode == 'link' && $file['mimeType'] == 'application/vnd.google-apps.folder')) {
            // Unsupported format
            $mesg = $langs->trans('UnsupportedFormat');
            // FIXME: duplicated code. Factorize me!
            if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
                setEventMessages($mesg, '', 'errors');
            } elseif ($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) { // DOL_VERSION >= 3.3
                setEventMessage($mesg, 'errors');
            }
            throw new Exception('Unsupported format');
        }

        $res = false;
        $exists = false;

        //we have all we need for link mode
        if ($mode == 'link') {
                require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
                $linkObject = new Link($db);
                $linkObject->entity = $conf->entity;
                $linkObject->url = $link;
                $linkObject->objecttype = $element;
                $linkObject->objectid = $objectid;
                $linkObject->label = $file['title'];
                $res = $linkObject->create($user);
        } else {
            //fileupload mode, we need to download the file content and write
            //in the appropriate directory
            $request = new Google_Http_Request($url, 'GET');
            $httpRequest = $client->getAuth()->authenticatedRequest($request);
            if ($httpRequest->getResponseHttpCode() != 200) {
                throw new Exception('HTTP Response code: ' . $httpRequest->getResponseHttpCode());
            }
            $file['content'] = $httpRequest->getResponseBody();
            //have to check for $element value because coherence is overrated
            switch ($element) {
                case 'order_supplier':
                    $dir = $conf->fournisseur->commande->dir_output;
                    break;
                case 'fichinter':
                    $dir = $conf->ficheinter->dir_output;
                    break;
                case 'invoice_supplier':
                    $dir = $conf->fournisseur->facture->dir_output . '/' . get_exdir($ref, 2);
                    break;
                case 'chargesociales':
                    $dir = $conf->tax->dir_output;
                    break;
                case 'project':
                    $dir = $conf->projet->dir_output;
                    break;
                case 'project_task':
                    require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
                    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
                    $task = new Task($db);
                    $task->fetch('', $ref);
                    $project = new Project($db);
                    $project->fetch($task->fk_project);
                    $dir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($project->ref);
                    break;
                case 'member':
                    $dir = $conf->adherent->dir_output . "/" . get_exdir($ref, 2, 0, 1);
                    break;
                case 'action':
                    $dir = $conf->agenda->dir_output;
                    break;
                default:
                    $dir = $conf->$element->dir_output;
                    break;
            }
            $dir .= '/' . dol_sanitizeFileName($ref);
            if (!is_dir($dir)) {
                $d = dol_mkdir($dir);
            }
            $filepath = $dir . '/' . $file['title'];
            $exists = file_exists($filepath);
            if ($exists) {
                $mesg = $langs->trans("ErrorFileAlreadyExists");
                // FIXME: duplicated code. Factorize me!
                if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
                    setEventMessages($mesg, '', 'errors');
                } elseif ($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) { // DOL_VERSION >= 3.3
                    setEventMessage($mesg, 'errors');
                } else {
                    $_SESSION['DolMessage'] = $mesg;
                }
                dol_syslog('process error: ' . $mesg, LOG_ERR);
            } else {
                $res = file_put_contents($filepath, $file['content']);
            }
        }
        //file/link has been created
        if ($res) {
            $mesg = $langs->trans("FileTransferComplete");
            // FIXME: duplicated code. Factorize me!
            if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
                setEventMessages($mesg, '');
            } elseif ($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) { // DOL_VERSION >= 3.3
                setEventMessage($mesg);
            } else {
                $_SESSION['DolMessage'] = $mesg;
            }
            dol_syslog('process success: ' . $mesg, LOG_INFO);
        } elseif (!$res && !$exists) { //file/link didn't already exist but creation failed
            $mesg = $langs->trans("ErrorFileNotUploaded");
            // FIXME: duplicated code. Factorize me!
            if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
                setEventMessages($mesg, '', 'errors');
            } elseif ($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) { // DOL_VERSION >= 3.3
                setEventMessage($mesg, 'errors');
            } else {
                $_SESSION['DolMessage'] = $mesg;
            }
            dol_syslog('process error: ' . $mesg, LOG_ERR);
        }
    } catch (Exception $e) {
        // FIXME: report error to next page
        dol_syslog($e->getMessage(), LOG_ERR);
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
