<?php
/*  Copyright (C) 2012		 Oscim					       <aurelien@oscim.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       includes/modules/calling/api_keyyo.php
 *	\brief      File of class to manage widget boxes
 *	\author     Oscim <mail oscim@users.sourceforge.net>
 *	\version    $Id: api_keyyo.php,v 1.56 2012/06/10 15:28:01 oscim Exp $
 */

include_once(DOL_DOCUMENT_ROOT ."/calling/core/modules/calling/calling_api.php");

/**
	@note view detail api
								http://www.keyyo.com/fr/echanger/api_espace_developpeur.php
*/
Class api_keyyo
	extends calling_api{


	/**
		@var level current mod
	*/
	public $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
	/**
		@var string name api
	*/
	public $nom='keyyo';
	/**
		@var int version api
	*/
	public $api_vers='1.2';

	public $error='';



	/**
		@brief      Return description of numbering module
		@return     string      Text with description
		*/
	function info(){
		global $langs;
			return $langs->trans("CallingApiKeyyoDescription");
	}


	/**
		@brief      Mode de fonctionnement
		@return     string
	 */
// 	function getDetail(){
// 		return "emission &amp; reception";
// 	}

	/**
		@brief      Url for external notifi in dolibarr
		@return     string
	 */
	function getUrlForNotification(){
		return $this->internalurlnotif.'?account=_ACCOUNT_&caller=_CALLER_&callee=_CALLEE_&type=_N_TYPE_&callref=_CALLREF_&version=_N_VERSION_&redirectby=_REDIRECTING_NUMBER_';
	}



	/**
		@brief      Test activation possible du module
		@return     boolean
	 */
	function canBeActivated(){
		global $conf,$langs;

		return true;
	}


	/**
		@brief REception des appels et add action in dolibarr + propose popup
		@param $user object user current
		@param $time
	*/
	function Process_Receive($user, $time){
		global $conf,$langs,$db;

		/*
			If all GET is not exist, retrun for execute process clicktocall
		*/
		if(!isset($_GET['account']) || !isset($_GET['caller']) || !isset($_GET['callee']) || !isset($_GET['type'])  )
			return -99;

			/**
				@var $etat string is step calling
					SETUP calling : 1
					CONNECT calling : 2 uniquement si reponse
					RELEASE calling : 3
			*/
			$etat = $_GET['type'];
			$callto = $_GET["callee"]; // To
			$telephone = $_GET["caller"]; // from
			$string = '';
		  $object = new StdClass();

			// init support class
			$calling = new calling($db);

			/// Init open line
			if($etat =='SETUP'){
			}

			$array = array(
					'time'=>microtime(),
					'etat'=>$etat,
					'callto'=>$callto,
					'callto_alias'=>$callto,
			);


			// format Tel To
			// clean tel and retrun array all format
			$array_res = calling_api::FormatTelNum($callto);
			$callto = $array_res['decline'][2]; //$array_res['master'];

			/*
				CALLING_ALERT_TYPE_MODE
				Appel interne
				Collaborateur ou numero protable associé . Echappement cf config
			*/
			if(  ($local_user = calling_api::AlertTypeMode($array_res['decline']) ) && $local_user == false )
				return ;
			else{
				if(is_object($local_user))
					$array['collaborateur'] =$local_user->id;
			}



			// Stock valeur of numer telephone incomming 
			$array_res = calling_api::FormatTelNum($telephone);
			$telephone = $array_res['decline'][2]; //$array_res['master'];
			
// 			$userlist = calling_api::GetInternalTel($array_res['decline']);
// 			$array['collaborateur'] = $userlist[0]->id;
// 			if($userlist !=false && is_array($userlist))
				
			
			// search in db for tel in table soc, contact, tiers
			$array['user'] = calling_api::GetExternalTel($array_res, (($etat =='SETUP')? true: false ) );
			$object->user = $array['user'][0];

				if( !empty(calling_api::$call_to_master) ){
// 					$calling->call_to = calling_api::$call_to_master;
// 					$calling->call_to_alias = $callto;
					
					$calling->call_to_alias = calling_api::$call_to_master;
					$array['callto_alias'] = $calling->call_to_alias;
				}
// 				else 

					$calling->call_to = $callto;
					
					
					
// 					echo 'icic '.$etat; 
		//
		//
		// Process By Action 
		if($etat =='SETUP'){
// 		var_dump(calling_api::$call_to_master); 

				$calling->call_to_user_id = (($local_user == false) ? 0 :  $local_user->id ) ;
				$calling->call_from = $telephone;
				$calling->data = $array;

				$r = $calling->create($user);
				return true;
		}
		/**
				@remarks Stock in session ligne reponse
		*/
		elseif($etat =='CONNECT'){
// 		var_dump( array('call_to'=>$callto, 'call_from'=>$telephone, 'mode'=>1) ); 
				$id = $calling->search(array('call_to'=>$calling->call_to, 'call_from'=>$telephone, 'mode'=>1) );
// var_dump( $id ); 
				if($id > 0) {
					if( $calling->fetch($id, $user) ){
						$calling->mode = 2;
						$calling->data->etat =  'CONNECT';
						$calling->update($user);
					}
					else
						return false;
				}
				return true;
		}
		/**
				@remarks If fin d'appel suppression du fichier
		*/
		elseif($_GET['type'] =='RELEASE'){

				// Close Calling after connected
				if( ($id = $calling->search(array('call_to'=>$calling->call_to, 'call_from'=>$telephone, 'mode'=>2) )) && $id > 0) {
					$calling->fetch($id, $user);
					$calling->mode = 3;
					$calling->update($user);

					// reload
					$calling->fetch($id, $user);
				}
				// close calling but no reponse
				elseif( ($id = $calling->search(array('call_to'=>$calling->call_to, 'call_from'=>$telephone, 'mode'=>1) )) && $id > 0){
					$calling->fetch($id, $user);
					$calling->mode = 2;
				}
				else
					return false;
// exit; 
				// reload data
				$calling->fetch($id, $user);

				// trace in Action Agenda
				$actcom = new ActionCommCalling($db);
				$r= $actcom->AddCallingIncoming($calling,$object,$user);
// var_dump($r); 
				// delete entry
				$calling->delete($user);
			}


		return true;
	}

	/**
		@brief Emission d'un appel
	*/
	function Process_Send($user){
		global $conf,$langs,$db;

		if(isset($_GET['login']) && isset($_GET['password']) && isset($_GET['caller']) && isset($_GET['called'])  ) {


			$telephone = $_GET["called"];
			
			
			$tel_login = trim($_GET['login']); 
			
			$provider_login = trim($_GET['login']); 
			$provider_password = trim($_GET['password']); 
			
			if(strlen($tel_login) <3 ) {
			
				
				dol_include_once('/user2O/class/usergroup2O.class.php');

				$grp = new UserGroup2O($db);
			
				$res = $grp->listGroups2OForUser($tel_login);
				
				$hash='';

				foreach($res as $grp=>$det){
					
					$grp_id = ($grp - 4); 
				
					$tmp = explode(';', trim($_GET['caller']));

					$provider_login = $tmp[$grp_id];
// 					$hash.=$tmp[$grp_id];
					
// 					$hash.=':';
// 									print_r( $tmp[$grp_id]); 
// 				exit; 
					$tmp = explode(';', trim($_GET['password']));
					
// 					$hash.=$tmp[$grp_id];
					
					$provider_password=$tmp[$grp_id];
				}
			}
// 			else{
// 				$hash= trim($_GET['login']).':'.trim($_GET['password']);
// 			}
			
// 			$hash= trim($_GET['login']).':'.trim($_GET['password']);
			$hash= $provider_login.':'.$provider_password;
			$object = new StdClass();

			// called
			// https://ssl.keyyo.com/makecall.html?ACCOUNT=<ligne keyyo>&CALLEE=<destination>&CALLEE_NAME=<nom appelé>
			
			$telephone = $_GET['called'];

			if( substr($telephone, 0,1) == 3 && strlen($telephone) >=11 )
				$telephone = $telephone;
			// add 0 for external keyyo lan
			elseif( (int)substr($telephone, 0,1) > 0 )
				$telephone = '0'.$telephone;
			else
				$telephone = '32'.substr($telephone, 1);

		$url = 'https://ssl.keyyo.com/makecall.html?ACCOUNT='.$provider_login.'&CALLEE='.urlencode($telephone) .'&CALLEE_NAME='.$user->lastname;

echo $url;
			$ch = @curl_init();
			@curl_setopt($ch, CURLOPT_URL, $url);
			@curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,  (int)self::$cam['timeout']);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
			curl_setopt($ch, CURLOPT_USERPWD, $hash  );
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);

			$result = @curl_exec($ch);
			@curl_close($ch);

var_dump($result); 
			if($result =='OK'){
				echo "telephonyClick2CallDo successfull\n";
				flush();

				$array_res = calling_api::FormatTelNum($telephone);
				$callto = $array_res['master'];

				$array = calling_api::GetExternalTel($array_res,  false  );
				$object->user = $array[0];

				// trace in Action Agenda
				$actcom = new ActionCommCalling($db);
				$r= $actcom->AddCallingOutgoing($object,$user);

				// l appel passe donc je te renvoie vers Dolibarr
				echo "<script type='text/javascript'>history.back();</script>";

				return true;
			}
				else{
						echo "<script type='text/javascript'>history.back();</script>";
				}
		}


		return false;
	}

}

?>
