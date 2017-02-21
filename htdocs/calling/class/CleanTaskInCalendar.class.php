<?php

// die('ifif'); 



class CleanTaskInCalendar {
	/**
		@var object db current doli
	*/
	public $db;

	public function __construct( $db ){
		$this->db = $db; 
	}
	
	
	
	
	
	public function CleanLinkClosed(){
// 	var_dump(__LINE__);
	
		//suppression des doublon 
		$this->SearchDoubleForIncoming();
	
	
		// cloture des tache 
		$this->SearchCleaningIncomingAfterEvent();
	

		return 1;
	}
	
	
	
	
	/**
		recherche d evenement avec une action effectué posterieur
	*/
	protected function SearchCleaningIncomingAfterEvent(){
	
		$sql = "SELECT id, label,datep,  fk_soc, fk_contact ";
		$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm  ";
		$sql.= " WHERE code = 'AC_TEL' AND percent =100 AND ( label LIKE '%<<%' OR label LIKE '%>>%' )  AND datep >= DATE_SUB( CURDATE( ) , INTERVAL -1
DAY ) ";
		$sql.= " ORDER BY id DESC  ";
echo $sql; 

		$tmp = array(); 
		$result = $this->db->query($sql);

		if($result !=false) {
			$num =  $this->db->num_rows($result);
			if($num) {
				$var=True;
				while ($i < $num)
				{
						$obj = $this->db->fetch_object($resql);
						$var=!$var;
						$i++;
						
						$sql2 = "UPDATE  ".MAIN_DB_PREFIX."actioncomm  SET percent = 100 WHERE  code = 'AC_TEL' AND percent <100 ";
						$sql2.= " AND  label = '".str_replace( '<<', '>>',$obj->label)."' ";
						$sql2.= " AND  datep2 <= '".$obj->datep."'  ";
// 						$sql2.= " LIMIT  1 ";

						
						$tmp[] = $sql2;
// 						$this->db->query($sql2);
				}
			}
		}
		
		
		foreach($tmp as $row){
			echo $row; 
			$this->db->query($row);
		}
	}
	
	/**
		recherche d evenement mutliple pour un numero
		et suppression des doublons 
	*/
	protected function SearchDoubleForIncoming(){
	
		$sql = "SELECT id, label,datep,  COUNT( id ) AS nbr ";
		$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm  ";
		$sql.= " WHERE code = 'AC_TEL' AND percent =100 AND label LIKE '%>>%' AND datep > DATE_SUB( CURDATE( ) , INTERVAL -10 MINUTE )";
		$sql.= " GROUP BY label, datep ";
// 		$sql.= " HAVING nbr >1 ";
		$sql.= " ORDER BY id DESC  ";
// echo $sql; 

		$tmp = array(); 
		$db = $this->db; 
		$result = $this->db->query($sql);

		if($result !=false) {
			$num =  $this->db->num_rows($result);
			if($num) {
				$var=True;
				while ($i < $num)
				{
						$obj = $this->db->fetch_object($resql);
						$var=!$var;
						$i++;
						
						$sql2 = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm  WHERE  code = 'AC_TEL' AND percent <100 ";
// 						$sql2.= " AND  datep = '".$obj->datep."'  AND label = '".$obj->label."' ";
						$sql2.= " AND label = '".$obj->label."' ";
						$sql2.= " AND datep > DATE_SUB( CURDATE( ) , INTERVAL -10 MINUTE )  ";
						$sql2.= " AND  id <> '".$obj->id."'  ";
// 						$sql2.= " LIMIT  ".($obj->nbr -1 )." ";
// echo $sql2;
						$tmp[] = $sql2;
				}
			}
		}
		
		
		foreach($tmp as $row){
			echo $row; 
			$this->db->query($row);
		}
	}
}


?>
