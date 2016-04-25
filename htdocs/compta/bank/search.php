<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Vinícius Nogueira    <viniciusvgn@gmail.com>
 * Copyright (C) 2014       Florian Henry    	 <florian.henry@open-cooncept.pro>
 * Copyright (C) 2015       Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       htdocs/compta/bank/search.php
 *	\ingroup    banque
 *	\brief      List of bank transactions
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("companies");
$langs->load("margins");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque');

$description=GETPOST("description",'alpha');
$debit=GETPOST("debit",'alpha');
$credit=GETPOST("credit",'alpha');
$type=GETPOST("type",'alpha');
$account=GETPOST("account",'alpha');
$bid=GETPOST("bid","int");
$search_dt_start = dol_mktime(0, 0, 0, GETPOST('search_start_dtmonth', 'int'), GETPOST('search_start_dtday', 'int'), GETPOST('search_start_dtyear', 'int'));
$search_dt_end = dol_mktime(0, 0, 0, GETPOST('search_end_dtmonth', 'int'), GETPOST('search_end_dtday', 'int'), GETPOST('search_end_dtyear', 'int'));
$req_userid = GETPOST('userid');
$search_thirdparty=GETPOST("thirdparty",'alpha');
$search_req_nb=GETPOST("req_nb",'alpha');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='b.dateo';

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if (!empty($description)) $param.='&description='.$description;
if (!empty($type)) $param.='&type='.$type;
if (!empty($debit)) $param.='&debit='.$debit;
if (!empty($credit)) $param.='&credit='.$credit;
if (!empty($account)) $param.='&account='.$account;
if (!empty($bid))  $param.='&bid='.$bid;
if (!empty($req_userid)) $param.='&userid='.$req_userid;
if (dol_strlen($search_dt_start) > 0)
    $param .= '&search_start_dtmonth=' . GETPOST('search_start_dtmonth', 'int') . '&search_start_dtday=' . GETPOST('search_start_dtday', 'int') . '&search_start_dtyear=' . GETPOST('search_start_dtyear', 'int');
if (dol_strlen($search_dt_end) > 0)
    $param .= '&search_end_dtmonth=' . GETPOST('search_end_dtmonth', 'int') . '&search_end_dtday=' . GETPOST('search_end_dtday', 'int') . '&search_end_dtyear=' . GETPOST('search_end_dtyear', 'int');
if (GETPOST("req_nb")) $param.='&amp;req_nb='.urlencode(GETPOST("req_nb"));
if (GETPOST("thirdparty")) $param.='&amp;thirdparty='.urlencode(GETPOST("thirdparty"));


/*
 * Actions
 */       

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$description="";
	$type="";
	$debit="";
	$credit="";
	$account="";
	$bid="";
	$search_req_nb='';
	$search_thirdparty='';
	$thirdparty='';
	$req_userid="";
}


/*
 * View
 */

$companystatic=new Societe($db);
$bankaccountstatic=new Account($db);

llxHeader('', $langs->trans("BankTransactions"), '', '', 0, 0, array(), array(), $param);

$form = new Form($db);
$formother = new FormOther($db);

if ($vline) $viewline = $vline;
else $viewline = 50;

//$sql = "SELECT fac.fk_user_author as id_user_fac, fac_fourn.fk_user_author as id_user_fac_fourn, b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq,";
// Finalement, on veut filtrer sur le user qui a enregistrer le règlement
$sql = "SELECT b.fk_user_author as id_user_reglement, b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq,";
$sql.= " b.fk_account, b.fk_type,";
$sql.= " ba.rowid as bankid, ba.ref as bankref,";
$sql.= " bu.label as labelurl, bu.url_id,";
$sql.= " s.nom, s.name_alias, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
$sql.= " FROM ";
if ($bid) $sql.= MAIN_DB_PREFIX."bank_class as l,";
$sql.= " ".MAIN_DB_PREFIX."bank_account as ba,";
$sql.= " ".MAIN_DB_PREFIX."bank as b";


/*
// Jointures sur table facture client
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement pay ON (b.rowid = pay.fk_bank)';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture pay_f ON (pay.rowid = pay_f.fk_paiement)';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture fac ON (pay_f.fk_facture = fac.rowid)';

// Jointures sur factures fournisseurs
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn pay_fourn ON (b.rowid = pay_fourn.fk_bank)';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn pay_fourn_f ON (pay_fourn.rowid = pay_fourn_f.fk_paiementfourn)';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn fac_fourn ON (pay_fourn_f.fk_facturefourn = fac_fourn.rowid)';
*/

