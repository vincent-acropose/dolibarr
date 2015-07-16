<?php
/* Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Philippe Grand        <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/custom/ultimatepdf/core/modules/livraison/pdf/pdf_ultimate_receipt.modules.php
 *	\ingroup    livraison
 *	\brief      File of class to manage receving receipts with template ultimate_receipt
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/livraison/modules_livraison.php';
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");


/**
 *	\class      pdf_ultimate_receipt
 *	\brief      Classe permettant de generer les bons de livraison au modele Typho
 */

class pdf_ultimate_receipt extends ModelePDFDeliveryOrder
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
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");
		$langs->load("sendings");
		$langs->load("companies");
		$langs->load("ultimatepdf@ultimatepdf");

		$this->db = $db;
		$this->name = "ultimate_receipt";
		$this->description = $langs->trans("DocumentDesignUltimate_receipt");

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

		$this->franchise=!$mysoc->tva_assuj;

		if(!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR))
		{
			$bordercolor = html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR);
			$dashdotted = $conf->global->ULTIMATE_DASH_DOTTED;
			$this->style = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted , 'color' => $bordercolor);
		}

		// Recupere emetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini

		// Defini position des colonnes
		if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
		{
			$this->posxdesc=$this->marge_gauche+1;
		}
		else
		{			
			$this->posxref=$this->marge_gauche+1;
			$this->posxdesc=32;	
		}
		$this->posxcomm=112;
		$this->posxqty=174;

		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxcomm-=20;
			$this->posxqty-=20;
		}

		$this->tva=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}


	/**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int             			1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc,$hookmanager;
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		if (! is_object($outputlangs)) $outputlangs=$langs;

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("deliveries");
		$outputlangs->load("sendings");

		if ($conf->expedition->dir_output)
		{
			$object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->expedition->dir_output."/receipt";
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expedition->dir_output."/receipt/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
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

				$nblines = count($object->lines);
				
				// Create pdf instance
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
				$expedition=new Expedition($this->db);
				$result = $expedition->fetch($object->expedition_id);

				$commande = new Commande($this->db);
				if ($expedition->origin == 'commande')
				{
					$commande->fetch($expedition->origin_id);
				}
				$object->commande=$commande;


				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("DeliveryOrder"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("DeliveryOrder"));
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

				$tab_top = 90;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				// Affiche notes
				$notetoshow=empty($object->note_public)?'':$object->note_public;
				if (! empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_PROPAL_NOTE))
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
					$tab_top = 88;

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
					{
						$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);
					}
					else
					{			
						$pdf->writeHTMLCell(190, 3, $this->posxref-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);	
					}
					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme param
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

				// Loop on each lines
				for ($i = 0 ; $i < $nblines ; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColorArray($textcolor);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					// Description of product line
					$curX = $this->posxdesc-1;
                    $showpricebeforepagebreak=1;

                    $pdf->startTransaction();
					
                    if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF != "yes")
					{
						pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxcomm-$curX,3,$curX,$curY,$hideref,$hidedesc);
					}
					else
					{
						$pdf->SetFont('','', $default_font_size - 3);
						if (!empty($object->lines[$i]->fk_product)) pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,20,3,$this->posxref,$curY,$hideref,$hidedesc,0,'ref');
						$pdf->SetFont('','', $default_font_size - 2);
						pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,$this->posxcomm-$curX,3,$curX,$curY,$hideref,$hidedesc,0,'label');
					}
					
                    $pageposafter=$pdf->getPage();
                    if ($pageposafter > $pageposbefore)	// There is a pagebreak
                    {
                    	$pdf->rollbackTransaction(true);
                    	$pageposafter=$pageposbefore;
						
                    	$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
                    	if ( !($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes"))
						{
							pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxcomm-$curX,3,$curX,$curY,$hideref,$hidedesc);
						}
						else
						{
							$pdf->SetFont('','', $default_font_size - 3);
							if (!empty($object->lines[$i]->fk_product)) pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,20,3,$this->posxref,$curY,$hideref,$hidedesc,0,'ref');
							$pdf->SetFont('','', $default_font_size - 2);
							pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,$this->posxcomm-$curX,3,$curX,$curY,$hideref,$hidedesc,0,'label');
						}
						
						$pageposafter=$pdf->getPage();
                    	$posyafter=$pdf->GetY();
                    	if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
                    	{
                    		if ($i == ($nblines-1))	// No more lines, and no space left to show total, so we create a new page
                    		{
                    			$pdf->AddPage('','',true);
                    			if (! empty($tplidx)) $pdf->useTemplate($tplidx);
                    			if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
                    			$pdf->setPage($pageposafter+1);
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

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

					// Quantity
					$pdf->SetXY($this->posxqty, $curY);
					$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->posxqty, 3, $object->lines[$i]->qty_shipped, 0, 'C');

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
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1);
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
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

				// Affiche zone infos
				$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);

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
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}

		$this->error=$langs->transnoentities("ErrorConstantNotDefined","LIVRAISON_OUTPUTDIR");
		return 0;
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
		global $conf,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);
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
		global $conf,$mysoc,$langs;
		
		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFillColorArray($bgcolor);
		$pdf->SetFont('','', $default_font_size - 2);

		// Rect prend une longueur en 3eme param
		$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, 2, $round_corner = '0110','S', $this->style, '',$hidetop, $hidebottom);
		
		// line prend une position y en 3eme param
		if (empty($hidetop))
		{
			if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes")
			{
				$pdf->SetXY($this->posxref-1, $tab_top);
				$pdf->MultiCell(($this->posxdesc - $this->posxref+2), 6, $outputlangs->transnoentities("Ref"),'','C',1);
			}
		}
		
		$pdf->SetDrawColor(128,128,128);
		$pdf->SetFont('','', $default_font_size - 1);

		if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes")
		{
			$pdf->line($this->posxdesc, $tab_top, $this->posxdesc, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxdesc, $tab_top);
				$pdf->MultiCell(($this->posxcomm - $this->posxdesc), 6, $outputlangs->transnoentities("Description"),'','C',1);
			}
		}
		else
		{			
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxdesc-1, $tab_top);
				$pdf->MultiCell(($this->posxcomm - $this->posxdesc), 6, $outputlangs->transnoentities("Description"),'','C',1);
			}
		}

		// Modif SEB pour avoir une col en plus pour les commentaires clients
		$pdf->line($this->posxcomm, $tab_top, $this->posxcomm, $tab_top + $tab_height);
		if (empty($hidetop)) 
		{
			$pdf->SetXY($this->posxcomm, $tab_top);
			$pdf->MultiCell($this->posxqty - $this->posxcomm,6, $outputlangs->transnoentities("Comments"),0,'L',1);
		}

		// Qty
		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		if (empty($hidetop)) 
		{
			$pdf->SetXY($this->posxqty-1, $tab_top);
			$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->posxqty+1, 6, $outputlangs->transnoentities("QtyShipped"),0,'C',1);
		}

		if (empty($hidebottom)) {
			// Cadres signatures
			$default_font_size = pdf_getPDFFontSize($outputlangs);
			$deltay=$this->page_hauteur*4/5-10;
			$cury=max($cury,$deltay);
			$deltax=$this->marge_gauche;

			$pdf->RoundedRect($deltax, $cury, 85, 40, 2, $round_corner = '1111', 'S', $this->style, '');
			$pdf->SetFont('','B', $default_font_size - 1);
			$pdf->SetXY($deltax, $cury);
			$titre = $outputlangs->transnoentities("For").' '.$outputlangs->convToOutputCharset($mysoc->name);
			$pdf->MultiCell(80, 5, $titre, 0, 'L',0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($deltax, $cury+5);
			$pdf->SetFont('','I', $default_font_size - 2);
			$pdf->MultiCell(90, 3, "",0,'L',0);
			$pdf->SetXY($deltax, $cury+12);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->MultiCell(80, 3, $langs->transnoentities("DocORDER3"), 0, 'L', 0);
			$pdf->SetXY($deltax, $cury+17);
			$pdf->SetFont('','I', $default_font_size - 2);
			$pdf->MultiCell(80, 3, $langs->transnoentities("DocORDER5"), 0, 'L', 0);

			$deltay=$this->page_hauteur*4/5-10;
			$cury=max($cury,$deltay);
			$deltax=$this->page_largeur/2;

			$pdf->RoundedRect($deltax, $cury, 95, 40, 2, $round_corner = '1111', 'S', $this->style, '');
			$pdf->SetFont('','B', $default_font_size - 1);
			$pdf->SetXY($deltax, $cury);
			$titre = $outputlangs->trans("ForCustomer");
			$pdf->MultiCell(80, 5, $titre, 0, 'L',0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($deltax, $cury+5);
			$pdf->SetFont('','I', $default_font_size - 2);
			$pdf->MultiCell(90, 3, "",0,'L',0);
			$pdf->SetXY($deltax, $cury+12);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->MultiCell(80, 3, $langs->transnoentities("DocORDER3"), 0, 'L', 0);
			$pdf->SetXY($deltax, $cury+17);
			$pdf->SetFont('','I', $default_font_size - 2);
			$pdf->MultiCell(80, 3, $langs->transnoentities("DocORDER4"), 0, 'L', 0);
		}

	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			&$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $langs,$conf,$mysoc;
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);
		
		// Show Draft Watermark
		if($object->statut==0 && (! empty($conf->global->COMMANDE_DRAFT_WATERMARK)) )
		{
			pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->COMMANDE_DRAFT_WATERMARK);
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

		// Entete
		$pdf->SetTextColorArray($textcolor);
		$pdf->SetFont('','B', $default_font_size + 2);
		$pdf->SetXY($posx,$posy);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DeliveryOrder")." ".$outputlangs->convToOutputCharset($object->ref), '', 'R');

		$pdf->SetFont('','',$default_font_size + 2);

		$posy+=5;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColorArray($textcolor);
		if ($object->date_valid)
		{
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : " . dol_print_date(($object->date_delivery?$object->date_delivery:$date->valid),"%d %b %Y",false,$outputlangs,true), '', 'R');
		}
		else
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DeliveryNotValidated"), '', 'R');
			$pdf->SetTextColorArray($textcolor);
		}

		if ($object->client->code_client)
		{
			$posy+=5;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}

		$pdf->SetTextColorArray($textcolor);

		$posy+=2;

		// Add list of linked orders on shipment
		// Currently not supported by pdf_writeLinkedObjects, link for delivery to order is done through shipment)
		if ($object->origin == 'expedition' || $object->origin == 'shipping')
		{
			$Yoff=$posy-5;

			include_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
			$shipment = new Expedition($this->db);
			$shipment->fetch($object->origin_id);

		    $origin 	= $shipment->origin;
			$origin_id 	= $shipment->origin_id;

			if ($conf->$origin->enabled)
			{
				$outputlangs->load('orders');

				$classname = ucfirst($origin);
				$linkedobject = new $classname($this->db);
				$result=$linkedobject->fetch($origin_id);
				if ($result >= 0)
				{
					$pdf->SetFont('','', $default_font_size - 2);
					$text=$linkedobject->ref;
					if ($linkedobject->ref_client) $text.=' ('.$linkedobject->ref_client.')';
					$Yoff = $Yoff+8;
					$pdf->SetXY($this->page_largeur - $this->marge_droite - 100,$Yoff);
					$pdf->MultiCell(100, 2, $outputlangs->transnoentities("RefOrder") ." : ".$outputlangs->transnoentities($text), 0, 'R');
					$Yoff = $Yoff+3;
					$pdf->SetXY($this->page_largeur - $this->marge_droite - 60,$Yoff);
					$pdf->MultiCell(60, 2, $outputlangs->transnoentities("OrderDate")." : ".dol_print_date($linkedobject->date,"day",false,$outputlangs,true), 0, 'R');
				}
			}

			$posy=$Yoff;
		}
		
		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

		if ($showaddress)
		{
			// Sender properties
			$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
			$carac_emetteur = pdf_build_address($outputlangs,$this->emetteur);

			// Show sender
			$posy=40;
			$posx=$this->marge_gauche;
			$hautcadre=40;

			// Show sender frame
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->SetFont('','', $default_font_size - 1);
			$pdf->RoundedRect($posx, $posy, 84, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor );

			// Show sender name
			$pdf->SetXY($this->marge_gauche+2,$posy+3);
			$pdf->SetFont('','B',$default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetXY($posx+2,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell(86,4, $carac_emetteur, 0, "L");

			// If SHIPPING contact defined on invoice, we use it
			$usecontact=false;
			$arrayidcontact=$object->commande->getIdContact('external','SHIPPING');
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
			$posy=40;
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;

			// Show recipient frame
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor );

			// Show customer/recipient name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox,4, $carac_client_name, 0, 'L');

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy+4+(dol_nboflines_bis($carac_client_name,50)*4));
			$pdf->MultiCell($widthrecbox,4, $carac_client, 0, 'L');
		}
		$pdf->SetTextColorArray($textcolor);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *		@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	void
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		return pdf_pagefoot($pdf,$outputlangs,'DELIVERY_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,0,$hidefreetext);
	}

}

?>