<?php
/* Copyright (C) 2015	Charlie BENKE	<charlie@patas-monkey.com>
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
 *      \file       htdocs/myfield/groupright.php
 * 		\ingroup	customtabs
 *      \brief      Page setting usergroup right on myfield
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory

require_once 'class/myfield.class.php';

if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	if (! $user->rights->user->group_advance->read && ! $user->admin)
		accessforbidden();
}

$langs->load("users");
$langs->load("myfield@myfield");


$action=GETPOST('action','alpha');
$actionright=GETPOST('right','alpha');
$usergroup=GETPOST('fk_usergroup','int');
$fk_myfield=GETPOST('fk_myfield','int');

// Create user from a member
if ($action == 'addread')
{
	$sql="INSERT INTO ".MAIN_DB_PREFIX."myfield_usergroup_rights";
	$sql.=" (fk_usergroup, fk_myfield, rights) VALUE ";
	$sql.=" (".$usergroup.", ".$fk_myfield.", '')";
	$resql = $db->query($sql);
}
else if ($action == 'delread')
{
	$sql="DELETE FROM ".MAIN_DB_PREFIX."myfield_usergroup_rights";
	$sql.=" WHERE fk_usergroup=".$usergroup;
	$sql.=" AND   fk_myfield=".$fk_myfield;
	$resql = $db->query($sql);
}
else if ($action == 'changeright')
{
	$sql="UPDATE ".MAIN_DB_PREFIX."myfield_usergroup_rights";
	if ($actionright[0] == 'A')
		$sql.=" SET rights = CONCAT(rights, '".$actionright[1]."')";
	else
		$sql.=" SET rights = replace(rights, '".$actionright[1]."', '')";
	$sql.=" WHERE fk_usergroup=".$usergroup;
	$sql.=" AND   fk_myfield=".$fk_myfield;
	$resql = $db->query($sql);
}
/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("GroupRight"));

$sql = "SELECT g.rowid, g.nom, g.entity, g.datec";
$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && ($conf->multicompany->transverse_mode || ($user->admin && ! $user->entity)))
{
	$sql.= " WHERE g.entity IS NOT NULL";
}
else
{
	$sql.= " WHERE g.entity IN (0,".$conf->entity.")";
}
$sql.= $db->order($sortfield,$sortorder);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	
	$param="&search_group=".urlencode($search_group)."&amp;sall=".urlencode($sall);
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Group"),$_SERVER["PHP_SELF"],"g.nom",$param,"","",$sortfield,$sortorder);
	//multicompany
	if(! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1)
	{
		print_liste_field_titre($langs->trans("Entity"),$_SERVER["PHP_SELF"],"g.entity",$param,"",'align="center"',$sortfield,$sortorder);
	}
	//print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"g.datec",$param,"",'align="right"',$sortfield,$sortorder);
	$myField = new Myfield($db);
	$tblfields = $myField ->get_all_myfield();
	if (count($tblfields) >0 )
	{
		foreach($tblfields as $fieldsarray)
		{	//var_dump($customtabsarray);
			print_liste_field_titre($fieldsarray['label'],$_SERVER["PHP_SELF"],"",$param,"",'align="center"',$sortfield,$sortorder);
		}
	}

	print "</tr>\n";
	$var=True;
	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		
		print "<tr $bc[$var] style='height:24px;'>";
		print '<td><a href="card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$obj->nom.'</a>';
		if (! $obj->entity)
		{
			print img_picto($langs->trans("GlobalGroup"),'redstar');
		}
		print "</td>";
		//multicompany
		if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1)
		{
			$mc->getInfo($obj->entity);
			print '<td align="center">'.$mc->label.'</td>';
		}
		
		if (count($tblfields) > 0 ) 
			foreach($tblfields as $key => $value)
				print "<td align=center valign=top>".getRightGroupType($obj->rowid, $key )."</td>";

		print "</tr>\n";
		$i++;
	}
	print "</table>";
	$db->free();
}
else
{
    dol_print_error($db);
}


llxFooter();
$db->close();

function getRightGroupType($idusergroup, $idmyfield)
{
	global $db;
	global $langs;
	$sql = "SELECT cur.rights FROM ".MAIN_DB_PREFIX."myfield_usergroup_rights as cur , ".MAIN_DB_PREFIX."myfield as m";
	$sql .= " WHERE cur.fk_usergroup=".$idusergroup;
	$sql .= " AND cur.fk_myfield=".$idmyfield;
	$sql .= " AND cur.fk_myfield=m.rowid";
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		if ($num == 0)
		{
			$szres='<table ><tr><td >';
			$szres.='<a href="'.$_SERVER["PHP_SELF"].'?action=addread&amp;fk_usergroup='.$idusergroup.'&amp;fk_myfield='.$idmyfield.'">';
			$szres.=img_picto($langs->trans("DisabledRead"), "user_red@myfield" ).'</a>';
			$szres.='</td><td width=16px>';
			$szres.='</td></tr></table>';
		}
		else
		{
			$obj = $db->fetch_object($resql);
			$szres='<table><tr><td >';
			$szres.='<a href="'.$_SERVER["PHP_SELF"].'?action=delread&amp;fk_usergroup='.$idusergroup.'&amp;fk_myfield='.$idmyfield.'">';
			$szres.=img_picto($langs->trans("EnabledRead"), "user@myfield" );
			$szres.='</a></td><td>';
			if (strpos ($obj->rights, 'U')=== false)
			{
				$szres.='<a href="'.$_SERVER["PHP_SELF"].'?action=changeright&amp;right=AU&amp;fk_usergroup='.$idusergroup.'&amp;fk_myfield='.$idmyfield.'">';
				$szres.=img_picto($langs->trans("DisabledWrite"), "user_edit_red@myfield" ).'</a>';
			}
			else
			{
				$szres.='<a href="'.$_SERVER["PHP_SELF"].'?action=changeright&amp;right=DU&amp;fk_usergroup='.$idusergroup.'&amp;fk_myfield='.$idmyfield.'">';
				$szres.=img_picto($langs->trans("EnabledWrite"), "user_edit@myfield" ).'</a>';
			}
			$szres.='</td></tr></table>';
		}
	}
	return $szres;
}
?>
