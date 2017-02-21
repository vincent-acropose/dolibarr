<?php
/* Copyright (C) 2015		Charlie BENKE	<charlie@patas-monkey.com>
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
 *    \file       htdocs/myfield/list.php
 *    \ingroup    myfield
 *    \brief      Page liste des champs personnalisées
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/myfield/class/myfield.class.php';
require_once DOL_DOCUMENT_ROOT.'/myfield/core/lib/myfield.lib.php';


$langs->load('myfield@myfield');

if (!$user->rights->myfield->lire) accessforbidden();

llxHeader("","",$langs->trans("Myfield"));

print_fiche_titre($langs->trans("MyfieldList"));

$LT = new Myfield($db);
$lists = $LT->get_all_myfield();
if ($lists != -1)
{
	print '<table id="listtable" class="noborder" width="100%">';
	print '<thead>';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("myFieldLabel").'</th>';
	print '<th>'.$langs->trans("context").'</th>';
	print '<th width=100px>'.$langs->trans("author").'</th>';
	print '<th width=150px>'.$langs->trans("activeMode").'</th>';
	print '<th width=100px>'.$langs->trans("Compulsory").'</th>';
	print '<th width=100px>'.$langs->trans("Color").'</th>';
	print '<th width=100px>'.$langs->trans("replacement").'</th>';
	print '</tr>';
	print '</thead>';
	print '<tbody>';
	foreach ($lists as $list)
	{
		print "<tr >\n";
		print "\t<td><a href='card.php?rowid=".$list['rowid']."'>".($list['label']? $list['label']: "Myfield ".$list['rowid'] )."</a></td>\n";
		print "<td align='left'>".$list['context']."</td>\n";
		print "<td align='left'>".$list['author']."</td>\n";
		print "<td align='center'>".ShowActiveMode($list['active'])."</td>\n";
		print "<td align='center'>".yn($list['compulsory'])."</td>\n";
		print "<td align='left'>".$list['color']."</td>\n";
		print "<td align='left'>".$list['replacement']."</td>\n";
		print "</tr>\n";
	}
	print '</tbody>';
	print "</table>";
}
else
{
	dol_print_error();
}

/*
 * Boutons actions
 */
print '<br>';
print '<div class="tabsAction">';
if ($user->rights->myfield->creer)
{
	print '<a class="butAction" href="card.php?action=create">'.$langs->trans('NewField').'</a>';
}
else
{
	print '<a class="butActionRefused" href="#" title="'.$langs->trans("NorightForCreateField").'">'.$langs->trans('NewField').'</a>';
}

print "</div>";

$db->close();
llxFooter();

if (!empty($conf->global->MAIN_USE_JQUERY_DATATABLES))
{
	print "\n";
	print '<script type="text/javascript">'."\n";
	print 'jQuery(document).ready(function() {'."\n";
	print 'jQuery("#listtable").dataTable( {'."\n";

	print '"oColVis": {"buttonText": "'.$langs->trans('showhidecols').'" },'."\n";
	print '"bPaginate": true,'."\n";
	print '"bFilter": false,'."\n";
	print '"sPaginationType": "full_numbers",'."\n";
	print '"bJQueryUI": false,'."\n"; 
	print '"oLanguage": {"sUrl": "'.$langs->trans('mfdatatabledict').'" },'."\n";
	print '"iDisplayLength": 25,'."\n";
	print '"aLengthMenu": [[10, 15, 25, 50, -1], [10, 15, 25, 500, "All"]],'."\n";
	print '"bSort": true,'."\n";
	print '} );'."\n";
	print '});'."\n";
	print '</script>'."\n";
}
?>
