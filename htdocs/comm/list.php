<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Florian Henry       <florian.henry@open-concept.pro>
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
 *	\file       htdocs/comm/list.php
 *	\ingroup    commercial societe
 *	\brief      List of customers
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("commercial");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page=GETPOST('page','int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$search_nom=GETPOST("search_nom");
$search_zipcode=GETPOST("search_zipcode");
$search_town=GETPOST("search_town");
$search_code=GETPOST("search_code");
$search_compta=GETPOST("search_compta");
$search_status		= GETPOST("search_status",'int');
if ($search_status=='') $search_status=1; // always display activ customer first

$search_address=GETPOST('search_address');
$search_phone=GETPOST('search_phone');
$search_type=trim(GETPOST('search_type'));

// Load sale and categ filters
$search_sale  = GETPOST("search_sale");
$search_categ = GETPOST("search_categ",'int');
$catid        = GETPOST("catid",'int');
// If the internal user must only see his customers, force searching by him
if (!$user->rights->societe->client->voir && !$socid) $search_sale = $user->id;

$ts_logistique=GETPOST('options_ts_logistique','int');
$ts_prospection=GETPOST('options_ts_prospection','int');
$search_parent=GETPOST('search_parent','int');
if ($search_parent==-1) $search_parent='';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('customerlist'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
    $search_categ='';
    $catid='';
    $search_sale='';
    $socname="";
    $search_nom="";
    $search_zipcode="";
    $search_town="";
    $search_idprof1='';
    $search_idprof2='';
    $search_idprof3='';
    $search_idprof4='';
    $seach_status=1;
    $search_phone='';
    $search_address='';
    $search_parent='';
    $ts_logistique='';
    $ts_prospection='';
    $search_type='';
    
}



/*
 * view
 */

$formother=new FormOther($db);
$form = new Form($db);
$thirdpartystatic=new Societe($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);

$sql = "SELECT s.rowid, s.nom as name, s.client, s.zip, s.town, st.libelle as stcomm, s.prefix_comm, s.code_client, s.code_compta, s.status as status,";
$sql.= " s.datec, s.datea, s.canvas";
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
$sql.= " ,s.address";
$sql.= " ,s.phone";
$sql.= " ,typent.libelle as typent";
$sql.= " ,pays.libelle as payslib";
$sql.= " ,(SELECT MAX(propal.date_cloture) FROM ".MAIN_DB_PREFIX."propal as propal WHERE propal.fk_statut=2 AND propal.fk_soc=s.rowid) as lastpropalsigndt";
$sql.= " FROM (".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st)";
if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_societe"; // We need this table joined to the select in order to filter by categ
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent ON typent.id=s.fk_typent";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as pays ON pays.rowid=s.fk_pays";
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
if (! empty ( $ts_logistique ) || ! empty ( $ts_prospection )) {
	$sql.= ", ".MAIN_DB_PREFIX."societe_extrafields as extra";
}
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.client IN (1, 3)";
$sql.= ' AND s.entity IN ('.getEntity('societe', 1).')';
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale) $sql.= " AND s.rowid = sc.fk_soc";
if ($socid) $sql.= " AND s.rowid = ".$socid;
if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
if ($catid > 0)          $sql.= " AND cs.fk_categorie = ".$catid;
if ($catid == -2)        $sql.= " AND cs.fk_categorie IS NULL";
if ($search_categ > 0)   $sql.= " AND cs.fk_categorie = ".$search_categ;
if ($search_categ == -2) $sql.= " AND cs.fk_categorie IS NULL";
if ($search_nom)   $sql.= " AND (s.nom LIKE '%".$db->escape($search_nom)."%' OR s.code_client LIKE '%".$db->escape($search_nom)."%')";
if ($search_zipcode) $sql.= " AND s.zip LIKE '".$db->escape($search_zipcode)."%'";
if ($search_town) $sql.= " AND s.town LIKE '%".$db->escape($search_town)."%'";
if ($search_code)  $sql.= " AND s.code_client LIKE '%".$db->escape($search_code)."%'";
if ($search_compta) $sql.= " AND s.code_compta LIKE '%".$db->escape($search_compta)."%'";
if ($search_status!='') $sql .= " AND s.status = ".$db->escape($search_status);
if ($search_phone)   $sql .= " AND s.phone LIKE '%".$db->escape(str_replace(' ', '', $search_phone))."%'";
if ($search_address)   $sql .= " AND s.address LIKE '%".$db->escape($search_address)."%'";
if ($search_parent) $sql .= " AND s.parent =".$search_parent;
// Filter on type of thirdparty
if ($search_type > 0 && in_array($search_type,array('1,3','2,3'))) $sql .= " AND s.client IN (".$db->escape($search_type).")";
if ($search_type > 0 && in_array($search_type,array('4')))         $sql .= " AND s.fournisseur = 1";
if ($search_type == '0') $sql .= " AND s.client = 0 AND s.fournisseur = 0";

// Insert sale filter
if ($search_sale)
{
	$sql .= " AND sc.fk_user = ".$search_sale;
}
if (! empty ( $ts_logistique ) || ! empty ( $ts_prospection )) {
	$sql.= " AND extra.fk_object=s.rowid";
}
if (! empty ( $ts_logistique )) {
	$sql .= " AND extra.ts_logistique = ".$db->escape($ts_logistique);
}
if (! empty ( $ts_prospection )) {
	$sql .= " AND extra.ts_prospection = ".$db->escape($ts_prospection);
}

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit +1, $offset);

