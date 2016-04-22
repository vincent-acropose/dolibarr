<?php
/* Copyright (C) 2014	Charles-Fr BENKE	<charles.fr@benke.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * \file htdocs/customtabs/extrafields.php
 * \ingroup member
 * \brief Page to setup extra fields of group members
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include ($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory

require_once 'core/lib/customtabs.lib.php';
require_once 'class/customtabs.class.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

$langs->load("customtabs@customtabs");
$langs->load("admin");

$rowid = GETPOST('rowid', 'int');

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label = ExtraFields::$type2label;
// $tmptype2label=getStaticMember(get_class($extrafields),'type2label');
$type2label = array (
		'' 
);
foreach ( $tmptype2label as $key => $val )
	$type2label[$key] = $langs->trans($val);

$action = GETPOST('action', 'alpha');
$attrname = GETPOST('attrname', 'alpha');

$customtabs = new Customtabs($db);

$customtabs->fetch($rowid);

$elementtype = "cust_" . $customtabs->tablename;

// Security check
$result = restrictedArea($user, 'customtabs', $rowid, '');

/*
 * Actions
 */
$maxsizestring = 255;
$maxsizeint = 10;
$mesg = Array ();

$extrasize = GETPOST('size');
if (GETPOST('type') == 'double' && strpos($extrasize, ',') === false)
	$extrasize = '24,8';
if (GETPOST('type') == 'date')
	$extrasize = '';
if (GETPOST('type') == 'datetime')
	$extrasize = '';
	
	// Add attribute
if ($action == 'add') {
	if ($_POST["button"] != $langs->trans("Cancel")) {
		// Check values
		if (! GETPOST('type')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorFieldRequired", $langs->trans("Type"));
			$action = 'create';
		}
		
		if (GETPOST('type') == 'varchar' && $extrasize > $maxsizestring) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorSizeTooLongForVarcharType", $maxsizestring);
			$action = 'create';
		}
		if (GETPOST('type') == 'int' && $extrasize > $maxsizeint) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorSizeTooLongForIntType", $maxsizeint);
			$action = 'create';
		}
		if (GETPOST('type') == 'select' && ! GETPOST('param')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorNoValueForSelectType");
			$action = 'create';
		}
		if (GETPOST('type') == 'sellist' && ! GETPOST('param')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorNoValueForSelectListType");
			$action = 'create';
		}
		if (GETPOST('type') == 'checkbox' && ! GETPOST('param')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorNoValueForCheckBoxType");
			$action = 'create';
		}
		if (GETPOST('type') == 'radio' && ! GETPOST('param')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorNoValueForRadioType");
			$action = 'create';
		}
		
		if (((GETPOST('type') == 'radio') || (GETPOST('type') == 'checkbox') || (GETPOST('type') == 'radio')) && GETPOST('param')) {
			// Construct array for parameter (value of select list)
			$parameters = GETPOST('param');
			$parameters_array = explode("\r\n", $parameters);
			foreach ( $parameters_array as $param_ligne ) {
				if (! empty($param_ligne)) {
					if (preg_match_all('/,/', $param_ligne, $matches)) {
						if (count($matches[0]) > 1) {
							$error ++;
							$langs->load("errors");
							$mesg = $langs->trans("ErrorBadFormatValueList", $param_ligne);
							$action = 'create';
						}
					} else {
						$error ++;
						$langs->load("errors");
						$mesg = $langs->trans("ErrorBadFormatValueList", $param_ligne);
						$action = 'create';
					}
				}
			}
		}
		if (! $error) {
			// Type et taille non encore pris en compte => varchar(255)
			if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/", $_POST['attrname'])) {
				// Construct array for parameter (value of select list)
				$default_value = GETPOST('default_value');
				$parameters = GETPOST('param');
				$parameters_array = explode("\r\n", $parameters);
				foreach ( $parameters_array as $param_ligne ) {
					list ( $key, $value ) = explode(',', $param_ligne);
					$params['options'][$key] = $value;
				}
				$result = $extrafields->addExtraField($_POST['attrname'], $_POST['label'], $_POST['type'], $_POST['pos'], $extrasize, $elementtype, (GETPOST('unique') ? 1 : 0), (GETPOST('required') ? 1 : 0), $default_value, $params);
				if ($result > 0) {
					header("Location: " . $_SERVER["PHP_SELF"] . "?rowid=" . $rowid);
					exit();
				} else {
					$error ++;
					$mesg = $extrafields->error;
				}
			} else {
				$error ++;
				$langs->load("errors");
				$mesg = $langs->trans("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("AttributeCode"));
				$action = 'create';
			}
		}
	}
}

