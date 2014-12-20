<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2013      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 *	\file       htdocs/societe/societe.php
 *	\ingroup    societe
 *	\brief      Page to show a third party
 */

require_once '../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$search_nom=trim(GETPOST("search_nom"));
$search_nom_only=trim(GETPOST("search_nom_only"));
$search_all=trim(GETPOST("search_all"));
$search_town=trim(GETPOST("search_town"));
$socname=trim(GETPOST("socname"));
$search_idprof1=trim(GETPOST('search_idprof1'));
$search_idprof2=trim(GETPOST('search_idprof2'));
$search_idprof3=trim(GETPOST('search_idprof3'));
$search_idprof4=trim(GETPOST('search_idprof4'));
$search_idprof5=trim(GETPOST('search_idprof5'));
$search_idprof6=trim(GETPOST('search_idprof6'));
$search_sale=trim(GETPOST("search_sale"));
$search_categ=trim(GETPOST("search_categ"));
$mode=GETPOST("mode");
$modesearch=GETPOST("mode_search");
$search_type=trim(GETPOST('search_type'));
$search_zip=GETPOST('search_zip');
$search_address=GETPOST('search_address');
$search_phone=GETPOST('search_phone');
$search_status		= GETPOST("search_status",'int');
//if ($search_status=='') $search_status=1; // always display activ customer first

$ts_logistique=GETPOST('options_ts_logistique','int');
$ts_prospection=GETPOST('options_ts_prospection','int');
$search_parent=GETPOST('search_parent','int');
if ($search_parent==-1) $search_parent='';

$sortfield=GETPOST("sortfield",'alpha');
$sortorder=GETPOST("sortorder",'alpha');
$page=GETPOST("page",'int');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Actions
 */

// Recherche
if ($mode == 'search')
{
	$search_nom=$socname;

	$sql = "SELECT s.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if ($search_sale || (!$user->rights->societe->client->voir && !$socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    // We'll need this table joined to the select in order to filter by categ
    if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
    $sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";

        // For natural search
        $scrit = explode(' ', $socname);
        foreach ($scrit as $crit) {
            $sql.= " AND (";
            $sql.= " s.nom LIKE '%".$db->escape($crit)."%'";
            $sql.= " OR s.code_client LIKE '%".$db->escape($crit)."%'";
            $sql.= " OR s.email LIKE '%".$db->escape($crit)."%'";
            $sql.= " OR s.url LIKE '%".$db->escape($crit)."%'";
            $sql.= " OR s.siren LIKE '%".$db->escape($crit)."%'";

            if (!empty($conf->barcode->enabled))
            {
                    $sql.= "OR s.barcode LIKE '".$db->escape($crit)."'";
            }

            $sql.= ")";
        }

	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
    if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";        // Join for the needed table to filter by sale
    if ($search_categ) $sql.= " AND s.rowid = cs.fk_societe";   // Join for the needed table to filter by categ
	if (! $user->rights->societe->lire || ! $user->rights->fournisseur->lire)
	{
		if (! $user->rights->fournisseur->lire) $sql.=" AND s.fournisseur != 1";
	}
    // Insert sale filter
    if ($search_sale)
    {
        $sql .= " AND sc.fk_user = ".$search_sale;
    }
    // Insert categ filter
    if ($search_categ)
    {
        $sql .= " AND cs.fk_categorie = ".$search_categ;
    }
    // Filter on type of thirdparty
	if ($search_type > 0 && in_array($search_type,array('1,3','2,3'))) $sql .= " AND s.client IN (".$db->escape($search_type).")";
	if ($search_type > 0 && in_array($search_type,array('4')))         $sql .= " AND s.fournisseur = 1";
	if ($search_type == '0') $sql .= " AND s.client = 0 AND s.fournisseur = 0";

	$result=$db->query($sql);
	if ($result)
	{
		if ($db->num_rows($result) == 1)
		{
			$obj = $db->fetch_object($result);
			$socid = $obj->rowid;
			header("Location: ".DOL_URL_ROOT."/societe/soc.php?socid=".$socid);
			exit;
		}
		$db->free($result);
	}
}



