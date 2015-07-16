<?php
/* Copyright (C) 2013-2014 Philippe Grand  <philippe.grand@atoo-net.com>
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
 *	    \file       /ultimatepdf/lib/ultimatepdf.lib.php
 *		\brief      Ensemble de fonctions de base pour le module ultimatepdf
 *      \ingroup    ultimatepdf
 */

function html2rgb($color)
{
	if ($color[0] == '#')
	{
		$color = substr($color, 1);
	}
	
	if (strlen($color) == 6)
	{
		list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
	}
	elseif (strlen($color) == 3)
	{
		list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1],   $color[2].$color[2]);
	}
	
	$r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
	
	return array($r, $g, $b);
} 

function ultimatepdf_prepare_head()
{
	global $langs, $conf;
	$langs->load("bills");
	$langs->load("orders");
	$langs->load("propal");
	$langs->load("sendings");
	$langs->load('ultimatepdf@ultimatepdf');

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/ultimatepdf.php",1);
	$head[$h][1] = $langs->trans("UltimatepdfDesigns");
	$head[$h][2] = 'designs';
	$h++;

	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/options.php",1);
	$head[$h][1] = $langs->trans("Options");
	$head[$h][2] = 'options';
	$h++;
	
	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/proposals.php",1);
	$head[$h][1] = $langs->trans("Proposals");
	$head[$h][2] = 'proposals';
	$h++;
	
	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/orders.php",1);
	$head[$h][1] = $langs->trans("Orders");
	$head[$h][2] = 'orders';
	$h++;
	
	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/invoices.php",1);
	$head[$h][1] = $langs->trans("Invoices");
	$head[$h][2] = 'invoices';
	$h++;
	
	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/shipments.php",1);
	$head[$h][1] = $langs->trans("Shipments");
	$head[$h][2] = 'shipments';
	$h++;
	
	$head[$h][0] = dol_buildpath("/ultimatepdf/admin/about.php",1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'ultimatepdf');

	return $head;
}


/**
 *	Output line description into PDF
 *
 *  @param  PDF				&$pdf               PDF object
 *	@param	Object			$object				Object
 *	@param	int				$i					Current line number
 *  @param  Translate		$outputlangs		Object lang for output
 *  @param  int				$w					Width
 *  @param  int				$h					Height
 *  @param  int				$posx				Pos x
 *  @param  int				$posy				Pos y
 *  @param  int				$hideref       		Hide reference
 *  @param  int				$hidedesc            Hide description
 * 	@param	int				$issupplierline		Is it a line for a supplier object ?
 * 	@param	string			$type				ref or label
 * 	@return	void
 */
