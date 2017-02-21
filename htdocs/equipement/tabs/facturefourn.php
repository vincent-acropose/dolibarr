<?php
/* Copyright (C) 2012-2013	Charles.fr Benke	<charles.fr@benke.fr>
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
 * 	\file       htdocs/equipement/tabs/facture.php
 * 	\brief      List of all equipement associated with a bill
 * 	\ingroup    equipement
 */
$res = @include("../../main.inc.php");                    // For root directory
if (!$res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
        $res = @include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (!$res) $res = @include("../../../main.inc.php");        // For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php');
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

dol_include_once('/equipement/class/equipement.class.php');
dol_include_once('/equipement/core/lib/equipement.lib.php');


dol_include_once('/projet/class/project.class.php');
$langs->load("companies");
$langs->load("equipement@equipement");
$langs->load("bills");

$id  = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int'));
$ref = GETPOST('ref', 'alpha');

$errorsAdd=array();
$infosAdd=array();

class project2 extends project
{
    public $step = '';
    public $step_desc = '';

    function getStep()
    {

        //$sql = " SELECT cg_etapes.numetape , cg_etapes.descriptionetape FROM llx_projet, llx_projet_customfields, llx_societe, cg_etapes WHERE llx_projet.rowid = llx_projet_customfields.fk_projet AND llx_projet.fk_soc = llx_societe.rowid AND llx_projet_customfields.etapeprojet_numetape_descriptionetape = cg_etapes.rowid AND llx_projet_customfields.magasins_ville = 1 AND llx_projet.rowid ='".."' ";
        $sql = "SELECT e.numetape , e.descriptionetape
FROM llx_projet AS p, llx_projet_customfields AS pcf, llx_societe AS s, cg_etapes AS e
WHERE p.rowid = pcf.fk_projet
AND p.fk_soc = s.rowid
AND pcf.etapeprojet_numetape_descriptionetape = e.rowid
AND p.rowid ='{$this->id}'";

// 		echo ;


        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);

            $objp = $this->db->fetch_object($result);

            $this->step      = $objp->numetape;
            $this->step_desc = $objp->descriptionetape;
        }
    }
}
$object = new FactureFournisseur($db);
$object->fetch($id, $ref);

// Security check
if (!empty($user->societe_id)) $socid  = $user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page      = GETPOST('page', 'int');
if ($page == -1) {
    $page = 0;
}
$offset   = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "e.datec";
if ($page == -1) {
    $page = 0;
}

$limit    = $conf->liste_limit;
$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_ref            = GETPOST('search_ref', 'alpha');
$search_refProduct     = GETPOST('search_refProduct', 'alpha');
$search_company_client = GETPOST('search_company_client', 'alpha');
$search_reffact_client = GETPOST('search_reffact_client', 'alpha');
$search_entrepot       = GETPOST('search_entrepot', 'alpha');


$action = GETPOST('action', 'alpha');

$sqlListing = "SELECT";
$sqlListing .= " e.ref, e.rowid, e.fk_statut, e.fk_product,e.description, p.ref as refproduit, e.fk_entrepot, ent.label,";
$sqlListing .= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_facture_fourn,";
$sqlListing .= " e.fk_facture, f.facnumber as refFacture,";
$sqlListing .= " e.datee, e.dateo, ee.libelle as etatequiplibelle";
$sqlListing .= " ,ev.fk_project ";

$sqlListing .= " FROM ".MAIN_DB_PREFIX."equipement as e";
$sqlListing .= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as ee on e.fk_etatequipement = ee.rowid";
$sqlListing .= " LEFT JOIN ".MAIN_DB_PREFIX."equipementevt as ev on (e.rowid = ev.fk_equipement)";
$sqlListing .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
$sqlListing .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
$sqlListing .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f on e.fk_facture = f.rowid";
$sqlListing .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
$sqlListing .= " WHERE e.entity = ".$conf->entity;
if ($search_ref)
        $sqlListing .= " AND e.ref like '%".$db->escape($search_ref)."%'";
if ($search_refProduct)
        $sqlListing .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
if ($search_company_client)
        $sqlListing .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
if ($search_reffact_client)
        $sqlListing .= " AND f.facnumber like '%".$db->escape($search_reffact_client)."%'";
if ($search_entrepot)
        $sqlListing .= " AND ent.label like '%".$db->escape($search_entrepot)."%'";
