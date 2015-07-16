<?php
/* Copyright (C) 2011-2012 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Philippe Grand <philippe.grand@atoo-net.com>
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
 *	\file       /ultimatepdf/class/actions_ultimatepdf.class.php
 *	\ingroup    ultimatepdf
 *	\brief      ultimatepdf designs actions class files
 */

dol_include_once('/ultimatepdf/class/dao_ultimatepdf.class.php','DaoUltimatepdf');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';

/**
 *	\class      ActionsUltimatepdf
 *	\brief      Ultimatepdf designs actions class files
 */
class ActionsUltimatepdf
{
	var $db;
	var $dao;

	var $mesg;
	var $error;
	var $errors= array();
	//! Numero de l'erreur
	var $errno = 0;
	
	var $id;
	
	var $template_dir;
	var $template;

	var $label;
	var $description;
	var $value;
	var $cancel;
	var $dashdotted;
	var $bgcolor;
	var $bordercolor;
	var $textcolor;
	var $qrcodecolor;
	var $withref;
	var $widthref;
	var $withoutvat;
	var $otherlogo;
	var $otherfont;
	var $heightforfreetext;
	var $freetextfontsize;
	var $usebackground;
	var $imglinesize;
	var $logoheight;
	var $logowidth;
	var $otherlogoheight;
	var $otherlogowidth;
	var $invertSenderRecipient;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;

	var $options=array();
	var $designs=array();
	var $tpl=array();


	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Instantiation of DAO class
	 *
	 * @return	void
	 */
	private function getInstanceDao()
	{
		if (! is_object($this->dao))
		{
			$this->dao = new DaoUltimatepdf($this->db);
		}
	}


