<?php

/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2013 	   Philippe Grand       <philippe.grand@atoo-net.com>
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
 * 	\file       ultimateline/admin/settings.php
 * 	\ingroup    ultimateline
 * 	\brief      Ultimateline module setup page
 */
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");	// For "custom" directory
require_once(DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php');
require_once(DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
dol_include_once('/ultimateline/core/lib/ultimateline.lib.php');
dol_include_once('/ultimateline/class/ultimateLine.class.php');

global $conf, $langs;

$langs->load("admin");
$langs->load("ultimateline@ultimateline");

if (!$user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');
$seeDetail = false;

$ultimateLine = new UltimateLine($db);


$db->begin();

$error = 0;


$id = GETPOST('id', 'int');

if (isset($_POST['submit_affect']))
{
    $serviceId = GETPOST('ultimateline_service', 'int', 2);
    $serviceType = GETPOST('ultimateline_type', 'int', 2);
    $serviceValue = GETPOST('ultimateline_value', '', 2);
    $serviceValue = str_replace(',', '.', $serviceValue);
    $serviceValue = is_numeric($serviceValue) ? $serviceValue : '';

    if ($serviceId > 0 && !empty($serviceValue))
    {
        $ultimateLine->id = $serviceId;
        $ultimateLine->type = $serviceType;
        $ultimateLine->value = $serviceValue;

        if (!($ultimateLine->create($user) > 0))
            $error++;
        
        // Delete any line where this service is target
        if (!($ultimateLine->delete_lines(array(), $ultimateLine->id)))
            $error++;
        
        if(!$error)
            $id = $ultimateLine->id;
    }
}

if ($id > 0)
{

    $ultimateLine->fetch($id);
    $ultimateLine->fetch_lines();
}

if (isset($_POST['submit_see_details']) && $id > 0)
{
    $seeDetail = true;
}
 
if (isset($_POST['submit_update']))
{
    // remove lines
    $idsToRemoveJson = GETPOST('target_json', '', 2);
    if (!empty($idsToRemoveJson))
        if (!($ultimateLine->delete_lines(json_decode($idsToRemoveJson)) > 0))
            $error++;

    // diff to add
    $idsToAddJson = GETPOST('linked_json', '', 2);
    $idsToAddJson = array_diff(json_decode($idsToAddJson), $ultimateLine->lines);

    if (!empty($idsToAddJson))
        if (!($ultimateLine->create_lines($idsToAddJson, $user) > 0))
            $error++;

    // add diffs

    $seeDetail = true;
}

if ($action == 'delete')
{
    if (!($ultimateLine->delete($user)))
        $error++;
}

if ($action=='addallproduct') {
	
	$result=$ultimateLine->associateAllProduct();
	if ($result<0) {
		setEventMessage($ultimateLine->error,'errors');
	}
	
	$seeDetail = true;
}

if (!$error)
{
    $db->commit();
}
else
{
    $db->rollback();
}



/*
 * Actions
 */
$allProductsAndServices = $ultimateLine->getProducts();
$ultimates = $allProductsAndServices['ultimate_services'];
$usables = $allProductsAndServices['usable_services'];
$products = $allProductsAndServices['products'];
$ultimatesTypes = $ultimateLine->getTypes();

/*
 * View
 */

$extrajs = array('/ultimateline/js/settings.js');

$form = new Form($db);
$htmltooltip ='';
$htmltooltip = $langs->trans("UltimatelineServiceInfo");
$help_url = '';
llxHeader('', $langs->trans("Configuration"), $help_url, '', '', '', $extrajs, '', 0, 0);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("UltimatelineSetup"),$linkback,'ultimateline@ultimateline');

$head = ultimateline_prepare_head();

dol_fiche_head($head, 'options', $langs->trans("Module300000Name"), 0, "ultimateline@ultimateline");


//---- Defining a service as a ultimate one block ----//
$fieldset = array();
$fieldset['formname'] = 'affect_form';
$fieldset['formaction'] = $_SERVER['PHP_SELF'];
$fieldset['options'] = 'style="width:80%;padding-top:20px;"';
$fieldset['legend'] = array(
        'options' => '',
        'html' => $langs->trans('SetNewServiceData')
);
$fieldset['hiddens'] = array(
        array(
                'name' => '',
                'value' => ''
        )
);

// Set fieldset table's
$fieldset['tables'][] = array(
        'alt_lines_bg' => false,
        'print_lines_bg' => false,
        'options' => 'class="" width="100%"',
        'lines' => array(
                array(
                        'options' => '',
                        'cells' => array(
                                array(
                                        'html' => $langs->trans('ChooseAService'),
                                        'options' => 'width="40%"'
                                ),
                                array(
                                        'html' => $form->selectarray('ultimateline_service', $usables['labels'], -1, 0),
                                        'options' => ''
                                ),
								array(
                                        'html' => $form->textwithpicto('',$htmltooltip,1,0),
                                        'options' => ''
                                )
                        )
                ),
                array(
                        'options' => '',
                        'cells' => array(
                                array(
                                        'html' => $langs->trans('ChooseType'),
                                        'options' => 'width="40%"'
                                ),
                                array(
                                        'html' => $form->selectarray('ultimateline_type', $ultimatesTypes, 0, 0),
                                        'options' => ''
                                )
                        )
                ),
                array(
                        'options' => '',
                        'cells' => array(
                                array(
                                        'html' => $langs->trans('DefineValue'),
                                        'options' => 'width="40%"'
                                ),
                                array(
                                        'html' => '<input type="text" name="ultimateline_value" value="" />',
                                        'options' => ''
                                )
                        )
                ),
                array(
                        'options' => '',
                        'cells' => array(
                                array(
                                        'html' => '<input type="submit" class="button" name="submit_affect" value="' . $langs->trans('Affect') . '" />',
                                        'options' => 'align="right" colspan="2"'
                                )
                        )
                )
        )
);
include ('./tpl/fieldset.tpl.php');

//---- Details and param of a ultimate service ----//

$fieldset = array();
$fieldset['formname'] = 'show_form';
$fieldset['formaction'] = $_SERVER['PHP_SELF'];
$fieldset['options'] = 'style="width:80%;padding-top:20px;" id="selected_ultimate_fieldset"';
$fieldset['legend'] = array(
        'options' => '',
        'html' => $langs->trans('ServiceDataDetails')
);
$fieldset['hiddens'] = array(
        array(
                'name' => '',
                'value' => ''
        )
);

// Set fieldset table's
$fieldset['tables'][] = array(
        'alt_lines_bg' => false,
        'print_lines_bg' => false,
        'options' => 'class="" width="100%"',
        'lines' => array(
                array(
                        'options' => '',
                        'cells' => array(
                                array(
                                        'html' => $langs->trans('SelectAUltimateService'),
                                        'options' => 'width="40%"'
                                ),
                                array(
                                        'html' => $form->selectarray('id', $ultimates['labels'], ($seeDetail ? $id : -1), 0),
                                        'options' => 'align="left"'
                                )
                        )
                ),
                array(
                        'options' => '',
                        'cells' => array(
                                array(
                                        'html' => '<input type="submit" class="button" name="submit_see_details" value="' . $langs->trans('SeeDetails') . '" />',
                                        'options' => 'align="right" colspan="2"'
                                )
                        )
                )
        )
);
include ('./tpl/fieldset.tpl.php');


if ($seeDetail)
{
    $ultimateLine->fetch($id);
    $ultimateLine->fetch_lines();
    // Prepare target/linked options html
    $targetServicesOptions = '';
    $linkedServicesOptions = '';

    $linkedHiddenArray = array();
    $targetHiddenArray = array();
    foreach (array_diff_key($ultimateLine->lines, array(-1 => '')) as $linkedId)
    {
        $linkedServicesOptions.= '<option value="' . $linkedId . '">' . $products['labels'][$linkedId] . '</option>';
        $linkedHiddenArray[] = $linkedId;
    }
    foreach (array_diff_key($products['labels'], array_flip($ultimateLine->lines), array(-1 => '')) as $targetId => $targetLabel)
    {
        $targetServicesOptions.= '<option value="' . $targetId . '">' . $targetLabel . '</option>';
        $targetHiddenArray[] = $targetId;
    }

//---- Hidden fieldset containing details  ----//
    $fieldset = array();
    $fieldset['formname'] = 'details_form';
    $fieldset['formaction'] = $_SERVER['PHP_SELF'];
    $fieldset['options'] = 'style="width:80%;padding-top:20px;" id="details_ultimate_fieldset" ';
    $fieldset['legend'] = array(
            'options' => '',
            'html' => $langs->trans('ServiceDataDetails')
    );
    $fieldset['hiddens'] = array(
            array(
                    'name' => 'id',
                    'value' => $ultimateLine->id
            ),
            array(
                    'name' => 'linked_json',
                    'value' => htmlentities(json_encode($linkedHiddenArray))
            ),
            array(
                    'name' => 'target_json',
                    'value' => htmlentities(json_encode($targetHiddenArray))
            )
    );

// Details table
    $fieldset['tables'][] = array(
            'alt_lines_bg' => false,
            'print_lines_bg' => false,
            'options' => 'class="" width="30%"',
            'labels' => array(
                    array(
                            'html' => $langs->trans('UltimateDetails'),
                            'options' => 'width="100%" colspan="2"'
                    )
            ),
            'lines' => array(
                    array(
                            'options' => '',
                            'cells' => array(
                                    array(
                                            'html' => $langs->trans('ServiceRef'),
                                            'options' => 'height="60px" width="60%"'
                                    ),
                                    array(
                                            'html' => $ultimates['references'][$ultimateLine->id],
                                            'options' => 'align="right"'
                                    )
                            )
                    ),
                    array(
                            'options' => '',
                            'cells' => array(
                                    array(
                                            'html' => $langs->trans('ServiceLabel'),
                                            'options' => 'height="60px width="60%"'
                                    ),
                                    array(
                                            'html' => $ultimates['labels'][$ultimateLine->id],
                                            'options' => 'align="right"'
                                    )
                            )
                    ),
                    array(
                            'options' => '',
                            'cells' => array(
                                    array(
                                            'html' => $langs->trans('ServiceType'),
                                            'options' => 'height="60px width="60%"'
                                    ),
                                    array(
                                            'html' => $ultimatesTypes[$ultimateLine->type],
                                            'options' => 'align="right"'
                                    )
                            )
                    ),
                    array(
                            'options' => '',
                            'cells' => array(
                                    array(
                                            'html' => $langs->trans('ServiceValue'),
                                            'options' => 'height="60px width="60%"'
                                    ),
                                    array(
                                            'html' => $ultimateLine->value,
                                            'options' => 'align="right"'
                                    )
                            )
                    )
            )
    );


// Currently associated select multiple
    $fieldset['tables'][] = array(
            'alt_lines_bg' => false,
            'print_lines_bg' => false,
            'options' => 'class="" style="margin-left:10%" width="25%"',
            'labels' => array(
                    array(
                            'html' => $langs->trans('LinkedServices'),
                            'options' => 'width="100%" colspan="2" align="center"'
                    )
            ),
            'lines' => array(
                    array(
                            'options' => '',
                            'cells' => array(
                                    array(
                                            'html' => '<select id="select_linked" style="height:500px;width:100%" multiple="multiple" name="linked_products">' . $linkedServicesOptions . '</select>',
                                            'options' => 'align="right"'
                                    )
                            )
                    )
            )
    );

// To link select multiple
    $fieldset['tables'][] = array(
            'alt_lines_bg' => false,
            'print_lines_bg' => false,
            'options' => ' width="10%" height="500px"',
            'lines' => array(
                    array(
                            'options' => '',
                            'cells' => array(
                                    array(
                                            'html' => '<span id="rem_button">' . img_picto($langs->trans("Remove"), 'next') . '</span>',
                                            'options' => 'align="center"  valign="bottom"'
                                    )
                            )
                    ),
                    array(
                            'options' => '',
                            'cells' => array(
                                    array(
                                            'html' => '<span id="add_button">' . img_picto($langs->trans("Add"), 'previous') . '</span>', //'<input type="button" name="add_selected" value="'. $langs->trans('Add').'"/>',
                                            'options' => 'align="center"  valign="top"'
                                    )
                            )
                    )
            )
    );



// Currently associated select multiple
    $fieldset['tables'][] = array(
            'alt_lines_bg' => false,
            'print_lines_bg' => false,
            'options' => 'class="" width="25%"',
            'labels' => array(
                    array(
                            'html' => $langs->trans('TargetServices'),
                            'options' => 'width="100%" colspan="2" align="center"'
                    )
            ),
            'lines' => array(
                    array(
                            'options' => '',
                            'cells' => array(
                                    array(
                                            'html' => '<select id="select_target" style="height:500px;width:100%" multiple="multiple" name="target_products">' . $targetServicesOptions . '</select>',
                                            'options' => 'align="left"'
                                    )
                            )
                    ),
            )
    );

    // Currently associated select multiple
    $fieldset['tables'][] = array(
            'alt_lines_bg' => false,
            'print_lines_bg' => false,
            'options' => 'class="" width="100%" style="margin-top:15px;"',
            'lines' => array(
                    array(
                            'options' => '',
                            'cells' => array(
                                    array(
                                            'html' => '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $ultimateLine->id . '&amp;action=addallproduct">' . $langs->trans('AddAllProduct') . '</a>'.'<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $ultimateLine->id . '&amp;action=delete">' . $langs->trans('Delete') . '</a>' . '<input class="button" type="submit" name="submit_update" value="' . $langs->trans('Apply') . '" />',
                                            'options' => 'align="right"'
                                    )
                            )
                    ),
            )
    );

    include ('./tpl/fieldset.tpl.php');
}
dol_fiche_end();

$db->close();

llxFooter('');
?>
