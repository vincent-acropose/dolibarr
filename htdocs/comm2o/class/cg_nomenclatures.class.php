<?php




class Cg_Nomenclatures{


  public $db ; 
  
  
  public $id; 
  public $numnomenclatures; 
  public $description; 
  public $honoraires; 
  public $intervoa; 
  public $intervclient; 
  public $idclassement; 
  
  
  public function __construct($_db){
	$this->db = $_db;
  }
  
  
  /**
	@brief fetch in table 
	@return 1 OK 0 KO
  */
  public function fetch($id='', $ref=''){
		if (empty($id) && empty($ref)) return -1;

        $sql = "SELECT rowid, numnomenclatures,  description, honoraires, intervoa";
        $sql.= ", intervclient, idclassement ";
        $sql.= " FROM cg_nomenclatures";
        if (! empty($id))
        {
        	$sql.= " WHERE rowid=".$id;
        }
        else if (! empty($ref))
        {
        	$sql.= " WHERE numnomenclatures='".$ref."'";
        }
// echo $sql; 
        dol_syslog(get_class($this)."::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->numnomenclatures = $obj->numnomenclatures;
                $this->description = $obj->description;
                $this->honoraires = (float)str_replace(',', '.',$obj->honoraires);
                $this->intervoa = (float)str_replace(',', '.',$obj->intervoa);
                $this->intervclient = $obj->intervclient;
                $this->idclassement = $obj->idclassement; 


                $this->db->free($resql);

                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $this->error = $this->db->lasterror();
            dol_syslog(get_class($this)."::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }
  
  
  public function Specimen(){
  }
  
  
  
}

?>
