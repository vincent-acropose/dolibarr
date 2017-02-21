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
 * \file    class/actions_cliagcaudio.class.php
 * \ingroup cliagcaudio
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionscliagcaudio
 */
class Actionscliagcaudio
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $conf, $TMessage;
			
		dol_include_once('/cliagcaudio/lib/cliagcaudio.lib.php');
		dol_include_once('/core/lib/functions.lib.php');

		$actionATM = GETPOST('actionATM');
		
		if($actionATM === 'send_relance_from_card') {
			
			$content_mail = GETPOST('content_mail');
			$subject_mail = GETPOST('subject_mail');
			$num_relance = GETPOST('num_relance');
			$emetteur_list = GETPOST('emetteur_list');
			$emetteur = GETPOST('emetteur');
			if(!empty($emetteur_list)) $emetteur = $emetteur_list;
			$destinataire = GETPOST('destinataire');
			$cc = GETPOST('cc');
			$cci = GETPOST('cci');
			
			$array = array($object->id);
			sendMail($db, $array, $num_relance, $subject_mail, $content_mail, $emetteur, $destinataire, $cc, $cci);

		}
		
		if($actionATM === 'create_relance_odt_from_card') {
			
			$num_relance = GETPOST('num_relance');
			
			$array = array($object->id);
			generateDoc($db, $array, $num_relance, $conf->projet->multidir_output[$conf->entity].'/'.dol_sanitizeFileName($object->ref).'/');

		}
		
		// Affichage des messages utilisateurs
		if(!empty($TMessage['ok'])) setEventMessage(implode('<br />', $TMessage['ok']));
		if(!empty($TMessage['errors'])) setEventMessage(implode('<br />', $TMessage['errors']), 'errors');
		if(!empty($TMessage['warnings'])) setEventMessage(implode('<br />', $TMessage['warnings']), 'warnings');

	}
	
	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		
		global $db, $conf;
		
		if($action === 'create') return 0;

		define('INC_FROM_DOLIBARR', true);
		
		dol_include_once('/cliagcaudio/config.php');
		dol_include_once('/core/class/doleditor.class.php');
		dol_include_once('/cliagcaudio/lib/cliagcaudio.lib.php');
		
		// Récupération des différentes relances disponibles
		$TTitreRelance = unserialize($conf->global->CLIAGC_AUDIO_TRELANCE_TITLE);

		if(!empty($TTitreRelance)) {
			
			$txt = '<div id="div_send_relance" title="Relance mail" style="display:none;">
						<form name="send_relance" method="POST" action="">
							<input type="hidden" name="actionATM" value="send_relance_from_card" />';
			$txt .=  'Emetteur : <input type="text" placeholder="Défaut si vide" id="emetteur" name="emetteur" /> ';
			$txt .= '<select name="emetteur_list">
						<option value="0"></option>
						<option value="info@maison-appareil-auditif.be">info@maison-appareil-auditif.be</option>
						<option value="liege@maison-appareil-auditif.be">liege@maison-appareil-auditif.be</option>
						<option value="charleroi@maison-appareil-auditif.be">charleroi@maison-appareil-auditif.be</option>
						<option value="commandemaisondelaudition@gmail.com">commandemaisondelaudition@gmail.com</option>
					</select>';
			$txt .=  '<br /><br />';
			$txt .=  'Destinataire : <input type="text" id="destinataire" placeholder="Défaut si vide" name="destinataire" /> ';
			$txt .=  '<br /><br />';
			$txt .=  'CC (séparées par ",") : <input type="text" id="cc" placeholder="mail1@test.fr,mail2@test.fr" name="cc" /> ';
			$txt .=  '<br /><br />';
			$txt .=  'CCI (séparées par ",") : <input type="text" id="cci" placeholder="mail1@test.fr,mail2@test.fr" name="cci" /> ';
			$txt .=  '<br /><br />';
			$txt.= 'Relance : <select name="num_relance">';
			foreach($TTitreRelance as $num=>$titre) $txt .= '<option value="'.$num.'">'.$titre.'</option>';
			$txt .= '</select>';
			
			foreach($TTitreRelance as $num=>$titre) {

				// On charge le contact concerné (celui de la mutuelle)
				$TContact = getProjectContact($db, $object->id, $num, false);
				$fk_socpeople = 0;
				if(!empty($TContact)) $fk_socpeople = $TContact[0]['fk_socpeople'];
				$contact = new Contact($db);
				if(!empty($fk_socpeople)) $contact->fetch($fk_socpeople);
				
				// Affichage des différents contenus
				print '<div style="display:none;" id="relance_num_'.$num.'" >'.getContentMail($db, $object, $contact, $conf->global->{'CLIAGC_AUDIO_MSG_CONTENT_'.$num}, $num).'</div>';
				
			}
			
			foreach($TTitreRelance as $num=>$titre) print '<div style="display:none;" id="relance_subject_'.$num.'">'.$conf->global->{'CLIAGC_AUDIO_MSG_SUBJECT_'.$num}.'</div>';
			$txt .=  '<br /><br />';
			reset($TTitreRelance);
			$key = key($TTitreRelance);
			$txt .=  'Sujet mail : <input type="text" id="subject_mail" name="subject_mail" value="'.$conf->global->{'CLIAGC_AUDIO_MSG_SUBJECT_'.$key}.'" />';
			$txt .=  '<br /><br />Contenu mail :';
			$editor=new DolEditor('content_mail',getContentMail($db, $object, $contact, $conf->global->{'CLIAGC_AUDIO_MSG_CONTENT_'.$key}, $num),'',270,'dolibarr_notes','In', true, true, true, 120);
			$txt .= $editor->Create(1);
			//print 'Contenu mail :<br /><textarea id="content_mail" name="content_mail" rows="20" cols="60">'.$conf->global->{'CLIAGC_AUDIO_MSG_CONTENT_'.$key}.'</textarea>';
			$txt .= '<br /><br />';
			$txt .=  '<input type="SUBMIT" class="butAction" value="Envoyer">';
			$txt .=  '</form>';
			$txt .=  '</div>';
			
			// Form publipostage odt pdf
			$txt2 = '<div id="create_relance_odt" title="Relance odt/pdf">';
			$txt2.= '<form name="create_relance_odt" method="POST" action="">';
			$txt2.= '<input type="hidden" name="actionATM" value="create_relance_odt_from_card" />';
			$txt2.= 'Relance : <select name="num_relance">';
			foreach($TTitreRelance as $num=>$titre) $txt2 .= '<option value="'.$num.'">'.$titre.'</option>';
			$txt2.= '</select>';
			$txt2 .= '<input type="SUBMIT" class="butAction" value="Créer document">';
			$txt2.= '</form>';
			$txt2.= '</div>';
			
		}
		
		?>
			<script language="JavaScript" type="text/JavaScript">
			
				$(document).ready(function() {
					var form_send_relance = <?php echo json_encode($txt); ?>;
					var form_create_relance_odt = <?php echo json_encode($txt2); ?>;
					
					var btn = $('<input type="button" class="butAction" value="Relance mail" />');
					var btn_relance_odt = $('<input type="button" class="butAction" value="Relance odt/pdf" />');
					var num_relance = $("[name=num_relance]");
					
					$("div.tabsAction").prepend(btn,btn_relance_odt);
					
					btn.click(function() {
						
						$(form_send_relance).first().dialog({
							width: 900,
        					height: 700,
        					open: function(event, ui) {
						
								$(document).append($(form_send_relance).last());
						
								$("[name=num_relance]").unbind().bind('change', function() {
									var val = $(this).val();
									$("#subject_mail").val($("#relance_subject_" + $(this).val()).html());
									CKEDITOR.instances['content_mail'].setData($("#relance_num_" + $(this).val()).html());
									
								});
								
        					}
						});
					
					});
					
					btn_relance_odt.click(function() {
						
						$(form_create_relance_odt).first().dialog({
							width: 400,
        					height: 80,
						});
					
					});
					
					
					
				});
				
			</script>
		<?php
		
	}
	
	function invoicedao($parameters, &$object, &$action, $hookmanager)
	{
	}
}
