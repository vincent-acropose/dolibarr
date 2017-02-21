<?php 
/* Copyright (C) 2014 Oscim 	<support@oscim.fr>
 * Copyright (C) 2015 Oscss-Shop Team <support@oscss-shop.fr>
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
 
 
Class dolmessage 
	extends CommonObject{
	/**
		@var string 
	*/
	public $element='dolmessage';
	/**
		@var string
	*/
	public $table_element='message';
	/**
		@var object 
	*/
	public $user;
	
	
	
	/**
		@var internal id int 
	*/
	public $id; 
	/**
		@var message uid int 
	*/
	public $uid; 
	/**
		@var int 
	*/
	public $user_id; 
	/**
		@var int 
	*/
	public $usergroup_id; 
	/**
		@var int 
	*/
	public $number = 1; 
	/**
		@var int
	*/
	public $entity; 
	/**
		@var message_id Unique / string 
	*/
	public $message_id; 
	/**
		@var date
	*/
	public $datec; 
	/**
		@var recent
	*/
	public $recent; 
	/**
		@var unseen
	*/
	public $unseen; 
	/**
		@var flagged
	*/
	public $flagged; 
	/**
		@var answered
	*/
	public $answered; 
	/**
		@var piece jointes 0/1
	*/
	public $joint; 
	
	/**
		@var array 
	*/
	public $linkedObjects=array();
	/**
		@var string
	*/
	public $path=''; 
	

	
	public function __construct($db, $user =false){
	
		if($user == false)  {
			global $user;
		}
			
		$this->db = $db;
		$this->user = $user;
	}
	public function specimen(){
		$this->id = 0; 
		$this->message_id = '';
	}
	
	public function create($group_id=0){
		global $conf, $langs;

		$error=0;
		$this->db->begin();
		
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."message (";
		$sql.= "message_id";
		if($group_id==0)
			$sql.= ", user_id ";
		else 
			$sql.= ", usergroup_id ";
			
		$sql.= ", number ";
		$sql.= ", entity ";
		$sql.= ", message_uid";
		$sql.= ", datec";
		$sql.= ", recent";
		$sql.= ", unseen";
		$sql.= ", flagged";
		$sql.= ", answered";
		$sql.= ", joint";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->message_id.'"';
		
		if($group_id==0)
			$sql.= ', "'.$this->user->id.'"';
		else
			$sql.= ', "'.$group_id.'"';
		
		$sql.= ', "'.$this->number.'"';
		$sql.= ', "'.$conf->entity.'"';
		$sql.= ', "'.$this->uid.'"';
		$sql.= ', "'.$this->datec.'"';
		$sql.= ', "'.$this->recent.'"';
		$sql.= ', "'.$this->unseen.'"';
		$sql.= ', "'.$this->flagged.'"';
		$sql.= ', "'.$this->answered.'"';
		$sql.= ', "'.$this->joint.'"';
		$sql.= ")";

		dol_syslog(get_class($this)."::Create sql=".$sql);
		$result = $this->db->query($sql);
// 		var_dump($this->db); 
// exit; 

		if ( $result )
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."message");

			if ($id > 0)
			{
				$this->id = $id;
				
				
				
				// Add object linked
				if (! $error && $this->id && is_array($this->linkedObjects) && ! empty($this->linkedObjects))
				{
					foreach($this->linkedObjects as $origin => $origin_id)
					{
						$ret = $this->add_object_linked($origin, $origin_id);
						if (! $ret)
						{
							dol_print_error($this->db);
							$error++;
						}
					}
				}
			}
		}
		
		
		if (! $error){
			$this->db->commit();
			return $this->id;
		}
		else{
			$this->db->rollback();
			return -$error;
		}
	}
	

	public function update($user){
	
		$sql=' UPDATE '.MAIN_DB_PREFIX."message"; 
		$sql.=' SET ';
		$sql.=' recent = "'.$this->recent.'", ';
		$sql.=' unseen = "'.$this->unseen.'", ';
		$sql.=' flagged = "'.$this->flagged.'", ';
		$sql.=' answered = "'.$this->answered.'", ';
		$sql.=" WHERE row_id='".$this->id."' ";
	
	
	
		// Add object linked
		if (! $error && $this->id && is_array($this->linkedObjects) && ! empty($this->linkedObjects))
		{
			foreach($this->linkedObjects as $origin => $origin_id)
			{
				$ret = $this->add_object_linked($origin, $origin_id);
				if (! $ret)
				{
					dol_print_error($this->db);
					$error++;
				}
			}
		}
	}
	
	public function fetch($id=0, $ref='', $no_user=true, $number = 1, $group_id=0){
		global $conf,$langs;
		
		$sql = "SELECT * ";
		$sql.= " FROM ".MAIN_DB_PREFIX."message";
		$sql.= " WHERE  1 ";
		
		if(!$no_user) {
			$sql.= "  AND number ='".$number."' ";
			
			if($group_id==0)
				$sql.= " AND user_id = '".$this->user->id."' ";
			else
				$sql.= 'AND usergroup_id = "'.$group_id.'"';
		}
			
		$sql.= "  AND entity IN (".getEntity($this->element, 1).")";
		
		if($id > 0 ) 
			$sql .=" AND row_id = '" .$id."' ";
		else 
			$sql .=" AND message_id = '" .$ref."' ";
// echo $sql; 
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);

			$this->id =  $obj->row_id;
			$this->message_id =  $obj->message_id;
			$this->uid =  $obj->message_uid; 
			$this->user_id=  $obj->user_id; 
			$this->usergroup_id =  $obj->usergroup_id; 
			$this->number=  $obj->number; 
			$this->datec =  $obj->datec; 
