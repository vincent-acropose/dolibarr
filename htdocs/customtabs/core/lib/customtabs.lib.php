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
 * or see http://www.gnu.org/
 */

/**
 * \file htdocs/customtabs/core/lib/customtabs.lib.php
 * \brief Ensemble de fonctions de base pour custom-parc
 */

/**
 * Return array head with list of tabs to view object informations
 *
 * @param Object $object Member
 * @return array head
 */
function customtabs_prepare_head_menu($object, $fk_parent) {
	global $db, $langs, $conf, $user;
	
	$h = 0;
	$head = "";
	
	// create the sous-tabs
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
	$usr_group = new UserGroup($db);
	$group_array = $usr_group->listGroupsForUser($user->id);
	if (is_array($group_array) && count($group_array) > 0) {
		$sql = "SELECT distinct c.rowid, c.libelle, c.tablename, c.mode  ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "customtabs as c, " . MAIN_DB_PREFIX . "customtabs_usergroup_rights as cur, " . MAIN_DB_PREFIX . "usergroup_user as ugu";
		$sql .= " WHERE c.fk_statut=1";
		$sql .= " AND c.fk_parent=" . $fk_parent; // gestion des sous-onglets
		$sql .= " AND cur.fk_customtabs =c.rowid";
		$sql .= " AND ugu.fk_user =" . $user->id;
		$sql .= " AND ugu.fk_usergroup = cur.fk_usergroup";
	} else {
		// si pas de groupe on ne se soucis pas des habilitations
		$sql = "SELECT distinct c.rowid, c.libelle, c.tablename, c.mode  ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "customtabs as c";
		$sql .= " WHERE c.fk_statut=1";
		$sql .= " AND c.fk_parent=" . $fk_parent; // gestion des sous-onglets
	}
	
	dol_syslog("customtabs.Lib::customtabs_prepare_head_menu sql=" . $sql);
	
	$resql = $db->query($sql);
	if ($resql) {
		$head = array ();
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num) {
			while ( $i < $num ) {
				$obj = $db->fetch_object($resql);
				
				$h ++;
				switch ($obj->mode) {
					case 1 : // mode fiche
					        // selon le mode on est en fiche ou en liste
						$head[$h][0] = DOL_URL_ROOT . '/customtabs/tabs/menu_card.php?tabsid=' . $obj->rowid . '&id=' . $object->id;
						break;
					case 2 : // mode liste
						$head[$h][0] = DOL_URL_ROOT . '/customtabs/tabs/menu_list.php?tabsid=' . $obj->rowid . '&id=' . $object->id;
						break;
				}
				$head[$h][1] = $langs->trans($obj->libelle);
				$head[$h][2] = "customtabs_" . $obj->rowid; // tablename;
				$i ++;
			}
		}
	}
	return $head;
}
function customtabs_prepare_head($object) {
	global $langs, $conf, $user;
	
	$h = 0;
	$head = array ();
	
	$head[$h][0] = DOL_URL_ROOT . '/customtabs/card.php?rowid=' . $object->rowid;
	$head[$h][1] = $langs->trans("TabsCard");
	$head[$h][2] = 'general';
	
	$h ++;
	$head[$h][0] = DOL_URL_ROOT . '/customtabs/extrafields.php?rowid=' . $object->rowid;
	$head[$h][1] = $langs->trans("TabsFields");
	$head[$h][2] = 'attributes';
	
	$h ++;
	$head[$h][0] = DOL_URL_ROOT . '/customtabs/template.php?rowid=' . $object->rowid;
	$head[$h][1] = $langs->trans("TabsTemplate");
	$head[$h][2] = 'template';
	
	// uniquement pour les onglets de type liste
	if ($object->mode == 2) {
		$h ++;
		$head[$h][0] = DOL_URL_ROOT . '/customtabs/import.php?rowid=' . $object->rowid;
		$head[$h][1] = $langs->trans("TabsImport");
		$head[$h][2] = 'import';
	}
	
	// $h++;
	// $head[$h][0] = DOL_URL_ROOT.'/customtabs/categorie.php?rowid='.$object->rowid;
	// $head[$h][1] = $langs->trans("TabsCategories");
	// $head[$h][2] = 'categories';
	
	// $h++;
	// $head[$h][0] = DOL_URL_ROOT.'/customtabs/calcfields.php?rowid='.$object->rowid;
	// $head[$h][1] = $langs->trans("TabsCalcFields");
	// $head[$h][2] = 'calculate';
	
	// $h++;
	// $head[$h][0] = DOL_URL_ROOT.'/customtabs/querybutton.php?rowid='.$object->rowid;
	// $head[$h][1] = $langs->trans("TabsButton");
	// $head[$h][2] = 'button';
	
	// $h++;
	// $head[$h][0] = DOL_URL_ROOT.'/customtabs/demo.php?rowid='.$object->rowid;
	// $head[$h][1] = $langs->trans("TabsDemo");
	// $head[$h][2] = 'demo';
	
	return $head;
}
function customtabs_admin_prepare_head() {
	global $langs, $conf, $user;
	
	$h = 0;
	$head = array ();
	
	$head[$h][0] = DOL_URL_ROOT . '/customtabs/admin/customtabs.php';
	$head[$h][1] = $langs->trans("Setup");
	$head[$h][2] = 'admin';
	
	$h ++;
	$head[$h][0] = DOL_URL_ROOT . '/customtabs/admin/about.php';
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	
	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param Object $object Object related to tabs
 * @param string $type Type of category
 * @return array Array of tabs to shoc
 */
function dictionary_prepare_head($object) {
	global $langs, $conf, $user;
	// var_dump($conf->modules_parts['tabs']);
	$langs->load("customtabs@customtabs");
	
	$h = 0;
	$head = array ();
	
	$head[$h][0] = DOL_URL_ROOT . '/customtabs/dictionary.php';
	$head[$h][1] = $langs->trans("Dictionarys");
	$head[$h][2] = 'card';
	$h ++;
	
	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to add new tab
	// $this->tabs = array('entity:-tabname); to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'dictionary');
	
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'dictionary', 'remove');
	
	return $head;
}
function elementarray() {
	global $langs, $conf;
	$langs->load("companies");
	$langs->load("sendings");
	$langs->load('projects');
	$langs->load('contracts');
	$langs->load("members");
	$langs->load("bills");
	$langs->load("orders");
	$langs->load("propal");
	$langs->load("banks");
	$langs->load("salaries");
	
	$arrayelement = array ();
	
	// on g�re selon l'activation ou non du module
	$arrayelement['thirdparty'] = $langs->trans("ThirdParties");
	$arrayelement['contact'] = $langs->trans("Contact");
	$arrayelement['product'] = $langs->trans("Product");
	$arrayelement['stock'] = $langs->trans("Warehouse");
	if (! empty($conf->propal->enabled))
		$arrayelement['propal'] = $langs->trans("Proposal");
	if (! empty($conf->commande->enabled))
		$arrayelement['commande'] = $langs->trans("Order");
	if (! empty($conf->fournisseur->enabled))
		$arrayelement['supplier_order'] = $langs->trans("SupplierOrder");
	if (! empty($conf->facture->enabled))
		$arrayelement['invoice'] = $langs->trans("Invoice");
	if (! empty($conf->fournisseur->enabled))
		$arrayelement['supplier_invoice'] = $langs->trans("SupplierInvoice");
	$arrayelement['project'] = $langs->trans("Project");
	if (! empty($conf->contrat->enabled))
		$arrayelement['contract'] = $langs->trans("Contract");
	if (! empty($conf->ficheinter->enabled))
		$arrayelement['intervention'] = $langs->trans("Intervention");
	if (! empty($conf->banque->enabled))
		$arrayelement['bank'] = $langs->trans("Account");
	$arrayelement['delivery'] = $langs->trans("Shipment");
	$arrayelement['user'] = $langs->trans("User");
	$arrayelement['usergroup'] = $langs->trans("Group");
	$arrayelement['member'] = $langs->trans("Member");
	$arrayelement['dictionary'] = $langs->trans("Dictionary");
	if (! empty($conf->salaries->enabled))
		$arrayelement['payment_salaries'] = $langs->trans("SalaryPayment");
	$arrayelement['payment_vat'] = $langs->trans("VATPayment");
	$arrayelement['tax'] = $langs->trans("SocialContribution");
	
	// specific modules
	if (! empty($conf->global->MAIN_MODULE_EQUIPEMENT)) {
		$langs->load("equipement@equipement");
		$arrayelement['equipement'] = $langs->trans("Equipement");
	}
	if (! empty($conf->global->MAIN_MODULE_FACTORY)) {
		$langs->load("factory@factory");
		$arrayelement['factory'] = $langs->trans("Factory");
	}
	if (! empty($conf->global->MAIN_MODULE_LEAD)) {
		$langs->load("lead@lead");
		$arrayelement['lead'] = $langs->trans("Lead");
	}
	if (! empty($conf->global->MAIN_MODULE_AGEFODD)) {
		$langs->load("agefodd@agefodd");
		// $arrayelement['agefodd'] = $langs->trans("AgfCatalogDetail");
		$arrayelement['agefodd_training'] = "AGEFODD - " . $langs->trans("AgfTraining");
		$arrayelement['agefodd_trainee'] = "AGEFODD - " . $langs->trans("AgfParticipant");
		$arrayelement['agefodd_trainer'] = "AGEFODD - " . $langs->trans("AgfFormateur");
		$arrayelement['agefodd_session'] = "AGEFODD - " . $langs->trans("AgfSessionDetail");
		$arrayelement['agefodd_contact'] = "AGEFODD - " . $langs->trans("AgfContact");
		$arrayelement['agefodd_site'] = "AGEFODD - " . "Site de formation";
		$arrayelement['agefodd_cursus'] = "AGEFODD - " . $langs->trans("AgfMenuCursus");
		$arrayelement['agefodd_agenda'] = "AGEFODD - " . $langs->trans("AgfMenuAgenda");
	}
	
	if (! empty($conf->global->CUSTOMTABS_OPTIONAL_MODULES)) {
		$lstoptionalmodules = explode(":", $conf->global->CUSTOMTABS_OPTIONAL_MODULES);
		
		foreach ( $lstoptionalmodules as $optionalmodules ) {
			$langs->load($optionalmodules . "@" . $optionalmodules);
			$arrayelement[$optionalmodules] = $langs->trans(ucfirst($optionalmodules));
		}
	}
	
	return $arrayelement;
}
function modearray() {
	global $langs;
	$arraymode = array ();
	$arraymode[1] = $langs->trans("Fiche");
	$arraymode[2] = $langs->trans("Liste");
	return $arraymode;
}
function getmodelib($modetab) {
	global $langs;
	
	if ($modetab == 1) {
		return $langs->trans("Fiche");
	}
	if ($modetab == 2) {
		return $langs->trans("Liste");
	}
}

?>