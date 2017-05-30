<?php

chdir(__DIR__);

require '../master.inc.php';

$res = $db->query("SELECT saas_env,fk_object FROM ".MAIN_DB_PREFIX."societe_extrafields WHERE saas_env IS NOT NULL AND saas_status='todo'");
if($res === false) {
	var_dump($db);
	exit;
}
if($obj = $db->fetch_object($res)) {
	
	$code_env = $obj->saas_env;
	
	echo $code_env.'...';
	
	$societe=new Societe($db);
	if($societe->fetch($obj->fk_object)>0) {
		$user=new User($db);
		$user->fetch(1);
		
		$societe->array_options['options_saas_status'] = 'inprogress';
		$societe->update($societe->id, $user);
		
		mkdir($code_env,0777);
		$dir_document = strtr($dolibarr_main_document_root,array('/htdocs'=>'/'.$code_env));
		mkdir($dir_document ,0777);
		
		$dbpassword = md5($code_env.time().rand(0,100000));
		
		$conffile = '<?php 
$dolibarr_main_url_root=\''.$dolibarr_main_url_root.'\';
$dolibarr_main_document_root=\''.$dolibarr_main_document_root.'\';
//$dolibarr_main_url_root_alt=\'/custom\';
//$dolibarr_main_document_root_alt=\''.$dolibarr_main_document_root.'/custom\';
$dolibarr_main_data_root=\''.$dir_document.'\';
$dolibarr_main_db_host=\''.$dolibarr_main_db_host.'\';
$dolibarr_main_db_port=\''.(empty($dolibarr_main_db_port) ? '' : $dolibarr_main_db_port).'\';
$dolibarr_main_db_name=\''.$code_env.'\';
$dolibarr_main_db_prefix=\''.$dolibarr_main_db_prefix.'\';
$dolibarr_main_db_user=\''.$code_env.'\';
$dolibarr_main_db_pass=\''.$dbpassword.'\';
$dolibarr_main_db_type=\''.$dolibarr_main_db_type.'\';
$dolibarr_main_db_character_set=\''.$dolibarr_main_db_character_set.'\';
$dolibarr_main_db_collation=\''.$dolibarr_main_db_collation.'\';
$dolibarr_main_authentication=\''.$dolibarr_main_authentication.'\';
		
// Specific settings
$dolibarr_main_prod=\'0\';
$dolibarr_nocsrfcheck=\'1\';
$dolibarr_main_force_https=\''.$dolibarr_main_force_https.'\';
$dolibarr_main_cookie_cryptkey=\''.md5($code_env.time()).'\';
$dolibarr_mailing_limit_sendbyweb=\'3\';
';
		
		
		$res = file_put_contents($code_env.'/conf.php', $conffile);
		if(!$res) {
			
			exit('Error creation conf file : '.$code_env);
			
		}
		
		chmod($code_env.'/conf.php', 0444);
		
		$res = $db->query("CREATE DATABASE ".$code_env.";");
		if($res === false) { var_dump($db); exit; }
		$res = $db->query("CREATE USER $code_env@localhost IDENTIFIED BY '".$dbpassword."';");
		if($res === false) { var_dump($db); exit; }
		$res = $db->query("GRANT ALL PRIVILEGES ON $code_env.* TO $code_env@localhost;");
		if($res === false) { var_dump($db); exit; }
		
		$res = exec('mysql --user=\''.$code_env.'\' --password=\''.$dbpassword.'\' \''.$code_env.'\' < default.sql');
		var_dump($res);
		
		$password_crypted = dol_hash('admin');
		
		$sql = "UPDATE $code_env.".MAIN_DB_PREFIX."user";
		$sql.= " SET pass_crypted = '".$db->escape($password_crypted)."',";
		$sql.= " pass_temp = null, pass = null";
		$sql.= " WHERE rowid = 1";
		$db->query($sql);
		
		$societe->array_options['options_saas_status'] = 'installed';
		$societe->update($societe->id, $user);
		
		echo $code_env.'ok<br />';
		
	}
	
	
}