// 			$this->fetch_object =  $obj->fetch_object; 
			$this->unseen =  $obj->unseen; 
			$this->flagged =  $obj->flagged; 
			$this->answered =  $obj->answered; 
			$this->joint =  $obj->joint; 
			
// 			if($this->joint > 0 )

			$this->fetchObjectLinked('','',$this->id, $this->element);
// print_r($this->load_object_linked($this->id, $this->element)); 
			return $this->id; 
		}
		
		return false; 
	}
	
	/**
		@param $group_id int default is 0, if this not null; not use user_id but usergroupid
	*/
	public function delete($group_id=0, $number=1 ,$id=0, $ref=''){
		global $conf,$langs;
		
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."message";
		$sql.= " WHERE 1 ";
		
		if($id <= 0 ){
			if($group_id==0)
				$sql.= " AND user_id = '".$this->user->id."' ";
			else
				$sql.= " AND usergroup_id = '".$group_id."' ";
		}
		
		$sql.= "  AND number ='".$number."' ";
		$sql.= "  AND entity IN (".getEntity($this->element, 1).")";
		
		if($id > 0 ) 
			$sql .=" AND row_id = '" .$id."' ";
		else 
			$sql .=" AND message_id = '" .$ref."' ";

		return $this->db->query($sql);
	}
	
	
	/**
		*    	Return a link on thirdparty (with picto)
		*
		*		@param	int		$withpicto		Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
		*		@param	string	$option			Target of link ('', 'customer', 'prospect', 'supplier')
		*		@param	int		$maxlen			Max length of text
		*		@return	string					String with URL
		*/
	function getNomUrl($withpicto=0,$option='',$maxlen=0, $params=""){
        global $conf,$langs;

        $name=(!preg_match('#<|>#',$this->message_id)?$this->message_id:$this->id);

        $result='';

        
        if( $option =='dolmessage')
					$file = 'info.php'; 
				else 
					$file = 'fiche.php'; 
					
        $lien = '<a class="linkSubject" href="'. dol_buildpath('/dolmessage/'.$file, 1) .'?';

        if($this->id > 0 )
					$lien.='id='.$this->id;
        elseif($this->uid > 0 )
					$lien.='&uid='.$this->uid;

				
				if(strlen($params) > 1)
					$lien.=(substr($params,0,1)=='&')? $params : '&'.$params; 
					
					
					
					
        // Add type of canvas
        $lien.=(!empty($this->canvas)?'&amp;canvas='.$this->canvas:'').'">';
        $lienfin='</a>';

        if ($withpicto) $result.=($lien.img_object($langs->trans("DolMessageView").'','dolmessage@dolmessage').$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$lien.($maxlen?dol_trunc($name,$maxlen):$name).$lienfin;

        return $result;
    }
}

?>
