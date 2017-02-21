<?php
/* Copyright (C) 2014 Juanjo Menent        <jmenent@2byte.es>
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
 *   	\file       webmail/message.php
 *		\ingroup    webmail
 */

if (! defined('NOSTYLECHECK')) define('NOSTYLECHECK','1');

$res=@include("../main.inc.php");								// For root directory
if (! $res) $res=@include("../../main.inc.php");                // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
dol_include_once('/webmail/class/message.class.php');
dol_include_once('/webmail/lib/webmail.lib.php');
dol_include_once('/webmail/lib/message.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

if (!class_exists('FormMail')) 
{
	dol_include_once('/webmail/class/html.formmail.class.php');
}
// Load traductions files requiredby by page
$langs->load("webmail@webmail");
$langs->load("mails");

// Get parameters
$id			= GETPOST('id','int');
$action		= ($_POST['action']?$_POST['action']:($_GET['action']?$_GET['action']:'')); //  =GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$sendtocc	= GETPOST('sendtoccc','alpha');

if ($_POST['sendmail']) $action="send";

// Protection if external user
if ($user->societe_id > 0)
{
	accessforbidden();
}

$object = new Message($db);

/*******************************************************************
* ACTIONS
*
********************************************************************/

if($id)
{
	$object->fetch($id);
	if ($object->state_new && ! $object->is_outbox && $object->fk_user== $user->id)
	{
		$object->set_read();
	}
}

if ($action=="forward" || $action=="reply")
{
	
	if($action=="forward")
	{
		$typeres="FW";
		
		$sql = "SELECT rowid, file_name, file, file_size, file_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."webmail_files";
		$sql.= " WHERE fk_mail=".$id;
		
		$resql = $db->query($sql);
		if ($resql)
		{
			$i=0;
		
			$num = $db->num_rows($resql);
			if ($num)
			{
				$vardir=$conf->user->dir_output."/".$user->id;
				$upload_dir_tmp = $vardir.'/temp';
				dol_mkdir($upload_dir_tmp);
			
				while ($i < $num)
				{	
					$objp = $db->fetch_object($resql);
				
					$decoded=__getmail_getmime($id);
					if(!$decoded) setEventMessage("Email not found","error");
		
					$result=__getmail_getcid(__getmail_getnode("0",$decoded),$objp->file);
		
					if(!$result) setEventMessage("Attachment not found");
					$ext=strtolower(extension($result["cname"]));
					if(!$ext) $ext=substr($result["ctype"],strrpos($result["ctype"],"/")+1);
					$file=get_temp_file($ext);
					file_put_contents($file,$result["body"]);
					$name=$result["cname"];
					$type=$result["ctype"];
					$size=$result["csize"];
					$filedest=$upload_dir_tmp."/".$name;
					dol_move($file, $filedest);
					$i++;
				}
			}
		}
	}
	else
	{
		$object->set_reply(1);
		$typeres="RE";
		$sendto=$object->from;
	}
	
	$id=0;
	
	$action="presend";
	$topicmail=$typeres.":".$object->subject;
	
	$bodymail = $langs->trans("The")." ". dol_print_date($object->datetime,'dayhour').", ".$object->from." ".$langs->trans("write").":<br><br>".$object->body;
	
}

if($action=='addmail')
{
	if(GETPOST('socidcc'))
	{
		$typesoc=substr(GETPOST('socidcc'), 0, 1);
		$socid= substr(GETPOST('socidcc'), 1);

		//TODO: Put into fuction
		if ($typesoc=='t')
		{
			$soc=new Societe($db);
		
			$soc->fetch($socid);
			if (strlen($sendtocc)>0)
				$sendtocc.=";";
			$sendtocc.=$soc->name." <".$soc->email.">";
		}
		elseif ($typesoc=='c')
		{
			$soc=new Contact($db);
			$soc->fetch($socid);
			if (strlen($sendtocc)>0)
				$sendtocc.=";";
			$sendtocc.=$soc->firstname." ".$soc->lastname." <".$soc->email.">";
		}
		elseif ($typesoc=='u')
		{
			$soc = new User($db);
			$soc->fetch($socid);
			if (strlen($sendtocc)>0)
				$sendtocc.=";";
			$sendtocc.=$soc->firstname." ".$soc->lastname." <".$soc->email.">";
		}
	}
	$action="presend";
	$id=0;
}

if($action=='setspam')
{
	$object->set_spam(1);
}

if($action=='setnospam')
{
	$object->set_spam(0);
}

if ($action=='archive')
{
	$id=0;
	
	if ($object->fk_soc || $object->fk_contact)
	{
		$object->set_archiv(1);
	}
	
	if ($object->is_outbox)
	{
		header('Location: '.dol_buildpath('/webmail/outbox.php',1));
	}
	else
	{
		header('Location: '.dol_buildpath('/webmail/inbox.php',1));
	}
	exit;
}


if ($_POST["cancel"])
{
	
	header('Location: '.dol_buildpath('/webmail/inbox.php',1));
	
	exit;
}

/*
 * Add file in email form
*/
if (GETPOST('addfile'))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory TODO Use a dedicated directory for temp mails files
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	dol_add_file_process($upload_dir_tmp,0,0);
	$action ='presend';
	
	if($_GET["action"]=='reply' || $_GET["action"]=='forward')
	{
		$id=0;
	}
	
}