/*
 * View
 */

$form=new Form($db);
$htmlother=new FormOther($db);
$companystatic=new Societe($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);


// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
    $search_categ='';
    $search_sale='';
    $socname="";
	$search_nom="";
	$search_town="";
	$search_idprof1='';
	$search_idprof2='';
	$search_idprof3='';
	$search_idprof4='';
	$search_type='';
	$search_zip='';
	$search_address='';
	$search_phone='';
	$ts_logistique='';
	$ts_prospection='';
	$search_status='';
}

if ($socname)
{
	$search_nom=$socname;
}


/*
 * Mode Liste
 */
/*
 REM: Regle sur droits "Voir tous les clients"
 REM: Exemple, voir la page societe.php dans le mode liste.
 Utilisateur interne socid=0 + Droits voir tous clients        => Voit toute societe
 Utilisateur interne socid=0 + Pas de droits voir tous clients => Ne voit que les societes liees comme commercial
 Utilisateur externe socid=x + Droits voir tous clients        => Ne voit que lui meme
 Utilisateur externe socid=x + Pas de droits voir tous clients => Ne voit que lui meme
 */
$title=$langs->trans("ListOfThirdParties");

$sql = "SELECT s.rowid, s.nom as name, s.town, s.datec, s.datea,status, s.code_client, s.code_fournisseur, ";
$sql.= " st.libelle as stcomm, s.prefix_comm, s.client, s.fournisseur, s.canvas, s.status as status,";
$sql.= " s.siren as idprof1, s.siret as idprof2, ape as idprof3, idprof4 as idprof4";
$sql.= " ,s.zip";
$sql.= " ,s.address";
$sql.= " ,s.phone";
$sql.= " ,typent.libelle as typent";
$sql.= " ,pays.libelle as payslib";
$sql.= " ,extra.ts_maison";
$sql.= " ,s.siret";
$sql.= " ,(SELECT MAX(propal.date_cloture) FROM ".MAIN_DB_PREFIX."propal as propal WHERE propal.fk_statut=2 AND propal.fk_soc=s.rowid) as lastpropalsigndt";
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
// We'll need these fields in order to filter by categ
if ($search_categ) $sql .= ", cs.fk_categorie, cs.fk_societe";
$sql.= " FROM (".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."c_stcomm as st)";
$sql.= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."c_typent as typent ON typent.id=s.fk_typent";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as pays ON pays.rowid=s.fk_pays";
$sql.= ", ".MAIN_DB_PREFIX."societe_extrafields as extra";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale || (!$user->rights->societe->client->voir && !$socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
$sql.= " AND extra.fk_object=s.rowid";
if (! $user->rights->societe->client->voir && ! $socid)	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)	$sql.= " AND s.rowid = ".$socid;
if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";        // Join for the needed table to filter by sale
if ($search_categ) $sql.= " AND s.rowid = cs.fk_societe";   // Join for the needed table to filter by categ
if (! $user->rights->fournisseur->lire) $sql.=" AND (s.fournisseur <> 1 OR s.client <> 0)";    // client=0, fournisseur=0 must be visible
// Insert sale filter
if ($search_sale)
{
    $sql .= " AND sc.fk_user = ".$search_sale;
}
// Insert categ filter
if ($search_categ)
{
    $sql .= " AND cs.fk_categorie = ".$search_categ;
}
if ($search_nom_only)
{
	$sql.= " AND s.nom LIKE '%".$db->escape($search_nom_only)."%'";
}
if ($search_all)
{
	$sql.= " AND (";
	$sql.= "s.nom LIKE '%".$db->escape($search_all)."%'";
	$sql.= " OR s.code_client LIKE '%".$db->escape($search_all)."%'";
	$sql.= " OR s.email LIKE '%".$db->escape($search_all)."%'";
	$sql.= " OR s.url LIKE '%".$db->escape($search_all)."%'";
	$sql.= ")";
}
if ($search_nom)
{
	$sql.= " AND (";
	$sql.= "s.nom LIKE '%".$db->escape($search_nom)."%'";
	$sql.= " OR s.code_client LIKE '%".$db->escape($search_nom)."%'";
	$sql.= " OR s.email LIKE '%".$db->escape($search_nom)."%'";
	$sql.= " OR s.url LIKE '%".$db->escape($search_nom)."%'";
	$sql.= ")";
}
if ($search_town)   $sql .= " AND s.town LIKE '%".$db->escape($search_town)."%'";
if ($search_zip)   $sql .= " AND s.zip LIKE '%".$db->escape($search_zip)."%'";
if ($search_status!='') $sql .= " AND s.status = ".$db->escape($search_status);
if ($search_address)   $sql .= " AND s.address LIKE '%".$db->escape($search_address)."%'";
if ($search_phone)   $sql .= " AND s.phone LIKE '%".$db->escape(str_replace(' ', '', $search_phone))."%'";
/*if ($search_idprof1) $sql .= " AND s.siren LIKE '%".$db->escape($search_idprof1)."%'";
if ($search_idprof2) $sql .= " AND s.siret LIKE '%".$db->escape($search_idprof2)."%'";
if ($search_idprof3) $sql .= " AND s.ape LIKE '%".$db->escape($search_idprof3)."%'";
if ($search_idprof4) $sql .= " AND s.idprof4 LIKE '%".$db->escape($search_idprof4)."%'";
if ($search_idprof5) $sql .= " AND s.idprof5 LIKE '%".$db->escape($search_idprof5)."%'";
if ($search_idprof6) $sql .= " AND s.idprof6 LIKE '%".$db->escape($search_idprof6)."%'";*/
// Filter on type of thirdparty
if ($search_type > 0 && in_array($search_type,array('1,3','2,3'))) $sql .= " AND s.client IN (".$db->escape($search_type).")";
if ($search_type > 0 && in_array($search_type,array('4')))         $sql .= " AND s.fournisseur = 1";
if ($search_type == '0') $sql .= " AND s.client = 0 AND s.fournisseur = 0";
if ($search_parent) $sql .= " AND s.parent =".$search_parent;
if (! empty ( $ts_logistique )) {
	$sql .= " AND extra.ts_logistique = ".$db->escape($ts_logistique);
}
if (! empty ( $ts_prospection )) {
	$sql .= " AND extra.ts_prospection = ".$db->escape($ts_prospection);
}

