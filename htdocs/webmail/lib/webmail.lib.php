<?php
/* Copyright (C) 2014	Juanjo Menent  <jmenent@2byte.es>
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

dol_include_once("/webmail/class/mime_parser.php");
dol_include_once("/webmail/class/rfc822_addresses.php");
dol_include_once("/webmail/class/pop3.php");

// SOME DEFINES
define("__HTML_PAGE_OPEN__",'<html><head><style type="text/css">'.getDefault("defines/htmlpage").'</style></head><body>');
define("__HTML_PAGE_CLOSE__",'</body></html>');
define("__HTML_BOX_OPEN__",'<div style="'.getDefault("defines/htmlbox").'">');
define("__HTML_BOX_CLOSE__",'</div>');
define("__HTML_TABLE_OPEN__",'<table>');
define("__HTML_TABLE_CLOSE__",'</table>');
define("__HTML_ROW_OPEN__",'<tr>');
define("__HTML_ROW_CLOSE__",'</tr>');
define("__HTML_CELL_OPEN__",'<td>');
define("__HTML_RCELL_OPEN__",'<td align="right" nowrap="nowrap">');
define("__HTML_CELL_CLOSE__",'</td>');
define("__HTML_TEXT_OPEN__",'<span style="'.getDefault("defines/htmltext").'">');
define("__HTML_TEXT_CLOSE__",'</span>');
define("__PLAIN_TEXT_OPEN__",'<span style="'.getDefault("defines/plaintext").'">');
define("__PLAIN_TEXT_CLOSE__",'</span>');
define("__HTML_SEPARATOR__",'<hr style="'.getDefault("defines/separator").'"/>');
define("__HTML_NEWLINE__",'<br/>');
define("__BLOCKQUOTE_OPEN__",'<blockquote style="'.getDefault("defines/blockquote").'">');
define("__BLOCKQUOTE_CLOSE__",'</blockquote>');
define("__SIGNATURE_OPEN__",'<span style="'.getDefault("defines/signature").'">');
define("__SIGNATURE_CLOSE__",'</span>');

// REMOVE ALL BODY (ONLY FOR DEBUG PURPOSES)
function __getmail_removebody($array) {
	if(isset($array["Body"])) $array["Body"]="##### BODY REMOVED FOR DEBUG PURPOSES #####";
	$parts=__getmail_getnode("Parts",$array);
	if($parts) {
		foreach($parts as $index=>$node) {
			$array["Parts"][$index]=__getmail_removebody($node);
		}
	}
	return $array;
}

// REMOVE ALL SCRIPT TAGS
function __getmail_removescripts($temp) {
	$temp=preg_replace("@<script[^>]*?.*?</script>@siu","",$temp);
	return $temp;
}

// THE FOLLOW FUNCTIONS UNIFY THE CONCEPT OF PROCESS
function __getmail_processmessage($disp,$type) {
	return ($type=="message" && $disp=="inline");
}

function __getmail_processplainhtml($disp,$type) {
	return (in_array($type,array("plain","html")) && $disp=="inline");
}

function __getmail_processfile($disp,$type) {
	return ($disp=="attachment" || ($disp=="inline" && !in_array($type,array("plain","html","message","alternative","multipart"))));
}
/*
// CHECK VIEW PERMISION FOR THE CURRENT USER AND THE REQUESTED EMAIL
function __getmail_checkperm($id) {
	$query="SELECT a.id FROM (SELECT a2.*,uc.email_privated email_privated FROM tbl_correo a2 LEFT JOIN tbl_usuarios_c uc ON a2.id_cuenta=uc.id) a LEFT JOIN tbl_registros_i e ON e.id_aplicacion='".page2id("correo")."' AND e.id_registro=a.id LEFT JOIN tbl_usuarios d ON e.id_usuario=d.id WHERE a.id='".abs($id)."' AND (TRIM(IFNULL(email_privated,0))='0' OR (TRIM(IFNULL(email_privated,0))='1' AND e.id_usuario='".current_user()."')) AND ".check_sql("correo","view");
	return execute_query($query);
}

// RETURN THE ORIGINAL RFC822 MESSAGE
function __getmail_getsource($id,$max=0) {
	$query="SELECT * FROM tbl_correo WHERE id='$id'";
	$row=execute_query($query);
	if(!$row) return "";
	$email="${row["id_cuenta"]}/${row["uidl"]}";
	$fext=getDefault("exts/emailext",".eml").getDefault("exts/gzipext",".gz");
	$file=($row["is_outbox"]?get_directory("dirs/outboxdir"):get_directory("dirs/inboxdir")).$email.$fext;
	if(!file_exists($file)) return "";
	$fp=gzopen($file,"r");
	$message="";
	if(!$max) $max=filesize($file)+1;
	while(!feof($fp) && strlen($message)<$max) $message.=gzread($fp,min(8192,$max-strlen($message)));
	if(!feof($fp)) $message.="\n...";
	gzclose($fp);
	return $message;
}
*/
// RETURN THE DECODED MIME STRUCTURE OF MESSAGE
function __getmail_getmime($id) 
{
	global $db, $conf;
	
	$sql="SELECT * FROM ".MAIN_DB_PREFIX."webmail_mail WHERE rowid='".$id."'";
	
	
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		if ($num)
		{
			$objp = $db->fetch_object($resql);
			$email=$objp->fk_user."/".$objp->uidl;
			$fext=getDefault("exts/emailext",".eml").getDefault("exts/gzipext",".gz");
			$cache=get_cache_file($objp,getDefault("exts/emailext",".eml"));
	
			if(!file_exists($cache))
			{
				$file=($objp->is_outbox?$conf->webmail->dir_output."/outbox/":$conf->webmail->dir_output."/inbox/").$email.$fext;
				if(!file_exists($file)) return "";
				$mime=new mime_parser_class;
				$decoded="";
				$mime->Decode(array("File"=>$file),$decoded);
				file_put_contents($cache,serialize($decoded));
				@chmod($cache,0666);
			}
			else
			{
				$decoded=unserialize(file_get_contents($cache));
			}
			return $decoded;
			
		}
		else
		{
			return "";
		}
		
	}
	else 
	{
		return "";	
	}
	
	
	$row=execute_query($query);
	if(!$row) return "";
	
}

// RETURN A NODE USING A XPATH NOTATION
function __getmail_getnode($path,$array) 
{
	if(!is_array($path)) $path=explode("/",$path);
	$elem=array_shift($path);
	if(!is_array($array) || !isset($array[$elem])) return null;
	if(count($path)==0) return $array[$elem];
	return __getmail_getnode($path,$array[$elem]);
}

