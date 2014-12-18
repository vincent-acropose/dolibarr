<?php
/* Copyright (C) 2013-2014		Charles-Fr BENKE 		<charles.fr@benke.fr>
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
 *	\file       	htdocs/mylist/mylist.php
 *	\ingroup    	listtable
 *	\brief      	list of fields
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

require_once DOL_DOCUMENT_ROOT.'/mylist/class/mylist.class.php';


$socid=GETPOST('socid','int');


$codeListable=GETPOST('code','alpha');

// load the mylist definition
$myliststatic = new Mylist($db);
$myliststatic->fetch($codeListable);


if ($myliststatic->langs)
	foreach(explode(":", $myliststatic->langs) as $newlang)
		$langs->load($newlang);

$langs->load('mylist@mylist');
$langs->load('personalfields@mylist');

// Security check
$module='mylist';

if (! empty($user->societe_id))
	$socid=$user->societe_id;
	
if (! empty($socid))
{
	$objectid=$socid;
	$module='societe';
	$dbtable='&societe';
}

//$result = restrictedArea($user, $module, $objectid, $dbtable);


/*
 * Actions
 */
if (GETPOST('action')=="dojob")
{
	// on r�cup�re les id � traiter
	$tbllistcheck= GETPOST('checksel');
	foreach ($tbllistcheck as $rowidsel) 
	{
		// on r�cup�re la requete � lancer
		$sqlQuerydo=$myliststatic->querydo;
		// on lance la requete
		$sqlQuerydo=str_replace("#ROWID#", $rowidsel, $sqlQuerydo);
		dol_syslog("mylist.php"."::sqlQuerydo=".$sqlQuerydo);
		//print $sqlQuerydo;
		$resultdo=$db->query($sqlQuerydo);
	}
}


/*
 * View
 */