	/**
	 * 	Enter description here ...
	 *
	 * 	@param	string	$action		Action type
	 */
	function doActions($parameters = false, &$object, &$action = '')
	{
		dol_syslog ( get_class ( $this ) . ':: doActions', LOG_DEBUG );
		
		global $conf,$user,$langs,$hookmanager;
		
		$this->getInstanceDao();
		
		$id=GETPOST('id','int');
		$label=GETPOST('label','alpha');
		$description=GETPOST('description','alpha');
		$value=GETPOST('value','int');
		$cancel=GETPOST('cancel');
		$dashdotted=GETPOST('dashdotted');
		$bgcolor=GETPOST('bgcolor');
		$bordercolor=GETPOST('bordercolor');
		$textcolor=GETPOST('textcolor');
		$qrcodecolor=GETPOST('qrcodecolor');
		$withref=GETPOST('withref');
		$widthref=GETPOST('widthref');
		$withoutvat=GETPOST('withoutvat');
		$otherlogo=GETPOST('otherlogo');
		$otherfont=GETPOST('otherfont');
		$heightforfreetext=GETPOST('heightforfreetext');
		$freetextfontsize=GETPOST('freetextfontsize');
		$usebackground=GETPOST('usebackground');
		$imglinesize=GETPOST('imglinesize');
		$logoheight=GETPOST('logoheight');
		$logowidth=GETPOST('logowidth');
		$otherlogoheight=GETPOST('otherlogoheight');
		$otherlogowidth=GETPOST('otherlogowidth');
		$invertSenderRecipient=GETPOST('invertSenderRecipient');
		$marge_gauche=GETPOST('marge_gauche');
		$marge_droite=GETPOST('marge_droite');
		$marge_haute=GETPOST('marge_haute');
		$marge_basse=GETPOST('marge_basse');

		
		if (is_object($object) && $object->table_element == 'propal')
		{
			if ( ! empty ( $object->id ) && $action == 'filemerge') {
			
				dol_include_once ( '/ultimatepdf/class/propalmergedpdf.class.php' );
				
				$filetomerge_file_array=GETPOST('filetoadd');			
				
				//Delete all file already associated
				$filetomerge = new Propalmergedpdf ( $this->db );		

				$filetomerge->delete_by_propal ($user, $object->id);			
					
				//for each file checked add it to the proposal
				if (is_array($filetomerge_file_array)) {
					foreach ($filetomerge_file_array as $filetomerge_file) {
						$filetomerge->fk_propal = $object->id;
						$filetomerge->file_name = $filetomerge_file;
							
						$filetomerge->create ( $user );
					} 
				}		
			}
			return 0;
		}
		elseif (is_object($object) && $object->table_element == 'facture')
		{
			if ( ! empty ( $object->id ) && $action == 'filemerge') {
			
				dol_include_once ( '/ultimatepdf/class/invoicemergedpdf.class.php' );
				
				$filetomerge_file_array=GETPOST('filetoadd');			
				
				//Delete all file already associated
				$filetomerge = new Invoicemergedpdf ( $this->db );		

				$filetomerge->delete_by_invoice ($user, $object->id);			
					
				//for each file checked add it to the proposal
				if (is_array($filetomerge_file_array)) {
					foreach ($filetomerge_file_array as $filetomerge_file) {
						$filetomerge->fk_facture = $object->id;
						$filetomerge->file_name = $filetomerge_file;
							
						$filetomerge->create ( $user );
					} 
				}		
			}
			return 0;
		}
		elseif (is_object($object) && $object->table_element == 'commande')
		{
			if ( ! empty ( $object->id ) && $action == 'filemerge') {
			
				dol_include_once ( '/ultimatepdf/class/ordermergedpdf.class.php' );
				
				$filetomerge_file_array=GETPOST('filetoadd');			
				
				//Delete all file already associated
				$filetomerge = new Ordermergedpdf ( $this->db );		

				$filetomerge->delete_by_order ($user, $object->id);			
					
				//for each file checked add it to the proposal
				if (is_array($filetomerge_file_array)) {
					foreach ($filetomerge_file_array as $filetomerge_file) {
						$filetomerge->fk_commande = $object->id;
						$filetomerge->file_name = $filetomerge_file;
							
						$filetomerge->create ( $user );
					} 
				}		
			}
			return 0;
		}
		else
		{

			if (GETPOST('add') && empty($this->cancel) && $user->admin)
			{
				$error=0;

				if (! $label)
				{
					$error++;
					array_push($this->errors, $langs->trans("ErrorFieldRequired",$langs->transnoentities("Label") ) );
					$action = 'create';
				}

				// Verify if label already exist in database
				if ($label)
				{
					$this->dao->getDesigns();
					if (! empty($this->dao->designs))
					{
						$label = strtolower(trim($label));

						foreach($this->dao->designs as $design)
						{
							if (strtolower($design->label) == $label) $error++;
						}
						if ($error)
						{
							array_push($this->errors, $langs->trans("ErrorDesignLabelAlreadyExist") );
							$action = 'create';
						}
					}
				}

				if (! $error)
				{
					$this->db->begin();

					$this->dao->label = $label;
					$this->dao->description = $description;

					$this->dao->options['dashdotted'] = $dashdotted;
					$this->dao->options['bgcolor'] = $bgcolor;
					$this->dao->options['bordercolor'] = $bordercolor;
					$this->dao->options['textcolor'] = $textcolor;
					$this->dao->options['qrcodecolor'] = $qrcodecolor;
					$this->dao->options['withref'] = $withref;
					$this->dao->options['widthref'] = $widthref;
					$this->dao->options['withoutvat'] = $withoutvat;
					$this->dao->options['otherlogo'] = $otherlogo;
					$this->dao->options['otherfont'] = $otherfont;
					$this->dao->options['heightforfreetext'] = $heightforfreetext;
					$this->dao->options['freetextfontsize'] = $freetextfontsize;
					$this->dao->options['usebackground'] = $usebackground;
					$this->dao->options['imglinesize'] = $imglinesize;
					$this->dao->options['logoheight'] = $logoheight;
					$this->dao->options['logowidth'] = $logowidth;
					$this->dao->options['otherlogoheight'] = $otherlogoheight;
					$this->dao->options['otherlogowidth'] = $otherlogowidth;
					$this->dao->options['invertSenderRecipient'] = $invertSenderRecipient;
					$this->dao->options['marge_gauche'] = $marge_gauche;
					$this->dao->options['marge_droite'] = $marge_droite;
					$this->dao->options['marge_haute'] = $marge_haute;
					$this->dao->options['marge_basse'] = $marge_basse;
					

					$id = $this->dao->create($user);
					if ($id <= 0)
					{
						$error++;
						$errors=($this->dao->error ? array($this->dao->error) : $this->dao->errors);
						$action = 'create';
					}

					if (! $error && $id > 0)
					{
						$this->db->commit();
					}
					else
					{
						$this->db->rollback();
					}
				}
			}

			if ($action == 'edit' && $user->admin && ! $user->design)
			{
				$error=0;

				if ($this->dao->fetch($id) < 0)
				{
					$error++;
					array_push($this->errors, $langs->trans("ErrorDesignIsNotValid"));
					$_GET["action"] = $_POST["action"] = '';
				}
			}

			if (GETPOST('update') && $id && $user->admin && ! $user->design)
			{
				$error=0;

				$ret = $this->dao->fetch($id);
				if ($ret < 0)
				{
					$error++;
					array_push($this->errors, $langs->trans("ErrorDesignIsNotValid"));
					$action = '';
				}
				else if (! $label)
				{
					$error++;
					array_push($this->errors, $langs->trans("ErrorFieldRequired",$langs->transnoentities("Label") ) );
					$action = 'edit';
				}

				if (! $error)
				{
					$this->db->begin();

					$this->dao->label = $label;
					$this->dao->description	= $description;

					$this->dao->options['dashdotted'] = (GETPOST('dashdotted') ? GETPOST('dashdotted') : null);
					$this->dao->options['bgcolor'] = (GETPOST('bgcolor') ? GETPOST('bgcolor') : null);
					$this->dao->options['bordercolor'] = (GETPOST('bordercolor') ? GETPOST('bordercolor') : null);
					$this->dao->options['textcolor'] = (GETPOST('textcolor') ? GETPOST('textcolor') : null);
					$this->dao->options['qrcodecolor'] = (GETPOST('qrcodecolor') ? GETPOST('qrcodecolor') : null);
					$this->dao->options['withref'] = (GETPOST('withref') ? GETPOST('withref') : 'no');
					$this->dao->options['widthref'] = (GETPOST('widthref') ? GETPOST('widthref') : null);
					$this->dao->options['withoutvat'] = (GETPOST('withoutvat') ? GETPOST('withoutvat') : 'no');
					$this->dao->options['otherlogo'] = (GETPOST('otherlogo') ? GETPOST('otherlogo') : null);
					$this->dao->options['otherfont'] = (GETPOST('otherfont') ? GETPOST('otherfont') : null);
					$this->dao->options['heightforfreetext'] = (GETPOST('heightforfreetext') ? GETPOST('heightforfreetext') : null);
					$this->dao->options['freetextfontsize'] = (GETPOST('freetextfontsize') ? GETPOST('freetextfontsize') : null);
					$this->dao->options['usebackground'] = (GETPOST('usebackground') ? GETPOST('usebackground') : null);
					$this->dao->options['imglinesize'] = (GETPOST('imglinesize') ? GETPOST('imglinesize') : null);
					$this->dao->options['logoheight'] = (GETPOST('logoheight') ? GETPOST('logoheight') : null);
					$this->dao->options['logowidth'] = (GETPOST('logowidth') ? GETPOST('logowidth') : null);
					$this->dao->options['otherlogoheight'] = (GETPOST('otherlogoheight') ? GETPOST('otherlogoheight') : null);
					$this->dao->options['otherlogowidth'] = (GETPOST('otherlogowidth') ? GETPOST('otherlogowidth') : null);
					$this->dao->options['invertSenderRecipient'] = (GETPOST('invertSenderRecipient') ? GETPOST('invertSenderRecipient') : 'no');
					$this->dao->options['marge_gauche'] = (GETPOST('marge_gauche') ? GETPOST('marge_gauche') : null);
					$this->dao->options['marge_droite'] = (GETPOST('marge_droite') ? GETPOST('marge_droite') : null);
					$this->dao->options['marge_haute'] = (GETPOST('marge_haute') ? GETPOST('marge_haute') : null);
					$this->dao->options['marge_basse'] = (GETPOST('marge_basse') ? GETPOST('marge_basse') : null);
					
					$ret = $this->dao->update($id,$user);

					if ($ret <= 0)
					{
						$error++;
						$errors=($this->dao->error ? array($this->dao->error) : $this->dao->errors);
						$action = 'edit';
					}

					if (! $error && $ret > 0)
					{

						dolibarr_set_const($this->db, "ULTIMATE_DASH_DOTTED", $dashdotted,'chaine',0,'',$this->dao->entity);

						dolibarr_set_const($this->db, "ULTIMATE_BGCOLOR_COLOR", $bgcolor,'chaine',0,'',$this->dao->entity);

						dolibarr_set_const($this->db, "ULTIMATE_BORDERCOLOR_COLOR", $bordercolor,'chaine',0,'',$this->dao->entity);

						dolibarr_set_const($this->db, "ULTIMATE_TEXTCOLOR_COLOR", $textcolor,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_QRCODECOLOR_COLOR", $qrcodecolor,'chaine',0,'',$this->dao->entity);

						dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_REF", $withref,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_REF_WIDTH", $widthref,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT", $withoutvat,'chaine',0,'',$this->dao->entity);

						dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO", $otherlogo,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "MAIN_PDF_FORCE_FONT", $otherfont,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "MAIN_PDF_FREETEXT_HEIGHT", $heightforfreetext,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATEPDF_FREETEXT_FONT_SIZE", $freetextfontsize,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "MAIN_USE_BACKGROUND_ON_PDF", $usebackground,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "MAIN_DOCUMENTS_WITH_PICTURE_WIDTH", $imglinesize,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_LOGO_HEIGHT", $logoheight,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_LOGO_WIDTH", $logowidth,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_HEIGHT", $otherlogoheight,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_WIDTH", $otherlogowidth,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_INVERT_SENDER_RECIPIENT", $invertSenderRecipient,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_LEFT", $marge_gauche,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_RIGHT", $marge_droite,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_TOP", $marge_haute,'chaine',0,'',$this->dao->entity);
						
						dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_BOTTOM", $marge_basse,'chaine',0,'',$this->dao->entity);

						$this->db->commit();

					}
					else
					{
						$this->db->rollback();
					}
				}
			}
			
			/*if (GETPOST('sendit') && $id)
			{$return = $this->getOtherLogo();
					if ($ret <= 0)
					{
						$error++;
						$errors=($this->dao->error ? array($this->dao->error) : $this->dao->errors);
						$action = 'edit';
					}
					$action='edit';
			}*/

			if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' && $user->admin && ! $user->design)
			{
				$error=0;

				if ($id == 1)
				{
					$error++;
					array_push($this->errors, $langs->trans("ErrorNotDeleteMasterDesign") );
					$action = '';
				}

				if (! $error)
				{
					if ($this->dao->fetch($id) > 0)
					{
						if ($this->dao->delete($id) > 0)
						{
							$this->mesg=$langs->trans('ConfirmedDesignDeleted');
						}
						else
						{
							$this->error=$this->dao->error;
							$action = '';
						}
					}
				}
			}

			if ($action == 'setactive' && $user->admin && ! $user->design)
			{
				$this->dao->setDesign($id,'active',$value);
			}			

		}
	}

