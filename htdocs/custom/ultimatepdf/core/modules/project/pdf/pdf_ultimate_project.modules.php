<?php
/* Copyright (C) 2010      Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Philippe Grand <philippe.grand@atoo-net.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/custom/ultimatepdf/core/modules/project/pdf/pdf_ultimate_project.modules.php
 *	\ingroup    project
 *	\brief      Fichier de la classe permettant de generer les projets au modele ultimate_project
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");


/**
 * Classe permettant de generer les projets au modele ultimate_project
 */

class pdf_ultimate_project extends ModelePDFProjects
{
	var $db;
    var $name;
    var $description;
    var $type;

    var $phpmin = array(5,2,0); // Minimum version of PHP required by module
    var $version = 'dolibarr';

    var $page_largeur;
    var $page_hauteur;
    var $format;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;
	var $style;

	var $emetteur;	// Objet societe qui emet

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$DB      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("projects");
		$langs->load("companies");
		$langs->load("ultimatepdf@ultimatepdf");

		$this->db = $db;
		$this->name = "ultimate project";
		$this->description = $langs->trans("DocumentModelBaleine");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->ULTIMATE_PDF_MARGIN_LEFT)?$conf->global->ULTIMATE_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->ULTIMATE_PDF_MARGIN_RIGHT)?$conf->global->ULTIMATE_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->ULTIMATE_PDF_MARGIN_TOP)?$conf->global->ULTIMATE_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->ULTIMATE_PDF_MARGIN_BOTTOM)?$conf->global->ULTIMATE_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_codeproduitservice = 1;      // Affiche code produit-service

		if(!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR))
		{
			$bordercolor = html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR);
			$dashdotted = $conf->global->ULTIMATE_DASH_DOTTED;
			$this->style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted , 'color' => $bordercolor);
		}

		// Recupere emetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini

		// Defini position des colonnes
		$this->posxref=$this->marge_gauche+1;
		$this->posxlabel=$this->marge_gauche+25;
		$this->posxprogress=$this->marge_gauche+120;
		$this->posxdatestart=$this->marge_gauche+145;
		$this->posxdateend=$this->marge_gauche+170;
	}


	/**
	 *      Fonction generant le projet sur le disque
	 *		@param	    object   		Object project a generer
	 *		@param		outputlangs		Lang output object
	 *		@return	    int         	1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs)
	{
		global $user,$langs,$conf,$hookmanager;

		if (! is_object($outputlangs)) $outputlangs=$langs;

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("projects");

		if ($conf->projet->dir_output)
		{
			$nblignes = count($object->lines);

			$objectref = dol_sanitizeFileName($object->ref);
			$dir = $conf->projet->dir_output;
			if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
			$file = $dir . "/" . $objectref . ".pdf";

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

				$pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
                $heightforinfotot = 50;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
                $pdf->SetAutoPageBreak(1,0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
                if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				// Complete object by loading several other informations
				$task = new Task($this->db);
				$tasksarray = $task->getTasksArray(0,0,$object->id);

				$object->lines=$tasksarray;
				$nblignes=count($object->lines);

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				//Generation de l entete du fichier
				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Project"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Project"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs, $hookmanager);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColorArray($textcolor);

				//Initialisation des coordonnees
				$tab_top = 90;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				// Affiche notes
				if (! empty($object->note_public))
				{
					$tab_top = 94;

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					$pdf->writeHTMLCell(190, 3, $this->posxref-1, $tab_top-2, dol_htmlentitiesbr($object->note_public), 0, 1);
					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme et 4eme param
					$pdf->SetDrawColor(192,192,192);					
					$pdf->RoundedRect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1, 2, $round_corner = '1111', 'S', $this->style, $fill_color=array(''));

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY+6;
				}
				else
				{
					$height_note=0;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				// Boucle sur les lignes
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 2);   // Dans boucle pour gerer multi-page
					$pdf->SetTextColorArray($textcolor);	
					
					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

                    $showpricebeforepagebreak=1;

					// Description of ligne
					$ref=$object->lines[$i]->ref;
					$libelleline=$object->lines[$i]->label;
					$progress=$object->lines[$i]->progress.'%';
					$datestart=dol_print_date($object->lines[$i]->date_start,'day');
					$dateend=dol_print_date($object->lines[$i]->date_end,'day');


					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					$pdf->startTransaction();
					$pdf->SetXY($this->posxref, $curY);
					$pdf->MultiCell($this->posxlabel-$this->marge_gauche, 3, $outputlangs->convToOutputCharset($ref), 0, 'L');
					$pdf->SetXY($this->posxlabel, $curY);
					$pdf->MultiCell($this->posxprogress-$this->posxlabel, 3, $outputlangs->convToOutputCharset($libelleline), 0, 'L');
					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						$pdf->SetXY($this->posxref, $curY);
						$pdf->MultiCell($this->posxlabel-$this->marge_gauche, 3, $outputlangs->convToOutputCharset($ref), 0, 'L');
						$pdf->SetXY($this->posxlabel, $curY);
						$pdf->MultiCell($this->posxprogress-$this->posxlabel, 3, $outputlangs->convToOutputCharset($libelleline), 0, 'L');
						
						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
								$pdf->setPage($pagenb+1);
							}
						}
						else
						{
							// We found a page break
							$showpricebeforepagebreak=0;
						}
					}
					else	// No pagebreak
					{
						$pdf->commitTransaction();
					}
					
					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('','',  $default_font_size - 2);   // On repositionne la police par defaut
					
					$pdf->SetXY($this->posxprogress, $curY);
					$pdf->MultiCell($this->posxdatestart-$this->posxprogress, 3, $progress, 0, 'L');
					$pdf->SetXY($this->posxdatestart, $curY);
					$pdf->MultiCell($this->posxdateend-$this->posxdatestart, 3, $datestart, 0, 'L');
					$pdf->SetXY($this->posxdateend, $curY);
					$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->posxdateend, 3, $dateend, 0, 'L');
					
					$pageposafter=$pdf->getPage();

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut
					$nexY = $pdf->GetY();

					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1))
					{
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(210,210,210)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY+=2;    // Passe espace entre les lignes

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
					}
					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');

				// Actions on extra fields (by external module or standard code)
				if (! is_object($hookmanager))
				{
					include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","PROJECT_OUTPUTDIR");
				return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}


	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			&$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf,$mysoc;
		
		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

        $default_font_size = pdf_getPDFFontSize($outputlangs);
		$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFillColorArray($bgcolor);
		$pdf->SetFont('','B', $default_font_size - 1);

		// Rect prend une longueur en 3eme param
		$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, 2, $round_corner = '1111','S', $this->style, array(200, 210, 234) );

		// line prend une position y en 3eme param
		$pdf->line($this->marge_gauche, $tab_top+6, $this->page_largeur-$this->marge_droite, $tab_top+6);
		
		if (empty($hidetop))
		{		
			$pdf->SetXY($this->posxref-1, $tab_top);
			$pdf->MultiCell($this->posxlabel-$this->posxref+1,6, $outputlangs->transnoentities("RefTask"),'','L',1);
		}
		
		$pdf->line($this->posxlabel-1, $tab_top, $this->posxlabel-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY ($this->posxlabel-1, $tab_top);
			$pdf->MultiCell($this->posxprogress-$this->posxlabel+1,6, $outputlangs->transnoentities("LabelTask"),0,'C',1);
		}	
		
		$pdf->line($this->posxprogress-1, $tab_top, $this->posxprogress-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY ($this->posxprogress-1, $tab_top);
			$pdf->MultiCell($this->posxdatestart-$this->posxprogress+1,6, $outputlangs->transnoentities("Progress"),0,'C',1);
		}

		$pdf->line($this->posxdatestart-1, $tab_top, $this->posxdatestart-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY ($this->posxdatestart-1, $tab_top);
			$pdf->MultiCell($this->posxdateend-$this->posxdatestart+1,6, $outputlangs->transnoentities("DateStart"),0,'C',1);
		}	
		
		$pdf->line($this->posxdateend-1, $tab_top, $this->posxdateend-1, $tab_top + $tab_height);
		if (empty($hidetop))
        {
        	$pdf->SetXY ($this->posxdateend-1, $tab_top);
        	$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->posxdateend+1,6, $outputlangs->transnoentities("DateEnd"), 0, 'R', 1);
        }		
	}

	/**
	 *   	Show header of page
	 *
	 *   	@param      $pdf     		Object PDF
	 *   	@param      $object     	Object project
	 *      @param      $showaddress    0=no, 1=yes
	 *      @param      $outputlangs	Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $langs,$conf,$mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->SENDING_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->SENDING_DRAFT_WATERMARK);
		}

		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('','B', $default_font_size + 3);

        $posx=$this->page_largeur-$this->marge_droite-100;
		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Other Logo 
		if ($conf->global->ULTIMATE_OTHERLOGO)
		{
			$logo=$conf->global->ULTIMATE_OTHERLOGO;
			$f=fopen($logo,'r'); 
			if ($f)
			{
				$height=pdf_getHeightForLogo($logo,true);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
				fclose($f);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else
		{		
			$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
			if ($this->emetteur->logo)
			{
				if (is_readable($logo))
				{
					$height=pdf_getHeightForLogo($logo);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
				}
				else
				{
					$pdf->SetTextColor(200,0,0);
					$pdf->SetFont('','B', $default_font_size - 2);
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
				}
			}
			else
			{
				$text=$this->emetteur->name;
				$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($text), 0, 'L');
			}
		}

		//Nom du Document
		$pdf->SetXY($this->marge_gauche, $this->marge_haute);
		$pdf->SetFont('','B', $default_font_size + 3);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell(0, 3, $outputlangs->transnoentities("Project")." ".$outputlangs->convToOutputCharset($object->ref), '', 'R');
		
		$pdf->SetFont('','', $default_font_size + 2);

		if ($showaddress)
		{
			// Sender properties
			$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
			$carac_emetteur = pdf_build_address($outputlangs,$this->emetteur);

			// Show sender
			$posy=36;
			$posx=$this->marge_gauche;
			$hautcadre=40;

			// Show sender frame
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->RoundedRect($posx, $posy, 84, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor);

			// Show sender name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');

			// Show sender information
			$pdf->SetXY($posx+2,$posy+8);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(84, 4, $carac_emetteur, 0, 'L');

			// If SHIPPING contact defined on invoice, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external','SERVICE');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

			// Recipient name
			if (! empty($usecontact))
			{
				// On peut utiliser le nom de la societe du contact
				if (! empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) $socname = $object->contact->socname;
				else $socname = $object->client->nom;
				$carac_client_name=$outputlangs->convToOutputCharset($socname);
			}
			else
			{
				$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);
			}

			$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,$object->contact,$usecontact,'target');

			// Show recipient
			$widthrecbox=100;					
			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
			$posy=36;
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;	
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			
			// Show recipient frame
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('','', $default_font_size - 2);

			// Show shipping address
			$pdf->SetXY($posx+2,$posy-4);
		
			$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor);
				
			// Show recipient name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy+4+(dol_nboflines_bis($carac_client_name,50)*4));
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	void
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		return pdf_pagefoot($pdf,$outputlangs,'PROJECT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,0,$hidefreetext);
	}

}

?>