function pdf_writelinedesc_ref(&$pdf,$object,$i,$outputlangs,$w,$h,$posx,$posy,$hideref=0,$hidedesc=0,$issupplierline=0,$type='')
{
	global $db, $conf, $langs, $hookmanager;
	
	$reshook=0;
	if (is_object($hookmanager) && ( ($object->lines[$i]->product_type == 9 && ! empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		$parameters = array('pdf'=>$pdf,'i'=>$i,'outputlangs'=>$outputlangs,'w'=>$w,'h'=>$h,'posx'=>$posx,'posy'=>$posy,'hideref'=>$hideref,'hidedesc'=>$hidedesc,'issupplierline'=>$issupplierline,'special_code'=>$special_code,'type'=>$type);
		$action='';
		$reshook=$hookmanager->executeHooks('pdf_writelinedesc_ref',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	}
	if (empty($reshook))
	{
		$labelproductservice=pdf_getlinedesc_ref($object,$i,$outputlangs,$hideref,$hidedesc,$issupplierline,$type);
		// Description
		if ($type=='ref') {
			$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 0, false, true, 'J',true);
		} else {
			$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'J',true);
		}
		return $labelproductservice;
	}
}

/**
 *  Return line description translated in outputlangs and encoded into htmlentities and with <br>
 *
 *  @param  Object		$object              Object
 *  @param  int			$i                   Current line number (0 = first line, 1 = second line, ...)
 *  @param  Translate	$outputlangs         Object langs for output
 *  @param  int			$hideref             Hide reference
 *  @param  int			$hidedesc            Hide description
 *  @param  int			$issupplierline      Is it a line for a supplier object ?
 * 	@param	string		$type			 	 ref or label
 *  @return string       				     String with line
 */
function pdf_getlinedesc_ref($object,$i,$outputlangs,$hideref=0,$hidedesc=0,$issupplierline=0,$type='')
{
	global $db, $conf, $langs;

	$idprod=(! empty($object->lines[$i]->fk_product)?$object->lines[$i]->fk_product:false);
	$label=(! empty($object->lines[$i]->label)?$object->lines[$i]->label:(! empty($object->lines[$i]->product_label)?$object->lines[$i]->product_label:''));
	$desc=(! empty($object->lines[$i]->desc)?$object->lines[$i]->desc:(! empty($object->lines[$i]->description)?$object->lines[$i]->description:''));
	$ref_supplier=(! empty($object->lines[$i]->ref_supplier)?$object->lines[$i]->ref_supplier:(! empty($object->lines[$i]->ref_fourn)?$object->lines[$i]->ref_fourn:''));    // TODO Not yet saved for supplier invoices, only supplier orders
	$note=(! empty($object->lines[$i]->note)?$object->lines[$i]->note:'');

	if ($issupplierline) $prodser = new ProductFournisseur($db);
	else $prodser = new Product($db);

	if ($idprod)
	{
		$prodser->fetch($idprod);
		// If a predefined product and multilang and on other lang, we renamed label with label translated
		if ($conf->global->MAIN_MULTILANGS && ($outputlangs->defaultlang != $langs->defaultlang))
		{
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["label"]) && $label == $prodser->label)     $label=$prodser->multilangs[$outputlangs->defaultlang]["label"];
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["description"]) && $desc == $prodser->description) $desc=$prodser->multilangs[$outputlangs->defaultlang]["description"];
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["note"]) && $note == $prodser->note)        $note=$prodser->multilangs[$outputlangs->defaultlang]["note"];
		}
	}
	// Description short of product line
	$libelleproduitservice=$label;

	// Description long of product line
	if ($desc && ($desc != $label))
	{
		if ($libelleproduitservice && empty($hidedesc))
		{
			$libelleproduitservice.='__N__';
		}

		if ($desc == '(CREDIT_NOTE)' && $object->lines[$i]->fk_remise_except)
		{
			$discount=new DiscountAbsolute($db);
			$discount->fetch($object->lines[$i]->fk_remise_except);
			$libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromCreditNote",$discount->ref_facture_source);
		}
		elseif ($desc == '(DEPOSIT)' && $object->lines[$i]->fk_remise_except)
		{
			$discount=new DiscountAbsolute($db);
			$discount->fetch($object->lines[$i]->fk_remise_except);
			$libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromDeposit",$discount->ref_facture_source);
			// Add date of deposit
			if (! empty($conf->global->INVOICE_ADD_DEPOSIT_DATE)) echo ' ('.dol_print_date($discount->datec,'day','',$outputlangs).')';
		}
		else
		{
			if ($idprod)
			{
				if (empty($hidedesc)) $libelleproduitservice.=$desc;
			}
			else
			{
				$libelleproduitservice.=$desc;
			}
		}
	}

	// If line linked to a product
	if ($idprod)
	{
		// On ajoute la ref
		if ($prodser->ref)
		{
			$prefix_prodserv = "";
			$ref_prodserv = "";
			if ($conf->global->PRODUCT_ADD_TYPE_IN_DOCUMENTS)   // In standard mode, we do not show this
			{
				if ($prodser->isservice())
				{
					$prefix_prodserv = $outputlangs->transnoentitiesnoconv("Service")." ";
				}
				else
				{
					$prefix_prodserv = $outputlangs->transnoentitiesnoconv("Product")." ";
				}
			}

			if (empty($hideref))
			{
				if ($issupplierline) $ref_prodserv = $prodser->ref.' ('.$outputlangs->transnoentitiesnoconv("SupplierRef").' '.$ref_supplier.')';   // Show local ref and supplier ref
				else $ref_prodserv = $prodser->ref; // Show local ref only
			}

			if ($type=='ref') {
				$libelleproduitservice=$ref_prodserv;
			}
			elseif ($type=='label') {
				
				if ($issupplierline) $libelleproduitservice=$prefix_prodserv.$libelleproduitservice.' ('.$outputlangs->transnoentitiesnoconv("SupplierRef").' : '.$ref_supplier.')';
			}else {
				$libelleproduitservice=$prefix_prodserv.$ref_prodserv.' - '.$libelleproduitservice;
			}			
		}
	}
	
	// Add an additional description for the category products
	if (! empty($conf->global->CATEGORY_ADD_DESC_INTO_DOC) && $idprod && ! empty($conf->categorie->enabled))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$categstatic=new Categorie($db);
		// recovering the list of all the categories linked to product
		$tblcateg=$categstatic->containing($idprod,0);
		foreach ($tblcateg as $cate)
		{
			// Adding the descriptions if they are filled
			$desccateg=$cate->add_description;
			if ($desccateg)
				$libelleproduitservice.='__N__'.$desccateg;
		}
	}

	if (! empty($object->lines[$i]->date_start) || ! empty($object->lines[$i]->date_end))
	{
		$format='day';
		// Show duration if exists
		if ($object->lines[$i]->date_start && $object->lines[$i]->date_end)
		{
			$period='('.$outputlangs->transnoentitiesnoconv('DateFromTo',dol_print_date($object->lines[$i]->date_start, $format, false, $outputlangs),dol_print_date($object->lines[$i]->date_end, $format, false, $outputlangs)).')';
		}
		if ($object->lines[$i]->date_start && ! $object->lines[$i]->date_end)
		{
			$period='('.$outputlangs->transnoentitiesnoconv('DateFrom',dol_print_date($object->lines[$i]->date_start, $format, false, $outputlangs)).')';
		}
		if (! $object->lines[$i]->date_start && $object->lines[$i]->date_end)
		{
			$period='('.$outputlangs->transnoentitiesnoconv('DateUntil',dol_print_date($object->lines[$i]->date_end, $format, false, $outputlangs)).')';
		}
		//print '>'.$outputlangs->charset_output.','.$period;
		$libelleproduitservice.="__N__".$period;
		//print $libelleproduitservice;
	}

	// Now we convert \n into br
	if (dol_textishtml($libelleproduitservice)) $libelleproduitservice=preg_replace('/__N__/','<br>',$libelleproduitservice);
	else $libelleproduitservice=preg_replace('/__N__/',"\n",$libelleproduitservice);
	$libelleproduitservice=dol_htmlentitiesbr($libelleproduitservice,1);

	return $libelleproduitservice;
}

/**
 *  Show bank informations for PDF generation
 *
 *  @param	PDF			&$pdf            		Object PDF
 *  @param  Translate	$outputlangs     		Object lang
 *  @param  int			$curx            		X
 *  @param  int			$cury            		Y
 *  @param  Account		$account         		Bank account object
 *  @param  int			$onlynumber      		Output only number
 *  @param	int			$default_font_size		Default font size
 *  @return	void
 */
