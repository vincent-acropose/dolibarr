<?php
/* Export to accounting module for Dolibarr
 * Copyright (C) 2013  Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \file admin/support.php
 * \ingroup accountingexport
 * \brief Support page
 */
$res = 0;
// from standard dolibarr install
if (! $res && file_exists("../../main.inc.php")) {
        $res = @include("../../main.inc.php");
}
// from custom dolibarr install
if (! $res && file_exists("../../../main.inc.php")) {
        $res = @include("../../../main.inc.php");
}
if (! $res) {
    die("Main include failed");
}

require_once '../lib/message.lib.php';

$langs->load("webmail@webmail");
$langs->load("admin");
$langs->load("help");

// only readable by admin
if (! $user->admin) {
    accessforbidden();
}

/*
 * View
 */
$page_name = "WebmailSupport";
llxHeader('', $langs->trans($page_name));

/// Navigation in the modules
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print_fiche_titre($langs->trans($page_name), $linkback, 'setup');

$head = webmailadmin_prepare_head();

dol_fiche_head($head, 'support', $langs->trans("Webmail"), 0, 'webmail@webmail');

echo '<a target="_blank" href="http://2byte.gotdns.com/liveagent/index.php?type=page&urlcode=669978&title=M%C3%B3dulo-2WebMail">',
//	'<img src="../img/logo_assist.png" alt="', $langs->trans("HelpCenter"),'">',
    $langs->trans("HelpCenter"),
    '</a>',
    '<br>';

llxFooter();
