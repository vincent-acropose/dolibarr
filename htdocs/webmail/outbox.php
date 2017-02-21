<?php
/* Copyright (C) 2014      Juanjo Menent          <jmenent@2byte.es>
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
 *   	\file       webmail/outbox.php
 *		\ingroup    webmail
 */


$res=@include("../main.inc.php");								// For root directory
if (! $res) $res=@include("../../main.inc.php");                // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
dol_include_once("/webmail/class/webmail.class.php");
dol_include_once("/webmail/class/userconfig.class.php");
dol_include_once("/webmail/lib/webmail.lib.php");
dol_include_once("/webmail/class/message.class.php");


$langs->load('webmail@webmail');

$sremitente=GETPOST('sremitente','alpha');
$sasunto=GETPOST('sasunto','alpha');
$date_start = dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);	// Date for local PHP server
$date_end = dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
$viewstatut=GETPOST('mail_statut');

// Security check
$result = restrictedArea($user, 'webmail', '','');

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

$parameters=array('socid'=>$socid);

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
    $sremitente='';
	$sasunto='';
	$date_start='';
	$date_end='';
	$viewstatut=-1;
}

if ($action=='GetNewMessages')
{
	$pop3=new POP3($db);
	$userpop= new Userconfig($db,TRUE);
	
	if($pop3->connect($conf->global->WEBMAIL_POP3_SERVER,$conf->global->WEBMAIL_POP3_PORT))
	{
		$userpop->fetch_from_user($user->id);
		
    	if($pop3->login($userpop->login,$userpop->password))
    	{
    		$newmail = $pop3->get_new_messages();
    	}
    	else
    	{
        	setEventMessage($pop3->error, 'errors');
    	}
	}
	else
	{
    	setEventMessage($pop3->error, 'errors');
	}
    
	if(! $newmail)
	{
    	setEventMessage($langs->trans("NoNewsMails"));
	}
	else
	{
		setEventMessage($langs->trans("NewsMails",$newmail));
	}
	
	$pop3->close();
}


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

$thirdpartystatic = new Societe($db);
$contactstatic=new Contact($db);
$mailstatic = new Message($db);
$userstatic=new User($db);

$help_url="EN:Module_WebMail|FR:Module_WebMail|ES:Módulo_WebMail";
llxHeader('',$langs->trans("WebMail"),$help_url);

$sql = "SELECT rowid, `to` as envia, `subject` as asunto, datetime as datec, state_new as leido, state_reply, is_outbox, state_spam, files";
$sql.= " FROM ".MAIN_DB_PREFIX."webmail_mail";

$sql.= " WHERE fk_user IN ".getusersmail()." AND is_outbox=1";
$sql.= " AND entity=".$conf->entity;


if ($sremitente) 
{
	$sql .= natural_search('`from`', $sremitente);
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
	if ($socid)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('OutBox') . ' - '.$soc->nom;
	}
	else
	{
		$title = $langs->trans('OutBox');
	}
	
	
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
	if ($viewstatut) 	  $param.='&viewstatut='.$viewstatut;
	if ($sremitente)      $param.='&sremitente='.$sremitente;
	if ($sasunto)         $param.='&sasunto='.$sasunto;
	
	$num = $db->num_rows($resql);
	print_barre_liste($title, $page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num);
	$i = 0;
	
	$period=$form->select_date($date_start,'date_start',0,0,1,'',1,0,1).' - '.$form->select_date($date_end,'date_end',0,0,1,'',1,0,1);

	// Lignes des champs de filtre
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

	print '<table class="noborder" width="100%">';

 	
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('MailReceiver'),$_SERVER["PHP_SELF"],'from','',$param,'width="25%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('MailTopic'),$_SERVER["PHP_SELF"],'subject','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('MailDate'),$_SERVER["PHP_SELF"],'datetime','',$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Attachments'),$_SERVER["PHP_SELF"],'files','',$param,'align="right"',$sortfield,$sortorder);
	//print_liste_field_titre($langs->trans('MailStatus'),$_SERVER["PHP_SELF"],'state_new','',$param,'align="right"',$sortfield,$sortorder);
	print '<td class="liste_titre" colspan="1">&nbsp;</td>';
	
	print '</tr>';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="sremitente" value="'.$sremitente.'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="25" name="sasunto" value="'.$sasunto.'">';
	print '</td>';
	print '<td class="liste_titre" colspan="1" align="center">';
	print $period;
	print '</td>';
	print '<td class="liste_titre" colspan="1">&nbsp;</td>';
	//print '<td class="liste_titre" align="right">';
	//select_mail_statut($viewstatut);
	//print '</td>';
	
	
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
			$initpos =strpos($objp->envia, "<");
			$name = substr ($objp->envia , 0, $initpos);
			print dol_trunc($name,40);
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
		/*
		$status=$objp->leido;
		
		if ($objp->state_spam)
			$status=3;
		elseif($objp->state_reply)
			$status=2;
		
		if ($objp->is_outbox)
			$status=4;
		
		print '<td class="nowrap">'.LibStatut($status).'</td>';
		*/
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