function pdf_ultimate_orders_bank(&$pdf,$outputlangs,$curx,$cury,$account,$onlynumber=0,$default_font_size=10)
{
	global $mysoc, $conf;

	$diffsizetitle=(empty($conf->global->PDF_DIFFSIZE_TITLE)?3:$conf->global->PDF_DIFFSIZE_TITLE);
	$diffsizecontent=(empty($conf->global->PDF_DIFFSIZE_CONTENT)?4:$conf->global->PDF_DIFFSIZE_CONTENT);

	$pdf->SetXY($curx, $cury);

	if (empty($onlynumber))
	{
		$pdf->SetFont('','B',$default_font_size - $diffsizetitle);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
		$cury+=4;
	}

	$outputlangs->load("banks");

	// Get format of bank account according to its country
	$usedetailedbban=$account->useDetailedBBAN();

	//$onlynumber=0; $usedetailedbban=0; // For tests
	if (!$conf->global->ULTIMATE_BANK_HIDE_DETAILS_WITHIN_ORDERS)
	{
		if ($usedetailedbban)
		{
			$savcurx=$curx;

			if (empty($onlynumber))
			{
				$pdf->SetFont('','',$default_font_size - $diffsizecontent);
				$pdf->SetXY($curx, $cury);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
				$cury+=3;
			}
		
			if (empty($onlynumber)) $pdf->line($curx+1, $cury+1, $curx+1, $cury+8);

			if ($usedetailedbban == 1)
			{
				$fieldstoshow=array('bank','desk','number','key');
				if ($conf->global->BANK_SHOW_ORDER_OPTION==1) $fieldstoshow=array('bank','desk','key','number');
			}
			else if ($usedetailedbban == 2)
			{
				$fieldstoshow=array('bank','number');
			}
			else dol_print_error('','Value returned by function useDetailedBBAN not managed');

			foreach ($fieldstoshow as $val)
			{
				if ($val == 'bank')
				{
					// Bank code
					$tmplength=18;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_banque), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankCode"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
				if ($val == 'desk')
				{
					// Desk
					$tmplength=18;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_guichet), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("DeskCode"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
				if ($val == 'number')
				{
					// Number
					$tmplength=24;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->number), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumber"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
				if ($val == 'key')
				{
					// Key
					$tmplength=13;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->cle_rib), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumberKey"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
			}

			$curx=$savcurx;
			$cury+=10;
		}
	}
	else
	{
		$pdf->SetFont('','B',$default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
		$cury+=3;

		$pdf->SetFont('','B',$default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("BankAccountNumber").': ' . $outputlangs->convToOutputCharset($account->number), 0, 'L', 0);
		$cury+=3;

		if ($diffsizecontent <= 2) $cury+=1;
	}
	

	// Use correct name of bank id according to country
	$ibankey="IBANNumber";
	$bickey="BICNumber";
	if ($account->getCountryCode() == 'IN') $ibankey="IFSC";
	if ($account->getCountryCode() == 'IN') $bickey="SWIFT";

	$pdf->SetFont('','',$default_font_size - $diffsizecontent);

	if (empty($onlynumber) && ! empty($account->domiciliation))
	{
		$pdf->SetXY($curx, $cury);
		$val=$outputlangs->transnoentities("Residence").': ' . $outputlangs->convToOutputCharset($account->domiciliation);
		$pdf->MultiCell(100, 3, $val, 0, 'L', 0);
		//$nboflines=dol_nboflines_bis($val,120);
		//$cury+=($nboflines*3)+2;
		$tmpy=$pdf->getStringHeight(100, $val);
		$cury+=$tmpy;
	}
	else if (! $usedetailedbban) $cury+=1;

	if (! empty($account->iban))
	{
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($ibankey).': ' . $outputlangs->convToOutputCharset($account->iban), 0, 'L', 0);
		$cury+=3;
	}

	if (! empty($account->bic))
	{
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($bickey).': ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);
	}

	return $pdf->getY();
}

/**
 *  Show bank informations for PDF generation
 *
 *  @param	PDF			&$pdf            		Object PDF
 *  @param  Translate	$outputlangs     		Object lang
 *  @param  int			$curx            		X
 *  @param  int			$cury            		Y
 *  @param  Account		$account         		Bank account object
 *  @param  int			$onlynumber      		Output only number
 *  @param	int			$default_font_size		Default font size
 *  @return	void
 */
function pdf_ultimate_proposals_bank(&$pdf,$outputlangs,$curx,$cury,$account,$onlynumber=0,$default_font_size=10)
{
	global $mysoc, $conf;

	$diffsizetitle=(empty($conf->global->PDF_DIFFSIZE_TITLE)?3:$conf->global->PDF_DIFFSIZE_TITLE);
	$diffsizecontent=(empty($conf->global->PDF_DIFFSIZE_CONTENT)?4:$conf->global->PDF_DIFFSIZE_CONTENT);

	$pdf->SetXY($curx, $cury);

	if (empty($onlynumber))
	{
		$pdf->SetFont('','B',$default_font_size - $diffsizetitle);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
		$cury+=4;
	}

	$outputlangs->load("banks");

	// Get format of bank account according to its country
	$usedetailedbban=$account->useDetailedBBAN();

	//$onlynumber=0; $usedetailedbban=0; // For tests
	if (!$conf->global->ULTIMATE_BANK_HIDE_DETAILS_WITHIN_PROPOSALS)
	{
		if ($usedetailedbban)
		{
			$savcurx=$curx;

			if (empty($onlynumber))
			{
				$pdf->SetFont('','',$default_font_size - $diffsizecontent);
				$pdf->SetXY($curx, $cury);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
				$cury+=3;
			}
		
			if (empty($onlynumber)) $pdf->line($curx+1, $cury+1, $curx+1, $cury+8);

			if ($usedetailedbban == 1)
			{
				$fieldstoshow=array('bank','desk','number','key');
				if ($conf->global->BANK_SHOW_ORDER_OPTION==1) $fieldstoshow=array('bank','desk','key','number');
			}
			else if ($usedetailedbban == 2)
			{
				$fieldstoshow=array('bank','number');
			}
			else dol_print_error('','Value returned by function useDetailedBBAN not managed');

			foreach ($fieldstoshow as $val)
			{
				if ($val == 'bank')
				{
					// Bank code
					$tmplength=18;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_banque), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankCode"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
				if ($val == 'desk')
				{
					// Desk
					$tmplength=18;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_guichet), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("DeskCode"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
				if ($val == 'number')
				{
					// Number
					$tmplength=24;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->number), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumber"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
				if ($val == 'key')
				{
					// Key
					$tmplength=13;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->cle_rib), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumberKey"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
			}

			$curx=$savcurx;
			$cury+=10;
		}
	}
	else
	{
		$pdf->SetFont('','B',$default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
		$cury+=3;

		$pdf->SetFont('','B',$default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("BankAccountNumber").': ' . $outputlangs->convToOutputCharset($account->number), 0, 'L', 0);
		$cury+=3;

		if ($diffsizecontent <= 2) $cury+=1;
	}
	

	// Use correct name of bank id according to country
	$ibankey="IBANNumber";
	$bickey="BICNumber";
	if ($account->getCountryCode() == 'IN') $ibankey="IFSC";
	if ($account->getCountryCode() == 'IN') $bickey="SWIFT";

	$pdf->SetFont('','',$default_font_size - $diffsizecontent);

	if (empty($onlynumber) && ! empty($account->domiciliation))
	{
		$pdf->SetXY($curx, $cury);
		$val=$outputlangs->transnoentities("Residence").': ' . $outputlangs->convToOutputCharset($account->domiciliation);
		$pdf->MultiCell(100, 3, $val, 0, 'L', 0);
		//$nboflines=dol_nboflines_bis($val,120);
		//$cury+=($nboflines*3)+2;
		$tmpy=$pdf->getStringHeight(100, $val);
		$cury+=$tmpy;
	}
	else if (! $usedetailedbban) $cury+=1;

	if (! empty($account->iban))
	{
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($ibankey).': ' . $outputlangs->convToOutputCharset($account->iban), 0, 'L', 0);
		$cury+=3;
	}

	if (! empty($account->bic))
	{
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($bickey).': ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);
	}

	return $pdf->getY();
}

/**
 *  Show bank informations for PDF generation
 *
 *  @param	PDF			&$pdf            		Object PDF
 *  @param  Translate	$outputlangs     		Object lang
 *  @param  int			$curx            		X
 *  @param  int			$cury            		Y
 *  @param  Account		$account         		Bank account object
 *  @param  int			$onlynumber      		Output only number
 *  @param	int			$default_font_size		Default font size
 *  @return	void
 */
function pdf_ultimate_invoices_bank(&$pdf,$outputlangs,$curx,$cury,$account,$onlynumber=0,$default_font_size=10)
{
	global $mysoc, $conf;

	$diffsizetitle=(empty($conf->global->PDF_DIFFSIZE_TITLE)?3:$conf->global->PDF_DIFFSIZE_TITLE);
	$diffsizecontent=(empty($conf->global->PDF_DIFFSIZE_CONTENT)?4:$conf->global->PDF_DIFFSIZE_CONTENT);

	$pdf->SetXY($curx, $cury);

	if (empty($onlynumber))
	{
		$pdf->SetFont('','B',$default_font_size - $diffsizetitle);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
		$cury+=4;
	}

	$outputlangs->load("banks");

	// Get format of bank account according to its country
	$usedetailedbban=$account->useDetailedBBAN();

	//$onlynumber=0; $usedetailedbban=0; // For tests
	if (!$conf->global->ULTIMATE_BANK_HIDE_DETAILS_WITHIN_INVOICES)
	{
		if ($usedetailedbban)
		{
			$savcurx=$curx;

			if (empty($onlynumber))
			{
				$pdf->SetFont('','',$default_font_size - $diffsizecontent);
				$pdf->SetXY($curx, $cury);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
				$cury+=3;
			}
		
			if (empty($onlynumber)) $pdf->line($curx+1, $cury+1, $curx+1, $cury+8);

			if ($usedetailedbban == 1)
			{
				$fieldstoshow=array('bank','desk','number','key');
				if ($conf->global->BANK_SHOW_ORDER_OPTION==1) $fieldstoshow=array('bank','desk','key','number');
			}
			else if ($usedetailedbban == 2)
			{
				$fieldstoshow=array('bank','number');
			}
			else dol_print_error('','Value returned by function useDetailedBBAN not managed');

			foreach ($fieldstoshow as $val)
			{
				if ($val == 'bank')
				{
					// Bank code
					$tmplength=18;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_banque), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankCode"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
				if ($val == 'desk')
				{
					// Desk
					$tmplength=18;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_guichet), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("DeskCode"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
				if ($val == 'number')
				{
					// Number
					$tmplength=24;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->number), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumber"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
				if ($val == 'key')
				{
					// Key
					$tmplength=13;
					$pdf->SetXY($curx, $cury+5);
					$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->cle_rib), 0, 'C', 0);
					$pdf->SetXY($curx, $cury+1);
					$curx+=$tmplength;
					$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumberKey"), 0, 'C', 0);
					if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
				}
			}

			$curx=$savcurx;
			$cury+=10;
		}
	}
	else
	{
		$pdf->SetFont('','B',$default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
		$cury+=3;

		$pdf->SetFont('','B',$default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("BankAccountNumber").': ' . $outputlangs->convToOutputCharset($account->number), 0, 'L', 0);
		$cury+=3;

		if ($diffsizecontent <= 2) $cury+=1;
	}
	

	// Use correct name of bank id according to country
	$ibankey="IBANNumber";
	$bickey="BICNumber";
	if ($account->getCountryCode() == 'IN') $ibankey="IFSC";
	if ($account->getCountryCode() == 'IN') $bickey="SWIFT";

	$pdf->SetFont('','',$default_font_size - $diffsizecontent);

	if (empty($onlynumber) && ! empty($account->domiciliation))
	{
		$pdf->SetXY($curx, $cury);
		$val=$outputlangs->transnoentities("Residence").': ' . $outputlangs->convToOutputCharset($account->domiciliation);
		$pdf->MultiCell(100, 3, $val, 0, 'L', 0);
		//$nboflines=dol_nboflines_bis($val,120);
		//$cury+=($nboflines*3)+2;
		$tmpy=$pdf->getStringHeight(100, $val);
		$cury+=$tmpy;
	}
	else if (! $usedetailedbban) $cury+=1;

	if (! empty($account->iban))
	{
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($ibankey).': ' . $outputlangs->convToOutputCharset($account->iban), 0, 'L', 0);
		$cury+=3;
	}

	if (! empty($account->bic))
	{
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($bickey).': ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);
	}

	return $pdf->getY();
}

/**
 *   	Return a string with full address formated
 *
 * 		@param	Translate	$outputlangs		Output langs object
 *   	@param  Societe		$sourcecompany		Source company object
 *   	@param  Societe		$targetcompany		Target company object
 *      @param  Contact		$targetcontact		Target contact object
 * 		@param	int			$usecontact			Use contact instead of company
 * 		@param	int			$mode				Address type ('source', 'target', 'targetwithdetails')
 * 		@return	string							String with full address
 */
function pdf_invoice_build_address($outputlangs,$sourcecompany,$targetcompany='',$targetcontact='',$usecontact=0,$mode='source')
{
	global $conf;

	$stringaddress = '';

	if ($mode == 'source' && ! is_object($sourcecompany)) return -1;
	if ($mode == 'target' && ! is_object($targetcompany)) return -1;
	if ($mode == 'delivery' && ! is_object($deliverycompany)) return -1;

	if (! empty($sourcecompany->state_id) && empty($sourcecompany->departement)) $sourcecompany->departement=getState($sourcecompany->state_id); //TODO: Deprecated
	if (! empty($sourcecompany->state_id) && empty($sourcecompany->state)) $sourcecompany->state=getState($sourcecompany->state_id);
	if (! empty($targetcompany->state_id) && empty($targetcompany->departement)) $targetcompany->departement=getState($targetcompany->state_id);

	if ($mode == 'source')
	{
		$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($sourcecompany))."\n";

		if (empty($conf->global->MAIN_PDF_DISABLESOURCEDETAILS))
		{
			// Phone
			if ($sourcecompany->phone) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
			// Fax
			if ($sourcecompany->fax) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
			// EMail
			if ($sourcecompany->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
			// Web
			if ($sourcecompany->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
		}
	}

	if ($mode == 'target' || $mode == 'targetwithdetails')
	{
		if ($usecontact)
		{
			$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs,1));

			if (!empty($targetcontact->address)) {
				$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcontact))."\n";
			}else {
				$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany))."\n";
			}
			// Country
			if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
				$stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code))."\n";
			}
			else if (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
				$stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code))."\n";
			}

			if (! empty($conf->global->ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails')
			{
				// Phone
				if (! empty($targetcontact->phone_pro) || ! empty($targetcontact->phone_mobile)) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ";
				if (! empty($targetcontact->phone_pro)) $stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_pro);
				if (! empty($targetcontact->phone_pro) && ! empty($targetcontact->phone_mobile)) $stringaddress .= " / ";
				if (! empty($targetcontact->phone_mobile)) $stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_mobile);
				// Fax
				if ($targetcontact->fax) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcontact->fax);
				// EMail
				if ($targetcontact->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcontact->email);
				// Web
				if ($targetcontact->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcontact->url);
			}
		}
		else
		{
			$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany))."\n";
			// Country
			if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) $stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code))."\n";

			if (! empty($conf->global->ULTIMATE_PDF_INVOICE_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails')
			{
				// Phone
				if (! empty($targetcompany->phone) || ! empty($targetcompany->phone_mobile)) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ";
				if (! empty($targetcompany->phone)) $stringaddress .= $outputlangs->convToOutputCharset($targetcompany->phone);
				if (! empty($targetcompany->phone) && ! empty($targetcompany->phone_mobile)) $stringaddress .= " / ";
				if (! empty($targetcompany->phone_mobile)) $stringaddress .= $outputlangs->convToOutputCharset($targetcompany->phone_mobile);
				// Fax
				if ($targetcompany->fax) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcompany->fax);
				// EMail
				if ($targetcompany->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcompany->email);
				// Web
				if ($targetcompany->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcompany->url);
			}
		}

		// Intra VAT
		if (empty($conf->global->ULTIMATE_TVAINTRA_NOT_IN_INVOICE_ADDRESS))
		{
			if ($targetcompany->tva_intra) $stringaddress.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);
		}

		// Professionnal Ids
		if (! empty($conf->global->MAIN_PROFID1_IN_ADDRESS) && ! empty($targetcompany->idprof1))
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId1",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
		}
		if (! empty($conf->global->MAIN_PROFID2_IN_ADDRESS) && ! empty($targetcompany->idprof2))
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId2",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
		}
		if (! empty($conf->global->MAIN_PROFID3_IN_ADDRESS) && ! empty($targetcompany->idprof3))
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId3",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
		}
		if (! empty($conf->global->MAIN_PROFID4_IN_ADDRESS) && ! empty($targetcompany->idprof4))
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId4",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof4);
		}
	}

	return $stringaddress;
}

