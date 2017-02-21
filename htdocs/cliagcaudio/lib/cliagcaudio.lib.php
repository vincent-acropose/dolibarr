<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/cliagcaudio.lib.php
 *	\ingroup	cliagcaudio
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function cliagcaudioAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("cliagcaudio@cliagcaudio");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/cliagcaudio/admin/cliagcaudio_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/cliagcaudio/admin/cliagcaudio_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@cliagcaudio:/cliagcaudio/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@cliagcaudio:/cliagcaudio/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'cliagcaudio');

    return $head;
}

function copyTemplateOdt($num_tpl)
{
	//ODT template
	$const_tpl_label = 'CLIAGC_AUDIO_TEMPLATE_ODT_'.$num_tpl;
	$const_tpl_val = $conf->global->{'CLIAGC_AUDIO_TEMPLATE_ODT_'.$num_tpl};
	if (isset($_FILES[$const_tpl_label]) && !$_FILES[$const_tpl_label]['error'])
	
	$src=$_FILES[$const_tpl_label]['tmp_name'];
	$dirodt=DOL_DATA_ROOT.'/cliagcaudio';
	$dest=$dirodt.'/'.$_FILES[$const_tpl_label]['name'];

	if (file_exists($src))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		dol_mkdir($dirodt);
		$result=dol_copy($src,$dest);
		if ($result < 0)
		{
			$langs->load("errors");
			setEventMessage($langs->trans('ErrorFailToCopyFile',$src,$dest), 'errors');
			return 0;
		}
		
		return $_FILES[$const_tpl_label]['name'];
	}
	
	return 0;
}

function getProjectContact(&$db, $fk_project, $num_relance, $email_require=true)
{
	global $conf;
	
	$TLabelTypeContact = array(
								'CLIENT'=>'service paiement' // Si le document est réservé au client, on n'est pas sensé avoir besoin d'un contact de la mututelle, mais au cas où on récupère celui du service paiement
								,'MUT_SRV_MEDECIN'=>'service medecin'
								,'MUT_SRV_PAIEMENT'=>'service paiement'
							);
	
	// Pour éviter les cas où l'accent sur médecin a été oublié, ou une majuscule
	$TStrConv = array('é'=>'e','  '=>' ');
	
	$destinataire_courrier = $conf->global->{'CLIAGC_AUDIO_DESTINATAIRE_'.$num_relance};
	
	$TRes = array();
	$sql = 'SELECT sp.rowid, sp.lastname, sp.firstname, sp.email 
			FROM '.MAIN_DB_PREFIX.'projet_customfields pc 
			INNER JOIN '.MAIN_DB_PREFIX.'socpeople sp ON (sp.fk_soc = pc.choixmutuelleprojet_idprof6_nom)
			WHERE pc.fk_projet = '.(int) $fk_project;
	if ($email_require)	$sql .= ' AND sp.email IS NOT NULL';
	
	$resql = $db->query($sql);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$nom = $line->lastname.' '.$line->firstname;
			if(strpos(strtr(strtolower($nom), $TStrConv), $TLabelTypeContact[$destinataire_courrier]) !== false) {

				$TRes[] = array(
					'fk_socpeople' => $line->rowid
					,'name' => $nom
					,'email' => $line->email
				);
				
			}
		}
	}
	
	return $TRes;
}


