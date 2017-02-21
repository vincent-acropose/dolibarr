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
// from standard dolibarr install
if (! $res && file_exists('../main.inc.php')) {
        $res = @include('../main.inc.php');
}
// from custom dolibarr install
if (! $res && file_exists('../../main.inc.php')) {
        $res = @include('../../main.inc.php');
}
if (! $res) {
    die("Main include failed");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once './lib/drive.lib.php';
dol_include_once('/zenfusionoauth/class/TokenStorage.class.php');

$dolibarr_version = versiondolibarrarray();

if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 4) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.4
    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
} else {
    require_once './lib/compatibility.lib.php';
}

use \zenfusion\oauth\TokenStorage;

global $langs, $db, $user, $conf;

$langs->load('zenfusiondrive@zenfusiondrive');
$file = GETPOST('file', 'alpha');
$modulepart = GETPOST('modulepart', 'alpha');
$parent_id = GETPOST('parent_id', 'alpha');

if (empty($file) || empty($modulepart) || (!$user->rights->zenfusiondrive->use && !$user->admin)) {
    accessforbidden();
} else {

    $check_access = dol_check_secure_access_document(
        $modulepart,
        $file,
        $conf->entity
    );
    $file = $check_access['original_file'];
    $tokenstorage = TokenStorage::getUserToken($db, $user);
    $res = 0;
    if ($tokenstorage) {
        $res = uploadToDrive($tokenstorage->token, $file, $parent_id);
    }
    //set message
    if ($res) {
        $mesg = $langs->trans('ObjectUploadSuccess');
        // FIXME: duplicated code. Factorize me!
        if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
            setEventMessages($mesg, '');
        } elseif ($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) { // DOL_VERSION >= 3.3
            setEventMessage($mesg);
        } else {
            $mesg = urlencode('<font class="ok">' . $mesg . '</font>');
            $msg = '&msg=' . $mesg;
        }
    } else {
        $mesg = $langs->trans('ObjectUploadFailure');
        // FIXME: duplicated code. Factorize me!
        if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 7) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.7
            setEventMessages($mesg, '', 'errors');
        } elseif ($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) { // DOL_VERSION >= 3.3
            setEventMessage($mesg, 'errors');
        } else {
            $mesg = urlencode('<font class="error">' . $mesg . '</font>');
            $msg = '&msg=' . $mesg;
        }
    }
    header('Location: ' . $_SERVER['HTTP_REFERER'] . $msg);
    exit;
}
