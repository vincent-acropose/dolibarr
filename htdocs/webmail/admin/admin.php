<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 *       \file       webmail/admin/admin.php
 *       \brief      Page to setup webmail
 */

$res=@include("../../main.inc.php");                                // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once("/webmail/lib/message.lib.php");

$langs->load("admin");
$langs->load("mails");
$langs->load("other");
$langs->load("webmail@webmail");

if (! $user->admin) accessforbidden();

$substitutionarrayfortest=array(
'__LOGIN__' => $user->login,
'__ID__' => 'TESTIdRecord',
'__EMAIL__' => 'TESTEMail',
'__LASTNAME__' => 'TESTLastname',
'__FIRSTNAME__' => 'TESTFirstname',
'__SIGNATURE__' => (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN))?$user->signature:''),
//'__PERSONALIZED__' => 'TESTPersonalized'	// Hiden because not used yet
);
complete_substitutions_array($substitutionarrayfortest, $langs);

$action=GETPOST('action');


/*
 * Actions
 */

if ($action == 'update' && empty($_POST["cancel"]))
{
	dolibarr_set_const($db, "MAIN_DISABLE_ALL_MAILS",   GETPOST("MAIN_DISABLE_ALL_MAILS"),'chaine',0,'',$conf->entity);
    // Send mode parameters

	if (isset($_POST["WEBMAIL_SMTP_PORT"]))   dolibarr_set_const($db, "WEBMAIL_SMTP_PORT",   GETPOST("WEBMAIL_SMTP_PORT"),'chaine',0,'',$conf->entity);
	if (isset($_POST["WEBMAIL_SMTP_SERVER"])) dolibarr_set_const($db, "WEBMAIL_SMTP_SERVER", GETPOST("WEBMAIL_SMTP_SERVER"),'chaine',0,'',$conf->entity);
	if (isset($_POST["WEBMAIL_POP3_SERVER"]))    dolibarr_set_const($db, "WEBMAIL_POP3_SERVER",    GETPOST("WEBMAIL_POP3_SERVER"), 'chaine',0,'',$conf->entity);
	if (isset($_POST["WEBMAIL_POP3_PORT"]))    dolibarr_set_const($db, "WEBMAIL_POP3_PORT",    GETPOST("WEBMAIL_POP3_PORT"), 'chaine',0,'',$conf->entity);
	if (isset($_POST["WEBMAIL_EMAIL_TLS"]))   dolibarr_set_const($db, "WEBMAIL_EMAIL_TLS",   GETPOST("WEBMAIL_EMAIL_TLS"),'chaine',0,'',$conf->entity);
	
	if (isset($_POST["WEBMAIL_EMAIL_FILTER"]))   dolibarr_set_const($db, "WEBMAIL_EMAIL_FILTER",   GETPOST("WEBMAIL_EMAIL_FILTER"),'chaine',0,'',$conf->entity);
   
	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


/*
 * Add file in email form
 */
if (GETPOST('addfile') || GETPOST('addfilehtml'))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp';
	dol_add_file_process($upload_dir,0,0);

	if ($_POST['addfile'])     $action='test';
	if ($_POST['addfilehtml']) $action='testhtml';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']) || ! empty($_POST['removedfilehtml']))
{
	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp';

	$keytodelete=isset($_POST['removedfile'])?$_POST['removedfile']:$_POST['removedfilehtml'];
	$keytodelete--;

	$listofpaths=array();
	$listofnames=array();
	$listofmimes=array();
	if (! empty($_SESSION["listofpaths"])) $listofpaths=explode(';',$_SESSION["listofpaths"]);
	if (! empty($_SESSION["listofnames"])) $listofnames=explode(';',$_SESSION["listofnames"]);
	if (! empty($_SESSION["listofmimes"])) $listofmimes=explode(';',$_SESSION["listofmimes"]);

	if ($keytodelete >= 0)
	{
		$pathtodelete=$listofpaths[$keytodelete];
		$filetodelete=$listofnames[$keytodelete];
		$result = dol_delete_file($pathtodelete,1);
		if ($result >= 0)
		{
			setEventMessage($langs->trans("FileWasRemoved"), $filetodelete);

			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			$formmail->remove_attached_files($keytodelete);
		}
	}
	if ($_POST['removedfile'] || $action='send')     $action='test';
	if ($_POST['removedfilehtml'] || $action='sendhtml') $action='testhtml';
}

/*
 * Send mail
 */
if (($action == 'send' || $action == 'sendhtml') && ! GETPOST('addfile') && ! GETPOST('addfilehtml') && ! GETPOST('removedfile') && ! GETPOST('cancel'))
{
	$error=0;
	
	$email_from='';
	if (! empty($_POST["fromname"])) $email_from=$_POST["fromname"].' ';
	if (! empty($_POST["frommail"])) $email_from.='<'.$_POST["frommail"].'>';

	$errors_to  = $_POST["errorstomail"];
	$sendto     = $_POST["sendto"];
	$sendtocc   = $_POST["sendtocc"];
	$sendtoccc  = $_POST["sendtoccc"];
	$subject    = $_POST['subject'];
	$body       = $_POST['message'];
	$deliveryreceipt= $_POST["deliveryreceipt"];
	
	//Check if we have to decode HTML
	if (!empty($conf->global->FCKEDITOR_ENABLE_MAILING) && dol_textishtml(dol_html_entity_decode($body, ENT_COMPAT | ENT_HTML401))) {
		$body=dol_html_entity_decode($body, ENT_COMPAT | ENT_HTML401);
	}
	
	// Create form object
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);

	$attachedfiles=$formmail->get_attached_files();
	$filepath = $attachedfiles['paths'];
	$filename = $attachedfiles['names'];
	$mimetype = $attachedfiles['mimes'];

	if (empty($_POST["frommail"]))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("MailFrom")),'errors');
		$action='test';
		$error++;
	}
	if (empty($sendto))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("MailTo")),'errors');
		$action='test';
		$error++;
	}
	if (! $error)
	{
		// Le message est-il en html
		$msgishtml=0;	// Message is not HTML
		if ($action == 'sendhtml') $msgishtml=1;	// Force message to HTML

		// Pratique les substitutions sur le sujet et message
		$subject=make_substitutions($subject,$substitutionarrayfortest);
		$body=make_substitutions($body,$substitutionarrayfortest);

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
        $mailfile = new CMailFile(
            $subject,
            $sendto,
            $email_from,
            $body,
            $filepath,
            $mimetype,
            $filename,
            $sendtocc,
            $sendtoccc,
            $deliveryreceipt,
            $msgishtml,
            $errors_to
        );

		$result=$mailfile->sendfile();

		if ($result)
		{
			setEventMessage($langs->trans("MailSuccessfulySent",$mailfile->getValidAddress($email_from,2),$mailfile->getValidAddress($sendto,2)));
		}
		else
		{
			setEventMessage($langs->trans("ResultKo").'<br>'.$mailfile->error.' '.$result,'errors');
		}

		$action='';
	}
}



