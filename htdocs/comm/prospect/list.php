<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/comm/prospect/list.php
 *	\ingroup    prospect
 *	\brief      Page to list prospects
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/prospect/class/prospect.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("propal");
$langs->load("companies");

// Security check
$socid = GETPOST("socid",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$action				= GETPOST('action','alpha');
$socname            = GETPOST("socname",'alpha');
$stcomm             = GETPOST("stcomm",'int');
$search_nom         = GETPOST("search_nom");
$search_zipcode     = GETPOST("search_zipcode");
$search_town        = GETPOST("search_town");
$search_state       = GETPOST("search_state");
$search_datec       = GETPOST("search_datec");
$search_categ       = GETPOST("search_categ",'int');
$search_status		= GETPOST("search_status",'int');
if ($search_status=='') $search_status=1; // always display activ customer first
$catid              = GETPOST("catid",'int');

$search_phone=GETPOST('search_phone');
$search_address=GETPOST('search_address');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page      = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$search_level_from = GETPOST("search_level_from","alpha");
$search_level_to   = GETPOST("search_level_to","alpha");

$ts_logistique=GETPOST('options_ts_logistique','int');
$ts_prospection=GETPOST('options_ts_prospection','int');

$search_parent=GETPOST('search_parent','int');
if ($search_parent==-1) $search_parent='';

// If both parameters are set, search for everything BETWEEN them
if ($search_level_from != '' && $search_level_to != '')
{
	// Ensure that these parameters are numbers
	$search_level_from = (int) $search_level_from;
	$search_level_to = (int) $search_level_to;

	// If from is greater than to, reverse orders
	if ($search_level_from > $search_level_to)
	{
		$tmp = $search_level_to;
		$search_level_to = $search_level_from;
		$search_level_from = $tmp;
	}

	// Generate the SQL request
	$sortwhere = '(sortorder BETWEEN '.$search_level_from.' AND '.$search_level_to.') AS is_in_range';
}
// If only "from" parameter is set, search for everything GREATER THAN it
else if ($search_level_from != '')
{
	// Ensure that this parameter is a number
	$search_level_from = (int) $search_level_from;

	// Generate the SQL request
	$sortwhere = '(sortorder >= '.$search_level_from.') AS is_in_range';
}
// If only "to" parameter is set, search for everything LOWER THAN it
else if ($search_level_to != '')
{
	// Ensure that this parameter is a number
	$search_level_to = (int) $search_level_to;

	// Generate the SQL request
	$sortwhere = '(sortorder <= '.$search_level_to.') AS is_in_range';
}
// If no parameters are set, dont search for anything
else
{
	$sortwhere = '0 as is_in_range';
}

// Select every potentiels, and note each potentiels which fit in search parameters
dol_syslog('prospects::prospects_prospect_level',LOG_DEBUG);
$sql = "SELECT code, label, sortorder, ".$sortwhere;
$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
$sql.= " WHERE active > 0";
$sql.= " ORDER BY sortorder";

$resql = $db->query($sql);
if ($resql)
{
	$tab_level = array();
	$search_levels = array();

	while ($obj = $db->fetch_object($resql))
	{
		// Compute level text
		$level=$langs->trans($obj->code);
		if ($level == $obj->code) $level=$langs->trans($obj->label);

		// Put it in the array sorted by sortorder
		$tab_level[$obj->sortorder] = $level;

		// If this potentiel fit in parameters, add its code to the $search_levels array
		if ($obj->is_in_range == 1)
		{
			$search_levels[] = '"'.preg_replace('[^A-Za-z0-9_-]', '', $obj->code).'"';
		}
	}

	// Implode the $search_levels array so that it can be use in a "IN (...)" where clause.
	// If no paramters was set, $search_levels will be empty
	$search_levels = implode(',', $search_levels);
}
else dol_print_error($db);

// Load sale and categ filters
$search_sale = GETPOST('search_sale');
$search_categ = GETPOST('search_categ');
// If the internal user must only see his prospect, force searching by him
if (!$user->rights->societe->client->voir && !$socid) $search_sale = $user->id;

// List of avaible states; we'll need that for each lines (quick changing prospect states) and for search bar (filter by prospect state)
$sts = array(-1,0,1,2,3);


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('prospectlist'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks

if ($action == 'cstc')
{
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["pstcomm"];
	$sql .= " WHERE rowid = ".$_GET["socid"];
	$result=$db->query($sql);
}


/*
 * View
 */

$formother=new FormOther($db);
$form=new Form($db);

$sql = "SELECT s.rowid, s.nom, s.zip, s.town, s.datec, s.datea, s.status as status, s.code_client, s.client,";
$sql.= " st.libelle as stcomm, s.prefix_comm, s.fk_stcomm, s.fk_prospectlevel,";
$sql.= " d.nom as departement";
$sql.= " ,s.address";
$sql.= " ,s.phone";
$sql.= " ,typent.libelle as typent";
$sql.= " ,pays.libelle as payslib";
$sql.= " ,extra.ts_maison";
$sql.= " ,s.siret";
$sql.= " ,(SELECT MAX(propal.date_cloture) FROM ".MAIN_DB_PREFIX."propal as propal WHERE propal.fk_statut=2 AND propal.fk_soc=s.rowid) as lastpropalsigndt";
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
$sql .= " FROM (".MAIN_DB_PREFIX."c_stcomm as st";
$sql.= ", ".MAIN_DB_PREFIX."societe as s)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d on (d.rowid = s.fk_departement)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent ON typent.id=s.fk_typent";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as pays ON pays.rowid=s.fk_pays";
$sql.= ", ".MAIN_DB_PREFIX."societe_extrafields as extra";

if (! empty($search_categ) || ! empty($catid)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_societe"; // We need this table joined to the select in order to filter by categ
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.client IN (2, 3)";
$sql.= ' AND s.entity IN ('.getEntity('societe', 1).')';
$sql.= " AND extra.fk_object=s.rowid";
if ((!$user->rights->societe->client->voir && !$socid) || $search_sale) $sql.= " AND s.rowid = sc.fk_soc";
if ($socid) $sql.= " AND s.rowid = " .$socid;
if (isset($stcomm) && $stcomm != '') $sql.= " AND s.fk_stcomm=".$stcomm;
if ($catid > 0)          $sql.= " AND cs.fk_categorie = ".$catid;
if ($catid == -2)        $sql.= " AND cs.fk_categorie IS NULL";
if ($search_categ > 0)   $sql.= " AND cs.fk_categorie = ".$search_categ;
if ($search_categ == -2) $sql.= " AND cs.fk_categorie IS NULL";
if ($search_nom)   $sql .= " AND (s.nom LIKE '%".$db->escape($search_nom)."%' OR s.code_client LIKE '%".$db->escape($search_nom)."%')";
if ($search_zipcode) $sql .= " AND s.zip LIKE '".$db->escape(strtolower($search_zipcode))."%'";
if ($search_town) $sql .= " AND s.town LIKE '%".$db->escape(strtolower($search_town))."%'";
if ($search_state) $sql .= " AND d.nom LIKE '%".$db->escape(strtolower($search_state))."%'";
if ($search_datec) $sql .= " AND s.datec LIKE '%".$db->escape($search_datec)."%'";
if ($search_status!='') $sql .= " AND s.status = ".$db->escape($search_status);
if ($search_phone)   $sql .= " AND s.phone LIKE '%".$db->escape(str_replace(' ', '', $search_phone))."%'";
if ($search_address)   $sql .= " AND s.address LIKE '%".$db->escape($search_address)."%'";
if ($search_parent) $sql .= " AND s.parent =".$search_parent;

if (! empty ( $ts_logistique )) {
	$sql .= " AND extra.ts_logistique = ".$db->escape($ts_logistique);
}
if (! empty ( $ts_prospection )) {
	$sql .= " AND extra.ts_prospection = ".$db->escape($ts_prospection);
}

// Insert levels filters
if ($search_levels)
{
	$sql .= " AND s.fk_prospectlevel IN (".$search_levels.')';
}
// Insert sale filter
if ($search_sale)
{
	$sql .= " AND sc.fk_user = ".$db->escape($search_sale);
}
if ($socname)
{
	$sql .= " AND s.nom LIKE '%".$db->escape($socname)."%'";
	$sortfield = "s.nom";
	$sortorder = "ASC";
}

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= " ORDER BY $sortfield $sortorder, s.nom ASC";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

dol_syslog('comm/propsect/list.php: sql='.$sql,LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	if ($num == 1 && $socname)
	{
		$obj = $db->fetch_object($resql);
		header("Location: fiche.php?socid=".$obj->rowid);
		exit;
	}
	else
	{
        $help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
        llxHeader('',$langs->trans("ListOfProspects"),$help_url);
	}

	$param='&amp;stcomm='.$stcomm.'&amp;search_nom='.urlencode($search_nom).'&amp;search_zipcode='.urlencode($search_zipcode).'&amp;search_ville='.urlencode($search_ville);
 	// Store the status filter in the URL
 	if (isSet($search_cstc))
 	{
 		foreach ($search_cstc as $key => $value)
 		{
 			if ($value == 'true')
 				$param.='&amp;search_cstc['.((int) $key).']=true';
 			else
 				$param.='&amp;search_cstc['.((int) $key).']=false';
 		}
 	}
 	if ($search_level_from != '') $param.='&amp;search_level_from='.$search_level_from;
 	if ($search_level_to != '') $param.='&amp;search_level_to='.$search_level_to;
 	if ($search_categ != '') $param.='&amp;search_categ='.$search_categ;
 	if ($search_sale != '') $param.='&amp;search_sale='.$search_sale;
 	if ($search_status != '') $param.='&amp;search_status='.$search_status;
 	if ($search_phone != '') $param.='&amp;search_phone='.$search_phone;
 	if ($search_address != '') $param.='&amp;search_address='.$search_address;
 	if ($search_parent != '') $param.='&amp;search_parent='.$search_parent;
 	// $param and $urladd should have the same value
 	$urladd = $param;

	print_barre_liste($langs->trans("ListOfProspects"), $page, $_SERVER["PHP_SELF"], $param, $sortfield,$sortorder,'',$num,$nbtotalofrecords);


 	// Print the search-by-sale and search-by-categ filters
 	print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';

	// Filter on categories
 	$moreforfilter='';
	if (! empty($conf->categorie->enabled))
	{
	 	$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories(2,$search_categ,'search_categ',1);
	 	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
	 	$moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user);
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

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Address"),$_SERVER["PHP_SELF"],"s.address","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Country"),$_SERVER["PHP_SELF"],"pays.libelle","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Phone"),$_SERVER["PHP_SELF"],"s.phone","",$params,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdPartyType"),$_SERVER["PHP_SELF"],"s.fk_typent","",$params,'',$sortfield,$sortorder);
	//print_liste_field_titre($langs->trans("State"),$_SERVER["PHP_SELF"],"s.fk_departement","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"s.datec","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ProspectLevelShort"),$_SERVER["PHP_SELF"],"s.fk_prospectlevel","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("StatusProsp"),$_SERVER["PHP_SELF"],"s.fk_stcomm","",$param,'align="center"',$sortfield,$sortorder);
	print '<td class="liste_titre">&nbsp;</td>';
    print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Dernière prop. signée"),$_SERVER["PHP_SELF"],"","",$params,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("SIRET"),$_SERVER["PHP_SELF"],"s.siret","",$params,'',$sortfield,$sortorder);
    print '<td class="liste_titre">&nbsp;</td>';
    
    $parameters=array();
    $formconfirm=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook

	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_nom" size="10" value="'.$search_nom.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_zipcode" size="10" value="'.$search_zipcode.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_town" size="10" value="'.$search_town.'">';
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
	
	
 	/*print '<td class="liste_titre" align="center">';
    print '<input type="text" class="flat" name="search_state" size="10" value="'.$search_state.'">';
    print '</td>';*/
    print '<td align="center" class="liste_titre">';
	print '<input class="flat" type="text" size="10" name="search_datec" value="'.$search_datec.'">';
    print '</td>';

 	// Added by Matelli
 	print '<td class="liste_titre" align="center">';
 	// Generate in $options_from the list of each option sorted
 	$options_from = '<option value="">&nbsp;</option>';
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_from .= '<option value="'.$tab_level_sortorder.'"'.($search_level_from == $tab_level_sortorder ? ' selected="selected"':'').'>';
 		$options_from .= $langs->trans($tab_level_label);
 		$options_from .= '</option>';
 	}

 	// Reverse the list
 	array_reverse($tab_level, true);

 	// Generate in $options_to the list of each option sorted in the reversed order
 	$options_to = '<option value="">&nbsp;</option>';
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_to .= '<option value="'.$tab_level_sortorder.'"'.($search_level_to == $tab_level_sortorder ? ' selected="selected"':'').'>';
 		$options_to .= $langs->trans($tab_level_label);
 		$options_to .= '</option>';
 	}

 	// Print these two select
 	print $langs->trans("From").' <select class="flat" name="search_level_from">'.$options_from.'</select>';
 	print ' ';
 	print $langs->trans("To").' <select class="flat" name="search_level_to">'.$options_to.'</select>';

    print '</td>';

    print '<td class="liste_titre" align="center">';
	print '&nbsp;';
    print '</td>';

    print '<td class="liste_titre" align="center">';
    print '&nbsp;';
    print '</td>';
    
    print '<td class="liste_titre" align="center">';
     print $form->selectarray('search_status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$search_status);
    print '</td>';
    
    print '<td class="liste_titre" align="center">';
    print '&nbsp;';
    print '</td>';
    
    //SIRET
    print '<td class="liste_titre" align="center">';
    print '&nbsp;';
    print '</td>';

    // Print the search button
    print '<td class="liste_titre" align="right">';
	print '<input class="liste_titre" name="button_search" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';

	$parameters=array();
	$formconfirm=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook

	print "</tr>\n";

	$i = 0;
	$var=true;

	$prospectstatic=new Prospect($db);
	$prospectstatic->client=2;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);

		$var=!$var;

		print '<tr '.$bc[$var].'>';
		print '<td>';
		$prospectstatic->id=$obj->rowid;
		$prospectstatic->nom=$obj->nom;
        $prospectstatic->status=$obj->status;
        $prospectstatic->code_client=$obj->code_client;
        $prospectstatic->client=$obj->client;
        $prospectstatic->fk_prospectlevel=$obj->fk_prospectlevel;
        $prospectstatic->array_options['options_ts_maison']=$obj->ts_maison;
		print $prospectstatic->getNomUrl(1,'prospect');
        print '</td>';
        print "<td>".$obj->zip."&nbsp;</td>";
		print "<td>".$obj->town."&nbsp;</td>";
		
		
		print "<td>".$obj->address."</td>\n";
		print "<td>".$obj->payslib."</td>\n";
		print "<td>".dol_print_phone($obj->phone)."</td>\n";
		print "<td>".$obj->typent."</td>\n";
		
		
		
		
		//print '<td align="center">'.$obj->departement.'</td>';
		// Creation date
		print '<td align="center">'.dol_print_date($db->jdate($obj->datec)).'</td>';
		// Level
		print '<td align="center">';
		print $prospectstatic->getLibProspLevel();
		print "</td>";
		// Statut
		print '<td align="center" class="nowrap">';
		print $prospectstatic->LibProspStatut($obj->fk_stcomm,2);
		print "</td>";

		//$sts = array(-1,0,1,2,3);
		print '<td align="right" class="nowrap">';
		foreach ($sts as $key => $value)
		{
			if ($value <> $obj->fk_stcomm)
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$obj->rowid.'&amp;pstcomm='.$value.'&amp;action=cstc&amp;'.$param.($page?'&amp;page='.$page:'').'">';
				print img_action(0,$value);
				print '</a>&nbsp;';
			}
		}
		print '</td>';

        print '<td align="center">';
		print $prospectstatic->LibStatut($prospectstatic->status,3);
        print '</td>';
        
        print "<td>".dol_print_date($obj->lastpropalsigndt,'daytextshort')."</td>\n";
        
        print "<td>".$obj->siret."</td>\n";
        
        print '<td></td>';
        
        $parameters=array('obj' => $obj);
        $formconfirm=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook

        print "</tr>\n";
		$i++;
	}

	if ($num > $conf->liste_limit || $page > 0) print_barre_liste('', $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);

	print "</table>";

	print "</form>";

	$db->free($resql);

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
