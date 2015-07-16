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
 *  \file       htdocs/custom/ultimatepdf/admin/options.php
 *  \ingroup    ultimatepdf
 *  \brief      Page d'administration/configuration du module ultimatepdf
 */

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");					// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formbarcode.class.php");
require_once("../lib/ultimatepdf.lib.php");

$langs->load("admin");
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

if ($action == 'GENBARCODE_BARCODETYPE_THIRDPARTY')
{
	$coder_id = GETPOST('coder_id','alpha');
	$res = dolibarr_set_const($db, "GENBARCODE_BARCODETYPE_THIRDPARTY", $coder_id,'chaine',0,'',$conf->entity);
}

/*
 * View
 */

$formbarcode = new FormBarCode($db);

llxHeader('',$langs->trans("UltimatepdfSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("UltimatepdfSetup"),$linkback,'ultimatepdf@ultimatepdf');

$head = ultimatepdf_prepare_head();

dol_fiche_head($head, 'options', $langs->trans("ModuleSetup"), 0, "ultimatepdf@ultimatepdf");

// Addresses
print_fiche_titre($langs->trans("PDFAddressForging"),'','').'<br>';
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// add also details for contact address. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowAlsoTargetDetails").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_PDF_ADDALSOTARGETDETAILS');
}
else
{
	if($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_PDF_ADDALSOTARGETDETAILS">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_PDF_ADDALSOTARGETDETAILS">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// hide TVA intra within address. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideTvaIntraWithinAddress").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_TVAINTRA_NOT_IN_ADDRESS');
}
else
{
	if($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_TVAINTRA_NOT_IN_ADDRESS">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_TVAINTRA_NOT_IN_ADDRESS">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

print_fiche_titre($langs->trans("PDFColumnForging"),'','').'<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

/*
 * Formulaire parametres fabrication des colonnes
 */
 
 // Hide product description 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideByDefaultProductDescInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_HIDE_DESC');
}
else
{
	if($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_GENERATE_DOCUMENTS_HIDE_DESC">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_GENERATE_DOCUMENTS_HIDE_DESC">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// Hide product reference 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideByDefaultProductRefInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_HIDE_REF');
}
else
{
	if($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_GENERATE_DOCUMENTS_HIDE_REF">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_GENERATE_DOCUMENTS_HIDE_REF">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// Hide product details 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideBydefaultProductDetailsInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS');
}
else
{
	if($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// Hide product VAT column
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideBydefaultProductVATColumnInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATE_SHOW_HIDE_VAT_COLUMN');
}
else
{
	if($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATE_SHOW_HIDE_VAT_COLUMN">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATE_SHOW_HIDE_VAT_COLUMN == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATE_SHOW_HIDE_VAT_COLUMN">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// Hide product PUHT
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideBydefaultProductPUHTInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATE_SHOW_HIDE_PUHT');
}
else
{
	if($conf->global->ULTIMATE_SHOW_HIDE_PUHT == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATE_SHOW_HIDE_PUHT">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATE_SHOW_HIDE_PUHT">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// Hide product QTY
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideBydefaultProductQtyInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATE_SHOW_HIDE_QTY');
}
else
{
	if($conf->global->ULTIMATE_SHOW_HIDE_PUHT == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATE_SHOW_HIDE_QTY">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATE_SHOW_HIDE_PUHT == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATE_SHOW_HIDE_QTY">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// Hide product Total HT
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideBydefaultProductTHTInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATE_SHOW_HIDE_THT');
}
else
{
	if($conf->global->ULTIMATE_SHOW_HIDE_PUHT == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATE_SHOW_HIDE_THT">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATE_SHOW_HIDE_THT == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATE_SHOW_HIDE_THT">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

/*
 * Formulaire parametres divers
 */
 
 print_fiche_titre($langs->trans("UltimatepdfMiscellaneous"),'','').'<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';
 
 // add dash between lines. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultDashBetweenLinesInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_PDF_DASH_BETWEEN_LINES');
}
else
{
	if($conf->global->MAIN_PDF_DASH_BETWEEN_LINES == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_PDF_DASH_BETWEEN_LINES">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_PDF_DASH_BETWEEN_LINES == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_PDF_DASH_BETWEEN_LINES">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

 // do not repeat header. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DoNotRepeatHeadInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_PDF_DONOTREPEAT_HEAD');
}
else
{
	if($conf->global->MAIN_PDF_DONOTREPEAT_HEAD == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_PDF_DONOTREPEAT_HEAD">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_PDF_DONOTREPEAT_HEAD == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_PDF_DONOTREPEAT_HEAD">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// use autowrap on free text. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UseAutowrapOnFreeTextInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('MAIN_USE_AUTOWRAP_ON_FREETEXT');
}
else
{
	if($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_USE_AUTOWRAP_ON_FREETEXT">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_USE_AUTOWRAP_ON_FREETEXT">'.img_picto($langs->trans("Enabled"),'on').'</a>';
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

// add barcode at bottom within proposals. 
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ShowByDefaultBarcodeAtBottomInsideUltimatepdf").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE');
}
else
{
	if($conf->global->ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ULTIMATEPDF_GENERATE_DOCUMENTS_WITH_BOTTOM_BARCODE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

// Module thirdparty
if (! empty($conf->societe->enabled))
{
	$var=!$var;
	print "<form method=\"post\" action=\"".$_SERVER["PHP_SELF"]."\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"GENBARCODE_BARCODETYPE_THIRDPARTY\">";
	print "<tr ".$bc[$var].">";
	print '<td>'.$langs->trans("SetDefaultBarcodeTypeThirdParties").'</td>';
	print '<td width="60" align="right">';
	print $formbarcode->select_barcode_type($conf->global->GENBARCODE_BARCODETYPE_THIRDPARTY,"coder_id",1);
	print '</td><td align="right">';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</td>";
	print '</tr>';
	print '</form>';
}


print '</table>';

$db->close();

llxFooter();
?>