/**
 *  Show footer of page for PDF generation
 *
 *	@param	PDF			&$pdf     		The PDF factory
 *  @param  Translate	$outputlangs	Object lang for output
 * 	@param	string		$paramfreetext	Constant name of free text
 * 	@param	Societe		$fromcompany	Object company
 * 	@param	int			$marge_basse	Margin bottom we use for the autobreak
 * 	@param	int			$marge_gauche	Margin left (no more used)
 * 	@param	int			$page_hauteur	Page height (no more used)
 * 	@param	Object		$object			Object shown in PDF
 * 	@param	int			$showdetails	Show company details into footer. This param seems to not be used by standard version.
 *  @param	int			$hidefreetext	1=Hide free text, 0=Show free text
 * 	@return	int							Return height of bottom margin including footer text
 */
function pdf_ultimatepagefoot(&$pdf,$outputlangs,$paramfreetext,$fromcompany,$marge_basse,$marge_gauche,$page_hauteur,$object,$showdetails=0,$hidefreetext=0)
{
	global $conf,$user;

	$outputlangs->load("dict");
	$line='';

	$dims=$pdf->getPageDimensions();

	// Line of free text
	if (empty($hidefreetext) && ! empty($conf->global->$paramfreetext))
	{
		// Make substitution
		$substitutionarray=array(
		'__FROM_NAME__' => $fromcompany->nom,
		'__FROM_EMAIL__' => $fromcompany->email,
		'__TOTAL_TTC__' => $object->total_ttc,
		'__TOTAL_HT__' => $object->total_ht,
		'__TOTAL_VAT__' => $object->total_vat
		);
		complete_substitutions_array($substitutionarray,$outputlangs,$object);
		$newfreetext=make_substitutions($conf->global->$paramfreetext,$substitutionarray);
		$line.=$outputlangs->convToOutputCharset($newfreetext);
	}

	// First line of company infos

	if ($showdetails)
	{
		$line1="";
		// Company name
		if ($fromcompany->name)
		{
			$line1.=($line1?" - ":"").$fromcompany->name;
		}
		// Address
		if ($fromcompany->address)
		{
			$fromcompany->address = str_replace(array( '<br>', '<br />', "\n", "\r" ), array( '', '', '', '' ), $fromcompany->address);
			$line1.=($line1?" - ":"").$fromcompany->address;
		}
		// Zip code
		if ($fromcompany->zip)
		{
			$line1.=($line1?" - ":"").$fromcompany->zip;
		}
		// Town
		if ($fromcompany->town)
		{
			$line1.=($line1?" ":"").$fromcompany->town;
		}

		$line2="";
		// Juridical status
		if ($fromcompany->forme_juridique_code)
		{
			$line2.=($line2?" - ":"").$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
		}
		// Capital
		if ($fromcompany->capital)
		{
			$line2.=($line2?" - ":"").$outputlangs->transnoentities("CapitalOf",$fromcompany->capital)." ".$outputlangs->transnoentities("Currency".$conf->currency);
		}
		// Prof Id 1
		if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || ! $fromcompany->idprof2))
		{
			$field=$outputlangs->transcountrynoentities("ProfId1",$fromcompany->country_code);
			if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
			$line2.=($line2?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof1);
		}
		// Prof Id 2
		if ($fromcompany->idprof2)
		{
			$field=$outputlangs->transcountrynoentities("ProfId2",$fromcompany->country_code);
			if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
			$line2.=($line2?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof2);
		}
		// Prof Id 3
		if ($fromcompany->idprof3)
		{
			$field=$outputlangs->transcountrynoentities("ProfId3",$fromcompany->country_code);
			if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
			$line2.=($line2?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof3);
		}
	}

	// Line 3 of company infos
	$line3="";
	
	// Phone
	if ($fromcompany->phone)
	{
		$line3.=($line3?" - ":"").$outputlangs->transnoentities("Phone").": ".$fromcompany->phone;
	}
	// Fax
	if ($fromcompany->fax)
	{
		$line3.=($line3?" - ":"").$outputlangs->transnoentities("Fax").": ".$fromcompany->fax;
	}
	// Mail
	if ($fromcompany->email)
	{
		$line3.=($line3?" - ":"").$outputlangs->transnoentities("EMail").": ".$fromcompany->email;
	}

	// Line 4 of company infos
	$line4="";
	
	// Prof Id 3
	if ($fromcompany->tva_intra != '')
	{
		$line4.=($line4?" - ":"").$outputlangs->transnoentities("VATIntraShort").": ".$outputlangs->convToOutputCharset($fromcompany->tva_intra);
	}
	
	// Set free text font size
	if (! empty($conf->global->ULTIMATEPDF_FREETEXT_FONT_SIZE)) {
		$freetextfontsize=$conf->global->ULTIMATEPDF_FREETEXT_FONT_SIZE;
	}
	$pdf->SetFont('','',$freetextfontsize);
	$pdf->SetDrawColor(224,224,224);

	// On positionne le debut du bas de page selon nbre de lignes de ce bas de page
	$freetextheight=0;
	if ($line)	// Free text
	{
		$width=20000; $align='L';	// By default, ask a manual break: We use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
		if (! empty($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT)) {
			$width=$page_largeur-$marge_gauche-$marge_droite; $align='C';
		}
		$freetextheight=$pdf->getStringHeight($width,$line);
	}

	$marginwithfooter=$marge_basse + $freetextheight + (! empty($line1)?3:0) + (! empty($line2)?3:0) + (! empty($line3)?3:0) + (! empty($line4)?3:0);
	$posy=$marginwithfooter+0;

	if ($line)	// Free text
	{
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($width, 3, $line, 0, $align, 0);
		$posy-=$freetextheight;
	}
	$pdf->SetFont('','',7);
	$pdf->SetY(-$posy);
	$pdf->line($dims['lm'], $dims['hk']-$posy, $dims['wk']-$dims['rm'], $dims['hk']-$posy);
	$posy--;

	if (! empty($line1))
	{
		$pdf->SetFont('','B',7);
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line1, 0, 'C', 0);
		$posy-=3;
		$pdf->SetFont('','',7);
	}

	if (! empty($line2))
	{
		$pdf->SetFont('','B',7);
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line2, 0, 'C', 0);
		$posy-=3;
		$pdf->SetFont('','',7);
	}

	if (! empty($line3))
	{
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line3, 0, 'C', 0);
	}

	if (! empty($line4))
	{
		$posy-=3;
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line4, 0, 'C', 0);
	}
	
	$posy-=3;
	$pdf->SetXY($dims['lm'],-$posy);
	//Display Thirdparty barcode at top			
	$barcode=$object->client->barcode;
	$object->client->fetch_barcode();
	$styleBc = array(
		'position' => '',
		'align' => 'L',
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
	if (! empty($conf->global->ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE))
	{
		if ($barcode) 
		$pdf->write1DBarcode($barcode, $object->client->barcode_type_code, $dims['lm'], -$posy+294, $dims['wk']-$dims['rm'], 12, 0.4, $styleBc, 'L');
	}

	// Show page nb only on iso languages (so default Helvetica font)
	if (pdf_getPDFFont($outputlangs) == 'Helvetica')
	{
		$pdf->SetXY(-20,-$posy);
		if (empty($conf->global->MAIN_USE_FPDF)) $pdf->MultiCell(13, 2, $pdf->PageNo().'/'.$pdf->getAliasNbPages(), 0, 'R', 0);
		else $pdf->MultiCell(13, 2, $pdf->PageNo().'/{nb}', 0, 'R', 0);
	}		

	return $marginwithfooter;
}

