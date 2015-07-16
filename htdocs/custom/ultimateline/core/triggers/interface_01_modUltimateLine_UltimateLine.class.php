<?php

/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2013-2014 Philippe Grand       <philippe.grand@atoo-net.com>
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
 */

/**
 *      \file       htdocs/includes/triggers/interface_01_modUltimateLine_UltimateLine.class.php
 *      \ingroup    financial
 *      \brief      Fichier de personalisation des actions du workflow
 */

/**
 *      \class      InterfaceUltimateLine
 *      \brief      Class of triggers for UltimateLine module
 */
class InterfaceUltimateLine
{

    var $db;
	var $error;
    var $errors=array();

   /**
     *	Constructor
     *
     *	@param	DoliDB	$db		Database handler
     */
    function __construct($db)
    {
        $this->db = $db ;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "financial";
        $this->description = "Triggers used to add one or more final lines on invoices.";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'ultimateline@ultimateline';
    }

    /**
     *   Return name of trigger file
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/includes/triggers
     *      @param      action      Code de l'evenement
     *      @param      object      Objet concerne
     *      @param      user        Objet user
     *      @param      langs       Objet langs
     *      @param      conf        Objet conf
     *      @return     int         <0 if KO, 0 if no triggered ran, >0 if OK
     */
    function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Users

        switch ($action)
        {
            case 'LINEBILL_INSERT':
            case 'LINEBILL_UPDATE':
            case 'LINEBILL_DELETE':
                dol_include_once('/ultimateline/class/ultimateLine.class.php');
				$error=0;
                // Prepare some data arrays: existing ultimatelines ids and products linked to
                $staticUltimateLine = new UltimateLine($this->db);
                $ultimatesData = $staticUltimateLine->getUltimatesData();
                $ultimatesByTargets = $staticUltimateLine->getUltimatesByTargets();
                $ultimatesIds = array_keys($ultimatesData);

                // Ultimate lines data to add array
                $ultimatesLines = array();

                // Get line object fetched
                if (!($object->fetch($object->rowid) > 0))
                    $error++;

                // Si la ligne appelante est une ligne finale, STOP (pas de no_trigger dans les fonction DU CORE -_- ) - autrement loop infini pour delete
                if (in_array($object->fk_product, $ultimatesIds))
                    return 1;

                // Get invoice for current line calling
				require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
                $invoice = new Facture($this->db);
                if (!($invoice->fetch($object->fk_facture) > 0))
                    $error++;
                if (!($invoice->fetch_lines() > 0))
                    $error++;				

                $test = 0;
                foreach ($invoice->lines as $invoiceLine)
                {
                    // If current service define as a final line exists in finals, REMOVE IT & DON NOT INCLUDE in calc
                    if (isset($invoiceLine->fk_product) && in_array($invoiceLine->fk_product, $ultimatesIds))
                    {
                        $tempInvoiceLine = new FactureLigne($this->db);
                        $tempInvoiceLine->rowid = $invoiceLine->rowid;
                        if (!($tempInvoiceLine->delete() > 0))
                            $error++;
                    } else
                    {
                        if (array_key_exists($invoiceLine->fk_product, $ultimatesByTargets))
                        {
                            foreach ($ultimatesByTargets[$invoiceLine->fk_product]['ultimates'] as $ultimatesToApply)
                            {
                                if (!array_key_exists($ultimatesToApply, $ultimatesLines))
                                    $ultimatesLines[$ultimatesToApply] = 0;

                                // Prepare line when service is a rate on price
                                if ($ultimatesData[$ultimatesToApply]['type'] == UltimateLine::SERVICE_TYPE_RATEONPRICE)
                                    $ultimatesLines[$ultimatesToApply] += ($ultimatesData[$ultimatesToApply]['value'] / 100) * $invoiceLine->total_ht;
								// Prepare line when service is a value on price
                                if ($ultimatesData[$ultimatesToApply]['type'] == UltimateLine::SERVICE_TYPE_VALUEONPRICE)
                                    $ultimatesLines[$ultimatesToApply] += $ultimatesData[$ultimatesToApply]['value'] * $invoiceLine->qty;
                            }
                        }
                    }
                }
			
	
                foreach ($ultimatesLines as $ultimateLineServiceId => $amount)
				{
					require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
					$staticProduct = new Product($this->db);
					$staticProduct->fetch($ultimateLineServiceId);
					$tva_tx = $staticProduct->tva_tx;
				
					if (! empty($_POST['origin']) && ! empty($_POST['originid']))
					{
						$invoiceOrigin = new Facture($this->db);
						$invoiceOrigin->fetch($invoiceOrigin->origine_id);
						if (!($invoiceOrigin->addline($lineDesc, price2num($amount), 1, $tva_tx, '', '', $ultimateLineServiceId) > 0))
						$error++;
						
					}
					else
					{
						if (!($invoice->addline($lineDesc, price2num($amount), 1, $tva_tx, '', '', $ultimateLineServiceId) > 0))
						$error++;				
					}
					if (! $error)
					{
						return 0;
					}				
				}
                break;
        }

