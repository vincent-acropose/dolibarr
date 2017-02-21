<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/cliagcaudio.php
 * 	\ingroup	cliagcaudio
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/cliagcaudio.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

// Translations
$langs->load("cliagcaudio@cliagcaudio");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
//var_dump($_REQUEST);exit;
/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (substr($code, 0, 25) == 'CLIAGC_AUDIO_TEMPLATE_ODT') 
	{
		$num_tpl = substr($code, 26, 2);
		$res=copyTemplateOdt($num_tpl);
		if ($res) dolibarr_set_const($db, $code, $res, 'chaine', 0, '', $conf->entity);
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	elseif ($code == 'TPL') {
		$TTitre = array();
		foreach($_REQUEST['TConst'] as $name=>$value) {
			if($name === 'CLIAGC_AUDIO_TRELANCE_TITLE') {
				$TTitre[] = array('num'=>GETPOST('num_relance'), 'titre'=>$value);
			} else {
				dolibarr_set_const($db, $name, $value, 'chaine', 0, '', $conf->entity);
			}
		}
		$TTitreRelance = unserialize($conf->global->CLIAGC_AUDIO_TRELANCE_TITLE);
		$TTitreRelance[$TTitre[0]['num']] = $TTitre[0]['titre'];
		if(empty($TTitre[0]['titre'])) unset($TTitreRelance[$TTitre[0]['num']]);
		ksort($TTitreRelance);
		dolibarr_set_const($db, 'CLIAGC_AUDIO_TRELANCE_TITLE', serialize($TTitreRelance), 'chaine', 0, '', $conf->entity);
	}
	elseif (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
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
	if (dolibarr_del_const($db, $code, 0) > 0)
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
$form = new Form($db);
$page_name = "cliagcaudioSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = cliagcaudioAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104002Name"),
    0,
    "cliagcaudio@cliagcaudio"
);

// Setup page goes here
$form=new Form($db);
$var=false;

print $langs->trans("Exemple template publipostage (ODT)").' - <a href="'.dol_buildpath('/cliagcaudio/exampleTemplate/exemple.odt', 2).'">exemple.odt</a><br /><br />';

print '<table class="noborder" width="100%">';

for($i=1;$i<21;$i++) print_form_modele($i);

function print_form_modele($i) {
	
	global $db, $bc, $conf, $langs;
	
	$const_subject_label = 'CLIAGC_AUDIO_MSG_SUBJECT_'.$i;
	$const_subject_val = $conf->global->{'CLIAGC_AUDIO_MSG_SUBJECT_'.$i};
	$const_destinataire_label = 'CLIAGC_AUDIO_DESTINATAIRE_'.$i;
	$const_destinataire_val = $conf->global->{'CLIAGC_AUDIO_DESTINATAIRE_'.$i};
	$const_content_label = 'CLIAGC_AUDIO_MSG_CONTENT_'.$i;
	$const_content_val = $conf->global->{'CLIAGC_AUDIO_MSG_CONTENT_'.$i};
	$const_tpl_label = 'CLIAGC_AUDIO_TEMPLATE_ODT_'.$i;
	$const_tpl_val = $conf->global->{'CLIAGC_AUDIO_TEMPLATE_ODT_'.$i};
	$const_title_label = 'CLIAGC_AUDIO_TRELANCE_TITLE';
	$const_title_val = unserialize($conf->global->{'CLIAGC_AUDIO_TRELANCE_TITLE'});
	
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_TPL">';
	print '<input type="hidden" name="num_relance" value="'.$i.'">';
	
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("relanceTitle").' : ';
	print '<input type="text" name="TConst['.$const_title_label.']" size="20" value="'.$const_title_val[$i].'" />';
	print '</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100"></td>'."\n";
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("Destinataire").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="800">';
	//print '<input type="text" name="TConst['.$const_destinataire_label.']" size="50" value="'.$const_destinataire_val.'" />';
	print_list_destinataire($const_destinataire_label, $const_destinataire_val);
	print '</tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("Subject Mail").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="800">';
	print '<input type="text" name="TConst['.$const_subject_label.']" size="50" value="'.$const_subject_val.'" />';
	print '</td></tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("Contenu Mail").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="800">';
	if (!empty($conf->global->FCKEDITOR_ENABLE_MAIL))
	{
		$editor=new DolEditor('TConst['.$const_content_label.']',$const_content_val,'',200,'dolibarr_notes','In', true, true, 1, 120, 8, 0);
		$editor->Create();	
	}
	else {
		print '<textarea rows="3" cols="80" name="TConst['.$const_content_label.']">'.$const_content_val.'</textarea>';
	}
	print '</td>';
	
	print '<tr><td></td><td></td><td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
	print '</form>';
	print '</td></tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td></td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="800">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_'.$const_tpl_label.'">';
	if (!empty($const_tpl_val)) print '<a href="'.dol_buildpath('document.php?modulepart=cliagcaudio&file='.$const_tpl_val, 2).'">'.$const_tpl_val.'</a> - ';
	print '<input type="file" name="'.$const_tpl_label.'" />';
	print '<input type="submit" class="button" value="'.$langs->trans("Upload").'">';
	print '</form>';
	print '</td></tr>';

}

function print_list_destinataire($const_destinataire_label, $const_destinataire_val) {
	
	?>
	
		<select name="TConst[<?php print $const_destinataire_label; ?>]" id="<?php print $const_destinataire_label; ?>">
			<option value=""></option>
			<option value="CLIENT">Client</option>
			<option value="MUT_SRV_MEDECIN">Mutuelle (service médecin)</option>
			<option value="MUT_SRV_PAIEMENT">Mutuelle (service paiement)</option>
		</select>
		
		<script>
			$("#<?php print $const_destinataire_label;?>").val("<?php print $const_destinataire_val;?>");
		</script>
		
	<?php
	
}

print '</table>';

llxFooter();

$db->close();