function pdf_codeContents()
{
	global $object;
		$codeContents  = 'BEGIN:VCARD'."\n";
		$codeContents .= 'FN:'.$object->client->name."\n";
		$codeContents .= 'TEL;WORK;VOICE:'.$object->client->phone."\n";
		$codeContents .= 'ADR;TYPE=work;'.
			'LABEL="'.$addressLabel.'":'
			.$object->client->address.';'
			.$object->client->town.';'
			.$object->client->zip.';'
			.$object->client->country
		."\n";
		$codeContents .= 'EMAIL:'.$object->client->email."\n"; 
		$codeContents .= 'END:VCARD';
	
	return $codeContents;
}

function pdf_mycompCodeContents()
{
	global $mysoc;
		$codeContents  = 'BEGIN:VCARD'."\n";
		$codeContents .= 'FN:'.$mysoc->name."\n";
		$codeContents .= 'TEL;WORK;VOICE:'.$mysoc->phone."\n";
		$codeContents .= 'ADR;TYPE=work;'.
			'LABEL="'.$addressLabel.'":'
			.$mysoc->address.';'
			.$mysoc->town.';'
			.$mysoc->zip.';'
			.$mysoc->country
		."\n";
		$codeContents .= 'EMAIL:'.$mysoc->email."\n"; 
		$codeContents .= 'END:VCARD';
	
	return $codeContents;
}

