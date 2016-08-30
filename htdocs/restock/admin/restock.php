<?php
/* Copyright (C) 2014	  Charles-Fr BENKE	 <charles.fr@benke.fr>
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
 *	  \file	   htdocs/restock/admin/restock.php
 *		\ingroup	restock
 *		\brief	  Page to setup restock module
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/restock/core/lib/restock.lib.php";
require_once DOL_DOCUMENT_ROOT.'/restock/class/restock.class.php';
// les classes pour les status
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php";

$langs->load("restock@restock");
$langs->load("admin");
$langs->load("errors");
$langs->load("propal");
$langs->load("orders");
$langs->load("bills");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$value = GETPOST('value','alpha');



/*
 * Actions
 */

if ($action == 'setvalue')
{
	// save the setting
	dolibarr_set_const($db, "RESTOCK_PROPOSAL_DRAFT", GETPOST('select0propals','int'),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "RESTOCK_PROPOSAL_VALIDATE", GETPOST('select1propals','int'),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "RESTOCK_PROPOSAL_SIGNED", GETPOST('select2propals','int'),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "RESTOCK_ORDER_DRAFT", GETPOST('select0commandes','int'),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "RESTOCK_ORDER_VALIDATE", GETPOST('select1commandes','int'),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "RESTOCK_ORDER_PARTIAL", GETPOST('select2commandes','int'),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "RESTOCK_BILL_DRAFT", GETPOST('select0factures','int'),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "RESTOCK_BILL_VALIDATE", GETPOST('select1factures','int'),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "RESTOCK_BILL_PARTIAL", GETPOST('select2factures','int'),'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "RESTOCK_COEF_ORDER_CLIENT_FOURN", GETPOST('coefcmdclient2fourn','int'),'chaine',0,'',$conf->entity);

	$mesg = "<font class='ok'>".$langs->trans("SetupSaved")."</font>";
}


// Get setting 
$select0propals=$conf->global->RESTOCK_PROPOSAL_DRAFT;
$select1propals=$conf->global->RESTOCK_PROPOSAL_VALIDATE;
$select2propals=$conf->global->RESTOCK_PROPOSAL_SIGNED;
$select0commandes=$conf->global->RESTOCK_ORDER_DRAFT;
$select1commandes=$conf->global->RESTOCK_ORDER_VALIDATE;
$select2commandes=$conf->global->RESTOCK_ORDER_PARTIAL;
$select0factures=$conf->global->RESTOCK_BILL_DRAFT;
$select1factures=$conf->global->RESTOCK_BILL_VALIDATE;
$select2factures=$conf->global->RESTOCK_BILL_PARTIAL;

$coefcmdclient2fourn=$conf->global->RESTOCK_COEF_ORDER_CLIENT_FOURN;

/*
 * View
 */

$title = $langs->trans('RestockSetup');
$tab = $langs->trans("RestockSetup");


llxHeader("",$langs->trans("RestockSetup"),'EN:Restock_Configuration|FR:Configuration_module_Restock|ES:Configuracion_Restock');

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("RestockSetup"),$linkback,'setup');

$head = restock_admin_prepare_head();
dol_fiche_head($head, 'setup', $tab, 0, 'restock@restock');


// la sélection des status à suivre dans le process commercial
print '<br>';
print_titre($langs->trans("DisplayPonderedSetting"));
print '<br>';

print '<form method="post" action="restock.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';
print '<table class="noborder" >';
print '<tr class="liste_titre">';
print '<td width="160px">'.$langs->trans("BusinessDocuments").'</td>';
print '<td colspan=6 width=600px align=center>'.$langs->trans("PonderableStatus").'</td>';
print '</tr >';

// ligne des propales
$generic_status = new Propal($db);
print '<tr >';
print '<td>'.$langs->trans("ProposalShort").'</td>';
print checkvalue('select0propals', $select0propals, $generic_status->LibStatut(0,1));
print checkvalue('select1propals', $select1propals, $generic_status->LibStatut(1,1));
print checkvalue('select2propals', $select2propals, $generic_status->LibStatut(2,1));
print '</tr>'."\n";

// liste des commandes
$generic_status = new Commande($db);
print '<tr >';
print '<td>'.$langs->trans("Commande").'</td>';
print checkvalue('select0commandes', $select0commandes, $generic_status->LibStatut(0,0,1));
print checkvalue('select1commandes', $select1commandes, $generic_status->LibStatut(1,0,1));
print checkvalue('select2commandes', $select2commandes, $generic_status->LibStatut(2,0,1));

print '</tr>'."\n";
// ligne des factures
$generic_status = new Facture($db);
print '<tr >';
print '<td>'.$langs->trans("Bills").'</td>';
print checkvalue('select0factures', $select0factures, $generic_status->LibStatut(0,0,1,-1));
print checkvalue('select1factures', $select1factures, $generic_status->LibStatut(0,1,1,-1));
print checkvalue('select2factures', $select2factures, $generic_status->LibStatut(0,3,1, 1)." ".$langs->trans("Partial"));
print '</tr>'."\n";
print '<tr ><td colspan=7><hr></td></tr>';
print '<tr ><td>'.$langs->trans("CoefCommandeClient2fournisseur").'</td>';
print checkvalue('coefcmdclient2fourn', $coefcmdclient2fourn, $langs->trans("Remise"));
print '<td colspan=4>'.$langs->trans("InfoCoefCommandeClient2fournisseur").'</td>';
print '</tr>'."\n";

print '</table>';



// Boutons d'action
print '<div class="tabsAction">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</div>';
print '</td></tr>'."\n";

print '</form>';


dol_htmloutput_mesg($mesg);

$db->close();
llxFooter();

function checkvalue($selectname, $selectValue, $label)
{
	$formother = new FormOther($db);
	global $conf, $langs;
	$temp= '<td align=right>';
	$temp.=" ".$formother->select_percent($selectValue, $selectname);
	$temp.='</td><td align=left>';
	$temp.=$label;
	$temp.='</td>';
	return $temp;
}
?>
