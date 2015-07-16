<?php
/* Copyright (C) 2004-2010 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin          <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand       <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010      Juanjo Menent		  <jmenent@2byte.es>
 * Copyright (C) 2011-2014 Philippe Grand		  <philippe.grand@atoo-net.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/custom/ultimatepdf/core/modules/facture/doc/pdf_ultimate_invoice1.modules.php
 *	\ingroup    facture
 *	\brief      File of class to generate invoices from pdf_ultimate_invoice1 model
 */

require_once(DOL_DOCUMENT_ROOT."/core/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/pdf.lib.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");


/**
 *	Class to manage PDF invoice pdf_ultimate_invoice1 template 
 */

class pdf_ultimate_invoice1 extends ModelePDFFactures
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
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("perso");
		$langs->load("bills");
		$langs->load("ultimatepdf@ultimatepdf");

		$this->db = $db;
		$this->name = "ultimate_invoice1";
		$this->description = $langs->trans('PDFUltimate_invoice1Description');

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

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 1;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;

		if(!empty($conf->global->ULTIMATE_BORDERCOLOR_COLOR))
		{
			$bordercolor = html2rgb($conf->global->ULTIMATE_BORDERCOLOR_COLOR);
			$dashdotted = $conf->global->ULTIMATE_DASH_DOTTED;
			$this->style = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => $dashdotted , 'color' => $bordercolor);
		}

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Defini position of columns
		if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF != "yes")
		{
			$this->posxdesc=$this->marge_gauche+1;
		}
		else
		{			
			$this->posxref=$this->marge_gauche+1;
			$this->posxdesc=$this->marge_gauche + (isset($conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH)?$conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH:22);	
		}
		$this->posxtva=121;
		$this->posxup=137;
		$this->posxqty=155;
		$this->posxdiscount=168;
		$this->postotalht=179;
		if (! ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no" && empty($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN))) $this->posxtva=$this->posxup;
		$this->posxpicture=$this->posxtva-1 - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?20:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH);	// width of images
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxdesc-=20;
			$this->posxpicture-=20;
			$this->posxtva-=20;
			$this->posxup-=20;
			$this->posxqty-=20;
			$this->posxdiscount-=20;
			$this->postotalht-=20;
		}

		$this->tva=array();
		$this->localtax1=array();
		$this->localtax2=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}

	/**
     *  Function to build pdf onto disk
     *
     *  @param		int		$object				Id of object to generate
     *  @param		object	$outputlangs		Lang output object
     *  @param		string	$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int		$hidedetails		Do not show line details
     *  @param		int		$hidedesc			Do not show desc
     *  @param		int		$hideref			Do not show ref
     *  @param		object	$hookmanager		Hookmanager object
     *  @return     int             			1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc,$db,$hookmanager;
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		if (! is_object($outputlangs)) $outputlangs=$langs;

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		
		$nblignes = count($object->lines);
		
		// Loop on each lines to detect if there is at least one image to show
		$realpatharray=array();
		if (! empty($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PICTURE))
		{
			for ($i = 0 ; $i < $nblignes ; $i++)
			{
				if (empty($object->lines[$i]->fk_product)) continue;
				
				$objphoto = new Product($this->db);
				$objphoto->fetch($object->lines[$i]->fk_product);

				$pdir = get_exdir($object->lines[$i]->fk_product,2) . $object->lines[$i]->fk_product ."/photos/";
				$dir = $conf->product->dir_output.'/'.$pdir;

				$realpath='';
				foreach ($objphoto->liste_photos($dir,1) as $key => $obj)
				{
					$filename=$obj['photo'];
					$realpath = $dir.$filename;
					break;
				}

				if ($realpath) $realpatharray[$i]=$realpath;
			}
		}
		if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

		if ($conf->facture->dir_output)
		{
			$object->fetch_thirdparty();

			$deja_regle = $object->getSommePaiement();
			$amount_credit_notes_included = $object->getSumCreditNotesUsed();
			$amount_deposits_included = $object->getSumDepositsUsed();

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->facture->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->facture->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}
			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
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
				$default_font_size = pdf_getPDFFontSize($outputlangs);  // Must be after pdf_getInstance
				$heightforinfotot = 40;	// Height reserved to output the info and total part
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

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Invoice"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// Positionne $this->atleastonediscount si on a au moins une remise
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					if ($object->lines[$i]->remise_percent)
					{
						$this->atleastonediscount++;
					}
				}
				if (empty($this->atleastonediscount))
				{
					$this->posxpicture+=($this->postotalht - $this->posxdiscount);
					$this->posxtva+=($this->postotalht - $this->posxdiscount);
					$this->posxup+=($this->postotalht - $this->posxdiscount);
					$this->posxqty+=($this->postotalht - $this->posxdiscount);
					$this->posxdiscount+=($this->postotalht - $this->posxdiscount);
				}

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

				$tab_top = $this->marge_haute+$logo_height+56;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?$this->marge_haute+$logo_height+5:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				// Affiche notes
				$notetoshow=empty($object->note_public)?'':$object->note_public;
				if (! empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_INVOICE_NOTE))
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
					$tab_top = $this->marge_haute+$logo_height+56;

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF != "yes")
					{
						$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					}
					else
					{
						$pdf->writeHTMLCell(190, 3, $this->posxref-1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					}
					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme et 4eme param
					$pdf->SetDrawColor(192,192,192);					
					$pdf->RoundedRect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1, 2, $round_corner = '1111', 'S', $this->style, $fill_color=array());

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY+4;
				}
				else
				{
					$height_note=0;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				// Loop on each lines
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 2);   // Into loop to work with multipage
					$pdf->SetTextColorArray($textcolor);

					// Define size of image if we need it
					$imglinesize=array();
					if (! empty($realpatharray[$i])) $imglinesize=pdf_getSizeForImage($realpatharray[$i]);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();
					
					$showpricebeforepagebreak=1;
					$posYAfterImage=0;
					$posYAfterDescription=0;

					// We start with Photo of product line
					if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur-($heightforfooter+$heightforfreetext+$heightforinfotot)))	// If photo too high, we moved completely on new page
					{
						$pdf->AddPage('','',true);
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						$pdf->setPage($pagenb+1);

						$curY = $tab_top_newpage;
						$showpricebeforepagebreak=0;
					}

					if (isset($imglinesize['width']) && isset($imglinesize['height']))
					{
						$curX = $this->posxpicture-1;
						$pdf->Image($realpatharray[$i], $curX + (($this->posxtva-$this->posxpicture-$imglinesize['width'])/2), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);	// Use 300 dpi
						// $pdf->Image does not increase value return by getY, so we save it manually
						$posYAfterImage=$curY+$imglinesize['height'];
					}

					// Description of product line
					$curX = $this->posxdesc-1;

					$pdf->startTransaction();
					if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF != "yes")
					{
						pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxpicture-$curX,3,$curX,$curY,$hideref,$hidedesc);
					}
					else
					{
						$pdf->SetFont('','', $default_font_size - 3);
						if (!empty($object->lines[$i]->fk_product)) pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,22,3,$this->marge_gauche,$curY,$hideref,$hidedesc,0,'ref');
						$pdf->SetFont('','', $default_font_size - 2);
						pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,$this->posxpicture-$curX,3,$curX,$curY,$hideref,$hidedesc,0,'label');
					}
					
					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF != "yes")
						{
							pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxpicture-$curX,3,$curX,$curY,$hideref,$hidedesc);
						}
						else
						{
							$pdf->SetFont('','', $default_font_size - 3);
							if (!empty($object->lines[$i]->fk_product)) pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,22,3,$this->marge_gauche,$curY,$hideref,$hidedesc,0,'ref');
							$pdf->SetFont('','', $default_font_size - 2);
							pdf_writelinedesc_ref($pdf,$object,$i,$outputlangs,$this->posxpicture-$curX,3,$curX,$curY,$hideref,$hidedesc,0,'label');
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
								$pdf->setPage($pageposafter+1);
							}
						}
						else
						{
							// We found a page break
							$showpricebeforepagebreak=1;
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
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

					// VAT Rate
					if ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no" && empty($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN))
					{
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxtva, $curY);
						$pdf->MultiCell($this->posxup-$this->posxtva-0.8, 3, $vat_rate, 0, 'C');
					}

					// Unit price before discount
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_PUHT))
					{
						$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY ($this->posxup, $curY);
						$pdf->MultiCell($this->posxqty-$this->posxup, 3, $up_excl_tax, 0, 'C', 0);
					}

					// Quantity
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_QTY))
					{
						$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY ($this->posxqty, $curY);
						$pdf->MultiCell($this->posxdiscount-$this->posxqty-0.8, 3, $qty, 0, 'C');
					}	

					// Discount on line					
					if ($object->lines[$i]->remise_percent)
					{
						$pdf->SetXY($this->posxdiscount-2, $curY);
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->postotalht-$this->posxdiscount+2, 3, $remise_percent, 0, 'C');
					}

					// Total HT line
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_THT))
					{
						$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY ($this->postotalht, $curY);
						$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalht, 3, $total_excl_tax, 0, 'R', 0);
					}

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					$tvaligne=$object->lines[$i]->total_tva;
					$localtax1ligne=$object->lines[$i]->total_localtax1;
					$localtax2ligne=$object->lines[$i]->total_localtax2;
					$localtax1_rate=$object->lines[$i]->localtax1_tx;
					$localtax2_rate=$object->lines[$i]->localtax2_tx;
					$localtax1_type=$object->lines[$i]->localtax1_type;
					$localtax2_type=$object->lines[$i]->localtax2_type;

					if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax1ligne-=($localtax1ligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax2ligne-=($localtax2ligne*$object->remise_percent)/100;

					$vatrate=(string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
					&& (! empty($localtax1_rate) || ! empty($localtax2_rate))) // and there is local tax
					{
						$localtaxtmp_array=getLocalTaxesFromRate($vatrate,0,$object->thirdparty,$mysoc);
						$localtax1_type = $localtaxtmp_array[0];
						$localtax2_type = $localtaxtmp_array[2];
					}

				    // retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0)
						$this->localtax1[$localtax1_type][$localtax1_rate]+=$localtax1ligne;
					if ($localtax2_type && $localtax2ligne != 0)
						$this->localtax2[$localtax2_type][$localtax2_rate]+=$localtax2ligne;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
					if (! isset($this->tva[$vatrate])) 				$this->tva[$vatrate]='';
					$this->tva[$vatrate] += $tvaligne;
					
					if ($posYAfterImage > $posYAfterDescription) $nexY=$posYAfterImage;

					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1))
					{
						$pdf->setPage($pageposafter);
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

				// Affiche zone totaux
				$posy=$this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				// Affiche zone versements
				if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included)
				{
					$posy=$this->_tableau_versements($pdf, $object, $posy, $outputlangs);
				}

				if ($object->mode_reglement_code == 'LCR')
				{
					$this->_pagefoot($pdf,$object,$outputlangs);

					// New page
					$pdf->AddPage();
					$this->_pagehead($pdf, $object, 0, $outputlangs);
					pdf_pagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,($this->marge_haute)+80,$this->marge_gauche,$this->page_hauteur, $object);
					$this->_pagelcr($pdf, $object, 180, $outputlangs);
				}
				else
				{
					$this->_pagefoot($pdf, $object, $outputlangs);
					if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();
				}
				
				// Add PDF ask to merge
				dol_include_once ( '/ultimatepdf/class/invoicemergedpdf.class.php' );
				
				$already_merged=array();

				if (! empty ( $object->id ) && !(in_array($object->id, $already_merged))) {
					
					// Find the desire PDF
					$filetomerge = new Invoicemergedpdf ( $this->db );
						
					if ($conf->global->MAIN_MULTILANGS) {
						$filetomerge->fetch_by_invoice ( $object->id, $outputlangs->defaultlang);
					} else {
						$filetomerge->fetch_by_invoice ( $object->id );
					}
											
					$already_merged[]= $object->id;
						
					// If PDF is selected and file is not empty
					if (count ( $filetomerge->lines ) > 0) {
						foreach ( $filetomerge->lines as $linefile ) {
								
							if (! empty ( $linefile->id ) && ! empty ( $linefile->file_name )) {

								$filetomerge_dir = $conf->facture->dir_output. '/' . dol_sanitizeFileName ( $object->ref );
								
								$infile = $filetomerge_dir . '/' . $linefile->file_name;
								dol_syslog ( get_class ( $this ) . ':: $upload_dir=' . $filetomerge_dir, LOG_DEBUG );
								// If file really exists
								if (is_file ( $infile )) {
										
									$count = $pdf->setSourceFile ( $infile );
									// import all page
									for($i = 1; $i <= $count; $i ++) {
										// New page
										$pdf->AddPage ();
										$tplIdx = $pdf->importPage ( $i );
										$pdf->useTemplate ( $tplIdx, 0, 0, $this->page_largeur );
										if (method_exists ( $pdf, 'AliasNbPages' ))
											$pdf->AliasNbPages ();
									}
								}
							}
						}
					}
				}

				$pdf->Close();

				$pdf->Output($file,'F');

				// Add pdfgeneration hook
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
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","FAC_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}


	/**
	 *  Show payments table
	 *
     *  @param	PDF			&$pdf           Object PDF
     *  @param  Object		$object         Object invoice
     *  @param  int			$posy           Position y in PDF
     *  @param  Translate	$outputlangs    Object langs for output
     *  @return int             			<0 if KO, >0 if OK
	 */
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf,$langs;

		$sign=1;
        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

		$tab3_posx = 120;
		$tab3_top = $posy + 8;
		$tab3_width = 80;
		$tab3_height = 4;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$tab3_posx -= 20;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$currency_code = $langs->getCurrencySymbol($conf->currency);

		$title=$outputlangs->transnoentities("PaymentsAlreadyDone");
		if ($object->type == 2) $title=$outputlangs->transnoentities("PaymentsBackAlreadyDone");

		$pdf->SetFont('','', $default_font_size - 2);
		$pdf->SetXY ($tab3_posx, $tab3_top - 5);
		$pdf->MultiCell(60, 5, $title, 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top, $tab3_posx+$tab3_width, $tab3_top);

		$pdf->SetFont('','', $default_font_size - 4);
		$pdf->SetXY ($tab3_posx, $tab3_top );
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY ($tab3_posx+21, $tab3_top );
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY ($tab3_posx+40, $tab3_top );
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY ($tab3_posx+58, $tab3_top );
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top-1+$tab3_height, $tab3_posx+$tab3_width, $tab3_top-1+$tab3_height);

		$y=0;

		$pdf->SetFont('','', $default_font_size - 4);

		// Loop on each deposits and credit notes included
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
		$sql.= " re.description, re.fk_facture_source,";
		$sql.= " f.type, f.datef";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except AS re, ".MAIN_DB_PREFIX ."facture AS f";
		$sql.= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = ".$object->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			$invoice=new Facture($this->db);
			while ($i < $num)
			{
				$y+=3;
				$obj = $this->db->fetch_object($resql);

				if ($obj->type == 2) $text=$outputlangs->trans("CreditNote");
				elseif ($obj->type == 3) $text=$outputlangs->trans("Deposit");
				else $text=$outputlangs->trans("UnknownType");

				$invoice->fetch($obj->fk_facture_source);

				$pdf->SetXY ($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($obj->datef,'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($obj->amount_ttc, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+40, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $text, 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3 );

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog($this->db,$this->error, LOG_ERR);
			return -1;
		}

		// Loop on each payment
		$sql = "SELECT p.datep AS date, p.fk_paiement AS type, p.num_paiement AS num, pf.amount AS amount,";
		$sql.= " cp.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture AS pf, ".MAIN_DB_PREFIX."paiement as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement AS cp ON p.fk_paiement = cp.id";
		$sql.= " WHERE pf.fk_paiement = p.rowid and pf.fk_facture = ".$object->id;
		$sql.= " ORDER BY p.datep";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$y+=3;
				$row = $this->db->fetch_object($resql);

				$pdf->SetXY ($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date),'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($sign * $row->amount, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+40, $tab3_top+$y);
				$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog($this->db,$this->error, LOG_ERR);
			return -1;
		}

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

		$pdf->SetFont('','', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1)
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		$widthrecbox=($this->page_largeur-$this->marge_gauche-$this->marge_droite)/2;

		// Show payments conditions
		if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement))
		{
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = '<b>'.$outputlangs->transnoentities("PaymentConditions").'</b>'.': ';		
			$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
			$pdf->writeHTMLCell($widthrecbox, 4,$this->marge_gauche,$posy, $titre.' '.$lib_condition_paiement, 0, 0, false, true, 'L', true);
	        
			$posy=$pdf->GetY()+7;
		}

		if ($object->type != 2)
		{
			// Check a payment mode is defined
			if (empty($object->mode_reglement_code)
			&& ! $conf->global->FACTURE_CHQ_NUMBER
			&& ! $conf->global->FACTURE_RIB_NUMBER)
			{
				$this->error = $outputlangs->transnoentities("ErrorNoPaiementModeConfigured");
			}
			// Avoid having any valid PDF with setup that is not complete
			elseif (($object->mode_reglement_code == 'CHQ' && empty($conf->global->FACTURE_CHQ_NUMBER))
				|| ($object->mode_reglement_code == 'VIR' && empty($conf->global->FACTURE_RIB_NUMBER)))
			{
				$outputlangs->load("errors");

				$pdf->SetXY($this->marge_gauche, $posy);
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$this->error = $outputlangs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup",$object->mode_reglement_code);
				$pdf->MultiCell($widthrecbox, 3, $this->error,0,'L',0);
				$pdf->SetTextColorArray($textcolor);

				$posy=$pdf->GetY()+1;
			}

			// Show payment mode
			if ($object->mode_reglement_code
			&& $object->mode_reglement_code != 'CHQ'
			&& $object->mode_reglement_code != 'VIR')
			{
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $posy);
				$titre = $outputlangs->transnoentities("PaymentMode").':';
				$pdf->MultiCell($widthrecbox, 5, $titre, 0, 'L');
				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY($widthrecbox, $posy);
				$lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell($widthrecbox, 5, $lib_mode_reg,0,'L');

				$posy=$pdf->GetY()+2;
			}
			
			// Auto-liquidation régime de la sous-traitance
			if (! empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_AUTO_LIQUIDATION))
			{
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $posy);
				$titre1 = $outputlangs->transnoentities("AutoLiquidation1");			
				$pdf->MultiCell($widthrecbox, 4, $titre1, 0, 'L');
				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY(70, $posy);
				$titre2 = $outputlangs->transnoentities("AutoLiquidation2");
				$pdf->MultiCell(60, 4, $titre2, 0, 'L');
				$posy=$pdf->GetY()+2;
			}
			
			// Example using extrafields
			$title_key=(empty($object->array_options['options_newline']))?'':($object->array_options['options_newline']);
			$extrafields = new ExtraFields ( $this->db );
			$extralabels = $extrafields->fetch_name_optionals_label ( $object->table_element, true );
			if (is_array ( $extralabels ) && key_exists ( 'newline', $extralabels ) && !empty($title_key)) {
				$title = $extrafields->showOutputField ( 'newline', $title_key );
				$pdf->MultiCell($widthrecbox, 5, $title, 0, 'L');
				$posy=$pdf->GetY()+2;
			}

			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ')
			{
				// Si mode reglement non force ou si force a CHQ
				if (! empty($conf->global->FACTURE_CHQ_NUMBER))
				{
					$diffsizetitle=(empty($conf->global->PDF_DIFFSIZE_TITLE)?3:$conf->global->PDF_DIFFSIZE_TITLE);
						
					if ($conf->global->FACTURE_CHQ_NUMBER > 0)
					{
						$account = new Account($this->db);
						$account->fetch($conf->global->FACTURE_CHQ_NUMBER);

						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell($widthrecbox, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio),0,'L',0);
						$posy=$pdf->GetY()+1;

			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
			            {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('','', $default_font_size - $diffsizetitle);
							$pdf->MultiCell($widthrecbox, 3, $outputlangs->convToOutputCharset($account->owner_address), 0, 'L', 0);
							$posy=$pdf->GetY()+2;
			            }
					}
					if ($conf->global->FACTURE_CHQ_NUMBER == -1)
					{
						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell($widthrecbox, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$this->emetteur->name),0,'L',0);
						$posy=$pdf->GetY()+1;

			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
			            {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('','', $default_font_size - $diffsizetitle);
							$pdf->MultiCell($widthrecbox, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
							$posy=$pdf->GetY()+2;
			            }
					}
				}
			}

			// If payment mode not forced or forced to VIR, show payment with BAN
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR')
			{
				if (! empty($object->fk_bank) || ! empty($conf->global->FACTURE_RIB_NUMBER))
				{
					$bankid=(empty($object->fk_bank)?$conf->global->FACTURE_RIB_NUMBER:$object->fk_bank);
					$account = new Account($this->db);
					$account->fetch($bankid);

					$curx=$this->marge_gauche;
					$cury=$posy;

					$posy=pdf_ultimate_invoices_bank($pdf,$outputlangs,$curx,$cury,$account,0,$default_font_size);
				}
			}
		}
		return $posy;
	}


	/**
	 *	Show total to pay
	 *
	 *	@param      pdf             Objet PDF
	 *	@param      object          Objet facture
	 *	@param      deja_regle      Montant deja regle
	 *	@param		posy			Position depart
	 *	@param		outputlangs		Objet langs
	 *	@return     y               Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf,$mysoc,$langs;

		$sign=1;
        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$currency_code = $langs->getCurrencySymbol($conf->currency);
		$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('','', $default_font_size - 1);

		// Tableau total
		$col1x = 120; $col2x = 170;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);
		
		$widthrecbox=($this->page_largeur-$this->marge_gauche-$this->marge_droite-4)/2;
		$deltax=$this->marge_gauche+$widthrecbox+4;
		$pdf->RoundedRect($deltax, $tab2_top, $widthrecbox, 20, 2, $round_corner = '1111', 'F', $this->style, $bgcolor);

		$useborder=0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY ($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
		$pdf->SetXY ($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($object->total_ht + (! empty($object->remise)?$object->remise:0)),0,$outputlangs,0,-1,-1,$currency_code), 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248,248,248);

		$this->atleastoneratenotnull=0;
		if ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no")
		{
			$tvaisnull=((! empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (! empty($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT_ISNULL) && $tvaisnull)
			{
				// Nothing to do
			}
			else
			{
				//Local tax 1 before VAT
				
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5','7'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'R', 1);
							}
						}
					}

				//Local tax 2 before VAT
				
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5','7'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'R', 1);
							}
						}
					}
				
				// VAT
				foreach($this->tva as $tvakey => $tvaval)
				{
					if ($tvakey > 0)    // On affiche pas taux 0
					{
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl='';
						if (preg_match('/\*/',$tvakey))
						{
							$tvakey=str_replace('*','',$tvakey);
							$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat =$outputlangs->transnoentities("TotalVAT").' ';
						$totalvat.=vatrate($tvakey,1).$tvacompl;
						$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'R', 1);
					}
				}

				//Local tax 1 after VAT
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey>0)    // On affiche pas taux 0
							{
								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;

								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);
								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'R', 1);
							}
						}
					}
				//Local tax 2 after VAT
				
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
						    // retrieve global local tax
							if ($tvakey>0)    // On affiche pas taux 0
							{
								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';

								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'R', 1);
							}
						}
					}
				
				// Revenue stamp
				if (price2num($object->revenuestamp) != 0)
				{
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RevenueStamp"), $useborder, 'L', 1);

					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->revenuestamp).''.$currency_code, $useborder, 'R', 1);
				}
				
				// Total TTC
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(224,224,224);
				$pdf->SetFont('','B',$default_font_size );
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl,  price($sign * $object->total_ttc, 0, $outputlangs,0,-1,-1,$currency_code), $useborder, 'R', 1);
			}
		}
		else
		{
			// Total TTC without VAT			
			$index++;
			$pdf->SetXY ($col1x, $tab2_top + 0);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('','B',$default_font_size );
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ht + (! empty($object->remise)?$object->remise:0),0,$userlang,0,-1,-1,$currency_code), 0, 'R', 1);		
		}
		
		$pdf->SetTextColorArray($textcolor);

		$creditnoteamount=$object->getSumCreditNotesUsed();
		$depositsamount=$object->getSumDepositsUsed();
		$resteapayer = price2num($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if ($object->paye) $resteapayer=0;

		if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0)
		{
			// Already paid + Deposits
			$index++;
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'R', 0);

			// Credit note
			if ($creditnoteamount)
			{
				$index++;
				$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
				$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'R', 0);
			}

			// Escompte
			if ($object->close_code == 'discount_vat')
			{
				$index++;
				$pdf->SetFillColor(255,255,255);

				$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOffered"), $useborder, 'L', 1);
				$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs,0,-1,-1,$currency_code), $useborder, 'R', 1);

				$resteapayer=0;
			}

			$index++;
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFillColor(224,224,224);
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs,0,-1,-1,$currency_code), $useborder, 'R', 1);

			// Fin
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetTextColorArray($textcolor);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			&$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf,$langs;
		$outputlangs->load("ultimatepdf@ultimatepdf");

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColorArray($textcolor);
        $pdf->SetFillColorArray($bgcolor);
		$pdf->SetFont('','', $default_font_size - 2);

		$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, 2, $round_corner = '0110','S', $this->style, '');   
		
		// line prend une position y en 2eme param et 4eme param		
		if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes")
		{
			$pdf->line($this->posxdesc-1, $tab_top, $this->posxdesc-1, $tab_top + $tab_height);
		}
		if (empty($hidetop))
		{		
			if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF == "yes")
			{			
				$pdf->SetXY ($this->posxref-1, $tab_top);
				$pdf->MultiCell($this->posxdesc,6, $outputlangs->transnoentities("Ref"),0,'L',1);
				$pdf->SetXY ($this->posxdesc-1, $tab_top);
				$pdf->MultiCell(112,6, $outputlangs->transnoentities("Designation"), 0, 'L', 1);
			}
			else			
			{			
				$pdf->SetXY ($this->posxdesc-1, $tab_top);
				$pdf->MultiCell(120,6, $outputlangs->transnoentities("Designation"),0,'L',1);
			}
		}
		
		if (!empty($conf->global->ULTIMATE_GENERATE_INVOICES_WITH_PICTURE))
		{
			$pdf->line($this->posxpicture-1, $tab_top, $this->posxpicture-1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
			
			}
		}		

		if ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no" && empty($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN))
		{
			$pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxtva-3, $tab_top);
				$pdf->MultiCell($this->posxup-$this->posxtva+3,6, $outputlangs->transnoentities("VAT"),0,'C', 1);
			}
		}
		else
		{
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxdesc, $tab_top);
				$pdf->MultiCell($this->posxup-$this->posxdesc+3,6, $outputlangs->transnoentities(""),0,'C', 1);
			}	
		}

		$pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY ($this->posxup-1, $tab_top);
			$pdf->MultiCell($this->posxqty-$this->posxup+2,6, $outputlangs->transnoentities("PriceUHT"), 0, 'C', 1);
		}

		 $pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY ($this->posxqty-1, $tab_top);
			$pdf->MultiCell($this->posxdiscount-$this->posxqty+1,6, $outputlangs->transnoentities("Qty"), 0, 'C', 1);
		}

		$pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			if ($this->atleastonediscount)
			{
				$pdf->SetXY ($this->posxdiscount-1, $tab_top);
				$pdf->MultiCell($this->postotalht-$this->posxdiscount+1,6, $outputlangs->transnoentities("ReductionShort"), 0, 'C', 1);
			}
		}

        if ($this->atleastonediscount)
        {
            $pdf->line($this->postotalht-1, $tab_top, $this->postotalht-1, $tab_top + $tab_height);
        }
        if (empty($hidetop))
        {
        	 $pdf->SetXY ($this->postotalht-16, $tab_top);
        	 $pdf->MultiCell($this->page_largeur-$this->postotalht+6,6, $outputlangs->transnoentities("TotalHT"), 0, 'R', 1);
        }
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			&$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	object		$hookmanager	Hookmanager object
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf,$langs;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("companies");
		$outputlangs->load('deliveries');

		$default_font_size = pdf_getPDFFontSize($outputlangs);	
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);
		$qrcodecolor =  html2rgb($conf->global->ULTIMATE_QRCODECOLOR_COLOR);
		$main_page = $this->page_largeur-$this->marge_gauche-$this->marge_droite;

		//affiche repere de pliage	
		if (! empty($conf->global->MAIN_DISPLAY_INVOICES_FOLD_MARK))
		{
			$pdf->Line(0,($this->page_hauteur)/3,3,($this->page_hauteur)/3);
		}
		
		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);
		
		//Affiche le filigrane brouillon - Print Draft Watermark
        if($object->statut==0 && (! empty($conf->global->FACTURE_DRAFT_WATERMARK)) )
        {
		    pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->FACTURE_DRAFT_WATERMARK);
        }

		//Print content
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
		
		//Display Thirdparty barcode at top			
		$barcode=$object->client->barcode;
		$object->client->fetch_barcode();
		$posxbarcode=$this->page_largeur*2/3;
		$posybarcode=$posy-$this->marge_haute;
		$pdf->SetXY($posxbarcode,$posy-$this->marge_haute);
		$styleBc = array(
			'position' => '',
			'align' => 'R',
			'stretch' => false,
			'fitwidth' => true,
			'cellfitalign' => '',
			'border' => false,
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
		if (! empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_BARCODE))
		{
			if ($barcode) 
			$pdf->write1DBarcode($barcode, $object->client->barcode_type_code, $posxbarcode, $posybarcode, '', 12, 0.4, $styleBc, 'R');
		}
		
		if ($logo_height<=30) {
			$heightQRcode=$logo_height;
		}
		else {
			$heightQRcode=30;
		}
		$posxQRcode=$this->page_largeur/2;
		// set style for QR-code
		$styleQr = array(
		'border' => false,
		'vpadding' => 'auto',
		'hpadding' => 'auto',
		'fgcolor' => $qrcodecolor,
		'bgcolor' => false, //array(255,255,255)
		'module_width' => 1, // width of a single module in points
		'module_height' => 1 // height of a single module in points
		);
		$posxQRcode=$this->page_largeur/2+4;
		// QR-code
		if (! empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_TOP_QRCODE))
		{
			$code = pdf_codeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}
		// My Company QR-code
		if (! empty($conf->global->ULTIMATEPDF_GENERATE_INVOICES_WITH_MYCOMP_QRCODE))
		{
			$code = pdf_mycompCodeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}

		$pdf->SetFont('','B', $default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColorArray($textcolor);
		$title=$outputlangs->transnoentities("Invoice");
		if ($object->type == 1) $title=$outputlangs->transnoentities("InvoiceReplacement");
		if ($object->type == 2) $title=$outputlangs->transnoentities("InvoiceAvoir");
		if ($object->type == 3) $title=$outputlangs->transnoentities("InvoiceDeposit");
		if ($object->type == 4) $title=$outputlangs->transnoentities("InvoiceProFormat");
		$pdf->MultiCell(100, 3, $title, '', 'R');

		$pdf->SetFont('','B', $default_font_size + 2);

		$posy+=6;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy+=5;
		$pdf->SetFont('','', $default_font_size - 1);

		$objectidnext=$object->getIdReplacingInvoice('validated');
		if ($object->type == 0 && $objectidnext)
		{
			$objectreplacing=new Facture($this->db);
			$objectreplacing->fetch($objectidnext);

			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($objectreplacing->ref), '', 'R');
		}
		if ($object->type == 1)
		{
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}
		if ($object->type == 2)
		{
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}
		
		// Example using extrafields
		$title_key=(empty($object->array_options['options_codesupplier']))?'':($object->array_options['options_codesupplier']);	
		$extrafields = new ExtraFields ( $this->db );
		$extralabels = $extrafields->fetch_name_optionals_label ( $object->table_element, true );
		if (is_array ( $extralabels ) && key_exists ( 'codesupplier', $extralabels ) && !empty($title_key)) {
			$title = $extrafields->showOutputField ( 'codesupplier', $title_key );
			$posy+=2;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColorArray($textcolor);
			$pdf->MultiCell(100, 5, $title, 0, 'R');
		}

		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size+1);

		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur = pdf_invoice_build_address($outputlangs,$this->emetteur);
			$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);

			// Show sender
			$posy=$logo_height+$this->marge_haute+2;
			$posx=$this->marge_gauche;
			$hautcadre=40;
			$widthrecbox=($this->page_largeur-$this->marge_gauche-$this->marge_droite-4)/2;
			if (! empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx=$this->page_largeur-$this->marge_droite-$widthrecbox;     

			// Show sender frame
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor);

			// Show sender name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox-5, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetXY($posx+2,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox-5, 4, $carac_emetteur, 0, 'L');

			if ($arrayidcontact=$object->getIdContact('external','BILLING') && $object->getIdContact('external','SHIPPING'))
			{
				// If SHIPPING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','BILLING');
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

				$carac_client=pdf_invoice_build_address($outputlangs,$this->emetteur,$object->client,($usecontact?$object->contact:''),$usecontact,'target');

				// Show recipient
				$posy=$logo_height+$this->marge_haute+2;
				$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
				if (! empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx=$this->marge_gauche;


				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('','', $default_font_size - 2);

				// Show invoice address
				$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre*0.6, 2, $round_corner = '1111', 'F', '', $bgcolor);
				$pdf->SetXY($posx+2,$posy);
				$pdf->MultiCell($widthrecbox-5,4, $outputlangs->transnoentities("BillAddress"), 0, 'R');

				// Show recipient name
				$pdf->SetXY($posx+2,$posy+1);
				$pdf->SetFont('','B', $default_font_size);
				$pdf->MultiCell($widthrecbox-5,4, $carac_client_name, 0, 'L');

				// Show recipient information
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->SetXY($posx+2,$posy+6);
				$pdf->MultiCell($widthrecbox-5,4, $carac_client, 0, 'L');
				
				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('','', $default_font_size - 2);

				// Show shipping address
				$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre*0.6, 2, $round_corner = '1111', 'F', '', $bgcolor);
				$pdf->SetXY($posx+2,$posy);
				$pdf->MultiCell($widthrecbox-5,4, $outputlangs->transnoentities("DeliveryAddress"), 0, 'R');		

				// If SHIPPING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','SHIPPING');

				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}

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
				$carac_client=pdf_invoice_build_address($outputlangs,$this->emetteur,$object->client,($usecontact?$object->contact:''),$usecontact,'target');

				// Show recipient name
				$pdf->SetXY($posx+2,$posy+1);
				$pdf->SetFont('','B', $default_font_size);
				$pdf->MultiCell($widthrecbox-5,4, $carac_client_name, 0, 'L');
			
				// Show recipient information
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->SetXY($posx+2,$posy+6);
				$pdf->MultiCell($widthrecbox-5,4, $carac_client, 0, 'L');
			}
			else
			{
				// If BILLING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','BILLING');
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

				$carac_client=pdf_invoice_build_address($outputlangs,$this->emetteur,$object->client,($usecontact?$object->contact:''),$usecontact,'target');

				// Show recipient
				$posy=$logo_height+$this->marge_haute+2;
				$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;	
				if (! empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx=$this->marge_gauche;
				
				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('','', $default_font_size - 2);

				// Show billing or shipping address
				$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor);
				$pdf->SetXY($posx,$posy);
				if ($arrayidcontact=$object->getIdContact('external','BILLING')) 
				{			
					$pdf->MultiCell($widthrecbox,4, $outputlangs->transnoentities("BillAddress"), 0, 'R');
				}
				elseif ($arrayidcontact=$object->getIdContact('external','SHIPPING')) 
				{
					$pdf->MultiCell($widthrecbox,4, $outputlangs->transnoentities("DeliveryAddress"), 0, 'R');
				}				
				// Show recipient name
				$pdf->SetXY($posx+2,$posy+3);
				$pdf->SetFont('','B', $default_font_size);
				$pdf->MultiCell($widthrecbox-5, 4, $carac_client_name, 0, 'L');

				// Show recipient information
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->SetXY($posx+2,$posy+4+(dol_nboflines_bis($carac_client_name,50)*4));
				$pdf->MultiCell($widthrecbox-5, 4, $carac_client, 0, 'L');
				
				// If SHIPPING contact defined, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','SHIPPING');
				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}
				
				// Recipient name
				if (! empty($usecontact))
				{
					// On peut utiliser le nom de la societe du contact
					if ($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) $socname = $object->contact->socname;
					else $socname = $object->client->nom;
					$carac_client_name=$outputlangs->convToOutputCharset($socname);
				}
				else
				{
					$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);
				}

				$carac_client=pdf_invoice_build_address($outputlangs,$this->emetteur,$object->client,$object->contact,$usecontact,'target');

				// Show recipient
				$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
				if (! empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx=$this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('','', $default_font_size - 2);
				
				// Show shipping address
				$pdf->SetXY($posx,$posy-4);				
			}	
		
			// Other informations
	        $pdf->SetFillColor(255,255,255);

	        // Date facturation
			$width=$main_page/5 -1.5;
			$RoundedRectHeight = $this->marge_haute+$logo_height+$hautcadre+4;
			$pdf->RoundedRect($this->marge_gauche, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 1);
	        $pdf->SetXY($this->marge_gauche,$RoundedRectHeight);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 5, $outputlangs->transnoentities("DateInvoice"), 0, 'C', false);

	        $pdf->SetFont('','', $default_font_size - 1);
	        $pdf->SetXY($this->marge_gauche,$RoundedRectHeight+6);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 6, dol_print_date($object->date,"day",false,$outputlangs,true), 0, 'C');       

	        // DateEcheance
			$pdf->RoundedRect($this->marge_gauche+$width+2, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 1);
	        $pdf->SetXY($this->marge_gauche+$width+2,$RoundedRectHeight);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 5, $outputlangs->transnoentities("DateEcheance"), 0, 'C', false);
	       
	        $pdf->SetFont('','', $default_font_size - 1);
	        $pdf->SetXY($this->marge_gauche+$width+2,$RoundedRectHeight+6);
	        $pdf->SetTextColorArray($textcolor);
			$pdf->SetFillColor(255,255,255);
	        $pdf->MultiCell($width, 6, dol_print_date($object->date_lim_reglement,"day",false,$outputlangs,true), 0, 'C');

	        // Customer ref
			$pdf->RoundedRect($this->marge_gauche+$width*2+4, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 1);
	        $pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width+2, 5, $outputlangs->transnoentities("RefCustomer"), 0, 'C', false);
	        
			if ($object->ref_client)
			{
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight+6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $object->ref_client, '0', 'C');	
			}
			else
			{
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight+6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, 'NR', '0', 'C');	
			}
			
	        // Customer code
			$pdf->RoundedRect($this->marge_gauche+$width*3+6, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 1);
	        $pdf->SetXY($this->marge_gauche+$width*3+6,$RoundedRectHeight);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 5, $outputlangs->transnoentities("CustomerCode"), 0, 'C', false);

	        if ($object->client->code_client)
	        {
	        	$pdf->SetFont('','', $default_font_size - 1);
	        	$pdf->SetXY($this->marge_gauche+$width*3+6,$RoundedRectHeight+6);
	        	$pdf->SetTextColorArray($textcolor);
	        	$pdf->MultiCell($width, 6, $outputlangs->transnoentities($object->client->code_client), '0', 'C');
	        }
			else
			{
				$pdf->SetFont('','', $default_font_size - 1);
	        	$pdf->SetXY($this->marge_gauche+$width*3+6,$RoundedRectHeight+6);
	        	$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255,255,255);
				$pdf->MultiCell($width, 6, 'NR', '0', 'C');
			}

			// VAT intra 
			$pdf->RoundedRect($this->marge_gauche+$width*4+8, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor); 
		    $pdf->SetFont('','B', $default_font_size - 1);
			$pdf->SetXY($this->marge_gauche+$width*4+8,$RoundedRectHeight);  
			$pdf->SetTextColorArray($textcolor); 
			$pdf->MultiCell($width, 5, $outputlangs->transnoentities("VATIntra"), 0, 'C', false);

			if ($object->client->tva_intra) 
			{ 
				$pdf->SetFont('','', $default_font_size - 1); 
				$pdf->SetXY($this->marge_gauche+$width*4+8,$RoundedRectHeight+6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $outputlangs->transnoentities($object->client->tva_intra), '0', 'C'); 
			} 
			else 
			{ 
				$pdf->SetFont('','', $default_font_size - 1); 
				$pdf->SetXY($this->marge_gauche+$width*4+8,$RoundedRectHeight+6); 
				$pdf->SetTextColorArray($textcolor); 
				$pdf->SetFillColor(255,255,255); 
				$pdf->MultiCell($width, 6, 'NR', '0', 'C'); 					
			} 			        
		}
		$pdf->SetTextColorArray($textcolor);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		return pdf_ultimatepagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,1,$hidefreetext);
	}

	function _pagelcr(&$pdf, $object, $posy, $outputlangs,$hidefreetext=0)
	{
		global $conf,$langs;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		$outputlangs->load("ultimatepdf@ultimatepdf");

		$currency_code = $langs->getCurrencySymbol($conf->currency);

		$pdf->SetDrawColor(128,128,128);
		$style2 = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(162,162,162));
		$curx=$this->marge_gauche;
		$cury=$posy+30;
		//$pdf->SetFont('zapfdingbats','',20);
		//$pdf->SetXY(185, $cury-5.7);
		//$pdf->write(3,"!");
		$pdf->SetFont("Helvetica",'',7);
		$pdf->Line(0,$cury, 210, $cury);
		$cury+=3;
		$pdf->SetXY(90, $cury);
		$pdf->Cell(100, 3, $outputlangs->transnoentities('DocLCR1'), 0, 1, 'L', 0);
		$cury+=3;
		$pdf->SetXY(90, $cury);
		$pdf->Cell(100, 3, $outputlangs->transnoentities('DocLCR2'), 0, 1, 'L', 0);
		$cury+=3;
		$pdf->SetXY(90, $cury);
		$pdf->Cell(100, 3, $outputlangs->transnoentities('DocLCR3'), 0, 1, 'L', 0);
		$cury+=3;
		$pdf->SetXY(90, $cury);
		$pdf->Cell(100, 3, $outputlangs->transnoentities('DocLCR4'), 0, 0, 'L', 0);

		// Sender properties
		$pdf->SetFont('helvetica','B',8);
		$pdf->SetXY(130, $cury-5);
		$carac_emetteur = $outputlangs->convToOutputCharset($this->emetteur->name);
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->address);
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->zip).' '.$outputlangs->convToOutputCharset($this->emetteur->town);
		$carac_emetteur .= "\n";
		$pdf->MultiCell(50, 4, $carac_emetteur,0,C);

		//Affichage code monnaie
		$pdf->SetXY(180, $cury);
		$pdf->SetFont('helvetica','',7);
		$pdf->Cell(18, 0, $outputlangs->transnoentities('DocLCR5'),0,1,C);
		$pdf->SetXY(180, $cury+2.5);
		$pdf->SetFont('helvetica','B',14);
		$pdf->Cell(18, 0, $currency_code,0,0,C);

		//Affichage lieu / date
		$cury+=5;
		$pdf->SetXY(5, $cury);
		$pdf->SetFont('helvetica','',8);
		$pdf->Cell(2, 0, "A",0,1,C);
		$pdf->SetXY(20, $cury);
		$pdf->SetFont('helvetica','B',8);
		$pdf->Cell(15, 0, $outputlangs->convToOutputCharset($this->emetteur->town),0,1,C);
		$pdf->SetXY(45, $cury);
		$pdf->SetFont('helvetica','',8);
		$pdf->Cell(2, 0, ", le",0,1,C);

		// Row
		$curx=43;
		$largeur_cadre=5;
		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre+5, $cury, $style2);
		$pdf->Line($curx+$largeur_cadre+5, $cury, $curx+$largeur_cadre+5, $cury+2, $style2);
		$pdf->Line($curx+$largeur_cadre+4, $cury+2, $curx+$largeur_cadre+6, $cury+2, $style2);
		$pdf->Line($curx+$largeur_cadre+4, $cury+2, $curx+$largeur_cadre+5, $cury+3, $style2);
		$pdf->Line($curx+$largeur_cadre+6, $cury+2, $curx+$largeur_cadre+5, $cury+3, $style2);

		//Affichage toute la ligne qui commence par "montant pour controle" ...
		$curx=$this->marge_gauche;
		$cury+=5;
		$hauteur_cadre=6;
		$largeur_cadre=27;
		$pdf->SetXY($curx, $cury-1);
		$pdf->SetFont('helvetica','',7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR6'),0,0,C);
		$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->SetXY($curx, $cury+2.5);
		$pdf->SetFont('helvetica','B',8);
		$pdf->Cell($largeur_cadre, 0, price($object->total_ttc),0,0,C);

		$curx=$curx+$largeur_cadre+5;
		$hauteur_cadre=6;
		$largeur_cadre=25;
		$pdf->SetXY($curx, $cury-1);
		$pdf->SetFont('helvetica','',7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR7'),0,0,C);
		$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->SetXY($curx, $cury+2.5);
		$pdf->SetFont('helvetica','B',8);
		$pdf->Cell($largeur_cadre, 0, dol_print_date($object->date,"day",false,$outpulangs),0,0,C);

		$curx=$curx+$largeur_cadre+5;
		$hauteur_cadre=6;
		$largeur_cadre=25;
		$pdf->SetXY($curx, $cury-1);
		$pdf->SetFont('helvetica','',7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR8'),0,0,C);
		$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->SetXY($curx, $cury+2.5);
		$pdf->SetFont('helvetica','B',8);
		$pdf->Cell($largeur_cadre, 0, dol_print_date($object->date_lim_reglement,"day"),0,0,C);

		$curx=$curx+$largeur_cadre+5;
		$hauteur_cadre=6;
		$largeur_cadre=75;
		$pdf->SetXY($curx, $cury-1);
		$pdf->SetFont('helvetica','',7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR9'),0,0,C);

		$largeurportioncadre=30;
		$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeurportioncadre, $cury+$hauteur_cadre);
		$curx+=$largeurportioncadre;
		$pdf->Line($curx, $cury+2, $curx, $cury+$hauteur_cadre);

		$curx+=10;
		$largeurportioncadre=6;
		$pdf->Line($curx, $cury+2, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeurportioncadre, $cury+$hauteur_cadre);
		$curx+=$largeurportioncadre;
		$pdf->Line($curx, $cury+2, $curx, $cury+$hauteur_cadre);

		$curx+=3;
		$largeurportioncadre=6;
		$pdf->Line($curx, $cury+2, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeurportioncadre, $cury+$hauteur_cadre);
		$curx+=$largeurportioncadre;
		$pdf->Line($curx, $cury+2, $curx, $cury+$hauteur_cadre);

		$curx+=3;
		$largeurportioncadre=12;
		$pdf->Line($curx, $cury+2, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeurportioncadre, $cury+$hauteur_cadre);
		$curx+=$largeurportioncadre;
		$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);

		$curx+=3;
		$hauteur_cadre=6;
		$largeur_cadre=30;
		$pdf->SetXY($curx, $cury-1);
		$pdf->SetFont('helvetica','',7);
		$pdf->Cell($largeur_cadre, 0, $outputlangs->transnoentities('DocLCR10'),0,0,C);
		$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->SetXY($curx, $cury+2.5);
		$pdf->SetFont('helvetica','B',8);
		$pdf->Cell($largeur_cadre, 0, price($object->total_ttc),0,0,C);

		$cury=$cury+$hauteur_cadre+3;
		$curx=20;
		$hauteur_cadre=4;
		$largeur_cadre=70;
		$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury, $curx+$largeur_cadre/5, $cury);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeur_cadre/5, $cury+$hauteur_cadre);

		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre*4/5, $cury);
		$pdf->Line($curx+$largeur_cadre, $cury+$hauteur_cadre, $curx+$largeur_cadre*4/5, $cury+$hauteur_cadre);
		$pdf->SetXY($curx, $cury);
		$pdf->SetFont('helvetica','B',8);
		$pdf->Cell($largeur_cadre, 1, $outputlangs->convToOutputCharset($object->ref),0,0,C);

		$curx=$curx+$largeur_cadre+15;
		$largeur_cadre=50;
		$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury, $curx+$largeur_cadre/5, $cury);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeur_cadre/5, $cury+$hauteur_cadre);

		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre*4/5, $cury);
		$pdf->Line($curx+$largeur_cadre, $cury+$hauteur_cadre, $curx+$largeur_cadre*4/5, $cury+$hauteur_cadre);
		$pdf->SetXY($curx, $cury+2);
		$pdf->SetFont('helvetica','B',8);
		//$pdf->Cell($largeur_cadre, 0, "R�f ",0,0,C);

		$curx=$curx+$largeur_cadre+10;
		$largeur_cadre=30;
		$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);
		$pdf->Line($curx, $cury, $curx+$largeur_cadre/5, $cury);
		$pdf->Line($curx, $cury+$hauteur_cadre, $curx+$largeur_cadre/5, $cury+$hauteur_cadre);

		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre, $cury+$hauteur_cadre);
		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre*4/5, $cury);
		$pdf->Line($curx+$largeur_cadre, $cury+$hauteur_cadre, $curx+$largeur_cadre*4/5, $cury+$hauteur_cadre);
		$pdf->SetXY($curx, $cury+2);
		$pdf->SetFont('helvetica','B',8);

		// RIB client
		$cury=$cury+$hauteur_cadre+3;
		$largeur_cadre=70;
		$hauteur_cadre=6;
		$sql = "SELECT rib.fk_soc, rib.domiciliation, rib.code_banque, rib.code_guichet, rib.number, rib.cle_rib";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_rib as rib";
		$sql.= " WHERE rib.fk_soc = ".$object->client->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i <= $num)
			{
				$cpt = $this->db->fetch_object($resql);

				$curx=$this->marge_gauche;
				$pdf->Line($curx, $cury, $curx+$largeur_cadre, $cury);
				$pdf->Line($curx, $cury, $curx, $cury+$hauteur_cadre);
				$pdf->Line($curx+22, $cury, $curx+22, $cury+$hauteur_cadre-2);
				$pdf->Line($curx+35, $cury, $curx+35, $cury+$hauteur_cadre-2);
				$pdf->Line($curx+60, $cury, $curx+60, $cury+$hauteur_cadre-2);
				$pdf->Line($curx+70, $cury, $curx+70, $cury+$hauteur_cadre);
				$pdf->SetXY($curx+5, $cury+$hauteur_cadre-5);
				$pdf->SetFont('helvetica','B',8);
				if ($cpt->code_banque && $cpt->code_guichet && $cpt->number && $cpt->cle_rib)
					$pdf->Cell($largeur_cadre, 1, $cpt->code_banque."             ".$cpt->code_guichet."         ".$cpt->number."        ".$cpt->cle_rib,0,0,L);
				$pdf->SetXY($curx, $cury+$hauteur_cadre-1);
				$pdf->SetFont('helvetica','',6);
				$pdf->Cell($largeur_cadre, 1, $outputlangs->transnoentities('DocLCR11').'    '.    $outputlangs->transnoentities('DocLCR12').'        '.          $outputlangs->transnoentities('DocLCR13').'            '.            $outputlangs->transnoentities('DocLCR14'),0,0,L);
				$curx=150;
				$largeur_cadre=55;
				$pdf->SetXY($curx, $cury-1);
				$pdf->SetFont('helvetica','',6);
				$pdf->Cell($largeur_cadre, 1, $outputlangs->transnoentities('DocLCR15'),0,0,C);
				$pdf->SetXY($curx, $cury+2);
				$pdf->SetFont('helvetica','B',8);
				if ($cpt->domiciliation)
					$pdf->Cell($largeur_cadre, 5,$outputlangs->convToOutputCharset($cpt->domiciliation) ,1,0,C);
				$i++;
			}
		}
		//
		$cury=$cury+$hauteur_cadre+3;
		$curx=$this->marge_gauche;
		$largeur_cadre=20;
		$pdf->SetXY($curx, $cury);
		$pdf->SetFont('helvetica','',6);
		$pdf->Cell($largeur_cadre, 1, $outputlangs->transnoentities('DocLCR16'),0,0,L);
		// Row
		$pdf->Line($curx+$largeur_cadre, $cury, $curx+$largeur_cadre+5, $cury);
		$pdf->Line($curx+$largeur_cadre+5, $cury, $curx+$largeur_cadre+5, $cury+2);
		$pdf->Line($curx+$largeur_cadre+4, $cury+2, $curx+$largeur_cadre+6, $cury+2);
		$pdf->Line($curx+$largeur_cadre+4, $cury+2, $curx+$largeur_cadre+5, $cury+3);
		$pdf->Line($curx+$largeur_cadre+6, $cury+2, $curx+$largeur_cadre+5, $cury+3);

		//Coordonnees du tire
		$curx+=50;
		$largeur_cadre=20;
		$hauteur_cadre=4;
		$pdf->SetXY($curx, $cury);
		$pdf->SetFont('helvetica','',6);
		$pdf->MultiCell($largeur_cadre, $hauteur_cadre, "Nom \n et Adresse \n".$outputlangs->transnoentities('DocLCR17'),0,R);
		$pdf->SetXY($curx+$largeur_cadre+2, $cury);
		$pdf->SetFont('helvetica','B',8);
		$arrayidcontact = $object->getIdContact('external','BILLING');
		$carac_client=$outputlangs->convToOutputCharset($object->client->name);
		$carac_client.="\n".$outputlangs->convToOutputCharset($object->client->address);
		$carac_client.="\n".$outputlangs->convToOutputCharset($object->client->zip) . " " . $outputlangs->convToOutputCharset($object->client->town)."\n";
		$pdf->MultiCell($largeur_cadre*2.5, $hauteur_cadre, $carac_client,1,C);
		//No Siren
		$pdf->SetXY($curx, $cury+16);
		$pdf->SetFont('helvetica','',6);
		$pdf->MultiCell($largeur_cadre, 4, $outputlangs->transnoentities('DocLCR18'),0,R);
		$pdf->SetXY($curx+$largeur_cadre+2, $cury+16);
		$pdf->SetFont('helvetica','B',8);
		$pdf->MultiCell($largeur_cadre*2.5, 4, $outputlangs->convToOutputCharset($object->client->idprof1),1,C);
		//signature du tireur
		$pdf->SetXY($curx+$largeur_cadre*5, $cury);
		$pdf->SetFont('helvetica','',6);
		$pdf->MultiCell($largeur_cadre*2, 4, $outputlangs->transnoentities('DocLCR19'),0,C);

		$pdf->Line(0,$this->page_hauteur-$this->marge_basse,$this->page_largeur, $this->page_hauteur-$this->marge_basse);
		$pdf->SetXY($this->page_largeur-65,$this->page_hauteur-$this->marge_basse-3 );
		$pdf->SetFont('helvetica','',6);
		$pdf->MultiCell(50, 4, $outputlangs->transnoentities('DocLCR20'),0,R);

	}
}

?>