// RETURN INTERNAL CONTENT TYPE
function __getmail_gettype($array) {
	$ctype=__getmail_getnode("Headers/content-type:",$array);
	if(!$ctype) $ctype="TEXT/PLAIN";
	if(is_array($ctype)) $ctype=$ctype[0]; // TO PREVENT ERRORS WHEN THE HEADER IS MALFORMED
	$ctype=strtoupper($ctype);
	if(strpos($ctype,"TEXT/HTML")!==false) $type="html";
	elseif(strpos($ctype,"TEXT/PLAIN")!==false) $type="plain";
	elseif(strpos($ctype,"MESSAGE/RFC822")!==false) $type="message";
	elseif(strpos($ctype,"MULTIPART/ALTERNATIVE")!==false) $type="alternative";
	elseif(strpos($ctype,"MULTIPART/")!==false) $type="multipart";
	else $type="other";
	return $type;
}

// RETURN INTERNAL DISPOSITION
function __getmail_getdisposition($array) {
	$cdisp=__getmail_getnode("Headers/content-disposition:",$array);
	if(!$cdisp) $cdisp="INLINE";
	if(is_array($cdisp)) $cdisp=$cdisp[0]; // TO PREVENT ERRORS WHEN THE HEADER IS MALFORMED
	$cdisp=strtoupper($cdisp);
	if(strpos($cdisp,"ATTACHMENT")!==false) $disp="attachment";
	elseif(strpos($cdisp,"INLINE")!==false) $disp="inline";
	else $disp="other";
	return $disp;
}

// RETURN THE UTF-8 CONVERTED STRING IF IT'S NEEDED
function __getmail_getutf8($temp) {
	if(!mb_check_encoding($temp,"UTF-8")) $temp=mb_convert_encoding($temp,"UTF-8");
	return $temp;
}

// FUNCTION THAT CONVERT HTML TO PLAIN TEXT
function __getmail_html2text($html) {
	dol_include_once("/webmail/lib/html2text/class.html2text.inc");
	$html=str_replace("$","",$html); // SECURITY FIX
	$h2t=new html2text($html);
	$text=$h2t->get_text();
	return $text;
}

// RETURN AN ARRAY OF ATTACHMENTS FILES
function __getmail_getfiles($array,$level=0) {
	$result=array();
	$disp=__getmail_getdisposition($array);
	$type=__getmail_gettype($array);
	if(__getmail_processfile($disp,$type)) {
		$temp=__getmail_getnode("Body",$array);
		if($temp) {
			$cid=__getmail_getnode("Headers/content-id:",$array);
			if(substr($cid,0,1)=="<") $cid=substr($cid,1);
			if(substr($cid,-1,1)==">") $cid=substr($cid,0,-1);
			$cname=__getmail_getutf8(__getmail_getnode("FileName",$array));
			$location=__getmail_getnode("Headers/content-location:",$array);
			if($cid=="" && $cname=="" && $location!="") $cid=$location;
			$ctype=__getmail_getnode("Headers/content-type:",$array);
			if(strpos($ctype,";")!==false) $ctype=strtok($ctype,";");
			if($cid=="" && $cname=="" && __getmail_processfile($disp,$type)) $cname=encode_bad_chars($ctype).getDefault("exts/defaultext",".dat");
			if($cname!="") {
				$csize=__getmail_getnode("BodyLength",$array);
				$hsize=__getmail_gethumansize($csize);
				$chash=md5(serialize(array(md5($temp),$cid,$cname,$ctype,$csize))); // MD5 INSIDE FOR MEMORY TRICK
				$result[]=array("disp"=>$disp,"type"=>$type,"ctype"=>$ctype,"cid"=>$cid,"cname"=>$cname,"csize"=>$csize,"hsize"=>$hsize,"chash"=>$chash,"body"=>$temp);
			}
		}
	} elseif(__getmail_processplainhtml($disp,$type)) {
		// THIS DATA IS USED BY THE NEXT TRICK
		$temp=__getmail_getnode("Body",$array);
		if($temp) {
			$temp=__getmail_getutf8($temp);
			$result[]=array("disp"=>$disp,"type"=>$type,"body"=>$temp);
		}
	} elseif(__getmail_processmessage($disp,$type)) {
		$temp=__getmail_getnode("Body",$array);
		if($temp) {
			$mime=new mime_parser_class;
			$decoded="";
			$mime->Decode(array("Data"=>$temp),$decoded);
			$result=array_merge($result,__getmail_getfiles(__getmail_getnode("0",$decoded),$level+1));
		}
	}
	$parts=__getmail_getnode("Parts",$array);
	if($parts) {
		foreach($parts as $index=>$node) {
			$result=array_merge($result,__getmail_getfiles($node,$level+1));
		}
	}
	if($level==0) {
		// TRICK TO REMOVE THE FILES THAT CONTAIN NAME AND CID
		foreach($result as $index=>$node) {
			$disp=$node["disp"];
			$type=$node["type"];
			if(__getmail_processplainhtml($disp,$type)) {
				$temp=$node["body"];
				foreach($result as $index2=>$node2) {
					$disp2=$node2["disp"];
					$type2=$node2["type"];
					if(__getmail_processfile($disp2,$type2)) {
						$cid2=$node2["cid"];
						if($cid2!="") if(strpos($temp,"cid:${cid2}")!==false) unset($result[$index2]);
					}
				}
				unset($result[$index]);
			}
		}
	}
	return $result;
}

// RETURN THE HUMAN SIZE (GBYTES, MBYTES, KBYTES OR BYTES)
function __getmail_gethumansize($size) {
	if($size>1073741824) $size=round($size/1073741824,2)." Gbytes";
	elseif($size>1048576) $size=round($size/1048576,2)." Mbytes";
	elseif($size>1024) $size=round($size/1024,2)." Kbytes";
	else $size=$size." bytes";
	return $size;
}

