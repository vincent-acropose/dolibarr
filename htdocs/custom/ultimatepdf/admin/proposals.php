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
 *  \file       ultimatepdf/admin/proposals.php
 *  \ingroup    ultimatepdf
 *  \brief      Page d'administration/configuration du module ultimatepdf
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once("../lib/ultimatepdf.lib.php");

$langs->load("admin");
$langs->load("propal");
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

dol_fiche_head($head, 'proposals', $langs->trans("ModuleSetup"), 0, "ultimatepdf@ultimatepdf");


/*
 * Formulaire parametres divers
 */
 
 print_fiche_titre($langs->trans("UltimatepdfSpecificProposals"),'','').'<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

 // display fold mark. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultFoldMarkInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_DISPLAY_PROPOSALS_FOLD_MARK');
}
else
{
	if($conf->global->MAIN_DISPLAY_PROPOSALS_FOLD_MARK == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_DISPLAY_PROPOSALS_FOLD_MARK">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_DISPLAY_PROPOSALS_FOLD_MARK == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_DISPLAY_PROPOSALS_FOLD_MARK">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

 // display sale representative signature within proposal note . 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultSaleRepSignatureInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_ADD_SALE_REP_SIGNATURE_IN_PROPAL_NOTE');
}
else
{
	if($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_PROPAL_NOTE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_ADD_SALE_REP_SIGNATURE_IN_PROPAL_NOTE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_PROPAL_NOTE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_ADD_SALE_REP_SIGNATURE_IN_PROPAL_NOTE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

 // display agreement bloc 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultAgreementBlockInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_DISPLAY_PROPOSAL_AGREEMENT_BLOCK');
}
else
{
	if($conf->global->MAIN_DISPLAY_PROPOSAL_AGREEMENT_BLOCK == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_DISPLAY_PROPOSAL_AGREEMENT_BLOCK">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_DISPLAY_PROPOSAL_AGREEMENT_BLOCK == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_DISPLAY_PROPOSAL_AGREEMENT_BLOCK">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// add photos within proposals. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultPhotosInsidePropalUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATE_GENERATE_PROPOSALS_WITH_PICTURE');
}
else
{
	if($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_PICTURE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATE_GENERATE_PROPOSALS_WITH_PICTURE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATE_GENERATE_PROPOSALS_WITH_PICTURE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATE_GENERATE_PROPOSALS_WITH_PICTURE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// add Bank details within proposals. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideByDefaultBankDetailsInsidePropalUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATE_BANK_HIDE_DETAILS_WITHIN_PROPOSALS');
}
else
{
	if($conf->global->ULTIMATE_BANK_HIDE_DETAILS_WITHIN_PROPOSALS == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATE_BANK_HIDE_DETAILS_WITHIN_PROPOSALS">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATE_BANK_HIDE_DETAILS_WITHIN_PROPOSALS == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATE_BANK_HIDE_DETAILS_WITHIN_PROPOSALS">'.img_picto($langs->trans("Enabled"),'on').'</a>';
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

// add barcode at top within proposals. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultBarcodeAtTopInsidePropalUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_BARCODE');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_BARCODE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_BARCODE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_BARCODE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_BARCODE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// add QRcode at top within proposals. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultQRcodeAtTopInsidePropalUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_QRCODE');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_QRCODE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_QRCODE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_QRCODE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_TOP_QRCODE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// add my comp QRcode at top within proposals. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultMycompQRcodeAtTopInsidePropalUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MYCOMP_QRCODE');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MYCOMP_QRCODE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MYCOMP_QRCODE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MYCOMP_QRCODE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MYCOMP_QRCODE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// add "Auto-liquidation régime de la sous-traitance" within proposals. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultAutoLiquidationInsideProposalsUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_PROPOSALS_WITH_AUTO_LIQUIDATION');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_AUTO_LIQUIDATION == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_AUTO_LIQUIDATION">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_AUTO_LIQUIDATION == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_AUTO_LIQUIDATION">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// add merge of pdf files within proposals. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultMergePdfInsideProposalsUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MERGED_PDF');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MERGED_PDF == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MERGED_PDF">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MERGED_PDF == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_PROPOSALS_WITH_MERGED_PDF">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

print '</table>';

$db->close();

llxFooter();
?>
