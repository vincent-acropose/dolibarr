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
 *  \file       htdocs/restock/restockProduct.php
 *  \ingroup    stock
 *  \brief      Page to manage reodering
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/restock/class/restock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("restock@restock");
$langs->load("suppliers");


// Security check
if ($user->societe_id) $socid=$user->societe_id;

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas)?$object->canvas:GETPOST("canvas");
$objcanvas='';
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db,$action);
    $objcanvas->getCanvas('product','card',$canvas);
}

$action=GETPOST("action");
$id = GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$object = new Product($db);

$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype,$objcanvas);




if (! $object->fetch($id, $ref) > 0)
{
	dol_print_error($db);
}
$id = $object->id;

/*
 * Actions
 */

if (isset($_POST["button_removefilter_x"]))
{
	$sref="";
	$snom="";
	$search_categ=0;
}


/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);

$soc = new Societe($db);
$soc->fetch($object->socid);

if( isset($_POST['reload']) ) $action = 'restock';


if ($action!="createrestock")
{
	$title=$langs->trans("RestockProduct");
	llxHeader('',$title,'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes','');
	
	$head = product_prepare_head($object, $user);
	
	dol_fiche_head($head, 'restock', $langs->trans("RestockProduct"), 0, 'product');
	
	
	print '<table class="border" width="100%">';
	
	$linkback = '<a href="'.DOL_URL_ROOT.'/product/liste.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
	
	// Ref
	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print "</td></tr>";
	
	// Ref commande client
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
	print $langs->trans('RefCustomer').'</td><td align="left">';
	print '</td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	print $object->ref_client;
	print '</td>';
	print '</tr>';
	
	// Customer
	print "<tr><td>".$langs->trans("Company")."</td>";
	print '<td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';
	print '</table><br><br>';
}

if ($action=="")
{
	print '<form action="restockProduct.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="restock">';
	print '<input type="hidden" name="id" value="'.$id.'">';

	$restock_static=new Restock($db);
	$tblRestock=array();
	$tblRestock[0] = new Restock($db);
	$tblRestock[0]->id= $id;
	
	// on récupère les produits présents dans la commande
	//$tblRestock=$restock_static->get_array_product_cmde_client($tblRestock, $id);
	
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
				$tblRestockTemp[$lineofproduct]->nbCmdeClient 	= $lgncomponent[1]*$lgnRestock->nbCmdeClient;
			}
			else
			{
				$tblRestockTemp[$numlines] = new Restock($db);
				$tblRestockTemp[$numlines]->id= $lgncomponent[0];
				$tblRestockTemp[$numlines]->nbCmdeClient 	= $lgncomponent[1]*$lgnRestock->nbCmdeClient;
				$numlines++;
			}
		}
	}
	$tblRestock=$restock_static->enrichir_product($tblRestockTemp);

	// Lignes des titres
	print '<table class="liste" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td class="liste_titre" align="left">'.$langs->trans("Ref").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Label").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("SellingPrice").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("BuyingPriceMinShort").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("PhysicalStock").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("StockLimit").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("AlreadyOrder2").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("QtyRestock").'</td>';
	print "</tr>\n";
	
	//var_dump($tblRestock);
	$idprodlist="";
	$product_static=new Product($db);
	foreach($tblRestock as $lgnRestock)
	{
		// on affiche que les produits commandable à un fournisseur ?
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
			print '<td align="right">'.$lgnRestock->StockQty.'</td>';
			print '<td align="right">'.$lgnRestock->StockQtyAlert.'</td>';
			print '<td align="right">'.$lgnRestock->nbCmdFourn.'</td>';
			$product_fourn = new ProductFournisseur($db);
			$product_fourn_list = $product_fourn->list_product_fournisseur_price($product_static->id, "", "");
			if (count($product_fourn_list) > 0)
			{
				// détermination du besoin
				$EstimedNeed=$lgnRestock->nbCmdeClient;
				$EstimedNeed-=$lgnRestock->StockQty;
				$EstimedNeed-=$lgnRestock->nbCmdFourn;
				// si on en a suffisament en stock
				if ($EstimedNeed < 0)
				{
					// si on est en dessous du stock attention on est en valeur négative
					if ($lgnfactory->StockQtyAlert+$EstimedNeed > 0)
						$EstimedNeed=($lgnRestock->StockQtyAlert+$EstimedNeed);
					else
						$EstimedNeed=0;
				}
				print '<td align="right"><input type=text size=5 name="prd-'.$lgnRestock->id.'" value="'.round($EstimedNeed).'"></td>';
			}
			else
			{	print '<td align="right">';
				print $langs->trans("NoFournish");
				print '</td>';
			}
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
	// deuxieme étape : la sélection des fournisseur
	print '<form action="restockProduct.php" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="createrestock">';
	print '<input type="hidden" name="id" value="'.$id.'">';
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
	print '<table width=75%><tr><td width=110px align=right>'.$langs->trans('ReferenceOfOrder').' :</td><td align=left>';
	// on mémorise la référence du de la facture client sur la commande fournisseur
	print '<input type=text size=30 name=reforderfourn value="'.$langs->trans('RestockofProduct').'&nbsp;'.$object->ref.'"></td>';
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

	// on va maintenant créer les commandes fournisseurs
	foreach($tblCmdeFourn as $CmdeFourn)
	{
		$objectcf = new CommandeFournisseur($db);
		$objectcf->ref_supplier  	= GETPOST("reforderfourn");
		$objectcf->socid         	= $CmdeFourn[0];
		$objectcf->note_private	= '';
		$objectcf->note_public   	= '';
		$objectcf->origin_id = GETPOST("id");
		$objectcf->origin = "order_supplier";
		$idCmdFourn = $objectcf->create($user);
		// ensuite on boucle sur les lignes de commandes
		foreach($CmdeFourn[1] as $lgnCmdeFourn)
		{
			// on cree la commande fournisseur
			$result=$objectcf->addline(
				'', 0,
				$lgnCmdeFourn[1],
				$lgnCmdeFourn[3],	// TxTVA
				0, 0,
				$lgnCmdeFourn[0],
				$lgnCmdeFourn[2],
				0, 0, 'HT', 0, 0 );

			// on interface cette commande fournisseur avec la commande client
			// Add object linked
			if (! $error && $objectcf->id && ! empty($objectcf->origin) && ! empty($objectcf->origin_id))
			{
				$ret = $objectcf->add_object_linked();
				if (! $ret)
				{
					dol_print_error($objectcf->db);
					$error++;
				}
			}
		}
	}
	// une fois que c'est terminé, on affiche les commandes fournisseurs crée
	// on crée les commandes et on les listes sur l'écran
	header("Location: ".DOL_URL_ROOT."/fourn/commande/liste.php?search_ref_supplier=".GETPOST("reforderfourn"));
	exit;
}

print '</div>';
print '<div class="fichecenter"><div class="fichehalfleft">';
/*
 * Linked object block
*/
$somethingshown=$object->showLinkedObjectBlock();
print '</div>';
print '</div>';
llxFooter();
$db->close();
?>
