<?php
/* Copyright (C) 2013		Charles-Fr BENKE	<charles.fr@benke.fr>
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
 *    \file       htdocs/mylist/liste.php
 *    \ingroup    Liste
 *    \brief      liste des pages de tableaux de bord
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
require_once 'class/mydoliboard.class.php';

$langs->load('mydoliboard@mydoliboard');

if (!$user->rights->mydoliboard->lire) accessforbidden();

llxHeader("","",$langs->trans("Mydoliboard"));

print_fiche_titre($langs->trans("MydoliboardSetting"));

$LT = new Mydoliboard($db);
$lists = $LT->get_all_mydoliboard();

if ($lists != -1)
{
	print '<table id="mydoliboard" class="noborder" width="100%">';
	print '<thead>';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("label").'</th>';
	print '<th>'.$langs->trans("mainmenu").'</th>';
	print '<th>'.$langs->trans("leftmenu").'</th>';
	print '<th>'.$langs->trans("menutitle").'</th>';
	print '<th>'.$langs->trans("perms").'</th>';
	print '<th width=100px>'.$langs->trans("author").'</th>';
	print '<th width=50px>'.$langs->trans("active").'</th>';
	print '<th width=100px align=right>'.$langs->trans("nbBoards").'</th>';
	print '</tr>';
	print '</thead>';
	print '<tbody>';
	$var=true;
	foreach ($lists as $list)
	{
		$var = ! $var;

		if ($list['rowid'])
		{
			$LT->fetch($list['rowid']);
			print "<tr ".$bc[$var].">\n";
			print "\t<td>".$LT->getNomUrl(1)."</td>\n";
			print "<td align='left'>".$list['mainmenu']."</td>\n";
			print "<td align='left'>".$list['leftmenu']."</td>\n";
			print "<td align='left'>".$list['titlemenu']."</td>\n";
			print "<td align='left'>".$list['perms']."</td>\n";
			print "<td align='right'>".$list['author']."</td>\n";
			print "<td align='right'>".yn($list['active'])."</td>\n";
			print "<td align='right'>".$list['nbBoard']."</td>\n";
			print "</tr>\n";
		}
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
if ($user->rights->mydoliboard->creer)
	print '<a class="butAction" href="fiche.php?action=create">'.$langs->trans('NewPage').'</a>';
else
	print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('NewPage').'</a>';

print "</div>";

$db->close();
llxFooter();

if (!empty($conf->global->MAIN_USE_JQUERY_DATATABLES))
{
	print "\n";
	print '<script type="text/javascript">'."\n";
	print 'jQuery(document).ready(function() {'."\n";
	print 'jQuery("#mydoliboard").dataTable( {'."\n";
	print '"sDom": \'TCR<"clear">lfrtip\','."\n";
	print '"oColVis": {"buttonText": "'.$langs->trans('showhidecols').'" },'."\n";
	print '"bPaginate": true,'."\n";
	print '"bFilter": false,'."\n";
	print '"sPaginationType": "full_numbers",'."\n";
	print '"bJQueryUI": false,'."\n"; 
	print '"oLanguage": {"sUrl": "'.$langs->trans('datatabledict').'" },'."\n";
	print '"iDisplayLength": 25,'."\n";
	print '"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],'."\n";
	print '"bSort": true,'."\n";
	print '"oTableTools": { "sSwfPath": "../includes/jquery/plugins/datatables/extras/TableTools/swf/copy_csv_xls_pdf.swf" }'."\n";
	print '} );'."\n";
	print '});'."\n";
	print '</script>'."\n";
}
?>
