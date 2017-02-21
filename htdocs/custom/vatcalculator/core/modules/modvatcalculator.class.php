<?php
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module TTCPrice
 */
class modVATCalculator extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct ($db)
    {
        global $conf;

        $this->db = $db;

        $this->numero       = 504902;
        $this->rights_class = 'vatcalculator';

        $this->family      = "other";
        $this->name        = 'VAT Calculator';
        $this->description = "Allow input of tax incl./excl. prices";
        $this->version     = '1.0';
        $this->const_name  = 'MAIN_MODULE_VATCALCULATOR';
        $this->special     = 0;
        $this->picto       = 'generic';

        $this->module_parts = [
            'hooks' => ['propalcard', 'invoicecard', 'ordercard', 'contractcard', 'invoicesuppliercard', 'ordersuppliercard']
        ];

        $this->config_page_url = ['config.php@vatcalculator'];

        // Dependencies
        $this->hidden                = false;
        $this->depends               = [];
        $this->requiredby            = [];
        $this->conflictwith          = [];
        $this->phpmin                = [5, 0];
        $this->need_dolibarr_version = [3, 0];
        $this->langfiles             = ["vatcalculator_general@vatcalculator"];

        // Constants
        $this->const = [
            0 => ['MODULE_VATCALCULATOR_CQUOTE', 'yesno', '1', 'Use VATCalculator module on customer quotation page', 0, 'current', 1],
            1 => ['MODULE_VATCALCULATOR_CORDER', 'yesno', '1', 'Use VATCalculator module on customer order page', 0, 'current', 1],
            2 => ['MODULE_VATCALCULATOR_CINVOICE', 'yesno', '1', 'Use VATCalculator module on customer invoice page', 0, 'current', 1],
            3 => ['MODULE_VATCALCULATOR_CONTRACT', 'yesno', '1', 'Use VATCalculator module on contract page', 0, 'current', 1],
            4 => ['MODULE_VATCALCULATOR_SORDER', 'yesno', '1', 'Use VATCalculator module on supplier order page', 0, 'current', 1],
            5 => ['MODULE_VATCALCULATOR_SINVOICE', 'yesno', '1', 'Use VATCalculator module on supplier invoice page', 0, 'current', 1]
        ];

        if (!isset($conf->mymodule->enabled)) {
            $conf->mymodule          = new stdClass();
            $conf->mymodule->enabled = 0;
        }

        $this->dirs         = [];
        $this->tabs         = [];
        $this->dictionaries = [];
        $this->boxes        = [];
        $this->cronjobs     = [];
        $this->rights       = [];
        $this->menu         = [];
    }

    /**
     *        Function called when module is enabled.
     *        The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr
     *        database. It also creates data directories
     *
     * @param      string $options Options when enabling module ('', 'noboxes')
     * @return     int                1 if OK, 0 if KO
     */
    public function init ($options = '')
    {
        return $this->_init([], $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * @param      string $options Options when enabling module ('', 'noboxes')
     * @return     int                1 if OK, 0 if KO
     */
    public function remove ($options = '')
    {
        return $this->_remove([], $options);
    }

}