$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND bu.type = 'company'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
$sql.= " WHERE b.fk_account = ba.rowid";
$sql.= " AND ba.entity IN (".getEntity('bank_account', 1).")";
if ($search_req_nb)
{
    $sql.= " AND b.num_chq LIKE '%".$db->escape($search_req_nb)."%'";
}
if ($search_thirdparty)
{
    $sql.=" AND s.nom LIKE '%".$db->escape($search_thirdparty)."%'";
}
if ($bid)
{
	$sql.= " AND b.rowid=l.lineid AND l.fk_categ=".$bid;
}
if (! empty($type))
{
	$sql.= " AND b.fk_type = '".$db->escape($type)."' ";
}
// Search period criteria
if (dol_strlen($search_dt_start)>0) {
	$sql .= " AND b.dateo >= '" . $db->idate($search_dt_start) . "'";
}
if (dol_strlen($search_dt_end)>0) {
	$sql .= " AND b.dateo <= '" . $db->idate($search_dt_end) . "'";
}

if($req_userid > 0) $sql.= " AND (b.fk_user_author = ".$req_userid.")";

// Search criteria amount
$debit = price2num(str_replace('-','',$debit));
$credit = price2num(str_replace('-','',$credit));
if ($debit) $sql.= natural_search('- b.amount', $debit, 1);
if ($credit) $sql.= natural_search('b.amount', $credit, 1);
$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1,$offset);