function pdf_codeOrderLink()
{
	global $object;

		$urlwithroot=DOL_MAIN_URL_ROOT;
		$codeOrderLink  = $urlwithroot.'/commande/fiche.php?id='.$object->id;
	
	return $codeOrderLink;
}

/**
 * Return height to use for Logo onto PDF
 *
 * @param	string		$logo		Full path to logo file to use
 * @param	bool		$url		Image with url (true or false)
 * @return	number
 */
function pdf_getUltimateHeightForLogo($logo, $url = false)
{
	global $conf;
	
	include_once DOL_DOCUMENT_ROOT."/core/lib/pdf.lib.php";
	$formatarray=pdf_getFormat();
	$page_largeur = $formatarray['width'];
	$marge_gauche=isset($conf->global->ULTIMATE_PDF_MARGIN_LEFT)?$conf->global->ULTIMATE_PDF_MARGIN_LEFT:10;
	$marge_droite=isset($conf->global->ULTIMATE_PDF_MARGIN_RIGHT)?$conf->global->ULTIMATE_PDF_MARGIN_RIGHT:10;
	$logo_height=$conf->global->ULTIMATE_LOGO_HEIGHT?$conf->global->ULTIMATE_LOGO_HEIGHT:30; $maxwidth=($page_largeur-$marge_gauche-$marge_droite-4)/2;

	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$tmp=dol_getImageSize($logo, $url);
	if ($tmp['height'])
	{
		$width=round($logo_height*$tmp['width']/$tmp['height']);
		if ($width > $maxwidth) $logo_height=$logo_height*$maxwidth/$width;
	}
	return $logo_height;
	
}

