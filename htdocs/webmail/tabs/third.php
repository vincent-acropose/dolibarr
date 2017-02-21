<?php
/* Copyright (C) 2014-2015	   Juanjo Menent		<jmenent@2byte.es>
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
 *   	\file       webmail/tabs/third.php
 *		\ingroup    webmail
 */

$res=@include("../../main.inc.php");								// For root directory
if (! $res) $res=@include("../../../main.inc.php");					// For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
dol_include_once("/webmail/class/webmail.class.php");
dol_include_once("/webmail/class/userconfig.class.php");
dol_include_once("/webmail/lib/webmail.lib.php");
dol_include_once("/webmail/class/message.class.php");

// Security check
$socid = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');

$object = new Societe($db);
if ($socid > 0) $object->fetch($socid);

$sremitente=GETPOST('sremitente','alpha');
$srecibe=GETPOST('srecibe','alpha');
$sasunto=GETPOST('sasunto','alpha');
$date_start = dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);	// Date for local PHP server
$date_end = dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
$viewstatut=GETPOST('mail_statut');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='datetime';
if (! $sortorder) $sortorder='DESC';
$limit = $conf->liste_limit;

$action=GETPOST('action');


/*
 * Actions
 */
// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
    $sremitente='';
    $srecibe='';
	$sasunto='';
	$date_start='';
	$date_end='';
	$viewstatut=-1;
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$productstatic=new Product($db);

$thirdpartystatic = new Societe($db);
$contactstatic=new Contact($db);
$mailstatic = new Message($db);
$userstatic=new User($db);

$titre = $langs->trans("WebMail",$object->name);
llxHeader('',$titre,'');

if (empty($socid))
{
	dol_print_error($db);
	exit;
}

$head = societe_prepare_head($object);
dol_fiche_head($head, 'mail', $langs->trans("ThirdParty"),0,'company');

print '<table class="border" width="100%">';
print '<tr><td width="25%">'.$langs->trans('ThirdPartyName').'</td>';
print '<td colspan="3">';
print $form->showrefnav($object,'id','',($user->societe_id?0:1),'rowid','nom');
print '</td></tr>';