function sendMail(&$db, &$TIDProjet, $num_relance, $sujet_mail='', $contenu_mail='', $emetteur_mail='', $destinataire_mail='', $cc='', $cci='')
{
	global $conf,$user,$langs,$TMessage;
	
	$langs->load('cliagcaudio@cliagcaudio');
	
	define('INC_FROM_DOLIBARR', true);
	dol_include_once('/cliagcaudio/config.php');
	dol_include_once('/comm/action/class/actioncomm.class.php');
	dol_include_once('/recouvrement/class/dossier.class.php');
	dol_include_once('/contact/class/contact.class.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/core/class/CMailFile.class.php');
	dol_include_once('/core/lib/files.lib.php');

	$TError = array();
	$msgishtml = $conf->fckeditor->enabled && !empty($conf->global->FCKEDITOR_ENABLE_MAIL) ? 1 : 0;
	
	$nb_send = 0;
	
	$subject = $conf->global->{'CLIAGC_AUDIO_MSG_SUBJECT_'.$num_relance};
	if(!empty($sujet_mail)) $subject = $sujet_mail; // Depuis fiche projet
	$msg_conf_with_tags = $conf->global->{'CLIAGC_AUDIO_MSG_CONTENT_'.$num_relance};
	//if(!empty($contenu_mail)) $msg_conf_with_tags = $contenu_mail; // Depuis fiche projet
	$emetteur = $conf->global->MAIN_MAIL_EMAIL_FROM;
	if(!empty($emetteur_mail)) $emetteur = $emetteur_mail;
	
	if(empty($msg_conf_with_tags)) {
		$TMessage['errors'][] = 'Corps du mail vide pour la relance sélectionnée';
		return 0;
	}
	
	foreach ($TIDProjet as $fk_project)
	{
		$project = new Project($db);
		$project->fetch($fk_project);

		// On charge le contact concerné (celui de la mutuelle)
		$TContact = getProjectContact($db, $project->id, $num_relance, false);
		$fk_socpeople = 0;
		if(!empty($TContact)) $fk_socpeople = $TContact[0]['fk_socpeople'];
		$contact = new Contact($db);
		if(!empty($fk_socpeople)) $contact->fetch($fk_socpeople);
		
		$destinataire = &$mutuelle;
		
		// Mail destinataire manuel
		if(!empty($destinataire_mail)) $mail = $destinataire_mail;
		elseif($conf->global->{'CLIAGC_AUDIO_DESTINATAIRE_'.$num_relance} === 'CLIENT') {
			$destinataire = &$soc;
			$contact = ''; // Si le mail est pour le client, on n'attache pas le contact de la mutuelle à l'événement agenda, ça n'a pas de sens.
			$mail = $soc->email;
		}
		else $mail = $contact->email;
		
		//pre($TFacturesHTML, true);exit;
		if(empty($mail) || !isValidEmail($mail)) {
			
			$TMessage['warnings'][] = 'Mail destinataire invalide, ou aucun mail trouvé pour le dossier <a href="'.dol_buildpath('/projet/fiche.php?id='.$project->id, 2).'">'.$project->ref.'</a>';
			continue;
			
		}
		
		// Conversion des tags du mail :
		if(!empty($contenu_mail)) $msg = $contenu_mail;
		else $msg = getContentMail($db, $project, $contact, $msg_conf_with_tags, $num_relance);
		
		/*echo '<pre>';
		print_r($msg);
		echo '</pre>';*/
		// Ajout des pièces jointes
		//$TFileName = _get_pieces_jointe($project, $soc->id, $num_relance);
		
		$filename_list = array();
		$mimetype_list = array();
		$mimefilename_list = array();
		if(!empty($TFileName)) {
			foreach($TFileName as $file) {
				$filename = basename($file);
				$mimefile=dol_mimetype($file);
				$filename_list[] = $file;
				$mimetype_list[] = $mimefile;
				$mimefilename_list[] = $filename;
			}
		}
		
		$cci_ = $emetteur.(!empty($cci) ? ','.$cci : '');
		
		// Construct mail
		$CMail = new CMailFile(
			$subject
			,$mail
			,$emetteur
			,$msg
			,$filename_list
			,$mimetype_list
			,$mimefilename_list
			,$cc //,$addr_cc=""
			,$cci_ //,$addr_bcc=""
			,'' //,$deliveryreceipt=0
			,$msgishtml //,$msgishtml=0*/
			,$conf->global->MAIN_MAIL_ERRORS_TO
			//,$css=''
		);
		
		// Send mail
		$CMail->sendfile();
		if(!empty($TFileName)) {
			foreach($TFileName as $fname) unlink($fname); // Suppression des fichiers temporaires après envoi
		}
		
		// Create agenda event
		if ($CMail->error) $TMessage['errors'] = $CMail->error;
		else {
			$nb_send++;
			_createEvent($project, $destinataire, $contact, $subject, $msg, $num_relance, 'mail', $emetteur, $mail);
		}
		
	}
//exit;
	if($nb_send > 0) $TMessage['ok'][] = 'Nombre de mails envoyés : '.$nb_send;
}