/*
 * Remove file in email form
*/
if (GETPOST('removedfile'))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	// TODO Delete only files that was uploaded from email form
	dol_remove_file_process(GETPOST('removedfile'),0);
	$action ='presend';
	
	if($_GET["action"]=='reply' || $_GET["action"]=='forward')
	{
		$id=0;
	}
}

/*
 * Send mail
*/
if ($action == 'send' && ! GETPOST('addfile') && ! GETPOST('removedfile') && ! GETPOST('cancel'))
{	
	$langs->load('mails');
	
	//Tests
	
	if (dol_strlen(GETPOST('subject'))) 
	{
		$subject=GETPOST('subject');	
		$typesoc=substr(GETPOST('socid'), 0, 1);
		$socid= substr(GETPOST('socid'), 1);
		if ($typesoc=='t')
		{
			$soc=new Societe($db);
			
			$soc->fetch($socid);
			$sendto=$soc->name." <".$soc->email.">";
		}
		elseif ($typesoc=='c')
		{
			$soc=new Contact($db);
			$soc->fetch($socid);
			$sendto=$soc->firstname." ".$soc->lastname." <".$soc->email.">";
		}
		elseif ($typesoc=='u')
		{
			$soc = new User($db);
			$soc->fetch($socid);
			$sendto=$soc->firstname." ".$soc->lastname." <".$soc->email.">";
		}
		else
		{
			$sendto=GETPOST('sendto','alpha');
		}
		
		if (dol_strlen($sendto))
		{
			$langs->load("commercial");
	
			$from = GETPOST('fromname') . ' <' . GETPOST('frommail') .'>';
			$replyto = GETPOST('replytoname'). ' <' . GETPOST('replytomail').'>';
			$message = GETPOST('message');
			$sendtocc = GETPOST('sendtoccc');
			$deliveryreceipt = GETPOST('deliveryreceipt');
					
			if ($action == 'send')
			{
				if (dol_strlen(GETPOST('subject'))) $subject=GETPOST('subject');
				else $subject = '';
				$actiontypecode='AC_COM';
				$actionmsg = $langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
				if ($message)
				{
					$actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
					$actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
					$actionmsg.=$message;
				}
				$actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
			}
	
			// Create form object
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
	
			$attachedfiles=$formmail->get_attached_files();
			$filepath = $attachedfiles['paths'];
			$filename = $attachedfiles['names'];
			$mimetype = $attachedfiles['mimes'];
	
			// Send mail
			dol_include_once('/webmail/class/smtp.class.php');
			
			$mailfile = new SMTPFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt,-1);
			if ($mailfile->error)
			{
				//$mesg='<div class="error">'.$mailfile->error.'</div>';
				setEventMessage($mailfile->error, 'errors');
			}
			else
			{
				$result=$mailfile->sendfile();
				if ($result)
				{
					//$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));	// Must not contains "
					
					setEventMessage($langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2)), 'mesgs');
					$error=0;
	
					// Initialisation donnees
					$object->sendtoid		= $sendtoid;
					$object->actiontypecode	= $actiontypecode;
					$object->actionmsg		= $actionmsg;
					$object->actionmsg2		= $actionmsg2;
					$object->fk_element		= $object->id;
					$object->elementtype	= $object->element;
						
					// Insert into llx_webmail_messages
					dol_include_once('/webmail/class/message.class.php');
					
					$clsmessage = new Message($db);
											
					$clsmessage->fk_user=trim($user->id);
	
					$clsmessage->subject=trim($subject);
					$clsmessage->body=trim($message);
					$clsmessage->is_outbox=1;
					$clsmessage->state_sent=1;
					$clsmessage->state_new=0;
					$clsmessage->state_reply=0;
					$clsmessage->state_forward=0;
					$clsmessage->state_wait=0;
					$clsmessage->state_spam=0;
					$clsmessage->id_correo=0;
					$clsmessage->from=trim($from);
					$clsmessage->to=trim($sendto);
					$clsmessage->cc=trim($sendtocc);
					$clsmessage->bcc=trim($sendtobcc);
					$clsmessage->files=sizeof($attachedfiles['names']);
					$clsmessage->datetime=dol_now();
					$clsmessage->uidl=dol_now();
						
					$clsmessage->create($user);
					
					$clsmessage->movefilestooutbox($attachedfiles);
					
					$formmail->clear_attached_files();
										
					// Redirect here
					// This avoid sending mail twice if going out and then back to page
					header('Location: '.dol_buildpath('/webmail/outbox.php',1));
					exit;
					
				}
				else
				{
					$langs->load("other");
					//$mesg='<div class="error">';
					if ($mailfile->error)
					{
						$mesg =$langs->trans('ErrorFailedToSendMail',$from,$sendto);
						$mesg.='<br>'.$mailfile->error;
						
						setEventMessage($mesg, 'errors');
						
					}
					else
					{
						$mesg='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
						setEventMessage($mesg, 'errors');
					}
					//$mesg.='</div>';
				}
			}
			
		}
		else
		{
			setEventMessage($langs->trans("PutDest"), 'errors');
			$action="presend";
			$bodymail=GETPOST('message','alpha');
			$topicmail=GETPOST('subject','alpha');		
		}
	}
	else
	{
		setEventMessage($langs->trans("PutSubject"), 'errors');
		$action="presend";
		$bodymail=GETPOST('message','alpha');
		$topicmail=GETPOST('subject','alpha');	
	}
}