dol_syslog('comm/list.php: sql='.$sql,LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	$param = "&amp;search_nom=".$search_nom."&amp;search_code=".$search_code."&amp;search_zipcode=".$search_zipcode."&amp;search_town=".$search_town;
 	if ($search_categ != '') $param.='&amp;search_categ='.$search_categ;
 	if ($search_sale != '')	$param.='&amp;search_sale='.$search_sale;
 	if ($search_status != '') $param.='&amp;search_status='.$search_status;
 	if ($search_phone != '') $param.='&amp;search_phone='.$search_phone;
 	if ($search_address != '') $param.='&amp;search_address='.$search_address;
 	if ($search_parent != '') $param.='&amp;search_parent='.$search_parent;

	print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	$i = 0;

	print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

	// Filter on categories
 	$moreforfilter='';
	
 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
	 	$moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user);
 	}
 	
 	$moreforfilter.=$langs->trans('ParentCompany'). ': ';
 	$moreforfilter.=$form->select_company($search_parent,'search_parent','s.parent IS NULL',1);

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

	print '<table class="liste" width="100%">'."\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Address"),$_SERVER["PHP_SELF"],"s.address","",$params,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Country"),$_SERVER["PHP_SELF"],"pays.libelle","",$params,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Phone"),$_SERVER["PHP_SELF"],"s.phone","",$params,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("ThirdPartyType"),$_SERVER["PHP_SELF"],"s.fk_typent","",$params,'',$sortfield,$sortorder);
    
	//print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AccountancyCode"),$_SERVER["PHP_SELF"],"s.code_compta","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"datec","",$param,'align="right"',$sortfield,$sortorder);
	print '<td></td>';
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Dernière prop. signée"),$_SERVER["PHP_SELF"],"","",$params,'',$sortfield,$sortorder);
    print '<td class="liste_titre" width="1%">&nbsp;</td>';
    $parameters=array();
    $formconfirm=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook

    print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_nom" value="'.$search_nom.'" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_zipcode" value="'.$search_zipcode.'" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
    print '<input type="text" class="flat" name="search_town" value="'.$search_town.'" size="10">';
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
    
    /*
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" name="search_code" value="'.$search_code.'" size="10">';
    print '</td>';*/

    print '<td align="left" class="liste_titre">';
    print '<input type="text" class="flat" name="search_compta" value="'.$search_compta.'" size="10">';
    print '</td>';
    
    //Date creation
    print '<td class="liste_titre" align="center">';
    print '&nbsp;';
    print '</td>';
    
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
	
	//status
    print '<td class="liste_titre" align="center">';
    print $form->selectarray('search_status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$search_status);
    print '</td>';
    
    print '<td class="liste_titre" align="center">';
    print '&nbsp;';
    print '</td>';

    print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '&nbsp; ';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';

    $parameters=array();
    $formconfirm=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook

    print "</tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($result);

		$var=!$var;

		print "<tr $bc[$var]>";
		print '<td>';
		$thirdpartystatic->id=$obj->rowid;
        $thirdpartystatic->name=$obj->name;
        $thirdpartystatic->client=$obj->client;
        $thirdpartystatic->code_client=$obj->code_client;
        $thirdpartystatic->canvas=$obj->canvas;
        $thirdpartystatic->status=$obj->status;
        print $thirdpartystatic->getNomUrl(1);
		print '</td>';
		print '<td>'.$obj->zip.'</td>';
        print '<td>'.$obj->town.'</td>';
        print "<td>".$obj->address."</td>\n";
        print "<td>".$obj->payslib."</td>\n";
        print "<td>".dol_print_phone($obj->phone)."</td>\n";
        print "<td>".$obj->typent."</td>\n";
        //print '<td>'.$obj->code_client.'</td>';
        print '<td>'.$obj->code_compta.'</td>';
        print '<td align="right">'.dol_print_date($db->jdate($obj->datec),'day').'</td>';
        
        print '<td align="center">';
        $s='';
        if (($obj->client==1 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
        {
        	$thirdpartystatic->name=$langs->trans("Customer");
        	$s.=$thirdpartystatic->getNomUrl(0,'customer');
        }
        if (($obj->client==2 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
        {
        	if ($s) $s.=" / ";
        	$thirdpartystatic->name=$langs->trans("Prospect");
        	$s.=$thirdpartystatic->getNomUrl(0,'prospect');
        }
        if (! empty($conf->fournisseur->enabled) && $obj->fournisseur)
        {
        	if ($s) $s.=" / ";
        	$thirdpartystatic->name=$langs->trans("Supplier");
        	$s.=$thirdpartystatic->getNomUrl(0,'supplier');
        }
        print $s;
        print '</td>';
        
        print '<td align="center">'.$thirdpartystatic->getLibStatut(3);
        print '</td>';
        
        print "<td>".dol_print_date($obj->lastpropalsigndt,'daytextshort')."</td>\n";
        
        print '<td></td>';
        

        $parameters=array('obj' => $obj);
        $formconfirm=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook

        print "</tr>\n";
		$i++;
	}
	//print_barre_liste($langs->trans("ListOfCustomers"), $page, $_SERVER["PHP_SELF"],'',$sortfield,$sortorder,'',$num);
	print "</table>\n";
	print "</form>\n";
	$db->free($result);

	$parameters=array('sql' => $sql);
	$formconfirm=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
?>
