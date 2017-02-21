<?php
/* Copyright (C) 2014		Charles.fr Benke	<charles.fr@benke.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/customlink/tabs/supplier_order.php
 *	\brief      liaison de facture fournisseur
 *	\ingroup    customlink
 */
$res=@include("../../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");        // For "custom" directory


require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php');

dol_include_once('/customlink/class/customlink.class.php');
dol_include_once('/customlink/core/lib/customlink.lib.php');

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	$langs->load('projects');
}


$langs->load("companies");
$langs->load("customlink@customlink");
$langs->load("bills");

$id = (GETPOST('id','int') ? GETPOST('id','int') : GETPOST('facid','int'));
$ref = GETPOST('ref','alpha');
$action	= GETPOST('action','alpha');

// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

$object = new FactureFournisseur($db);
$object->fetch($id,$ref);

$customlinkstatic = new Customlink($db);
// on alimente les clés
$customlinkstatic->fk_source=$id;


// suppression d'une ventilation
if ($action=="deletebillink")
{
	$customlinkstatic->rowid = GETPOST("facture_link");
	$customlinkstatic->deleteventilation($user);
	$action="";
}


// Ajout d'une ventilation
if ($action=="addbillink")
{
	$dateventil=dol_mktime(0,0,0,GETPOST('datevmonth','int'),GETPOST('datevday','int'),GETPOST('datevyear','int'));	
	$customlinkstatic->type_source="invoice_supplier";
	$typeobjectlinked=GETPOST("typeobjectlinked");
	// on active la bonne liaison selon le type de target
	if ($typeobjectlinked == 0)
		$customlinkstatic->type_target="facture";
	else
		$customlinkstatic->type_target="invoice_supplier";
	// on récupère l'id de la facture à lier
	$customlinkstatic->fk_target = $customlinkstatic->get_idlink($customlinkstatic->type_target, GETPOST("reffact"));
	
	if ($customlinkstatic->fk_target >= 0) // on crée le lien
		$customlinkstatic->addventil(GETPOST("subprice"), GETPOST("tva_tx"), GETPOST("qty"), GETPOST("label"), $dateventil);
	else
	{
		setEventMessage($langs->trans("ErrorRefNotFound",$langs->transnoentities("RefTarget")),'errors');
		$error++;
	}
}


/*
 *	View
*/

$form = new Form($db);

llxHeader();

$object->fetch_thirdparty();

$head = facturefourn_prepare_head($object);
$titre=$langs->trans('SupplierInvoice');
dol_fiche_head($head, 'customlink', $titre, 0, 'bill');

$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

print '<table class="border" width="100%">';

print '<tr><td width="20%" nowrap="nowrap">'.$langs->trans("Ref").'</td><td colspan="3">';
//print $form->showrefnav($object,'facid','',1,'rowid','ref',$morehtmlref);
print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '</td>';
print "</tr>\n";

// Ref supplier
print '<tr><td nowrap="nowrap">'.$langs->trans("RefSupplier").'</td><td colspan="3">'.$object->ref_supplier.'</td>';
print "</tr>\n";

// Company
print '<tr><td>'.$langs->trans('Supplier').'</td><td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

// Type
print '<tr><td>'.$langs->trans('Type').'</td><td colspan="4">';
print $object->getLibType();
if ($object->type == 1)
{
	$facreplaced=new FactureFournisseur($db);
	$facreplaced->fetch($object->fk_facture_source);
	print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
}
if ($object->type == 2)
{
	$facusing=new FactureFournisseur($db);
	$facusing->fetch($object->fk_facture_source);
	print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
}
// Label
print '<tr><td>'.$langs->trans('Label').'</td>';
print '<td colspan="3">'.$object->label.'</td>';

	// Date invoice
	print '<tr><td>';
	print $langs->trans('Date');
	print '</td><td colspan="3">';
	print dol_print_date($object->date,'daytext');

	print '</td>';
// Status
$alreadypaid=$object->getSommePaiement();
print '<tr><td>'.$langs->trans('Status').'</td><td colspan="3">'.$object->getLibStatut(4,$alreadypaid).'</td></tr>';

print '<tr><td>'.$langs->trans('AmountHT').'</td><td width=150px align="right">'.price($object->total_ht,1,$langs,0,-1,-1,$conf->currency).'</td><td colspan="2" align="left">&nbsp;</td></tr>';
print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right">'.price($object->total_tva,1,$langs,0,-1,-1,$conf->currency).'</td><td colspan="2" align="left">&nbsp;</td></tr>';
print '<tr><td>'.$langs->trans('AmountTTC').'</td><td  align="right">'.price($object->total_ttc,1,$langs,0,-1,-1,$conf->currency).'</td><td colspan="2" align="left">&nbsp;</td></tr>';