// RETURN ALL INFORMATION OF THE DECODED MESSAGE
function __getmail_getinfo($array) {
	if(eval_bool(getDefault("debug/getmaildebug"))) echo "<pre>".sprintr(__getmail_removebody($array))."</pre>";
	$result=array("emails"=>array(),"datetime"=>"","subject"=>"","spam"=>"","files"=>array(),"crt"=>0,
		"priority"=>0,"sensitivity"=>0,"from"=>"","to"=>"","cc"=>"","bcc"=>"");
	// CREATE THE FROM, TO, CC AND BCC STRING
	$lista=array(1=>"from",2=>"to",3=>"cc",4=>"bcc",5=>"return-path",6=>"reply-to",7=>"disposition-notification-to");
	foreach($lista as $key=>$val) {
		$addresses=__getmail_getnode("ExtractedAddresses/${val}:",$array);
		if($addresses) {
			$temp=array();
			foreach($addresses as $a) {
				$name=__getmail_getutf8(__getmail_getnode("name",$a));
				$addr=__getmail_getutf8(__getmail_getnode("address",$a));
				$result["emails"][]=array("id_tipo"=>$key,"tipo"=>$val,"nombre"=>$name,"valor"=>$addr);
				$temp[]=($name!="")?$name."<".$addr.">":$addr;
			}
			$temp=implode("; ",$temp);
			if(array_key_exists($val,$result)) $result[$val]=$temp;
		}
	}
	// CREATE THE DATETIME STRING
	$datetime=__getmail_getnode("Headers/date:",$array);
	if(is_array($datetime)) $datetime=$datetime[0]; // TO PREVENT ERRORS WHEN THE HEADER IS MALFORMED
	if($datetime && strpos($datetime,"(")!==false) $datetime=strtok($datetime,"(");
	if($datetime) $result["datetime"]=date("Y-m-d H:i:s",strtotime($datetime));
	if(!$datetime) $result["datetime"]=dol_now();
	// CREATE THE SUBJECT STRING
	$subject=__getmail_getnode("DecodedHeaders/subject:/0/0/Value",$array);
	if(!$subject) {
		$subject=__getmail_getnode("Headers/subject:",$array);
	}
	if($subject) {
		if(is_array($subject)) $subject=$subject[0]; // TO PREVENT ERRORS WHEN THE HEADER IS MALFORMED
		$subject=__getmail_getutf8($subject);
		$result["subject"]=$subject;
	}
	// CHECK X-SPAM-STATUS HEADER
	$spam=__getmail_getnode("Headers/x-spam-status:",$array);
	if(is_array($spam)) $spam=$spam[0]; // TO PREVENT ERRORS WHEN THE HEADER IS MALFORMED
	$spam=strtoupper(trim($spam));
	$result["spam"]=(substr($spam,0,3)=="YES" || substr($spam,-3,3)=="YES")?"1":"0";
	// GET THE NUMBER OF ATTACHMENTS
	$result["files"]=__getmail_getfiles($array);
	// GET THE CRT IF EXISTS
	foreach($result["emails"] as $email) if($email["id_tipo"]==7) $result["crt"]=1;
	// GET THE PRIORITY IF EXISTS
	$priority=strtolower(__getmail_getnode("Headers/x-priority:",$array));
	$priorities=array("low"=>5,"high"=>1);
	if(isset($priorities[$priority])) $priority=$priorities[$priority];
	$priority=intval($priority);
	$priorities=array(5=>-1,4=>-1,3=>0,2=>1,1=>1);
	if(isset($priorities[$priority])) $result["priority"]=$priorities[$priority];
	// GET THE SENSITIVITY IF EXISTS
	$sensitivity=strtolower(__getmail_getnode("Headers/sensitivity:",$array));
	$sensitivities=array("personal"=>1,"private"=>2,"company-confidential"=>3,"company confidential"=>3);
	if(isset($sensitivities[$sensitivity])) $result["sensitivity"]=$sensitivities[$sensitivity];
	// RETURN THE RESULT
	if(eval_bool(getDefault("debug/getmaildebug"))) $result["body"]=__getmail_gettextbody($array);
	if(eval_bool(getDefault("debug/getmaildebug"))) echo "<pre>".sprintr($result)."</pre>";
	if(eval_bool(getDefault("debug/getmaildebug"))) die();
	return $result;
}

// RETURN ALL TEXT BODY CONCATENATED
function __getmail_gettextbody($array,$level=0) {
	$result=array();
	$disp=__getmail_getdisposition($array);
	$type=__getmail_gettype($array);
	if(__getmail_processplainhtml($disp,$type)) {
		$temp=__getmail_getnode("Body",$array);
		if($temp) {
			//if($type=="html") $temp=__getmail_html2text($temp);
			$temp=__getmail_getutf8($temp);
			$result[]=array("type"=>$type,"body"=>$temp);
		}
	} elseif(__getmail_processmessage($disp,$type)) {
		$temp=__getmail_getnode("Body",$array);
		if($temp) {
			$mime=new mime_parser_class;
			$decoded="";
			$mime->Decode(array("Data"=>$temp),$decoded);
			$result[]=array("type"=>$type,"body"=>__getmail_gettextbody(__getmail_getnode("0",$decoded)));
		}
	}
	$parts=__getmail_getnode("Parts",$array);
	if($parts) {
		$recursive=array();
		foreach($parts as $index=>$node) {
			$recursive=array_merge($recursive,__getmail_gettextbody($node,$level+1));
		}
		if($type=="alternative") {
			$count_plain=0;
			$count_html=0;
			foreach($recursive as $index=>$node) {
				if($node["type"]=="plain") $count_plain++;
				elseif($node["type"]=="html") $count_html++;
			}
			if($count_plain==1 && $count_html==1) {
				foreach($recursive as $index=>$node) {
					if($node["type"]=="plain") break;
				}
				unset($recursive[$index]);
			}
		}
		$result=array_merge($result,$recursive);
	}
	if($level==0) {
		foreach($result as $index=>$node) {
			$result[$index]=$node["body"];
		}
		$result=implode("\n",$result);
	}
	return $result;
}

