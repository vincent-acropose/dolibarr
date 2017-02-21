<?php

/** 		Function called to complete substitution array (before generating on ODT, or a personalized email)
 * 		functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * 		is inside directory htdocs/core/substitutions
 *
 * 		@param	array		$substitutionarray	Array with substitution key=>val
 * 		@param	Translate	$langs			Output langs
 * 		@param	Object		$object			Object to use to get values
 * 		@return	void					The entry parameter $substitutionarray is modified
 */
function comm2o_completesubstitutionarray(&$substitutionarray, $langs, $object,
                                          $parameters)
{
    global $conf, $db;


    $substitutionarray['object_total_ttc'] = number_format((float)$object->total_ttc,
        2, ',','');
    $moins = $substitutionarray['cf_projdevis_ref_cf_claturemut_idclassement_numnomenclatures_honoraires'];
    $plus =  $substitutionarray['cf_projdevis_ref_cf_claturemut_idclassement_numnomenclatures_intervclient'];
    $total = (float)$object->total_ttc - (float)str_replace(',', '.', $moins) + (float)str_replace(',', '.', $total);
    $substitutionarray['comm2o_total_soustraction'] = number_format($total, 2, ',','');
    dol_syslog('comm2o_total_soustraction '.number_format($total, 2, ',',''));

//    print_r($object);
//    exit;


    require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';


    $Societe = new Societe($db);
    $Societe->fetch($object->array_options['options_mutuel_customer']);



    $sql = "SELECT";
    $sql .= " e.ref, e.rowid, e.fk_statut, e.fk_product, p.ref as refproduit, e.fk_entrepot, ent.label,";
    $sql .= " e.fk_soc_fourn, sfou.nom as CompanyFourn, e.unitweight, e.quantity,";
    $sql .= " e.fk_soc_client, scli.nom as CompanyClient, e.fk_etatequipement, et.libelle as etatequiplibelle,";
    $sql .= " ee.rowid as eerowid, ee.datee, ee.dateo, eet.libelle as equipevttypelibelle, ee.fk_equipementevt_type,";
    $sql .= " ee.fk_fichinter, fi.ref as reffichinter, ee.fk_contrat, co.ref as refcontrat, ee.fk_expedition, exp.ref as refexpedition ";

    $sql .= " FROM ".MAIN_DB_PREFIX."equipement as e";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipement_etat as et on e.fk_etatequipement = et.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sfou on e.fk_soc_fourn = sfou.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ent on e.fk_entrepot = ent.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as scli on e.fk_soc_client = scli.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on e.fk_product = p.rowid";
    $sql .= " , ".MAIN_DB_PREFIX."equipementevt as ee";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_equipementevt_type as eet on ee.fk_equipementevt_type = eet.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."fichinter as fi on ee.fk_fichinter = fi.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."contrat as co on ee.fk_contrat = co.rowid";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expedition as exp on ee.fk_expedition = exp.rowid";

    $sql .= " WHERE e.entity = ".$conf->entity;
    $sql .= " and e.rowid=ee.fk_equipement";
    $sql .= " and ee.fk_project=".$object->fk_project;

// 		if ($search_ref)			$sql .= " AND e.ref like '%".$db->escape($search_ref)."%'";
// 		if ($search_refProduct)		$sql .= " AND p.ref like '%".$db->escape($search_refProduct)."%'";
// 		if ($search_company_fourn)	$sql .= " AND sfou.nom like '%".$db->escape($search_company_fourn)."%'";
// 		if ($search_entrepot)		$sql .= " AND ent.label like '%".$db->escape($search_entrepot)."%'";
// 		if ($search_company_client)	$sql .= " AND scli.nom like '%".$db->escape($search_company_client)."%'";
// 		if ($search_etatequipement)	$sql .= " AND e.fk_etatequipement =".$search_etatequipement;
// 		if ($search_equipevttype)	$sql .= " AND ee.fk_equipementevt_type =".$search_equipevttype;
// 		$sql.= " ORDER BY ".$sortfield." ".$sortorder;
// 		$sql.= $db->plimit($limit+1, $offset);
// echo $sql;
    $result                                 = $db->query($sql);
    $substitutionarray['comm2o_snequip']    = '';
    $substitutionarray['comm2o_labelequip'] = '';
    $substitutionarray['comm2o_labellist'] = '';
    if ($result) {
        $num = $db->num_rows($result);
        $i   = 0;
        while ($i < $num) {
            $objp                                   = $db->fetch_object($result);
            $i++;
            $substitutionarray['comm2o_snequip']    .= $objp->ref."\n";
            $substitutionarray['comm2o_labelequip'] .= $objp->refproduit."\n";
            $prod = new Product($db);
            $prod->fetch($objp->fk_product);
            $substitutionarray['comm2o_labellist'] .= $prod->array_options['options_dossier_inami_codeidprod']."\n";
        }
    }

    require_once(DOL_DOCUMENT_ROOT.'/comm2o/class/cg_nomenclatures.class.php');

    $nomenclature = new Cg_Nomenclatures($db);
    if (isset($object->array_options['options_ref_mutuel_nomeclature'])) {
        $nomenclature->fetch($object->array_options['options_ref_mutuel_nomeclature']);


        $substitutionarray['comm2o_resteapayer']            = round(($object->total_ttc
            - $nomenclature->intervoa), 2);
        $substitutionarray['comm2o_partialpaye_cf_caution'] = round(($object->total_ttc
            - $nomenclature->intervoa) / (int) $object->array_options['options_nbr_caution'],
            2);
    } else {
        $substitutionarray['comm2o_resteapayer']            = $object->total_ttc;
        $substitutionarray['comm2o_partialpaye_cf_caution'] = round($object->total_ttc
            / (int) $object->array_options['options_nbr_caution'], 2);
    }

//    print_r($object->array_options);
//    exit;
    $substitutionarray['comm2o_annexe12_0'] = '';
    $substitutionarray['comm2o_annexe12_1'] = '';

    if ($substitutionarray['comm2o_resteapayer'] <= 0 /* || empty($object->array_options['options_ref_mutuel_nomeclature']) */)
            $substitutionarray['comm2o_annexe12_0'] = 'oui';
// 		elseif(empty($object->array_options['options_ref_mutuel_nomeclature']))
//
// 		elseif(!empty($object->array_options['options_ref_mutuel_nomeclature']))
// 			$substitutionarray['comm2o_annexe12_1'] = 'oui';
 //   else $substitutionarray['comm2o_annexe12_2'] = 'oui';

    $substitutionarray['comm2o_annexe12_2'] = 'oui';

    $substitutionarray['comm2o_mutuel_mastername'] = $Societe->name;
    $substitutionarray['comm2o_mutuel_idprof6']    = $Societe->idprof6;
    $substitutionarray['comm2o_mutuel_name']       = $Societe->name;
    $substitutionarray['comm2o_mutuel_address']    = $Societe->address;
    $substitutionarray['comm2o_mutuel_zip']        = $Societe->zip;
    $substitutionarray['comm2o_mutuel_town']       = $Societe->town;

    foreach ($Societe->contact_array() as $key => $row) {

        if (preg_match('#(service).*(conseil)#i', $row)) {

            $Contact = new Contact($db);
            $Contact->fetch($key);

            $substitutionarray['comm2o_servicemutuel_mastername'] = $Societe->name;
            $substitutionarray['comm2o_servicemutuel_name']       = $Contact->lastname;
            $substitutionarray['comm2o_servicemutuel_address']    = $Contact->address;
            $substitutionarray['comm2o_servicemutuel_zip']        = $Contact->zip;
            $substitutionarray['comm2o_servicemutuel_town']       = $Contact->town;
        } elseif (preg_match('#(service.paiement)#i', $row)) {

            $Contact = new Contact($db);
            $Contact->fetch($key);

            $substitutionarray['comm2o_servicepaiement_mastername'] = $Societe->name;
            $substitutionarray['comm2o_servicepaiement_name']       = $Contact->lastname;
            $substitutionarray['comm2o_servicepaiement_address']    = $Contact->address;
            $substitutionarray['comm2o_servicepaiement_zip']        = $Contact->zip;
            $substitutionarray['comm2o_servicepaiement_town']       = $Contact->town;
        }
    }


//        print'<pre>';
//        print_r($substitutionarray);
//        exit;



}