if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
{
	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

if ($object->client)
{
	print '<tr><td>';
	print $langs->trans('CustomerCode').'</td><td colspan="3">';
	print $object->code_client;
	if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
	print '</td></tr>';
}

if ($object->fournisseur)
{
	print '<tr><td>';
	print $langs->trans('SupplierCode').'</td><td colspan="3">';
	print $object->code_fournisseur;
	if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
	print '</td></tr>';
}
print '</table>';

dol_fiche_end();
print '<br>';

$sql = "SELECT rowid, `from` as envia, `to` as recibe, `subject` as asunto, datetime as datec, state_new as leido, state_reply, state_spam, files, is_outbox";
$sql.= " FROM ".MAIN_DB_PREFIX."webmail_mail";

$sql.= " WHERE 1=1 AND (`from` LIKE '%".($object->email?$object->email:'##@!')."%' OR `to` LIKE '%".($object->email?$object->email:'##@!')."%' OR fk_soc=".$socid.")";

//$sql.= " WHERE fk_soc=".$socid;

$sql.= " AND fk_user IN ".getusersmail()." ";
$sql.= " AND entity=".$conf->entity;


if ($sremitente) 
{
	$sql .= natural_search('`from`', $sremitente);
}
if ($srecibe) 
{
	$sql .= natural_search('`to`', $srecibe);
}
if ($sasunto)
{
	$sql .= natural_search('`subject`', $sasunto);
}
if ($viewstatut <> '' && $viewstatut>=0 && $viewstatut<=1)
{
	$sql.= ' AND state_new IN ('.$viewstatut.')';
}

if ($viewstatut <> '' && $viewstatut==2)
{
	$sql.= ' AND state_reply = 1';
}

if ($viewstatut <> '' && $viewstatut==3)
{
	$sql.= ' AND state_spam = 1';
}

//Date filter
if ($date_start && $date_end) $sql.= " AND datetime >= '".$db->idate($date_start)."' AND datetime <= '".$db->idate($date_end)."'";

$sql.= ' ORDER BY `'.$sortfield.'` '.$sortorder;
$sql.= $db->plimit($limit + 1,$offset);

$resql = $db->query($sql);

if ($resql)
{
	$title = $langs->trans('WebMail');
	
	if (empty($viewstatut))
	{
		$viewstatut=-1;
	}
	elseif ($viewstatut == 0)
	{
		$title.=' - '.$langs->trans('StatusNotRead');
	}
	elseif ($viewstatut == 1)
	{
		$title.=' - '.$langs->trans('StatusRead');
	}

	$param='';
	if ($viewstatut) 	$param.='&viewstatut='.$viewstatut;
	if ($sremitente)	$param.='&sremitente='.$sremitente;
	if ($srecibe)		$param.='&srecibe='.$srecibe;
	if ($sasunto)		$param.='&sasunto='.$sasunto;
	if ($socid)			$param.='&id='.$socid;
	
	$num = $db->num_rows($resql);
	print_barre_liste($title, $page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num);
	$i = 0;
	
	$period=$form->select_date($date_start,'date_start',0,0,1,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,1,'',1,0,1);

	// Lignes des champs de filtre
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
	print '<input type="hidden" name="id" value="'.$socid.'">';

	print '<table class="noborder" width="100%">';

 	
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('MailSender'),$_SERVER["PHP_SELF"],'from','',$param,'width="20%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('MailReceiver'),$_SERVER["PHP_SELF"],'to','',$param,'width="20%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('MailTopic'),$_SERVER["PHP_SELF"],'subject','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('MailDate'),$_SERVER["PHP_SELF"],'datetime','',$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Attachments'),$_SERVER["PHP_SELF"],'files','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('MailStatus'),$_SERVER["PHP_SELF"],'state_new','',$param,'align="right"',$sortfield,$sortorder);
	print '<td class="liste_titre" colspan="1">&nbsp;</td>';
	
	print '</tr>';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="sremitente" value="'.$sremitente.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="srecibe" value="'.$srecibe.'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="25" name="sasunto" value="'.$sasunto.'">';
	print '</td>';
	print '<td class="liste_titre" colspan="1" align="center">';
	print $period;
	print '</td>';
	print '<td class="liste_titre" colspan="1">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	select_mail_statut($viewstatut);
	print '</td>';
	
	
	print '<td align="right" class="liste_titre">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td></tr>';

	$var=true;

	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print '<tr '.$bc[$var].'>';		
		
		//Remitente
		print '<td class="nobordernopadding nowrap">';
		
		$typemail = search_sender($objp->envia);
		
		if(is_array($typemail))
		{	
			switch ($typemail['type']) 
			{
    			case "Third":
    				$thirdpartystatic->id=$typemail['id'];
       				$thirdpartystatic->name=$typemail['name'];
      		  		print $thirdpartystatic->getNomUrl(1);
    				break;
    			case "Contact":
    				$contactstatic->lastname=$typemail['lastname'];
					$contactstatic->firstname=$typemail['firstname'];
					$contactstatic->id=$typemail['id'];
					print $contactstatic->getNomUrl(1);
    				break;
				case "User":				
					$userstatic->id=$typemail['id'];
					$userstatic->lastname=$typemail['lastname'];
					$userstatic->firstname=$typemail['firstname'];
					print $userstatic->getNomUrl(1);
				break;
    			default:
    				print "";
			}
		}
		else
		{
			print dol_htmlentitiesbr($objp->envia);
		}
		print '</td>';
		
		// Recibe
		print '<td class="nobordernopadding nowrap">';
		
		$typemail = search_sender($objp->recibe);
		
		if(is_array($typemail))
		{	
			switch ($typemail['type']) 
			{
    			case "Third":
    				$thirdpartystatic->id=$typemail['id'];
       				$thirdpartystatic->name=$typemail['name'];
      		  		print $thirdpartystatic->getNomUrl(1);
    				break;
    			case "Contact":
    				$contactstatic->lastname=$typemail['lastname'];
					$contactstatic->firstname=$typemail['firstname'];
					$contactstatic->id=$typemail['id'];
					print $contactstatic->getNomUrl(1);
    				break;
				case "User":				
					$userstatic->id=$typemail['id'];
					$userstatic->lastname=$typemail['lastname'];
					$userstatic->firstname=$typemail['firstname'];
					print $userstatic->getNomUrl(1);
				break;
    			default:
    				print "";
			}
		}
		else
		{
			print dol_htmlentitiesbr($objp->recibe);
		}
		print '</td>';

		// Asunto
		
		$mailstatic->id = $objp->rowid;
		$mailstatic->subject=$objp->asunto;
		
		print '<td>'.$mailstatic->getNomUrl(1).'</td>';

		// Date mail
		print '<td align="center">';
		print dol_print_date($db->jdate($objp->datec),'dayhourtext')."</td>\n";

		//Files
		if ($objp->files)
		{	
			print '<td align="right">'.$objp->files.'</td>';
		}
		else 
		{
			print '<td>&nbsp;</td>';
		}
		// Estado
		
		$status=$objp->leido;
		
		if ($objp->state_spam)
			$status=3;
		elseif($objp->state_reply)
			$status=2;
		
		if ($objp->is_outbox)
			$status=4;
		
		print '<td class="nowrap">'.LibStatut($status).'</td>';
		print '<td>&nbsp;</td>';

		print '</tr>';

		
		$i++;
	}

	

	print '</table>';

	print '</form>';

	$db->free($resql);
}
else
{
	print dol_print_error($db);
}

llxFooter();

$db->close();
?>