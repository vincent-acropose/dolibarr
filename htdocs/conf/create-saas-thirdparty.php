<?php 

	require '../master.inc.php';
	
	$fk_soc = GETPOST('fk_soc');
	
	if($fk_soc>0) {
		
		$user=new User($db);
		$user->fetch(1);
		
		$societe = new Societe($db);
		if($societe->fetch($fk_soc)>0) {
			
			if(empty($societe->array_options['options_saas_env'] )) {
				
				$societe->array_options['options_saas_env'] = _get_env_code($societe);
				$societe->array_options['options_saas_status'] = 'todo';
				
				$societe->update($societe->id, $user);
			}
			
			
		}
		
		
	}

function _get_env_code(&$societe) {
	global $db;
	
	while(true) {
	
		$code =strtoupper( dol_sanitizeFileName(substr(trim($societe->name),0,3).rand(1000,9999).substr(trim($societe->zip), 0,1).substr(trim($societe->town), 0,1)));
		
		$res = $db->query("SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."societe_extrafields WHERE saas_env='".$code."'");
		$obj = $db->fetch_object($res);
		
		if($obj->nb == 0) {
			return $code;			
		}
		
	
	}
	
}