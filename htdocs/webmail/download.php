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


/**
 * Header empty
 *
 * @return	void
 */
function llxHeader() { }
/**
 * Footer empty
 *
 * @return	void
 */
function llxFooter() { }

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

$file = GETPOST('file','int');
$sql="SELECT file FROM ".MAIN_DB_PREFIX."webmail_files WHERE fk_mail='".$id."' AND rowid=".$file;
	
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	if ($num)
	{
		$objp = $db->fetch_object($resql);
		$cid=$objp->file;
	}
}
	
if(!$cid) setEventMessage("Unknown file","error");
	
$decoded=__getmail_getmime($id);
if(!$decoded) setEventMessage("Email not found","error");
	
$result=__getmail_getcid(__getmail_getnode("0",$decoded),$cid);
	
if(!$result) setEventMessage("Attachment not found");
$ext=strtolower(extension($result["cname"]));
if(!$ext) $ext=substr($result["ctype"],strrpos($result["ctype"],"/")+1);
$file=get_temp_file($ext);
file_put_contents($file,$result["body"]);
$name=$result["cname"];
$type=$result["ctype"];
$size=$result["csize"];
	
$filedest= $conf->webmail->dir_output."/cache/".$name;
dol_move($file, $filedest,664);
	//$type=dol_mimetype($filedest);
	
	//Download
$filename = basename($filedest);

	// Output file on browser
dol_syslog("webmail::attachements.php download $filedest $filename content-type=$type size=$size");
$original_file_osencoded=dol_osencode($filedest);	// New file name encoded in OS encoding charset

	// This test if file exists should be useless. We keep it to find bug more easily
if (! file_exists($original_file_osencoded))
{
	dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$filedest));
	exit;
}
	
header('Content-Description: File Transfer');
//if ($encoding)   header('Content-Encoding: '.$encoding);
if ($type)       header('Content-Type: '.$type);//.(preg_match('/text/',$type)?'; charset="'.$conf->file->character_set_client:''));
//header ("Content-Type: application/octet-stream");
// Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, atachment=need user action to open)
header('Content-Disposition: attachment; filename="'.$filename.'"');

header('Content-Length: ' . $size);
// Ajout directives pour resoudre bug IE
header('Cache-Control: Public, must-revalidate');
header('Pragma: public');

readfile($original_file_osencoded);