// mode onglet : il est actif et une cl� est transmise
$idreftab=GETPOST('id');
if (!empty($myliststatic->elementtab) && $idreftab != "")
{
	$form = new Form($db);
	llxHeader();
	switch($myliststatic->elementtab) {
		case 'Societe' :
			require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
			$objecttab = new Societe($db);
			$result = $objecttab->fetch($idreftab);
			$head = societe_prepare_head($objecttab);
			dol_fiche_head($head, 'mylist_'.$myliststatic->code, $langs->trans("ThirdParty"), 0, 'company');

			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="border" width="100%">';
			print '<tr><td width="20%">'.$langs->trans('ThirdPartyName').'</td>';
			print '<td colspan="3">';
			print $form->showrefnav($objecttab,'id','',($user->societe_id?0:1),'rowid','nom','','&code='.$codeListable);
			print '</td></tr>';

			if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
			{
				print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$objecttab->prefix_comm.'</td></tr>';
			}

			if ($objecttab->client)
			{
				print '<tr><td>';
				print $langs->trans('CustomerCode').'</td><td colspan="3">';
				print $objecttab->code_client;
				if ($objecttab->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
				print '</td></tr>';
			}

			if ($objecttab->fournisseur)
			{
				print '<tr><td>';
				print $langs->trans('SupplierCode').'</td><td colspan="3">';
				print $objecttab->code_fournisseur;
				if ($objecttab->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
				print '</td></tr>';
			}
			print '</table></form><br>';

			break;

		case 'Product' :
			require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
			require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
			$objecttab = new Product($db);
			$result = $objecttab->fetch($idreftab);
			$head = product_prepare_head($objecttab, $user);
			dol_fiche_head($head, 'mylist_'.$myliststatic->code, $langs->trans("Product"), 0, 'product');
			
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="border" width="100%">';

			print '<tr>';
			print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
			print $form->showrefnav($objecttab,'ref','',1,'ref');
			print '</td>';
			print '</tr>';

			// Label
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$objecttab->libelle.'</td></tr>';

			// Status (to sell)
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
			print $objecttab->getLibStatut(2,0);
			print '</td></tr>';

			// Status (to buy)
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
			print $objecttab->getLibStatut(2,1);
			print '</td></tr>';

			print '</table></form><br>';

			break;
			
		case 'CategSociete' :
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

			$objecttab = new Categorie($db);
			$result = $objecttab->fetch($idreftab);

			$title=$langs->trans("SocietesCategoryShort");
			$type = 2;
			$head = categories_prepare_head($objecttab, $type);
			dol_fiche_head($head, 'mylist_'.$myliststatic->code, $title, 0, 'category');

			print '<table class="border" width="100%">';

			// Path of category
			print '<tr><td width="20%" class="notopnoleft">';
			$ways = $objecttab->print_all_ways();
			print $langs->trans("Ref").'</td><td>';
			print '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
			foreach ($ways as $way)
			{
				print $way."<br>\n";
			}
			print '</td></tr>';

			// Description
			print '<tr><td width="20%" class="notopnoleft">';
			print $langs->trans("Description").'</td><td>';
			print nl2br($objecttab->description);
			print '</td></tr>';		
			print '</table><br>';
			break;

		case 'CategProduct' :
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

			$objecttab = new Categorie($db);
			$result = $objecttab->fetch($idreftab);

			$title=$langs->trans("ProductsCategoryShort");
			$type = 0;
			$head = categories_prepare_head($objecttab, $type);
			dol_fiche_head($head, 'mylist_'.$myliststatic->code, $title, 0, 'category');

			print '<table class="border" width="100%">';

			// Path of category
			print '<tr><td width="20%" class="notopnoleft">';
			$ways = $objecttab->print_all_ways();
			print $langs->trans("Ref").'</td><td>';
			print '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
			foreach ($ways as $way)
			{
				print $way."<br>\n";
			}
			print '</td></tr>';

			// Description
			print '<tr><td width="20%" class="notopnoleft">';
			print $langs->trans("Description").'</td><td>';
			print nl2br($objecttab->description);
			print '</td></tr>';		
			print '</table><br>';
			break;
	}
}
else
	llxHeader('',$myliststatic->label,'EN:mylist_EN|FR:mylist_FR|ES:mylist_ES');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);


$now=dol_now();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
if (! $sortfield) $sortfield='1';
if (! $sortorder) $sortorder='DESC';

if (empty($conf->global->MAIN_USE_JQUERY_DATATABLES)) 
{
	$page = GETPOST("page",'int');
	if ($page == -1) { $page = 0; }
	$offset = $conf->global->MYLIST_NB_ROWS * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
}

// construction de la requete sql 
$limit = $conf->global->MYLIST_NB_ROWS;
$ArrayTable =$myliststatic->fieldused;
$sql = "SELECT DISTINCT ". $myliststatic->GetSqlFields($ArrayTable);

// Replace the prefix tables
if ($dolibarr_main_db_prefix != 'llx_')
	$sql.= " ".preg_replace('/llx_/i',$dolibarr_main_db_prefix, $myliststatic->querylist);
else
	$sql.= " ".$myliststatic->querylist;

// init fields managment
if ($myliststatic->fieldinit)
{
	$tblInitFields=explode(":",$myliststatic->fieldinit);
	foreach ($tblInitFields as $initfields ) 
	{
		$tblInitField=explode("=",$initfields);
		$valueinit = GETPOST($tblInitField[0]);
		// on prend la valeur par d�faut si la valeur n'est pas saisie...
		if (!$valueinit)
			$valueinit = $tblInitField[1];
		$sql=str_replace("#".$tblInitField[0]."#", $valueinit, $sql);
	}
}

// boucle sur les champs filtrables
$sqlfilter= $myliststatic->GetSqlFilterQuery($ArrayTable);

// pour g�rer le cas du where dans la query
// si y a des champs � filter et pas de where dans la requete de base
if ($sqlfilter && strpos(strtoupper($sql), "WHERE") ==0)
	$sqlfilter= " WHERE 1=1 ".$sqlfilter;
	
// pour g�rer le cas du filtrage selon utilisateur
if (strpos(strtoupper($sql), "#USER#") > 0)
	$sql=str_replace("#USER#", $user->id, $sql);

// filtre sur l'id de l'�l�ment en mode tabs
if (!empty($myliststatic->elementtab) && $idreftab != "")
{
	switch($myliststatic->elementtab) {
		case 'Societe' :
			// il faut la table societe as s
			//$sql.=", srowid as elementrowid";
			$sqlfilter.=" AND s.rowid=".$idreftab;
			break;
		case 'Product' :
			// il faut la table product as p
			$sqlfilter.=" AND p.rowid=".$idreftab;
			break;
		case 'CategProduct' :
		case 'CategSociete' :
			// il faut la table categories as c
			$sqlfilter.=" AND c.rowid=".$idreftab;
			break;
	}

}

// on positionne les champs � filter avant un group by ou un order by
if (strpos(strtoupper($sql), 'GROUP BY') > 0)
{
	// on d�coupe le sql
	$sqlleft=substr($sql,0,strpos(strtoupper($sql), 'GROUP BY')-1);
	$sqlright=substr($sql,strpos(strtoupper($sql), 'GROUP BY'));
	$sql=$sqlleft." ".$sqlfilter." ".$sqlright;
}
elseif (strpos(strtoupper($sql), 'ORDER BY') > 0)
{
	// on d�coupe le sql
	$sqlleft=substr($sql,0,strpos(strtoupper($sql), 'ORDER BY')-1);
	$sqlright=substr($sql,strpos(strtoupper($sql), 'ORDER BY'));
	$sql=$sqlleft." ".$sqlfilter." ".$sqlright;
}
else
	$sql.= $sqlfilter;

// if we don't allready have a group by
if (strpos(strtoupper($sql), 'GROUP BY') == 0)
	$sql.= $myliststatic->GetGroupBy($ArrayTable);

// Si il y a un order by pr�d�fini dans la requete on d�sactive le tri
if (strpos(strtoupper($myliststatic->querylist), 'ORDER BY') == 0) 
	$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;
	
if ( empty($conf->global->MAIN_USE_JQUERY_DATATABLES)) $sql.= $db->plimit($limit + 1,$offset);

//  pour les tests on affiche la requete SQL 
if ($myliststatic->active ==0)  // lancement de la requete � partir du menu mylist
	print $sql;
	
	
dol_syslog("mylist.php"."::sql=".$sql);
$result=$db->query($sql);


if ($result)
{
    $num = $db->num_rows($resql);
    $i = 0;
	
	// g�n�ration dynamique du param
	$param='&code='.$codeListable;
	$param.="&id=".$idreftab;
	
	if ( empty($conf->global->MAIN_USE_JQUERY_DATATABLES))
	{
		// ajout des filtres 
		$param.=$myliststatic->GenParamFilterFields($ArrayTable);
		$param.=$myliststatic->GenParamFilterInitFields();
		print_barre_liste($myliststatic->label  , $page, $_SERVER["PHP_SELF"],$param, $sortfield, $sortorder, '', $num);
	}
	else
		print_barre_liste($myliststatic->label  , $page, $_SERVER["PHP_SELF"],$param, $sortfield, $sortorder, '', 0);

	// Lignes des champs de filtre
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="code" value="'.$codeListable.'">';
	print '<input type="hidden" name="id" value="'.$idreftab.'">';

	// champs filtr�s, champ personnalis�s et case � cocher
	if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES))
	{
		print '<div STYLE="float:left;">';
		print '<input type="image" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '</div>';
		// gestion des champs personnalis�s
		if (! empty($myliststatic->fieldinit))
		{
			print '<div STYLE="float:left;">';
			print $myliststatic->GenFilterInitFieldsTables();
			print '</div><br><br>';
		}
		// boucle sur les champs filtrables
		print $myliststatic->GenFilterFieldsTables($ArrayTable);
		print '</form>';		

		// gestion de la requete de mise � jour en masse
		if (! empty($myliststatic->querydo))
		{	// on r�cup�re le champ servant de cl� pour la ligne
			foreach ($ArrayTable as $key => $fields) 
			{
				if ($fields['type'] == 'Check')
					if ($fields['alias']!="")
						$lineid=$fields['alias'];
					else
						$lineid=str_replace(array('.', '-'),"_",$fields['field']);
					//print "===".$lineid;
			}
			print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="action" value="dojob">';
			print '<input type="hidden" name="code" value="'.$codeListable.'">';
			print '<input type="hidden" name="id" value="'.$idreftab.'">';
		}

		print '<br><br>';
		print '<table id="listtable" class="noborder" width="100%">';
		print "<thead>\n";
		print '<tr class="liste_titre">';
		foreach ($ArrayTable as $key => $fields) 
			print "<th align=left>".$langs->trans($fields['name'])."</th>";
		if (! empty($myliststatic->querydo))  print "<th>Sel.</th>";
		print '</tr>';
		print "</thead>\n";
	}
	else
	{
		print '<table class="liste" width="100%">';

		if (! empty($myliststatic->fieldinit))
		{
			print '<tr class="liste_titre">';
			print $myliststatic->GenFilterInitFieldsTables();
			print '</tr>';
		}

		print '<tr class="liste_titre">';
		// si il y a une requete de mise � jour
		
		foreach ($ArrayTable as $key => $fields)
			if ($fields['visible']=='true')
				print_liste_field_titre($langs->trans($fields['name']),$_SERVER["PHP_SELF"],$key,'',$param, 'align="'.$fields['align'].'"', $sortfield,$sortorder);
		if (! empty($myliststatic->querydo))  print "<th></th>";
		print "<th></th></tr>\n";

		print '<tr class="liste_titre">';
		
		print $myliststatic->GenFilterFieldsTables($ArrayTable);
		print '<td><input type="image" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
		if (! empty($myliststatic->querydo))  print "<th></th>";
		print "</tr>\n";
		print '</form>';
		if (! empty($myliststatic->querydo))
		{
			print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="action" value="dojob">';
			print '<input type="hidden" name="code" value="'.$codeListable.'">';
			print '<input type="hidden" name="id" value="'.$idreftab.'">';
		}
	}
	print "<tbody>\n";
	
	$var=true;
	$total=0;
	$subtotal=0;

	if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES))
	{
		// en mode datatable si un filtre est appliqu� 
		if ($sqlfilter !="")
			$limit=$num;				// on affiche tous les enregistrements
		else
			$limit=min($num,100);	// sinon on affiche soit le nombre, soit 100 (4 pages)
	}
	else
	{
		// en mode standard on affiche la limite au max
		$limit=min($num,$limit);
	}
	while ($i < $limit)
	{
		$objp = $db->fetch_object($result);
		//var_dump($objp);
		$now = dol_now();
		$var=!$var;
		print '<tr '.$bc[$var].'>';
		foreach ($ArrayTable as $key => $fields) 
		{
			if (!empty($conf->global->MAIN_USE_JQUERY_DATATABLES) || $fields['visible']=='true'	)
			{
				if (strpos($fields['field'], '.rowid') > 0 && $fields['elementfield'])
				{
					// pour les cl�s qui sont li� � un autre �l�ment
					print '<td nowrap="nowrap" align="'.$fields['align'].'">';
					$tblelement=explode(":",$fields['elementfield']);
					if ($tblelement[1]!="")
						require_once DOL_DOCUMENT_ROOT.$tblelement[1];
					if ($fields['alias']!="")
						$fieldsname=$fields['alias'];
					else
						$fieldsname=str_replace(array('.', '-'),"_",$fields['field']);
					// seulement si le champs est renseign�
					if ($objp->$fieldsname)
					{
						$objectstatic = new $tblelement[0]($db);
						$objectstatic->id=$objp->$fieldsname;
						$objectstatic->fetch($objp->$fieldsname);
						print $objectstatic->getNomUrl(1);
					}
					print '</td>';
				}
				elseif (strpos($fields['field'], 'fk_') > 0 && $fields['elementfield']) 
				{
					print '<td nowrap="nowrap" align="'.$fields['align'].'">';
					// cas � part des status
					if (strpos($fields['field'], 'fk_statut') > 0 )
					{
						$tblelement=explode(":",$fields['elementfield']);
						if ($tblelement[1]!="")
							require_once DOL_DOCUMENT_ROOT.$tblelement[1];
						$objectstatic = new $tblelement[0]($db);
						if ($fields['alias']!="")
							$fieldsname=$fields['alias'];
						else
							$fieldsname=str_replace(array('.', '-'),"_",$fields['field']);
							
						$objectstatic->statut=$objp->$fieldsname;
						if ($fieldsname == 'f_fk_statut')
							$objectstatic->paye=1;
						print $objectstatic->getLibStatut(5);
					}
					else
						print $myliststatic->get_infolist($objp->$fieldsname,$fields['elementfield']);
					print '</td>';
				}
				elseif (strpos($fields['field'], 'mc.statut')!==false && $fields['elementfield'])
				{
					print '<td nowrap="nowrap" align="'.$fields['align'].'">';
					// cas � part des status
					if (strpos($fields['field'], 'mc.statut')!==false)
					{
						$tblelement=explode(":",$fields['elementfield']);
						if ($tblelement[1]!="")
							require_once DOL_DOCUMENT_ROOT.$tblelement[1];
						$objectstatic = new $tblelement[0]($db);
						if ($fields['alias']!="")
							$fieldsname=$fields['alias'];
						else
							$fieldsname=str_replace(array('.', '-'),"_",$fields['field']);
							
						$objectstatic->statut=$objp->$fieldsname;
						if ($fieldsname == 'f_fk_statut')
							$objectstatic->paye=1;
						
						print $objectstatic->getLibStatut(5);
					}
					else
						print $myliststatic->get_infolist($objp->$fieldsname,$fields['elementfield']);
					print '</td>';
				}
				else
				{
					print $myliststatic->genDefaultTD ($fields['field'], $fields, $objp);
				}
			}
		}
		// si il y a une requete de mise � jour
		if (! empty($myliststatic->querydo))
		{
			print "\n";
			print '<td align=right>';
			print '<input type="checkbox" name="checksel[]" value="'.$objp->$lineid.'">';
			print '</td>'; 
		}
		if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES))
			print "</tr>\n";
		else
			print "<td></td></tr>\n";
		$i++;
	}
	print '</tbody>';
	print '</table>';
	
	if (! empty($myliststatic->querydo))
	{
		print '<br><div class="tabsAction">';
		print '<input class="butAction" type=submit value="'.$langs->trans('DoJob').'" >';
		print "</div>";

	}
	
	print '</form>';

	$db->free($result);
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
// si datatable est actif on cache les champs affichables
if (!empty($conf->global->MAIN_USE_JQUERY_DATATABLES))
{
	print "\n";
	print '<script type="text/javascript">'."\n";
	print 'jQuery(document).ready(function() {'."\n";
	print 'jQuery("#listtable").dataTable( {'."\n";
	print '"sDom": \'TCR<"clear">lfrtip\','."\n";
	print '"oColVis": {"buttonText": "'.$langs->trans('showhidecols').'" },'."\n";
	print '"bPaginate": true,'."\n";
	print '"bFilter": false,'."\n";
	print '"sPaginationType": "full_numbers",'."\n";
	print $myliststatic->gen_aoColumns($ArrayTable, !empty($myliststatic->querydo)); // pour g�rer le format de certaine colonnes
	print $myliststatic->gen_aasorting($sortfield, $sortorder, $ArrayTable, !empty($myliststatic->querydo)); // pour g�rer le trie par d�faut dans la requete SQL
	print '"bJQueryUI": false,'."\n"; 
	print '"oLanguage": {"sUrl": "'.$langs->trans('datatabledict').'" },'."\n";
	print '"iDisplayLength": '.$conf->global->MYLIST_NB_ROWS.','."\n";
	print '"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],'."\n";
	print '"bSort": true,'."\n";
	print '"oTableTools": { "sSwfPath": "../includes/jquery/plugins/datatables/extras/TableTools/swf/copy_csv_xls_pdf.swf" }'."\n";
	print '} );'."\n";
	print '});'."\n";

	// extension pour le trie
	print 'jQuery.extend( jQuery.fn.dataTableExt.oSort, {';
	// pour g�rer les . et les , des d�cimales et le blanc des milliers
	print '"numeric-comma-pre": function ( a ) {';
	print 'var x = (a == "-") ? 0 : a.replace( /,/, "." );';
	print 'x = x.replace( " ", "" );';
	print 'return parseFloat( x );';
	print '},';
	print '"numeric-comma-asc": function ( a, b ) {return ((a < b) ? -1 : ((a > b) ? 1 : 0));},';
	print '"numeric-comma-desc": function ( a, b ) {return ((a < b) ? 1 : ((a > b) ? -1 : 0));},';
	
	// pour g�rer les dates au format europ�enne
	print '"date-euro-pre": function ( a ) {';
    print 'if ($.trim(a) != "") {';
    print 'var frDatea = $.trim(a).split("/");';
    print 'var x = (frDatea[2] + frDatea[1] + frDatea[0]) * 1;';
    print '} else { var x = 10000000000000; }';
	print 'return x;';
    print '},';
 	print '"date-euro-asc": function ( a, b ) {return a - b; },';
 	print '"date-euro-desc": function ( a, b ) {return b - a;}';
	print '} );';
	print "\n";
	print '</script>'."\n";

	print $myliststatic->genHideFields($ArrayTable);
}
?>
