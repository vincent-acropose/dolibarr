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
 * \file admin/conf.php
 * \ingroup zenfusiondrive
 * \brief Module configuration page
 */
$res = 0;
// from standard dolibarr install
if (! $res && file_exists('../../main.inc.php')) {
        $res = @include('../../main.inc.php');
}
// from custom dolibarr install
if (! $res && file_exists('../../../main.inc.php')) {
        $res = @include('../../../main.inc.php');
}
if (! $res) {
    die("Main include failed");
}

require_once '../lib/admin.lib.php';
require_once '../lib/drive.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/zenfusionoauth/lib/zfcopybutton.lib.php');

global $langs, $user, $db, $conf;

$langs->load('zenfusionoauth@zenfusionoauth');
$langs->load('zenfusiondrive@zenfusiondrive');
$langs->load('admin');
$langs->load('help');

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
unset($_POST['action']);
unset($_GET['action']);
$error = 0; // Error counter

/*
 * Actions
 */

$driveusers = selectDriveUsers();
$mesg = '';

if ($action == 'adduser') {
    $user_id = GETPOST('userid', 'int');
    $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'zenfusion_drive(userid)';
    $sql .= ' VALUES(' . $user_id . ')';
    $resql = $db->query($sql);
    if ($resql) {
        $db->commit();
        $mesg = '<font class="ok">' . $langs->trans("Saved") . '</font>';
    } else {
        $db->rollback();
        $mesg = '<font class="error">'
            . $langs->trans("UnexpectedError")
            . '</font>';
    }
    $_SESSION['mesg'] = $mesg;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
} elseif ($action == 'deluser') {
    $linecount = GETPOST('linecount', 'int');
    unset($_POST['linecount']);
    if ($linecount > 0) {
        for ($i = 0; $i < $linecount; $i++) {
            $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'zenfusion_drive';
            $sql .= ' WHERE rowid = ' . GETPOST('id' . $i, 'int');
            $resql = $db->query($sql);
            if ($resql) {
                //success mesg
                $db->commit();
            } else {
                //err mesg
                $db->rollback();
            }
        }
    }
    $_SESSION['mesg'] = $mesg;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
} elseif ($action == 'saveDB') {
    if ($driveusers) {
        foreach ($driveusers as $driveuser) {
            $ids[] = $driveuser->oauthid;
        }
        $driveusersids = implode(',', $ids);
        $res = saveDatabaseToDrive($driveusersids);
        if ($res) {
            $mesg = '<font class="ok">'
            . $langs->trans("SaveDBSuccess")
            . '</font>';
        } else {
            $mesg = '<font class="error">'
            . $langs->trans("SaveDBError")
            . '</font>';
        }
    } else {
        $mesg = '<font class="error">'
            . $langs->trans("NoDriveUsersSelected")
            . '</font>';
    }
} elseif ($action == 'set') {
    require_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';
    require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
    dol_include_once('/zenfusiondrive/core/modules/modZenFusionDrive.class.php');
    $mod = new modZenFusionDrive($db);
    $cronsave = new Cronjob($db);
    $cronsave->label = $langs->trans('SaveDB');
    $cronsave->jobtype = 'function';
    $cronsave->module_name = $mod->name;
    $cronsave->libname = 'admin.lib.php';
    $cronsave->methodename = 'saveDB';
    $ids = array();
    foreach ($driveusers as $driveuser) {
        $ids[] = $driveuser->oauthid;
    }
    $cronsave->params = implode(',', $ids);
    $cronsave->datestart = dol_now();
    //placeholder, the real frequency is defined by the sysadmin cron task
    $cronsave->frequency = 60;
    $cronsave->unitfrequency = $cronsave->frequency;
    $cronsave->status = 1;
    $cronid = $cronsave->create($user);
    if ($cronid <= 0) {
        $error++;
    }

    $res = dolibarr_set_const(
        $db,
        'SAVE_DB_CRON_TASK',
        $cronid,
        '',
        0,
        '',
        $conf->entity
    );

    if (!$res) {
        $error++;
    }

    $res = dolibarr_set_const(
        $db,
        'CRON_KEY',
        getRandomPassword(true),
        'chaine',
        0,
        '',
        $conf->entity
    );

    if (!$res) {
        $error++;
    }

    if ($error == 0) {
        $mesg = '<font class="ok">' . $langs->trans("Saved") . '</font>';
    } else {
        $mesg = '<font class="error">'
            . $langs->trans("UnexpectedError")
            . '</font>';
    }
} elseif ($action == 'reset') {
    $cronid = $conf->global->SAVE_DB_CRON_TASK;
    require_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';
    $cronsave = new Cronjob($db);
    $cronsave->id = $cronid;
    $del = $cronsave->delete($user);
    if (!$del) {
        $error++;
    }
    $res = dolibarr_del_const($db, 'SAVE_DB_CRON_TASK', $conf->entity);
    if (!$res) {
        $error++;
    }
    $res = dolibarr_del_const($db, 'CRON_KEY', $conf->entity);
    if (!$res) {
        $error++;
    }
    if (!$error) {
        $mesg = '<font class="ok">' . $langs->trans("Saved") . '</font>';
    } else {
        $mesg = '<font class="error">'
            . $langs->trans("UnexpectedError")
            . '</font>';
    }
}

unset($action);

/**
 * view
 */
llxHeader();
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print load_fiche_titre("ZenFusion", $linkback, 'setup');