if ($search_etatequipement)
        $sqlListing .= " AND e.fk_etatequipement =".$search_etatequipement;

$sqlListing .= " AND e.fk_facture_fourn =".$id;
$sqlListing .= " GROUP BY e.rowid ";
$sqlListing .= " ORDER BY ".$sortfield." ".$sortorder;
$sqlListing .= $db->plimit($limit + 1, $offset);
dol_syslog("SQLFF ".$sqlListing);
/*
 * 	View
 */


// gestion du transfert d'un équipement dans un entrepot
if ($action == "AddEquipement") {
    // a partir de la facture on récupère l'id du client
    $socid          = $object->socid;
    $tblSerial      = explode(";", GETPOST('listEquipementRef', 'alpha'));
    // clean up
    $tblSerial = array_map( 'trim' ,$tblSerial);
    foreach ($tblSerial as $sn) {
        $equipement = new Equipement($db);
        // un evènement est toujours associé à un fournisseur
        // on associe avec la facture
        $return = $equipement->fetch('', $sn);
        if($return > 0){
            if($equipement->fk_fact_fourn === null || $equipement->fk_fact_fourn == 0 ){
                $old_fourn = $equipement->fk_soc_fourn;
                if($equipement->set_fact_fourn($user, $id)==-1){ // impossible de l'ajouter
                    $errorsAdd[] = $langs->trans("Impossible d'ajouter l'&eacute;quipement $sn : ") . $equipement->errorsToString();
                } else {
                    // ça a marché
                    if( $old_fourn == NULL || $old_fourn == 0 ){
                        // atribué fournisseur
                        $infosAdd[] = $langs->trans("&Eacute;quipement $sn ajouté");
                    } elseif($old_fourn != $equipement->fk_soc_fourn){
                        // écraser fournisseur
                        $errorsAdd[] = $langs->trans("&Eacute;quipement $sn a chang&eacute; de fournisseur");
                    } elseif($old_fourn == $equipement->fk_soc_fourn) {
                        // c'etait déjà le bon fournisseur
                        $infosAdd[] = $langs->trans("&Eacute;quipement $sn ajouté");
                    }
                }
            } elseif ($equipement->fk_fact_fourn === $id) { // equipement déjà sur cette facture
                $errorsAdd[] = $langs->trans("L'&eacute;quipement $sn est d&eacute;j&agrave; sur cette facture");
            } else { //equipement déjà sur une facture
                $factTmp= new FactureFournisseur($db);
                $factTmp->fetch($equipement->fk_fact_fourn);
                $linktmpfact= $factTmp->getNomUrl(0);
                $errorsAdd[] = $langs->trans("L'&eacute;quipement $sn est d&eacute;j&agrave; sur la facture {$linktmpfact}");
            }
        } else { // equipement introuvable
            $errorsAdd[] = $langs->trans("Aucun &eacute;quipement pour le SN '$sn'");
        }
    }
} elseif ($action == "SendToInvoice") {
    // retrieve quantity of products
    $result = $db->query($sqlListing);
    $productQuantity= array();
    while ($objp = $db->fetch_object($result)) {
        if (isset($productQuantity[$objp->fk_product])) {
            $productQuantity[$objp->fk_product]['quantity'] ++;
        } else {
            $productQuantity[$objp->fk_product] = array( 'quantity' => 1 , 'SN' => array());
        }
        $productQuantity[$objp->fk_product]['SN'][] =$objp->ref;
    }

    // add products to invoice
    foreach ($productQuantity as $fk_product => $array_info) {
                // loading product info
        $product = new ProductFournisseur($db);
        $product->fetch($fk_product);


        // retrieve fournissur price
        $sql2 = "SELECT pfp.rowid, pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.remise, pfp.tva_tx, pfp.fk_availability,";
        $sql2.= " pfp.fk_soc, pfp.ref_fourn, pfp.fk_product, pfp.charges, pfp.unitcharges"; // , pfp.recuperableonly as fourn_tva_npr";  FIXME this field not exist in llx_product_fournisseur_price
        $sql2.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql2.= " WHERE pfp.`fk_soc` = {$object->socid} AND `fk_product` = $fk_product ORDER BY pfp.rowid DESC";

        $resql = $db->query($sql2);
        $obj = $db->fetch_object($resql);
        if ($obj)
        {   // price found
            $desc = $obj->ref_fourn;
            $pu	= $obj->price;
            $remise_percent = $obj->remise_percent;
            $txtva = $obj->tva_tx;
            $desc .= "\nSN: " . implode(" ", $array_info['SN']);
        } else { // no price
            $txtva=0;
            $pu= 0;
            $remise_percent= 0;
            $desc = $product->description;
            $desc .= "<br>SN: " . implode(" ", $array_info['SN']);
        }
        // retrieve info for bill
        $type = $product->type;
        $txlocaltax1 = 0;
        $txlocaltax2 = 0;
        $price_base_type='HT';
        $object->addline($desc, $pu, $txtva, $txlocaltax1, $txlocaltax2, $array_info['quantity'], $fk_product, $remise_percent, '', '',0, '', $price_base_type, $type);
    //break;
    }
   header("Location: ".dol_buildpath("/fourn/facture/fiche.php?facid=$id",2));

}