// Rename field
if ($action == 'update') {
	if ($_POST["button"] != $langs->trans("Cancel")) {
		// Check values
		if (! GETPOST('type')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorFieldRequired", $langs->trans("Type"));
			$action = 'create';
		}
		if (GETPOST('type') == 'varchar' && $extrasize > $maxsizestring) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorSizeTooLongForVarcharType", $maxsizestring);
			$action = 'edit';
		}
		if (GETPOST('type') == 'int' && $extrasize > $maxsizeint) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorSizeTooLongForIntType", $maxsizeint);
			$action = 'edit';
		}
		if (GETPOST('type') == 'select' && ! GETPOST('param')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorNoValueForSelectType");
			$action = 'edit';
		}
		if (GETPOST('type') == 'sellist' && ! GETPOST('param')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorNoValueForSelectListType");
			$action = 'edit';
		}
		if (GETPOST('type') == 'checkbox' && ! GETPOST('param')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorNoValueForCheckBoxType");
			$action = 'edit';
		}
		if (GETPOST('type') == 'radio' && ! GETPOST('param')) {
			$error ++;
			$langs->load("errors");
			$mesg = $langs->trans("ErrorNoValueForRadioType");
			$action = 'edit';
		}
		if (((GETPOST('type') == 'radio') || (GETPOST('type') == 'checkbox') || (GETPOST('type') == 'radio')) && GETPOST('param')) {
			// Construct array for parameter (value of select list)
			$parameters = GETPOST('param');
			$parameters_array = explode("\r\n", $parameters);
			foreach ( $parameters_array as $param_ligne ) {
				if (! empty($param_ligne)) {
					if (preg_match_all('/,/', $param_ligne, $matches)) {
						if (count($matches[0]) > 1) {
							$error ++;
							$langs->load("errors");
							$mesg = $langs->trans("ErrorBadFormatValueList", $param_ligne);
							$action = 'edit';
						}
					} else {
						$error ++;
						$langs->load("errors");
						$mesg = $langs->trans("ErrorBadFormatValueList", $param_ligne);
						$action = 'edit';
					}
				}
			}
		}
		if (! $error) {
			$pos = GETPOST('pos', 'int');
			// Construct array for parameter (value of select list)
			$parameters = GETPOST('param');
			$parameters_array = explode("\r\n", $parameters);
			foreach ( $parameters_array as $param_ligne ) {
				list ( $key, $value ) = explode(',', $param_ligne);
				$params['options'][$key] = $value;
			}
			if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/", $_POST['attrname'])) {
				$result = $extrafields->update($_POST['attrname'], $_POST['label'], $_POST['type'], $extrasize, $elementtype, (GETPOST('unique') ? 1 : 0), (GETPOST('required') ? 1 : 0), $pos, $params);
				if ($result > 0) {
					header("Location: " . $_SERVER["PHP_SELF"] . "?rowid=" . $rowid);
					exit();
				} else {
					$error ++;
					$mesg = $extrafields->error;
				}
			} else {
				$error ++;
				$langs->load("errors");
				$mesg = $langs->trans("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("AttributeCode"));
			}
		}
	}
}

