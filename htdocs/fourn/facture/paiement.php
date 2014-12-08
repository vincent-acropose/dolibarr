<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/fourn/facture/paiement.php
 *	\ingroup    fournisseur,facture
 *	\brief      Payment page for suppliers invoices
 */

require '../../main.inc.php';
require DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load('companies');
$langs->load('bills');
$langs->load('banks');

$facid=GETPOST('facid','int');
$action=GETPOST('action','alpha');
$socid=GETPOST('socid','int');

$search_categ= GETPOST('search_categ','int');

$month    = GETPOST('month','int');
$year     = GETPOST('year','int');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";

$amounts = array();

// Security check
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}




/*
 * Actions
 */
if ($action == 'add_paiement')
{
    $error = 0;

    $datepaye = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
    $paiement_id = 0;
    $total = 0;

    // Genere tableau des montants amounts
    foreach ($_POST as $key => $value)
    {
        if (substr($key,0,7) == 'amount_')
        {
            $other_facid = substr($key,7);
            $amounts[$other_facid] = price2num(GETPOST($key));
            $total = $total + $amounts[$other_facid];
        }
    }

    // Effectue les verifications des parametres
    if ($_POST['paiementid'] <= 0)
    {
    	setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentities('PaymentMode')), 'errors');
        $error++;
    }

    if (! empty($conf->banque->enabled))
    {
        // Si module bank actif, un compte est obligatoire lors de la saisie
        // d'un paiement
        if (! $_POST['accountid'])
        {
        	setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentities('AccountToCredit')), 'errors');
            $error++;
        }
    }

    if ($total == 0)
    {
    	setEventMessage($langs->trans('ErrorFieldRequired',$langs->trans('PaymentAmount')), 'errors');
        $error++;
    }

    if (empty($datepaye))
    {
    	setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentities('Date')), 'errors');
        $error++;
    }

    if (! $error)
    {
        $db->begin();

        // Creation de la ligne paiement
        $paiement = new PaiementFourn($db);
        $paiement->datepaye     = $datepaye;
        $paiement->amounts      = $amounts;   // Array of amounts
        $paiement->paiementid   = $_POST['paiementid'];
        $paiement->num_paiement = $_POST['num_paiement'];
        $paiement->note         = $_POST['comment'];
        if (! $error)
        {
            $paiement_id = $paiement->create($user,(GETPOST('closepaidinvoices')=='on'?1:0));
            if ($paiement_id < 0)
            {
            	setEventMessage($paiement->error, 'errors');
                $error++;
            }
        }

        if (! $error)
        {
            $result=$paiement->addPaymentToBank($user,'payment_supplier','(SupplierInvoicePayment)',$_POST['accountid'],'','');
            if ($result < 0)
            {
            	setEventMessage($paiement->error, 'errors');
                $error++;
            }
        }

        if (! $error)
        {
            $db->commit();

            // If payment dispatching on more than one invoice, we keep on summary page, otherwise go on invoice card
            $invoiceid=0;
            foreach ($paiement->amounts as $key => $amount)
            {
                $facid = $key;
                if (is_numeric($amount) && $amount <> 0)
                {
                    if ($invoiceid != 0) $invoiceid=-1; // There is more than one invoice payed by this payment
                    else $invoiceid=$facid;
                }
            }
            if ($invoiceid > 0) $loc = DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$invoiceid;
            else $loc = DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$paiement_id;
            header('Location: '.$loc);
            exit;
        }
        else
        {
            $db->rollback();
        }
    }
}


/*
 * View
 */

$supplierstatic=new Societe($db);
$invoicesupplierstatic = new FactureFournisseur($db);
$htmlother=new FormOther($db);

llxHeader('',$langs->trans("SuppliersInvoices"),'EN:Suppliers_Invoices|FR:FactureFournisseur|ES:Facturas_de_proveedores');

$form=new Form($db);