/*
 * View
 */

$linuxlike=1;
if (preg_match('/^win/i',PHP_OS)) $linuxlike=0;
if (preg_match('/^mac/i',PHP_OS)) $linuxlike=0;

$smtpport=! empty($conf->global->WEBMAIL_SMTP_PORT)?$conf->global->WEBMAIL_SMTP_PORT:ini_get('smtp_port');
if (! $smtpport) $smtpport=25;
$serversmtp=! empty($conf->global->WEBMAIL_SMTP_SERVER)?$conf->global->WEBMAIL_SMTP_SERVER:ini_get('SMTP');
if (! $serversmtp) $serversmtp='127.0.0.1';

$popport=! empty($conf->global->WEBMAIL_POP3_PORT)?$conf->global->WEBMAIL_POP3_PORT:ini_get('pop3_port');
if (! $popport) $popport=110;
$serverpop=! empty($conf->global->WEBMAIL_POP3_SERVER)?$conf->global->WEBMAIL_POP3_SERVER:ini_get('SMTP');
if (! $serverpop) $serverpop='127.0.0.1';

/*
 * View
 */

$wikihelp='';//$wikihelp='EN:Setup EMails|FR:Paramétrage EMails|ES:Configuración EMails';
llxHeader('',$langs->trans("Setup"),$wikihelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("WebmailSetup"),$linkback,'setup');