function getContentMail(&$db, &$project, &$contact, $msg_conf_with_tags, $num_relance) {
	
	global $conf,$user,$langs,$TMessage;
	
	$TBS=new TTemplateTBS();
	
	// On charge le tiers associé au projet
	$soc = _getClient($db, $project);
	
	// On charge la mutuelle associée au projet
	$mutuelle = _getMutuelle($project);
	
	// Récupération des factures
	$TRes = _getFacturesProjet($TBS, $project, $soc);
	$TFacturesHTML = $TRes['tableau'];
	$factures_montant_total = $TRes['montant_total'];
	$factures_total_paiements = $TRes['total_paiements'];
	$ref_factures = $TRes['ref_factures'];
	$date_paiement_facture = $TRes['date_paiement'];
	$label_paiement_facture = $TRes['libelle_virement'];
	$montant_paiement_facture = $TRes['montant_paiement'];
	
	// Récupération des cautions
	$TRes = _getFacturesProjet($TBS, $project, $soc, 'caution');
	$TCautionsHTML = $TRes['tableau'];
	$cautions_montant_total = $TRes['montant_total'];
	$cautions_total_paiements = $TRes['total_paiements'];
	$ref_cautions = $TRes['ref_factures'];
	$date_paiement_caution = $TRes['date_paiement'];
	$label_paiement_caution = $TRes['libelle_virement'];
	$montant_paiement_caution = $TRes['montant_paiement'];
	
	$signature=$user->signature;
	
	$obj_infos_client = _getInfosFromCustomFields('societe_customfields', 'fk_societe', $soc->id);
	$obj_infos_projet = _getInfosFromCustomFields('projet_customfields', 'fk_projet', $project->id, array('cg_nomenclatures nom', MAIN_DB_PREFIX.'user u', 'cg_magasins mag'), array('claturemut_idclassement_numnomenclatures = nom.rowid', 'u.rowid = audioprojet_firstname_lastname', 'magasins_ville = mag.id'));
	
	// Remplacement des tags TBS
	$msg = $TBS->render($msg_conf_with_tags,array(
		'client_infos'=>array($obj_infos_client)
		,'projet_infos'=>array($obj_infos_projet)
	
	),array(
		'date'=>dol_print_date(time(), 'daytext')
		,'date_15'=>dol_print_date(strtotime('+15 day'), 'daytext')
		,'date_maj_etape'=>dol_print_date($obj_infos_projet->dateetapeprojet, 'daytext')
		,'logo'=>DOL_DATA_ROOT."/mycompany/logos/".MAIN_INFO_SOCIETE_LOGO
		,'signature'=>dol_string_nohtmltag($signature, 0)
		
		// Facture
		,'ref_factures'=>$ref_factures
		,'factures'=>$TFacturesHTML
		,'factures_montant_total'=>price($factures_montant_total)
		,'factures_total_ttc_restant'=>price(price2num($factures_montant_total)-$factures_total_paiements, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE)
		,'factures_date_paiement'=>$date_paiement_facture
		,'factures_libelle_paiement'=>$label_paiement_facture
		,'factures_montant_paiement'=>price($montant_paiement_facture, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE)
		
		// Facture
		,'ref_cautions'=>$ref_cautions
		,'cautions'=>$TCautionsHTML
		,'cautions_montant_total'=>price($cautions_montant_total)
		,'cautions_total_ttc_restant'=>price(price2num($cautions_montant_total)-$cautions_total_paiements, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE)
		,'cautions_date_paiement'=>$date_paiement_caution
		,'cautions_libelle_paiement'=>$label_paiement_caution
		,'cautions_montant_paiement'=>price($montant_paiement_caution, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE)
		
		// Client
		,'client_nom'=>$soc->nom
		,'client_adresse'=>$soc->address
		,'client_adresse_cp'=>$soc->zip
		,'client_adresse_ville'=>$soc->town
		,'client_niss'=>$obj_infos_client->nissclient
		,'client_numero_adherent'=>$obj_infos_projet->numeroclientmutuelle
		
		// Nomenclature
		,'nomenclature_numero'=>$obj_infos_projet->numnomenclatures
		,'nomenclature_description'=>$obj_infos_projet->description
		
		// Mutuelle
		,'mutuelle_nom'=>$mutuelle->nom
		,'mutuelle_adresse'=>$mutuelle->address
		,'mutuelle_adresse_cp'=>$mutuelle->zip
		,'mutuelle_adresse_ville'=>$mutuelle->town
		
		// Contact Mutuelle
		,'contact_mutuelle_nom'=>$contact->lastname.' '.$contact->firstname
		,'contact_mutuelle_adresse'=>$contact->address
		,'contact_mutuelle_adresse_cp'=>$contact->zip
		,'contact_mutuelle_adresse_ville'=>$contact->town
		
		// Projet
		,'ref_projet'=>$project->ref
		,'date_accord_medecin'=>_getDateAccordMedecin($project)
	));
	
	return $msg;
	
}