if ($action=='confirm_delete' && $confirm == 'yes')
{
 	if ($user->rights->webmail->delete)
        {
            $result = $object->delete($object->id);
        }

        if ($result > 0)
        {
            header('Location: '.dol_buildpath('/webmail/inbox.php',1));
            exit;
        }
        else
        {
        	setEventMessage($langs->trans($object->error), 'errors');
            $reload = 0;
            $action='';
        }
}

/***************************************************
* VIEW
*
****************************************************/

llxHeader('','message','');

$form=new Form($db);

if($action=='presend' && ! $id)
{
	if(GETPOST('socid'))
	{
		$typesoc=substr(GETPOST('socid'), 0, 1);
		$socid= substr(GETPOST('socid'), 1);

		if ($typesoc=='t')
		{
			$soc=new Societe($db);
		
			$soc->fetch($socid);
			$sendto=$soc->name." <".$soc->email.">";
		}
		elseif ($typesoc=='c')
		{
			$soc=new Contact($db);
			$soc->fetch($socid);
			$sendto=$soc->firstname." ".$soc->lastname." <".$soc->email.">";
		}
		elseif ($typesoc=='u')
		{
			$soc = new User($db);
			$soc->fetch($socid);
			$sendto=$soc->firstname." ".$soc->lastname." <".$soc->email.">";
		}
		else
		{
			$sendto=GETPOST('sendto','alpha');
		}
	}
	elseif ($_GET['action']=='reply' && !$sendto)
	{
		$sendto=GETPOST('sendto','alpha');
	}
	
	if(!$sendto && GETPOST('sendto','alpha'))
	{	
		$sendto= GETPOST('sendto','alpha');
	}
	
	
	// By default if $action=='presend'
	$titreform='SendMail';
	$action='send';
	$modelmail='thirdparty';
	
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';
	
	$fileparams = dol_most_recent_file($upload_dir_tmp);
	$file=$fileparams['fullname'];
	

	print '<br>';
	print_titre($langs->trans($titreform));

	// Cree l'objet formulaire mail
	
	//dol_include_once('/webmail/class/html.formmail.class.php');
	
	if ($user->signature)
	{
		$bodymail.= $user->signature;
	}
		
	$formmail = new FormWebMail($db);
	$formmail->fromtype = 'user';
	$formmail->fromid   = $user->id;
	$formmail->fromname = $user->getFullName($langs);
	$formmail->frommail = $user->email;
	$formmail->withfrom=1;
	$formmail->withtopic=($topicmail?$topicmail:1);
	$formmail->withtofree=1;
	
	if($sendto)
		$formmail->withto=$sendto;
	
	
	if ($sendtocc)
		$formmail->withtoccc=$sendtocc;
	else
		$formmail->withtoccc=1;
	
	$formmail->withfile=2;
	$formmail->withbody=($bodymail?$bodymail:1);
	$formmail->withdeliveryreceipt=1;
	$formmail->withcancel=1;
	// Tableau des substitutions
	//$formmail->substit['__SIGNATURE__']=$user->signature;
	$formmail->substit['__PERSONALIZED__']='';
	$formmail->substit['__CONTACTCIVNAME__']='';
	
	// Tableau des parametres complementaires du post
	$formmail->param['action']=$action;
	//$formmail->param['models']=$modelmail;

	// Init list of files
	if (GETPOST("mode")=='init')
	{
		if (! isset($_POST["token"]))
		{
			$formmail->clear_attached_files();
		}
		else
		{
			$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
		}
	}
	
	if (GETPOST("action")=='forward')
	{
		if(GETPOST('cancel')!='') $formmail->clear_attached_files();
		
		//$fileparams = dol_most_recent_file($upload_dir_tmp);
		$fileparams = dol_dir_list($upload_dir_tmp,'files',0);
		$filecount=sizeof($fileparams);
		if($filecount)
		{
			$i=0;
			while($i<$filecount)
			{
				$formmail->add_attached_files($fileparams[$i]['fullname'],basename($fileparams[$i]['fullname']),dol_mimetype($fileparams[$i]['fullname']));
				$i++;
			}
		}
	}
	
	if (GETPOST("action")=='reply' && ! isset($_POST["token"]))
	{
		$formmail->clear_attached_files();
	}

	$formmail->show_form();

	print '<br>';

}