        if (isset($error) && !$error)
            return 1;
        
        //return -1;

		switch ($action)
        {
            case 'LINEPROPAL_INSERT':
            case 'LINEPROPAL_UPDATE':
            case 'LINEPROPAL_DELETE':
                dol_include_once('/ultimateline/class/ultimateLine.class.php');
				$error=0;
                // Prepare some data arrays: existing ultimatelines ids and products linked to
                $staticUltimateLine = new UltimateLine($this->db);
                $ultimatesData = $staticUltimateLine->getUltimatesData();
                $ultimatesByTargets = $staticUltimateLine->getUltimatesByTargets();
                $ultimatesIds = array_keys($ultimatesData);

                // Ultimate lines data to add array
                $ultimatesLines = array();

                // Get line object fetched
                if (!($object->fetch($object->rowid) > 0))
                    $error++;

                // Si la ligne appelante est une ligne finale, STOP (pas de no_trigger dans les fonction DU CORE -_- ) - autrement loop infini pour delete
                if (in_array($object->fk_product, $ultimatesIds))
                    return 1;

                // Get propal for current line calling
                $propal = new Propal($this->db);
                if (!($propal->fetch($object->fk_propal) > 0))
                    $error++;
                if (!($propal->getLinesArray() > 0))
                    //$error++;

                $test = 0;
                foreach ($propal->lines as $propalLine)
                {
                    // If current service define as a final line exists in finals, REMOVE IT & DON NOT INCLUDE in calc
                    if (isset($propalLine->fk_product) && in_array($propalLine->fk_product, $ultimatesIds))
                    {
                        $tempPropaleLine = new PropaleLigne($this->db);
                        $tempPropaleLine->rowid = $propalLine->rowid;
                        if (!($tempPropaleLine->delete() > 0))
                            $error++;
                    } else
                    {
                        if (array_key_exists($propalLine->fk_product, $ultimatesByTargets))
                        {
                            foreach ($ultimatesByTargets[$propalLine->fk_product]['ultimates'] as $ultimatesToApply)
                            {
                                if (!array_key_exists($ultimatesToApply, $ultimatesLines))
                                    $ultimatesLines[$ultimatesToApply] = 0;
                                // Prepare line when service is a rate on price
                                if ($ultimatesData[$ultimatesToApply]['type'] == UltimateLine::SERVICE_TYPE_RATEONPRICE)
                                    $ultimatesLines[$ultimatesToApply] += ($ultimatesData[$ultimatesToApply]['value'] / 100) * $propalLine->total_ht;
								// Prepare line when service is a value on price
                                if ($ultimatesData[$ultimatesToApply]['type'] == UltimateLine::SERVICE_TYPE_VALUEONPRICE)
                                    $ultimatesLines[$ultimatesToApply] += $ultimatesData[$ultimatesToApply]['value'] * $propalLine->qty;
                            }
                        }
                    }
                }

                foreach ($ultimatesLines as $ultimateLineServiceId => $amount)
				{
					require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
					$staticProduct = new Product($this->db);
					$staticProduct->fetch($ultimateLineServiceId);
					$tva_tx = $staticProduct->tva_tx;
				
                    if (!($propal->addline($lineDesc, price2num($amount), 1, $tva_tx, '', '', $ultimateLineServiceId) > 0))				
                        $error++;
				}
                break;
        }

