<?php
/* Copyright (C) 2011-2012 	Regis Houssin  	<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 	Philippe Grand 	<philippe.grand@atoo-net.com>
 * Copyright (C) 2012 		Juanjo Menent 	<jmenent@2byte.es>
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
 *	\file       htdocs/custom/core/modules/propale/pdf_ultimate_propal2.modules.php
 *  \ingroup    propale
 *  \brief      Fichier de la classe permettant de generer les propales au modele ultimate_propal2 
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
dol_include_once("/ultimatepdf/lib/ultimatepdf.lib.php");

/**
 *     \class      pdf_ultimate_propal2
 *     \brief      Classe permettant de generer les propales au modele ultimate_propal2
 */
class pdf_ultimate_propal2 extends ModelePDFPropales
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
		$langs->load("bills");
		$langs->load("ultimatepdf@ultimatepdf");

		$this->db = $db;
        $this->name = "ultimate_propal2";
        $this->description = $langs->trans('PDFUltimate_propal2Description');

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
		$this->option_credit_note = 1;             // Gere les avoirs
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
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($outputlangs->defaultlang,-2);    // By default, if was not defined

		// Defini position des colonnes
        if ($conf->global->ULTIMATE_DOCUMENTS_WITH_REF != "yes")
		{
			$this->posxdesc=$this->marge_gauche+1;
		}
		else
		{			
			$this->posxref=$this->marge_gauche+1;
			$this->posxdesc=$this->marge_gauche + (isset($conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH)?$conf->global->ULTIMATE_DOCUMENTS_WITH_REF_WIDTH:22);		
		}
        $this->posxtva=109;
		$this->posxup=119;
		$this->posxdiscount=136;
		$this->posxupafter=148;
		$this->posxqty=165;
		$this->postotalht=178;
		if (! ($conf->global->ULTIMATE_GENERATE_DOCUMENTS_WITHOUT_VAT == "no" && empty($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN))) $this->posxtva=$this->posxup;
		$this->posxpicture=$this->posxtva-1 - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?16:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH);	// width of images
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxdesc-=20;
			$this->posxpicture-=20;
			$this->posxtva-=20;
			$this->posxup-=20;
			$this->posxdiscount-=20;
			$this->posxupafter-=20;
			$this->posxqty-=20;		
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
		$outputlangs->load("propal");
		$outputlangs->load("products");
		$outputlangs->load("ultimatepdf@ultimatepdf");
		
		$nblignes = count($object->lines);
		
		// Loop on each lines to detect if there is at least one image to show
		$realpatharray=array();
		if (! empty($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_PICTURE))
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
					//if ($obj['photo_vignette']) $filename='thumbs/'.$obj['photo_vignette'];
					$realpath = $dir.$filename;
					break;
				}

				if ($realpath) $realpatharray[$i]=$realpath;
			}
		}
		if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

        if ($conf->propal->dir_output)
        {
        	$object->fetch_thirdparty();

			// Definition de $dir et $file
			if ($object->specimen)
			{
				$dir = $conf->propal->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->propal->dir_output . "/" . $objectref;
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

				// Create pdf instance
                $pdf=pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				if (! empty($conf->global->MAIN_DISPLAY_PROPOSAL_AGREEMENT_BLOCK))
				{
					$heightforinfotot = 60;	// Height reserved to output the info and total part
				}
				else
				{
					$heightforinfotot = 40;
				}
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
				$pdf->SetSubject($outputlangs->transnoentities("CommercialProposal"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("CommercialProposal"));
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
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    $curY = $nexY;
                    $pdf->SetFont('','', $default_font_size - 2);   // Dans boucle pour gerer multi-page
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
						$pdf->setPage($pageposbefore+1);

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

					// We suppose that a too long description or photo were moved completely on next page
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
						$pdf->SetXY($this->posxup, $curY);
						$pdf->MultiCell($this->posxdiscount-$this->posxup-0.8, 3, $up_excl_tax, 0, 'C', 0);
					}
					
					// Discount on line                	
					if ($object->lines[$i]->remise_percent)
					{
						$pdf->SetXY($this->posxdiscount-2, $curY);
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->posxupafter-$this->posxdiscount+2, 3, $remise_percent, 0, 'C');
					}
					
					// Prix unitaire HT apres remise
					$string = $langs->trans("Offered");
					if ($remise_percent == $string)
					{
						$up_after = price(0);
						$pdf->SetXY ($this->posxupafter-5, $curY);
						$pdf->MultiCell($this->posxqty-$this->posxupafter+4, 4, $up_after, 0, 'C');
					}
					else
					{
						if ($object->lines[$i]->remise_percent > 0)
						{
							$up_after = price2num((float)(100 - $remise_percent) * price2num($up_excl_tax) / (float)100, 'MU');
							$pdf->SetXY ($this->posxupafter-5, $curY);
							$pdf->MultiCell($this->posxqty-$this->posxupafter+4, 4, price($up_after), 0, 'C');
						}
					}

					// Quantity
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_QTY))
					{
						$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxqty, $curY);
						$pdf->MultiCell($this->postotalht-$this->posxqty-0.8, 3, $qty, 0, 'C');	
					}

                    // Total HT ligne
					if (empty($conf->global->ULTIMATE_SHOW_HIDE_THT))
					{
						$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->postotalht, $curY);
						$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalht, 3, $total_excl_tax, 0, 'C', 0);
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
					if (! isset($this->tva[$vatrate]))				$this->tva[$vatrate]='';
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
				$posy=$this->_tableau_tot($pdf, $object, 0, $bottomlasttab, $outputlangs);

                // Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();
				
				// Add PDF ask to merge
				dol_include_once ( '/ultimatepdf/class/propalmergedpdf.class.php' );
				
				$already_merged=array();
				foreach ( $object->lines as $line ) {
					if (! empty ( $line->fk_propal ) && !(in_array($line->fk_propal, $already_merged))) {
						// Find the desire PDF
						$filetomerge = new Propalmergedpdf ( $this->db );
						
						if ($conf->global->MAIN_MULTILANGS) {
							$filetomerge->fetch_by_propal ( $line->fk_propal, $outputlangs->defaultlang);
						} else {
							$filetomerge->fetch_by_propal ( $line->fk_propal );
						}
						
						
						$already_merged[]=$line->fk_propal;
						
						// If PDF is selected and file is not empty
						if (count ( $filetomerge->lines ) > 0) {
							foreach ( $filetomerge->lines as $linefile ) {
								
								if (! empty ( $linefile->id ) && ! empty ( $linefile->file_name )) {
									
									if (! empty ( $conf->propal->enabled ))
										$filetomerge_dir = $conf->propal->dir_output. '/' . dol_sanitizeFileName ( $object->ref );
									
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
				}
				
                $pdf->Close();

				$pdf->Output($file,'F');

				//Add pdfgeneration hook
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
                $this->error=$outputlangs->trans("ErrorCanNotCreateDir",$dir);
                return 0;
            }
        }
        else
        {
            $this->error=$outputlangs->trans("ErrorConstantNotDefined","PROP_OUTPUTDIR");
            return 0;
        }

        $this->error=$outputlangs->trans("ErrorUnknown");
        return 0;   // Erreur par defaut
    }

	/**
	 *  Show payments table
     *  @param      pdf             Object PDF
     *  @param      object          Object invoice
     *  @param      posy            Position y in PDF
     *  @param      outputlangs     Object langs for output
     *  @return     int             <0 if KO, >0 if OK
	 */
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{

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
		$outputlangs->load("ultimatepdf@ultimatepdf");
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		$pdf->SetFont('','', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1)
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		$widthrecbox=($this->page_largeur-$this->marge_gauche-$this->marge_droite-4)/2;
		
		// Show availability conditions
		if ($object->availability_code || $object->availability)    // Show availability conditions
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetTextColorArray($textcolor);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("AvailabilityPeriod").' : ';
			$pdf->MultiCell($widthrecbox, 4, $titre, 0, 'L');			
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche+mb_strlen($titre)+5, $posy);
			$lib_availability=$outputlangs->transnoentities("AvailabilityType".$object->availability_code)!=('AvailabilityType'.$object->availability_code)?$outputlangs->transnoentities("AvailabilityType".$object->availability_code):$outputlangs->convToOutputCharset($object->availability);
			$lib_availability=str_replace('\n',"\n",$lib_availability);
			$pdf->MultiCell($widthrecbox, 4, $lib_availability, 0, 'L');

			$posy=$pdf->GetY()+1;
		}

		// Show payments conditions

		if (empty($conf->global->PROPALE_PDF_HIDE_PAYMENTTERMCOND) && ($object->cond_reglement_code || $object->cond_reglement))
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("PaymentConditions").' : ';
			$pdf->MultiCell($widthrecbox, 4, $titre, 0, 'L');
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche+mb_strlen($titre)+10, $posy);
			$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
			$pdf->MultiCell($widthrecbox, 4, $lib_condition_paiement, 0, 'L');

			$posy=$pdf->GetY()+3;
		}
		
		$pdf->SetFont('','B', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche, $posy);

		if (empty($conf->global->PROPALE_PDF_HIDE_PAYMENTTERMCOND))
		{
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
				$pdf->SetXY($this->marge_gauche+mb_strlen($titre)+10, $posy);
				$lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell($widthrecbox, 5, $lib_mode_reg,0,'L');

				$posy=$pdf->GetY()+2;
			}
			
			// Auto-liquidation régime de la sous-traitance
			if (! empty($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_AUTO_LIQUIDATION))
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
						$pdf->MultiCell($widthrecbox, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio).':',0,'L',0);
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

					$posy=pdf_ultimate_proposals_bank($pdf,$outputlangs,$curx,$cury,$account,0,$default_font_size);
				}
			}
		}
		
		if (! empty($conf->global->MAIN_DISPLAY_PROPOSAL_AGREEMENT_BLOCK))
	    {
			$heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
			$heightforfooter = $this->marge_basse + 12;	// Height reserved to output the footer (value include bottom margin)
			$heightforinfotot = 60;	// Height reserved to output the info and total part
			$deltay=$this->page_hauteur-$heightforfreetext-$heightforfooter-$heightforinfotot/2;	
			$cury=max($cury,$deltay);
			$deltax=$this->marge_gauche+$widthrecbox+4;
			$pdf->RoundedRect($deltax, $cury, $widthrecbox, 40, 2, $round_corner = '1111', 'S', $this->style, '');
			$pdf->SetFont('','B', $default_font_size - 1);
			$pdf->SetXY($deltax, $cury);
			$titre = $outputlangs->transnoentities('DocORDER1'); 
			$pdf->MultiCell(80, 5, $titre, 0, 'L',0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($deltax, $cury+5);
			$pdf->SetFont('','I', $default_font_size - 2);
			$pdf->MultiCell(90, 3, $outputlangs->transnoentities('DocORDER2'),0,'L',0);
			$pdf->SetXY($deltax, $cury+12);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER3'), 0, 'L', 0);
			$pdf->SetXY($deltax, $cury+17);
			$pdf->SetFont('','I', $default_font_size - 2);
			$pdf->MultiCell(80, 3, $outputlangs->transnoentities('DocORDER4'), 0, 'L', 0);

			return $posy;
		}
	}

	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			&$pdf           Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf,$mysoc,$langs;
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
		
		$index = 0;
		$useborder=0;

		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY ($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

		$pdf->SetXY ($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ht + (! empty($object->remise)?$object->remise:0),0,$outputlangs,0,-1,-1,$currency_code), 0, 'R', 1);

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
						if ($tvakey != 0)    // On affiche pas taux 0
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

				// Total TTC			
				$index++;
				$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColorArray($textcolor);
				$pdf->SetFont('','B',$default_font_size );
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

				$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl,  price($object->total_ttc, 0, $outputlangs,0,-1,-1,$currency_code), $useborder, 'R', 1);										
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
		
		if ($deja_regle > 0)
		{
			// Already paid + Deposits
			$index++;

			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid"), 0, 'L', 0);

			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle, 0, $outputlangs,0,-1,-1,$currency_code), 0, 'R', 0);

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
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);

		// Amount in (at tab_top - 1)
        $pdf->SetFillColorArray($bgcolor);
		$pdf->SetTextColorArray($textcolor);
        $pdf->SetFont('','', $default_font_size - 1);

		// Output RoundedRect
		$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, 2, $round_corner = '0110','S', $this->style, '');
		
        // line prend une position y en 3eme param
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
				$pdf->MultiCell(112,6, $outputlangs->transnoentities("Designation"),0,'L',1);
			}
		}
		
		if (!empty($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_PICTURE))
		{
			$pdf->line($this->posxpicture-1, $tab_top, $this->posxpicture-1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				//
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
			$pdf->MultiCell($this->posxdiscount-$this->posxup+2,6, $outputlangs->transnoentities("PriceUHT"), 0, 'C', 1);
		}
		
		$pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY ($this->posxdiscount-1, $tab_top);
			$pdf->MultiCell($this->posxupafter-$this->posxdiscount+1,6, $outputlangs->transnoentities("Discount"), 0, 'C', 1);
		}
		
		$pdf->line($this->posxupafter-1, $tab_top, $this->posxupafter-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY ($this->posxupafter-1, $tab_top);
			$pdf->MultiCell($this->posxqty-$this->posxupafter,6, $outputlangs->transnoentities("PuAfter"), 0, 'C', 1);
		}

        $pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY ($this->posxqty-1, $tab_top);
			$pdf->MultiCell($this->postotalht-$this->posxqty+1,6, $outputlangs->transnoentities("Qty"), 0, 'C', 1);
		}        
			
		$pdf->line($this->postotalht-1, $tab_top, $this->postotalht-1, $tab_top + $tab_height);
        if (empty($hidetop))
        {
        	$pdf->SetXY ($this->postotalht-16, $tab_top);
        	$pdf->MultiCell($this->page_largeur-$this->postotalht+6,6, $outputlangs->transnoentities("AmountHT"), 0, 'R', 1);
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
		$outputlangs->load("commercial");
        $outputlangs->load("deliveries");
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$textcolor =  html2rgb($conf->global->ULTIMATE_TEXTCOLOR_COLOR);
		$qrcodecolor =  html2rgb($conf->global->ULTIMATE_QRCODECOLOR_COLOR);
		$main_page = $this->page_largeur-$this->marge_gauche-$this->marge_droite;
		
		//affiche repere de pliage	
		if (! empty($conf->global->MAIN_DISPLAY_PROPOSALS_FOLD_MARK))
		{
			$pdf->Line(0,($this->page_hauteur)/3,3,($this->page_hauteur)/3);
		}

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);
		
		//  Show Draft Watermark
		if($object->statut==0 && (! empty($conf->global->PROPALE_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->PROPALE_DRAFT_WATERMARK);
		}

		//Prepare la suite
        $pdf->SetTextColorArray($textcolor);
        $pdf->SetFont('','B', $default_font_size - 1);

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
		if (! empty($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_BARCODE))
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
		// set style for barcode
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
		if (! empty($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_QRCODE))
		{
			$code = pdf_codeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}
		// My Company QR-code
		if (! empty($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MYCOMP_QRCODE))
		{
			$code = pdf_mycompCodeContents();
			$pdf->write2DBarcode($code, 'QRCODE,L', $posxQRcode, $posy, $heightQRcode, $heightQRcode, $styleQr, 'N');
		}

		$pdf->SetFont('','B',$default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColorArray($textcolor);
		$title=$outputlangs->transnoentities("CommercialProposal");
		$pdf->MultiCell(100, 4, $title, '' , 'R');

		$pdf->SetFont('','B',$default_font_size + 2);

		$posy+=6;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy+=5;
		$pdf->SetFont('','', $default_font_size - 1);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColorArray($textcolor);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("DateEndPropal")." : " . dol_print_date($object->fin_validite,"day",false,$outputlangs,true), '', 'R');
		
		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

        if ($showaddress)
        {
			// Customer and Sender properties
			$bgcolor = html2rgb($conf->global->ULTIMATE_BGCOLOR_COLOR);
			
			// Sender properties
			$carac_emetteur='';
		 	// Add internal contact of proposal if defined
			$arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
		 	if (count($arrayidcontact) > 0)
		 	{
		 		$object->fetch_user($arrayidcontact[0]);
		 		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
		 	}

		 	$carac_emetteur .= pdf_build_address($outputlangs,$this->emetteur);
			
			// Show sender
			$posy=$logo_height+$this->marge_haute+2;
			$posx=$this->marge_gauche;
			$hautcadre=40;
			$widthrecbox=($this->page_largeur-$this->marge_gauche-$this->marge_droite-4)/2;
			if (! empty($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT) && ($conf->global->ULTIMATE_INVERT_SENDER_RECIPIENT != "no")) $posx=$this->page_largeur-$this->marge_droite-$widthrecbox;  

			// Show sender frame
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->SetFont('','', $default_font_size - 1);
			$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, 2, $round_corner = '1111', 'F', '', $bgcolor );

			// Show sender name
	        $pdf->SetXY($posx+2,$posy+3);
	        $pdf->SetFont('','B', $default_font_size - 1);
	        $pdf->MultiCell($widthrecbox-5,4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->getY();

	        // Show sender information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy);
			$pdf->MultiCell($widthrecbox-5,4, $carac_emetteur, 0, 'L');

        	if ($arrayidcontact=$object->getIdContact('external','BILLING') && $object->getIdContact('external','CUSTOMER'))
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

				$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,($usecontact?$object->contact:''),$usecontact,'target');

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

				// If CUSTOMER contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','CUSTOMER');

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
				$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,($usecontact?$object->contact:''),$usecontact,'target');

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
				// If CUSTOMER contact defined, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','CUSTOMER');
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

				$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,($usecontact?$object->contact:''),$usecontact,'target');

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
				elseif ($arrayidcontact=$object->getIdContact('external','CUSTOMER')) 
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
				
				// If CUSTOMER contact defined, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','CUSTOMER');
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

				$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,$object->contact,$usecontact,'target');

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

	        // Date proposition
			$width=$main_page/5 -1.5;
			$RoundedRectHeight = $this->marge_haute+$logo_height+$hautcadre+3;
			$pdf->RoundedRect($this->marge_gauche, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 2);
	        $pdf->SetXY($this->marge_gauche,$RoundedRectHeight);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 5, $outputlangs->transnoentities("Date proposition"), 0, 'C', false);

	        $pdf->SetFont('','', $default_font_size - 2);
	        $pdf->SetXY($this->marge_gauche,$RoundedRectHeight+6);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 6, dol_print_date($object->date,"day",false,$outputlangs,true), '0', 'C');	       

	        // Delivery date
			$pdf->RoundedRect($this->marge_gauche+$width+2, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 2);
	        $pdf->SetXY($this->marge_gauche+$width+2,$RoundedRectHeight);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 5, $outputlangs->transnoentities("DeliveryDate"), 0, 'C', false);

	        if ($object->date_livraison)
	        {
	        	$pdf->SetFont('','', $default_font_size - 2);
	        	$pdf->SetXY($this->marge_gauche+$width+2,$RoundedRectHeight+6);
	        	$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255,255,255);
	        	$pdf->MultiCell($width, 6, dol_print_date($object->date_livraison,"day",false,$outputlangs,true), '0', 'C');
	        }
			else
			{
				$pdf->SetFont('','', $default_font_size - 2);
	        	$pdf->SetXY($this->marge_gauche+$width+2,$RoundedRectHeight+6);
	        	$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255,255,255);
				$pdf->MultiCell($width, 6, 'NR', '0', 'C');
			}

	        // Commercial Interlocutor
			$pdf->RoundedRect($this->marge_gauche+$width*2+4, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 2);
	        $pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 5, $outputlangs->transnoentities("Contact"), 0, 'C', false);

	        $contact_id = $object->getIdContact('internal','SALESREPFOLL');

	        if (! empty($contact_id))
	        {
	        	$object->fetch_user($contact_id[0]);
	        	$pdf->SetFont('','', $default_font_size - 2);
	        	$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight+6);
	        	$pdf->SetTextColorArray($textcolor);
	        	$pdf->MultiCell($width, 7, $object->user->firstname.' '.$object->user->lastname, '0', 'C');
				$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight+9);
	        	$pdf->SetTextColorArray($textcolor);
	        	$pdf->MultiCell($width, 7, $object->user->office_phone, '0', 'C');
	        }		
	        else if ($object->user_author_id)
	        {
	        	$object->fetch_user($object->user_author_id);
	        	$pdf->SetFont('','', $default_font_size - 2);
	        	$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight+6);
	        	$pdf->SetTextColorArray($textcolor);
	        	$pdf->MultiCell($width, 7, $object->user->firstname.' '.$object->user->lastname, '0', 'C');
				$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight+9);
	        	$pdf->SetTextColorArray($textcolor);
	        	$pdf->MultiCell($width, 7, $object->user->office_phone, '0', 'C');
	        }
			else
			{
				$pdf->SetFont('','', $default_font_size - 2);
	        	$pdf->SetXY($this->marge_gauche+$width*2+4,$RoundedRectHeight+6);
	        	$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255,255,255);
				$pdf->MultiCell($width, 6, 'NR', '0', 'C');
			}

	        // Customer code
			$pdf->RoundedRect($this->marge_gauche+$width*3+6, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 2);
	        $pdf->SetXY($this->marge_gauche+$width*3+6,$RoundedRectHeight);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 5, $outputlangs->transnoentities("CustomerCode"), 0, 'C', false);

	        if ($object->client->code_client)
	        {
	        	$pdf->SetFont('','', $default_font_size - 2);
	        	$pdf->SetXY($this->marge_gauche+$width*3+6,$RoundedRectHeight+6);
	        	$pdf->SetTextColorArray($textcolor);
	        	$pdf->MultiCell($width, 6, $outputlangs->transnoentities($object->client->code_client), '0', 'C');
	        }
			else
			{
				$pdf->SetFont('','', $default_font_size - 2);
	        	$pdf->SetXY($this->marge_gauche+$width*3+6,$RoundedRectHeight+6);
	        	$pdf->SetTextColorArray($textcolor);
				$pdf->SetFillColor(255,255,255);
				$pdf->MultiCell($width, 6, 'NR', '0', 'C');
			}
			
			// Customer ref
			$pdf->RoundedRect($this->marge_gauche+$width*4+8, $RoundedRectHeight, $width, 6, 2, $round_corner = '1001', 'F', $this->style, $bgcolor);
	        $pdf->SetFont('','B', $default_font_size - 2);
	        $pdf->SetXY($this->marge_gauche+$width*4+8,$RoundedRectHeight);
	        $pdf->SetTextColorArray($textcolor);
	        $pdf->MultiCell($width, 5, $outputlangs->transnoentities("RefCustomer"), 0, 'C', false);
	        
			if ($object->ref_client)
			{
				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche+$width*4+8,$RoundedRectHeight+6);
				$pdf->SetTextColorArray($textcolor);
				$pdf->MultiCell($width, 6, $object->ref_client, '0', 'C');	
			}
			else
			{
				$pdf->SetFont('','', $default_font_size - 2);
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
		return pdf_ultimatepagefoot($pdf,$outputlangs,'PROPALE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,1,$hidefreetext);
	}

}

?>