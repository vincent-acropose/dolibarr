<?php
/* Copyright (C) 2011 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014	Philippe Grand	<philippe.grand@atoo-net.com>
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
 */

/**
 *  \file       ultimatepdf/admin/shipments.php
 *  \ingroup    ultimatepdf
 *  \brief      Page d'administration/configuration du module ultimatepdf
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once("../lib/ultimatepdf.lib.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("sendings");
$langs->load("deliveries");
$langs->load("ultimatepdf@ultimatepdf");

// Security check
if (! $user->admin) accessforbidden();

$action	= GETPOST('action');


/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if (preg_match('/del_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

/*
 * View
 */

llxHeader('',$langs->trans("UltimatepdfSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("UltimatepdfSetup"),$linkback,'ultimatepdf@ultimatepdf');

$head = ultimatepdf_prepare_head();

dol_fiche_head($head, 'shipments', $langs->trans("ModuleSetup"), 0, "ultimatepdf@ultimatepdf");


/*
 * Formulaire parametres divers
 */
 
 print_fiche_titre($langs->trans("UltimatepdfSpecificShipments"),'','').'<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// display sale representative signature within shipments note . 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE');
}
else
{
	if($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_ADD_SALE_REP_SIGNATURE_IN_SHIPMENT_NOTE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

print '</table>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// add barcode at top within shipments. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultLSLRInsideShipmentsUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATE_DOCUMENTS_WITH_LSLR');
}
else
{
	if($conf->global->ULTIMATE_DOCUMENTS_WITH_LSLR == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATE_DOCUMENTS_WITH_LSLR">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATE_DOCUMENTS_WITH_LSLR == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATE_DOCUMENTS_WITH_LSLR">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// add barcode at top within shipments. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultBarcodeAtTopInsideShipmentsUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_BARCODE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// add QRcode at top within Shipments. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultQRcodeAtTopInsideShipmentsUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_TOP_QRCODE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// add my comp QRcode at top within Shipments. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultMycompQRcodeAtTopInsideShipmentsUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_SHIPMENTS_WITH_MYCOMP_QRCODE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

print '</table>';

$db->close();

llxFooter();
?>