function _getFacturesProjet(&$TBS, &$project, &$soc, $type='facture') {
	
	global $db, $conf;
	
	dol_include_once('/compta/facture/class/facture.class.php');
	
	$total_paiements = 0;
	$ref_factures='';

    // On récupère les factures de type standard liées au projet :
    // Construction du tableau de factures avec leur montant
	$sql = 'SELECT rowid, type FROM '.MAIN_DB_PREFIX.'facture WHERE fk_projet = '.$project->id;
	$resql = $db->query($sql);
	
	$montant_total = 0;
	while($res = $db->fetch_object($resql)) {
		$fact = new Facture($db);
		$fact->fetch($res->rowid);
		
		// Pour la mutuelle, on ne doit récupérer que la facture standard, et pas les cautions (acomptes)
		if($fact->id <= 0) continue;
		
		if(($res->type > 0 && $type === 'facture') || ($res->type == 0 && $type !== 'facture')) continue;
		
		// On récupère le montant des règlements
		$somme_paiements_fact = $fact->getSommePaiement();
		$total_paiements += $somme_paiements_fact;
		
		// On récupère également le montant des paiements issus d'acomptes :
		$q = "SELECT SUM(re.amount_ttc) as total
			  FROM " . MAIN_DB_PREFIX . "societe_remise_except as re
			  WHERE fk_facture = ".$fact->id;
		$resultset = $db->query($q);
		$ress = $db->fetch_object($resultset);
		if($ress->total > 0) $total_paiements += $ress->total;
		
		$montant_fact = _getMontant("", $fact);
		$montant_total += _getMontant("", $fact, 'simple');
		
		$total_ttc = price($fact->total_ttc, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE);
		$reste_a_payer = price(price2num($montant_fact)-$somme_paiements_fact, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE);
		
		// Récupération & label date paiement
		$query = 'SELECT DATE_FORMAT(p.datep, "%d/%m/%Y") as date_paiement, p.note, p.amount FROM '.MAIN_DB_PREFIX.'paiement p
				  INNER JOIN '.MAIN_DB_PREFIX.'paiement_facture pf ON (p.rowid = pf.fk_paiement)
				  WHERE pf.fk_facture = '.$fact->id.'
				  ORDER BY pf.rowid DESC LIMIT 1';
		$res_set = $db->query($query);
		$res = $db->fetch_object($res_set);
		$date_paiement = $res->date_paiement;
		$montant_paiement = $res->amount;
		$note = $res->note;
		
		$TFact[] = array(
			"ref" => $fact->ref
			, "total_ttc" => $total_ttc
			, "date"=>date("d/m/Y", $fact->date)
			, "reste_a_payer"=>$reste_a_payer
		);
		
        if(!empty($ref_factures)) $ref_factures.=', ';
        $ref_factures.=  $fact->ref;
		
	}
	
	if(!empty($TFact)){
		$TFacturesHTML = $TBS->render(dol_buildpath('/recouvrement/tpl/mail.tpl.php')
			,array(
				'facture'=>$TFact
			)/*
			,array(
				'total_ttc_dossier'=>price(price2num($this->getMontant(''))-$total_paiements, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE)
			)*/
		);
	}
	return array('tableau'=>$TFacturesHTML, 'ref_factures'=>$ref_factures, 'montant_total'=>$montant_total, 'total_paiements'=>$total_paiements, 'date_paiement'=>$date_paiement, 'libelle_virement'=>$note, 'montant_paiement'=>$montant_paiement);
	
}

function _getMontant($currency = "EUR", $fact, $mode='') {
	global $conf;
	
	$montant = $fact->total_ttc;
	
	if($mode == 'simple') return $montant;
	return number_format(round($montant,2),2,',',' ').' '.$currency;
}

function _getClient(&$db, &$project) {
	
	dol_include_once('/societe/class/societe.class.php');
	
	$soc = new Societe($db);
	$soc->fetch($project->socid);
	
	return $soc;
	
}

function _getMutuelle(&$project) {
	
	global $db, $conf;
	
	dol_include_once('/societe/class/societe.class.php');
	
	$sql = 'SELECT choixmutuelleprojet_idprof6_nom
			FROM '.MAIN_DB_PREFIX.'projet_customfields
			WHERE fk_projet = '.$project->id;
	
	$resql = $db->query($sql);
	$res = $db->fetch_object($resql);
	
	$mutuelle = new Societe($db);
	$mutuelle->fetch($res->choixmutuelleprojet_idprof6_nom);
	
	return $mutuelle;
	
}

function _getInfosFromCustomFields($table, $field, $fk_element, $table_jointure='', $champs_jointure='') {
	
	global $db;
	
	$sql = 'SELECT *
			FROM '.MAIN_DB_PREFIX.$table;
	if(!empty($table_jointure)) {
		foreach($table_jointure as $k=>$table) {
			$sql.= ' LEFT JOIN '.$table.' ON ('.$champs_jointure[$k].')';
		}
	}
	
	$sql.= ' WHERE '.$field.' = '.$fk_element;
	//echo $sql;exit;
	$resql = $db->query($sql);
	return $db->fetch_object($resql);
	
}

