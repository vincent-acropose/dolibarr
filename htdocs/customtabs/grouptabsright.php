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
 * \file htdocs/customtabs/grouptabsright.php
 * \ingroup customtabs
 * \brief Page setting usergroup right on custom tabs
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include ($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory

require_once 'class/customtabs.class.php';

if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
	if (! $user->rights->user->group_advance->read && ! $user->admin)
		accessforbidden();
}

$langs->load("users");
$langs->load("customtabs@customtabs");

$action = GETPOST('action', 'alpha');
$actionright = GETPOST('right', 'alpha');
$usergroup = GETPOST('fk_usergroup', 'int');
$fk_customtabs = GETPOST('fk_customtabs', 'int');

// Create user from a member
if ($action == 'addread') {
	$sql = "INSERT INTO " . MAIN_DB_PREFIX . "customtabs_usergroup_rights";
	$sql .= " (fk_usergroup, fk_customtabs, rights) VALUE ";
	$sql .= " (" . $usergroup . ", " . $fk_customtabs . ", '')";
	$resql = $db->query($sql);
} else if ($action == 'delread') {
	$sql = "DELETE FROM " . MAIN_DB_PREFIX . "customtabs_usergroup_rights";
	$sql .= " WHERE fk_usergroup=" . $usergroup;
	$sql .= " AND   fk_customtabs=" . $fk_customtabs;
	$resql = $db->query($sql);
} else if ($action == 'changeright') {
	$sql = "UPDATE " . MAIN_DB_PREFIX . "customtabs_usergroup_rights";
	if ($actionright[0] == 'A')
		$sql .= " SET rights = CONCAT(rights, '" . $actionright[1] . "')";
	else
		$sql .= " SET rights = replace(rights, '" . $actionright[1] . "', '')";
	$sql .= " WHERE fk_usergroup=" . $usergroup;
	$sql .= " AND   fk_customtabs=" . $fk_customtabs;
	$resql = $db->query($sql);
}
/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("CustomTabsSettingRight"));

