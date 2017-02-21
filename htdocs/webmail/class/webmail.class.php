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

dol_include_once("/webmail/lib/webmail.lib.php");

/**
 *	\file       webmail/class/webmail.class.php
 *	\brief      webmail pop3 
 *	\ingroup    webmail
 */
class POP3
{
	var $pop3;
	
	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      		Database handler
	 */
    function __construct($db)
    {
        $this->db = $db;
        $this->pop3=new pop3_class;
    }
    
    function connect($hostname,$port,$tls=false)
    {

    	$connected =true;
    	
		$this->pop3->hostname=$hostname;
		$this->pop3->port=$port;
		$this->pop3->tls=$tls;
		
		$error=$this->pop3->Open();
		
		if($error!="") 
		{
			$connected=false;
			$this->error=$errors;
		}
		return $connected;
    }
    
    function login($user,$pass)
    {
    	$login=true;
    	
    	$error=$this->pop3->Login($user,$pass);
		
		if($error!="")
		{
			$login=false;
			$this->error= $error;	
		}
		return $login;
    }
    	
    function get_new_messages()
    {
    	global $user, $conf;
    	
    	$olduidls=array();
    	
    	$sql="SELECT uidl FROM ".MAIN_DB_PREFIX."webmail_mail WHERE fk_user='".$user->id."'";
    	
    	$resql= $this->db->query($sql);
    	
    	$i=0;
    	$num = $this->db->num_rows($resql);
    	
    	while ($i < $num)
		{
			$objp = $this->db->fetch_object($resql);
			$olduidls[$i+1]=$objp->uidl;
			$i++;
		}
		
		$sizes=$this->pop3->ListMessages("",0);
		if(!is_array($sizes)) $error=$sizes;
		
		if($error=="") 
		{
			$uidls=$this->pop3->ListMessages("",1);
			if(!is_array($uidls)) $error=$uidls;
		}
		
		if($error=="") 
		{
    		$retrieve=array_diff($uidls,$olduidls);
			foreach($retrieve as $index=>$uidl)
			{
				if($error=="")
				{
					$fext=getDefault("exts/emailext",".eml").getDefault("exts/gzipext",".gz");
					$dir = $conf->webmail->dir_output."/inbox/".$user->id;
					$file =$dir."/".$uidls[$index].$fext;

				}

				if (! file_exists($dir))
				{
					dol_mkdir($dir);
				}

				if (file_exists($dir))
				{
					if(!file_exists($file))
					{
						// RETRIEVE THE ENTIRE MESSAGE
						$error=$this->pop3->OpenMessage($index,-1);
						if($error=="")
						{
							$message="";
							$eof=0;
							while(!$eof && $error=="")
							{
								$temp="";
								$error=$this->pop3->GetMessage($sizes[$index]+1,$temp,$eof);
								$message.=$temp;
							}
						}
						if($error=="")
						{
							// STORE THE MESSAGE INTO SINGLE FILE
							$fp=gzopen($file,"w");
							gzwrite($fp,$message);
							gzclose($fp);
							@chmod($file,0666);
							$message=""; // TRICK TO RELEASE MEMORY
						}
					}
					if($error=="")
					{
						$messageid=$user->id."/".$uidls[$index];
						$last_id=__getmail_insert($file,$messageid,1,0,0,0,0,0,0,"");
						$newemail++;
					
					}
				}
			}
		}
		
		return $newemail;
		
	}
	
	function delete_old_messages($days_old=10,$now=false)
	{
		global $db, $user;
		
		$olduidls=array();
    	
    	$sql="SELECT uidl, `datetime` FROM ".MAIN_DB_PREFIX."webmail_mail WHERE fk_user='".$user->id."' AND is_outbox=0";
    	
    	$resql= $this->db->query($sql);
    	
    	$i=0;
    	$num = $this->db->num_rows($resql);
    	
    	while ($i < $num)
		{
			$objp = $this->db->fetch_object($resql);
			$olduidls[$i+1]["uidl"]=$objp->uidl;
			$olduidls[$i+1]["datetime"]=$objp->datetime;
			$i++;
		}

		$time1=dol_now();
		$uidls=$this->pop3->ListMessages("",1);
			
		foreach($olduidls as $row2)
		{
			$time2=strtotime($row2["datetime"]);
			if($time1-$time2>=$days_old*86400 || $now==true)
			{
				$i=1;
				foreach ($uidls as $uidl)
				{
					if ($uidl==$row2["uidl"])
					{
						$error=$this->pop3->DeleteMessage($i);
					}
					$i++;	
				}
				//$error=$this->pop3->DeleteMessage(strval($row2["uidl"]));
				//unset($uidls[$index2]);
			}
			if($error!="") break;
		}
		
		if($error=="")
		{
			$error=$this->pop3->Close();
		}	
	}
	
	function Close()
	{
		$this->pop3->Close();
	}
}