/**
 *	Return invoice line weight
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	void
 */
function pdf_getlineweight($object,$i,$outputlangs,$hidedetails=0)
{
	global $db, $langs, $hookmanager;
	
	if ($object->ref == 'SPECIMEN') {
		$weight = '1,5 Kg';
		return $weight;
	}
	
	if (is_object($hookmanager) && ( ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
		$action='';
		return $hookmanager->executeHooks('pdf_getlineweight',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	}
	else
	{
		
		if (empty($hidedetails) || $hidedetails > 1) {
			
			$langs->load('other');
			
			include_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');
			
			$sql = 'SELECT p.weight,p.weight_units';
			$sql.= ' FROM '.MAIN_DB_PREFIX.$object->table_element_line.' as l';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
			$sql.= ' WHERE l.rowid = '.$object->lines[$i]->rowid;
			
			dol_syslog('ultimatepdf.lib.php::pdf_getlineweight sql='.$sql, LOG_DEBUG);
			$result = $db->query($sql);
			if ($result)
			{
				$objw = $db->fetch_object($result);
				$weight=($objw->weight*$object->lines[$i]->qty)." ".measuring_units_string($objw->weight_units,"weight");
			}
			else
			{
				$error=$db->lasterror();
				dol_syslog('ultimatepdf.lib.php::pdf_getlineweight '.$error,LOG_ERR);
			}
	
			return $weight;
		}
	}
}

/**
 *	Return total weight to use onto PDF
 *
 *	@param	Object		$object				Object
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	void
 */
function pdf_getweight($object,$outputlangs,$hidedetails=0)
{
	global $db, $langs, $hookmanager;
	
	if ($object->ref == 'SPECIMEN') {
		$weight = '9 Kg';
		return $weight;
	}

	if (is_object($hookmanager))
	{
		$parameters = array('outputlangs'=>$outputlangs,'hidedetails'=>$hidedetail);
		$action='';
		$returnhook= $hookmanager->executeHooks('pdf_getweight',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	}
	if ($returnhook==0)
	{
		if (empty($hidedetails) || $hidedetails > 1) {
				
			$langs->load('other');
				
			include_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');
				
			$sql = 'SELECT p.weight,p.weight_units,l.qty';
			$sql.= ' FROM '.MAIN_DB_PREFIX.$object->table_element_line.' as l';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
			$sql.= ' WHERE l.'.$object->fk_element.' = '.$object->id;
			
			$weight=0;
			
			dol_syslog('ultimatepdf.lib.php::pdf_getweight sql='.$sql, LOG_DEBUG);
			$result = $db->query($sql);
			if ($result)
			{
				
				$num = $db->num_rows($result);
				$i = 0;
				$sameunit=true;
				while ($i < $num)
				{
					$objw = $db->fetch_object($result);
					
					if (($lastunit!=$objw->weight_units) && ($i!=0)) {
						$sameunit=false;
					}
					$lastunit=$objw->weight_units;
					
					//Ref unit is kilogram
					switch ($objw->weight_units) {
					    case 0:
					    	//Kg
					    	$weight+=$objw->weight*$objw->qty;
					        break;
					    case 3:
					        //Ton
					    	$weight+=($objw->weight*1000)*$objw->qty;
					        break;
					    case -3:
					        //g
					    	$weight+=($objw->weight*0.001)*$objw->qty;
					        break;
				        case -6:
				        	$weight+=($objw->weight*0.000001)*$objw->qty;
				        	//mg
				        	break;
			        	case 99:
			        		//pound
			        		$weight+=($objw->weight*0.45359237)*$objw->qty;
			        		break;
					}
					
					$i++;
				}				
			}
			else
			{
				$error=$db->lasterror();
				dol_syslog('ultimatepdf.lib.php::pdf_getweight '.$error,LOG_ERR);
			}

			if ($sameunit) {
				//if only one unit is use convert kg in this unit to render it 
				
				switch ($lastunit) {
					case 0:
						//Kg
						$weight=$weight; //Already in kg
						break;
					case 3:
						//Ton
						$weight=($weight/1000);
						break;
					case -3:
						//g
						$weight=($weight/0.001);
						break;
					case -6:
						$weight=($weight/0.000001);
						//mg
						break;
					case 99:
						//pound
						$weight=($weight/2.20462262);
						break;
				}
				
				$weight=$weight." ".measuring_units_string($lastunit,"weight");
			}else {
				$weight=$weight." ".measuring_units_string(0,"weight");
			}
			
			return $weight;
		}
	} else {
		return $returnhook;
	}
}

/**
 *	Return total Qty
 *
 *	@param	Object		$object				Object
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 * 	@return	void
 */
function pdf_getqty($object,$outputlangs,$hidedetails=0)
{
	global $db, $langs, $hookmanager;

	if (is_object($hookmanager))
	{
		$parameters = array('outputlangs'=>$outputlangs,'hidedetails'=>$hidedetail);
		$action='';
		$returnhook= $hookmanager->executeHooks('pdf_getqty',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	}
	if ($returnhook==0)
	{

		if (empty($hidedetails) || $hidedetails > 1) {

			$langs->load('other');

			include_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');

			$sql = 'SELECT sum(l.qty) as totalqty';
			$sql.= ' FROM '.MAIN_DB_PREFIX.$object->table_element_line.' as l';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
			$sql.= ' WHERE l.'.$object->fk_element.' = '.$object->id;
				
			$qty=0;
			
			dol_syslog('ultimatepdf.lib.php::pdf_getqty sql='.$sql, LOG_DEBUG);
			$result = $db->query($sql);
			if ($result)
			{
				$objqty = $db->fetch_object($result);
				$qty=$objqty->totalqty;
			}
			else
			{
				$error=$db->lasterror();
				dol_syslog('ultimatepdf.lib.php::pdf_getqty '.$error,LOG_ERR);
			}
				
			return $qty;
		}
	} 
	else {
		return $returnhook;
	}
}

?>