function _createEvent(&$project, &$destinataire, &$contact, $subject, $message, $num_relance, $type='mail', $emetteur_mail, $destinataire_mail)
{
	global $db, $user, $langs, $conf;
	
	$TTitle = unserialize($conf->global->CLIAGC_AUDIO_TRELANCE_TITLE);
	
	dol_include_once('/comm/action/class/actioncomm.class.php');
	$langs->load('mails');
	
	$actionmsg = $actionmsg2 = '';
	
	$sendto = '';
	
	if ($message)
	{
		if($type === 'mail') {
			if(is_object($contact)) $sendto = $contact->email;
			else $sendto = $destinataire->email;
			$actionmsg2='Mail : "'.$TTitle[$num_relance].'"';
			$emetteur = $user->email;
			if(!empty($emetteur_mail)) $emetteur = $emetteur_mail;
			$dest = $sendto;
			if(!empty($destinataire_mail)) $dest = $destinataire_mail;
			$actionmsg=$langs->transnoentities('MailRelanceSentBy').' '.$emetteur.' à '.$dest;
			$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
			$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
			$actionmsg = dol_concatdesc($actionmsg, $message);
		} else {
			$actionmsg2 = 'Courrier : "'.$TTitle[$num_relance].'"';
			$actionmsg = 'Fichier généré disponible dans l\'onglet "Fichiers joints"';
		}
	}
	
	$societeforaction=new Societe($db);
    if ($destinataire->id > 0) $societeforaction->fetch($destinataire->id);
	$now=dol_now();
	
	$event = new ActionComm($db);
	$event->type_code   = 'AC_EMAIL';		// code of parent table llx_c_actioncomm (will be deprecated)
	$event->code='AC_PROJECT_SENTBYMAIL';
	if($type !== 'mail'){
		$event->type_code = 'AC_DOC';
		$event->code='';
	}
	$event->label       = $actionmsg2;
	$event->note        = $actionmsg;
	$event->datep       = $now;
	$event->datef       = $now;
	$event->durationp   = 0;
	$event->punctual    = 1;
	$event->percentage  = 100;   // Not applicable
	$event->contact     = $contact;
	$event->societe     = $societeforaction;
	$event->fk_project  = $project->id;
	$event->author      = $user;   // User saving action
	$event->usertodo    = $user;	// User action is assigned to (owner of action)
	$event->userdone    = $user;	// User doing action (deprecated, not used anymore)

	$event->fk_element  = $project->id;
	$event->elementtype = $project->element;
	
	return $event->add($user);
}


function generateDoc(&$db, &$TIDProjet, $num_relance, $path_pj)
{
	global $db, $conf, $langs, $TMessage;
	
	if (!defined('INC_FROM_DOLIBARR')) define('INC_FROM_DOLIBARR', 1);
	dol_include_once('/cliagcaudio/config.php');
	
	$dir = $conf->cliagcaudio->dir_output;
	$dir_temp = $conf->cliagcaudio->dir_temp;
	
	$template = $conf->global->{'CLIAGC_AUDIO_TEMPLATE_ODT_'.$num_relance};
	$templateFilePath = $dir.'/'.$template;
	
	if(empty($template)) {
		$TMessage['errors'][] = 'Aucun modèle trouvé pour la relance sélectionnée';
		return 0;
	}
	
	$TFile = array();
	
	$time = date('YmdHis');
	$nom_fichier = $time.'_publipostage';
	
	foreach ($TIDProjet as $fk_project)
	{
		$project = new Project($db);
		$project->fetch($fk_project);
		
		if (!empty($project->id))
		{
			$filePath = createFile($db, $project, $templateFilePath, $dir_temp, $num_relance, $nom_fichier, $path_pj);
			if ($filePath)
			{
				$TFile[] = $filePath;
			}
		}
	}
	
	if (!empty($TFile))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
		$pdf=pdf_getInstance();
		if (class_exists('TCPDF'))
		{
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($langs));
	
		if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);
	
		$pagecount = concat($pdf, $TFile);
		if ($pagecount)
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			$file_output = $dir.'/publipostage/'.$nom_fichier.'.pdf';
			$pdf->Output($file_output.'','F');
		}
		
		$TMessage['ok'][] = 'Document créé avec succès';
		
	}
}

