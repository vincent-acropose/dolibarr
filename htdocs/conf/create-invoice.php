<?php 
	chdir(__DIR__);

	require '../master.inc.php';
	require 'price.php';
	dol_include_once('/compta/facture/class/facture.class.php');
	
	$user=new User($db);
	$user->fetch(1);
	$user->getrights();
	
	$resEnv = $db->query("SELECT saas_env,fk_object FROM ".MAIN_DB_PREFIX."societe_extrafields WHERE saas_env IS NOT NULL AND saas_status='installed'");

	while($obj = $db->fetch_object($resEnv)) {
	//var_dump($obj);//	continue;
		$env = $obj->saas_env;
		$fk_soc = $obj->fk_object;
		
		$resUser = $db->query("SELECT count(*) as nb FROM ".$env.".".MAIN_DB_PREFIX."user WHERE statut = 1");
		$objUser = $db->fetch_object($resUser);
		
		$nb_user_instance = (int)$objUser->nb;
	
		if($nb_user_instance>0) {
			
			$amount = $nb_user_instance * 10; //10e
			
			$resMod = $db->query("SELECT numero FROM ".$env.".".MAIN_DB_PREFIX."const WHERE name LIKE 'MAIN_MODULE_%' AND value=1 AND numero IS NOT NULL ");
			
			if($resMod) {
				
				while($objMod = $db->fetch_object($resMod)) {
					//var_dump($objMod);
					if($objMod->numero>0 && isset($TPriceModule[$objMod->numero])) {
						
						list($price) = $TPriceModule[$objMod->numero];
						//var_dump($env,$nb_user_instance,$objMod->numero,$price);
						
						$amount += $nb_user_instance * $price;
					}
					
					
				}
				
			}
		
			
			if($amount>0) {
				
				$facture=new Facture($db);
				$facture->socid = $fk_soc;
				$facture->date = time();
				$facture->date_lim_reglement = strtotime('+7day', $facture->date);
				$res = $facture->create($user);
				if($res<0) {
					var_dump($res, $facture);
				}
				
				$facture->addline($langs->trans('AmountForInstance'), $amount, 1, 20);
				
				$res = $facture->validate($user);
				if($res<0) {
					var_dump($res, $facture);
				}
				
				echo $facture->ref.'<br />';
				
				//exit($facture->ref);
				
			}
			
		}
		
	}