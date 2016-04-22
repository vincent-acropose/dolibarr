<?php
/* Copyright (C) 2014-2015	Charlie BENKE	<charlie@patas-monkey.com>
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
 * \file htdocs/customtabs/tabs/liste.php
 * \ingroup customtabs
 * \brief Page of customtab mode list
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"))
	$res = @include ($_SERVER['DOCUMENT_ROOT'] . "/main.inc.php"); // Use on dev env only
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

require_once DOL_DOCUMENT_ROOT . '/customtabs/core/lib/customtabs.lib.php';
require_once DOL_DOCUMENT_ROOT . '/customtabs/class/customtabs.class.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

$langs->load("customtabs@customtabs");
$langs->load("users");
$langs->load('other');

$action = GETPOST('action', 'alpha');

$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');
if ($socid && ! $id)
	$id = $socid;

$ref = GETPOST('ref', 'alpha');

$confirm = GETPOST('confirm', 'alpha');
$tabsid = GETPOST('tabsid', 'int');

$filetoimport = GETPOST('filetoimport');

// on récupère la classe associé au complément si elle a été générée, pas encore d'actualité
// $file=DOL_DOCUMENT_ROOT.'/customtabs/compl_class/'.GETPOST("tabsid").'.class.php';
// if (file_exists($file))
// {
// $res=require_once $file;
// }

$form = new Form($db);

$customtabs = new CustomTabs($db);
$customtabs->fetch($tabsid);

$object = $customtabs->element_setting();
$result = $object->fetch($id, $ref);

if ($ref)
	$id = $object->id;

if ($action == 'sendit' && ! empty($conf->global->MAIN_UPLOAD_DOC)) {
	dol_mkdir($conf->customtabs->dir_temp);
	$nowyearmonth = dol_print_date(dol_now(), '%Y%m%d%H%M%S');
	
	$fullpath = $conf->customtabs->dir_temp . "/" . $nowyearmonth . '-' . $_FILES['userfile']['name'];
	if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $fullpath, 1) > 0) {
		dol_syslog("File " . $fullpath . " was added for import");
	} else {
		$langs->load("errors");
		setEventMessage($langs->trans("ErrorFailedToSaveFile"), 'errors');
	}
}

if ($action == 'importit') {
	$model = $format;
	// $liste=$objmodelimport->liste_modeles($db);
	
	// Create classe to use for import
	$dir = DOL_DOCUMENT_ROOT . "/core/modules/import/";
	$file = "import_csv.modules.php";
	$classname = "ImportCsv";
	require_once $dir . $file;
	$obj = new ImportCsv($db, $datatoimport);
	
	$obj->separator = $customtabs->csvseparator;
	$obj->enclosure = $customtabs->csvenclosure;
	
	// Load source fields in input file
	$fieldssource = array ();
	$result = $obj->import_open_file($conf->customtabs->dir_temp . '/' . $filetoimport, $langs);
	if ($result >= 0) {
		if ($customtabs->colnamebased != 2) // soit définit par le nom du champ, soit par le nom de la colonne
{
			// on se positionne sur la ligne contenant les entêtes
			$sourcelinenb = 0;
			
			while ( $sourcelinenb < $customtabs->colnameline ) {
				$sourcelinenb ++;
				// Read line till header
				$arrayrecord = $obj->import_read_record();
			}
			
			// Put into array fieldssource starting with 1.
			$nbcols = 1;
			foreach ( $arrayrecord as $key => $val ) {
				$fieldssource[$nbcols]['field'] = $val['val'];
				$fieldssource[$i]['colname'] = '';
				$nbcols ++;
			}
		}
		
		$extrafields = new ExtraFields($db);
		$extralabels = $extrafields->fetch_name_optionals_label($customtabs->table_element);
		// $res=$customtabs->fetch_optionalslist($id, $extralabels);
		for($i = 1; $i <= $nbcols; $i ++) {
			// on associe la colonne avec le champs dans le tableau
			switch ($customtabs->colnamebased) {
				case 0 :
					// plus compliqué on doit retrouver le nom de la colonne
					foreach ( $extralabels as $key => $label ) {
						if ($fieldssource[$i]['field'] == $label) {
							$fieldssource[$i]['colname'] = $key;
							$fieldssource[$i]['type'] = $extrafields->attribute_type[$key];
							break;
						}
					}
					break;
				case 1 :
					foreach ( $extralabels as $key => $label ) {
						if ($fieldssource[$i]['field'] == $key) {
							$fieldssource[$i]['colname'] = $key;
							$fieldssource[$i]['type'] = $extrafields->attribute_type[$label];
							break;
						}
					}
					break;
				case 2 :
					$fieldssource[$i]['colname'] = $i;
					break;
			}
		}
		
		// var_dump($fieldssource);
		
		$nboflines = dol_count_nb_of_line($conf->customtabs->dir_temp . '/' . $filetoimport);
		
		// on se positionne sur la ligne juste après celle de l'entete de colonnes
		$sourcelinenb = $customtabs->colnameline + 1;
		
		while ( $sourcelinenb < $nboflines && ! $endoffile ) {
			$sourcelinenb ++;
			// Read line and stor it into $arrayrecord
			$arrayrecord = $obj->import_read_record();
			if ($arrayrecord === false) {
				$arrayofwarnings[$sourcelinenb][0] = array (
						'lib' => 'File has ' . $nboflines . ' lines. However we reach end of file after record ' . $sourcelinenb . '. This may occurs when some records are split onto several lines.',
						'type' => 'EOF_RECORD_ON_SEVERAL_LINES' 
				);
				$endoffile ++;
				continue;
			}
			
			// on saute la premiere ligne
			if ($sourcelinenb <= $customtabs->colnameline) {
				// on récupère l'entete et on passe à la suite
				$arrayentete = $arrayrecord;
				continue;
			}
			
			// on fait le insert en base de la ligne
			$customtabs->importLine($fieldssource, $arrayrecord, $id);
		}
		// Close file
		$obj->import_close_file();
	}
}

// Delete file
if ($action == 'confirm_deletefile' && $confirm == 'yes') {
	$langs->load("other");
	
	$file = $conf->customtabs->dir_temp . '/' . GETPOST('urlfile'); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	$ret = dol_delete_file($file);
	if ($ret)
		setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	else
		setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	Header('Location: ' . $_SERVER["PHP_SELF"] . '?tabsid=' . $tabsid . '&id=' . $id);
	exit();
}

// export csv
if ($action == 'export_csv') {
	$sep = $conf->global->CUSTOMTABS_SEPARATORCSV;
	// if not set use default separator
	if (! $sep)
		$sep = ";";
		
		// Determine user rights according type of ressource
	$user_specials_rights = $customtabs->getUserSpecialsRights($user);
	
	$errmsg = '';
	$errmsgs = array ();
	
	// if something wrong in Load member and extrafields
	if ($result < 0) {
		dol_print_error($db, $object->error);
		exit();
	}
	
	if (($action == 'modify') && $user_specials_rights['edit'] != 1) {
		accessforbidden();
	}
	$extrafields = new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label($customtabs->table_element);
	// fetch spécial liste
	$res = $customtabs->fetch_optionalslist($id, $extralabels);
	
	header('Content-Type: text/csv');
	// le nom du fichier généré reprend le nom de l'onglet
	header('Content-Disposition: attachment;filename=' . str_replace(" ", "_", $customtabs->libelle) . '.csv');
	// entete de colonne en premiere ligne
	foreach ( $extrafields->attribute_label as $key => $label )
		print $key . $sep;
	print "\n";
	foreach ( $customtabs->array_options as $rowidExtrafields => $lineExtrafields ) {
		foreach ( $extrafields->attribute_label as $key => $label ) {
			$value = $lineExtrafields["options_" . $key];
			print $extrafields->showOutputField($key, $value) . $sep;
		}
		print "\n";
	}
} else {
	// show or not the tabs
	if ($customtabs->getShowCustomtabs($user->id)) {
		// Determine user rights according type of ressource
		$user_specials_rights = $customtabs->getUserSpecialsRights($user);
		
		$errmsg = '';
		$errmsgs = array ();
		
		// if something wrong in Load member and extrafields
		if ($result < 0) {
			dol_print_error($db, $object->error);
			exit();
		}
		
		if (($action == 'modify') && $user_specials_rights['edit'] != 1) {
			accessforbidden();
		}
		$extrafields = new ExtraFields($db);
		$extralabels = $extrafields->fetch_name_optionals_label($customtabs->table_element);
		// fetch spécial liste
		$res = $customtabs->fetch_optionalslist($id, $extralabels);
		
		/*
		 * 	Actions
		 */
		
		// pas de gestion de fichiers en mode liste
		
		if ($action == 'setextrafields' && $user_specials_rights['edit']) {
			if ($result > 0) {
				$extralabels = $extrafields->fetch_name_optionals_label($customtabs->table_element);
				$customtabs->id = $id; // on utilise l'id de l'enregistrement
				$ret = $extrafields->setOptionalsFromPost($extralabels, $customtabs);
				$rescust = $customtabs->editExtraFields_line($id, GETPOST('linerowid'));
				if ($rescust < 0) {
					$error ++;
					if ($error)
						$action = 'edit';
				} else {
					$action = '';
					header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/list.php?tabsid=' . $tabsid . '&id=' . $id);
					exit();
				}
			}
		}
		if ($action == 'addextrafields' && $user_specials_rights['create']) {
			if ($result > 0) {
				$nblineAdd = GETPOST("nblineAdd");
				$extralabels = $extrafields->fetch_name_optionals_label($customtabs->table_element);
				
				$error = 0;
				$myarray = array ();
				
				foreach ( $extralabels as $key => $value )
					$myarray[$key] = $value;
				
				for($i = 1; $i <= $nblineAdd; $i ++) {
					$ret = $customtabs->setOptionalsFromPost_line($myarray, $customtabs, '', $i);
					$rescust = $customtabs->insertExtraFields_line($id, $i);
					if ($rescust < 0) {
						$error ++;
						if ($error)
							$action = 'addline';
					}
				}
				if ($error == 0) {
					$action = '';
					header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/list.php?tabsid=' . $tabsid . '&id=' . $id);
					exit();
				}
			}
		}
		if ($action == 'delextrafields' && $user_specials_rights['delete']) {
			if ($result > 0) {
				$result = $customtabs->deleteExtraFields_line(GETPOST('linerowid'));
				$action = '';
				header('Location: ' . DOL_URL_ROOT . '/customtabs/tabs/list.php?tabsid=' . $tabsid . '&id=' . $id);
				exit();
			}
		}
		
		/*
		 * View
		 */
		
		/**
		 * ******************************************
		 *
		 * Liste en Visualisation
		 *
		 * ******************************************
		 */
		
		dol_htmloutput_errors($errmsg, $errmsgs);
		dol_htmloutput_mesg($mesg);
		
		$customtabs->tabs_head_element($tabsid);
		
		/*
		 * Confirm delete file
		 */
		if ($action == 'delete') {
			print $_SERVER["PHP_SELF"] . '?urlfile=' . urlencode(GETPOST('urlfile')) . '&tabsid=' . $tabsid . "&id=" . $id;
			print $form->formconfirm($_SERVER["PHP_SELF"] . '?urlfile=' . urlencode(GETPOST('urlfile')) . '&tabsid=' . $tabsid . "&id=" . $id, $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
		}
		
		// si il y a des champs dans la liste Extrafields
		if (! empty($extrafields->attribute_label)) {
			/*
			 * Barre d'actions Extrafields
			 */
			// Modify
			if ($action == "") {
				print '<div class="tabsAction" style="align:right;">';
				
				print "<form method=post action='" . $_SERVER["PHP_SELF"] . "'>";
				print '<input type="hidden" name="action" value="addline">';
				print '<input type="hidden" name="tabsid" value="' . $tabsid . '">';
				print '<input type="hidden" name="id" value="' . $id . '">';
				if ($user_specials_rights['create']) {
					print '<input type="text" name=nblineAdd value=1 size=1>';
					print '<input type="button" class="butAction" name="addline_btn" value="' . $langs->trans("AddLine") . '" onclick="addline();" >';
				}
				if ($customtabs->exportenabled == 1)
					print '<input type="button" class="butAction" name="export_csv" value="' . $langs->trans("ExportCSV") . '"  onclick="launch_export();" >';
				if ($customtabs->importenabled == 1)
					print '<input type="button" class="butAction" name="import_csv" value="' . $langs->trans("ImportCSV") . '"  onclick="launch_import();" >';
				
				print "</form>";
				print '</div>';
			}
			
			if ($action == 'edit' && ! $customtabs->template) {
				print_fiche_titre($langs->trans("EditLine"));
				
				// var_dump($object->array_options);
				print "<form method=post action='" . $_SERVER["PHP_SELF"] . "'>";
				print '<input type="hidden" name="action" value="setextrafields">';
				print '<input type="hidden" name="tabsid" value="' . $tabsid . '">';
				print '<input type="hidden" name="id" value="' . $id . '">';
				print '<input type="hidden" name="linerowid" value="' . GETPOST('linerowid') . '">';
				
				if ($customtabs->array_options) { // on affiche l'entete ssi il a des lignes
					print '<table class="noborder" width="100%">';
					print "<thead>\n";
					print '<tr class="liste_titre">';
					foreach ( $extrafields->attribute_label as $key => $label ) {
						// gestion du multilingue, attention au cas de traduction foireuse
						if ($langs->trans($key) != $key && $langs->trans($key) != 'ErrorBadValueForParamNotAString')
							$label = $langs->trans($key);
						print '<th nowrap>' . $label . '</th>';
					}
					print '<th width=42px nowrap></th>';
					print '</tr>';
					print "</thead>\n";
					
					print '<tr>';
					foreach ( $customtabs->array_options as $rowidExtrafields => $lineExtrafields ) {
						if ($rowidExtrafields == GETPOST('linerowid'))
							foreach ( $extrafields->attribute_label as $key => $label ) {
								print '<td>';
								$value = $lineExtrafields["options_" . $key];
								print $extrafields->showinputField($key, $value);
								print '</td>';
							}
					}
					print '</tr>';
					print '<td colspan=' . count($extrafields->attribute_label) . ' align=right>';
					print '<input type="submit" class="butAction" value="' . $langs->trans("Valid") . '">';
					print '<input type="submit" class="butAction" value="' . $langs->trans("Cancel") . '"></td>';
					print '</tr>';
					print '</table>';
				}
				print "</form>";
				print "<br><br>";
			}
			
			// gestion des templates
			if ($customtabs->template) {
				// pas d'entete de colonne, on travaille par bloc de divs
				print '<div>';
				// boucle sur les lignes
				foreach ( $customtabs->array_options as $rowidExtrafields => $lineExtrafields ) {
					print '<div class="boxstats">';
					$template = $customtabs->template;
					foreach ( $extrafields->attribute_label as $key => $label ) {
						// gestion du multilingue, attention au cas de traduction foireuse
						if ($langs->trans($key) != $key && $langs->trans($key) != 'ErrorBadValueForParamNotAString')
							$label = $langs->trans($key);
							// pour l'affichage de la description du champ
						$template = str_replace("#LABEL-" . $key . "#", $label, $template);
						
						$value = (isset($_POST["options_" . $key]) ? $_POST["options_" . $key] : $lineExtrafields["options_" . $key]);
						if ($action == "modify" && $user->rights->customtabs->creer && $user_specials_rights['edit'])
							$fields = $extrafields->showInputField($key, $value);
						else
							$fields = $extrafields->showOutputField($key, $value);
						$template = str_replace("#FIELD-" . $key . "#", $fields, $template);
					}
					
					// on affiche enfin le template
					print $template . "\n";
					print '</div>';
				}
				print '</div>';
			} else {
				// section d'ajout
				if ($action == "addline" && $user_specials_rights['create']) {
					
					print_fiche_titre($langs->trans("AddNewLine"));
					
					$nblineAdd = GETPOST("nblineAdd");
					
					print "<form method=post action='" . $_SERVER["PHP_SELF"] . "'>";
					print '<input type="hidden" name="action" value="addextrafields">';
					print '<input type="hidden" name="tabsid" value="' . $tabsid . '">';
					print '<input type="hidden" name="id" value="' . $id . '">';
					print '<input type="hidden" name="nblineAdd" value="' . $nblineAdd . '">';
					print '<table class="border" width="100%">';
					print '<tr class="liste_titre">';
					foreach ( $extrafields->attribute_label as $key => $label ) {
						// gestion du multilingue
						if ($langs->trans($key) != $key)
							$label = $langs->trans($key);
						print '<th nowrap>' . $label . '</th>';
					}
					print '<th nowrap></th>';
					print '</tr>';
					for($i = 1; $i <= $nblineAdd; $i ++) {
						print '<tr >';
						foreach ( $extrafields->attribute_label as $key => $label ) {
							print '<td >';
							print $extrafields->showInputField($key, '', '', $i);
							print '</td>' . "\n";
						}
						print '</tr >';
					}
					print '</table>';
					print '<div class="tabsAction" style="align:right;">';
					print '<input type="submit" class="butAction" value="' . $langs->trans("Valid") . '">';
					print '</div>';
					print "</form>";
					print "<br><br>";
				}
				
				print '<table id="listtable" class="noborder" width="100%">';
				if ($customtabs->array_options) { // on affiche l'entete ssi il a des lignes
					print "<thead>\n";
					print '<tr >';
					foreach ( $extrafields->attribute_label as $key => $label ) {
						// gestion du multilingue, attention au cas de traduction foireuse
						if ($langs->trans($key) != $key && $langs->trans($key) != 'ErrorBadValueForParamNotAString')
							$label = $langs->trans($key);
						print '<th nowrap>' . $label . '</th>';
					}
					print '<th width=42px nowrap></th>';
					print '</tr>';
					print "</thead>\n";
				}
				print "<tbody>\n";
				// boucle sur les lignes
				foreach ( $customtabs->array_options as $rowidExtrafields => $lineExtrafields ) {
					print '<tr>';
					foreach ( $extrafields->attribute_label as $key => $label ) {
						print '<td>';
						$value = $lineExtrafields["options_" . $key];
						print $extrafields->showOutputField($key, $value);
						print '</td>';
					}
					print '<td align="right">';
					// seulement si pas d'action en cours
					if ($action == '') {
						if ($user_specials_rights['edit']) {
							print '<a href="' . $_SERVER["PHP_SELF"] . '?action=edit&tabsid=' . $tabsid . '&id=' . $id . '&linerowid=' . $rowidExtrafields . '">' . img_edit() . '</a>';
						}
						if ($user_specials_rights['delete']) {
							print '&nbsp; <a href="' . $_SERVER["PHP_SELF"] . '?action=delextrafields&tabsid=' . $tabsid . '&id=' . $id . '&linerowid=' . $rowidExtrafields . '">' . img_delete() . "</a>\n";
						}
					}
					// la modification de la ligne se fait en dehors du tableau on sur ligne quand meme la ligne
					if ($action == 'edit' && $rowidExtrafields == GETPOST('linerowid'))
						print $langs->trans("LineEdit");
					
					print '</td></tr>';
				}
				print "</tbody>\n";
				print "</table>";
			}
			
			if (! empty($conf->global->MAIN_USE_JQUERY_DATATABLES)) {
				print "\n";
				print '<script type="text/javascript">' . "\n";
				print 'jQuery(document).ready(function() {' . "\n";
				print 'jQuery("#listtable").dataTable( {' . "\n";
				print '"sDom": \'C<"clear">flrtip\',' . "\n";
				print '"oColVis": {"buttonText": "' . $langs->trans('showhidecols') . '" },' . "\n";
				print '"bPaginate": true,' . "\n";
				print '"bFilter": true	,' . "\n";
				print '"sPaginationType": "full_numbers",' . "\n";
				print gen_aoColumns($extrafields->attribute_type); // pour gérer le format de certaine colonnes
				print '"bJQueryUI": false,' . "\n";
				print '"oLanguage": {"sUrl": "' . DOL_URL_ROOT . "/customtabs/" . $langs->trans('datatabledict') . '" },' . "\n";
				print '"iDisplayLength": 25,' . "\n";
				print '"aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],' . "\n";
				print '"bSort": true,' . "\n";
				print '} );' . "\n";
				print '});' . "\n";
				print "\n";
				
				// extension pour le trie
				print 'jQuery.extend( jQuery.fn.dataTableExt.oSort, {';
				// pour gérer les . et les , des décimales et le blanc des milliers
				print '"numeric-comma-pre": function ( a ) {';
				print 'var x = (a == "-") ? 0 : a.replace( /,/, "." );';
				print 'x = x.replace( " ", "" );';
				print 'return parseFloat( x );';
				print '},';
				print '"numeric-comma-asc": function ( a, b ) {return ((a < b) ? -1 : ((a > b) ? 1 : 0));},';
				print '"numeric-comma-desc": function ( a, b ) {return ((a < b) ? 1 : ((a > b) ? -1 : 0));},';
				
				// pour gérer les dates au format européenne
				print '"date-euro-pre": function ( a ) {';
				print 'if ($.trim(a) != "") {';
				print 'var frDatea = $.trim(a).split("/");';
				print 'var x = (frDatea[2] + frDatea[1] + frDatea[0]) * 1;';
				print '} else { var x = 10000000000000; }';
				print 'return x;';
				print '},';
				print '"date-euro-asc": function ( a, b ) {return a - b; },';
				print '"date-euro-desc": function ( a, b ) {return b - a;}';
				print '} );';
				print "\n";
				print '</script>' . "\n";
			}
		}
		
		// import csv
		if ($action == 'import_csv') {
			print '<form name="userfile" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data" METHOD="POST">';
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="max_file_size" value="' . $conf->maxfilesize . '">';
			print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
			
			$filetoimport = '';
			$var = true;
			
			print '<tr><td colspan="6">' . $langs->trans("ChooseFileToImport", img_picto('', 'filenew')) . '</td></tr>';
			
			print '<tr class="liste_titre"><td colspan="6">' . $langs->trans("FileWithDataToImport") . '</td></tr>';
			
			// Input file name box
			$var = false;
			print '<tr ' . $bc[$var] . '><td colspan="6">';
			print '<input type="file"   name="userfile" size="20" maxlength="80"> &nbsp; &nbsp; ';
			print '<input type="submit" class="button" value="' . $langs->trans("AddFile") . '" name="sendit">';
			print '<input type="hidden" value="sendit" name="action">';
			print '<input type="hidden" value="' . $step . '" name="step">';
			print '<input type="hidden" value="' . $format . '" name="format">';
			print '<input type="hidden" value="' . $excludefirstline . '" name="excludefirstline">';
			print '<input type="hidden" value="' . $separator . '" name="separator">';
			print '<input type="hidden" value="' . $enclosure . '" name="enclosure">';
			print '<input type="hidden" value="' . $datatoimport . '" name="datatoimport">';
			print '<input type="hidden" value="' . $tabsid . '" name="tabsid">';
			print '<input type="hidden" value="' . $id . '" name="id">';
			
			print "</tr>\n";
			print '</table></form>';
		}
		
		// Search available imports
		$filearray = dol_dir_list($conf->customtabs->dir_temp, 'files', 0, '', '', 'name', SORT_DESC);
		
		if (count($filearray) > 0 && $action == "") {
			print '<br><br>';
			print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
			print '<tr class="liste_titre"><td colspan="6">' . $langs->trans("FileWithDataToImport") . '</td></tr>';
			$dir = $conf->customtabs->dir_temp;
			
			// Search available files to import
			$i = 0;
			foreach ( $filearray as $key => $val ) {
				$file = $val['name'];
				
				// readdir return value in ISO and we want UTF8 in memory
				if (! utf8_check($file))
					$file = utf8_encode($file);
				
				if (preg_match('/^\./', $file))
					continue;
				
				$param = "&tabsid=" . $tabsid . "&id=" . $id;
				$modulepart = 'customtabs';
				$urlsource = $_SERVER["PHP_SELF"] . '?step=' . $step . $param . '&filetoimport=' . urlencode($filetoimport);
				$relativepath = $file;
				$var = ! $var;
				print '<tr ' . $bc[$var] . '>';
				print '<td width="16">' . img_mime($file) . '</td>';
				print '<td>';
				print '<a data-ajax="false" href="' . DOL_URL_ROOT . '/document.php?modulepart=' . $modulepart . '&file=' . urlencode($relativepath) . '&step=3' . $param . '" target="_blank">';
				print $file;
				print '</a>';
				print '</td>';
				// Affiche taille fichier
				print '<td align="right">' . dol_print_size(dol_filesize($dir . '/' . $file)) . '</td>';
				// Affiche date fichier
				print '<td align="right">' . dol_print_date(dol_filemtime($dir . '/' . $file), 'dayhour') . '</td>';
				// Del button
				print '<td align="right"><a href="' . $_SERVER['PHP_SELF'] . '?action=delete&step=3' . $param . '&urlfile=' . urlencode($relativepath);
				print '">' . img_delete() . '</a></td>';
				// Action button
				print '<td align="right">';
				print '<a href="' . $_SERVER['PHP_SELF'] . '?action=importit' . $param . '&filetoimport=' . urlencode($relativepath) . '">' . img_picto($langs->trans("NewImport"), 'filenew') . '</a>';
				print '</td>';
				print '</tr>';
			}
			print '</table >';
		}
	} else { // restricted area
		$errmsg = $langs->trans("RestrictedCustomTabs");
		$customtabs->tabs_head_element($tabsid);
		
		dol_htmloutput_errors($errmsg, $errmsgs);
		dol_htmloutput_mesg($mesg);
	}
	print '
		<script type="text/javascript">
			function launch_export() {
				$("div.tabsAction form input[name=\"action\"]").val("export_csv");
				$("div.tabsAction form ").submit();
				$("div.tabsAction form input[name=\"action\"]").val("addline");
			}
			function launch_import() {
				$("div.tabsAction form input[name=\"action\"]").val("import_csv");
				$("div.tabsAction form ").submit();
			}
			function addline() {
				$("div.tabsAction form input[name=\"action\"]").val("addline");
				$("div.tabsAction form ").submit();
			}
	</script>';
	
	llxFooter();
}

$db->close();

// gère le format et la taille des champs
function gen_aoColumns($arrayOfFields) {
	$tmp = '"aoColumns": [ ';
	// boucle sur les champs pour en définir le type pour le trie
	foreach ( $arrayOfFields as $key => $fields ) {
		// selon le type de données
		switch ($fields) {
			case "int" :
			case "price" :
			case "percent" :
				$tmp .= '{ "sType": "numeric-comma" ';
				$tmp .= ' }, ';
				break;
			
			case "date" :
				$tmp .= '{ "sType": "date-euro"';
				$tmp .= ', "sWidth": "80px"';
				$tmp .= ' }, ';
				break;
			default :
				$tmp .= 'null, ';
				break;
		}
	}
	
	// et un denier null pour la colonne d'édition
	$tmp .= ' null],' . "\n";
	return $tmp;
}

?>