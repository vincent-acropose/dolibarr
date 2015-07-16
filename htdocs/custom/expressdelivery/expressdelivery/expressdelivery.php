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
 * \file		expressdelivery/expressdelivery/expressdelivery.php
 * \ingroup	expressdelivery
 */

// Dolibarr environment
$res = @include ("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include ("../../../main.inc.php"); // From "custom" directory
}

require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT . '/livraison/class/livraison.class.php';

$orderid = GETPOST('id', 'int');
$warehouse_id = GETPOST('warehouse', 'int');
$action = GETPOST('action', 'alpha');
$check_stock = GETPOST('checkstock', 'int');

if ($action == 'expressdelivery_confirm') {
	
	$object_exped = new Expedition($db);
	
	$object_exped->origin = 'commande';
	$object_exped->origin_id = $orderid;
	$object_exped->date_delivery = dol_now();
	$object_exped->weight = "NULL";
	$object_exped->sizeH = "NULL";
	$object_exped->sizeW = "NULL";
	$object_exped->sizeS = "NULL";
	$object_exped->size_units = 0;
	$object_exped->weight_units = 0;
	$object_exped->shipping_method_id = GETPOST('shipping_method_id', 'int');
	
	$classname = ucfirst($object_exped->origin);
	$objectsrc = new $classname($db);
	$objectsrc->fetch($object_exped->origin_id);
	
	$object_exped->socid = $objectsrc->socid;
	$object_exped->ref_customer = $objectsrc->ref_client;
	$object_exped->fk_delivery_address = $objectsrc->fk_delivery_address;
	
	$num = count($objectsrc->lines);
	$totalqty = 0;
	foreach ( $objectsrc->lines as $line ) {
		$totalqty += $line->qty;
	}
	
	if ($totalqty > 0) {
		
		// Check Stock
		if (! empty($check_stock)) {
			require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
			
			$formproduct = new FormProduct($db);
			$outofstock = array ();
			foreach ( $objectsrc->lines as $line ) {
				$formproduct->loadWarehouses($line->fk_product);
				if (is_array($formproduct->cache_warehouses) && count($formproduct->cache_warehouses) > 0) {
					
					if ($formproduct->cache_warehouses[$warehouse_id]['stock'] < $line->qty) {
						$outofstock[] = $line->ref . '-' . $line->libelle;
					}
				}
			}
			if (count($outofstock) > 0) {
				
				// TODO : Add POST param into hook call
				// $paremurl='&outofstock=' . json_encode($outofstock);
				header("Location:" . DOL_URL_ROOT . '/commande/fiche.php?id=' . $orderid . '&action=expressdelivery_nostock');
				exit();
			}
		}
		
		foreach ( $objectsrc->lines as $line ) {
			$ret = $object_exped->addline($warehouse_id, $line->id, $line->qty);
			if ($ret < 0) {
				setEventMessage($object_exped->error, 'errors');
				$error ++;
			}
		}
		// Create shipement
		if (! $error) {
			$ret = $object_exped->create($user);
			if ($ret < 0) {
				setEventMessage($object_exped->error, 'errors');
				$error ++;
			}
		}
		
		//Valid shipment
		if (! $error) {
			$object_exped->fetch($object_exped->id);
			$object_exped->fetch_thirdparty();
			
			$result = $object_exped->valid($user);
			if ($result < 0) {
				setEventMessage($object_exped->error, 'errors');
				$error ++;
			}
			
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))
				$newlang = $object_exped->client->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				require_once DOL_DOCUMENT_ROOT . '/core/modules/expedition/modules_expedition.php';
				$result = expedition_pdf_create($db, $object_exped, $object_exped->modelpdf, $outputlangs);
			}
			
			if ($result < 0) {
				setEventMessage($object_exped->error, 'errors');
				$error ++;
			}
		}
		
		//Create Delivery
		if (! $error) {
			$result_shipid = $object_exped->create_delivery($user);
			if ($result < 0) {
				setEventMessage($object_exped->error, 'errors');
				$error ++;
			}
		}
		
		//Valid Delivery
		if (! $error) {
			$object_ship = new Livraison($db);
			$object_ship->fetch($result_shipid);
			$object_ship->fetch_thirdparty();
			
			$result = $object_ship->valid($user);
			
			$object_ship->fetch($object_ship->id);
			
			// Define output language
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object_ship->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				require_once DOL_DOCUMENT_ROOT.'/core/modules/livraison/modules_livraison.php';
				$result=delivery_order_pdf_create($db, $object_ship,$object_ship->modelpdf,$outputlangs);
			}
			if ($result < 0)
			{
				setEventMessage($object_ship->error, 'errors');
				$error ++;
			}
		}
		
		if (empty($error)) {
			header("Location:" . DOL_URL_ROOT . '/livraison/fiche.php?id=' . $result_shipid);
			exit();
		} else {
			header("Location:" . DOL_URL_ROOT . '/commande/fiche.php?id=' . $orderid);
			exit();
		}
	}
}