        if (isset($error) && !$error)
            return 1;
        
        //return -1;
		
		switch ($action)
        {
            case 'LINEORDER_INSERT':
            case 'LINEORDER_UPDATE':
            case 'LINEORDER_DELETE':
                dol_include_once('/ultimateline/class/ultimateLine.class.php');
				$error=0;
                // Prepare some data arrays: existing ultimatelines ids and products linked to
                $staticUltimateLine = new UltimateLine($this->db);
                $ultimatesData = $staticUltimateLine->getUltimatesData();
                $ultimatesByTargets = $staticUltimateLine->getUltimatesByTargets();
                $ultimatesIds = array_keys($ultimatesData);

                // Ultimate lines data to add array
                $ultimatesLines = array();

                // Get line object fetched
                if (!($object->fetch($object->rowid) > 0))
                    $error++;

                // Si la ligne appelante est une ligne finale, STOP (pas de no_trigger dans les fonction DU CORE -_- ) - autrement loop infini pour delete
                if (in_array($object->fk_product, $ultimatesIds))
                    return 1;

                // Get order for current line calling
                $order = new Commande($this->db);
                if (!($order->fetch($object->fk_commande) > 0))
                    $error++;
                if (!($order->getLinesArray() > 0))
                    //$error++;

                $test = 0;
                foreach ($order->lines as $orderLine)
                {
                    // If current service define as a final line exists in finals, REMOVE IT & DON NOT INCLUDE in calc
                    if (isset($orderLine->fk_product) && in_array($orderLine->fk_product, $ultimatesIds))
                    {
                        $tempOrderLine = new OrderLine($this->db);
                        $tempOrderLine->rowid = $orderLine->rowid;
                        if (!($tempOrderLine->delete() > 0))
                            $error++;
                    } else
                    {
                        if (array_key_exists($orderLine->fk_product, $ultimatesByTargets))
                        {
                            foreach ($ultimatesByTargets[$orderLine->fk_product]['ultimates'] as $ultimatesToApply)
                            {
                                if (!array_key_exists($ultimatesToApply, $ultimatesLines))
                                    $ultimatesLines[$ultimatesToApply] = 0;
                                // Prepare line when service is a rate on price
                                if ($ultimatesData[$ultimatesToApply]['type'] == UltimateLine::SERVICE_TYPE_RATEONPRICE)
                                    $ultimatesLines[$ultimatesToApply] += ($ultimatesData[$ultimatesToApply]['value'] / 100) * $orderLine->total_ht;
								// Prepare line when service is a value on price
                                if ($ultimatesData[$ultimatesToApply]['type'] == UltimateLine::SERVICE_TYPE_VALUEONPRICE)
                                    $ultimatesLines[$ultimatesToApply] += $ultimatesData[$ultimatesToApply]['value'] * $orderLine->qty;
                            }
                        }
                    }
                }

                foreach ($ultimatesLines as $ultimateLineServiceId => $amount)
				{
					require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
					$staticProduct = new Product($this->db);
					$staticProduct->fetch($ultimateLineServiceId);
					$tva_tx = $staticProduct->tva_tx;
					if (! empty($_POST['origin']) && ! empty($_POST['originid']))
					{
						$orderOrigin = new Commande($this->db);
						$orderOrigin->fetch($orderOrigin->origine_id);
						if (!($orderOrigin->addline($lineDesc, price2num($amount), 1, $tva_tx, '', '', $ultimateLineServiceId) > 0))				
						$error++;
					}
					else
					{
						if (!($order->addline($lineDesc, price2num($amount), 1, $tva_tx, '', '', $ultimateLineServiceId) > 0))				
							$error++;
					}
					if (! $error)
					{
						return 0;
					}
				}
                break;
        }

        if (isset($error) && !$error)
            return 1;
        
        //return -1;
    }

}

?>