if ($action == 'create' || $action == 'add_paiement')
{
    $object = new FactureFournisseur($db);
    $object->fetch($facid);

    $datefacture=dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
    $dateinvoice=($datefacture==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0):$datefacture);

    $sql = 'SELECT s.nom, s.rowid as socid,';
    $sql.= ' f.rowid, f.ref, f.ref_supplier, f.amount, f.total_ttc as total';
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
    $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'facture_fourn as f';
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= ' WHERE f.fk_soc = s.rowid';
    $sql.= ' AND f.rowid = '.$facid;
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        if ($num)
        {
            $obj = $db->fetch_object($resql);
            $total = $obj->total;

            print_fiche_titre($langs->trans('DoPayment'));
            
            // Add realtime total information
            if ($conf->use_javascript_ajax)
            {
            	print "\n".'<script type="text/javascript" language="javascript">';
            	print '$(document).ready(function () {
            			setPaiementCode();
            
            			$("#selectpaiementcode").change(function() {
            				setPaiementCode();
            			});
            
            			function setPaiementCode()
            			{
            				var code = $("#selectpaiementcode option:selected").val();
            
                            if (code == \'CHQ\' || code == \'VIR\')
            				{
            					$(\'.fieldrequireddyn\').addClass(\'fieldrequired\');
            					if ($(\'#fieldchqemetteur\').val() == \'\')
            					{
            						var emetteur = ('.$object->type.' == 2) ? \''.dol_escape_htmltag(MAIN_INFO_SOCIETE_NOM).'\' : jQuery(\'#thirdpartylabel\').val();
            						$(\'#fieldchqemetteur\').val(emetteur);
            					}
            				}
            				else
            				{
            					$(\'.fieldrequireddyn\').removeClass(\'fieldrequired\');
            					$(\'#fieldchqemetteur\').val(\'\');
            				}
            			}
            
						function _elemToJson(selector)
						{
							var subJson = {};
							$.map(selector.serializeArray(), function(n,i)
							{
								subJson[n["name"]] = n["value"];
							});
							return subJson;
						}
						function callForResult(imgId)
						{
							var json = {};
							var form = $("#payment_form");
            
							json["invoice_type"] = $("#invoice_type").val();
							json["amountPayment"] = $("#amountpayment").attr("value");
							json["amounts"] = _elemToJson(form.find("input[name*=\"amount_\"]"));
							json["remains"] = _elemToJson(form.find("input[name*=\"remain_\"]"));
            
							if (imgId != null) {
								json["imgClicked"] = imgId;
							}
            
							$.post("'.DOL_URL_ROOT.'/compta/ajaxpayment.php", json, function(data)
							{
								json = $.parseJSON(data);
            
								form.data(json);
            
								for (var key in json)
								{
									if (key == "result")	{
										if (json["makeRed"]) {
											$("#"+key).addClass("error");
										} else {
											$("#"+key).removeClass("error");
										}
										json[key]=json["label"]+" "+json[key];
										$("#"+key).text(json[key]);
									} else {
										form.find("input[name*=\""+key+"\"]").each(function() {
											$(this).attr("value", json[key]);
										});
									}
								}
							});
						}
						$("#payment_form").find("input[name*=\"amount_\"]").change(function() {
							callForResult();
						});
						$("#payment_form").find("input[name*=\"amount_\"]").keyup(function() {
							callForResult();
						});
			';
            
            	// Add user helper to input amount on invoices
            	if (! empty($conf->global->MAIN_JS_ON_PAYMENT))
            	{
            		print '	$("#payment_form").find("img").click(function() {
							callForResult(jQuery(this).attr("id"));
						});
            
						$("#amountpayment").change(function() {
							callForResult();
						});';
            	}
            
            	print '	});'."\n";
            	print '	</script>'."\n";
            }

            print '<form id="payment_form" name="addpaiement" action="paiement.php" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="add_paiement">';
            print '<input type="hidden" name="facid" value="'.$facid.'">';
            print '<input type="hidden" name="ref_supplier" value="'.$obj->ref_supplier.'">';
            print '<input type="hidden" name="socid" value="'.$obj->socid.'">';
            print '<input type="hidden" name="societe" value="'.$obj->nom.'">';
            print '<input type="hidden" name="type" id="invoice_type" value="'.$object->type.'">';

            print '<table class="border" width="100%">';

            print '<tr class="liste_titre"><td colspan="3">'.$langs->trans('Payment').'</td>';
            print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2">';
            $supplierstatic->id=$obj->socid;
            $supplierstatic->name=$obj->nom;
            print $supplierstatic->getNomUrl(1,'supplier');
            print '</td></tr>';
            print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td>';
            $form->select_date($dateinvoice,'','','','',"addpaiement",1,1);
            print '</td>';
            print '<td>'.$langs->trans('Comments').'</td></tr>';
            print '<tr><td class="fieldrequired">'.$langs->trans('PaymentMode').'</td><td>';
            $form->select_types_paiements(empty($_POST['paiementid'])?'':$_POST['paiementid'],'paiementid');
            print '</td>';
            print '<td rowspan="3" valign="top">';
            print '<textarea name="comment" wrap="soft" cols="60" rows="'.ROWS_3.'">'.(empty($_POST['comment'])?'':$_POST['comment']).'</textarea></td></tr>';
            print '<tr><td>'.$langs->trans('Numero').'</td><td><input name="num_paiement" type="text" value="'.(empty($_POST['num_paiement'])?'':$_POST['num_paiement']).'"></td></tr>';
            if (! empty($conf->banque->enabled))
            {
                print '<tr><td class="fieldrequired">'.$langs->trans('Account').'</td><td>';
                $form->select_comptes(empty($_POST['accountid'])?'':$_POST['accountid'],'accountid',0,'',2);
                print '</td></tr>';
            }
            else
            {
                print '<tr><td colspan="2">&nbsp;</td></tr>';
            }
            // Payment amount
            if ($conf->use_javascript_ajax && !empty($conf->global->MAIN_JS_ON_PAYMENT))
            {
            	print '<tr><td><span class="fieldrequired">'.$langs->trans('AmountPayment').'</span></td>';
            	print '<td>';
            	if ($action == 'add_paiement')
            	{
            		print '<input id="amountpayment" name="amountpaymenthidden" size="8" type="text" value="'.(empty($_POST['amountpayment'])?'':$_POST['amountpayment']).'" disabled="disabled">';
            		print '<input name="amountpayment" type="hidden" value="'.(empty($_POST['amountpayment'])?'':$_POST['amountpayment']).'">';
            	}
            	else
            	{
            		print '<input id="amountpayment" name="amountpayment" size="8" type="text" value="'.(empty($_POST['amountpayment'])?'':$_POST['amountpayment']).'">';
            	}
            	print '</td>';
            	print '</tr>';
            }
            
            print '</table>';


			$parameters=array('facid'=>$facid, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
			$reshook=$hookmanager->executeHooks('paymentsupplierinvoices',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
			$error=$hookmanager->error; $errors=$hookmanager->errors;
			if (empty($reshook))
			{
				/*
	             * Autres factures impayees
	             */
	            $sql = 'SELECT f.rowid as facid, f.ref, f.ref_supplier, f.total_ht, f.total_ttc, f.datef as df';
	            $sql.= ', SUM(pf.amount) as am';
	            $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
	            $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid';
	            $sql.= " WHERE f.entity = ".$conf->entity;
	            $sql.= ' AND f.fk_soc = '.$object->socid;
	            $sql.= ' AND f.paye = 0';
	            $sql.= ' AND f.fk_statut = 1';  // Statut=0 => non validee, Statut=2 => annulee
	            $sql.= ' GROUP BY f.rowid, f.ref, f.ref_supplier, f.total_ht, f.total_ttc, f.datef';
	            $resql = $db->query($sql);
	            if ($resql)
	            {
	                $num = $db->num_rows($resql);
	                if ($num > 0)
	                {
	                    $i = 0;
	                    print '<br>';

	                    print $langs->trans('Invoices').'<br>';
	                    print '<table class="noborder" width="100%">';
	                    print '<tr class="liste_titre">';
	                    print '<td>'.$langs->trans('Ref').'</td>';
	                    print '<td>'.$langs->trans('RefSupplier').'</td>';
	                    print '<td align="center">'.$langs->trans('Date').'</td>';
	                    print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
	                    print '<td align="right">'.$langs->trans('AlreadyPaid').'</td>';
	                    print '<td align="right">'.$langs->trans('RemainderToPay').'</td>';
	                    print '<td align="center">'.$langs->trans('Amount').'</td>';
	                    print '</tr>';

	                    $var=True;
	                    $total=0;
	                    $total_ttc=0;
	                    $totalrecu=0;
	                    while ($i < $num)
	                    {
	                        $objp = $db->fetch_object($resql);
	                        $var=!$var;
	                        print '<tr '.$bc[$var].'>';
	                        print '<td><a href="fiche.php?facid='.$objp->facid.'">'.img_object($langs->trans('ShowBill'),'bill').' '.$objp->ref;
	                        print '</a></td>';
	                        print '<td>'.$objp->ref_supplier.'</td>';
	                        if ($objp->df > 0 )
	                        {
	                            print '<td align="center">';
	                            print dol_print_date($db->jdate($objp->df)).'</td>';
	                        }
	                        else
	                        {
	                            print '<td align="center"><b>!!!</b></td>';
	                        }
	                        print '<td align="right">'.price($objp->total_ttc).'</td>';
	                        print '<td align="right">'.price($objp->am).'</td>';
	                        $remaintopay=$objp->total_ttc - $objp->am;
	                        print '<td align="right">'.price($remaintopay).'</td>';
	                        print '<td align="center">';
	                      
	                        $namef = 'amount_'.$objp->facid;
	                        $nameRemain = 'remain_'.$objp->facid;
	                        if ($conf->use_javascript_ajax && !empty($conf->global->MAIN_JS_ON_PAYMENT))
	                        {
	                        	print img_picto($langs->trans('AddRemind'),'rightarrow.png','id="'.$objp->facid.'"');
	                        	print '<input type=hidden name="'.$nameRemain.'" value="'.$remaintopay.'">';
	                        }

	                        
	                        print '<input type="text" size="8" name="'.$namef.'" value="'.GETPOST($namef).'">';
	                        print "</td></tr>\n";
	                        $total+=$objp->total_ht;
	                        $total_ttc+=$objp->total_ttc;
	                        $totalrecu+=$objp->am;
	                        $i++;
	                    }
	                    if ($i > 1)
	                    {
	                        // Print total
	                        print '<tr class="liste_total">';
	                        print '<td colspan="3" align="left">'.$langs->trans('TotalTTC').':</td>';
	                        print '<td align="right"><b>'.price($total_ttc).'</b></td>';
	                        print '<td align="right"><b>'.price($totalrecu).'</b></td>';
	                        print '<td align="right"><b>'.price($total_ttc - $totalrecu).'</b></td>';
	                        print '<td align="center" id="result" style="font-weight: bold;"></td>';
                   			
	                        print "</tr>\n";
	                    }
	                    print "</table>\n";
	                }
	                $db->free($resql);
	            }
	            else
	           {
	                dol_print_error($db);
	            }
			}

			//			print '<tr><td colspan="3" align="center">';
			print '<center><br><input type="checkbox" checked="checked" name="closepaidinvoices"> '.$langs->trans("ClosePaidInvoicesAutomatically");
			print '<br><input type="submit" class="button" value="'.$langs->trans('Save').'"></center>';
			//			print '</td></tr>';

            print '</form>';
        }
    }
}

/*
 * Show list
 */
if (empty($action))
{
    if ($page == -1) $page = 0 ;
    $limit = $conf->liste_limit;
    $offset = $limit * $page ;

    if (! $sortorder) $sortorder='DESC';
    if (! $sortfield) $sortfield='p.datep';

    $search_ref=GETPOST('search_ref');
    $search_account=GETPOST('search_account');
    if ($search_account == -1) {$search_account=0;}
    $search_paymenttype=GETPOST('search_paymenttype');
    $search_amount=GETPOST('search_amount');
    $search_company=GETPOST('search_company');

    $sql = 'SELECT p.rowid as pid, p.datep as dp, p.amount as pamount, p.num_paiement,';
    $sql.= ' s.rowid as socid, s.nom,';
    $sql.= ' c.libelle as paiement_type,';
    $sql.= ' ba.rowid as bid, ba.label,';
    if (!$user->rights->societe->client->voir) $sql .= ' sc.fk_soc, sc.fk_user,';
    $sql.= ' SUM(f.amount)';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn AS p';
    if (!$user->rights->societe->client->voir) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn AS pf ON p.rowid=pf.fk_paiementfourn';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn AS f ON f.rowid=pf.fk_facturefourn';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement AS c ON p.fk_paiement = c.id';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = f.fk_soc';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
    if (! empty($search_categ)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_fournisseur as cf ON f.fk_soc = cf.fk_societe";
    $sql.= " WHERE f.entity = ".$conf->entity;
    if (!$user->rights->societe->client->voir) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid)
    {
        $sql .= ' AND f.fk_soc = '.$socid;
    }
    // Search criteria
    if (! empty($search_ref))
    {
        $sql .= ' AND p.rowid='.$db->escape($search_ref);
    }
    if (! empty($search_account))
    {
        $sql .= ' AND b.fk_account='.$db->escape($search_account);
    }
    if (! empty($search_paymenttype))
    {
        $sql .= " AND c.code='".$db->escape($search_paymenttype)."'";
    }
    if (! empty($search_amount))
    {
        $sql .= " AND p.amount=".price2num($search_amount);
    }
    if (! empty($search_company))
    {
        $sql .= " AND s.nom LIKE '%".$db->escape($search_company)."%'";
    }
    if ($search_categ > 0)   $sql.= " AND cf.fk_categorie = ".$search_categ;
    if ($search_categ == -2) $sql.= " AND cf.fk_categorie IS NULL";
    
    if ($month > 0)
    {
    	if ($year > 0)
    		$sql.= " AND MONTH(p.datep) IN (".$month.") AND YEAR(p.datep)=".$year;
    	else
    		$sql.= " AND date_format(p.datep, '%m') = '$month'";
    }
    else if ($year > 0)
    {
    	$sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
    }
    
    $sql.= " GROUP BY p.rowid, p.datep, p.amount, p.num_paiement, s.rowid, s.nom, c.libelle, ba.rowid, ba.label";
    if (!$user->rights->societe->client->voir) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql.= $db->order($sortfield,$sortorder);
    $sql.= $db->plimit($limit+1, $offset);

    dol_syslog("fourn/facture/paiement.php::list sql=".$sql, LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var=True;

        $paramlist='';
        $paramlist.=(! empty($search_ref)?"&search_ref=".$search_ref:"");
        $paramlist.=(! empty($search_company)?"&search_company=".$search_company:"");
        $paramlist.=(! empty($search_amount)?"&search_amount=".$search_amount:"");
        if (!empty($search_categ))			$param.='&amp;search_categ='.urlencode($search_categ);
        if ($month) $param.='&amp;month='.urlencode($month);
        if ($year)  $param.='&amp;year=' .urlencode($year);

        print_barre_liste($langs->trans('SupplierPayments'), $page, 'paiement.php',$paramlist,$sortfield,$sortorder,'',$num);

        if ($mesg) dol_htmloutput_mesg($mesg);
        if ($errmsg) dol_htmloutput_errors($errmsg);

        print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
        
        // Filter on categories
        $moreforfilter='';
        if (! empty($conf->categorie->enabled))
        {
        	$moreforfilter.=$langs->trans('Categories'). ': ';
        	$moreforfilter.=$htmlother->select_categories(1,$search_categ,'search_categ',1);
        	$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
        }
        if ($moreforfilter)
        {
        	print '<div class="liste_titre">';
        	print $moreforfilter;
        	print '</div>';
        }
        
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans('RefPayment'),'paiement.php','p.rowid','',$paramlist,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Date'),'paiement.php','dp','',$paramlist,'align="center"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('ThirdParty'),'paiement.php','s.nom','',$paramlist,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Type'),'paiement.php','c.libelle','',$paramlist,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Account'),'paiement.php','ba.label','',$paramlist,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Amount'),'paiement.php','f.amount','',$paramlist,'align="right"',$sortfield,$sortorder);
        //print_liste_field_titre($langs->trans('Invoice'),'paiement.php','ref_supplier','',$paramlist,'',$sortfield,$sortorder);
        print "</tr>\n";

        // Lines for filters fields
        print '<tr class="liste_titre">';
        print '<td align="left">';
        print '<input class="flat" type="text" size="4" name="search_ref" value="'.$search_ref.'">';
        print '</td>';
        print '<td class="flat" align="center">';
       	print '<input class="flat" type="text" size="1" name="month" value="'.$month.'">';
		//print '&nbsp;'.$langs->trans('Year').': ';
		$syear = $year;
		//if ($syear == '') $syear = date("Y");
		$htmlother->select_year($syear?$syear:-1,'year',1, 20, 5);
		print '</td>';
        print '<td align="left">';
        print '<input class="fat" type="text" size="6" name="search_company" value="'.$search_company.'">';
        print '</td>';
        print '<td>';
        $form->select_types_paiements($search_paymenttype,'search_paymenttype','',2,1,1);
        print '</td>';
        print '<td>';
        $form->select_comptes($search_account,'search_account',0,'',1);
        print '</td>';
        print '<td align="right">';
        print '<input class="fat" type="text" size="4" name="search_amount" value="'.$search_amount.'">';
        print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
        print '</td>';
        print "</tr>\n";

        $total_amount=0;
        while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;
            print '<tr '.$bc[$var].'>';

            // Ref payment
            print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$objp->pid.'">'.img_object($langs->trans('ShowPayment'),'payment').' '.$objp->pid.'</a></td>';

            // Date
            print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";

            print '<td>';
            if ($objp->socid) print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$objp->socid.'">'.img_object($langs->trans('ShowCompany'),'company').' '.dol_trunc($objp->nom,90).'</a>';
            else print '&nbsp;';
            print '</td>';

            print '<td>'.dol_trunc($objp->paiement_type.' '.$objp->num_paiement,32)."</td>\n";

            print '<td>';
            if ($objp->bid) print '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.dol_trunc($objp->label,24).'</a>';
            else print '&nbsp;';
            print '</td>';

            print '<td align="right">'.price($objp->pamount).'</td>';

            // Ref invoice
            /*$invoicesupplierstatic->ref=$objp->ref_supplier;
            $invoicesupplierstatic->id=$objp->facid;
            print '<td class="nowrap">';
            print $invoicesupplierstatic->getNomUrl(1);
            print '</td>';*/

            print '</tr>';
            $i++;
            $total_amount+=$objp->pamount;
        }
        
        
        print '<tr class="liste_total">';
        print '<td colspan="5" align="left">'.$langs->trans("Total").'</td>';
        print '<td align="right"><b>'.price($total_amount).'</b></td>';
        print "</tr>\n";
        
        print "</table>";
        print "</form>\n";
    }
    else
    {
        dol_print_error($db);
    }
}

$db->close();

llxFooter();
?>