// Project
if (! empty($conf->projet->enabled))
{
	print '<tr>';
	print '<td>';
	print $langs->trans('Project');
	print '</td><td colspan="3">';
	$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id,$object->socid,$object->fk_project,'none');
	print '</td></tr>';
}
print '</table>';
print '<br>';

// liste des factures ventilé sur cette facture
$sql = "SELECT * ";
$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn_ventil as ffv";
$sql.= " WHERE ffv.entity = ".$conf->entity;
$sql .= " AND ffv.fk_facture_link =".$id;
$sql .= " AND ffv.fk_facture_typelink =1";
$sql.= " ORDER BY ffv.rowid";
$sql.= $db->plimit($limit+1, $offset);
//print $sql;
$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	if ($num > 0 ) 
	{
		// c'est forcément une facture fournisseur qui est ventilé
		$Facture=new FactureFournisseur($db);
	
		print_barre_liste($langs->trans("ListOfVentiledBillsInput"), $page, "facturefournventil.php",$urlparam,$sortfield,$sortorder,'',$num,0,'ventilinput@customlink');
	
		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">'."\n";
		print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
		print '<table class="noborder" width="100%">';
	
		print "<tr class=\"liste_titre\">";
		print_liste_field_titre($langs->trans("Ref"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Company"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateInvoice"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateVentilation"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("label"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("PriceUHT"),"","","",$urlparam,'',$sortfield,$sortorder);
	    print_liste_field_titre($langs->trans("VAT"),"","","",$urlparam,'',$sortfield,$sortorder);
	    print_liste_field_titre($langs->trans("Qty"),"","","",$urlparam,'',$sortfield,$sortorder);
	    print_liste_field_titre($langs->trans("PriceUTTC"),"","","",$urlparam,'',$sortfield,$sortorder);
		print "</tr>\n";
	
	
		$var=True;
		$total = 0;
		$i = 0;
		while ($i < min($num, $limit))
		{
			$objp = $db->fetch_object($result);
			$var=!$var;
			print "<tr $bc[$var]>";
			$linkedobject = new FactureFournisseur($db);
			$linkedobject->fetch($objp->fk_facture_link);
			print "<tr $bc[$var]>";
			print "<td>".$linkedobject->getNomUrl()."</td>";
			$soc = new Societe($db);
			$soc->fetch($linkedobject->socid);
			print "<td>".$soc->getNomUrl()."</td>";
			print "<td>".dol_print_date($linkedobject->date,"daytext")."</td>";
			print "<td>".dol_print_date($objp->datev,"daytext")."</td>";
			print "<td>".$objp->label."</td>";
			print "<td>".price($objp->subprice)."</td>";
			print "<td>".price($objp->tva_tx)."</td>";
			print "<td>".$objp->qty."</td>";
			print "<td>".price($objp->total_ttc)."</td>";
			print "</tr>\n";
		
			$i++;
		}
	
		print '</table>';
		print '<br>';
	}
}

dol_fiche_end();
dol_fiche_head();

print_barre_liste($langs->trans("AddNewVentilation"), $page, "facturefournventil.php",$urlparam,$sortfield,$sortorder,'',$num,0,'ventilation@customlink');

$value_qty=1;

// Ajout d'un lien sur une facture cliente
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addbillink">';
print '<input type="hidden" name="typeobjectlinked" value="0">';
print '<input type="hidden" name="id" value="'.$id.'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width=175px ><a name="addcustom"></a>'; // ancre
print $langs->trans('CustomerBillAdd').'</td>';
print_liste_field_titre($langs->trans("DateVentilation"),"","","",$urlparam,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("PriceUHT"),"","","",$urlparam,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("VAT"),"","","",$urlparam,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Qty"),"","","",$urlparam,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("label"),"","","",$urlparam,'',$sortfield,$sortorder);

print "<td></td>";
print "</tr>\n";
print "<tr >\n";
print '<td><input type=text name=reffact size=10 value="'.$value_pu.'"></td>';
print '<td>';
print $form->select_date("",'datev',0,0,'',"datev");
print '</td>';
print '<td align="left"><input type="text" size="8" name="subprice" value="'.$value_pu.'"></td>';
print '<td align="left">'.$form->load_tva('tva_tx',$value_tauxtva).'</td>';
print '<td align="left"><input type="text" size="3" name="qty" value="'.$value_qty.'"></td>';
print '<td><input type=text name=label size=30 value="'.$label.'"></td>';
print '<td align="center" valign="middle" >';
print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline">';
print '</td>';
print "</tr>\n";
print '</table >';
print '</form>'."\n";

// Ajout d'un lien sur une facture fournisseur
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addbillink">';
print '<input type="hidden" name="typeobjectlinked" value="1">';
print '<input type="hidden" name="id" value="'.$id.'">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td width=175px ><a name="addfournish"></a>'; // ancre
print $langs->trans('FournishBillAdd').'</td>';
print_liste_field_titre($langs->trans("DateVentilation"),"","","",$urlparam,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("PriceUHT"),"","","",$urlparam,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("VAT"),"","","",$urlparam,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Qty"),"","","",$urlparam,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("label"),"","","",$urlparam,'',$sortfield,$sortorder);
print "<td></td>";
print "</tr>\n";
print "<tr >\n";
print '<td><input type=text name=reffact size=10 value="'.$value_pu.'"></td>';
print '<td>';
print $form->select_date("",'datev',0,0,'',"datev");
print '</td>';

print '<td align="left"><input type="text" size="8" name="amount" value="'.$value_pu.'"></td>';
print '<td align="left">'.$form->load_tva('tauxtva',$value_tauxtva).'</td>';
print '<td align="left"><input type="text" size="3" name="qty" value="'.$value_qty.'"></td>';
print '<td><input type=text name=label size=30 value="'.$label.'"></td>';
print '<td align="center" valign="middle" >';
print '<input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline">';
print '</td>';
print "</tr>\n";
print '</table >';
print '</form>'."\n";

dol_fiche_end();
dol_fiche_head();

// ensuite la liste des montant associés aux factures

$sql = "SELECT * ";
$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn_ventil as ffv";
$sql.= " WHERE ffv.entity = ".$conf->entity;
$sql .= " AND ffv.fk_facture_fourn =".$id;
$sql.= " ORDER BY ffv.rowid";
$sql.= $db->plimit($limit+1, $offset);

$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	if ($num >0) 
	{
		print_barre_liste($langs->trans("ListOfVentiledBillsOutput"), $page, "facturefournventil.php",$urlparam,$sortfield,$sortorder,'',$num,0,'ventiloutput@customlink');
	
		print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
		print '<table class="noborder" width="100%">';
	
		print "<tr class=\"liste_titre\">";
		print_liste_field_titre($langs->trans("Ref"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Company"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateInvoice"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateVentilation"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("label"),"","","",$urlparam,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("PriceUHT"),"","","",$urlparam,'',$sortfield,$sortorder);
	    print_liste_field_titre($langs->trans("VAT"),"","","",$urlparam,'',$sortfield,$sortorder);
	    print_liste_field_titre($langs->trans("Qty"),"","","",$urlparam,'',$sortfield,$sortorder);
	    print_liste_field_titre($langs->trans("PriceUTTC"),"","","",$urlparam,'',$sortfield,$sortorder);
	    print_liste_field_titre("","","","","",'',"","");
		print "</tr>\n";
		
		$var=True;
		$total = 0;
		$i = 0;
		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
	
			$var=!$var;
			if ($objp->fk_facture_typelink==0)
				$linkedobject = new Facture($db);
			else
				$linkedobject = new FactureFournisseur($db);
			$linkedobject->fetch($objp->fk_facture_link);
			print "<tr $bc[$var]>";
			print "<td>".$linkedobject->getNomUrl()."</td>";
			$soc = new Societe($db);
			$soc->fetch($linkedobject->socid);
			print "<td>".$soc->getNomUrl()."</td>";
			print "<td>".dol_print_date($linkedobject->date,"daytext")."</td>";
			print "<td>".dol_print_date($objp->datev,"daytext")."</td>";
			print "<td>".$objp->label."</td>";
			print "<td>".price($objp->subprice)."</td>";
			print "<td>".price($objp->tva_tx)."</td>";
			print "<td>".$objp->qty."</td>";
			print "<td>".price($objp->total_ttc)."</td>";
			print "<td><a href='facturefournventil.php?action=deletebillink&id=".$id."&facture_link=".$objp->rowid."'>".img_delete()."</a></td>";
			print "</tr>\n";
		
			$i++;
		}
	
		print '</table>';
	}
	$db->free($result);
}
else
{
	dol_print_error($db);
}
$db->close();
llxFooter();
?>
