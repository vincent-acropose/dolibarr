<?php
/* Copyright (C) 2013-2014		Charles-Fr BENKE		<charles.fr@benke.fr>
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
 *  \file       htdocs/restock/restock.php
 *  \ingroup    stock
 *  \brief      Page to manage reodering
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/restock/class/restock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("suppliers");
$langs->load("bills");


// Security check
$result=restrictedArea($user,'produit','','','','','','');

$action=GETPOST("action");

/*
 * Actions
 */

if (isset($_POST["button_removefilter_x"]))
{
	$sref="";
	$snom="";
	$search_categ=0;
	$search_fourn=0;
}
else
{
	$search_categ=GETPOST("search_categ");
	$search_fourn=GETPOST("search_fourn");
}

/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);

if( isset($_POST['reload']) ) $action = 'restock';

$title=$langs->trans("RestockProduct");

if ($action=="")
{
	llxHeader('',$title,$helpurl,'');
	
	// premier écran la sélection des produits
	$param="&amp;sref=".$sref.($sbarcode?"&amp;sbarcode=".$sbarcode:"")."&amp;snom=".$snom."&amp;sall=".$sall."&amp;tosell=".$tosell."&amp;tobuy=".$tobuy;
	$param.=($fourn_id?"&amp;fourn_id=".$fourn_id:"");
	$param.=($search_categ?"&amp;search_categ=".$search_categ:"");
	$param.=isset($type)?"&amp;type=".$type:"";
	print_barre_liste($texte, $page, "restock.php", $param, $sortfield, $sortorder,'',$num);

	if (! empty($catid))
	{
		print "<div id='ways'>";
		$c = new Categorie($db);
		$ways = $c->print_all_ways(' &gt; ','restock/restock.php');
		print " &gt; ".$ways[0]."<br>\n";
		print "</div><br>";
	}

	print '<form action="restock.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="">';

	print '<table class="liste" width="100%">';

	// Filter on categories
 	$filtercateg='';
	if (! empty($conf->categorie->enabled))
	{
	 	$filtercateg.=$langs->trans('Categories'). ': ';
		$filtercateg.=$htmlother->select_categories(0,$search_categ,'search_categ',1);
	}
	$filterfourn="";
	if (! empty($conf->fournisseur->enabled))
	{
		$fournisseur=new Fournisseur($db);
		$tblfourn=$fournisseur->ListArray();
	 	$filterfourn.=$langs->trans('Fournisseur'). ': ';
	 	$filterfourn.=select_fournisseur($tblfourn, $search_fourn,'search_fourn');
	}
	
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" >'.$filtercateg.'</td>';
	print '<td class="liste_titre" colspan="3">'.$filterfourn.'</td>';
	print '</td><td colspan=4 align=right>';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td></tr>';
	print '</table>';
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre"><td class="liste_titre border" align=center colspan="4">'.$langs->trans("InfoProduct").'</td>';
	print '<td class="liste_titre border" align=center colspan="3">'.$langs->trans("InPropal").'</td>';
	print '<td class="liste_titre border" align=center colspan="3">'.$langs->trans("InOrder").'</td>';
	print '<td class="liste_titre border" align=center colspan="3">'.$langs->trans("InBill").'</td>';
	print '</td><td align=center>'.$langs->trans("Stock");
	print '</td><td align=center>'.$langs->trans("StockAlertAbrev");
	print '</td><td align=right>'.$langs->trans("AlreadyOrder1");
	print '</td><td align=center>'.$langs->trans("Qty").'</td></tr>';

	print '</form>';
	print '<form action="restock.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="restock">';
	
	$restock_static=new Restock($db);
	$tblRestock=array();
	// on récupère les produits présents dans les commandes et les propales
	$tblRestock=$restock_static->get_array_product_prop($tblRestock, $search_categ, $search_fourn, 0);
	$tblRestock=$restock_static->get_array_product_prop($tblRestock, $search_categ, $search_fourn, 1);
	$tblRestock=$restock_static->get_array_product_prop($tblRestock, $search_categ, $search_fourn, 2);
	$tblRestock=$restock_static->get_array_product_cmde($tblRestock, $search_categ, $search_fourn, 0);
	$tblRestock=$restock_static->get_array_product_cmde($tblRestock, $search_categ, $search_fourn, 1);
	$tblRestock=$restock_static->get_array_product_cmde($tblRestock, $search_categ, $search_fourn, 2);
	$tblRestock=$restock_static->get_array_product_bill($tblRestock, $search_categ, $search_fourn, 0);
	$tblRestock=$restock_static->get_array_product_bill($tblRestock, $search_categ, $search_fourn, 1);
	$tblRestock=$restock_static->get_array_product_bill($tblRestock, $search_categ, $search_fourn, 3); // paié partiellement
	
	// on gère la décomposition des produits
	$tblRestockTemp=array();
	foreach($tblRestock as $lgnRestock)
	{
		// on récupère la composition et les quantités
		$tbllistofcomponent=$restock_static->getcomponent($lgnRestock->id, 1);
		$numlines=count($tblRestockTemp);
		$lineofproduct = -1;
		foreach($tbllistofcomponent as $lgncomponent)
		{
			// on regarde si on trouve déjà le produit dans le tableau 
			for ($j = 0 ; $j < $numlines ; $j++)
				if ($tblRestockTemp[$j]->id == $lgncomponent[0])
					$lineofproduct=$j;

			if ($lineofproduct >= 0)
			{
				// on multiplie par la quantité du composant
				$tblRestockTemp[$lineofproduct]->nbCmdeDraft 	= $lgncomponent[1]*$lgnRestock->nbCmdeDraft;
				$tblRestockTemp[$lineofproduct]->nbCmdeValidate = $lgncomponent[1]*$lgnRestock->nbCmdeValidate;
				$tblRestockTemp[$lineofproduct]->nbCmdePartial 	= $lgncomponent[1]*$lgnRestock->nbCmdePartial;
				$tblRestockTemp[$lineofproduct]->nbPropDraft 	= $lgncomponent[1]*$lgnRestock->nbPropDraft;
				$tblRestockTemp[$lineofproduct]->nbPropValidate = $lgncomponent[1]*$lgnRestock->nbPropValidate;
				$tblRestockTemp[$lineofproduct]->nbPropSigned 	= $lgncomponent[1]*$lgnRestock->nbPropSigned;
				$tblRestockTemp[$lineofproduct]->nbBillDraft 	= $lgncomponent[1]*$lgnRestock->nbBillDraft;
				$tblRestockTemp[$lineofproduct]->nbBillValidate = $lgncomponent[1]*$lgnRestock->nbBillValidate;
				$tblRestockTemp[$lineofproduct]->nbBillPartial 	= $lgncomponent[1]*$lgnRestock->nbBillPartial;
			}
			else
			{
				$tblRestockTemp[$numlines] = new Restock($db);
				$tblRestockTemp[$numlines]->id= $lgncomponent[0];
				$tblRestockTemp[$numlines]->nbCmdeDraft 	= $lgncomponent[1]*$lgnRestock->nbCmdeDraft;
				$tblRestockTemp[$numlines]->nbCmdeValidate  = $lgncomponent[1]*$lgnRestock->nbCmdeValidate;
				$tblRestockTemp[$numlines]->nbCmdePartial 	= $lgncomponent[1]*$lgnRestock->nbCmdePartial;
				$tblRestockTemp[$numlines]->nbPropDraft 	= $lgncomponent[1]*$lgnRestock->nbPropDraft;
				$tblRestockTemp[$numlines]->nbPropValidate  = $lgncomponent[1]*$lgnRestock->nbPropValidate;
				$tblRestockTemp[$numlines]->nbPropSigned 	= $lgncomponent[1]*$lgnRestock->nbPropSigned;
				$tblRestockTemp[$numlines]->nbBillDraft 	= $lgncomponent[1]*$lgnRestock->nbBillDraft;
				$tblRestockTemp[$numlines]->nbBillValidate  = $lgncomponent[1]*$lgnRestock->nbBillValidate;
				$tblRestockTemp[$numlines]->nbBillPartial 	= $lgncomponent[1]*$lgnRestock->nbBillPartial;
				$numlines++;
			}
		}
	}
	$tblRestock=$restock_static->enrichir_product($tblRestockTemp);
	
	// Lignes des titres
	print "<tr class='liste_titre'>";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("SellingPrice").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("BuyingPriceMinShort").'</td>';
	print '<td class="liste_titre border" align="center">'.$langs->trans("Draft").'</td>';
	print '<td class="liste_titre border" align="center">'.$langs->trans("Validate").'</td>';
	print '<td class="liste_titre border" align="center">'.$langs->trans("Signed").'</td>';
	print '<td class="liste_titre border" align="center">'.$langs->trans("Draft").'</td>';
	print '<td class="liste_titre border" align="center">'.$langs->trans("Validate").'</td>';
	print '<td class="liste_titre border" align="center">'.$langs->trans("ActionsRunningshort").'</td>';
	print '<td class="liste_titre border" align="center">'.$langs->trans("Draft").'</td>';
	print '<td class="liste_titre border" align="center">'.$langs->trans("BillsUnpaid").'</td>';
	print '<td class="liste_titre border" align="center">'.$langs->trans("ActionsRunningshort").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("Physical").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("StockLimitAbrev").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("AlreadyOrder2").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("QtyRestock").'</td>';
	print "</tr>\n";
	
	
	$idprodlist="";
	$product_static=new Product($db);
	foreach($tblRestock as $lgnRestock)
	{
		// on affiche que les produits commandable à un fournisseur
		if ($lgnRestock->OnBuyProduct == 1 && $lgnRestock->fk_product_type == 0)
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";
			$idprodlist.=$lgnRestock->id."-";
			print '<td class="nowrap">';
			$product_static->id = $lgnRestock->id;
			$product_static->ref = $lgnRestock->ref_product;
			$product_static->type = 0;
			print $product_static->getNomUrl(1,'',24);
			print '</td>';
			print '<td align="left">'.$lgnRestock->libproduct.'</td>';
			print '<td align="right">'.price($lgnRestock->PrixVenteHT).'</td>';
			print '<td align="right">'.price($lgnRestock->PrixAchatHT).'</td>';
			print '<td align="right" class="border">'.$lgnRestock->nbPropDraft.'</td>';		
			print '<td align="right" class="border">'.$lgnRestock->nbPropValidate.'</td>';
			print '<td align="right" class="border">'.$lgnRestock->nbPropSigned.'</td>';		
			print '<td align="right" class="border">'.$lgnRestock->nbCmdeDraft.'</td>';
			print '<td align="right" class="border">'.$lgnRestock->nbCmdeValidate.'</td>';
			print '<td align="right" class="border">'.$lgnRestock->nbCmdePartial.'</td>';
			print '<td align="right" class="border">'.$lgnRestock->nbBillDraft.'</td>';
			print '<td align="right" class="border">'.$lgnRestock->nbBillValidate.'</td>';
			print '<td align="right" class="border">'.$lgnRestock->nbBillPartial.'</td>';
			print '<td align="right">'.$lgnRestock->StockQty.'</td>';
			print '<td align="right">'.$lgnRestock->StockQtyAlert.'</td>';
			print '<td align="right">'.$lgnRestock->nbCmdFourn.'</td>';
	
			// détermination du besoin
			$select0propals=$conf->global->RESTOCK_PROPOSAL_DRAFT;
			$select1propals=$conf->global->RESTOCK_PROPOSAL_VALIDATE;
			$select2propals=$conf->global->RESTOCK_PROPOSAL_SIGNED;
			$select0commandes=$conf->global->RESTOCK_ORDER_DRAFT;
			$select1commandes=$conf->global->RESTOCK_ORDER_VALIDATE;
			$select2commandes=$conf->global->RESTOCK_ORDER_PARTIAL;
			$select0factures=$conf->global->RESTOCK_BILL_DRAFT;
			$select1factures=$conf->global->RESTOCK_BILL_VALIDATE;
			$select2factures=$conf->global->RESTOCK_BILL_PARTIAL;
			
			
			$EstimedNeed=0;
			$EstimedNeed+=$lgnRestock->nbPropDraft*$select0propals/100;
			$EstimedNeed+=$lgnRestock->nbPropValidate*$select1propals/100;;
			$EstimedNeed+=$lgnRestock->nbPropSigned*$select2propals/100;;
			$EstimedNeed+=$lgnRestock->nbCmdeDraft*$select0commandes/100;
			$EstimedNeed+=$lgnRestock->nbCmdeValidate*$select1commandes/100;
			$EstimedNeed+=$lgnRestock->nbCmdePartial*$select2commandes/100;
			$EstimedNeed+=$lgnRestock->nbBillDraft*$select0factures/100;
			$EstimedNeed+=$lgnRestock->nbBillValidate*$select1factures/100;
			$EstimedNeed+=$lgnRestock->nbBillpartial*$select2factures/100;
			$EstimedNeed-=$lgnRestock->StockQty;
			$EstimedNeed-=$lgnRestock->nbCmdFourn;
			// si on en a suffisament en stock
			if ($EstimedNeed > 0)
			{
				// si on est en dessous du stock attention on est en valeur négative
				if (($lgnRestock->StockQtyAlert + $EstimedNeed) > 0)
					$EstimedNeed=($lgnRestock->StockQtyAlert+$EstimedNeed);
				else
					$EstimedNeed=0;
			}
			print '<td align="right"><input type=text size=5 name="prd-'.$lgnRestock->id.'" value="'.round($EstimedNeed).'"></td>';
			print "</tr>\n";
		}
	}
	print '</table>';
	// pour mémoriser les produits à réstockvisionner
	// on vire le dernier '-' si la prodlist est alimenté
	if ($idprodlist)
		$idprodlist=substr($idprodlist, 0, -1);
	print '<input type=hidden name="prodlist" value="'.$idprodlist.'"></td>';	
	
	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction">';
	print '<br><center><input type="submit" class="button" name="bouton" value="'.$langs->trans('RestockOrder').'"></center>';
	print '</div >';

	print '</form >';
}
elseif ($action=="restock")
{
	llxHeader('',$title,$helpurl,'');

	// deuxieme étape : la sélection des fournisseur
	print '<form action="restock.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createrestock">';
	print '<input type="hidden" name="prodlist" value="'.GETPOST("prodlist").'">';
	print '<table class="liste" width="100%">';
	// Lignes des titres
	print "<tr class=\"liste_titre\">";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("QtyRestock").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("FournishSelectInfo").'</td>';
	print "</tr>\n";
	$product_static=new Product($db);
	
	$tblproduct=explode("-", GETPOST("prodlist"));
	$var=true;
	// pour chaqe produit
	foreach($tblproduct as $idproduct)
	{
		$nbprod=GETPOST("prd-".$idproduct);
		if ($nbprod > 0)
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td class="nowrap">';
			$product_static->id = $idproduct;
			$product_static->fetch($idproduct);
			print $product_static->getNomUrl(1,'',24);
			print '</td>';
			print '<td>'.$product_static->label.'</td>';
			print '<td align=center>';
			print "<input type=text size=4 name='prd-".$idproduct."' value='".$nbprod."'>";
			print '</td><td width=60%>';
			// on récupère les infos fournisseurs
			$product_fourn = new ProductFournisseur($db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($idproduct, "", "");
			if (count($product_fourn_list) > 0)
			{
				print '<table class="liste" width="100%">';
				print '<tr class="liste_titre">';
				print '<td class="liste_titre">'.$langs->trans("Suppliers").'</td>';
				print '<td class="liste_titre">'.$langs->trans("Ref").'</td>';
				if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY)) print '<td class="liste_titre">'.$langs->trans("Availability").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("QtyMinAbrev").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("VAT").'</td>';

				// Charges ????
				print '<td class="liste_titre" align="right">'.$langs->trans("UnitPriceHTAbrev").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("Price")." ".$langs->trans("HT").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("Price")." ".$langs->trans("TTC").'</td>';
				print "</tr>\n";
			
				// pour chaque fournisseur du produit
				foreach($product_fourn_list as $productfourn)
				{
					//var_dump($productfourn);
					print "<tr >";
					$presel=false;
					if ($nbprod < $productfourn->fourn_qty)
					{	// si on est or seuil de quantité on désactive le choix
						print '<td>'.img_picto('disabled','disable') ;
					}
					else
					{
						// on mémorise à la fois l'id du fournisseur et l'id du produit du fournisseur
						if (count($product_fourn_list) > 1)
						{
							// on revient sur l'écran avec une préselection
							$checked="";
							if (GETPOST("fourn-".$idproduct) == $productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx)
							{	$presel=true;
								$checked = " checked=true ";
							}
							print '<td><input type=radio '.$checked.' name="fourn-'.$idproduct.'" value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'">&nbsp;';
						}
						else	// si il n'y a qu'un fournisseur il est sélectionné par défaut
						{
							$presel=true;
							print '<td><input type=radio checked=true name="fourn-'.$idproduct.'" value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'">&nbsp;';
						}
						//mouchard pour les tests
						//print '<input type=text  value="'.$productfourn->fourn_id.'-'.$productfourn->product_fourn_price_id.'-'.$productfourn->fourn_tva_tx.'">&nbsp;';
					}
					print $productfourn->getSocNomUrl(1,'supplier').'</td>';

					// Supplier
					print '<td align="left">'.$productfourn->fourn_ref.'</td>';

					//Availability
					if(!empty($conf->global->FOURN_PRODUCT_AVAILABILITY))
					{
						$form->load_cache_availability();
            			$availability= $form->cache_availability[$productfourn->fk_availability]['label'];
						print '<td align="left">'.$availability.'</td>';
					}

					// Quantity
					print '<td align="right">';
					print $productfourn->fourn_qty;
					print '</td>';

					// VAT rate
					print '<td align="right">';
					print vatrate($productfourn->fourn_tva_tx,true);
					print '</td>';

					// Unit price
					print '<td align="right">';
					print price($productfourn->fourn_unitprice);
					//print $objp->unitprice? price($objp->unitprice) : ($objp->quantity?price($objp->price/$objp->quantity):"&nbsp;");
					print '</td>';	

					// Unit Charges ???
					if (! empty($conf->margin->enabled))
					{
						$unitcharge=($productfourn->fourn_unitcharges?price($productfourn->fourn_unitcharges) : ($productfourn->fourn_qty?price($productfourn->fourn_charges/$productfourn->fourn_qty):"&nbsp;"));
					}
					if ($nbprod < $productfourn->fourn_qty)
						$nbprod = $productfourn->fourn_qty;
					$estimatedFournCost=$nbprod*$productfourn->fourn_unitprice+($unitcharge!="&nbsp;"?$unitcharge:0);
					print '<td align=right><b>'.price($estimatedFournCost).'<b></td>';
					if($productfourn->fourn_tva_tx)
						$estimatedFournCostTTC=$estimatedFournCost*(1+($productfourn->fourn_tva_tx/100));
					print '<td align=right><b>'.price($estimatedFournCostTTC).'<b></td>';
					if ($presel==true)
					{
						$totHT=$totHT+$estimatedFournCost;
						$totTTC=$totTTC+$estimatedFournCostTTC;
					}
					print '</tr>';
				}
				print "</table>";
			}
			else
			{
				print $langs->trans("NoFournishForThisProduct");
			}
			print '</td>';
			print '</tr>';
		}
	}
	print '<tr >';
	print '<td colspan=2></td><td align=right><input type="submit" class="button" name="reload" value="'.$langs->trans('RecalcReStock').'"></td>';
	print '<td><table width=100% ><tr><td ></td>';
	print '<td width=100px align=left>'.$langs->trans("AmountHT")." : <br>";
	print $langs->trans("AmountVAT")." : ".'</td>';
	print '<td width=100px align=right>'.price($totHT)." ".$langs->trans("Currency".$conf->currency).'<br>'.price($totTTC)." ".$langs->trans("Currency".$conf->currency).'</td>';

	print '</tr>';	
	print '</table>';
	print '</td></tr>';	
	print '</table>';
	
	/*
	 * Boutons actions
	*/
	print '<div class="tabsAction">';
	print '<table width=75%><tr><td width=110px align=right>'.$langs->trans('ReferenceOfOrder').' :</td><td align=left width=200px>';
	// on mémorise la référence du de la facture client sur la commande fournisseur
	print '<input type=text size=40 name=reforderfourn value="'.$langs->trans('Restockof').'&nbsp;'.dol_print_date(dol_now(),"%d/%m/%Y").'"></td>';
	print '<td align=right><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateFournOrder').'"></td>';
	print '</tr></table>';
	print '</div >';
	print '</form >';
}
elseif ($action=="createrestock")
{
	// dernière étape : la création des commande fournisseur
	// on récupère la liste des produits à commander
	$tblproduct=explode("-", GETPOST("prodlist"));

	// on va utilser un tableau pour stocker les commandes fournisseurs
	$tblCmdeFourn=array();
	// on parcourt les produits pour récupérer les fournisseurs, les produits et les quantitésds
	foreach($tblproduct as $idproduct)
	{
		$numlines=count($tblCmdeFourn);
		$lineoffourn = -1;
		if (GETPOST("fourn-".$idproduct))
		{
			$tblfourn=explode("-", GETPOST("fourn-".$idproduct));
			if ($tblfourn[0])
			{
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblCmdeFourn[$j][0] == $tblfourn[0])
						$lineoffourn =$j;
		
				// si le fournisseur n'est pas déja dans le tableau des fournisseurs
				if ($lineoffourn == -1)
				{
					$tblCmdeFourn[$numlines][0] = $tblfourn[0];
					$tblCmdeFourn[$numlines][1] = array(array($idproduct, GETPOST("prd-".$idproduct),$tblfourn[1],$tblfourn[2]));
				}
				else
				{
					$tblCmdeFourn[$lineoffourn][1] = array_merge($tblCmdeFourn[$lineoffourn][1],array(array($idproduct, GETPOST("prd-".$idproduct),$tblfourn[1],$tblfourn[2])));
				}
			}
		}
	}
	//var_dump($tblCmdeFourn);
	// on va maintenant créer les commandes fournisseurs
	foreach($tblCmdeFourn as $CmdeFourn)
	{
		$object = new CommandeFournisseur($db);
		$object->ref_supplier  	= GETPOST("reforderfourn");
		$object->socid         	= $CmdeFourn[0];
		$object->note_private	= '';
		$object->note_public   	= '';
		$id = $object->create($user);
		
		// ensuite on boucle sur les lignes de commandes
		foreach($CmdeFourn[1] as $lgnCmdeFourn)
		{

			//var_dump($lgnCmdeFourn);
			//$desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $fk_prod_fourn_price=0, $fourn_ref='', $remise_percent=0, $price_base_type='HT', $pu_ttc=0, $type=0, $info_bits=0, $notrigger=false)
			$result=$object->addline(
				'', 0, 
				$lgnCmdeFourn[1],	// $qty
				$lgnCmdeFourn[3],	// TxTVA
				0, 0,
				$lgnCmdeFourn[0],	// $fk_product
				$lgnCmdeFourn[2],	// $fk_prod_fourn_price
				0, 0,
				'HT',				// $price_base_type
				0, 0					// type
			);
		}
	}
	// une fois que c'est terminé, on affiche les commandes fournisseurs crée
	// on crée les commandes et on les listes sur l'écran
	header("Location: ".DOL_URL_ROOT."/fourn/commande/liste.php?search_ref_supplier=".GETPOST("reforderfourn"));
	exit;
}
llxFooter();
$db->close();

	/**
	 * Return select list for categories (to use in form search selectors)
	 *
	 * @param  attary	$fournlist	fournish list
	 * @param  string	$selected	Preselected value
	 * @param  string	$htmlname	Name of combo list
	 * @return string				Html combo list code
	 */
	function select_fournisseur($fournlist, $selected=0,$htmlname='search_fourn')
	{
		global $langs;

		// Print a select with each of them
		$moreforfilter ='<select class="flat" name="'.$htmlname.'">';
		$moreforfilter.='<option value="">&nbsp;</option>';	// Should use -1 to say nothing
		if (is_array($fournlist))
		{
			foreach ($fournlist as $key => $value)
			{
				$moreforfilter.='<option value="'.$key.'"';
				if ($key == $selected) $moreforfilter.=' selected="selected"';
				$moreforfilter.='>'.dol_trunc($value,50,'middle').'</option>';
			}
		}
		$moreforfilter.='</select>';

		return $moreforfilter;
	}

?>
