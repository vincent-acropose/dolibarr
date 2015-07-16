<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Philippe Grand       <philippe.grand@atoo-net.com>
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
 *	\file       ultimatepdf/core/modules/expedition/doc/pdf_ultimate_shipment.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier de la classe permettant de generer les bordereaux envoi au modele ultimate_shipment
 */

require_once(DOL_DOCUMENT_ROOT."/core/modules/expedition/modules_expedition.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");


/**
 *	\class      pdf_expedition_ultimate_shipment
 *	\brief      Classe permettant de generer les borderaux envoi au modele ultimate_shipment
 */
Class pdf_ultimate_shipment extends ModelePdfExpedition
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
	var $logo_height;

	var $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db=0)
	{
		global $conf,$langs,$mysoc;

		$langs->load("ultimatepdf@ultimatepdf");

		$this->db = $db;
		$this->name = "ultimate_shipment";
		$this->description = $langs->trans("DocumentDesignUltimate_shipment");

		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->ULTIMATE_PDF_MARGIN_LEFT)?$conf->global->ULTIMATE_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->ULTIMATE_PDF_MARGIN_RIGHT)?$conf->global->ULTIMATE_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->ULTIMATE_PDF_MARGIN_TOP)?$conf->global->ULTIMATE_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->ULTIMATE_PDF_MARGIN_BOTTOM)?$conf->global->ULTIMATE_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_multilang = 1;			   // Dispo en plusieurs langues
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		if(!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR))
		{
			$bordercolor = html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR);
			$dashdotted = $conf->global->ULTIMATE_DASH_DOTTED;
			$this->style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted , 'color' => $bordercolor);
		}

		// Recupere emetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default if not defined

		// Defini position des colonnes
		if(!empty($conf->global->ULTIMATE_DOCUMENTS_WITH_LSLR))
		{
			$this->posxls=$this->marge_gauche+1;
			$this->posxlr=$this->posxls + 10;
			if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
			{
				$this->posxdesc=$this->marge_gauche+$this->posxlr;
			}
			else
			{			
				$this->posxref=$this->marge_gauche+$this->posxlr;
				$this->posxdesc=isset($conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH)?$conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH:32;	
			}
		}
		else
		{
			if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
			{
				$this->posxdesc=$this->marge_gauche+1;
			}
			else
			{			
				$this->posxref=$this->marge_gauche+1;
				$this->posxdesc=isset($conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH)?$conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH:32;	
			}
		}
		
		$this->posxqtyordered=$this->page_largeur - $this->marge_droite - 60;
		$this->posxqtytoship=$this->page_largeur - $this->marge_droite - 30;
	}


	/**
	 *	Function to build pdf onto disk
	 *
	 *	@param		Object		&$object			Object expedition to generate (or id if old method)
	 *	@param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int         	    			1=OK, 0=KO
	 */
	function write_file(&$object, $outputlangs, $srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$conf,$langs,$mysoc,$hookmanager;
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		$object->fetch_thirdparty();

		if (! is_object($outputlangs)) $outputlangs=$langs;

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("propal");
		$outputlangs->load("sendings");
		$outputlangs->load("deliveries");
		$outputlangs->load("ultimatepdf@ultimatepdf");
		
		$nblignes = count($object->lines);

		//Verification de la configuration
		if ($conf->expedition->dir_output)
		{
			$object->fetch_thirdparty();

			$origin = $object->origin;

			//Creation de l expediteur
			$this->expediteur = $mysoc;

			//Creation du destinataire
			$idcontact = $object->$origin->getIdContact('external','SHIPPING');
            $this->destinataire = new Contact($this->db);
			if ($idcontact[0]) $this->destinataire->fetch($idcontact[0]);

			//Creation du livreur
			$idcontact = $object->$origin->getIdContact('internal','LIVREUR');
			$this->livreur = new User($this->db);
			if ($idcontact[0]) $this->livreur->fetch($idcontact[0]);


			// Definition de $dir et $file
			if ($object->specimen)
			{
				$dir = $conf->expedition->dir_output."/sending";
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$expref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expedition->dir_output . "/sending/" . $expref;
				$file = $dir . "/" . $expref . ".pdf";
			}

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

				// Create pdf instance
                $pdf=pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);  // Must be after pdf_getInstance
				$heightforinfotot = 20;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 15;	// Height reserved to output the footer (value include bottom margin)
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

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				//Generation de l entete du fichier
				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Shipment"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Shipment"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColorArray($textcolor);
				
				//catch logo height
				$logo_height=pdf_getUltimateHeightForLogo($logo);

				$tab_top = $this->marge_haute+$logo_height + 60;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?$this->marge_haute+$logo_height+5:10);
				$tab_height = 130;
				$tab_height_newpage = 150;
				
				// Affiche notes
				$notetoshow=empty($object->note_public)?'':$object->note_public;
				if (! empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE))
				{
					// Get first sale rep
					if (is_object($object->thirdparty))
					{
						$salereparray=$object->thirdparty->getSalesRepresentatives($user);
						$salerepobj=new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (! empty($salerepobj->signature)) $notetoshow=dol_concatdesc($notetoshow, $salerepobj->signature);
					}
				}
				if ($notetoshow)
				{
					$tab_top = $this->marge_haute+$logo_height + 60;					
					
					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					
					if(!empty($conf->global->ULTIMATE_DOCUMENTS_WITH_LSLR))
					{
						$pdf->writeHTMLCell(190, 3, $this->posxls-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);
					}
					else
					{
						if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
						{
							$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);
						}
						else
						{			
							$pdf->writeHTMLCell(190, 3, $this->posxref-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);	
						}
					}					

					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192,192,192);
					$pdf->RoundedRect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1, 2, $round_corner = '1111', 'S', $this->style, $fill_color=array());

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

				// Loop on each lines
				for ($i = 0; $i < $nblignes; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 2);   // Into loop to work with multipage
					$pdf->SetTextColorArray($textcolor);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();
					$posYAfterDescription=0;
					$showpricebeforepagebreak=1;

					// Description of product line
					$curX = $this->posxdesc;
					
					$pdf->startTransaction();
					
					if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
					{
						pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxqtyordered-$curX,3,$curX,$curY,$hideref,$hidedesc);
					}
					else
					{
						$pdf->SetFont('','', $default_font_size - 3);
						if (!empty($object->lines[$i]->fk_product)) pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,22,3,$this->posxref,$curY,$hideref,$hidedesc,0,'ref');
						$pdf->SetFont('','', $default_font_size - 2);
						pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,$this->posxqtyordered-$curX,3,$curX,$curY,$hideref,$hidedesc,0,'label');
					}

					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
						{
							pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxqtyordered-$curX,3,$curX,$curY,$hideref,$hidedesc);
						}
						else
						{
							$pdf->SetFont('','', $default_font_size - 3);
							if (!empty($object->lines[$i]->fk_product)) pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,22,3,$this->posxref,$curY,$hideref,$hidedesc,0,'ref');
							$pdf->SetFont('','', $default_font_size - 2);
							pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,$this->posxqtyordered-$curX,3,$curX,$curY,$hideref,$hidedesc,0,'label');
						}
						
						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
								$pdf->setPage($pageposbefore+1);
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
					$posYAfterDescription=$pdf->GetY();
					
					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)){
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

					//Creation des cases a cocher
					if(!empty($conf->global->ULTIMATE_DOCUMENTS_WITH_LSLR))
					{
						$pdf->rect(10+3, $curY+1, 3, 3);
						$pdf->rect(20+3, $curY+1, 3, 3);
					}

					$pdf->SetXY($this->posxqtyordered, $curY);
					$pdf->MultiCell(($this->posxqtytoship - $this->posxqtyordered), 3, $object->lines[$i]->qty_asked,'','C');

					$pdf->SetXY($this->posxqtytoship, $curY);
					$pdf->MultiCell(($this->page_largeur - $this->marge_droite - $this->posxqtytoship), 3, $object->lines[$i]->qty_shipped,'','C');

					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1))
					{
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(210,210,210)));
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
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
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
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext - $heightforinfotot, 0, $outputlangs, 0, 0);
					$bottomlasttab=$this->page_hauteur - $heightforfooter - $heightforfreetext - $heightforinfotot + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter - $heightforfreetext - $heightforinfotot, 0, $outputlangs, 1, 0);
					$bottomlasttab=$this->page_hauteur - $heightforfooter - $heightforfreetext - $heightforinfotot + 1;
				}
				
				// Affiche zone infos
				$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				//Insertion du pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');
				
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

                if (! empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
			else
			{
				$this->error=$outputlangs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$outputlangs->transnoentities("ErrorConstantNotDefined","EXP_OUTPUTDIR");
			return 0;
		}
		$this->error=$outputlangs->transnoentities("ErrorUnknown");
		return 0;   // Erreur par defaut
	}
	
	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		PDF			&$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
	    global $conf;

	    $default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('','', $default_font_size - 2);
		$heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
		$heightforfooter = $this->marge_basse + 15;	// Height reserved to output the footer (value include bottom margin)
		$heightforinfotot = 20;	// Height reserved to output the info and total part
		$deltay=$this->page_hauteur-$heightforfreetext-$heightforfooter-$heightforinfotot/2;
		$cury=max($cury,$deltay);
		$deltax=$this->marge_gauche;
		$pdf->SetXY($deltax, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("GoodStatusDeclaration") , 0, 'L');
		$pdf->SetXY($deltax, $cury+12);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ToAndDate") , 0, 'C');
		$pdf->SetXY($deltax+$this->page_largeur/2, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("NameAndSignature") , 0, 'C');

	    return $posy;
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
		global $conf,$langs;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		$langs->load("main");
		$langs->load("bills");

		$pdf->SetFillColorArray($bgcolor);
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('','B', $default_font_size - 2);

		$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, 2, $round_corner = '0110','S', $this->style, '',$hidetop, $hidebottom);
		
		if(!empty($conf->global->ULTIMATE_DOCUMENTS_WITH_LSLR))
		{
			if (empty($hidetop))
			{
				$pdf->SetXY ($this->posxls-1, $tab_top);
				$pdf->MultiCell($this->posxlr - $this->posxls,5, $outputlangs->transnoentities("LS"),0,'C',1);
			}
			
			$pdf->line(20, $tab_top, 20, $tab_top + $tab_height);
			if (empty($hidetop))
			{		
				if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
				{
					$pdf->SetXY ($this->posxlr-1, $tab_top);
					$pdf->MultiCell($this->posxdesc - $this->posxlr+2,5, $outputlangs->transnoentities("LR"),0,'C',1);
				}
				else
				{			
					$pdf->SetXY ($this->posxlr-1, $tab_top);
					$pdf->MultiCell($this->posxref - $this->posxlr+2,5, $outputlangs->transnoentities("LR"),0,'C',1);	
				}
			}
		
			if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
			{
				$pdf->line($this->posxdesc, $tab_top, $this->posxdesc, $tab_top + $tab_height);// line prend une position y en 3eme et 4eme param
			}
			else
			{			
				$pdf->line($this->posxref, $tab_top, $this->posxref, $tab_top + $tab_height);// line prend une position y en 3eme et 4eme param
			}
		}
				
		if (empty($hidetop))
		{
			if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes")
			{
				$pdf->SetXY($this->posxref-1, $tab_top);
				$pdf->MultiCell(($this->posxdesc - $this->posxref+2), 5, $outputlangs->transnoentities("Ref"),'','C',1);
			}
		}
		
		if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes")
		{
			$pdf->line($this->posxdesc, $tab_top, $this->posxdesc, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxdesc, $tab_top);
				$pdf->MultiCell(($this->posxqtyordered - $this->posxdesc), 5, $outputlangs->transnoentities("Description"),'','C',1);
			}
		}
		else
		{			
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxdesc-1, $tab_top);
				$pdf->MultiCell(($this->posxqtyordered - $this->posxdesc), 5, $outputlangs->transnoentities("Description"),'','C',1);
			}
		}

		$pdf->line($this->posxqtyordered-1, $tab_top, $this->posxqtyordered-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqtyordered-1, $tab_top);
			$pdf->MultiCell(($this->posxqtytoship - $this->posxqtyordered), 5, $outputlangs->transnoentities("QtyOrdered"),'','C',1);
		}

		$pdf->line($this->posxqtytoship-1, $tab_top, $this->posxqtytoship-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqtytoship-1, $tab_top);
			$pdf->MultiCell(($this->page_largeur - $this->marge_droite - $this->posxqtytoship+1), 5, $outputlangs->transnoentities("QtyToShip"),'','C',1);
		}
	}


	/**
	 *   	Show header of page
	 *
	 *      @param      pdf             Object PDF
	 *      @param      object          Object invoice
	 *      @param      showaddress     0=no, 1=yes
	 *      @param      outputlang		Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $langs,$mysoc;
		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);
		$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
		$qrcodecolor =  html2rgb($conf->global->ULTIMATE_QRCODECOLOR_COLOR);
		$main_page = $this->page_largeur-$this->marge_gauche-$this->marge_droite;
		
		$langs->load("orders");
		
		//affiche repere de pliage	
		if (! empty($conf->global->MAIN_DISPLAY_FOLD_MARK))
		{
			$pdf->Line(0,($this->page_hauteur)/3,3,($this->page_hauteur)/3);
		}
	
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->SENDING_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->SENDING_DRAFT_WATERMARK);
		}

		//Prepare la suite
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('','B', $default_font_size + 3);

        $posy=$this->marge_haute;
        $posx=$this->page_largeur-$this->marge_droite-100;

		$pdf->SetXY($this->marge_gauche,$posy);
		
		// Other Logo 
		if ($conf->global->ULTIMATE_OTHERLOGO)
		{
			$logo=$conf->global->ULTIMATE_OTHERLOGO;
			$f=fopen($logo,'r'); 
			if ($f)
			{
				$logo_height=pdf_getUltimateHeightForLogo($logo,true);
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $logo_height);	// width=0 (auto)
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
			// Logo			
			$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
			if ($this->emetteur->logo)
			{
				if (is_readable($logo))
				{
					$logo_height=pdf_getUltimateHeightForLogo($logo);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $logo_height);	// width=0 (auto)
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
		
		$posy-=2;
		//Display Thirdparty barcode at top			
		$barcode=$object->client->barcode;
		$object->client->fetch_barcode();
		
		$styleBc = array(
			'position' => '',
			'align' => 'C',
			'stretch' => false,
			'fitwidth' => true,
			'cellfitalign' => '',
			'border' => true,
			'hpadding' => 'auto',
			'vpadding' => 'auto',
			'fgcolor' => array(0,0,0),
			'bgcolor' => false, //array(255,255,255),
			'text' => true,
			'font' => 'helvetica',
			'fontsize' => 8,
			'stretchtext' => 4
			);
		
		// barcode_type_code
		if (! empty($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE))
		{
			$pdf->SetFont('','', $default_font_size - 2);
			//$pdf->Cell(0, 0, $object->client->barcode_type_code, 0, 1);
			$pdf->write1DBarcode($barcode, $object->client->barcode_type_code, $posx, $posy, '', 16, 0.4, $styleBc, 'N');
		}
		
		// set style for QRcode
		$styleQr = array(
		'border' => false,
		'vpadding' => 'auto',
		'hpadding' => 'auto',
		'fgcolor' => $qrcodecolor,
		'bgcolor' => false, //array(255,255,255)
		'module_width' => 1, // width of a single module in points
		'module_height' => 1 // height of a single module in points
		);
		// QRcode
		if (! empty($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE))
		{
			$code = pdf_codeOrderLink();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posx, $posy, 30, 30, $styleQr, 'N');
		}
		// My Company QR-code
		if (! empty($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE))
		{
			$code = pdf_mycompCodeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posx, $posy, 30, 30, $styleQr, 'N');
		}
		
		//Nom du Document
		$pdf->SetFont('','B', $default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColorArray($textcolor);
		$title=$outputlangs->transnoentities("SendingSheet");
		$pdf->MultiCell(100, 4, $title, '' , 'R');
		
		$posy+=4;
		
		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);
		
		/**********************************/
		/* Emplacement Informations Expediteur (My Company)*/
		/**********************************/
		
		$origin 	= $object->origin;
		$origin_id 	= $object->origin_id;
		
		if ($showaddress)
		{
			// Customer and Sender properties
			$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
			
			// Sender properties
			$carac_emetteur='';
		 	// Add internal contact of origin element if defined
			$arrayidcontact=array();
			if (! empty($origin) && is_object($object->$origin)) $arrayidcontact=$object->$origin->getIdContact('internal','SALESREPFOLL');
		 	if (count($arrayidcontact) > 0)
		 	{
		 		$object->fetch_user($arrayidcontact[0]);
		 		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
		 	}

		 	$carac_emetteur .= pdf_build_address($outputlangs,$this->emetteur);

			// Show sender
			$posy=$logo_height+1.2*$this->marge_haute;
			$posx=$this->marge_gauche;
			$hautcadre=40;
			$widthrecbox=$this->page_largeur/2-1.2*$this->marge_gauche;
			if (! empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx=$this->page_largeur-$this->marge_droite-$widthrecbox;       
			
			// Show sender frame
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->SetFont('','', $default_font_size - 1);
			$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor);

			// Show sender name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFont('','B',$default_font_size);
			$pdf->MultiCell($widthrecbox-5, 3, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy);
			$pdf->MultiCell($widthrecbox-5, 4, $carac_emetteur, 0, 'L');


			// If SHIPPING contact defined, we use it
			$usecontact=false;
			$arrayidcontact=$object->$origin->getIdContact('external','SHIPPING');
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

			$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,((!empty($object->contact))?$object->contact:null),$usecontact,'targetwithdetails');

			// Show recipient
			$posy=$logo_height+1.2*$this->marge_haute;
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
			if (! empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('','', $default_font_size - 2);

			$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor);
			$pdf->SetXY($posx+2,$posy);

			// Show recipient name
			$pdf->SetXY($posx+2,$posy+1);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox-5, 4, $carac_client_name, 0, 'L');

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy+6);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
			
			// Other informations
			
			$pdf->SetFillColor(255,255,255);

			// Date Expedition
			$width=$main_page/5 -1.5;
			$RoundedRectHeight = $logo_height+1.3*$this->marge_haute+$hautcadre;
			$pdf->RoundedRect($this->marge_gauche, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 2);
	        $pdf->SetXY($this->marge_gauche,$RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("Date"), 0, 'C', false);

			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche,$RoundedRectHeight+6);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 6, dol_print_date($object->date_creation,"daytext",false,$outputlangs,true), '0', 'C');

			// Add list of linked elements
			// TODO possibility to use with other elements (business module,...)
			//$object->load_object_linked();
			
			$origin 	= $object->origin;
			$origin_id 	= $object->origin_id;

			// TODO move to external function
			if (! empty($conf->$origin->enabled))
			{
				$outputlangs->load('orders');

				$classname = ucfirst($origin);
				$linkedobject = new $classname($this->db);
				$result=$linkedobject->fetch($origin_id);
				if ($result >= 0)
				{
					$pdf->RoundedRect($this->marge_gauche+$width+2, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
					$pdf->SetFont('','B', $default_font_size - 2);
					$pdf->SetXY($this->marge_gauche+$width+2,$RoundedRectHeight);
					$pdf->SetTextColorArray($textcolor);
					$pdf->MultiCell($width, 5, $outputlangs->transnoentities("RefCustomer"), 0, 'C', false);

					if ($linkedobject->ref)
					{
						$pdf->SetFont('','', $default_font_size - 2);
						$pdf->SetXY($this->marge_gauche+$width+2,$RoundedRectHeight+6);
						$pdf->SetTextColorArray($textcolor);
						$pdf->MultiCell($width, 6, $linkedobject->ref_client, '0', 'C');
					}
					else
					{
						$pdf->SetFont('','', $default_font_size - 2);
						$pdf->SetXY($this->marge_gauche+$width+2,$RoundedRectHeight+6);
						$pdf->SetTextColorArray($textcolor);
						$pdf->MultiCell($width, 6, NR, '0', 'C');
					}
				}
			}

			// Customer code
			$pdf->RoundedRect($this->marge_gauche+$width*2+4, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("CustomerCode"), 0, 'C', false);

			if ($object->client->code_client)
			{
				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight+6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 7, $object->client->code_client, '0', 'C');
			}
			else
			{
				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight+6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255,255,255);
				$pdf->MultiCell($width, 7, 'NR', '0', 'C');
			}

			// Delivery date
			$pdf->RoundedRect($this->marge_gauche+$width*3+6, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche+$width*3+6,$RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("DateDeliveryPlanned"), 0, 'C', false);

			if ($object->date_delivery)
			{
				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche+$width*3+6,$RoundedRectHeight+6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255,255,255);
				$pdf->MultiCell($width, 6, dol_print_date($object->date_delivery,"day",false,$outputlangs,true), '0', 'C');
			}
			else
			{
				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche+$width*3+6,$RoundedRectHeight+6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255,255,255);
				$pdf->MultiCell($width, 6, 'NR', '0', 'C');
			}

			// Deliverer
			$pdf->RoundedRect($this->marge_gauche+$width*4+8, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche+$width*4+8,$RoundedRectHeight);
			$pdf->SetTextColorArray($textcolor);

			if (! empty($object->tracking_number))
			{
				$object->GetUrlTrackingStatus($object->tracking_number);
				if (! empty($object->tracking_url))
				{
					if ($object->shipping_method_id > 0)
					{
						// Get code using getLabelFromKey
						$code=$outputlangs->getLabelFromKey($this->db,$object->shipping_method_id,'c_shipment_mode','rowid','code');
						$label=$outputlangs->trans("SendingMethod".strtoupper($code));
					}
					else
					{
						$label=$outputlangs->transnoentities("Deliverer");
					}
					
					$pdf->MultiCell($width, 3, $label, 0, 'C', false);
					$pdf->SetXY($this->marge_gauche+$width*4+8,$RoundedRectHeight+6);
					$pdf->SetTextColorArray($textcolor);
					$pdf->MultiCell($width, 6, $object->tracking_url, '0', 'C');					
				}
			}
			else
			{
				$pdf->MultiCell($width, 3, $outputlangs->transnoentities("Deliverer"), 0, 'C', false);
				$pdf->SetXY($this->marge_gauche+$width*4+8,$RoundedRectHeight+6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $outputlangs->convToOutputCharset($this->livreur->getFullName($outputlangs)), '0', 'C');
			}			
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
		return pdf_pagefoot($pdf,$outputlangs,'SHIPPING_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,1,$hidefreetext);
	}
}
?>