function createFile(&$db, &$project, $templateFilePath, $dir_temp, $num_relance, $nom_fichier, $path_pj='')
{
	global $db, $user, $conf, $langs;
	
	dol_include_once('/contact/class/contact.class.php');
	dol_include_once('/core/lib/functions.lib.php');
	
	$TBS=new TTemplateTBS();
	$TBS->TBS->protect=false;
	$TBS->TBS->noerr=true;
	
	// Chargement client
	$soc = _getClient($db, $project);
	
	// Chargement mutuelle
	$mutuelle = _getMutuelle($project);
	
	// On cherche le contact concerné (celui de la mutuelle)
	$TContact = getProjectContact($db, $project->id, $num_relance, false);
	if(!empty($TContact)) $fk_socpeople = $TContact[0]['fk_socpeople'];
	$contact = new Contact($db);
	if(!empty($fk_socpeople)) $contact->fetch($fk_socpeople);
	
	// Récupération des factures
	$TRes = _getFacturesProjet($TBS, $project, $soc);
	$TFacturesHTML = $TRes['tableau'];
	$factures_montant_total = $TRes['montant_total'];
	$factures_total_paiements = $TRes['total_paiements'];
	$ref_factures = $TRes['ref_factures'];
	$date_paiement_facture = $TRes['date_paiement'];
	$label_paiement_facture = $TRes['libelle_virement'];
	$montant_paiement_facture = $TRes['montant_paiement'];
	
	// Récupération des cautions
	$TRes = _getFacturesProjet($TBS, $project, $soc, 'caution');
	$TCautionsHTML = $TRes['tableau'];
	$cautions_montant_total = $TRes['montant_total'];
	$cautions_total_paiements = $TRes['total_paiements'];
	$ref_cautions = $TRes['ref_factures'];
	$date_paiement_caution = $TRes['date_paiement'];
	$label_paiement_caution = $TRes['libelle_virement'];
	$montant_paiement_caution = $TRes['montant_paiement'];

	$signature=$user->signature;
	
	$obj_infos_client = _getInfosFromCustomFields('societe_customfields', 'fk_societe', $soc->id);
	$obj_infos_projet = _getInfosFromCustomFields('projet_customfields', 'fk_projet', $project->id, array('cg_nomenclatures nom', MAIN_DB_PREFIX.'user u', 'cg_magasins mag'), array('claturemut_idclassement_numnomenclatures = nom.rowid', 'u.rowid = audioprojet_firstname_lastname', 'magasins_ville = mag.id'));
	//var_dump($obj_infos_client, $obj_infos_projet);exit;
	$destinataire = &$mutuelle;
	if($conf->global->{'CLIAGC_AUDIO_DESTINATAIRE_'.$num_relance} === 'CLIENT') {
		$destinataire = &$soc;
		$contact = ''; // Si le mail est pour le client, on n'attache pas le contact de la mutuelle à l'événement agenda, ça n'a pas de sens.
	}
	
	$path = $dir_temp.'/'.$nom_fichier.'_'.$project->ref.'.odt';
	if(!empty($path_pj)) {
		if(!is_dir($path_pj)) @mkdir($path_pj);
		$path = $path_pj.$nom_fichier.'_'.$project->ref.'.odt';
	}
	$file_path = $TBS->render(
		$templateFilePath
		,array(
			'client_infos'=>array($obj_infos_client)
			,'projet_infos'=>array($obj_infos_projet)
		)
		,array(
			'date'=>dol_print_date(time(), 'daytext')
			,'date_15'=>dol_print_date(strtotime('+15 day'), 'daytext')
			,'date_maj_etape'=>dol_print_date($obj_infos_projet->dateetapeprojet, 'daytext')
			,'logo'=>DOL_DATA_ROOT."/mycompany/logos/".MAIN_INFO_SOCIETE_LOGO
			,'signature'=>dol_string_nohtmltag($signature, 0)
			
			// Facture
			,'ref_factures'=>$ref_factures
			,'factures'=>$TFacturesHTML
			,'factures_montant_total'=>price($factures_montant_total)
			,'factures_total_ttc_restant'=>price(price2num($factures_montant_total)-$factures_total_paiements, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE)
			,'factures_date_paiement'=>$date_paiement_facture
			,'factures_libelle_paiement'=>$label_paiement_facture
			,'factures_montant_paiement'=>price($montant_paiement_facture, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE)
			
			// Facture
			,'ref_cautions'=>$ref_cautions
			,'cautions'=>$TCautionsHTML
			,'cautions_montant_total'=>price($cautions_montant_total)
			,'cautions_total_ttc_restant'=>price(price2num($cautions_montant_total)-$cautions_total_paiements, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE)
			,'cautions_date_paiement'=>$date_paiement_caution
			,'cautions_libelle_paiement'=>$label_paiement_caution
			,'cautions_montant_paiement'=>price($montant_paiement_caution, 0, '', 1, -1, -1, $conf->global->MAIN_MONNAIE)
			
			// Client
			,'client_nom'=>$soc->nom
			,'client_adresse'=>$soc->address
			,'client_adresse_cp'=>$soc->zip
			,'client_adresse_ville'=>$soc->town
			,'client_niss'=>$obj_infos_client->nissclient
			,'client_numero_adherent'=>$obj_infos_projet->numeroclientmutuelle
			
			// Nomenclature
			,'nomenclature_numero'=>$obj_infos_projet->numnomenclatures
			,'nomenclature_description'=>$obj_infos_projet->description
			
			// Mutuelle
			,'mutuelle_nom'=>$mutuelle->nom
			,'mutuelle_adresse'=>$mutuelle->address
			,'mutuelle_adresse_cp'=>$mutuelle->zip
			,'mutuelle_adresse_ville'=>$mutuelle->town
			
			// Contact Mutuelle
			,'contact_mutuelle_nom'=>$contact->lastname.' '.$contact->firstname
			,'contact_mutuelle_adresse'=>$contact->address
			,'contact_mutuelle_adresse_cp'=>$contact->zip
			,'contact_mutuelle_adresse_ville'=>$contact->town
			
			// Projet
			,'ref_projet'=>$project->ref
			,'date_accord_medecin'=>_getDateAccordMedecin($project)
			)
		,array()
		,array(
			'outFile'=>$path
			,'convertToPDF'=>true
			,'charset'=>OPENTBS_ALREADY_UTF8
		)
	);
	
	$id_event = _createEvent($project, $destinataire, $contact, $subject, 'Fichier concerné : '.$nom_fichier.'.pdf', $num_relance, 'doc');
	if($id_event > 0) addLink($nom_fichier.'.pdf', $id_event);
	
	return strtr($path, array('.odt'=>'.pdf'));
}