	/**
	 *	Return combo list of designs.
	 *
	 *	@param	int		$selected	Preselected design
	 *	@param	string	$option		Option
	 *	@return	void
	 */
	function select_designs($selected='', $htmlname='design', $option='', $login=0)
	{
		global $user,$langs;

		$this->getInstanceDao();

		$this->dao->getDesigns($login);

		$return = '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'"'.$option.'>';
		if (is_array($this->dao->designs))
		{
			foreach ($this->dao->designs as $design)
			{
				if ($design->active == 1 && ($user->admin && ! $user->design))
				{
					$return.= '<option value="'.$design->id.'" ';
					if ($selected == $design->id)	$return.= 'selected="selected"';
					$return.= '>';
					$return.= $design->label;
					$return.= '</option>';
				}
			}
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *	Return multiselect list of designs.
	 *
	 *	@param	string	$htmlname	Name of select
	 *	@param	array	$current	Current design to manage
	 *	@param	string	$option		Option
	 *	@return	void
	 */
	function multiselect_designs($htmlname, $current, $option='')
	{
		global $conf, $langs;

		$this->getInstanceDao();
		$this->dao->getDesigns();

		$return = '<select id="'.$htmlname.'" class="multiselect" multiple="multiple" name="'.$htmlname.'[]" '.$option.'>';
		if (is_array($this->dao->designs))
		{
			foreach ($this->dao->designs as $design)
			{
				if (is_object($current) && $current->id != $design->id && $design->active == 1)
				{
					$return.= '<option value="'.$design->id.'" ';
					if (is_array($current->options[$htmlname]) && in_array($design->id, $current->options[$htmlname]))
					{
						$return.= 'selected="selected"';
					}
					$return.= '>';
					$return.= $design->label;

					$return.= '</option>';
				}
			}
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *    Switch to another design.
	 *    @param	id		Id of the destination design
	 */
	function switchDesign($id)
	{
		global $conf,$user;

		$this->getInstanceDao();

		if ($this->dao->fetch($id) > 0)
		{
			// Controle des droits sur le changement
			if($this->dao->verifyRight($id,$user->id) || $user->admin)
			{
				dolibarr_set_const($this->db, "ULTIMATE_DESIGN", $id,'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_DASH_DOTTED", $this->dao->options['dashdotted'],'chaine',0,'',$conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_BGCOLOR_COLOR", $this->dao->options['bgcolor'],'chaine',0,'',$conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_BORDERCOLOR_COLOR", $this->dao->options['bordercolor'],'chaine',0,'',$conf->entity);

				dolibarr_set_const($this->db, "ULTIMATE_TEXTCOLOR_COLOR", $this->dao->options['textcolor'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_QRCODECOLOR_COLOR", $this->dao->options['qrcodecolor'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_REF", $this->dao->options['withref'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_DOCUMENTS_WITH_REF_WIDTH", $this->dao->options['widthref'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT", $this->dao->options['withoutvat'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO", $this->dao->options['otherlogo'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "MAIN_PDF_FORCE_FONT", $this->dao->options['otherfont'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "MAIN_PDF_FREETEXT_HEIGHT", $this->dao->options['heightforfreetext'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATEPDF_FREETEXT_FONT_SIZE", $this->dao->options['freetextfontsize'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "MAIN_USE_BACKGROUND_ON_PDF", $this->dao->options['usebackground'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "MAIN_DOCUMENTS_WITH_PICTURE_WIDTH", $this->dao->options['imglinesize'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_LOGO_HEIGHT", $this->dao->options['logoheight'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_LOGO_WIDTH", $this->dao->options['logowidth'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_HEIGHT", $this->dao->options['otherlogoheight'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO_WIDTH", $this->dao->options['otherlogowidth'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_INVERT_SENDER_RECIPIENT", $this->dao->options['invertSenderRecipient'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_LEFT", $this->dao->options['marge_gauche'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_RIGHT", $this->dao->options['marge_droite'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_TOP", $this->dao->options['marge_haute'],'chaine',0,'',$conf->entity);
				
				dolibarr_set_const($this->db, "ULTIMATE_PDF_MARGIN_BOTTOM", $this->dao->options['marge_basse'],'chaine',0,'',$conf->entity);

				return 1;
			}
			else
			{
				return -2;
			}
		}
		else
		{
			return -1;
		}
	}

	/**
	 * 	Get design info
	 * 	@param	id	Object id
	 */
	function getInfo($id)
	{
		$this->getInstanceDao();
		$this->dao->fetch($id);

		$this->label		= $this->dao->label;
		$this->description	= $this->dao->description;
	}

	/**
	 * 	Get action title
	 * 	@param	action	Type of action
	 */
	function getTitle($action='')
	{
		global $langs;

		if ($action == 'create') return $langs->trans("AddDesign");
		else if ($action == 'edit') return $langs->trans("EditDesign");
		else return $langs->trans("DesignsManagement");
	}

	/**
	 *    Assigne les valeurs pour les templates
	 *    @param      action     Type of action
	 */
	function assign_values(&$action = 'view')
	{
		global $conf,$langs,$user;
		global $form,$formother,$formadmin;

		$this->getInstanceDao();

		$this->template_dir = dol_buildpath('/ultimatepdf/tpl/');

		if ($action == 'create')
		{
			$this->template = 'ultimatepdf_create.tpl.php';
		}
		else if ($action == 'edit')
		{
			$this->template = 'ultimatepdf_edit.tpl.php';

			if (!empty($id)) $ret = $this->dao->fetch($id);
		}

		if ($action == 'create' || $action == 'edit')
		{
			// Label
			$this->tpl['label'] = ($label?$label:$this->dao->label);

			// Description
			$this->tpl['description'] = ($description?$description:$this->dao->description);

			// Dash dotted
			$ddvalue=array('0' => $langs->trans('ContinuousLine'), '8, 2' => $langs->trans('DottedLine'));
			$this->tpl['select_dashdotted'] = $form->selectarray('dashdotted',$ddvalue,($dashdotted?$dashdotted:$this->dao->options['dashdotted']));

			// Bgcolor
			$this->tpl['select_bgcolor'] = $formother->selectColor(($bgcolor?$bgcolor:$this->dao->options['bgcolor']), 'bgcolor', '', 1);

			// Bordercolor
			$this->tpl['select_bordercolor'] = $formother->selectColor(($bordercolor?$bordercolor:$this->dao->options['bordercolor']), 'bordercolor', '', 1);

			// Textcolor
			$this->tpl['select_textcolor'] = $formother->selectColor(($textcolor?$textcolor:$this->dao->options['textcolor']), 'textcolor', '', 1);
			
			// QRcodecolor
			$this->tpl['select_qrcodecolor'] = $formother->selectColor(($qrcodecolor?$qrcodecolor:$this->dao->options['qrcodecolor']), 'qrcodecolor', '', 1);
			
			// withref		
			$this->tpl['select_withref'] = $form->selectyesno('withref',($withref?$withref:$this->dao->options['withref']),0,false);
			
			// Ref width	
			$this->tpl['select_widthref'] = ($widthref?$widthref:$this->dao->options['widthref']);
			
			// withoutvat		
			$this->tpl['select_withoutvat'] = $form->selectyesno('withoutvat',($withoutvat?$withoutvat:$this->dao->options['withoutvat']),0,false);

			// Otherlogo
			$this->tpl['select_otherlogo'] = ($otherlogo?$otherlogo:$this->dao->options['otherlogo']);
			
			// Other font
			$fontvalue=array('Helvetica' => 'Helvetica', 'DejaVuSans' => 'DejaVuSans', 'FreeMono' => 'FreeMono');
			$this->tpl['select_otherfont'] = $form->selectarray('otherfont',$fontvalue,($otherfont?$otherfont:$this->dao->options['otherfont']));
			
			// heightforfreetext
			$this->tpl['select_heightforfreetext'] = ($heightforfreetext?$heightforfreetext:$this->dao->options['heightforfreetext']);
			
			// freetextfontsize
			$this->tpl['select_freetextfontsize'] = ($freetextfontsize?$freetextfontsize:$this->dao->options['freetextfontsize']);
			
			// Use background on pdf
			$this->tpl['usebackground'] = ($usebackground?$usebackground:$this->dao->options['usebackground']);
			
			// Set image width
			$this->tpl['imglinesize'] = ($imglinesize?$imglinesize:$this->dao->options['imglinesize']);
			
			// Set logo height
			$this->tpl['logoheight'] = ($logoheight?$logoheight:$this->dao->options['logoheight']);
			
			// Set logo width
			$this->tpl['logowidth'] = ($logowidth?$logowidth:$this->dao->options['logowidth']);
			
			// Set otherlogo height
			$this->tpl['otherlogoheight'] = ($otherlogoheight?$otherlogoheight:$this->dao->options['otherlogoheight']);
			
			// Set otherlogo width
			$this->tpl['otherlogowidth'] = ($otherlogowidth?$otherlogowidth:$this->dao->options['otherlogowidth']);
			
			// Invert sender and recipient
			$this->tpl['invertSenderRecipient'] = $form->selectyesno('invertSenderRecipient',($invertSenderRecipient?$invertSenderRecipient:$this->dao->options['invertSenderRecipient']),0,false);
			
			// Set marge_gauche
			$this->tpl['marge_gauche'] = ($marge_gauche?$marge_gauche:$this->dao->options['marge_gauche']);
			
			// Set marge_droite
			$this->tpl['marge_droite'] = ($marge_droite?$marge_droite:$this->dao->options['marge_droite']);
			
			// Set marge_haute
			$this->tpl['marge_haute'] = ($marge_haute?$marge_haute:$this->dao->options['marge_haute']);
			
			// Set marge_basse
			$this->tpl['marge_basse'] = ($marge_basse?$marge_basse:$this->dao->options['marge_basse']);
			
		}
		else
		{

			$this->dao->getDesigns();

			$this->tpl['designs']		= $this->dao->designs;
			$this->tpl['img_on'] 		= img_picto($langs->trans("Activated"),'on');
			$this->tpl['img_off'] 		= img_picto($langs->trans("Disabled"),'off');
			$this->tpl['img_modify'] 	= img_edit();
			$this->tpl['img_delete'] 	= img_delete();

			// Confirm delete
			if ($_GET["action"] == 'delete')
			{
				$this->tpl['action_delete'] = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".GETPOST('id'),$langs->trans("DeleteDesign"),$langs->trans("ConfirmDeleteDesign"),"confirm_delete",'',0,1);
			}

			$this->template = 'ultimatepdf_view.tpl.php';
		}
	}

	/**
	 *    Display the template
	 */
	function display()
	{
		global $conf, $langs;
		global $bc;

		include($this->template_dir.$this->template);
	}
	
	/**
	 * 	Set values of global conf for ultimatepdf
	 * 
	 * 	@param	Conf	$conf	Object conf
	 * 	@return void
	 */
	function setValues(&$conf)
	{
		$this->getInstanceDao();
		
		$this->dao->fetch($conf->design);		
	
		// Load configuration of current design
		$this->config = $this->dao->getDesignConfig($design);
		$this->setConstant($conf, $element);								
	}

	/**
	 * 	Get design to use
	 *
	 * 	@param	string	$element	Current element
	 * 	@return	int					Design id to use
	 */
	function getDesign($element=false)
	{
		global $conf;

		$addzero = array('user', 'usergroup');
		if (in_array($element, $addzero))
		{
			return '0,'.$conf->design;
		}

		if (! empty($element) && ! empty($this->designs[$element]))
		{			
			return $this->designs[$element];			
		}

		return $conf->design;		
	}
	

	/**
	 *
	 */
	function printTopRightMenu()
	{
		return $this->getTopRightMenu();
	}

	/**
	 * 	Show design info
	 */
	private function getTopRightMenu()
	{
		global $conf,$user,$langs;

		$langs->load('ultimatepdf@ultimatepdf');

		$out='';
		$form=new Form($this->db);
		$this->getInfo($conf->design);
		$this->getInstanceDao();
		$this->dao->getDesigns($login);
		if (is_array($this->dao->designs))
		{
			$htmltext ='<u>'.$langs->trans("Design").'</u>'."\n";
			foreach ($this->dao->designs as $design)
			{
				if ($design->active == 1 && ($user->admin && ! $user->design))
				{
					if ($conf->global->ULTIMATE_DESIGN == $design->id)	
					{
						$htmltext.='<br><b>'.$langs->trans("Label").'</b>: '.$design->label."\n";
						$htmltext.='<br><b>'.$langs->trans("Description").'</b>: '.$design->description."\n";
					}
				}
			}
		}
		$text ='<a href="#">';
		$text = img_picto('', 'object_ultimatepdf.png@ultimatepdf','id="switchdesign" class="design linkobject"');
		$text.='</a>';

		$out.= $form->textwithtooltip('',$htmltext,2,1,$text,'login_block_elem',2);

		$out.= '<script type="text/javascript">
			$( "#switchdesign" ).click(function() {
				$( "#dialog-switchdesign" ).dialog({
					modal: true,
					width: 400,
					buttons: {
						\''.$langs->trans('Ok').'\': function() {
							choice=\'ok\';
							$.get( "'.dol_buildpath('/ultimatepdf/ajaxswitchdesign.php',1).'", {
								action: \'switchdesign\',
								design: $( "#design" ).val()
							},
							function(content) {
								$( "#dialog-switchdesign" ).dialog( "close" );
							});
						},
						\''.$langs->trans('Cancel').'\': function() {
							choice=\'ko\';
							$(this).dialog( "close" );
						}
					},
					close: function(event, ui) {
						if (choice == \'ok\') {
							location.href=\''.DOL_URL_ROOT.'\';
						}
					}
				});
			});
			</script>';

		$out.= '<div id="dialog-switchdesign" class="hideobject" title="'.$langs->trans('SwitchToAnotherDesign').'">'."\n";
		$out.= '<br>'.$langs->trans('SelectADesign').': ';
		$out.= ajax_combobox('design');
		$out.= $this->select_designs($conf->global->ULTIMATE_DESIGN)."\n";
		$out.= '</div>'."\n";

		return $out;
	}
	
	/**
	 * formObjectOptions Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function formObjectOptions($parameters, &$object, &$action) {

		global $langs, $conf, $user, $hookmanager;
		
		$langs->load ( 'ultimatepdf@ultimatepdf' );
		
		dol_syslog ( get_class ( $this ) . ':: formObjectOptions', LOG_DEBUG );
		
		// Add javascript Jquery to add button Select doc form
		if ($object->table_element == 'propal' && ! empty ( $object->id ) && ! empty($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MERGED_PDF)) {
			
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			dol_include_once ( '/ultimatepdf/class/propalmergedpdf.class.php' );
			
			$filetomerge = new Propalmergedpdf ( $this->db );
			$result = $filetomerge->fetch_by_propal ( $object->id );			
			
			$form = new Form ( $db );
			
			if (! empty ( $conf->propal->enabled ))
				$upload_dir = $conf->propal->dir_output . '/' . dol_sanitizeFileName ( $object->ref );
			
			$filearray = dol_dir_list ( $upload_dir, "files", 0, '', '\.meta$', 'name', SORT_ASC, 1 );

			// For each file build select list with PDF extention
			if (count ( $filearray ) > 0) {
				$html = '<BR><BR>';
				// Actual file to merge is :
				if (count($filetomerge->lines)>0) {
					$html .= '<div class=\"fichecenter\">';
					$html .= '<div class=\"fichehalfleft\">';
					$html .= '<div class=\"titre\">';
					$html .= '<br>';
					$html .= $langs->trans ( 'PropalMergePdfPropalActualFile' );
					$html .= '</div>';
				}				
				
				$html .= '<form name=\"filemerge\" action=\"' . DOL_URL_ROOT . '/comm/propal.php?id=' . $object->id . '\" method=\"post\">';
				$html .= '<input type=\"hidden\" name=\"token\" value=\"' . $_SESSION ['newtoken'] . '\">';
				$html .= '<input type=\"hidden\" name=\"action\" value=\"filemerge\">';			
				
				
				if (count($filetomerge->lines)==0) {
					$html .= '<div class=\"fichecenter\">';
					$html .= '<div class=\"fichehalfleft\">';
					$html .= '<div class=\"titre\">';
					$html .= '<br>';
					$html .= $langs->trans ( 'PropalMergePdfPropalChooseFile' );
					$html .= '</div>';
				}
				
				$html .= '<table class=\"noborder\" width=\"100%\">';
				$html .= '<tbody>';			
				$html .= '<tr class=\"liste_titre\">';
				$html .= '<th>'. $langs->trans ( 'Documents' ) .'';
				$html .= '</th></tr>';
				$html .= '</tbody>';
				$style='impair';
				$hasfile=false;
				foreach ( $filearray as $filetoadd ) {

					if (($ext = pathinfo ( $filetoadd ['name'], PATHINFO_EXTENSION ) == 'pdf') && ($filename = pathinfo ( $filetoadd ['name'], PATHINFO_FILENAME )!=$object->ref)) {
				
						if ($style=='pair') {
							$style='impair';
						}
						else {
							$style='pair';
						}
						
						$checked = '';
						$filename=$filetoadd ['name'];
						
						if (array_key_exists($filetoadd ['name'],$filetomerge->lines)) {
							$checked =' checked=\"checked\" ';
						}
						
						$hasfile=true;
						$icon='<img border=\"0\" title=\"Fichier: '.$filename.'\" alt=\"Fichier: '.$filename.'\" src=\"'. DOL_URL_ROOT .'/theme/common/mime/pdf.png\">';
						$html .= '<tr class=\"'.$style.'\"><td class=\"nowrap\" style=\"font-weight:bold\">';
						
						$html .= '<input type=\"checkbox\" '.$checked.' name=\"filetoadd[]\" id=\"filetoadd\" value=\"'.$filetoadd ['name'].'\"> '.$icon.' '.$filename.'</input>';
						$html .= '</td></tr>';
					}								
				}
				
				if (!$hasfile) {
					$html .= '<tr><td>';
					$warning='<img border=\"0\" src=\"'. DOL_URL_ROOT .'/theme/eldy/img/warning.png\">';
					$html .= $warning.' '.$langs->trans ( 'GotoDocumentsTab' );
					$html .= '</td></tr>';
				}
				
				if ($hasfile) {
					$html .= '<tr><td>';			
					$html .= '<input type=\"submit\" class=\"button\" name=\"save\" value=\"' . $langs->trans ( 'Save' ) . '\">';
					$html .= '<br><br>';
					$html .= '</td></tr>';
				}
				
				$html .= '</table>';					
				$html .= '</form>';
				$html .= '</div>';				
				$html .= '</div>';
				
				print '<script type="text/javascript">jQuery(document).ready(function () {jQuery(function() {jQuery(".fiche").append("' . $html . '");});});</script>';
			}
		}
		elseif ($object->table_element == 'facture' && ! empty ( $object->id ) && ! empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_MERGED_PDF)) 
		{
			
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			dol_include_once ( '/ultimatepdf/class/invoicemergedpdf.class.php' );
			
			$filetomerge = new Invoicemergedpdf ( $this->db );
			$result = $filetomerge->fetch_by_invoice ( $object->id );			
			
			$form = new Form ( $db );
			
			if (! empty ( $conf->facture->enabled ))
				$upload_dir = $conf->facture->dir_output . '/' . dol_sanitizeFileName ( $object->ref );
			
			$filearray = dol_dir_list ( $upload_dir, "files", 0, '', '\.meta$', 'name', SORT_ASC, 1 );

			// For each file build select list with PDF extention
			if (count ( $filearray ) > 0) {
				$html = '<BR><BR>';
				// Actual file to merge is :
				if (count($filetomerge->lines)>0) {
					$html .= '<div class=\"fichecenter\">';
					$html .= '<div class=\"fichehalfleft\">';
					$html .= '<div class=\"titre\">';
					$html .= '<br>';
					$html .= $langs->trans ( 'InvoiceMergePdfInvoiceActualFile' );
					$html .= '</div>';
				}				
				
				$html .= '<form name=\"filemerge\" action=\"' . DOL_URL_ROOT . '/compta/facture.php?facid=' . $object->id . '\" method=\"post\">';
				$html .= '<input type=\"hidden\" name=\"token\" value=\"' . $_SESSION ['newtoken'] . '\">';
				$html .= '<input type=\"hidden\" name=\"action\" value=\"filemerge\">';			
				
				
				if (count($filetomerge->lines)==0) {
					$html .= '<div class=\"fichecenter\">';
					$html .= '<div class=\"fichehalfleft\">';
					$html .= '<div class=\"titre\">';
					$html .= '<br>';
					$html .= $langs->trans ( 'InvoiceMergePdfInvoiceChooseFile' );
					$html .= '</div>';
				}
				
				$html .= '<table class=\"noborder\" width=\"100%\">';
				$html .= '<tbody>';			
				$html .= '<tr class=\"liste_titre\">';
				$html .= '<th>'. $langs->trans ( 'Documents' ) .'';
				$html .= '</th></tr>';
				$html .= '</tbody>';
				$style='impair';
				$hasfile=false;
				foreach ( $filearray as $filetoadd ) {

					if (($ext = pathinfo ( $filetoadd ['name'], PATHINFO_EXTENSION ) == 'pdf') && ($filename = pathinfo ( $filetoadd ['name'], PATHINFO_FILENAME )!=$object->ref)) {
				
						if ($style=='pair') {
							$style='impair';
						}
						else {
							$style='pair';
						}
						
						$checked = '';
						$filename=$filetoadd ['name'];
						
						if (array_key_exists($filetoadd ['name'],$filetomerge->lines)) {
							$checked =' checked=\"checked\" ';
						}
						
						$hasfile=true;
						$icon='<img border=\"0\" title=\"Fichier: '.$filename.'\" alt=\"Fichier: '.$filename.'\" src=\"'. DOL_URL_ROOT .'/theme/common/mime/pdf.png\">';
						$html .= '<tr class=\"'.$style.'\"><td class=\"nowrap\" style=\"font-weight:bold\">';
						
						$html .= '<input type=\"checkbox\" '.$checked.' name=\"filetoadd[]\" id=\"filetoadd\" value=\"'.$filetoadd ['name'].'\"> '.$icon.' '.$filename.'</input>';
						$html .= '</td></tr>';
					}								
				}
				
				if (!$hasfile) {
					$html .= '<tr><td>';
					$warning='<img border=\"0\" src=\"'. DOL_URL_ROOT .'/theme/eldy/img/warning.png\">';
					$html .= $warning.' '.$langs->trans ( 'GotoDocumentsTab' );
					$html .= '</td></tr>';
				}
				
				if ($hasfile) {
					$html .= '<tr><td>';			
					$html .= '<input type=\"submit\" class=\"button\" name=\"save\" value=\"' . $langs->trans ( 'Save' ) . '\">';
					$html .= '<br><br>';
					$html .= '</td></tr>';
				}
				
				$html .= '</table>';					
				$html .= '</form>';
				$html .= '</div>';				
				$html .= '</div>';
				
				print '<script type="text/javascript">jQuery(document).ready(function () {jQuery(function() {jQuery(".fiche").append("' . $html . '");});});</script>';
			}
		}
		elseif ($object->table_element == 'commande' && ! empty ( $object->id ) && ! empty($conf->global->ULTIMATEPDF_GENERATE_ORDERS_WITH_MERGED_PDF)) 
		{
			
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			dol_include_once ( '/ultimatepdf/class/ordermergedpdf.class.php' );
			
			$filetomerge = new Ordermergedpdf ( $this->db );
			$result = $filetomerge->fetch_by_order ( $object->id );			
			
			$form = new Form ( $db );
			
			if (! empty ( $conf->commande->enabled ))
				$upload_dir = $conf->commande->dir_output . '/' . dol_sanitizeFileName ( $object->ref );
			
			$filearray = dol_dir_list ( $upload_dir, "files", 0, '', '\.meta$', 'name', SORT_ASC, 1 );

			// For each file build select list with PDF extention
			if (count ( $filearray ) > 0) {
				$html = '<BR><BR>';
				// Actual file to merge is :
				if (count($filetomerge->lines)>0) {
					$html .= '<div class=\"fichecenter\">';
					$html .= '<div class=\"fichehalfleft\">';
					$html .= '<div class=\"titre\">';
					$html .= '<br>';
					$html .= $langs->trans ( 'OrderMergePdfOrderActualFile' );
					$html .= '</div>';
				}				
				
				$html .= '<form name=\"filemerge\" action=\"' . DOL_URL_ROOT . '/commande/fiche.php?id=' . $object->id . '\" method=\"post\">';
				$html .= '<input type=\"hidden\" name=\"token\" value=\"' . $_SESSION ['newtoken'] . '\">';
				$html .= '<input type=\"hidden\" name=\"action\" value=\"filemerge\">';			
				
				
				if (count($filetomerge->lines)==0) {
					$html .= '<div class=\"fichecenter\">';
					$html .= '<div class=\"fichehalfleft\">';
					$html .= '<div class=\"titre\">';
					$html .= '<br>';
					$html .= $langs->trans ( 'OrderMergePdfOrderChooseFile' );
					$html .= '</div>';
				}
				
				$html .= '<table class=\"noborder\" width=\"100%\">';
				$html .= '<tbody>';			
				$html .= '<tr class=\"liste_titre\">';
				$html .= '<th>'. $langs->trans ( 'Documents' ) .'';
				$html .= '</th></tr>';
				$html .= '</tbody>';
				$style='impair';
				$hasfile=false;
				foreach ( $filearray as $filetoadd ) {

					if (($ext = pathinfo ( $filetoadd ['name'], PATHINFO_EXTENSION ) == 'pdf') && ($filename = pathinfo ( $filetoadd ['name'], PATHINFO_FILENAME )!=$object->ref)) {
				
						if ($style=='pair') {
							$style='impair';
						}
						else {
							$style='pair';
						}
						
						$checked = '';
						$filename=$filetoadd ['name'];
						
						if (array_key_exists($filetoadd ['name'],$filetomerge->lines)) {
							$checked =' checked=\"checked\" ';
						}
						
						$hasfile=true;
						$icon='<img border=\"0\" title=\"Fichier: '.$filename.'\" alt=\"Fichier: '.$filename.'\" src=\"'. DOL_URL_ROOT .'/theme/common/mime/pdf.png\">';
						$html .= '<tr class=\"'.$style.'\"><td class=\"nowrap\" style=\"font-weight:bold\">';
						
						$html .= '<input type=\"checkbox\" '.$checked.' name=\"filetoadd[]\" id=\"filetoadd\" value=\"'.$filetoadd ['name'].'\"> '.$icon.' '.$filename.'</input>';
						$html .= '</td></tr>';
					}								
				}
				
				if (!$hasfile) {
					$html .= '<tr><td>';
					$warning='<img border=\"0\" src=\"'. DOL_URL_ROOT .'/theme/eldy/img/warning.png\">';
					$html .= $warning.' '.$langs->trans ( 'GotoDocumentsTab' );
					$html .= '</td></tr>';
				}
				
				if ($hasfile) {
					$html .= '<tr><td>';			
					$html .= '<input type=\"submit\" class=\"button\" name=\"save\" value=\"' . $langs->trans ( 'Save' ) . '\">';
					$html .= '<br><br>';
					$html .= '</td></tr>';
				}
				
				$html .= '</table>';					
				$html .= '</form>';
				$html .= '</div>';				
				$html .= '</div>';
				
				print '<script type="text/javascript">jQuery(document).ready(function () {jQuery(function() {jQuery(".fiche").append("' . $html . '");});});</script>';
			}
		}
	}
	
	 /**
     * Complete doc forms
     *
     * @param	array	$parameters		Array of parameters
     * @param	object	&$object		Object
     * @return	string					HTML content to add by hook
     */
    function formBuilddocOptions($parameters,&$object)
    {
        global $langs, $user, $conf;
		global $form;

        $langs->load("ultimatepdf@ultimatepdf");
        $form=new Form($this->db);

        $out='';

        $morefiles=array();

        if (($parameters['modulepart'] == 'invoice' || $parameters['modulepart'] == 'facture') && ($object->mode_reglement_code == 'VIR' || empty($object->mode_reglement_code)))
        {
       		$selectedbank=empty($object->fk_bank)?(isset($_POST['fk_bank'])?$_POST['fk_bank']:$conf->global->FACTURE_RIB_NUMBER):$object->fk_bank;

       		$statut='0';$filtre='';
       		$listofbankaccounts=array();
       		$sql = "SELECT rowid, label, bank";
       		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
       		$sql.= " WHERE clos = '".$statut."'";
       		$sql.= " AND entity = ".$conf->entity;
       		if ($filtre) $sql.=" AND ".$filtre;
       		$sql.= " ORDER BY label";
       		dol_syslog(get_class($this)."::formBuilddocOptions sql=".$sql);
       		$result = $this->db->query($sql);
       		if ($result)
       		{
       			$num = $this->db->num_rows($result);
       			$i = 0;
       			if ($num)
       			{
       				while ($i < $num)
       				{
       					$obj = $this->db->fetch_object($result);
       					$listofbankaccounts[$obj->rowid]=$obj->label;
       					$i++;
       				}
       			}
       		}
			else dol_print_error($this->db);

        	$out.='<tr class="liste_titre">';
        	$out.='<td align="left" colspan="4" valign="top" class="formdoc">';
        	$out.=$langs->trans("BankAccount").' (pdf)';
       		$out.= $form->selectarray('fk_bank',$listofbankaccounts,$selectedbank,(count($listofbankaccounts)>1?1:0));
        }
        $out.='</td></tr>';

        return $out;
    }
	
	/**
	 * Return action of hook
	 *
	 * @param array $parameters
	 * @param object $object
	 * @param string $action
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function afterPDFCreation($parameters = false, &$object, &$action = '', $hookmanager) {
		
	}
	
	/**
	 * Download other logo
	 *
	 */
	/*function getOtherLogo() 
	{
		global $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		if ($_FILES["userfile"]["tmp_name"])
		{
			if (preg_match('/([^\\/:]+)$/i',$_FILES["userfile"]["name"],$reg))
			{
				$original_file=$reg[1];

				$isimage=image_format_supported($original_file);
				if ($isimage >= 0)
				{
				
					dol_syslog("Move file ".$_FILES["userfile"]["tmp_name"]." to ".$conf->ultimatepdf->dir_output.'/otherlogo/'.$original_file);
					if (! is_dir($conf->ultimatepdf->dir_output.'/otherlogo/'))
					{
						dol_mkdir($conf->ultimatepdf->dir_output.'/otherlogo/');
					}
					$result=dol_move_uploaded_file($_FILES["userfile"]["tmp_name"],$conf->ultimatepdf->dir_output.'/otherlogo/'.$original_file,1,0,$_FILES['otherlogo']['error']);
					

					if ($result > 0)
					{
						dolibarr_set_const($this->db, "ULTIMATE_OTHERLOGO",$original_file,'chaine',0,'',$conf->entity);
					}
					else if (preg_match('/^ErrorFileIsInfectedWithAVirus/',$result))
					{
						$error++;
						$langs->load("errors");
						$tmparray=explode(':',$result);
						setEventMessage($langs->trans('ErrorFileIsInfectedWithAVirus',$tmparray[1]),'errors');
					}
					else
					{
						$error++;
						setEventMessage($langs->trans("ErrorFailedToSaveFile"),'errors');
					}
					
				}
				else
				{
					$error++;
					setEventMessage($langs->trans("ErrorOnlyPngJpgSupported"),'errors');
				}
			}
		}		
	}*/

}
