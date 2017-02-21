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
 *   	\file       webmail/attachments.php
 *		\ingroup    webmail
 */

$action		= $_GET['action'];//,'alpha');

$res=@include("../main.inc.php");								// For root directory
if (! $res) $res=@include("../../main.inc.php");                // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/webmail/class/message.class.php');
dol_include_once('/webmail/lib/webmail.lib.php');
dol_include_once('/webmail/lib/message.lib.php');

// Load traductions files requiredby by page
$langs->load("webmail@webmail");

// Get parameters
$id			= GETPOST('id','int');

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

/***************************************************
* VIEW
*
****************************************************/

llxHeader('','message','');

$form=new Form($db);


// Put here content of your page
/*
// Example 1 : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_needroot();
	});
});
</script>';
*/

// Example 2 : Adding links to objects
// The class must extends CommonObject class to have this method available
//$somethingshown=$object->showLinkedObjectBlock();

if($id)
{
	$thirdpartystatic = new Societe($db);
	$contactstatic=new Contact($db);
	$userstatic=new User($db);

	$object->fetch($id);

	dol_htmloutput_mesg($mesg);

	$head = message_prepare_head($object);

	dol_fiche_head($head, 'attachment', $langs->trans("MessageCard"), 0, 'email');
	
	print '<table class="border" width="100%">';

	if ($object->is_outbox)
	{
		$linkback = '<a href="'.dol_buildpath('/webmail/outbox.php',1).'">'.$langs->trans("BackToOutBox").'</a>';
		$upload_dir = $conf->webmail->dir_output."/outbox/".$user->id."/".$object->id ;
	}
	else
	{
		$linkback = '<a href="'.dol_buildpath('/webmail/inbox.php',1).'">'.$langs->trans("BackToInbox").'</a>';
		$upload_dir = $conf->webmail->dir_output."/inbox/".$user->id."/".$object->id ;
	}

	// Ref
	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
	//print $object->id;
	
	print $form->showrefnav($object, 'uidl', $linkback, 1, 'uidl', 'uidl');
	
	print '</td></tr>';

	// From
	print "<tr><td>".$langs->trans("From")."</td>";
	
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
	// subject
	print '<tr><td>'.$langs->trans("MailTopic").'</td>';
	print '<td>'.$object->subject.'</td>';
	print '</tr>';

	// Date
	print '<tr><td>'.$langs->trans("Date").'</td>';
	print '<td>'.dol_print_date($object->db->idate($object->datetime),'dayhourtext').'</td>';
	print '</tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->LibStatut($object->state_new).'</td></tr>';
	
	print "</table><br>";
	print "\n";

	dol_fiche_end();
	print '<br>';
	
	if (!$object->is_outbox)
	{
		//Attachments
			
		$sql = "SELECT rowid, file_name, file, file_size, file_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."webmail_files";
		$sql.= " WHERE fk_mail=".$id;
		
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
		}
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('MailFileName'),$_SERVER["PHP_SELF"],'file_name','',$param,'width="75%"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('MailFileSize'),$_SERVER["PHP_SELF"],'file_size','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('MailFileType'),$_SERVER["PHP_SELF"],'file_type','',$param, 'align="center"',$sortfield,$sortorder);
		
		print '</tr>';
		
		$var=true;
		$i = 0;
		
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';		
			
			//Name
			
			//print '<td>'.img_mime($objp->file_name).' '.$objp->file_name.'</td>';
			
			print '<td>';
			print '<a data-ajax="false" href="'.dol_buildpath('/webmail/download.php',1).'?id='.$id.'&file='.$objp->rowid;
			print '">';
			print img_mime($objp->file_name).' '.$objp->file_name;
			print '</a>';
			print '</td>';
				
			print '<td align="right">'.dol_print_size($objp->file_size,1,1).'</td>';
			print '<td align="center">'.$objp->file_type.'</td>';
			print '</tr>';
			$i++;
		}
		print '</table>';
	}
	else
	{
		
		$formfile=new FormFile($db);
		$dir = $conf->webmail->dir_output."/outbox/".$user->id."/".$object->uidl;
		$filearray=dol_dir_list($dir,"files",1,'');
        $formfile->list_of_documents($filearray,$object,'webmail','',0,"/outbox/".$user->id."/".$object->uidl."/",0,0); //autoecmfiles($dir,$filearray,'webmail');	
	}
}

// End of page
llxFooter();
$db->close();
?>