function addLink($filename, $id_event) {
	
	global $db, $conf, $user;
	
	dol_include_once('/core/class/link.class.php');
	
	$linkObject = new Link($db);
	$linkObject->entity = $conf->entity;
	$linkObject->url = dol_buildpath('/document.php?modulepart=cliagcaudio&file=/publipostage/'.$filename, 1);
	$linkObject->objecttype = 'action';
	$linkObject->objectid = $id_event;
	$linkObject->label = $filename;
	$res = $linkObject->create($user);
	
}

function _getDateAccordMedecin($project)
{
	global $db;
	
	$sql = 'SELECT DATE_FORMAT(dateaccordmedmut, "%d/%m/%Y") AS dateaccordmedmut
			FROM '.MAIN_DB_PREFIX.'projet_customfields
			WHERE fk_projet = '.$project->id;
			
	$resql = $db->query($sql);
	$res = $db->fetch_object($resql);
	
	return $res->dateaccordmedmut;
}

function concat(&$pdf, $TFile)
{
	foreach($TFile as $file)
	{
		$pagecount = $pdf->setSourceFile($file);
		for ($i = 1; $i <= $pagecount; $i++)
		{
			$tplidx = $pdf->ImportPage($i);
			$s = $pdf->getTemplatesize($tplidx);
			$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
			$pdf->useTemplate($tplidx);
		}
	}
	
	return $pagecount;
}

function selectLieuProjet(&$db, $lieu)
{
	global $langs;
	
	$sql = 'SELECT id, ville FROM cg_magasins ORDER BY ville';
	$resql = $db->query($sql);
	
	$html= '<label class="flat" for="search_lieu">'.$langs->trans('Town').'</label>:&nbsp;';
	$html.= '<select name="search_lieu">';
	$html.= '<option value=""></option>';
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$html .= '<option '.($lieu == $line->id ? 'selected="selected"' : '').' value="'.$line->id.'">'.$line->ville.'</option>';
		}
	}
	$html .= '</select>';
	
	return $html;
}

function factureLink(&$db, &$objp)
{
	global $langs;
	
	$socid = $objp->socid;
	$fk_projet = $objp->projectid;
	$description = $objp->description;
	
	$TFactureHtml = array();
	$facturestatic = new Facture($db);
	
	$sql = 'SELECT rowid, facnumber, fk_statut, paye FROM '.MAIN_DB_PREFIX.'facture WHERE fk_projet = '.$fk_projet.' AND fk_soc = '.$socid.' ORDER BY facnumber';
	$resql = $db->query($sql);
	
	if(stripos($description, 'FACTURE-') !== false || stripos($description, '1214-') !== false) return 'Facture ancien sytème';
	
	if ($resql)
	{
		$i=0;
		while ($line = $db->fetch_object($resql))
		{
			if ($i < 5)
			{
				$facturestatic->id = $line->rowid;
				$facturestatic->ref = $line->facnumber;
				$facturestatic->statut = $line->statut;
				$TFactureHtml[] = $facturestatic->getNomUrl(1).' '.$facturestatic->LibStatut($line->paye,$line->fk_statut,3);
			}
			
			$i++;
		}
		
		if ($i > 5) $TFactureHtml[] = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$socid.'">'.$langs->transnoentitiesnoconv('soMuchInvoices', $i-5).'</a>';
	}
	
	return implode('<br />', $TFactureHtml);
}

// Retourne une description du dernier événement agenda associé à un projet pasé en param
function getLastActionComm(&$project) {
	
	global $db;
	
	$sql = 'SELECT id FROM '.MAIN_DB_PREFIX.'actioncomm WHERE fk_project = '.$project->id.' ORDER BY id DESC LIMIT 1';
	$resql = $db->query($sql);
	$res = $db->fetch_object($resql);
	
	$id_event = $res->id;
	
	if(!empty($id_event)) {
		dol_include_once('/comm/action/class/actioncomm.class.php');
		$a = new ActionComm($db);
		$a->fetch($id_event);
		$txt = '<b>Titre</b> : '.$a->label.'<br />';
		$txt.= '<b>Date de début</b> : '.date('d/m/Y', $a->datep).'<br />';
		$txt.= '<b>Date de fin</b> : '.date('d/m/Y', $a->datef).'<br />';
		$txt.= '<b>Description</b> : '.$a->note.'<br />';
	}
	
	return array('txt'=>$txt, 'id'=>$id_event);
	
}