// RETURN ALL BODY AND ATTACHMENTS INFORMATION
function __getmail_getfullbody($array) {
	$result=array();
	$disp=__getmail_getdisposition($array);
	$type=__getmail_gettype($array);
	if(__getmail_processplainhtml($disp,$type)) {
		$temp=__getmail_getnode("Body",$array);
		if($temp) {
			$temp=__getmail_getutf8($temp);
			$result[]=array("disp"=>$disp,"type"=>$type,"body"=>$temp);
		}
	} elseif(__getmail_processmessage($disp,$type)) {
		$temp=__getmail_getnode("Body",$array);
		if($temp) {
			$mime=new mime_parser_class;
			$decoded="";
			$mime->Decode(array("Data"=>$temp),$decoded);
			$result=array_merge($result,__getmail_getfullbody(__getmail_getnode("0",$decoded)));
		}
	} else {
		$temp=__getmail_getnode("Body",$array);
		if($temp) {
			$cid=__getmail_getnode("Headers/content-id:",$array);
			if(substr($cid,0,1)=="<") $cid=substr($cid,1);
			if(substr($cid,-1,1)==">") $cid=substr($cid,0,-1);
			$cname=__getmail_getutf8(__getmail_getnode("FileName",$array));
			$location=__getmail_getnode("Headers/content-location:",$array);
			if($cid=="" && $cname=="" && $location!="") $cid=$location;
			$ctype=__getmail_getnode("Headers/content-type:",$array);
			if(strpos($ctype,";")!==false) $ctype=strtok($ctype,";");
			if($cid=="" && $cname=="" && __getmail_processfile($disp,$type)) $cname=encode_bad_chars($ctype).getDefault("exts/defaultext",".dat");
			if($cid!="" || $cname!="") {
				$csize=__getmail_getnode("BodyLength",$array);
				$hsize=__getmail_gethumansize($csize);
				$chash=md5(serialize(array(md5($temp),$cid,$cname,$ctype,$csize))); // MD5 INSIDE FOR MEMORY TRICK
				$result[]=array("disp"=>$disp,"type"=>$type,"ctype"=>$ctype,"cid"=>$cid,"cname"=>$cname,"csize"=>$csize,"hsize"=>$hsize,"chash"=>$chash,"body"=>$temp);
			}
		}
	}
	$parts=__getmail_getnode("Parts",$array);
	if($parts) {
		$recursive=array();
		foreach($parts as $index=>$node) {
			$recursive=array_merge($recursive,__getmail_getfullbody($node));
		}
		if($type=="alternative") {
			$count_plain=0;
			$count_html=0;
			foreach($recursive as $index=>$node) {
				if($node["type"]=="plain") $count_plain++;
				elseif($node["type"]=="html") $count_html++;
			}
			if($count_plain==1 && $count_html==1) {
				foreach($recursive as $index=>$node) {
					if($node["type"]=="plain") break;
				}
				unset($recursive[$index]);
			}
		}
		$result=array_merge($result,$recursive);
	}
	return $result;
}

// RETURN THE ATTACHMENT REQUESTED
function __getmail_getcid($array,$hash) 
{
	$disp=__getmail_getdisposition($array);
	$type=__getmail_gettype($array);
	if(__getmail_processmessage($disp,$type)) 
	{
		$temp=__getmail_getnode("Body",$array);
		if($temp) 
		{
			$mime=new mime_parser_class;
			$decoded="";
			$mime->Decode(array("Data"=>$temp),$decoded);
			$result=__getmail_getcid(__getmail_getnode("0",$decoded),$hash);
			if($result) return $result;
		}
	}
	else
	{
		$temp=__getmail_getnode("Body",$array);
		if($temp)
		{
			$cid=__getmail_getnode("Headers/content-id:",$array);
			if(substr($cid,0,1)=="<") $cid=substr($cid,1);
			if(substr($cid,-1,1)==">") $cid=substr($cid,0,-1);
			$cname=__getmail_getutf8(__getmail_getnode("FileName",$array));
			$location=__getmail_getnode("Headers/content-location:",$array);
			if($cid=="" && $cname=="" && $location!="") $cid=$location;
			$ctype=__getmail_getnode("Headers/content-type:",$array);
			if(strpos($ctype,";")!==false) $ctype=strtok($ctype,";");
			if($cid=="" && $cname=="" && __getmail_processfile($disp,$type)) $cname=encode_bad_chars($ctype).getDefault("exts/defaultext",".dat");
			$csize=__getmail_getnode("BodyLength",$array);
			$chash=md5(serialize(array(md5($temp),$cid,$cname,$ctype,$csize))); // MD5 INSIDE FOR MEMORY TRICK
			if($chash==$hash)
			{
				$hsize=__getmail_gethumansize($csize);
				return array("disp"=>$disp,"type"=>$type,"ctype"=>$ctype,"cid"=>$cid,"cname"=>$cname,"csize"=>$csize,"hsize"=>$hsize,"chash"=>$chash,"body"=>$temp);
			}
		}
	}
	$parts=__getmail_getnode("Parts",$array);
	if($parts)
	{
		foreach($parts as $index=>$node)
		{
			$result=__getmail_getcid($node,$hash);
			if($result) return $result;
		}
	}
	return null;
}

