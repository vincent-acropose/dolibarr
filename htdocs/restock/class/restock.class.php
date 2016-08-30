<?php
/* Copyright (C) 2013-2014	Charles-Fr BENKE		<charles.fr@benke.fr>
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
 *	\file       htdocs/restock/class/restock.class.php
 *	\ingroup    categorie
 *	\brief      File of class to restock
 */

/**
 *	Class to manage Restock
 */
class Restock
{
	var $id;
	var $ref_product;
	var $libproduct;
	var $PrixAchatHT;
	var $PrixVenteHT;
	var $PrixVenteCmdeHT;		// pour les commandes clients
	var $ComposedProduct;
	var $OnBuyProduct;
	var $StockQty=0;
	var $nbBillDraft=0;
	var $nbBillValidate=0;
	var $nbBillpartial=0;
	var $nbCmdeDraft=0;
	var $nbCmdeValidate=0;
	var $nbCmdepartial=0;
	var $nbCmdeClient=0;
	var $MntCmdeClient=0;
	var $nbPropDraft=0;
	var $nbPropValidate=0;
	var $nbPropSigned=0;
	var $nbCmdFourn=0;
	
	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	function get_array_product_cmde($tblRestock, $search_categ, $search_fourn, $statut)
	{
		// on récupère les products des commandes
		$sql = 'SELECT DISTINCT cod.fk_product, sum(cod.qty) as nbCmde';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as co ON co.rowid = cod.fk_commande";
		if (! empty($search_fourn)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON cod.fk_product = pfp.fk_product";
		if (! empty($search_categ)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON cod.fk_product = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
		$sql.= " where co.fk_statut =".$statut;
		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY cod.fk_product";
		dol_syslog(get_class($this)."::get_array_product_cmde sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num)
			{	// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau 
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product)
						$lineofproduct=$j;

				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0)
				{
					// on met à jours les données pour la partie commande
					if ($statut==0)
						$tblRestock[$lineofproduct]->nbCmdeDraft = $tblRestock[$lineofproduct]->nbCmdeDraft + $objp->nbCmde;
					elseif ($statut==1)
						$tblRestock[$lineofproduct]->nbCmdeValidate = $tblRestock[$lineofproduct]->nbCmdeValidate + $objp->nbCmde;
					else
						$tblRestock[$lineofproduct]->nbCmdepartial = $tblRestock[$lineofproduct]->nbCmdepartial + $objp->nbCmde;
				}
				else
				{
					// sinon on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					if ($statut==0)
						$tblRestock[$numlines]->nbCmdeDraft = $objp->nbCmde;
					elseif ($statut==1)
						$tblRestock[$numlines]->nbCmdeValidate = $objp->nbCmde;
					else
						$tblRestock[$numlines]->nbCmdepartial = $objp->nbCmde;
				}
				$i++;
			}
		}
		return $tblRestock;
	}

	function get_array_product_bill($tblRestock, $search_categ, $search_fourn, $statut)
	{
		// on récupère les products des commandes
		$sql = 'SELECT DISTINCT fad.fk_product, sum(fad.qty) as nbBill';
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as fad";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON fa.rowid = fad.fk_facture";
		if (! empty($search_fourn)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON fad.fk_product = pfp.fk_product";
		if (! empty($search_categ)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON fad.fk_product = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
		$sql.= " where fa.fk_statut =".$statut;
		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY fad.fk_product";
		dol_syslog(get_class($this)."::get_array_product_bill sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num)
			{	// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau 
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product)
						$lineofproduct=$j;

				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0)
				{
					// on met à jours les données pour la partie commande
					if ($statut==0)
						$tblRestock[$lineofproduct]->nbBillDraft = $tblRestock[$lineofproduct]->nbBillDraft + $objp->nbBill;
					elseif ($statut==1)
						$tblRestock[$lineofproduct]->nbBillValidate = $tblRestock[$lineofproduct]->nbBillValidate + $objp->nbBill;
					else
						$tblRestock[$lineofproduct]->nbBillpartial = $tblRestock[$lineofproduct]->nbBillpartial + $objp->nbBill;
				}
				else
				{
					// sinon on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					if ($statut==0)
						$tblRestock[$numlines]->nbBillDraft = $objp->nbBill;
					elseif ($statut==1)
						$tblRestock[$numlines]->nbBillValidate = $objp->nbBill;
					else
						$tblRestock[$numlines]->nbBillpartial = $objp->nbBill;
				}
				$i++;
			}
		}
		return $tblRestock;
	}

