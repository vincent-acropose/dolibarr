<?php
/* Copyright (C) 2014	Charles-Fr BENKE	<charles.fr@benke.fr>
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
 * \file htdocs/customtabs/dictionary.php
 * \ingroup member
 * \brief complement fiche
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include ($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory

require_once 'core/lib/customtabs.lib.php';
require_once 'class/customtabs.class.php';
require_once 'class/dictionary.class.php';

$langs->load("customtabs@customtabs");

// Security check
$result = restrictedArea($user, 'customtabs', $rowid, '');

/*
 *	Actions
 */

$object = new Dictionary($db);
$object->fetch($id, $ref);
/*
 * View
 */

llxHeader('', $langs->trans("CustomTabs"), 'EN:Module_customtabs|FR:Module_customtabs|ES:M&oacute;dulo_customtabs');

$form = new Form($db);

/* ************************************************************************** */
/*                                                                            */
/* Visualisation / Edition de la fiche                                        */
/*                                                                            */
/* ************************************************************************** */

$head = dictionary_prepare_head($object);
dol_fiche_head($head, 'card', $langs->trans("Dictionarys"), 0, 'customtabs@customtabs');

llxFooter();
$db->close();
?>