function __getmail_insert($file,$messageid,$state_new,$state_reply,$state_forward,$state_wait,$id_correo,$is_outbox,$state_sent,$state_error)
{
	global $db,$conf;	
		
	list($id_cuenta,$uidl)=explode("/",$messageid);
	$size=gzfilesize($file);

	$datetime=dol_now();
	
	// DECODE THE MESSAGE
	$mime=new mime_parser_class;
	$decoded="";
	$mime->Decode(array("File"=>$file),$decoded);
	$fk_soc=0;
	$fk_contact=0;
	
	$info=__getmail_getinfo(__getmail_getnode("0",$decoded));
	$body=__getmail_gettextbody(__getmail_getnode("0",$decoded));
	// INSERT THE NEW EMAIL
	$lista=array("from","to","cc","bcc","subject");
	foreach($lista as $key=>$val) $info[$val]=addslashes($info[$val]);
	$body=addslashes($body);
	$files=count($info["files"]);
	
	$typemail = search_sender($info["from"]);
		
	if(is_array($typemail))
	{	
		switch ($typemail['type']) 
		{
    		case "Third":
    			$fk_soc = $typemail['id'];
       			break;
    		case "Contact":
    			$fk_contact= $typemail['id'];
    			if ($typemail['fk_soc']) 
    			{
    				$fk_soc=$typemail['fk_soc'];
    			}
    			break;
		}
	}
	
	
	$sql="INSERT INTO ".MAIN_DB_PREFIX."webmail_mail(`rowid`,`fk_user`, `entity`, `fk_soc`,`fk_contact`,`uidl`,`size`,`datetime`,`subject`,`body`,`state_new`,`state_reply`,`state_forward`,`state_wait`,`state_spam`,`id_correo`,`is_outbox`,`state_sent`,`state_error`,`state_crt`,`priority`,`sensitivity`,`from`,`to`,`cc`,`bcc`,`files`) VALUES(NULL,'${id_cuenta}',".$conf->entity." , '${fk_soc}','${fk_contact}','${uidl}','${size}','${info["datetime"]}','${info["subject"]}','${body}','${state_new}','${state_reply}','${state_forward}','${state_wait}','${info["spam"]}','${id_correo}','${is_outbox}','${state_sent}','${state_error}','${info["crt"]}','${info["priority"]}','${info["sensitivity"]}','${info["from"]}','${info["to"]}','${info["cc"]}','${info["bcc"]}','${files}')";
	
	$resql=$db->query($sql);
	if ($resql)
    {
		$query="SELECT MAX(rowid) as rowid FROM ".MAIN_DB_PREFIX."webmail_mail WHERE fk_user='${id_cuenta}' AND is_outbox='${is_outbox}' AND entity=".$conf->entity;
		$result= $db->query($query);
		$obj = $db->fetch_object($result);
		
        if ($obj)
        {
        	$last_id=$obj->rowid;
        }
     	
    }

    /*
	// INSERT ALL ADDRESS
	foreach($info["emails"] as $email) 
	{
		$email["nombre"]=addslashes($email["nombre"]);
		$email["valor"]=addslashes($email["valor"]);
		$query="INSERT INTO tbl_correo_a(`id`,`id_correo`,`id_tipo`,`nombre`,`valor`) VALUES(NULL,'${last_id}','${email["id_tipo"]}','${email["nombre"]}','${email["valor"]}')";
		db_query($query);
	}
	*/
    
	// INSERT ALL ATTACHMENTS
	foreach($info["files"] as $file)
	{
		$fichero=addslashes($file["cname"]);
		$fichero_file=$file["chash"];
		$fichero_size=$file["csize"];
		$fichero_type=$file["ctype"];
		$search=addslashes(encode_search(unoconv2txt(array("data"=>$file["body"],"ext"=>strtolower(extension($file["cname"]))))," "));
		$sql="INSERT INTO ".MAIN_DB_PREFIX."webmail_files (rowid,fk_mail,fk_user,`datetime`,`file_name`,`file`,`file_size`,`file_type`,`search`) VALUES(NULL,'${last_id}','${id_cuenta}','${datetime}','${fichero}','${fichero_file}','${fichero_size}','${fichero_type}','${search}')";
		$db->query($sql);
	}
	return $last_id;
}

function __getmail_update($campo,$valor,$id) {
	$valor=addslashes($valor);
	$query="UPDATE ".MAIN_DB_PREFIX."webmail_mail SET `${campo}`='${valor}' WHERE rowid='${id}'";
	db_query($query);
}

// FOR SOME HREF REPLACEMENTS
function __getmail_href_replace($temp) {
	// REPLACE THE INTERNALS LINKS TO OPENCONTENT CALLS
	$orig="href='".get_base();
	$dest=str_replace("href=","__href__=",$orig);
	$onclick="onclick='parent.opencontent(this.href);return false' ";
	$orig=array($orig,str_replace("'",'"',$orig),str_replace("'",'',$orig));
	$dest=array($onclick.$dest,$onclick.str_replace("'",'"',$dest),$onclick.str_replace("'",'',$dest));
	$temp=str_replace($orig,$dest,$temp);
	// REPLACE THE MAILTO LINKS TO MAILTO CALLS
	$orig="href='mailto:";
	$dest=str_replace("href=","__href__=",$orig);
	$onclick="onclick='parent.mailto(parent.substr(this.href,7));return false' ";
	$orig=array($orig,str_replace("'",'"',$orig),str_replace("'",'',$orig));
	$dest=array($onclick.$dest,$onclick.str_replace("'",'"',$dest),$onclick.str_replace("'",'',$dest));
	$temp=str_replace($orig,$dest,$temp);
	// REPLACE THE REST OF LINKS TO OPENWIN CALLS
	$orig="href='";
	$dest=str_replace("href=","__href__=",$orig);
	$onclick="onclick='parent.openwin(this.href);return false' ";
	$orig=array($orig,str_replace("'",'"',$orig),str_replace("'",'',$orig));
	$dest=array($onclick.$dest,$onclick.str_replace("'",'"',$dest),$onclick.str_replace("'",'',$dest));
	$temp=str_replace($orig,$dest,$temp);
	// RESTORE THE __HREF__= TO HREF=
	$temp=str_replace("__href__=","href=",$temp);
	return $temp;
}

// FOR RAWURLDECODE AUTO DETECTION
function __getmail_rawurldecode($temp) {
	if(strpos($temp,"%20")!==false) $temp=rawurldecode($temp);
	return $temp;
}

// USING WORDPRESS FEATURES
function __getmail_make_clickable($temp) {
	global $allowedentitynames;
	require_once("lib/wordpress/wordpress.php");
	$temp=make_clickable($temp);
	return $temp;
}

//Outils
function getDefault($key,$default="") {
	global $_CONFIG;
	$key=explode("/",$key);
	$count=count($key);
	$config=$_CONFIG;
	if($count==1 && isset($config["default"][$key[0]])) {
		$config=$config["default"][$key[0]];
		$count=0;
	}
	while($count) {
		$key2=array_shift($key);
		if(!isset($config[$key2])) return $default;
		$config=$config[$key2];
		$count--;
	}
	if($config==="") return $default;
	return $config;
}

function gzfilesize($filename) {
	$gzfs = FALSE;
	if(($zp = fopen($filename, 'r'))!==FALSE) {
		if(@fread($zp, 2) == "\x1F\x8B") { // this is a gzip'd file
			fseek($zp, -4, SEEK_END);
			if(strlen($datum = @fread($zp, 4))==4)
				extract(unpack('Vgzfs', $datum));
		}
		else // not a gzip'd file, revert to regular filesize function
			$gzfs = filesize($filename);
		fclose($zp);
	}
	return($gzfs);
}

function eval_bool($arg) {
	static $bools=array(
		"1"=>1, // FOR 1 OR TRUE
		"0"=>0, // FOR 0
		""=>0, // FOR FALSE
		"true"=>1,
		"false"=>0,
		"on"=>1,
		"off"=>0,
		"yes"=>1,
		"no"=>0
	);
	$bool=strtolower($arg);
	if(isset($bools[$bool])) return $bools[$bool];
	xml_error("Unknown boolean value '$arg'");
}

function encode_bad_chars_file($file) {
	$file=strrev($file);
	$file=explode(".",$file,2);
	// EXISTS MULTIPLE STRREV TO PREVENT UTF8 DATA LOST
	foreach($file as $key=>$val) $file[$key]=strrev(encode_bad_chars(strrev($val)));
	$file=implode(".",$file);
	$file=strrev($file);
	return $file;
}

