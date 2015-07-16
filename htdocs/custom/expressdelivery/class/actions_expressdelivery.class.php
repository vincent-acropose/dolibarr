<?php
/* Copyright (C) 2014		Florian Henry			<florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file /expressdelivery/class/actions_expressdelivery.class.php
 * \ingroup expressdelivery
 * \brief File of hook
 */

/**
 * \class ActionsExpressDelivery
 * \brief Class to manage express delivery
 */
class ActionsExpressDelivery {
	var $db;
	var $dao;
	var $error;
	var $errors = array ();
	var $resprints = '';
	
	/**
	 * Constructor
	 *
	 * @param DoliDB $db
	 */
	function __construct($db) {
		$this->db = $db;
		$this->error = 0;
		$this->errors = array ();
	}
	
	/**
	 * DoAction Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function doActions($parameters, &$object, &$action, $hookmanager) {
		// global $langs,$conf,$user;
		return 0;
	}
	
	
	/**
	 * form Confirm Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function formConfirm($parameters, &$object, &$action, $hookmanager) {
		global $langs,$conf,$user;
		
		$langs->load("stocks");
		
		$form = new Form($this->db);
		if($action=='expressdelivery') {
			
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
			
			$formproduct = new FormProduct($this->db);
			$formproduct->loadWarehouses();
			
			$expe = new Expedition($this->db);
			$expe->fetch_delivery_methods();
			
			$warehouse=array();
			if (is_array($formproduct->cache_warehouses) && count($formproduct->cache_warehouses)>0) {
				foreach($formproduct->cache_warehouses as $cache_warehouses) {
					$warehouse[$cache_warehouses['id']]=$cache_warehouses['label'];
				}
			}
			
			$form_question = array ();
			$form_question [] = array (
					'label' => $langs->trans("WarehouseSource"),
					'type' => 'select',
					'values' => $warehouse,
					'name' => 'warehouse' 
			);
			$form_question [] = array (
					'label' => $langs->trans("DeliveryMethod"),
					'type' => 'select',
					'values' => $expe->meths,
					'name' => 'warehouse'
			);
			$formconfirm=$form->formconfirm(dol_buildpath('/expressdelivery/expressdelivery/expressdelivery.php',1).'?id='.$object->id.'&checkstock=1',$langs->trans('ExpressDelivery'),'','expressdelivery_confirm',$form_question,'yes',1);
		}
		
		if($action=='expressdelivery_nostock') {
			require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
			$expe = new Expedition($this->db);
			$expe->fetch_delivery_methods();
			
			$form_question = array ();
			$form_question [] = array (
					'label' => $langs->trans("DeliveryMethod"),
					'type' => 'select',
					'values' => $expe->meths,
					'name' => 'shipping_method_id'
			);
			$formconfirm=$form->formconfirm(dol_buildpath('/expressdelivery/expressdelivery/expressdelivery.php',1).'?id='.$object->id.'&checkstock=0',$langs->trans('ExpressDeliveryNoStock'),$langs->trans('ExpressDeliveryNoStock'),'expressdelivery_confirm',$form_question,'yes',1);
		}
		
		return $formconfirm;
	}
	
	/**
	 * addMoreActionsButtons Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
		global $langs,$conf,$user;
		
		$current_context=explode(':',$parameters['context']);
		if (in_array('ordercard',$current_context)) {
			$langs->load("expressdelivery@expressdelivery");
			
			$numshipping=0;
			if (! empty($conf->expedition->enabled))
			{
				$numshipping = $object->nb_expedition();
			
				if ($object->statut > 0 && $object->statut < 3 && $object->getNbOfProductsLines() > 0)
				{
					if (($conf->expedition_bon->enabled && $user->rights->expedition->creer)
							|| ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->creer))
					{
						if ($user->rights->expedition->creer)
						{
							$html= '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=expressdelivery">'.$langs->trans('ExpressDeliveryBtn').'</a></div>';
						}
						else
						{
							$html= '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('ExpressDeliveryBtn').'</a></div>';
						}
					}
					else
					{
						$langs->load("errors");
						$html= '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")).'">'.$langs->trans('ExpressDeliveryBtn').'</a></div>';
					}
				}
			}
			$html=str_replace('"','\"',$html);
			print '<script type="text/javascript">jQuery(document).ready(function () {jQuery(function() {jQuery(".tabsAction").append("' . $html . '");});});</script>';
		}
		return 0;
	}
	
	/**
	 * Print Search Form
	 * @param unknown $parameters
	 * @param unknown $object
	 * @param unknown $action
	 * @param unknown $hookmanager
	 */
	function printSearchForm($parameters, &$object, &$action, $hookmanager) {
		global $langs;
		$langs->load('expressdelivery@expressdelivery');
		$langs->load("sendings");
		$langs->load('deliveries');
	
		$out = printSearchForm(dol_buildpath('/expedition/liste.php', 1), dol_buildpath('/expedition/liste.php', 1), img_object('', 'sending') . ' ' . $langs->trans("Shipment"), 'expedition', 'sf_ref');
		$out .= printSearchForm(dol_buildpath('/expressdelivery/livraison/liste.php', 1), dol_buildpath('/expressdelivery/livraison/liste.php', 1), img_object('', 'sending') . ' ' . $langs->trans("Delivery") , 'livraison', 'bl_ref');
	
		$this->resprints = $out;
	}
}