//print $sql;

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

dol_syslog('societe.php :: sql='.$sql);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$params = "&amp;socname=".$socname."&amp;search_nom=".$search_nom."&amp;search_town=".$search_town;
	$params.= '&amp;search_idprof1='.$search_idprof1;
	$params.= '&amp;search_idprof2='.$search_idprof2;
	$params.= '&amp;search_idprof3='.$search_idprof3;
	$params.= '&amp;search_idprof4='.$search_idprof4;
	$params.= '&amp;search_zip='.$search_zip;
	$params.= '&amp;search_adress='.$search_adress;
	$params.= '&amp;search_phone='.$search_phone;
	if ($search_status != '') $param.='&amp;search_status='.$search_status;

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

    // Show delete result message
    if (GETPOST('delsoc'))
    {
        dol_htmloutput_mesg($langs->trans("CompanyDeleted",GETPOST('delsoc')),'','ok');
    }

	$langs->load("other");
	$textprofid=array();
	foreach(array(1,2,3,4) as $key)
	{
		$label=$langs->transnoentities("ProfId".$key.$mysoc->country_code);
		$textprofid[$key]='';
		if ($label != "ProfId".$key.$mysoc->country_code)
		{	// Get only text between ()
			if (preg_match('/\((.*)\)/i',$label,$reg)) $label=$reg[1];
			$textprofid[$key]=$langs->trans("ProfIdShortDesc",$key,$mysoc->country_code,$label);
		}
	}

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	
	
	// Filter on categories
	$moreforfilter='';
	if (! empty($conf->categorie->enabled))
	{
		$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$htmlother->select_categories(2,$search_categ,'search_categ',1);
		$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
	{
		$moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
		$moreforfilter.=$htmlother->select_salesrepresentatives($search_sale,'search_sale',$user);
	}
	
	$moreforfilter.=$langs->trans('ParentCompany'). ': ';
	$moreforfilter.=$form->select_company($search_parent,'search_parent','extra.ts_maison=1',1);
	
	$extrafields = new ExtraFields ( $db );
	$extralabels = $extrafields->fetch_name_optionals_label ( 'societe', true );
	if (is_array($extralabels) && key_exists('ts_logistique', $extralabels)) {
		$moreforfilter.=$extralabels['ts_logistique'];
		$moreforfilter.=$extrafields->showInputField('ts_logistique', $ts_logistique);
	}
	if (is_array($extralabels) && key_exists('ts_prospection', $extralabels)) {
		$moreforfilter.=$extralabels['ts_prospection'];
		$moreforfilter.=$extrafields->showInputField('ts_prospection', $ts_prospection);
	}
	if ($moreforfilter)
	{
		print '<div class="liste_titre">';
		print $moreforfilter;
		print '</div>';
	}

	print '<table class="liste" width="100%">';

    // Filter on categories
    /* Not possible in this page because list is for ALL third parties type
	$moreforfilter='';
    if (! empty($conf->categorie->enabled))
    {
        $moreforfilter.=$langs->trans('Categories'). ': ';
        $moreforfilter.=$htmlother->select_categories(2,$search_categ,'search_categ');
        $moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
    }
    // If the user can view prospects other than his'
    if ($user->rights->societe->client->voir || $socid)
    {
        $moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
        $moreforfilter.=$htmlother->select_salesrepresentatives($search_sale,'search_sale',$user);
    }
    if ($moreforfilter)
    {
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" colspan="8">';
        print $moreforfilter;
        print '</td></tr>';
    }
	*/

    // Lines of titles
    print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Address"),$_SERVER["PHP_SELF"],"s.address","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Country"),$_SERVER["PHP_SELF"],"pays.libelle","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Phone"),$_SERVER["PHP_SELF"],"s.phone","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdPartyType"),$_SERVER["PHP_SELF"],"s.fk_typent","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Dernière prop. signée"),$_SERVER["PHP_SELF"],"","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"s.datec","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("SIRET"),$_SERVER["PHP_SELF"],"s.siret","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre($form->textwithpicto($langs->trans("ProfId1Short"),$textprofid[1],1,0),$_SERVER["PHP_SELF"],"s.siren","",$params,'class="nowrap"',$sortfield,$sortorder);
	//print_liste_field_titre($form->textwithpicto($langs->trans("ProfId2Short"),$textprofid[2],1,0),$_SERVER["PHP_SELF"],"s.siret","",$params,'class="nowrap"',$sortfield,$sortorder);
	//print_liste_field_titre($form->textwithpicto($langs->trans("ProfId3Short"),$textprofid[3],1,0),$_SERVER["PHP_SELF"],"s.ape","",$params,'class="nowrap"',$sortfield,$sortorder);
	//print_liste_field_titre($form->textwithpicto($langs->trans("ProfId4Short"),$textprofid[4],1,0),$_SERVER["PHP_SELF"],"s.idprof4","",$params,'class="nowrap"',$sortfield,$sortorder);
	print '<td></td>';
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$params,'align="center"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	if (! empty($search_nom_only) && empty($search_nom)) $search_nom=$search_nom_only;
	print '<input class="flat" type="text" name="search_nom" value="'.$search_nom.'">';
	print '</td>';
	//zip
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_zip" value="'.$search_zip.'">';
	print '</td>';
	//Town
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_town" value="'.$search_town.'">';
	print '</td>';
	
	//Address
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_address" value="'.$search_address.'">';
	print '</td>';
	
	//country
	print '<td class="liste_titre">';
	print '</td>';
	
	//Phone
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_phone" value="'.$search_phone.'">';
	print '</td>';
	
	//Type Ent
	print '<td class="liste_titre">';
	print '</td>';
	
	//Derniére prop signée
	print '<td class="liste_titre">';
	print '</td>';
	
	//created date
	print '<td class="liste_titre">';
	print '</td>';
	
	//SIRET
	print '<td class="liste_titre">';
	print '</td>';
	/*
	// IdProf1
	print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_idprof1" value="'.$search_idprof1.'">';
	print '</td>';
	// IdProf2
	print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_idprof2" value="'.$search_idprof2.'">';
	print '</td>';
	// IdProf3
	print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_idprof3" value="'.$search_idprof3.'">';
	print '</td>';
	// IdProf4
	print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_idprof4" value="'.$search_idprof4.'">';
	print '</td>';
	*/
	// Type (customer/prospect/supplier)
	print '<td class="liste_titre" align="middle">';
	print '<select class="flat" name="search_type">';
	print '<option value="-1"'.($search_type==''?' selected="selected"':'').'>&nbsp;</option>';
	if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1,3"'.($search_type=='1,3'?' selected="selected"':'').'>'.$langs->trans('Customer').'</option>';
	if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="2,3"'.($search_type=='2,3'?' selected="selected"':'').'>'.$langs->trans('Prospect').'</option>';
	//if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="3"'.($search_type=='3'?' selected="selected"':'').'>'.$langs->trans('ProspectCustomer').'</option>';
	print '<option value="4"'.($search_type=='4'?' selected="selected"':'').'>'.$langs->trans('Supplier').'</option>';
	print '<option value="0"'.($search_type=='0'?' selected="selected"':'').'>'.$langs->trans('Others').'</option>';
	print '</select></td>';
	// Status
	
	//status
	print '<td class="liste_titre" align="center">';
	print $form->selectarray('search_status', array(''=>'','0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$search_status);
	//print '</td>';
	
	//print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print "</tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]><td>";
		$companystatic->id=$obj->rowid;
		$companystatic->name=$obj->name;
		$companystatic->canvas=$obj->canvas;
        $companystatic->client=$obj->client;
        $companystatic->fournisseur=$obj->fournisseur;
        $companystatic->code_client=$obj->code_client;
        $companystatic->code_fournisseur=$obj->code_fournisseur;
        $companystatic->status=$obj->status;
        $companystatic->array_options['options_ts_maison']=$obj->ts_maison;
		print $companystatic->getNomUrl(1,'',35);
		print "</td>\n";
		print "<td>".$obj->zip."</td>\n";
		print "<td>".$obj->town."</td>\n";
		print "<td>".$obj->address."</td>\n";
		print "<td>".$obj->payslib."</td>\n";
		print "<td>".dol_print_phone($obj->phone)."</td>\n";
		print "<td>".$obj->typent."</td>\n";

		print "<td>".dol_print_date($db->jdate($obj->lastpropalsigndt),'daytextshort')."</td>\n";
		
		print "<td>".dol_print_date($db->jdate($obj->datec),'daytextshort')."</td>\n";
		
		print "<td>".$obj->siret."</td>\n";
		
		//print "<td>".$obj->idprof1."</td>\n";
		//print "<td>".$obj->idprof2."</td>\n";
		//print "<td>".$obj->idprof3."</td>\n";
		//print "<td>".$obj->idprof4."</td>\n";
		print '<td align="center">';
		$s='';
		if (($obj->client==1 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
		{
	  		$companystatic->name=$langs->trans("Customer");
		    $s.=$companystatic->getNomUrl(0,'customer');
		}
		if (($obj->client==2 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
		{
            if ($s) $s.=" / ";
		    $companystatic->name=$langs->trans("Prospect");
            $s.=$companystatic->getNomUrl(0,'prospect');
		}
		if (! empty($conf->fournisseur->enabled) && $obj->fournisseur)
		{
			if ($s) $s.=" / ";
            $companystatic->name=$langs->trans("Supplier");
            $s.=$companystatic->getNomUrl(0,'supplier');
		}
		print $s;
		print '</td>';
        print '<td align="center">'.$companystatic->getLibStatut(3).'</td>';

		print '</tr>'."\n";
		$i++;
	}

	$db->free($resql);

	print "</table>";

	print '</form>';

}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();

?>
