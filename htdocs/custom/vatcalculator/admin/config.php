<?php

if (file_exists('../../main.inc.php')) {
    require '../main.inc.php';
}
elseif (file_exists('../../../main.inc.php')) {
    require '../../../main.inc.php';
}
else {
    die("Error : Module must be installed in htdocs or htdocs/custom directory.");
}

// Load language
$langs->load('admin');
$langs->load('vatcalculator_admin@vatcalculator');

// Security check
if (! $user->admin || !isset($conf->global->MAIN_MODULE_VATCALCULATOR) || !$conf->global->MAIN_MODULE_VATCALCULATOR )
	accessforbidden();

llxHeader('', $langs->trans('VATCalculatorSetupTitle'));

print_fiche_titre(
    $langs->trans('VATCalculatorSetupTitle'),
    '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>',
    'title_setup'
);

dol_fiche_head(array(), 'card', $langs->trans('VATCalculator'), 1);

function updateConstValue($name, $bool) {
    global $db, $conf;

    $value = $bool ? '1' : '0';
    if ($conf->global->{$name} == $value)
        return;

    $sql = 'UPDATE ' . MAIN_DB_PREFIX . 'const SET '
            .'value = "'.$value.'" WHERE '
            .'name = "' . $name . '"';

    $db->query($sql);
    $conf->global->{$name} = $value;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && GETPOST('form_name') == 'vatcalculator_config') {
    updateConstValue('MODULE_VATCALCULATOR_CQUOTE', isset($_POST['customer_quotation']));
    updateConstValue('MODULE_VATCALCULATOR_CORDER', isset($_POST['customer_order']));
    updateConstValue('MODULE_VATCALCULATOR_CINVOICE', isset($_POST['customer_invoice']));
    updateConstValue('MODULE_VATCALCULATOR_CONTRACT', isset($_POST['contract']));
    updateConstValue('MODULE_VATCALCULATOR_SORDER', isset($_POST['supplier_order']));
    updateConstValue('MODULE_VATCALCULATOR_SINVOICE', isset($_POST['supplier_invoice']));
}

$configs = array(
    'customer_quotation' => $conf->global->MODULE_VATCALCULATOR_CQUOTE != '0' ,
    'customer_order' => $conf->global->MODULE_VATCALCULATOR_CORDER != '0',
    'customer_invoice' => $conf->global->MODULE_VATCALCULATOR_CINVOICE != '0',
    'contract' => $conf->global->MODULE_VATCALCULATOR_CONTRACT != '0',
    'supplier_order' => $conf->global->MODULE_VATCALCULATOR_SORDER != '0',
    'supplier_invoice' => $conf->global->MODULE_VATCALCULATOR_SINVOICE != '0'
);

?>
<!-- <div class="tabBar"> -->
    <?php print_titre($langs->trans('Parameters')); ?>
    <form method="post">
        <table class="noborder" width="100%">
            <thead>
                <tr class="liste_titre">
                    <th><?php echo $langs->trans('Name') ?></th>
                    <th><?php echo $langs->trans('Description') ?></th>
                    <th><?php echo $langs->trans('Status') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $langs->trans('VATCalculatorCustomerQuotation'); ?></td>
                    <td><?php echo $langs->trans('VATCalculatorCustomerQuotationDesc'); ?></td>
                    <td><input type="checkbox" name="customer_quotation" <?php echo $configs['customer_quotation'] == '1' ? 'checked' : '' ?>/></td>
                </tr>
                <tr>
                    <td><?php echo $langs->trans('VATCalculatorCustomerOrder'); ?></td>
                    <td><?php echo $langs->trans('VATCalculatorCustomerOrderDesc'); ?></td>
                    <td><input type="checkbox" name="customer_order" <?php echo $configs['customer_order'] == '1' ? 'checked' : '' ?>/></td>
                </tr>
                <tr>
                    <td><?php echo $langs->trans('VATCalculatorCustomerInvoice'); ?></td>
                    <td><?php echo $langs->trans('VATCalculatorCustomerInvoiceDesc'); ?></td>
                    <td><input type="checkbox" name="customer_invoice" <?php echo $configs['customer_invoice'] == '1' ? 'checked' : '' ?>/></td>
                </tr>
                <tr>
                    <td><?php echo $langs->trans('VATCalculatorContract'); ?></td>
                    <td><?php echo $langs->trans('VATCalculatorContractDesc'); ?></td>
                    <td><input type="checkbox" name="contract" <?php echo $configs['contract'] == '1' ? 'checked' : '' ?>/></td>
                </tr>
                <tr>
                    <td><?php echo $langs->trans('VATCalculatorSupplierOrder'); ?></td>
                    <td><?php echo $langs->trans('VATCalculatorSupplierOrderDesc'); ?></td>
                    <td><input type="checkbox" name="supplier_order" <?php echo $configs['supplier_order'] == '1' ? 'checked' : '' ?>/></td>
                </tr>
                <tr>
                    <td><?php echo $langs->trans('VATCalculatorSupplierInvoice'); ?></td>
                    <td><?php echo $langs->trans('VATCalculatorSupplierInvoiceDesc'); ?></td>
                    <td><input type="checkbox" name="supplier_invoice" <?php echo $configs['supplier_invoice'] == '1' ? 'checked' : '' ?>/></td>
                </tr>
            </tbody>
        </table>
        <input type="hidden" name="form_name" value="vatcalculator_config" />
        <div class="right">
            <input type="submit" class="button" value="<?php echo $langs->trans('Save'); ?>" />
        </div>
    </form>
<!-- </div> -->
<?php

llxFooter();