function _get_pieces_jointe(&$projet, $id_tiers, $num_relance, $folder='projet') {
	
	global $db, $conf;
	
	$destinataire_courrier = $conf->global->{'CLIAGC_AUDIO_DESTINATAIRE_'.$num_relance}; // MUT_SRV_MEDECIN ou MUT_SRV_PAIEMENT
	
	$societe = new Societe($db);
	$societe->fetch($id_tiers);
	
	// Recherche dans le dossier message du projet
	if($folder === 'projet') {
		$dir = $conf->projet->multidir_output[1] . "/" . $projet->id . '/message/';
		if(!is_dir($dir)) $dir = $conf->projet->multidir_output[1] . "/" . $projet->ref . '/message/'; // Parfois les dossiers ont pour nom le code du client
	} elseif($folder === 'tiers') { // Recherche dans le dossier message du tiers si inexistant dans pj
		if(!is_dir($dir)) $dir = $conf->societe->multidir_output[1] . "/" . $societe->id . '/message/';
		if(!is_dir($dir)) $dir = $conf->societe->multidir_output[$societe->entity] . "/" . $societe->code_client . '/message/'; // Parfois les dossiers ont pour nom le code du client
	}

	// Si aucun dossier message, fin de l'histoire
	if($folder === 'projet' && !is_dir($dir)) {
		_get_pieces_jointe($projet, $id_tiers, $num_relance, 'tiers');
		return 0;
	} elseif($folder === 'tiers' && !is_dir($dir)) return 0;

	$TFilesTemp = scandir($dir);
	$TFiles = array();
	
	// On ne garde que les fichiers
	foreach($TFilesTemp as $f) {
		if(is_file($dir.$f)) $TFiles[] = $dir.$f;
	}
	
	$TFileName = array();
	
	if(!empty($TFiles)) {
		foreach($TFiles as $filename) {
			
			$str_file = file_get_contents($filename);

			if(
				($destinataire_courrier === 'MUT_SRV_MEDECIN'
				&& (strpos($str_file, 'Subject: medecin conseil') !== false
				|| strpos($str_file, 'Subject : medecin conseil') !== false
				|| strpos($str_file, 'Subject :medecin conseil') !== false
				|| strpos($str_file, 'Subject: médecin conseil') !== false))
				
				|| ($destinataire_courrier === 'MUT_SRV_PAIEMENT'
				&& (strpos($str_file, 'Subject: tiers payant') !== false
				|| strpos($str_file, 'Subject : tiers payant') !== false
				|| strpos($str_file, 'Subject :tiers payant') !== false))
			) {
				$TFileName = array_merge($TFileName, _build_attached_file($dir, $str_file));
			}
		}
		
	}
	
	// Si aucun fichier dans le dossier projet, on tente avec celui du tiers
	if($folder === 'projet' && empty($TFileName)) {
		_get_pieces_jointe($projet, $id_tiers, $num_relance, 'tiers');
		return 0;
	}
	
	return array_unique($TFileName);

}

function _cmp_date_create_file($a, $b) {
	
	return (filemtime($a) < filemtime($b)) ? 1 : -1;
	
}

function _build_attached_file($dir, $str) {
	
	$TMatches = array();
	
	preg_match_all("/.*filename=\"?(.*)\"?/", $str, $TMatches);
	
	if(empty($TMatches)) return 0;
	
	$TFileToBuild = array();
	
	foreach($TMatches[1] as $i=>$pj) {
		
		$pj = strtr($pj, array('"'=>'', ' '=>'_'));
		$pj = trim($pj);
		
		$TRes = explode($TMatches[0][$i], $str);
		$TRes2 = explode("\n", $TRes[1]);
		
		$str_final = '';
		
		foreach($TRes2 as $i=>$line) {
			if(strlen($line) > 60) { // Premier ligne dont la taille est supérieure à 60 char
				while($i<=count($TRes2)) {
					$l = trim($TRes2[$i]);
					// On s'arrête au boundary (qui entoure les pj)
					if(preg_match("/^--.*/", $l)) break;
					$str_final.= $l;
					$i++;
				}
				break;
			}
		}

		if(!empty($str_final)) $TFileToBuild[$pj] = trim($str_final);
		
	}
	
	$TFileName = array();
	
	// Construction des fichiers temporaires
	if(!empty($TFileToBuild)) {
		foreach($TFileToBuild as $pj_name=>$str_b64) {
			$fname = sys_get_temp_dir().'/'.$pj_name;
			$TFileName[] = $fname;
			$f = fopen($fname, 'w+');
			chmod($fname, 0777);
			fwrite($f, base64_decode($str_b64));
			fclose($f);
		}
		return $TFileName;
	}
	
}