dol_syslog('compta/bank/search.php::', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$var=True;
	$num = $db->num_rows($resql);
	$i = 0;

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="search_form">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	print '<input type="hidden" name="action" value="search">';
	if (! empty($_REQUEST['bid'])) print '<input type="hidden" name="bid" value="'.$_REQUEST["bid"].'">';
	
	// Title
	$bankcateg=new BankCateg($db);
	if (GETPOST("bid"))
	{
		$result=$bankcateg->fetch(GETPOST("bid"));
		print_barre_liste($langs->trans("BankTransactionForCategory",$bankcateg->label).' '.($socid?' '.$soc->name:''), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_bank.png', 0, '', '', $limit);
	}
	else
	{
		print_barre_liste($langs->trans("BankTransactions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_bank.png', 0, '', '', $limit);
	}
	
	$moreforfilter = '';
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('Period') . ' ('.$langs->trans('DateOperationShort').') : ' . $langs->trans('DateStart') . ' ';
	$moreforfilter .= $form->select_date($search_dt_start, 'search_start_dt', 0, 0, 1, "search_form", 1, 0, 1);
	$moreforfilter .= ' - ';
	$moreforfilter .= $langs->trans('DateEnd') . ' ' . $form->select_date($search_dt_end, 'search_end_dt', 0, 0, 1, "search_form", 1, 0, 1);
	$moreforfilter .= '</div>';

	if ($moreforfilter) 
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>'."\n";
	}

	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'b.rowid','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('DateOperationShort'),$_SERVER['PHP_SELF'],'b.dateo','',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Value'),$_SERVER['PHP_SELF'],'b.datev','',$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Type"),$_SERVER['PHP_SELF'],'','',$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("User"));
    print_liste_field_titre($langs->trans("Numero"),$_SERVER['PHP_SELF'],'b.num_releve','',$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Description"),$_SERVER['PHP_SELF'],'','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER['PHP_SELF'],'bu.label','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Debit"),$_SERVER['PHP_SELF'],'b.amount','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Credit"),$_SERVER['PHP_SELF'],'b.amount','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Account"),$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="center">';
    $form->select_types_paiements(empty($type)?'':$type, 'type', '', 2, 0, 1);
	print '</td>';
	print '<td>';
	$form->select_users($req_userid, 'userid', 1);
	print '</td>';
    // Numero
    print '<td class="liste_titre" align="center"><input type="text" class="flat" name="req_nb" value="'.dol_escape_htmltag($search_req_nb).'" size="2"></td>';
    print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="description" size="10" value="'.dol_escape_htmltag($description).'">';
	print '</td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="thirdparty" value="'.dol_escape_htmltag($search_thirdparty).'" size="10"></td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="text" class="flat" name="debit" size="4" value="'.dol_escape_htmltag($debit).'">';
	print '</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="text" class="flat" name="credit" size="4" value="'.dol_escape_htmltag($credit).'">';
	print '</td>';
	print '<td></td>';
    print '<td class="liste_titre" align="right">';
    $searchpitco=$form->showFilterAndCheckAddButtons(0);
    print $searchpitco;
    print '</td>';
	print "</tr>\n";

    // Loop on each record
    $total_debit=0;
    $total_credit=0;
	$u = new User($db);
	
    while ($i < min($num,$limit)) {
        $objp = $db->fetch_object($resql);
        $printline=false;
        //Search Description
        if ($description) {
            preg_match('/\((.+)\)/i',$objp->label,$reg); // Si texte entoure de parenthese on tente recherche de traduction
            if ($reg[1]) {
                if ($langs->transnoentities($reg[1])==$description) {
                    $printline=true;
                }
            } elseif ($objp->label==$description) {
                $printline=true;
            }
        } else {
            $printline=true;
        }

        if ($printline) {
            $var=!$var;

            print "<tr ".$bc[$var].">";

            // Ref
            print '<td align="left" class="nowrap">';
            print "<a href=\"ligne.php?rowid=".$objp->rowid.'">'.img_object($langs->trans("ShowPayment").': '.$objp->rowid, 'payment', 'class="classfortooltip"').' '.$objp->rowid."</a> &nbsp; ";
            print '</td>';

            // Date ope
            print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($objp->do),"day")."</td>\n";

	        // Date value
	        print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($objp->dv),"day")."</td>\n";

	        // Payment type
	        print '<td align="center" class="nowrap">';
	        $labeltype=($langs->trans("PaymentTypeShort".$objp->fk_type)!="PaymentTypeShort".$objp->fk_type)?$langs->trans("PaymentTypeShort".$objp->fk_type):$langs->getLabelFromKey($db,$objp->fk_type,'c_paiement','code','libelle');
	        if ($labeltype == 'SOLD') print '&nbsp;'; //$langs->trans("InitialBankBalance");
	        else print $labeltype;
	        print "</td>\n";

			print '<td>';
			if(!empty($objp->id_user_reglement)) {
				$u->fetch($objp->id_user_reglement);
				if($u->id <= 0) $u->fetch($objp->id_user_reglement);
				print $u->getNomUrl(1);
			}
			print '</td>';

	        // Num
	        print '<td class="nowrap" align="center">'.($objp->num_chq?$objp->num_chq:"")."</td>\n";

	        // Description
			print "<td>";

			print "<a href=\"ligne.php?rowid=".$objp->rowid."&amp;account=".$objp->fk_account."\">";
			$reg=array();
			preg_match('/\((.+)\)/i',$objp->label,$reg);	// Si texte entoure de parenthee on tente recherche de traduction
			if ($reg[1] && $langs->trans($reg[1])!=$reg[1]) print $langs->trans($reg[1]);
			else print dol_trunc($objp->label,40);
			print "</a>&nbsp;";

			print '</td>';

			// Third party
			print "<td>";
			if ($objp->url_id)
			{
				$companystatic->id=$objp->url_id;
				$companystatic->name=$objp->nom;
				$companystatic->name_alias=$objp->name_alias;
				$companystatic->client=$objp->client;
				$companystatic->fournisseur=$objp->fournisseur;
				$companystatic->code_client=$objp->code_client;
				$companystatic->code_fournisseur=$objp->code_fournisseur;
				print $companystatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';

			// Debit/Credit
			if ($objp->amount < 0)
			{
				print '<td align="right">'.price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
				$total_debit+=$objp->amount;
			}
			else
			{
				print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</td>\n";
				$total_credit+=$objp->amount;
			}

			// Bank account
			print '<td align="right" class="nowrap">';
			$bankaccountstatic->id=$objp->bankid;
			$bankaccountstatic->label=$objp->bankref;
			print $bankaccountstatic->getNomUrl(1);
			print "</td>\n";
			
			print '<td></td>';
			
			print "</tr>";
		}
		$i++;
	}
	if ($num>0) {
		print '<tr  class="liste_total">';
		print '<td>' . $langs->trans('Total') . '</td>';
		print '<td colspan="6"></td>';
		print '<td>&nbsp;</td>';
		print '<td  align="right">' . price($total_debit * - 1) . '</td>';
		print '<td  align="right">' . price($total_credit) . '</td>';
		print '<td></td>';
		print '<td></td>';
		print '</tr>';
	}

	print "</table>";
    print '</form>';
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

// If no data to display after a search
if ($_POST["action"] == "search" && ! $num)
{
	print '<div class="opacitymedium">'.$langs->trans("NoRecordFound").'</div>';
}

llxFooter();

$db->close();
