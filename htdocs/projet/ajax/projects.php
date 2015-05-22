<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file htdocs/product/ajax/products.php
 * \brief File to return Ajax response on product list request
 */
if (! defined('NOTOKENRENEWAL'))
	define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU'))
	define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))
	define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))
	define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))
	define('NOCSRFCHECK', '1');
if (empty($_GET ['keysearch']) && ! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');

require '../../main.inc.php';

$htmlname = GETPOST('htmlname', 'alpha');
$socid = GETPOST('socid', 'int');
$type = GETPOST('type', 'int');
$mode = GETPOST('mode', 'int');
$status = ((GETPOST('status', 'int') >= 0) ? GETPOST('status', 'int') : - 1);
$outjson = (GETPOST('outjson', 'int') ? GETPOST('outjson', 'int') : 0);
$price_level = GETPOST('price_level', 'int');
$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$price_by_qty_rowid = GETPOST('pbq', 'int');

/*
 * View
 */

// print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

dol_syslog(join(',', $_GET));
// print_r($_GET);

require DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

$arrayresult = array();

$sql = 'SELECT rowid, ref, title
		FROM '.MAIN_DB_PREFIX.'projet
		WHERE fk_statut > 0 
		AND 
		(
			title LIKE "%'.GETPOST('projectid').'%"
			OR
			ref LIKE "%'.GETPOST('projectid').'%"
		)
		';

$resql = $db->query($sql);
$i=0;
while($res = $db->fetch_object($resql)) {
	$arrayresult[$i]['key'] = $res->rowid;
	$arrayresult[$i]['value'] = $res->ref;
	$arrayresult[$i]['label'] = $res->ref.' : '.$res->title;
	$i++;
}

echo json_encode($arrayresult);

