<?php
/* Copyright (C) 2012-2013	Charles-Fr Benke		<charles.fr@benke.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       mydoliboard/core/lib/mydoliboard.lib.php
 *	\brief      Ensemble de fonctions de base pour le module mydoliboard
 *	\ingroup    mydoliboard
 */

function mydoliboard_admin_prepare_head ()
{
	global $langs, $conf, $user;
	
	$h = 0;
	$head = array();
	
	$head[$h][0] = DOL_URL_ROOT.'/mydoliboard/admin/admin.php';
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'admin';
	
	$h++;
	$head[$h][0] = DOL_URL_ROOT.'/mydoliboard/admin/about.php';
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';

	return $head;
}

/**
 *	Return list of entrepot (for the stock
  *
 *	@param  string	$selected       Preselected type
 *	@param  string	$htmlname       Name of field in html form
 * 	@param	int		$showempty		Add an empty field
 * 	@param	int		$hidetext		Do not show label before combo box
 *  @return	void
 */
function select_mdbpage($selected='',$htmlname='fk_mdbpage',$showempty=0,$hidetext=0)
{
    global $db,$langs,$user,$conf;

	if (empty($hidetext)) print $langs->trans("doliboardPage").': ';

	// boucle sur les entrepots 
	$sql = "SELECT rowid, label";
	$sql.= " FROM ".MAIN_DB_PREFIX."mydoliboard";

	dol_syslog("mydoliboard.Lib::select_mdbpage sql=".$sql);

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty)
			{
				print '<option value="-1"';
				if ($selected == -1) print ' selected="selected"';
				print '>&nbsp;</option>';
			}
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->rowid.'"';
				if ($obj->rowid == $selected) print ' selected="selected"';
				print ">".$obj->label."</option>";
				$i++;
			}
			print '</select>';
		}
		else
		{
			// si pas de liste, on positionne un hidden à vide
			print '<input type="hidden" name="'.$htmlname.'" value=-1>';
		}
	}
}

function select_displaycell($selected='', $htmlname='displaycell', $showempty=0)
{
    global $db ,$langs, $user, $conf;

	$sz= '<select class="flat" name="'.$htmlname.'">';
	$sz.= '<option value="A"';
	if ($selected == "A") $sz.=' selected="selected"';
	$sz.= '>A - '.$langs->trans("Amode").'</option>';
	$sz.= '<option value="B"';
	if ($selected == "B") $sz.=' selected="selected"';
	$sz.= '>B - '.$langs->trans("Bmode").'</option>';
	$sz.= '<option value="C"';
	if ($selected == "C") $sz.=' selected="selected"';
	$sz.= '>C - '.$langs->trans("Cmode").'</option>';
	$sz.= '<option value="D"';
	if ($selected == "D") $sz.=' selected="selected"';
	$sz.= '>D - '.$langs->trans("Dmode").'</option>';
	$sz.= '</select>';
	return $sz;
}


function select_elementtab($selected='', $htmlname='elementtab')
{

	global $langs;

	$tmp="<select name=".$htmlname.">";
	$tmp.="<option value='' >".$langs->trans("NotInTab")."</option>";
	$tmp.="<option value='Societe' ".($selected=="Societe"?" selected ":"").">".$langs->trans("Societe")."</option>";
	$tmp.="<option value='Product' ".($selected=="Product"?" selected ":"").">".$langs->trans("Product")."</option>";
	$tmp.="<option value='CategProduct' ".($selected=="CategProduct"?" selected ":"").">".$langs->trans("CategProduct")."</option>";
	$tmp.="<option value='CategSociete' ".($selected=="CategSociete"?" selected ":"").">".$langs->trans("CategSociete")."</option>";
	$tmp.="</select>";
	return $tmp;

}
function select_blocmode($htmlname='blocAmode', $selected='')
{
    global $db, $langs, $user, $conf;

	$sz = '<select class="flat" name="'.$htmlname.'">';
	$sz.= '<option value="0"';
	if ($selected == "0") $sz.=' selected="selected"';
	$sz.= '>0 - '.$langs->trans("0mode").'</option>';
	$sz.= '<option value="1"';
	if ($selected == "1") $sz.=' selected="selected"';
	$sz.= '>1 - '.$langs->trans("1mode").'</option>';
	$sz.= '<option value="2"';
	if ($selected == "2") $sz.=' selected="selected"';
	$sz.= '>2 - '.$langs->trans("2mode").'</option>';
	$sz.= '<option value="3"';
	if ($selected == "3") $sz.=' selected="selected"';
	$sz.= '>3 - '.$langs->trans("3mode").'</option>';
	$sz.= '</select>';
	return $sz;
}

// générate the list of sheet in a box
function blocsheet($pageid, $listsUsed, $cellsheet)
{
	global $langs;
	$sz= $langs->trans('Bloc').' '.$cellsheet;
	$sz.= '<table width=100% id="tablelines'.$cellsheet.'" class="noborder" >';
	$sz.= '<thead>';
	$sz.= '<tr class="liste_titre">';
	$sz.= '<th width=10px>&nbsp;</th>';
	$sz.= '<th width=10px>&nbsp;</th>';
	$sz.= '<th width=150px >'.$langs->trans('TitleSheet').'</td>' ;;
	$sz.= '<th width=75px>'.$langs->trans('author').'</th>' ;
	$sz.= '<th width=75px>'.$langs->trans('perms').'</th>' ;
	$sz.= '<th width=50px>'.$langs->trans('langs').'</th>' ;
	$sz.= '<th width=100px align=center>'.$langs->trans('active').'</th>' ;
	$sz.= "</tr>\n";
	$sz.= "</thead><tbody>\n";
	$var=true;
	foreach ($listsUsed as $key=> $value )
	{
		if ($value['displaycell']==$cellsheet)
		{
			$var=!$var;
			$sz.= '<tr '.$bc[$var].' pageid="'.$pageid.'" displaycell="'.$value['displaycell'].'" cellorder="'.$value['cellorder'].'">'."\n";
			$sz.= '<td align=center class="upArrow"><img src="./img/1uparrow.png"></td><td align=center class="downArrow"><img src="./img/1downarrow.png"></td>';
			$sz.= '<td><a href=board.php?pageid='.$pageid.'&rowid='.urlencode($key).'>'.$value['titlesheet'].'</a></td>' ;

			$sz.= '<td>'.$value['author'].'</td>' ;
			$sz.= '<td>'.$value['perms'].'</td>' ;
			$sz.= '<td>'.$value['langs'].'</td>' ;
			$sz.= '<td align="center">'.yn($value['active']).'</td>' ;
			$sz.= "</tr>\n";
		}
	}
	$sz.="</tbody></table><br>\n";	
	return $sz;
}
?>