if($id)
{
	if($_GET["action"]=='reply' || $_GET["action"]=='forward')
	{
		$formmail = new FormWebMail($db);
		$formmail->clear_attached_files();
	}
	
	$thirdpartystatic = new Societe($db);
	$contactstatic=new Contact($db);
	$userstatic=new User($db);

	//dol_htmloutput_mesg($mesg);

	$head = message_prepare_head($object);

	dol_fiche_head($head, 'card', $langs->trans("MessageCard"), 0, 'email');

	// Deletion confirmation
	if ($action == 'delete')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteMail'), $langs->trans('ConfirmDeleteMail'), 'confirm_delete','',0,1);
		
	}

	print '<table class="border" width="100%">';

	if ($object->is_outbox)
	{
		$linkback = '<a href="'.dol_buildpath('/webmail/outbox.php',1).'">'.$langs->trans("BackToOutBox").'</a>';
	}
	else
	{
		$linkback = '<a href="'.dol_buildpath('/webmail/inbox.php',1).'">'.$langs->trans("BackToInbox").'</a>';
	}
	
	// Ref
	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
	//print $object->id;
	
	print $form->showrefnav($object, 'uidl', $linkback, 1, 'uidl', 'uidl');
	
	print '</td></tr>';

	// From
	print "<tr><td>".$langs->trans("MailSender")."</td>";
	
	$typemail = search_sender($object->from);
		
	if(is_array($typemail))
	{	
		switch ($typemail['type']) 
		{
			case "Third":
				$thirdpartystatic->id=$typemail['id'];
				$thirdpartystatic->name=$typemail['name'];				
				print "<td>".$thirdpartystatic->getNomUrl(1)."</td></tr>";
    			break;
			case "Contact":
				$contactstatic->lastname=$typemail['lastname'];
				$contactstatic->firstname=$typemail['firstname'];
				$contactstatic->id=$typemail['id'];
				print "<td>".$contactstatic->getNomUrl(1)."</td></tr>";
    			break;
			case "User":				
				$userstatic->id=$typemail['id'];
				$userstatic->lastname=$typemail['lastname'];
				$userstatic->firstname=$typemail['firstname'];
				print "<td>".$userstatic->getNomUrl(1)."</td></tr>";
				break;
				
			default:
    				print "<td>".dol_htmlentitiesbr($object->from)."</td></tr>";
		}
	}
	else
	{
		print "<td>".dol_htmlentitiesbr($object->from)."</td></tr>";
	}
	
	// To
	print "<tr><td>".$langs->trans("MailReceiver")."</td>";
	
	$typemail = search_sender($object->to);
		
	if(is_array($typemail))
	{	
		switch ($typemail['type']) 
		{
			case "Third":
				$thirdpartystatic->id=$typemail['id'];
				$thirdpartystatic->name=$typemail['name'];				
				print "<td>".$thirdpartystatic->getNomUrl(1)."</td></tr>";
    			break;
			case "Contact":
				$contactstatic->lastname=$typemail['lastname'];
				$contactstatic->firstname=$typemail['firstname'];
				$contactstatic->id=$typemail['id'];
				print "<td>".$contactstatic->getNomUrl(1)."</td></tr>";
    			break;
    		case "User":				
				$userstatic->id=$typemail['id'];
				$userstatic->lastname=$typemail['lastname'];
				$userstatic->firstname=$typemail['firstname'];
				print "<td>".$userstatic->getNomUrl(1)."</td></tr>";
				break;
			default:
				print "<td>".dol_htmlentitiesbr($object->to)."</td></tr>";
		}
	}
	else
	{
		print "<td>".dol_htmlentitiesbr($object->to)."</td></tr>";
	}
	
	if($object->cc)
	{
		print "<tr><td>".$langs->trans("MailCC")."</td>";
		print "<td>".dol_htmlentitiesbr($object->cc)."</td></tr>";
	}
	
	// subject
	print '<tr><td>'.$langs->trans("MailTopic").'</td>';
	print '<td>'.$object->subject.'</td>';
	print '</tr>';

	// Date
	print '<tr><td>'.$langs->trans("Date").'</td>';
	print '<td>'.dol_print_date($object->db->idate($object->datetime),'dayhourtext').'</td>';
	print '</tr>';

	// Statut
	
	$status=$object->state_new;

	if($object->state_reply)
	{
		$status=2;
	}
	if ($object->state_spam)
	{
		$status=3;
	}
	
	if ($object->is_outbox)
		$status=4;
	
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->LibStatut($status).'</td></tr>';
	
	//Message
	//print '<tr><td>'.$langs->trans("Message").'</td><td>'.dol_htmlentitiesbr($object->body).'</td></tr>';
	
	if (GETPOST('optioncss','alpha')=='print')
	{
		print '<tr><td>'.$langs->trans("Message").'</td><td>'.dol_htmlentitiesbr($object->body).'</td></tr>';
	}
	else
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor=new DolEditor('message',$object->body,'',280,'dolibarr_notes','In',false,false,1,8,72,1);
		print '<tr><td>'.$langs->trans("Message").'</td><td>'.$doleditor->Create(1).'</td></tr>';
	}
	print "</table><br>";
	print '</div>';
	print "\n";


	/*
	 * Barre d'actions
	*/
	print '<div class="tabsAction">';

	if ($user->societe_id == 0)
	{
	
		// Reply
		if ($user->rights->webmail->reply && ! $object->is_outbox)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=reply"';
			print '>'.$langs->trans("Reply").'</a></div>';
		}
		
		// Forward
		if ($user->rights->webmail->reply)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=forward"';
			print '>'.$langs->trans("Forward").'</a></div>';
		}
		
		if ($user->rights->webmail->reply && ! $object->is_outbox)
		{
			if ($object->state_spam)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setnospam"';
				print '>'.$langs->trans("SetasNoSpam").'</a></div>';
			}
			else 
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setspam"';
				print '>'.$langs->trans("SetasSpam").'</a></div>';
			}
		}
		
		// Archive
		if ($object->fk_soc || $object->fk_contact)
		{
			if ($user->rights->webmail->reply)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=archive"';
				print '>'.$langs->trans("Archive").'</a></div>';
			}
		}
		else
		{
			if (! $object->is_outbox)
			{
				
				$initpos =strpos($object->from, "<");
				$endpos=strpos($object->from, ">");
				$lenght= $endpos-$initpos-1;
	
				if($initpos)
				{
					$mail = substr ( $object->from , $initpos+1, $lenght);
					$name = substr ( $object->from , 0, $initpos);
				}
				

			}
			else 
			{
				$initpos =strpos($object->to, "<");
				$endpos=strpos($object->to, ">");
				$lenght= $endpos-$initpos-1;
	
				if($initpos)
				{
					$mail = substr ( $object->to , $initpos+1, $lenght);
					$name = substr ( $object->to , 0, $initpos);
				}
			}
			 
			
			if ( ! getuserbymail($mail))
			{
				$object->set_archiv;
				print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/contact/fiche.php',1).'?email='.$mail.'&lastname='.$name.'&action=create"';
				print '>'.$langs->trans("CreateContact").'</a></div>';
			}
		}
		
		// Delete
		if ($user->rights->webmail->delete)
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete"';
			print '>'.$langs->trans('Delete').'</a></div>';
		}

		
	}

	print '</div>';
	print '<br>';
}

// End of page
llxFooter();
$db->close();
?>