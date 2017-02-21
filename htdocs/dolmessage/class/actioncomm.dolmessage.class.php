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


require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");




class ActionCommDolMessage 
	Extends ActionComm {
	
	
	/**
		@brief add event in calendar 
		@param $user Object Dolibarr User 
		@param $societe Object Dolibarr Societe 
		@param $DolMessage Object DolMessage Module 
		@return none 
		
	*/
	public function SetEvent(User $user, Societe $societe, DolMessage $DolMessage, DMessage $Message ){
		global $conf, $langs; 
		
		
			$cactioncomm = new CActionComm($this->db);

			// Initialisation objet actioncomm
			$usertodo = $user;
			$actioncomm->usertodo = $usertodo;



			if ($conf->global->CODE_ACTIONCOMM_WEBMAIL)
					$cactioncomm_code = $conf->global->CODE_ACTIONCOMM_WEBMAIL;
			else
					$cactioncomm_code = "AC_OTH";

			$cactioncomm->fetch($cactioncomm_code);

			$this->type_id = $cactioncomm->id;
			$this->type_code = $cactioncomm->code;
			$this->priority = 0;
			$this->fulldayevent = 0;
			$this->location = '';
			$this->label = $langs->trans('MessageReceived') . ' ' . $Message->GetSubject();
			$this->fk_project = 0;
			$this->datep = strtotime($Message->GetDate());
			$this->note = ''; 
			$this->fk_element = $DolMessage->id;
			$this->elementtype = 'dolmessage';

			if (isset($societe->id) && $societe->id > 0)
					$this->societe = $societe;

			//         $this->contact = $contact;

			// Special for module webcal and phenix
			//     if ($_POST["add_webcal"] == 'on' && $conf->webcalendar->enabled)
			//         $this->use_webcal = 1;
			//     if ($_POST["add_phenix"] == 'on' && $conf->phenix->enabled)
			//         $this->use_phenix = 1;


			if (!$error) {
					$this->db->begin();

					// On cree l'action
					$idaction = $this->add($user);

					if ($idaction > 0) {
							if (!$actioncomm->error) {
									$this->db->commit();
							}
					}
			}
	}
	
}

?>