	function get_array_product_cmde_client($tblRestock, $rowid)
	{
		// on récupère les products des commandes
		$sql = 'SELECT DISTINCT cod.fk_product, sum(cod.qty) as nbCmde, sum(total_ht) as MntCmde';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " where cod.fk_commande=".$rowid;
		$sql.= " GROUP BY cod.fk_product";
		dol_syslog(get_class($this)."::get_array_product_cmde_client sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num)
			{	// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau 
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product)
						$lineofproduct=$j;

				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0)
				{	$tblRestock[$lineofproduct]->nbCmdeClient = $tblRestock[$lineofproduct]->nbCmdeClient + $objp->nbCmde;
					$tblRestock[$lineofproduct]->MntCmdeClient = $tblRestock[$lineofproduct]->MntCmdeClient + $objp->MntCmde;
				}
				else
				{
					// sinon on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					$tblRestock[$numlines]->nbCmdeClient = $objp->nbCmde;
					$tblRestock[$numlines]->MntCmdeClient = $objp->MntCmde;
				}
				$i++;
			}
		}
		return $tblRestock;
	}

	// mise à jour du prix de vente fournisseur à partir du prix de vente du produit sur la commande
	function update_product_price_cmde_client($rowid, $idproduct)
	{
		global $conf;
		$sql = 'SELECT DISTINCT subprice';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " where cod.fk_commande=".$rowid;
		$sql.= " and cod.fk_product=".$idproduct;


		dol_syslog(get_class($this)."::update_product_price_cmde_client sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$objp = $this->db->fetch_object($resql);
			$productprice=$objp->subprice;
			// on pondère
			$coef=$conf->global->RESTOCK_COEF_ORDER_CLIENT_FOURN/100;
			$productprice=$productprice * $coef;
			// on met à jour le prix fournisseur
			$sql= "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price ";
			$sql.= " SET price=".$productprice;
			$sql.= " , unitprice=".$productprice;
			$sql.= " where fk_product=".$idproduct;
			$resqlupdate = $this->db->query($sql);
			return 1;
		}
		return 0;
		
	}

	function get_array_product_prop($tblRestock, $search_categ, $search_fourn, $statut)
	{
		// on récupère les products des propales
		$sql = 'SELECT DISTINCT prd.fk_product, sum(prd.qty) as nbProp';
		$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as prd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."propal as pr ON pr.rowid = prd.fk_propal";
		// We'll need this table joined to the select in order to filter by categ
		if (! empty($search_fourn)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON prd.fk_product = pfp.fk_product";
		if (! empty($search_categ)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON prd.fk_product = cp.fk_product"; 
		$sql.= " where pr.fk_statut =".$statut;
		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY prd.fk_product";
		dol_syslog(get_class($this)."::get_array_product_prop sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num)
			{
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau 
				for ($j = 0 ; $j < $numlines ; $j++)
				{
					if ($tblRestock[$j]->id == $objp->fk_product)
					{
						$lineofproduct=$j;
						//exit for;
					}
				}
				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0)
				{
					// on met à jours les données pour la partie commande
					if ($statut==0)
						$tblRestock[$lineofproduct]->nbPropDraft = $tblRestock[$lineofproduct]->nbPropDraft + $objp->nbProp;
					elseif ($statut==1)
						$tblRestock[$lineofproduct]->nbPropValidate = $tblRestock[$lineofproduct]->nbPropValidate + $objp->nbProp;
					else
						$tblRestock[$lineofproduct]->nbPropSigned = $tblRestock[$lineofproduct]->nbPropSigned + $objp->nbProp;
				}
				else
				{
					// sinon on ajoute un nouveau produit dans le tableau
					$tblRestock[$numlines] = new Restock($db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					if ($statut==0)
						$tblRestock[$numlines]->nbPropDraft = $objp->nbProp;
					elseif ($statut==1)
						$tblRestock[$numlines]->nbPropValidate = $objp->nbProp;
					else
						$tblRestock[$numlines]->nbPropSigned = $objp->nbProp;
				}
				$i++;
			}
		}
		return $tblRestock;
	}

	function enrichir_product($tblRestock)
	{
		$numlines=count($tblRestock);
		for ($i = 0 ; $i < $numlines ; $i++)
		{
			// on récupère les infos des produits 
			$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type, MIN(pfp.unitprice) as minsellprice';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
			$sql.= " where p.rowid=".$tblRestock[$i]->id;

			dol_syslog(get_class($this)."::enrichir_product sql=".$sql);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$objp = $this->db->fetch_object($resql);
				$tblRestock[$i]->ref_product=	$objp->ref;
				$tblRestock[$i]->libproduct=	$objp->label;
				$tblRestock[$i]->PrixVenteHT=	$objp->price;
				$tblRestock[$i]->PrixAchatHT=	$objp->minsellprice;
				$tblRestock[$i]->OnBuyProduct=	$objp->tobuy;
				$tblRestock[$i]->fk_product_type=	$objp->fk_product_type;
				$tblRestock[$i]->StockQty= 		$objp->stock;
				$tblRestock[$i]->StockQtyAlert=	$objp->seuil_stock_alerte;
				// on calcul ici le prix de vente unitaire réel
				if ($tblRestock[$i]->nbCmdeClient > 0)
					$tblRestock[$i]->PrixVenteCmdeHT = $tblRestock[$i]->MntCmdeClient/$tblRestock[$i]->nbCmdeClient;
			}

			// on regarde si il n'y pas de commande fournisseur en cours
			$sql = 'SELECT DISTINCT sum(cofd.qty) as nbCmdFourn';
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cofd";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as cof ON cof.rowid = cofd.fk_commande";
			$sql.= " where cof.fk_statut = 3";
			$sql.= " and cofd.fk_product=".$tblRestock[$i]->id;
			dol_syslog(get_class($this)."::enrichir_product::cmde_fourn sql=".$sql);
			//print $sql;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$objp = $this->db->fetch_object($resql);
				$tblRestock[$i]->nbCmdFourn= $objp->nbCmdFourn;
			}
		}
		return $tblRestock;
	}

	function getcomponent($fk_parent, $qty)
	{
		$components=array();
		// pour le moment on regarde les produits virtuels
		$sql = 'SELECT fk_product_fils, qty from '.MAIN_DB_PREFIX.'product_association';
		$sql.= ' WHERE fk_product_pere  = '.$fk_parent;
		$res = $this->db->query($sql);
		if ($res)
		{
			$num = $this->db->num_rows($res);
			if($num > 0)
			{
				// si le produit à des composants
				$i=0;
				$nbcomponent=0;
				while ($i < $num)
				{	
					$objp = $this->db->fetch_object($res);
					// on regarde récursivement si les composants ont eux-même des composants
					$tblcomponent=$this->getcomponent($objp->fk_product_fils, $objp->qty);

					foreach($tblcomponent as $lgncomponent)
					{
						// on ajoute le composant trouvé au tableau des composants
						$components[$nbcomponent][0]=$lgncomponent[0];
						$components[$nbcomponent][1]=$lgncomponent[1]*$qty;
						$nbcomponent++;
					}
					$i++;
				}
			}
			else
			{
				// pas d'enfant, c'est un produit de base, il est sont propre composant unique
				$components[0][0]=$fk_parent;
				$components[0][1]=$qty;
			}
		}
		return $components;
	}
}
?>