function encode_bad_chars($cad,$pad="_") {
	static $orig=array(
		"á","à","ä","é","è","ë","í","ì","ï","ó","ò","ö","ú","ù","ü","ñ","ç",
		"Á","À","Ä","É","È","Ë","Í","Ì","Ï","Ó","Ò","Ö","Ú","Ù","Ü","Ñ","Ç");
	static $dest=array(
		"a","a","a","e","e","e","i","i","i","o","o","o","u","u","u","n","c",
		"a","a","a","e","e","e","i","i","i","o","o","o","u","u","u","n","c");
	$cad=str_replace($orig,$dest,$cad);
	$cad=strtolower($cad);
	$len=strlen($cad);
	for($i=0;$i<$len;$i++) {
		$letter=$cad[$i];
		$replace=1;
		if($letter>="a" && $letter<="z") $replace=0;
		if($letter>="0" && $letter<="9") $replace=0;
		if($replace) $cad[$i]=" ";
	}
	$cad=encode_words($cad,$pad);
	return $cad;
}

function encode_words($cad,$pad=" ") {
	$cad=trim($cad);
	$count=1;
	while($count) $cad=str_replace("  "," ",$cad,$count);
	$cad=str_replace(" ",$pad,$cad);
	return $cad;
}

function encode_search($cad,$pad=" ") {
	static $bad_chars=null;
	if($bad_chars===null) for($i=0;$i<32;$i++) $bad_chars[]=chr($i);
	$cad=str_replace($bad_chars,$pad,$cad);
	$cad=encode_words($cad,$pad);
	return $cad;
}

function extension($file) {
	return pathinfo($file,PATHINFO_EXTENSION);
}

function get_directory($key,$default="") {
	$default=$default?$default:getcwd()."/cache";
	$dir=getDefault($key,$default);
	$bar=(substr($dir,-1,1)!="/")?"/":"";
	return $dir.$bar;
}

function get_cache_file($data,$ext="") 
{
	global $conf;
	
	if(is_array($data)) $data=serialize($data);
	if($ext=="") $ext=strtolower(extension($data));
	if($ext=="") $ext=getDefault("exts/defaultext",".dat");
	if(substr($ext,0,1)!=".") $ext=".".$ext;
	$dir=  $conf->webmail->dir_output."/cache/" ;
	$file=$dir.md5($data->uidl).$ext;
	return $file;
}

function get_temp_file($ext="") {
	global $conf;
	
	if($ext=="") $ext=getDefault("exts/defaultext",".dat");
	if($ext[0]!=".") $ext=".".$ext;
	//$dir= get_directory("dirs/cachedir");
	
	$dir = $conf->webmail->dir_output."/cache/";
	
	while(1) {
		$uniqid=get_unique_id_md5();
		$file=$dir.$uniqid.$ext;
		if(!file_exists($file)) break;
	}
	return $file;
}

function init_random() {
	static $init=false;
	if($init) return;
	srand((float)microtime(true)*1000000);
	$init=true;
}

function get_unique_id_md5() {
	init_random();
	return md5(uniqid(rand(),true));
}

function search_sender($sender)
{
	global $db;
	
	$typemail = array();
	
	//get e-mail sender
	
	$initpos =strpos($sender, "<");
	$endpos=strpos($sender, ">");
	$lenght= $endpos-$initpos-1;
	
	if($initpos)
	{
		$temp = substr ( $sender , $initpos+1, $lenght);
	}
	else 
	{
		$temp=$sender;
	}
	$sql = "SELECT rowid, nom as name";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe";
	$sql.= " WHERE email='".$temp."'";
	$resql = $db->query($sql);
	
	if ($resql)
	{
		$num = $db->num_rows($resql);
	}
	
	if ($num)
	{
		$obj = $db->fetch_object($resql);
		
		$typemail['type']	=	'Third';
		$typemail['id']		=	$obj->rowid;
		$typemail['name']	=	$obj->name;
		
	}
	else 
	{
		$sql = "SELECT rowid, lastname, firstname, fk_soc";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
		$sql.= " WHERE email='".$temp."'";
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);	

		}
		
		if ($num)
		{
			$obj = $db->fetch_object($resql);
			
			$typemail['type']	=	'Contact';
			$typemail['id']		=	$obj->rowid;
			$typemail['firstname']	=	$obj->firstname;
			$typemail['lastname']	=	$obj->lastname;
			$typemail['fk_soc']	=	$obj->fk_soc;
		}
		else 
		{
			$sql = "SELECT rowid, lastname, firstname";
			$sql.= " FROM ".MAIN_DB_PREFIX."user";
			$sql.= " WHERE email='".$temp."'";
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);	
			}
		
			if ($num)
			{
				$obj = $db->fetch_object($resql);
			
				$typemail['type']	=	'User';
				$typemail['id']		=	$obj->rowid;
				$typemail['firstname']	=	$obj->firstname;
				$typemail['lastname']	=	$obj->lastname;
			}
			else 
			{
				return false;
			}
		}
	}
	return $typemail;
}
function select_mail_statut($selected)
{
	global $langs;
	
	print '<select class="flat" name="mail_statut">';
	print '<option value="">&nbsp;</option>';
	$num = 4;
	$i = 0;
	while ($i < $num)
	{
                  
		if ($selected == $i)
		{
			print '<option value="'.$i.'" selected="selected">';
		}
		else
		{
			print '<option value="'.$i.'">';
		}
                    
		print $langs->trans("MailStatus".$i);
                
		print '</option>';
		$i++;
	}
	print '</select>';
}
	
function LibStatut($statut)
{
	global $langs;
	$langs->load("webmail@webmail");
	
	if ($statut==1)//Leido
	{
		return img_picto($langs->trans('MailStatus'.$statut),'statut8').' '.$langs->trans("MailStatus".$statut);
	}
	if ($statut==0)//No leido
	{
		return img_picto($langs->trans('MailStatus'.$statut),'statut6').' '.$langs->trans("MailStatus".$statut);
	}
	if ($statut==2)//Reply
	{
		return img_picto($langs->trans('MailStatus'.$statut),'statut4').' '.$langs->trans("MailStatus".$statut);
	}
	if ($statut==3)//Spam
	{
		return img_picto($langs->trans('MailStatus'.$statut),'statut5').' '.$langs->trans("MailStatus".$statut);
	}
	if ($statut==4)//Send
	{
		return img_picto($langs->trans('MailStatus'.$statut),'statut4').' '.$langs->trans("MailStatus".$statut);
	}
}