$head = zfPrepareHead();
dol_fiche_head(
    $head,
    'conf',
    $langs->trans("Module105004Name"),
    0,
    'drive@zenfusiondrive'
);

// Error / confirmation messages
if (!$mesg) {
    $mesg = $_SESSION['mesg'];
}
dol_htmloutput_mesg($mesg);

print load_fiche_titre($langs->trans("DriveUsersManagement"));
echo '<br>';

if ($driveusers) {
    echo '<form method="POST" action="' , $_SERVER['PHP_SELF'] , '">',
         '<input type="hidden" name="token" value="' , $_SESSION['newtoken'] , '">',
         '<input type="hidden" name="action" value="deluser">',
         '<input type="hidden" name="linecount" value="' , count($driveusers) , '">',
         '<table class="noborder" width="40%">',
         '<tr class="liste_titre">',
         '<td></td>',
         '<td>' , $langs->trans("DriveUsers") , '</td>',
         '</tr>';
    $i = 0;
    foreach ($driveusers as $driveuser) {
        echo '<tr><td><input type="checkbox" name="' , $i , '"></td>',
             '<td>' , $driveuser->getFullName($langs) , '</td>',
             '<input type="hidden" name="id' , $i , '" value="' , $driveuser->id , '">',
             '</tr>';
        $i++;
    }
    echo '</table>',
         '<table class="noborder" width="40%">',
         '<tr><td align="right">',
         '<input class="button" type="submit" value="' ,
         $langs->trans('Delete') , '" style="width:99px;"></td></tr></table>',
         '</form><br>';
}

if ($conf->global->SAVE_DB_CRON_TASK) {
    $button = '<a href="' . $_SERVER['PHP_SELF'] . '?action=reset">'
           . img_picto($langs->trans("Activated"), 'switch_on')
           . '</a>';
} else {
    $button = '<a href="' . $_SERVER['PHP_SELF'] . '?action=set">'
           . img_picto($langs->trans("Disabled"), 'switch_off')
           . '</a>';
}

echo '<form method="POST" action="' , $_SERVER['PHP_SELF'] , '">',
     '<input type="hidden" name="token" value="' , $_SESSION['newtoken'] , '">',
     '<input type="hidden" name="action" value="adduser">',
     '<table class="noborder" width="40%">',
     '<tr class="liste_titre">',
     '<td>' , $langs->trans("AddUser") , '</td>',
     '<td></td>',
     '</tr>',
     '<tr>',
     '<td>', selectOauthUsers('userid', 1), '</td>',
     '<td align="right">',
     '<input type="submit" class="button" value ="',
     $langs->trans("Add"), '" style="width:99px;">',
     '</td></tr>',
     '</table></form><br>',

     '<form method="POST" action="' , $_SERVER['PHP_SELF'] , '">',
     '<input type="hidden" name="token" value="' , $_SESSION['newtoken'] , '">',
     '<input type="hidden" name="action" value="saveDB">',
     '<table class="noborder" width="40%">',
     '<tr class="liste_titre">',
     '<td>' , $langs->trans("SaveDB") , '</td>',
     '<td align="right"><input type="submit" class="button" value="'
     , $langs->trans('Save') , '" style="width:99px;"></td>',
     '</tr></table></form><br>',

     '<form method="POST" action="' , $_SERVER['PHP_SELF'] , '">',
     '<input type="hidden" name="token" value="' , $_SESSION['newtoken'] , '">',
     '<input type="hidden" name="action" value="cronTask">',
     '<table class="noborder" width="40%">',
     '<tr class="liste_titre">',
     '<td>' , $langs->trans("SaveCronTask") , '</td>',
     '<td>&nbsp;</td></tr>',
     '<tr><td>' , $langs->trans('Activate') , '</td>',
     '<td align="right">' , $button , '</td></tr>';

if ($conf->global->SAVE_DB_CRON_TASK && $conf->global->CRON_KEY) {
    $langs->load("cron");
    $crontaskid = $conf->global->SAVE_DB_CRON_TASK;
    $linuxlike = 1;
    if (preg_match('/^win/i', PHP_OS)) {
        $linuxlike = 0;
    }
    if (preg_match('/^mac/i', PHP_OS)) {
        $linuxlike = 0;
    }
    $key = $conf->global->CRON_KEY;
    $params = $key . ' ' . $user->login . ' ' . $crontaskid;
    //look for the scripts directory
    $rootarray = explode('/', DOL_DOCUMENT_ROOT);
    $size = count($rootarray);
    $doldir = array();
    for ($i = 0; $i < $size - 1; $i++) {
        $doldir[] = $rootarray[$i];
    }
    $scriptdir = implode('/', $doldir) . '/scripts';
    $cmd = 'php ' . $scriptdir . '/cron/cron_run_jobs.php' . ' ' . $params;
    $button = zfCopyToClipboardButton($cmd);
    echo '<tr><td>' , $langs->trans("FileToLaunchCronJobs") , ':</td>',
         '<td align="right">' , $cmd , $button , '</td></tr>',
         '<tr><td>' , $langs->trans("Note") , ':</td><td align="right">';
    if ($linuxlike) {
        echo $langs->trans("CronExplainHowToRunUnix");
    } else {
        echo $langs->trans("CronExplainHowToRunWin");
    }

}
echo '</td></tr></table></form><br>';

llxFooter();