llxHeader();
$form = new Form($db);


$object->fetch_thirdparty();

$head  = facturefourn_prepare_head($object);
$titre = $langs->trans('SupplierInvoice');
dol_fiche_head($head, 'equipement', $titre, 0, 'bill');


if($errorsAdd!=array()){
    dol_htmloutput_errors('',$errorsAdd);
}
if($infosAdd!=array()){
    dol_htmloutput_mesg('',$infosAdd);
}

print '<table class="border" width="100%">';

// Ref
print '<tr><td width="20%" nowrap="nowrap">'.$langs->trans("Ref").'</td><td colspan="3">';
print $form->showrefnav($object, 'facid', '', 1, 'rowid', 'ref', $morehtmlref);

print '</td>';
print "</tr>\n";

// Ref supplier
print '<tr><td nowrap="nowrap">'.$langs->trans("RefSupplier").'</td><td colspan="3">'.$object->ref_supplier.'</td>';
print "</tr>\n";

// Company
print '<tr><td>'.$langs->trans('Supplier').'</td><td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

print '</table>';
print '<br><br>';




$result = $db->query($sqlListing);
if ($result) {
    $num = $db->num_rows($result);

    $equipementstatic = new Equipement($db);

    $urlparam = "&amp;id=".$id;
    if ($search_ref) $urlparam .= "&amp;search_ref=".$db->escape($search_ref);
    if ($search_refProduct)
            $urlparam .= "&amp;search_refProduct=".$db->escape($search_refProduct);
    if ($search_company_client)
            $urlparam .= "&amp;search_company_client=".$db->escape($search_company_client);
    if ($search_reffact_client)
            $urlparam .= "&amp;search_reffact_client=".$db->escape($search_reffact_client);
    if ($search_entrepot)
            $urlparam .= "&amp;search_entrepot=".$db->escape($search_entrepot);
    if ($search_etatequipement >= 0)
            $urlparam .= "&amp;search_etatequipement=".$search_etatequipement;
    print_barre_liste($langs->trans("ListOfEquipements"), $page,
        "facturefourn.php", $urlparam, $sortfield, $sortorder, '', $num);

    print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    print '<input type="hidden" class="flat" name="id" value="'.$id.'">';
    print '<table class="noborder" width="100%">';

    print "<tr class=\"liste_titre\">";
    print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "e.ref",
        "", $urlparam, '', $sortfield, $sortorder);
    print '<td></td>';
    print_liste_field_titre($langs->trans("RefProduit"), $_SERVER["PHP_SELF"],
        "p.ref", "", $urlparam, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("CompanyClient"),
        $_SERVER["PHP_SELF"], "scli.nom", "", $urlparam, '', $sortfield,
        $sortorder);
    print_liste_field_titre($langs->trans("RefFactClient"),
        $_SERVER["PHP_SELF"], "f.facnumber", "", $urlparam,
        ' style="min-width:120px;" ', $sortfield, $sortorder);
// 	print_liste_field_titre($langs->trans("Dateo"),$_SERVER["PHP_SELF"],"e.dateo","",$urlparam,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Project"), $_SERVER["PHP_SELF"],
        "ev.fk_project", "", $urlparam, '', $sortfield, $sortorder);
// 	print_liste_field_titre($langs->trans("Datee"),$_SERVER["PHP_SELF"],"e.datee","",$urlparam,'',$sortfield,$sortorder);
    print '<td></td><td></td>';
    print_liste_field_titre($langs->trans("EtatEquip"), $_SERVER["PHP_SELF"],
        "e.fk_equipementetat", "", $urlparam, '', $sortfield, $sortorder);
    print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"],
        "e.fk_statut", "", $urlparam, 'align="right"', $sortfield, $sortorder);
    print "</tr>\n";

    print '<tr class="liste_titre">';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="8"></td>';
    print '<td class="liste_titre" colspan="1" align="right">';
