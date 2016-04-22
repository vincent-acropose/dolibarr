<?php
/* Copyright (C) 2014-2015	Charlie BENKE	<charlie@patas-monkey.com>
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
 * \file htdocs/customtabs/fiche.php
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

// require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
$langs->load("customtabs@customtabs");

// Security check
$result = restrictedArea($user, 'customtabs', $rowid, '');

$elementid = GETPOST("elementid");
$modeid = GETPOST("modeid");
$fk_statut = GETPOST("fk_statut");

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$elementid = '';
	$modeid = - 1;
	$fk_statut = - 1;
}
/*
 *	Actions
 */

/*
 * View
 */

llxHeader('', $langs->trans("CustomTabs"), 'EN:Module_customtabs|FR:Module_customtabs|ES:M&oacute;dulo_customtabs');

$form = new Form($db);

// Liste of customtabs

$customtabs = new Customtabs($db);
$tblelement = elementarray();

print_fiche_titre($langs->trans("CustomTabsList"));
print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Label") . '</td>';
print '<td align="center">' . $langs->trans("Element") . '</td>';
print '<td align="left">' . $langs->trans("TableName") . '</td>';
print '<td align="center">' . $langs->trans("ModeCustomTabs") . '</td>';
print '<td align="center">' . $langs->trans("FichierGED") . '</td>';
print '<td align="center">' . $langs->trans("Parent") . '</td>';
print '<td align="center">' . $langs->trans("ActiveStatut") . '</td>';
print '<td align="center">';
print '<input type="image" class="liste_titre" name="button_removefilter" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
print '</td>';
print "</tr>\n";
print '<tr class="liste_titre">';
print '<td></td>';
print '<td align="center">' . $form->selectarray("elementid", $tblelement, $elementid, 1) . '</td>';
print '<td align="left"></td>';
print '<td align="center">' . $form->selectarray("modeid", modearray(), $modeid, 1) . '</td>';
print '<td align="center"></td>';
print '<td align="center"></td>';
print '<td align="center">' . $form->selectyesno("fk_statut", $fk_statut, 1, false, 1) . '</td>';
print '<td align="center">';
print '<input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
print '</td>';
print "</tr>\n";

$var = True;
$tblcustomtabs = $customtabs->liste_array(($elementid != - 1 ? $elementid : ''), $modeid, ($fk_statut == '' ? $fk_statut = - 1 : $fk_statut));
if (count($tblcustomtabs) > 0)
	foreach ( $tblcustomtabs as $customtabsarray ) {
		$customtabs->fetch($customtabsarray['rowid']);
		$var = ! $var;
		print "<tr " . $bc[$var] . ">";
		print '<td><a href="card.php?rowid=' . $customtabsarray['rowid'] . '">';
		print img_object($langs->trans("ShowComplement"), 'list') . ' ' . $customtabsarray['libelle'] . '</a></td>';
		
		print '<td align=center>' . $tblelement[$customtabs->element] . '</td>';
		print '<td align=left>llx_cust_' . $customtabs->tablename . '_extrafields</td>';
		print '<td align="center">' . getmodelib($customtabs->mode) . '</td>';
		print '<td align="center">' . yn($customtabs->files) . '</td>';
		print '<td align="center">' . $customtabs->parentname . '</td>';
		print '<td align="center">' . yn($customtabs->fk_statut) . '</td>';
		print '<td align="center"></td>';
		print "</tr>";
		$i ++;
	}
print "</table>";

print '</form>' . "\n";

/*
 * Barre d'actions
 *
 */
print '<div class="tabsAction">';

// New type
if ($user->rights->customtabs->configurer) {
	print '<a class="butAction" href="card.php?action=create">' . $langs->trans("NewCustomtabs") . '</a>';
}

print "</div>";

llxFooter();
$db->close();

?>