// Delete attribute
if ($action == 'delete') {
	if (isset($_GET["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/", $_GET["attrname"])) {
		$result = $extrafields->delete($_GET["attrname"], $elementtype);
		if ($result >= 0) {
			header("Location: " . $_SERVER["PHP_SELF"] . "?rowid=" . $rowid);
			exit();
		} else
			$mesg = $extrafields->error;
	} else {
		$error ++;
		$langs->load("errors");
		$mesg = $langs->trans("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("AttributeCode"));
	}
}

/*
 * View
 */

$textobject = $langs->transnoentitiesnoconv("CustomTabs");

$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';
llxHeader('', $langs->trans("MembersSetup"), $help_url);

// print_fiche_titre($langs->trans("MembersSetup"),$linkback,'setup');

$head = customtabs_prepare_head($customtabs);

dol_fiche_head($head, 'attributes', $langs->trans("CustomTabs"), 0, 'user');

print $langs->trans("DefineHereCustomTabsAttributes", $textobject) . '<br>' . "\n";
print '<br>';

dol_htmloutput_errors($mesg);

$linkback = '<a href="' . DOL_URL_ROOT . '/customtabs/list.php">' . $langs->trans("BackToList") . '</a>';

print '<table class="border" width="100%">';
// Ref
print '<tr><td width="15%">' . $langs->trans("Ref") . '</td>';
print '<td>';
print $form->showrefnav($customtabs, 'rowid', $linkback, 1, 'rowid', 'rowid', '');
print '</td></tr>';

// tablename
print '<tr><td width="15%">' . $langs->trans("TableName") . '</td><td>' . $customtabs->tablename . '</td></tr>';

// Label
print '<tr><td width="15%">' . $langs->trans("Label") . '</td><td>' . $customtabs->libelle . '</td></tr>';
print '</table></br><br>';

// Load attribute_label
$extrafields->fetch_name_optionals_label($elementtype);

print '<table summary="listofattributes" class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Label") . '</td>';
print '<td>' . $langs->trans("AttributeCode") . '</td>';
print '<td>' . $langs->trans("Type") . '</td>';
print '<td align="right">' . $langs->trans("Size") . '</td>';
print '<td align="center">' . $langs->trans("Unique") . '</td>';
print '<td align="center">' . $langs->trans("Required") . '</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

$var = True;
foreach ( $extrafields->attribute_type as $key => $value ) {
	$var = ! $var;
	print "<tr " . $bc[$var] . ">";
	print "<td>" . $extrafields->attribute_label[$key] . "</td>\n";
	print "<td>" . $key . "</td>\n";
	print "<td>" . $type2label[$extrafields->attribute_type[$key]] . "</td>\n";
	print '<td align="right">' . $extrafields->attribute_size[$key] . "</td>\n";
	print '<td align="center">' . yn($extrafields->attribute_unique[$key]) . "</td>\n";
	print '<td align="center">' . yn($extrafields->attribute_required[$key]) . "</td>\n";
	print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edit&attrname=' . $key . '&rowid=' . $rowid . '">' . img_edit() . '</a>';
	print '&nbsp; <a href="' . $_SERVER["PHP_SELF"] . '?action=delete&attrname=' . $key . '&rowid=' . $rowid . '">' . img_delete() . "</a></td>\n";
	print "</tr>";
	// $i++;
}

print "</table>";

dol_fiche_end();

// Buttons
if ($action != 'create' && $action != 'edit') {
	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=create&rowid=' . $rowid . '">' . $langs->trans("NewAttribute") . "</a>";
	print "</div>";
}

/* ************************************************************************** */
/*                                                                            */
/* Creation d'un champ optionnel
 /*                                                                            */
/* ************************************************************************** */

if ($action == 'create') {
	print "<br>";
	print_titre($langs->trans('NewAttribute'));
	
	require 'tpl/admin_extrafields_add.tpl.php';
	// require DOL_DOCUMENT_ROOT.'/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************** */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'edit' && ! empty($attrname)) {
	print "<br>";
	print_titre($langs->trans("FieldEdition", $attrname));
	
	require 'tpl/admin_extrafields_edit.tpl.php';
}

llxFooter();

$db->close();
?>