// 	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdateo" value="'.$monthdateo.'">';
// 	$syear = $yeardateo;
// 	if($syear == '') $syear = date("Y");
// 	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardateo" value="'.$syear.'">';
    print '</td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_refProduct" value="'.$search_refProduct.'" size="8"></td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_company_client" value="'.$search_company_client.'" size="10"></td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="search_reffact_client" value="'.$search_reffact_client.'" size="10"></td>';

    print '<td class="liste_titre" colspan="1" align="right">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="monthdatee" value="'.$monthdatee.'">';
// 	$syear = $yeardatee;
// 	if($syear == '') $syear = date("Y");
// 	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardatee" value="'.$syear.'">';
    print '</td>';

    print '<td class="liste_titre" colspan="1" align="right">';
// 	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdateo" value="'.$monthdateo.'">';
// 	$syear = $yeardateo;
// 	if($syear == '') $syear = date("Y");
// 	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardateo" value="'.$syear.'">';
    print '</td>';
    print '<td class="liste_titre" colspan="1" align="right">';
// 	print '<input class="flat" type="text" size="1" maxlength="2" name="monthdateo" value="'.$monthdateo.'">';
// 	$syear = $yeardateo;
// 	if($syear == '') $syear = date("Y");
// 	print '&nbsp;/&nbsp;<input class="flat" type="text" size="1" maxlength="4" name="yeardateo" value="'.$syear.'">';
    print '</td>';
    // liste des état des équipements
    print '<td class="liste_titre" align="right">';
    print select_equipement_etat($search_etatequipement,
            'search_etatequipement', 1, 1);
    print '</td>';

    print '<td class="liste_titre" align="right">';
    print '<select class="flat" name="viewstatut">';
    print '<option value="">&nbsp;</option>';
    print '<option ';
    if ($viewstatut == '0') print ' selected ';
    print ' value="0">'.$equipementstatic->LibStatut(0).'</option>';
    print '<option ';
    if ($viewstatut == '1') print ' selected ';
    print ' value="1">'.$equipementstatic->LibStatut(1).'</option>';
    print '<option ';
    if ($viewstatut == '2') print ' selected ';
    print ' value="2">'.$equipementstatic->LibStatut(2).'</option>';
    print '</select>';
    print '<input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
    print "</tr>\n";


    $var   = True;
    $total = 0;
    $i     = 0;
    while ($i < min($num, $limit)) {
        $objp = $db->fetch_object($result);
        $var  = !$var;


        if ($objp->fk_project > 0) {
            $projectstatic = new Project2($db);
            $projectstatic->fetch($objp->fk_project);
            $projectstatic->getStep();
        }

        print "<tr $bc[$var]>";
        print "<td>";
        $equipementstatic->id  = $objp->rowid;
        $equipementstatic->ref = $objp->ref;
        print $equipementstatic->getNomUrl(1);
        print "</td>";
        print "<td>";
// 		$equipementstatic->id=$objp->rowid;
// 		$equipementstatic->ref=$objp->ref;
        print $objp->description;
        print "</td>";

        print '<td>';
        if ($objp->fk_product) {
            $sql_req = "SELECT * FROM `".MAIN_DB_PREFIX."product_fournisseur_price` WHERE `fk_soc` = {$object->socid} AND `fk_product` = {$objp->fk_product} ORDER BY rowid DESC";
            $resql_req = $db->query($sql_req);
            $nb_lines = $db->num_rows($resql_req);
            $productstatic = new Product($db);
            $productstatic->fetch($objp->fk_product);
            print $productstatic->getNomUrl(1);
            if($nb_lines!=1){
                print ' <a href="/htdocs/product/fournisseurs.php?id='.$objp->fk_product.'" title="'.$langs->trans('Probl&egrave;me de prix fournisseur').'"><img src="/htdocs/theme/eldy/img/warning.png" alt="'.$langs->trans('Probl&egrave;me de prix fournisseur').'" border="0"></a>';
            }
            if($nb_lines > 0){
                $obj2 = $db->fetch_object($resql_req);
                print ' ('.price( $obj2->unitprice * (( 100 - $obj2->remise_percent ) / 100)).'&euro;)';
            }
            //options_type2produit
            if(isset($productstatic->array_options['options_type2produit']) && $productstatic->array_options['options_type2produit'] != 0){
                $sql_req2 = "SELECT `param` FROM `".MAIN_DB_PREFIX."extrafields` WHERE `name` = 'type2produit'";
                $resql_req2 = $db->query($sql_req2);
                $type2produit = unserialize($db->fetch_object($resql_req2)->param);
                $typeprod= $type2produit['options'][$productstatic->array_options['options_type2produit']];
                print ' <small>('.$typeprod.')</small>';
            } else {
                print ' <a href="/htdocs/product/fiche.php?action=edit&id='.$objp->fk_product.'" title="'.$langs->trans('Probl&egrave;me de type produit').'"><img src="/htdocs/theme/eldy/img/warning.png" alt="'.$langs->trans('Probl&egrave;me de type produit').'" border="0"></a>';
            }

        }
        print '</td>';

        print "<td>";
        if ($objp->fk_soc_client) {
            $soc = new Societe($db);
            $soc->fetch($objp->fk_soc_client);
            print $soc->getNomUrl(1);
        }
        print '</td>';

        print "<td>";
        if ($objp->fk_facture) {
            $facturestatic = new Facture($db);
            $facturestatic->fetch($objp->fk_facture);
            print $facturestatic->getNomUrl(1);
        } else {
            dol_syslog("OBJECTP ".serialize($objp));
            print_r('<strike>'.$langs->trans('&Eacute;quipement sur aucune facture').'</strike>');
        }
        print '</td>';


// 		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->dateo),'day')."</td>\n";
        print "<td nowrap align='left'>";
        if ($objp->fk_project > 0) {
// 			$projectstatic=new Project2($db);
// 			$projectstatic->fetch($objp->fk_project);
// 			print_r($projectstatic);
            print $projectstatic->getNomUrl(1);
        }
        print "</td>\n";


        print "<td nowrap align='left'>";
        if ($objp->fk_project > 0) {
// 			$projectstatic=new Project2($db);
// 			$projectstatic->fetch($objp->fk_project);
// 			print_r($projectstatic);
            print $projectstatic->step;
        }
        print "</td>\n";

        print "<td nowrap align='left'>";
        if ($objp->fk_project > 0) {
// 			$projectstatic=new Project2($db);
// 			$projectstatic->fetch($objp->fk_project);
// 			print_r($projectstatic);
            print $projectstatic->step_desc;
        }
        print "</td>\n";

// 		print "<td nowrap align='center'>".dol_print_date($db->jdate($objp->datee),'day')."</td>\n";
        print '<td align="right">'.$langs->trans($objp->etatequiplibelle).'</td>';
        print '<td align="right">'.$equipementstatic->LibStatut($objp->fk_statut,
                5).'</td>';
        print "</tr>\n";

        $i++;
    }
    //print '<tr class="liste_total"><td colspan="7" class="liste_total">'.$langs->trans("Total").'</td>';
    //print '<td align="right" nowrap="nowrap" class="liste_total">'.$i.'</td><td>&nbsp;</td>';
    //print '</tr>';

    print '</table>';
    print "</form>\n";
    $db->free($result);
    //print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Ajouter les équipements sur le d&eacute;tail de la facture'), $text, 'confirm_valid', $formquestion, 1, 1, 240);
    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=SendToInvoice"';
    print '>'.$langs->trans('Ajouter les &eacute;quipements sur le d&eacute;tail de la facture').'</a>';
    print '</div>';
} else {
    dol_print_error($db);
}

$form = new Form($db);

print "<br><br>";
// Ajout d'équipement dans l'entrepot
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">'."\n";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="AddEquipement">';
print '<input type="hidden" name="id" value="'.$id.'">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>';
print '<a name="add"></a>'; // ancre
print $langs->trans('ListEquipementToAdd').'</td>';
print '<td align="center">'.$langs->trans('Date').'</td>';
print '<td colspan="4">&nbsp;</td>';
print "</tr>\n";

print '<tr '.$bc[$var].">\n";
print '<td>';
print '<textarea name="listEquipementRef" cols="80" rows="'.ROWS_3.'"></textarea>';
print '</td>';

print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline"></td>';
print "</tr>\n";
print '</table >';
print '</form>'."\n";

$db->close();

llxFooter();
?>
