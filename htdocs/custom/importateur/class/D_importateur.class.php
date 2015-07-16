<?php

/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       importateur/D_Importateur.class.php
 *      \ingroup    importateur
 *      \brief      Import tool for dolibarr
 *		\version    $Id: ecom_log.class.php,v 1.1 2010-04-09 22:07:38 jean Exp $
 *		\author		jean Heimburger jean@tiaris.info
 *		\remarks	version 1.0
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.product.class.php");

/**
 *      \class      societe_import
 *      \brief      File import utility
 *		\remarks	importateur module 2010-06-25
 */

class D_importateur {

var $db;
var $error;
var $errors=array();

var $process_resul;
var $process_msg;
var $filename;
var $firstline;

var $user;

    function D_importateur($db,  $filename,  $user) {      
    	$this->db = $db;
    	$this->error ='';
    	$this->filename = $filename;
    	$this->user = $user;
    	$this->firstline = 0;
    return 1;
    }
    
// fonction d'imports de fichiers'
function validate_import($object)
{
global $langs;

	$this->process_msg = '';
	$error = 0;
	if (! utf8_check($this->filename)) $$this->filename=utf8_encode($this->filename);
	$fp = @fopen($this->filename,"r");
	if ($fp)
	{
		switch ($object)
		{ 
		case 'ImportProduct':
			$i=0;
			while ($ligne = fgetcsv($fp,1000,";"))
			{
				$i++;
				if ($this->firstline && $i == 1) continue;
				
				foreach ($ligne as $element) {
					$this->process_msg .= $element." ";
				}
				$this->process_msg .= "\n";
				$this->process_msg .= $ligne[0].' ; '.$ligne[1].' ; '.$ligne[3]."\n";
			}
			break;
		case 'ImportThirtdparty':
			$i=0;
			while ($ligne = fgetcsv($fp,1000,";"))
			{
				$i++;
				if ($this->firstline && $i == 1) continue;
				if (!$ligne[0]) 
				{
					$this->process_msg .= $i.' '.$langs->trans("NonTraite"). $ligne[0].$langs->trans("NomSocOblig")."\n";
					$error++;
				}
			  	$this->process_msg .= "traitement de ".$i."  " .$ligne[0]."\n";
			  	
			}
			break;
		case 'ImportContact':
			$i=0;
			$societe = new Societe($this->db);
			while ($ligne = fgetcsv($fp,1000,";"))
			{
				$i++;
				if ($this->firstline && $i == 1) continue;
				if (!$ligne[0]) 
				{
					$this->process_msg .= "ligne $i code société est obligatoire"."\n";
					$error++;
				}
				// recherche id Société
				//if ($societe->fetch('',$ligne[0]) < 0) $this->process_msg .= "Erreur lecture société".$ligne[0]."\n";
				$socid = $this->get_socbyclientcode($ligne[0]);
				if ($socid <=0) $this->process_msg .= "Erreur lecture société".$ligne[0]."\n";
				else
				{
					$societe->fetch($socid);
					if (!$societe->id) 
					{
						$this->process_msg .= "Société inexistante ".$ligne[0]."\n";
						$error++;
					}
				}
				// test existence contact
				if ($ligne[1]) {
					$contactid = $this->get_contact_id($socid, $ligne[1], $ligne[2]) ;
					if ($contactid > 0) 
					{
						$this->process_msg .= "Contact existe déjà ".$ligne[0].' '.$ligne[1].' '.$ligne[2]."\n";
					}
				}
				foreach ($ligne as $element) {
					$this->process_msg .= $element." ";
				}
				$this->process_msg .= " id= ".$societe->id." nom = ".$societe->nom."\n";
			}
		case 'ImportActions':
			$i=0;
			$contact=new Contact($this->db);
			$societe = new Societe($this->db);
			$actioncomm = new ActionComm($this->db);
			$actuser = new User($this->db);
			
			while ($ligne = fgetcsv($fp,1000,";"))
			{
				$i++;
				if ($this->firstline && $i== 1) continue;
				
				if ($societe->fetch('',$ligne[0]) < 0 ) $this->process_msg .= "erreur lecture Société "."\n";
				else $socid = $societe->id;
				
				if (!$socid) 
				{
					$this->process_msg .= "Société non trouvée ".$ligne[0]." ligne $i non traitée"."\n";
					$error++;
				}
				$usertodo = '';
				if ($ligne[7])
				{
					if ($actuser->fetch('',$ligne[7]) < 0) $this->process_msg .= "erreur lecture user ".$ligne[7]."\n";
					else $usertodo = $actuser->id;
				}
				$userdone= '' ;
				if ($ligne[8])
				{
					if ($actuser->fetch('',$ligne[8]) < 0) $this->process_msg .= "erreur lecture user ".$ligne[8]."\n";
					else $userdone = $actuser->id;
				}
				if ($ligne[4])
				{
					// voir date
					$n = sscanf($ligne[4],"%02d/%02d/%04d", $d_day, $d_mon, $d_year);
					if ($n==3) $datep=dol_mktime(12, 0, 0, $d_mon, $d_day, $d_year);
					else $this->process_msg .= "erreur conversion date ".$ligne[4].' '.$d_day. ' '.$d_mon.' '.$d_year."\n";
				}
				if ($ligne[5])
				{
					// voir date
					$n = sscanf($ligne[5],"%02d/%02d/%04d", $d_day, $d_mon, $d_year);
					if ($n==3) $datep=dol_mktime(12, 0, 0, $d_mon, $d_day, $d_year);
					else $this->process_msg .= "erreur conversion date ".$ligne[4].' '.$d_day. ' '.$d_mon.' '.$d_year."\n";
				}
				foreach ($ligne as $element) {
					$this->process_msg .= $element." ";
				}
				$this->process_msg .= "\n";
			}
			break;	
		}	
	fclose($fp);
	}
	else 
	{
		$this->error = "erreur ouverture fichier ".$nomfich;
		$error = -1;
	}		
return $error;	
}   

// création objet dans dolibarr $object = 'product' typeimport = création,  ou Modification ou Delete
function import2Dolibarr($object, $typeimport)
{
global $conf;
global $langs;

	$this->process_msg = '';
	$error = 0;
	$fp = @fopen($this->filename,"r");
	if ($fp)
	{
		switch ($object)
		{
		case 'ImportStock':
			$doliprod = new Product($this->db);
			$i=0;
			while ($ligne = fgetcsv($fp,1000,";"))
			{
				$i++;
				if ($this->firstline && $i== 1) continue;
				
				if ($doliprod->fetch('', $ligne[0]) < 0) $this->process_msg .= "Erreur ligne $i produit inexistant". $ligne[0]."  ".$doliprod->error."\n";
				else
				{
					$pid = $doliprod->id;
					$doliprod->ref = $ligne[0];
					$entrepot = $ligne[1];
					$newstock = $ligne[2];
					
					$doliprod->load_stock();
					dol_syslog("stock produit ".$doliprod->stock_warehouse[$entrepot]->real. " entrepot ".$entrepot." ".$doliprod->stock_reel, LOG_DEBUG);
					// correction de stock
					$delta = 0;
					if ($newstock > $doliprod->stock_warehouse[$entrepot]->real) 
					{
						$delta = $newstock - $doliprod->stock_warehouse[$entrepot]->real;
						$sens = 0;  
					}
					elseif ($newstock < $doliprod->stock_warehouse[$entrepot]->real) 
					{
						$delta =  $doliprod->stock_warehouse[$entrepot]->real - $newstock ;
						$sens = 1;  
					} 
					
					if ($delta) {
						$res = $doliprod->correct_stock($this->user, $entrepot, $delta, $sens ,'Correction stock', 0);
					}
					dol_syslog("maj stock delta = ".$delta." sens ".$sens, LOG_DEBUG);
				}
			}
			break; 		 
		case 'ImportProduct':
			$doliprod = new Product($this->db);
			$i=0;
			while ($ligne = fgetcsv($fp,1000,";"))
			{
				$i++;
				if ($this->firstline && $i== 1) continue;
				
				if ($doliprod->fetch('', $ligne[0]) < 0) $this->process_msg .= "Erreur ligne $i  produit inexistant". $ligne[0]."  ".$doliprod->error."\n";
				else
				{	
					$pid = $doliprod->id;
					$doliprod->ref = $ligne[0];
					$doliprod->libelle = $ligne[1];
					if ($ligne[2]) $doliprod->status = $ligne[2];
					else $doliprod->status = 1;
					$doliprod->status_buy = 1;
					$doliprod->description = $ligne[3];
					$doliprod->price= $ligne[4];
					$doliprod->tva_tx= $ligne[5];
					$doliprod->weight=$ligne[6];
					$doliprod->volume=$ligne[7];
					$doliprod->barcode = $ligne[10];
										
					$doliprod->price_base_type = 'HT';
					$doliprod->type = 0;	 // toujours produit
					$doliprod->finished = 1; // produit manufacturé
					$doliprod->tva_npr = 0; //tva non r�cup�rable
					switch ($typeimport)
					{					
					case 'C':
						if ($pid > 0) $this->process_msg .= $i.' '.$langs->trans("NonTraite"). $ligne[0].$langs->trans("ProdExist")."\n";
						if ($doliprod->create($this->user) < 0) $this->process_msg .= "Erreur ligne $i création produit ". $ligne[0]."  ".$doliprod->error."\n";
						else {
							// image et code barre
							if ($ligne[8]) $doliprod->add_photo_web($conf->produit->dir_output,$ligne[8]);
							if ($ligne[10]) {
								if ($doliprod->setValueFrom('fk_barcode_type', 2) < 0) $this->process_msg .= "Erreur ligne $i création produit ". $ligne[0]."  ".$doliprod->error."\n"; // TODO paramétrer
								if ($doliprod->setValueFrom('barcode', $ligne[10]) < 0 ) $this->process_msg .= "Erreur ligne $i création produit ". $ligne[0]."  ".$doliprod->error."\n";;
							}
						}
						break;
					case 'M':
						if ($pid>0) 
						{
							if ($doliprod->update($pid, $this->user) < 0)  $this->process_msg .= "Erreur ligne $i mise à jour produit ". $ligne[0]."  ".$doliprod->error."\n";;
						}
						else $this->process_msg .= "Ligne $i non traitée ". $ligne[0]." Produit  n'existe pas "."\n";
						break;
					case 'D':
						if ($pid > 0) 
						{ 
							if ($doliprod->delete($pid) < 0 ) $this->process_msg .= "Erreur ligne $i suppression produit ". $ligne[0]."  ".$doliprod->error."\n";
						}
						else $this->process_msg .= "Ligne $i non traitée ". $ligne[0]." Produit  n'existe pas "."\n";
					}
				}	
			} // while
			break;

		case 'ImportThirtdparty':
				
			$i=0;
			$societe = new Societe($this->db);

			while ($ligne = fgetcsv($fp,1000,";"))
			{
				$i++;
				if ($this->firstline && $i== 1) continue;
				if (!$ligne[0]) 
				{
					$this->process_msg .= $i.' '.$langs->trans("NonTraite"). ' '.$langs->trans("NomSocOblig")."\n";
					continue;
				}

/*				if ( $societe->fetch('', $ligne[0]) > 0) $sid = $societe->id;
				else $sid = '';
*/
				// vérifier par code_client
				$sid = $this->get_socbyclientcode($ligne[13]);
				if ( $sid > 0)  $societe->fetch($sid);
				else $sid = '';
								 
				if ($ligne[12]) $pid = $this->get_pays_id($ligne[12]);
				else $pid = '';
//				if ($ligne[13]) $did = $this->get_departement_id($ligne[13], $pid);
//				else $did = '';
				if ($pid <= 0) $pid = '';
//				if ($did <= 0)	$did = ''; 
				$did = '';
				$societe->id = $sid;
				$societe->name = $ligne[0];
				$societe->particulier = 0; //Société
				$societe->address = $ligne[1]."\n".$ligne[2]."\n".$ligne[3];  
				$societe->zip = $ligne[4];
				$societe->town = $ligne[5];
				$societe->state_id = $did;
				if ($ligne[12]) $societe->country_code = $ligne[12];
				$societe->country_id = $pid; 
dol_syslog("codes $pid " . $lige[12], LOG_DEBUG);
				$societe->tel = $ligne[6];
				$societe->fax = $ligne[7];
				$societe->email = $ligne[8];
				$societe->url = $ligne[9];
				$societe->idprof1 = $ligne[10];
				switch ($ligne[11]) {
					case '0' :
						$societe->fournisseur = 0;
						$societe->client = $ligne[11];
						break;
					case '1' :
						$societe->fournisseur = 0;
						$societe->client = $ligne[11];
						break;
					case '2' :
						$societe->fournisseur = 0;
						$societe->client = $ligne[11];
						break;		
					case '10' :
						$societe->client = 0;
						$societe->fournisseur = 1;
						break;
					default:
						;
					break;
				}
	//			$societe->client = $ligne[11];
				
				if ($ligne[13]) $societe->code_client = $ligne[13];


				if ($ligne[14]) $societe->array_options["options_zone"]=$ligne[14];
				if (!empty($ligne[15])) $societe->array_options["options_CA"]=$ligne[15];
// effectif
				if (!empty($ligne[16]))
				{
					   if ($ligne[16] <= 5) $societe->effectif_id = 1;
					   elseif ($ligne[16] <= 10) $societe->effectif_id = 2;
					   elseif ($ligne[16] <= 50) $societe->effectif_id = 3;
					   elseif ($ligne[16] <= 100) $societe->effectif_id = 4;
					   elseif ($ligne[16] <= 500) $societe->effectif_id = 5;
					   else $societe->effectif_id = 7;
				}
dol_syslog("effectif " . $lige[16].'  '.$societe->effectif_id." ".print_r($societe->array_options, true), LOG_DEBUG);
				switch ($typeimport)
				{					
				case 'C':
					if ($sid > 0) $this->process_msg .= $i.' '.$langs->trans("NonTraite").' '. $ligne[0].' '.$langs->trans("SocExist")."\n";
					elseif ($societe->create($this->user) < 0) $this->process_msg .= $i.' '.$langs->trans("SocImportErr"). $ligne[0]."  ".$societe->error."\n";
					break;
				case 'M':
					if ($sid>0) 
					{
						if ($societe->update($pid, $this->user) < 0)  $this->process_msg .= "Erreur ligne $i mise à jour Société ". $ligne[0]."  ".$societe->error."\n";
					}
					else $this->process_msg .= "Ligne $i non traitée ". $ligne[0]." Société  n'existe pas "."\n";
					break;
				case 'D':
					if ($sid > 0) 
					{ 
						$this->process_msg .= " $i suppression Société ". $ligne[0]."  ".$sid."\n";
						if ($societe->delete($sid) < 0 ) $this->process_msg .= "Erreur ligne $i suppression Société ". $ligne[0]."  ".$societe->error."\n";
					}
					else $this->process_msg .= "Ligne $i non traitée ". $ligne[0]." Société  n'existe pas "."\n";
				}
			}	
			break;

		case 'ImportContact':
			$i=0;
			$contact=new Contact($this->db);
			$societe = new Societe($this->db);
			
			while ($ligne = fgetcsv($fp,1000,";"))
			{
				$i++;
				if ($this->firstline && $i== 1) continue;
//				if ($societe->fetch('',$ligne[0]) < 0 ) $this->process_msg .= "erreur lecture Société "."\n";
				$socid = $this->get_socbyclientcode($ligne[0]);
				if ($socid < 0 ) $this->process_msg .= "erreur lecture Société "."\n";
				else $societe->fetch($socid);
				
				if (!$societe->id) {
					$this->process_msg .= "Code société inconnu ".$ligne[0]."\n";
					continue;
				}
				
				if (empty($ligne[1])) {
					$this->process_msg .="Ligne $i non traitée : le nom est obligatoire "."\n";
					continue;
				}
				

				$contactid = $this->get_contact_id($socid, $ligne[1], $ligne[2]) ;
 
				$contact->id = $contactid;
				$contact->civilite_id = $ligne[3];
				$contact->lastname=$ligne[1];
				$contact->firstname=$ligne[2];
				if ($ligne[4] || $ligne[5] || $ligne[6]) $contact->address=$ligne[4]."\n".$ligne[5]."\n".$ligne[6];
				else $contact->address= $societe->address;
				if ($ligne[7]) $contact->cp=$ligne[7];
				else $contact->cp= $societe->zip;
				if ($ligne[8]) $contact->town=$ligne[8];
				else $contact->town=$societe->town;
				if ($ligne[9]) {
					$pid = $this->get_pays_id($ligne[9]);
					if ($pid <= 0) $pid = ''; 
					$contact->country_id=$pid;
					$contact->country_code=$ligne[9];
				}
				else {
					$contact->country_id=$societe->country_id;
					$contact->country_code=$societe->country_code;
				}
				$contact->socid=$socid;					// fk_soc
				$contact->status=1;	
				$contact->email=$ligne[10];
				$contact->phone_pro = $ligne[11];	
				$contact->fax = $ligne[12];
				$contact->phone_mobile = $ligne[13];
				$contact->priv=0;

				switch ($typeimport)
				{					
				case 'C':
					if ($contactid > 0) $this->process_msg .= "Ligne $i non traitée ". $ligne[0]." Contact existe déjà "."\n";
					elseif ($contact->create($this->user) < 0) $this->process_msg .= "Erreur ligne $i création contact ". $ligne[0]."  ".$doliprod->error."\n";
					break;
				case 'M':
					if ($contactid>0) 
					{
						if ($contact->update($contactid, $this->user) < 0)  $this->process_msg .= "Erreur ligne $i mise à jour contact ". $ligne[0]."  ".$contact->error."\n";
					}
					else $this->process_msg .= "Ligne $i non traitée ". $ligne[1]." Contact  n'existe pas "."\n";
					break;
				case 'D':
					if ($contactid > 0) 
					{ 
						if ($contact->delete($contactid) < 0 ) $this->process_msg .= "Erreur ligne $i suppression contact ". $ligne[0]."  ".$contact->error."\n";
					}
					else $this->process_msg .= "Ligne $i non traitée ". $ligne[0]." Contact  n'existe pas "."\n";
				}
			}	
			break;

		case 'ImportActions':
			$i=0;
			$contact=new Contact($this->db);
			$societe = new Societe($this->db);
			$actioncomm = new ActionComm($this->db);
			$actuser = new User($this->db);
			
			while ($ligne = fgetcsv($fp,1000,";"))
			{
				$i++;
				if ($this->firstline && $i== 1) continue;
				
				//if ($societe->fetch('',$ligne[0]) < 0 ) $this->process_msg .= "erreur lecture Société "."\n";
				//else $socid = $societe->id;
				$socid = $this->get_socbyclientcode($ligne[0]);
				if ($socid < 0 ) $this->process_msg .= "erreur lecture Société "."\n";
				else $societe->fetch($socid);
				$socid = $societe->id;
				
				if (!$socid) 
				{
					$this->process_msg .= "Société non trouvée ".$ligne[0]." ligne $i non traitée"."\n";
					continue;
				}
								
				//action sur un contact de la soc
				if ($ligne[1])
				{
					$contactid = $this->get_contact_id($socid, $ligne[1], $ligne[2]) ;
					
					if ($contactid < 0) {
						$this->process_msg .= "Ligne $i non traitée ". $ligne[0].'  '.$ligne[1].'  '.$ligne[2]." Contact inconnu "."\n";
						// réinitialiser ??
						continue;
					}
					else $contact->fetch($contactid);
					
				}
				

				$usertodo = '';
				if ($ligne[9])
				{
					$usertodo=new User($this->db);
					if ( $usertodo->fetch('',$ligne[9]) < 0 ) $this->process_msg .= " ligne $i erreur lecture user ".$ligne[9]."\n";
				}
				$userdone= '' ;
				if ($ligne[10])
				{
					$usertodo=new User($this->db);
					if ( $usertodo->fetch('',$ligne[10]) < 0 ) $this->process_msg .= " ligne $i erreur lecture user ".$ligne[10]."\n";
					
				}
				$datep = '';
				if ($ligne[6])
				{
					// voir date
					$n = sscanf($ligne[6],"%02d/%02d/%04d", $d_day, $d_mon, $d_year);
					if ($n==3) $datep=dol_mktime(12, 0, 0, $d_mon, $d_day, $d_year);
					if (!$datep) $this->process_msg .= " ligne $i erreur conversion date ".$ligne[6].' '.$d_day. ' '.$d_mon.' '.$d_year."\n";
				}
				else $datep ='';
				$datef='';
				if ($ligne[7])
				{
					// voir la date
					$n = sscanf($ligne[7],"%02d/%02d/%04d", $d_day, $d_mon, $d_year);
					if ($n==3)$datef=dol_mktime(12, 0, 0, $d_mon, $d_day, $d_year);
					if (!$datef) $this->process_msg .= " ligne $i erreur conversion date ".$ligne[7].' '.$d_day. ' '.$d_mon.' '.$d_year."\n";	
				}
				else $datef ='';
				//$datef='';
				$actioncomm->societe = $societe;
				if ($ligne[1]) $actioncomm->contact = $contact;
				else $actioncomm->contact = '';
				$actioncomm->type_code = $ligne[3];
				$actioncomm->priority = $ligne[4];
				$actioncomm->location = '' ;
				$actioncomm->label = $ligne[5];
				$actioncomm->datep = $datep;
				$actioncomm->datef = $datef;
				$actioncomm->percentage = $ligne[8];
				$actioncomm->usertodo = $usertodo;
				$actioncomm->userdone = $userdone;
				$actioncomm->note =$ligne[11];
				
				switch ($typeimport)
				{					
				case 'C':
					$this->db->begin();
					if ($actioncomm->add($this->user) < 0) 
					{ 
						$this->process_msg .= "Erreur ligne $i création action société ".$actioncomm->error."\n";
						$this->db->rollback();
					}
					else $this->db->commit();
					break;
				case 'M':

					break;
				case 'D':
					break;
				}
			}
				
			break;

			case 'Importtarif' : // ref four
		
				$i=0;
				$this->process_msg = '';
				$error = 0;
				$doliprod = new Product($this->db);
				$product = new ProductFournisseur($this->db);
				$societe = new Societe($this->db);
		
				while ($ligne = fgetcsv($fp,1000,";"))
				{
					$i++;
					if ($this->firstline && $i== 1) continue;
		
					// recherche du fournisseur
					if ( $societe->fetch('', $ligne[2]) > 0) $sid = $societe->id;
					else {
						$this->process_msg .= $i.' '.$langs->trans("NonTraite"). $ligne[2]." ".$langs->trans("FourNotFound").' '.$societe->error."\n"; 
						$sid = '';						
					}
					
					if ($doliprod->fetch('', $ligne[0]) > 0) $pid = $doliprod->id;
					else { 
						$this->process_msg .=  $i  ." ".$langs->trans("NonTraite") . $ligne[0].$langs->trans("ProdNotFound")."  ".$doliprod->error."\n";
						$pid = '';
					}
					
					if ($sid > 0 && $pid > 0)
					{
						$result=$product->fetch($doliprod->id);
						if ($result > 0)
						{
							$this->db->begin();
							switch ($typeimport)
							{					
							case 'C':
								$ret=$product->add_fournisseur($this->user, $sid, $ligne[1], $ligne[3]);
								
								if ($ret < 0 && $ret != -3)
								{
									$this->process_msg .=  $i. " ".$langs->trans("NonTraite"). " " .$langs->trans("ErrRefFour").$product->error."\n";
									$error ++;
								}
								else 
								{
									// gestion du prix obligatoire
									$supplier=new Fournisseur($this->db);
									$result=$supplier->fetch($sid);
									// TODO taux de tva par défaut
									if ($ligne[5]) $tva_tx = $ligne[5];
									else $tva_tx = 19.6;
									$ret=$product->update_buyprice($ligne[3], $ligne[4] , $this->user, 'HT', $supplier, '', $ligne[1], $tva_tx );
									if ($ret < 0)
									{
										$this->process_msg .= $i. " ".$langs->trans("NonTraite"). " qty ".$ligne[3]. " , prix ".$ligne[4] ." " .$langs->trans("ErrPriceFour")." ".$supplier->error."\n";
										$error ++;
									}
									
								}
								break;
							case 'D':
								// suprresion de la ligne avec le même nb d'article et le même prix
								$sql = "SELECT pfp.rowid FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
								$sql .= " JOIN ".MAIN_DB_PREFIX."product_fournisseur pf ON pfp.fk_product_fournisseur = pf.rowid ";
								$sql .= " AND pf.fk_soc = '".$sid."' AND pf.fk_product='".$pid."'";
								$sql .= " WHERE pfp.quantity = '".$ligne[3]."' AND unitprice = ".$ligne[4];
								
								$resql = $this->db->query($sql);
								if ($resql)
								{
									$obj = $this->db->fetch_object($resql);
									if ($obj->rowid > 0)
									{
										$result=$product->remove_product_fournisseur_price($obj->rowid);
									}
									else $this->process_msg .= "ligne tarif inexistante ".$sql."\n";
								}
								else $this->process_msg .= "Erruer sql ".$sql."\n";
								break;
							}//switch
								
						if (! $error)
						{
							$this->db->commit();
						}
						else
						{
							$this->db->rollback();
						}
					} // fournisseur trouvé	
				}// traitement ligne							
			}// while reffour
			
			break;
		} // fin switch
	fclose($fp);	
	}
	else 
	{
		$this->error = "erreur ouverture fichier ".$nomfich;
		$error = -1;
	}		

	return $error;	
}

// fonctions de vérifications
function get_contact_id($socid, $contact_name, $contact_firstname)
{
	if ($contact_name) $contact_name = $this->db->escape(trim($contact_name));
	else return 0;
	
	if ($contact_firstname) $contact_firstname = $this->db->escape(trim($contact_firstname));
	
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople ";
	$sql .= "WHERE fk_soc ='".$socid."' AND name='".$contact_name."' ";
	if ($contact_firstname) $sql .= " AND firstname='".$contact_firstname."'";

	dol_syslog("D_importateur::get_contact_id sql=".$sql, LOG_DEBUG);
	$resql=$this->db->query($sql);
	if ($resql)
	{
		if ($this->db->num_rows($resql) )
		{
			// on renvoie toujours le prenmier
			$obj=$this->db->fetch_object($resql);		
			$id = $obj->rowid;
		}
		else $id = 0;
	}
	else	$id = -1;
	
return $id;	
}

// fonctions get_id
function get_pays_id($code_pays)
{
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_pays ";
	$sql .= "WHERE code ='".$code_pays."'";

	dol_syslog("D_importateur::get_pays_id sql=".$sql, LOG_DEBUG);
	$resql=$this->db->query($sql);
	if ($resql)
	{
		if ($this->db->num_rows($resql))
		{
			$obj=$this->db->fetch_object($resql);		
			$id = $obj->rowid;
		}
		else $id = 0;
	}
	else	$id = -1;
	
return $id;	
}

function get_departement_id($code_dept, $code_pays)
{
	if ($code_pays > 0)
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_departement ";
		$sql .= "Join ".MAIN_DB_PREFIX."c_regions r ON r.code_region = d.fk_region and r.fk_pays = '".$code_pays."' ";
		$sql .= "WHERE code_departement ='".$code_dept."'";
	
		dol_syslog("D_importateur::get_departemetn_id sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj=$this->db->fetch_object($resql);		
				$id = $obj->rowid;
			}
			else $id = 0;
		}
		else	$id = -1;
	}
	else $id = -2;	
return $id;	
}

function get_socbyclientcode($code_client)
{
	if (empty($code_client)) return 0;
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE code_client='".trim($code_client)."'";
	
	dol_syslog("D_importateur::get_socbyclientcode sql=".$sql, LOG_DEBUG);
	$resql=$this->db->query($sql);
	if ($resql)
	{
		if ($this->db->num_rows($resql))
		{
			$obj=$this->db->fetch_object($resql);		
			$id = $obj->rowid;
		}
		else $id = 0;
	}
	else $id = -1;
	
	return $id;
}
    
}
?>