$head = webmailadmin_prepare_head();

dol_fiche_head($head, 'configuration', $langs->trans("Webmail"), 0, 'webmail@webmail');

print $langs->trans("WebMailsDesc")."<br>\n";
//print "<br>\n";

if ($action == 'edit')
{
	$form=new Form($db);

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	clearstatcache();
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>';
	print $form->selectyesno('MAIN_DISABLE_ALL_MAILS',$conf->global->MAIN_DISABLE_ALL_MAILS,1);
	print '</td></tr>';

	// Separator
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';


	// Server pop3
	$var=!$var;
	print '<tr '.$bc[$var].'><td>';
	$mainserver = (! empty($conf->global->WEBMAIL_POP3_SERVER)?$conf->global->WEBMAIL_POP3_SERVER:'');
	$popserver = ini_get('POP3')?ini_get('POP3'):$langs->transnoentities("Undefined");
	print $langs->trans("WEBMAIL_POP3_SERVER",$popserver);
	print '</td><td>';
	// SuperAdministrator access only
	if (empty($conf->multicompany->enabled) || ($user->admin && ! $user->entity))
	{
		print '<input class="flat" id="WEBMAIL_POP3_SERVER" name="WEBMAIL_POP3_SERVER" size="18" value="' . $mainserver . '">';
		print '<input type="hidden" id="WEBMAIL_POP3_SERVER_sav" name="WEBMAIL_POP3_SERVER_sav" value="' . $mainserver . '">';
	}
	else
	{
		$text = ! empty($mainserver) ? $mainserver : $popserver;
		$htmltext = $langs->trans("ContactSuperAdminForChange");
		print $form->textwithpicto($text,$htmltext,1,'superadmin');
		print '<input type="hidden" id="WEBMAIL_POP3_SERVER" name="WEBMAIL_POP3_SERVER" value="'.$mainserver.'">';
	}
	print '</td></tr>';

	// Port pop3
	$var=!$var;
	print '<tr '.$bc[$var].'><td>';
	
	$mainport = (! empty($conf->global->WEBMAIL_POP3_PORT) ? $conf->global->WEBMAIL_POP3_PORT : '');
	$popport = ini_get('pop3_port')?ini_get('pop3_port'):$langs->transnoentities("Undefined");
	print $langs->trans("WEBMAIL_POP3_PORT",$popport);
	print '</td><td>';
	// SuperAdministrator access only
	if (empty($conf->multicompany->enabled) || ($user->admin && ! $user->entity))
	{
		print '<input class="flat" id="WEBMAIL_POP3_PORT" name="WEBMAIL_POP3_PORT" size="3" value="' . $mainport . '">';
		print '<input type="hidden" id="WEBMAIL_POP3_PORT_sav" name="WEBMAIL_POP3_PORT_sav" value="' . $mainport . '">';
	}
	else
	{
		$text = (! empty($mainport) ? $mainport : $popport);
		$htmltext = $langs->trans("ContactSuperAdminForChange");
		print $form->textwithpicto($text,$htmltext,1,'superadmin');
		print '<input type="hidden" id="WEBMAIL_POP3_PORT" name="WEBMAIL_POP3_PORT" value="'.$mainport.'">';
	}
	
	print '</td></tr>';
	
	// Server smtp
	$var=!$var;
	
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';
	$var=!$var;
	
	print '<tr '.$bc[$var].'><td>';
	$mainserver = (! empty($conf->global->WEBMAIL_SMTP_SERVER)?$conf->global->WEBMAIL_SMTP_SERVER:'');
	$popserver = ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined");
	print $langs->trans("WEBMAIL_SMTP_SERVER",$popserver);
	print '</td><td>';
	// SuperAdministrator access only
	if (empty($conf->multicompany->enabled) || ($user->admin && ! $user->entity))
	{
		print '<input class="flat" id="WEBMAIL_SMTP_SERVER" name="WEBMAIL_SMTP_SERVER" size="18" value="' . $mainserver . '">';
		print '<input type="hidden" id="WEBMAIL_SMTP_SERVER_sav" name="WEBMAIL_SMTP_SERVER_sav" value="' . $mainserver . '">';
	}
	else
	{
		$text = ! empty($mainserver) ? $mainserver : $popserver;
		$htmltext = $langs->trans("ContactSuperAdminForChange");
		print $form->textwithpicto($text,$htmltext,1,'superadmin');
		print '<input type="hidden" id="WEBMAIL_SMTP_SERVER" name="WEBMAIL_SMTP_SERVER" value="'.$mainserver.'">';
	}
	print '</td></tr>';

	// Port SMTP
	$var=!$var;
	print '<tr '.$bc[$var].'><td>';
	
	$mainport = (! empty($conf->global->WEBMAIL_SMTP_PORT) ? $conf->global->WEBMAIL_SMTP_PORT : '');
	$popport = ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined");
	print $langs->trans("WEBMAIL_SMTP_PORT",$popport);
	print '</td><td>';
	// SuperAdministrator access only
	if (empty($conf->multicompany->enabled) || ($user->admin && ! $user->entity))
	{
		print '<input class="flat" id="WEBMAIL_SMTP_PORT" name="WEBMAIL_SMTP_PORT" size="3" value="' . $mainport . '">';
		print '<input type="hidden" id="WEBMAIL_SMTP_PORT_sav" name="WEBMAIL_SMTP_PORT_sav" value="' . $mainport . '">';
	}
	else
	{
		$text = (! empty($mainport) ? $mainport : $smtpport);
		$htmltext = $langs->trans("ContactSuperAdminForChange");
		print $form->textwithpicto($text,$htmltext,1,'superadmin');
		print '<input type="hidden" id="WEBMAIL_SMTP_PORT" name="WEBMAIL_SMTP_PORT" value="'.$mainport.'">';
	}
	
	print '</td></tr>';

	// TLS
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("WEBMAIL_EMAIL_TLS").'</td><td>';
	if (! empty($conf->use_javascript_ajax))
	{
		if (function_exists('openssl_open'))
		{
			print $form->selectyesno('WEBMAIL_EMAIL_TLS',(! empty($conf->global->WEBMAIL_EMAIL_TLS)?$conf->global->WEBMAIL_EMAIL_TLS:0),1);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

	
	// Separator
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';

	// Filter
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("WEBMAIL_EMAIL_FILTER").'</td><td>';
	
	print $form->selectyesno('WEBMAIL_EMAIL_FILTER',(! empty($conf->global->WEBMAIL_EMAIL_FILTER)?$conf->global->WEBMAIL_EMAIL_FILTER:0),1);
	
	print '</td></tr>';
	print '</table>';

	print '<br><center>';
	print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; ';
	print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</center>';

	print '</form>';
	print '<br>';
}
else
{
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>'.yn($conf->global->MAIN_DISABLE_ALL_MAILS).'</td></tr>';

	// Separator
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';
	
	
	//POP3 Server
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("WEBMAIL_POP3_SERVER",ini_get('POP3')?ini_get('POP3'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->WEBMAIL_POP3_SERVER)?$conf->global->WEBMAIL_POP3_SERVER:'').'</td></tr>';
	// Pop3 port
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("WEBMAIL_POP3_PORT",ini_get('pop3_port')?ini_get('pop3_port'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->WEBMAIL_POP3_PORT)?$conf->global->WEBMAIL_POP3_PORT:'').'</td></tr>';

	//SMTP Server
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("WEBMAIL_SMTP_SERVER",ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->WEBMAIL_SMTP_SERVER)?$conf->global->WEBMAIL_SMTP_SERVER:'').'</td></tr>';
	// SMTP Port
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("WEBMAIL_SMTP_PORT",ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->WEBMAIL_SMTP_PORT)?$conf->global->WEBMAIL_SMTP_PORT:'').'</td></tr>';
		
	// TLS
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("WEBMAIL_EMAIL_TLS").'</td><td>';
	
	if (function_exists('openssl_open'))
	{
		print yn($conf->global->WEBMAIL_EMAIL_TLS);
	}
	else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	
	print '</td></tr>';
	
	//Filter
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("WEBMAIL_EMAIL_FILTER").'</td><td>';
	
	print yn($conf->global->WEBMAIL_EMAIL_FILTER);
	
	print '</td></tr>';
	
	print '</table>';

    if ($conf->global->MAIN_MAIL_SENDMODE == 'mail' && empty($conf->global->MAIN_FIX_FOR_BUGGED_MTA))
    {
        print '<br>';
       
   	    print info_admin($langs->trans("SendmailOptionMayHurtBuggedMTA"));
    }

	// Boutons actions
	print '<div class="tabsAction">';

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';

	if (function_exists('fsockopen') && ! empty($conf->global->WEBMAIL_SMTP_SERVER) && ! empty($conf->global->WEBMAIL_SMTP_PORT))
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testconnect">'.$langs->trans("DoTestServerAvailability").'</a>';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&amp;mode=init">'.$langs->trans("DoTestSend").'</a>';
		if (! empty($conf->fckeditor->enabled))
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testhtml&amp;mode=init">'.$langs->trans("DoTestSendHTML").'</a>';
		}
		
	}
	print '</div>';


	// Run the test to connect
	if ($action == 'testconnect')
	{
		print '<br>';
		print_titre($langs->trans("DoTestServerAvailability"));

		// If we use SSL/TLS
		if (! empty($conf->global->WEBAIL_EMAIL_TLS) && function_exists('openssl_open')) $serversmtp='ssl://'.$serversmtp;

		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mail = new CMailFile('','','','');
		$result=$mail->check_server_port($serversmtp,$smtpport);
		if ($result) print '<div class="ok">'.$langs->trans("ServerAvailableOnIPOrPort",$serversmtp,$smtpport).'</div>';
		else
		{
			print '<div class="error">'.$langs->trans("ServerNotAvailableOnIPOrPort",$serversmtp,$smtpport);
			if ($mail->error) print ' - '.$mail->error;
			print '</div>';
		}
		print '<br>';
	}

	// Show email send test form
	if ($action == 'test' || $action == 'testhtml')
	{
		print '<br>';
		print_titre($action == 'testhtml'?$langs->trans("DoTestSendHTML"):$langs->trans("DoTestSend"));

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->fromname = (isset($_POST['fromname'])?$_POST['fromname']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->frommail = (isset($_POST['frommail'])?$_POST['frommail']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->withfromreadonly=0;
		$formmail->withsubstit=0;
		$formmail->withfrom=1;
		$formmail->witherrorsto=1;
		$formmail->withto=(! empty($_POST['sendto'])?$_POST['sendto']:($user->email?$user->email:1));
		$formmail->withtocc=(! empty($_POST['sendtocc'])?$_POST['sendtocc']:1);       // ! empty to keep field if empty
		$formmail->withtoccc=(! empty($_POST['sendtoccc'])?$_POST['sendtoccc']:1);    // ! empty to keep field if empty
		$formmail->withtopic=(isset($_POST['subject'])?$_POST['subject']:$langs->trans("Test"));
		$formmail->withtopicreadonly=0;
		$formmail->withfile=2;
		$formmail->withbody=(isset($_POST['message'])?$_POST['message']:($action == 'testhtml'?$langs->transnoentities("PredefinedMailTestHtml"):$langs->transnoentities("PredefinedMailTest")));
		$formmail->withbodyreadonly=0;
		$formmail->withcancel=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withfckeditor=($action == 'testhtml'?1:0);
		$formmail->ckeditortoolbar='dolibarr_mailings';
		// Tableau des substitutions
		$formmail->substit=$substitutionarrayfortest;
		// Tableau des parametres complementaires du post
		$formmail->param["action"]=($action == 'testhtml'?"sendhtml":"send");
		$formmail->param["models"]="body";
		$formmail->param["mailid"]=0;
		$formmail->param["returnurl"]=$_SERVER["PHP_SELF"];

		// Init list of files
        if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
		}

		$formmail->show_form(($action == 'testhtml'?'addfilehtml':'addfile'),($action == 'testhtml'?'removefilehtml':'removefile'));

		print '<br>';
	}
}


llxFooter();

$db->close();
?>