function LibAttach($files)
{
	global $langs;
	$langs->load("webmail@webmail");
	
	return img_picto($langs->trans('MailStatus'.$statut),'statut'.$statut).' '.$langs->trans("MailStatus".$statut);
}

function select_mail_receiver($selected='',$htmlname='socid',$filter='',$showempty=0, $btnadd=false, $showtype=0, $event=array(), $filterkey='', $outputmode=0, $limit=20)
{
	global $conf,$user,$langs,$db;

	$out='';
	
	if ($btnadd)
	{
		$out.= '<form method="get" action="'.$page.'">';
		$out.='<input type="hidden" name="action" value="addmail">';
		$out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		//$out.= '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		//$out.= '<tr><td>';
	}
	$outarray=array();

	// Search	
	$sql = "SELECT 'contacto', c.rowid, concat_ws(' ', firstname, lastname) as nom, c.email, s.nom as empresa";
	$sql.= " FROM ".MAIN_DB_PREFIX ."socpeople AS c";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX ."societe AS s ON c.fk_soc=s.rowid";
	if (!$user->rights->societe->client->voir && ! empty($conf->global->WEBMAIL_EMAIL_FILTER))
	{
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX ."societe_commerciaux sc ON sc.fk_soc = s.rowid";
	}
	$sql.= " WHERE c.email <>'' AND c.entity=".$conf->entity;
	
	if (!$user->rights->societe->client->voir && ! empty($conf->global->WEBMAIL_EMAIL_FILTER))
	{
		$sql.= " AND sc.fk_user=".$user->id;
	}
	
	$sql.=" UNION"; 
	$sql.= " SELECT 'tercero',soc.rowid, soc.nom, soc.email, soc.nom as empresa";
	$sql.= " FROM ".MAIN_DB_PREFIX ."societe as soc";
	
	if (!$user->rights->societe->client->voir && ! empty($conf->global->WEBMAIL_EMAIL_FILTER))
	{
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX ."societe_commerciaux scs ON scs.fk_soc = soc.rowid";
	}
	
	$sql.= " WHERE soc.email <>'' AND soc.entity=".$conf->entity;
	if (!$user->rights->societe->client->voir && ! empty($conf->global->WEBMAIL_EMAIL_FILTER))
	{
		$sql.= " AND scs.fk_user=".$user->id;
	}
	
	$sql.=" UNION"; 
	
	$sql.= " SELECT 'usuario', u.rowid, concat_ws(' ', firstname, lastname) as nom, u.email, 'interno' as empresa";
	$sql.= " FROM ".MAIN_DB_PREFIX ."user as u";
	$sql.= " WHERE u.email <>''";
	$sql.= " AND entity IN (".getEntity('user',1).")";
	        
	if ($filter) $sql.= " AND (".$filter.")";
        //$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if (! empty($conf->global->COMPANY_HIDE_INACTIVE_IN_COMBOBOX)) $sql.= " AND s.status<>0 ";
        
        // Add criteria
	if ($filterkey && $filterkey != '')
	{
		$sql.=" AND (";
		if (! empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE))   // Can use index
		{
			$sql.="(nom OR email LIKE '".$filterkey."%'";
			$sql.=")";
		}
		else
		{
			// For natural search
			$scrit = explode(' ', $filterkey);
			foreach ($scrit as $crit) 
			{
				$sql.=" AND (nom OR email LIKE '%".$crit."%'";
				$sql.=")";
			}
		}
        	
		$sql.=")";
	}
	$sql.= " ORDER BY nom ASC";

	$resql=$db->query($sql);
	if ($resql)
	{
		if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT && ! $forcecombo)
		{
			//$minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);
			$out.= ajax_combobox($htmlname, $event, $conf->global->COMPANY_USE_SEARCH_TO_SELECT);
		}

		// Construct $out and $outarray
		$out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">'."\n";
		if ($showempty) $out.= '<option value="-1"></option>'."\n";
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$label='';
				if($obj->contacto=='contacto' && $obj->empresa)
				{
					$label=$obj->nom. ' ('.$obj->empresa.') - '.$obj->email;
				}
				else
				{
					$label=$obj->nom.' - '.$obj->email;	
				}

				if ($selected > 0 && $selected == substr($obj->contacto, 0, 1).$obj->rowid)
				{
					$out.= '<option value="'.substr($obj->contacto, 0, 1).$obj->rowid.'" selected="selected">'.$label.'</option>';
				}
				else
				{
					$out.= '<option value="'.substr($obj->contacto, 0, 1).$obj->rowid.'">'.$label.'</option>';
				}

				array_push($outarray, array('key'=>substr($obj->contacto, 0, 1).$obj->rowid, 'value'=>substr($obj->contacto, 0, 1).$obj->nom, 'label'=>$obj->nom));

				$i++;
				if (($i % 10) == 0) $out.="\n";
			}
		}
		$out.= '</select>'."\n";
		
		if ($btnadd)
		{
			//$out.=  '</td>';
            $out.= '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
            //$out.= '</tr></table></form>';
            $out.= '</form>';
		}
	}
	else
	{
		dol_print_error($db);
	}

	if ($outputmode) return $outarray;
	return $out;
}


/*
 * Uconf
 */

function __unoconv_pre($array) {
	if(isset($array["input"])) {
		$input=$array["input"];
	} elseif(isset($array["data"]) && isset($array["ext"])) {
		$input=get_temp_file($array["ext"]);
		file_put_contents($input,$array["data"]);
	} else {
		//show_php_error(array("phperror"=>"Call to unoconv without valid input"));
	}
	if(isset($array["output"])) {
		$output=$array["output"];
	} else {
		$output=get_temp_file(getDefault("exts/outputext",".out"));
	}
	$type=content_type($input);
	$ext=strtolower(extension($input));
	$type0=strtok($type,"/");
	return array($input,$output,$type,$ext,$type0);
}

function __unoconv_post($array,$input,$output) {
	if(!isset($array["input"])) {
		unlink($input);
	}
	if(!isset($array["output"]) && file_exists($output)) {
		$result=file_get_contents($output);
		unlink($output);
	} else {
		$result="";
	}
	return $result;
}

