<?php

class ActionsVatcalculator
{
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs;

        $show = false;
        switch ($parameters['currentcontext']) {
            case 'propalcard':
                $show = $conf->global->MODULE_VATCALCULATOR_CQUOTE == '1';
                break;
            case 'ordercard':
                $show = $conf->global->MODULE_VATCALCULATOR_CORDER == '1';
                break;
            case 'invoicecard':
                $show = $conf->global->MODULE_VATCALCULATOR_CINVOICE == '1';
                break;
            case 'contractcard':
                $show = $conf->global->MODULE_VATCALCULATOR_CONTRACT == '1';
                break;
            case 'invoicesuppliercard':
                $show = $conf->global->MODULE_VATCALCULATOR_SINVOICE == '1';
                break;
            case 'ordersuppliercard':
                $show = $conf->global->MODULE_VATCALCULATOR_SORDER == '1';
                break;
        }

        if ($show) {
            $path = DOL_URL_ROOT . substr(realpath(dirname(__FILE__ ) . '/..'), strlen(DOL_DOCUMENT_ROOT))
            .'/js/vatcalculator.js';

            $langs->load('vatcalculator_front@vatcalculator');

            $decimalSeparator = $langs->trans('SeparatorDecimal');
            $thousandSeparator = $langs->trans('SeparatorThousand');

            $conf->global->MAIN_HTML_HEADER .=   "\n" . '<script type="text/javascript">'
                                                . 'var modvatcalculatorlang_ttclabel = "' . $langs->trans('VATCalculatorTaxInclLabel') . '",'
                                                . 'modvatcalculatorlang_htlabel = "' . $langs->trans('VATCalculatorTaxExclLabel') . '",'
                                                . 'modvatcalculatorlang_pulabel = "' . $langs->trans('VATCalculatorUnitPriceLabel') . '",'
                                                . 'modvatcalculator_decsep = "' . $decimalSeparator . '", '
                                                . 'modvatcalculator_thsep = "' . ($thousandSeparator == 'Space' ? ' ' : $thousandSeparator) . '";</script>'
                                                . '<script type="text/javascript" src="' . $path . '"></script>';
        }
    }
}