$sql = "SELECT g.rowid, g.nom, g.entity, g.datec";
$sql .= " FROM " . MAIN_DB_PREFIX . "usergroup as g";
if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && ($conf->multicompany->transverse_mode || ($user->admin && ! $user->entity))) {
	$sql .= " WHERE g.entity IS NOT NULL";
} else {
	$sql .= " WHERE g.entity IN (0," . $conf->entity . ")";
}
$sql .= $db->order($sortfield, $sortorder);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	
	$param = "&search_group=" . urlencode($search_group) . "&amp;sall=" . urlencode($sall);
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Group"), $_SERVER["PHP_SELF"], "g.nom", $param, "", "", $sortfield, $sortorder);
	// multicompany
	if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1) {
		print_liste_field_titre($langs->trans("Entity"), $_SERVER["PHP_SELF"], "g.entity", $param, "", 'align="center"', $sortfield, $sortorder);
	}
	// print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"g.datec",$param,"",'align="right"',$sortfield,$sortorder);
	
	$customtabs = new Customtabs($db);
	$tbltabs = $customtabs->liste_array();
	if (count($tbltabs) > 0) {
		foreach ( $tbltabs as $customtabsarray ) { // var_dump($customtabsarray);
			if (empty($customtabsarray['fk_parent']))
				$lib = "";
			else
				$lib = "Sous-";
			if ($customtabsarray['mode'] == 1)
				$lib .= "Fiche<br>" . $customtabsarray['libelle'];
			else
				$lib .= "Liste<br>" . $customtabsarray['libelle'];
			print_liste_field_titre($lib, $_SERVER["PHP_SELF"], "", $param, "", 'align="center"', $sortfield, $sortorder);
		}
	}
	
	print "</tr>\n";
	$var = True;
	while ( $i < $num ) {
		$obj = $db->fetch_object($resql);
		$var = ! $var;
		
		print "<tr $bc[$var] style='height:48px;'>";
		print '<td><a href="card.php?id=' . $obj->rowid . '">' . img_object($langs->trans("ShowGroup"), "group") . ' ' . $obj->nom . '</a>';
		if (! $obj->entity) {
			print img_picto($langs->trans("GlobalGroup"), 'redstar');
		}
		print "</td>";
		// multicompany
		if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1) {
			$mc->getInfo($obj->entity);
			print '<td align="center">' . $mc->label . '</td>';
		}
		// print '<td align="right" nowrap="nowrap">'.dol_print_date($db->jdate($obj->datec),"dayhour").'</td>';
		
		if (count($tbltabs) > 0)
			foreach ( $tbltabs as $key => $value )
				print "<td align=center valign=top>" . getRightGroupType($obj->rowid, $key) . "</td>";
		
		print "</tr>\n";
		$i ++;
	}
	print "</table>";
	$db->free();
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();
function getRightGroupType($idusergroup, $idcustomtabs) {
	global $db;
	global $langs;
	$sql = "SELECT cur.rights, c.mode FROM " . MAIN_DB_PREFIX . "customtabs_usergroup_rights as cur , " . MAIN_DB_PREFIX . "customtabs as c";
	$sql .= " WHERE cur.fk_usergroup=" . $idusergroup;
	$sql .= " AND cur.fk_customtabs=" . $idcustomtabs;
	$sql .= " AND cur.fk_customtabs=c.rowid";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num == 0) {
			$szres = '<table ><tr><td >';
			$szres .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=addread&amp;fk_usergroup=' . $idusergroup . '&amp;fk_customtabs=' . $idcustomtabs . '">';
			$szres .= img_picto($langs->trans("DisabledRead"), "user_red@customtabs") . '</a>';
			$szres .= '</td><td width=16px>';
			$szres .= '</td></tr></table>';
		} else {
			$obj = $db->fetch_object($resql);
			$szres = '<table><tr><td >';
			$szres .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=delread&amp;fk_usergroup=' . $idusergroup . '&amp;fk_customtabs=' . $idcustomtabs . '">';
			$szres .= img_picto($langs->trans("EnabledRead"), "user@customtabs");
			$szres .= '</a></td><td>';
			if (strpos($obj->rights, 'U') === false) {
				$szres .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=changeright&amp;right=AU&amp;fk_usergroup=' . $idusergroup . '&amp;fk_customtabs=' . $idcustomtabs . '">';
				$szres .= img_picto($langs->trans("DisabledWrite"), "user_edit_red@customtabs") . '</a>';
			} else {
				$szres .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=changeright&amp;right=DU&amp;fk_usergroup=' . $idusergroup . '&amp;fk_customtabs=' . $idcustomtabs . '">';
				$szres .= img_picto($langs->trans("EnabledWrite"), "user_edit@customtabs") . '</a>';
			}
			$szres .= '</td></tr><tr><td>';
			// ajout et suppression seulement sur le mode liste
			if ($obj->mode == 2) {
				if (strpos($obj->rights, 'A') === false) {
					$szres .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=changeright&amp;right=AA&amp;fk_usergroup=' . $idusergroup . '&amp;fk_customtabs=' . $idcustomtabs . '">';
					$szres .= img_picto($langs->trans("DisabledAdd"), "user_add_red@customtabs") . '</a>';
				} else {
					$szres .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=changeright&amp;right=DA&amp;fk_usergroup=' . $idusergroup . '&amp;fk_customtabs=' . $idcustomtabs . '">';
					$szres .= img_picto($langs->trans("EnabledAdd"), "user_add@customtabs") . '</a>';
				}
				
				$szres .= '</td><td>';
				if (strpos($obj->rights, 'D') === false) {
					$szres .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=changeright&amp;right=AD&amp;fk_usergroup=' . $idusergroup . '&amp;fk_customtabs=' . $idcustomtabs . '">';
					$szres .= img_picto($langs->trans("DisabledDelete"), "user_delete_red@customtabs") . '</a>';
				} else {
					$szres .= '<a href="' . $_SERVER["PHP_SELF"] . '?action=changeright&amp;right=DD&amp;fk_usergroup=' . $idusergroup . '&amp;fk_customtabs=' . $idcustomtabs . '">';
					$szres .= img_picto($langs->trans("EnabledDelete"), "user_delete@customtabs") . '</a>';
				}
			}
			$szres .= '</td></tr></table>';
		}
	}
	return $szres;
}
?>