function __unoconv_list() {
	if(!check_commands(getDefault("commands/unoconv"),60)) return array();
	$abouts=ob_passthru(getDefault("commands/unoconv")." ".getDefault("commands/__unoconv_about__"),60);
	$abouts=explode("\n",$abouts);
	$exts=array();
	foreach($abouts as $about) {
		$pos1=strpos($about,"[");
		$pos2=strpos($about,"]");
		if($pos1!==false && $pos2!==false) {
			$ext=substr($about,$pos1+1,$pos2-$pos1-1);
			if($ext[0]==".") $ext=substr($ext,1);
			if(!in_array($ext,$exts)) $exts[]=$ext;
		}
	}
	return $exts;
}

function unoconv2pdf($array) {
	list($input,$output,$type,$ext,$type0)=__unoconv_pre($array);
	if($type=="application/pdf") {
		copy($input,$output);
	} elseif((in_array($ext,__unoconv_list()) && !in_array($type,array("audio","video"))) || in_array($type0,array("text","message"))) {
		if(check_commands(getDefault("commands/unoconv"),60)) {
			ob_passthru(getDefault("commands/unoconv")." ".str_replace(array("__INPUT__","__OUTPUT__"),array($input,$output),getDefault("commands/__unoconv__")));
		}
	}
	return __unoconv_post($array,$input,$output);
}

function __unoconv_getutf8($temp) {
	//require_once("php/getmail.php");
	return __getmail_getutf8($temp);
}

function __unoconv_html2text($temp) {
	//require_once("php/getmail.php");
	return __getmail_html2text($temp);
}

function unoconv2txt($array) {
	list($input,$output,$type,$ext,$type0)=__unoconv_pre($array);
	if($type=="text/plain") {
		copy($input,$output);
	} elseif($type=="text/html") {
		file_put_contents($output,__unoconv_html2text(file_get_contents($input)));
	} elseif($type=="application/pdf") {
		if(check_commands(getDefault("commands/pdftotext"),60)) {
			ob_passthru(getDefault("commands/pdftotext")." ".str_replace(array("__INPUT__","__OUTPUT__"),array($input,$output),getDefault("commands/__pdftotext__")));
		}
	} elseif((in_array($ext,__unoconv_list()) && !in_array($type0,array("image","audio","video"))) || in_array($type0,array("text","message"))) {
		if(check_commands(array(getDefault("commands/unoconv"),getDefault("commands/pdftotext")),60)) {
			$temp=get_temp_file(getDefault("exts/pdfext",".pdf"));
			ob_passthru(getDefault("commands/unoconv")." ".str_replace(array("__INPUT__","__OUTPUT__"),array($input,$temp),getDefault("commands/__unoconv__")));
			if(file_exists($temp)) {
				ob_passthru(getDefault("commands/pdftotext")." ".str_replace(array("__INPUT__","__OUTPUT__"),array($temp,$output),getDefault("commands/__pdftotext__")));
				unlink($temp);
			}
		}
	}
	if(file_exists($output)) {
		$temp=file_get_contents($output);
		$temp=__unoconv_getutf8($temp);
		file_put_contents($output,$temp);
	}
	return __unoconv_post($array,$input,$output);
}

function content_type($file) {
	static $mimes=array(
		"css"=>"text/css",
		"js"=>"text/javascript",
		"xml"=>"text/xml",
		"htm"=>"text/html",
		"png"=>"image/png",
		"bmp"=>"image/bmp"
	);
	$ext=strtolower(extension($file));
	if(isset($mimes[$ext])) return $mimes[$ext];
	if(function_exists("mime_content_type")) return mime_content_type($file);
	if(function_exists("finfo_file")) return finfo_file(finfo_open(FILEINFO_MIME_TYPE),$file);
	return "application/octet-stream";
}

function check_commands($commands,$expires=0) {
	if(!is_array($commands)) $commands=explode(",",$commands);
	$result=1;
	foreach($commands as $command) $result&=ob_passthru(getDefault("commands/which")." ".str_replace(array("__INPUT__"),array($command),getDefault("commands/__which__")),$expires)?1:0;
	return $result;
}

function ob_passthru($cmd,$expires=0) {
	static $disableds_string=null;
	static $disableds_array=array();
	if($expires) {
		$cache=get_cache_file($cmd,getDefault("exts/outputext",".out"));
		list($mtime,$error)=filemtime_protected($cache);
		if(file_exists($cache) && !$error && time()-$expires<$mtime) return file_get_contents($cache);
	}
	if($disableds_string===null) {
		$disableds_string=ini_get("disable_functions");
		$disableds_array=$disableds_string?explode(",",$disableds_string):array();
		foreach($disableds_array as $key=>$val) $disableds_array[$key]=strtolower(trim($val));
	}
	if(!in_array("passthru",$disableds_array)) {
		ob_start();
		passthru($cmd);
		$buffer=ob_get_clean();
	} elseif(!in_array("system",$disableds_array)) {
		ob_start();
		system($cmd);
		$buffer=ob_get_clean();
	} elseif(!in_array("exec",$disableds_array)) {
		$buffer=array();
		exec($cmd,$buffer);
		$buffer=implode("\n",$buffer);
	} elseif(!in_array("shell_exec",$disableds_array)) {
		ob_start();
		$buffer=shell_exec($cmd);
		ob_get_clean();
	} else {
		$buffer="";
	}
	if($expires) {
		file_put_contents($cache,$buffer);
		@chmod($cache,0666);
	}
	return $buffer;
}

function filemtime_protected($file) {
	ob_start();
	$mtime=filemtime($file);
	$error=ob_get_clean();
	return array($mtime,$error);
}

function getusersmail()
{
	global $db, $user;
	
	$out="(".$user->id;
	
	$sql = "SELECT u.rowid";
	$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql .= " , ".MAIN_DB_PREFIX."webmail_users_view as sc";
	$sql .= " WHERE sc.fk_user =".$user->id;
	$sql .= " AND sc.fk_user_view = u.rowid";
	
	$resql = $db->query($sql);
	if ($resql)
	{
		
		$num = $db->num_rows($resql);
		
		if ($num > 0)
		{
			$userstatic=new User($db);
			$i=0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$out.=",".$obj->rowid;
				$i++;
	
			}
		}
	}
	$out.=")";
	return $out;
	
}

function getuserbymail($mail='')
{
	global $db, $user;
	
	if ($mail)
	{
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."user";
		$sql .= " WHERE email ='".$mail."'";
	
		$resql = $db->query($sql);
		if ($resql)
		{
		
			$num = $db->num_rows($resql);
		
			if ($num > 0)
			{
				return 1;
			}
		}
	}
	return 0;
	
}

?>