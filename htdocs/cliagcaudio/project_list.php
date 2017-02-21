<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
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
 *	\file       htdocs/projet/liste.php
 *	\ingroup    projet
 *	\brief      Page to list projects
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
dol_include_once('/cliagcaudio/lib/cliagcaudio.lib.php');
dol_include_once('/core/lib/files.lib.php');
dol_include_once('/core/class/html.form.class.php');
dol_include_once('/core/class/html.formfile.class.php');
dol_include_once('/core/lib/functions.lib.php');
//var_dump($_REQUEST);exit;
$langs->load('projects');
$langs->load('bills');
$langs->load('cliagcaudio@cliagcaudio');

$form = new Form($db);
$action = GETPOST('action');
$confirm = GETPOST('confirm');

$object = new stdClass();
$object->id = 1;

strpos($_REQUEST['urlfile'], '/temp') !== false ? $end = 'temp' : $end = 'publipostage';
$upload_dir = DOL_DATA_ROOT.'/cliagcaudio/'.$end;

require_once DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_pre_headers.tpl.php';

$TIDProject = GETPOST('TData');
$num_relance = GETPOST('num_relance');

$header = false;

$TMessage = array();

if (GETPOST('sendByMail'))
{
	if (!empty($TIDProject)) sendMail($db, $TIDProject, $num_relance);
	else $TMessage['warnings'][] = 'Aucun dossier sélectionné';
	$header = true;
}

if (GETPOST('publipostage'))
{
	if (!empty($TIDProject)) generateDoc($db, $TIDProject, $num_relance);
	else $TMessage['warnings'][] = 'Aucun dossier sélectionné';
	$header = true;
}

// Affichage des messages utilisateurs
if(!empty($TMessage['ok'])) setEventMessage(implode('<br />', $TMessage['ok']));
if(!empty($TMessage['errors'])) setEventMessage(implode('<br />', $TMessage['errors']), 'errors');
if(!empty($TMessage['warnings'])) setEventMessage(implode('<br />', $TMessage['warnings']), 'warnings');

/*if($header) {
	// Pour ne pas renvoyer les mails ou recréer le doc si appui sur page précédente
	header('Location: '.$_SERVER['PHP_SELF']);
}*/

$title = $langs->trans("Projects");

// Security check
$socid = (is_numeric($_GET["socid"]) ? $_GET["socid"] : 0 );
if ($user->societe_id > 0) $socid=$user->societe_id;
if ($socid > 0)
{
	$soc = new Societe($db);
	$soc->fetch($socid);
	$title .= ' (<a href="liste.php">'.$soc->nom.'</a>)';
}
if (!$user->rights->projet->lire) accessforbidden();


$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])? $_GET["page"]:$_POST["page"];
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;

$search_ref=GETPOST("search_ref");
$search_label=GETPOST("search_label");
$search_societe=GETPOST("search_societe");
$search_lieu=GETPOST('search_lieu');
$search_etape=GETPOST('search_etape');
$search_dateetapeprojet=GETPOST('search_dateetapeprojet');
$search_dateaccordmedmut_deb=GETPOST('search_dateaccordmedmut_deb');
$search_dateaccordmedmut_fin=GETPOST('search_dateaccordmedmut_fin');
$search_societe_cp=GETPOST('search_societe_cp');
$search_societe_ville=GETPOST('search_societe_ville');
$moreparams='';

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
	$search_ref='';
	$search_label='';
	$search_societe='';
	$search_lieu='';
	$search_etape='';
	$search_dateetapeprojet='';
	$search_dateaccordmedmut_deb='';
	$search_dateaccordmedmut_fin='';
	$search_societe_cp='';
	$search_societe_ville='';
	$moreparams='';
}

/*
 * View
 */

$projectstatic = new Project($db);
$socstatic = new Societe($db);

llxHeader("",$langs->trans("Projects"),"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");

if ($action == 'delete')
{
	$langs->load("companies");	// Need for string DeleteFile+ConfirmDeleteFiles
	$ret = $form->form_confirm(
			$_SERVER["PHP_SELF"] . '?id=' . $object->id . '&urlfile=' . urlencode(GETPOST("urlfile")) . '&linkid=' . GETPOST('linkid', 'int') . (empty($param)?'':$param),
			$langs->trans('DeleteFile'),
			$langs->trans('ConfirmDeleteFile'),
			'confirm_deletefile',
			'',
			0,
			1
	);
	if ($ret == 'html') print '<br>';
}

$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,($mine?$mine:($user->rights->projet->all->lire?2:0)),1,$socid);

$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_statut, p.public, p.fk_user_creat, p.description";
$sql.= ", p.datec as date_create, p.dateo as date_start, p.datee as date_end";
$sql.= ", s.nom, s.rowid as socid, s.zip, s.town";
$sql.= ', CONCAT(cge.numetape, " ", cge.descriptionetape) AS projectEtape, pcf.choixmutuelleprojet_idprof6_nom AS mutuelleid, pcf.dateetapeprojet, pcf.dateaccordmedmut';
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";

$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_customfields pcf ON (pcf.fk_projet = p.rowid)';
$sql.= ' LEFT JOIN cg_etapes cge ON (pcf.etapeprojet_numetape_descriptionetape = cge.rowid)';

$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine || ! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
//if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";

if ($search_ref)
{
	$sql .= natural_search('p.ref', $search_ref);
	$moreparams.='&search_ref='.$search_ref;
}

if ($search_label)
{
	$sql .= natural_search('p.title', $search_label);
	$moreparams.='&search_label='.$search_label;
}

if ($search_societe)
{
	$sql .= natural_search('s.nom', $search_societe);
	$moreparams.='&search_societe='.$search_societe;
}

if ($search_societe_cp)
{
	$sql .= natural_search('s.zip', $search_societe_cp);
	$moreparams.='&search_societe_cp='.$search_societe_cp;
}

if ($search_societe_ville)
{
	$sql .= natural_search('s.town', $search_societe_ville);
	$moreparams.='&search_societe_ville='.$search_societe_ville;
}

if($search_etape > 0)
{
	$sql .= ' AND cge.rowid = '.$search_etape;
	$moreparams.='&search_etape='.$search_etape;
}

if ($search_lieu)
{
	$sql .= ' AND pcf.magasins_ville = '.$search_lieu;
	$moreparams.='&search_lieu='.$search_lieu;
}

if($search_dateetapeprojet) {
	$dateetapeprojet = explode('/', $search_dateetapeprojet);
	$dateetapeprojet = implode('-', array_reverse($dateetapeprojet));
	$sql.= ' AND pcf.dateetapeprojet BETWEEN "'.$dateetapeprojet.' 00:00:00" AND "'.$dateetapeprojet.' 23:23:59"';
	$moreparams.='&search_dateetapeprojet='.$search_dateetapeprojet;
}

if($search_dateaccordmedmut_deb) {
	$dateaccordmedmut_deb = explode('/', $search_dateaccordmedmut_deb);
	$dateaccordmedmut_deb = implode('-', array_reverse($dateaccordmedmut_deb));
	$sql.= ' AND pcf.dateaccordmedmut >= "'.$dateaccordmedmut_deb.'"';
	$moreparams.='&search_dateaccordmedmut_deb='.$search_dateaccordmedmut_deb;
}

if($search_dateaccordmedmut_fin) {
	$dateaccordmedmut_fin = explode('/', $search_dateaccordmedmut_fin);
	$dateaccordmedmut_fin = implode('-', array_reverse($dateaccordmedmut_fin));
	$sql.= ' AND pcf.dateaccordmedmut <= "'.$dateaccordmedmut_fin.'"';
	$moreparams.='&search_dateaccordmedmut_fin='.$search_dateaccordmedmut_fin;
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

dol_syslog("list allowed project sql=".$sql);
$resql = $db->query($sql);
if ($resql)
{
	$var=true;
	$num = $db->num_rows($resql);
	$i = 0;

	$text=$langs->trans("Projects");
	if ($mine) $text=$langs->trans('MyProjects');
	print_barre_liste($text, $page, $_SERVER["PHP_SELF"], $moreparams, $sortfield, $sortorder, "", $num);

	// Show description of content
	if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
	else
	{
		if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
		else print $langs->trans("ProjectsPublicDesc").'<br><br>';
	}


	print 'nb resultat: '.$num.'<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';

	if ($mine) print '<input type="hidden" name="mode" value="mine" />';


	print selectLieuProjet($db, $search_lieu);

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.ref",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"p.title",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Invoices"));
	//print_liste_field_titre($langs->trans("Visibility"),$_SERVER["PHP_SELF"],"p.public",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateMajEtape"),$_SERVER["PHP_SELF"],"pcf.dateetapeprojet",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Etape"),$_SERVER["PHP_SELF"],"cge.rowid",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateAccordMedMut"),$_SERVER["PHP_SELF"],"pcf.dateaccordmedmut",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],'p.fk_statut',$moreparams,"",'align="right"',$sortfield,$sortorder);

	print '<td class="liste_titre"></td>';
	//if (($mine && $user->rights->cliagcaudio->project_send_mail->mine) || $user->rights->cliagcaudio->project_send_mail->all) print_liste_field_titre($langs->trans('Relancer par mail'), '', '', '', '', 'align="right"');
	//if (($mine && $user->rights->cliagcaudio->project_build_doc->mine) || $user->rights->cliagcaudio->project_build_doc->all) print_liste_field_titre($langs->trans('Publipostage'), '', '', '', '', 'align="right"');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="6">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_label" value="'.$search_label.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_societe" value="'.$search_societe.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_societe_cp" size="9" value="'.$search_societe_cp.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_societe_ville" value="'.$search_societe_ville.'">';
	print '</td>';
	print '<td class="liste_titre" align="right">&nbsp;</td>';
	//print '<td class="liste_titre" align="right">&nbsp;</td>';
	print '<td class="liste_titre" nowrap>';
	print $form->select_date(($dateetapeprojet?$dateetapeprojet:''),'search_dateetapeprojet', 0, 0, 1);
	print '</td>';
	print '<td class="liste_titre">';
	//print '<input type="text" class="flat" style="float:left" name="search_etape" value="'.$search_etape.'">';
	print get_list_etape($search_etape);
	print '</td>';
	print '<td class="liste_titre" nowrap>';
	print $form->select_date(($dateaccordmedmut_deb?$dateaccordmedmut_deb:''),'search_dateaccordmedmut_deb', 0, 0, 1);
	print '<br />';
	print $form->select_date(($dateaccordmedmut_fin?$dateaccordmedmut_fin:''),'search_dateaccordmedmut_fin', 0, 0, 1);
	print '</td>';
	print '<td nowrap><input class="liste_titre" type="image" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';
	print '<td class="liste_titre" align="center">';
	if(((($mine && $user->rights->cliagcaudio->project_send_mail->mine) || $user->rights->cliagcaudio->project_send_mail->all) || (($mine && $user->rights->cliagcaudio->project_build_doc->mine) || $user->rights->cliagcaudio->project_build_doc->all)))
		print '<input type="checkbox" id="checkall" name="checkall" value="1" onchange="checkAll()" />';
	print '</td>';
	print "</tr>\n";

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);

		$projectstatic->id = $objp->projectid;
		$projectstatic->fetch($objp->projectid);
		$projectstatic->user_author_id = $objp->fk_user_creat;
		$projectstatic->public = $objp->public;

		$userAccess = $projectstatic->restrictedProjectArea($user);

		if ($userAccess >= 0)
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";

			// Project url
			print '<td nowrap>';
			print $projectstatic->getNomUrl(1);

			// Note
			if(!empty($projectstatic->note_public)) {
				print '<div style="display:inline-block;width:15px;">';
				print $form->textwithtooltip(img_picto('', 'object_reduc.png'), $projectstatic->note_public, 1, 1);
				print '</div>';
			}

			// Dernier événement agenda
			$data = getLastActionComm($projectstatic);
			$last_action_comm = $data['txt'];
			if(!empty($last_action_comm)) {
				print '<div style="display:inline-block;width:15px;">';
				print '<a href="'.dol_buildpath('/comm/action/fiche.php?id='.$data['id'], 1).'">'.$form->textwithtooltip(img_picto('', 'calendar.png'), $last_action_comm, 1, 1).'</a>';
				print '</div>';
			}

			print "</td>";

			// Title
			print '<td>';
			print dol_trunc($objp->title,24);
			print '</td>';

			// Company
			print '<td>';
			if ($objp->socid)
			{
				$socstatic->id=$objp->socid;
				$socstatic->nom=$objp->nom;
				print $socstatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';

			print '<td>';
			print $objp->zip;
			print '</td>';

			print '<td>';
			print $objp->town;
			print '</td>';

			print '<td class="nowrap">';
			if ($objp->socid)
			{
				print factureLink($db, $objp);
			}
			print '</td>';

			// Visibility
			/*print '<td align="left">';
			if ($objp->public) print $langs->trans('SharedProject');
			else print $langs->trans('PrivateProject');
			print '</td>';*/

			// Project date maj étape
			print "<td>";
			if(!empty($objp->dateetapeprojet) && strpos($objp->dateetapeprojet, '1970-01-01') === false) print date('d/m/Y', strtotime($objp->dateetapeprojet));
			print "</td>";

			// Etape
			print '<td align="left">'.$objp->projectEtape.'</td>';

			// Project date maj étape
			print "<td>";
			if(!empty($objp->dateaccordmedmut)) print date('d/m/Y', strtotime($objp->dateaccordmedmut));
			print "</td>";

			// Status
			$projectstatic->statut = $objp->fk_statut;
			print '<td align="right">'.$projectstatic->getLibStatut(3).'</td>';

			if(((($mine && $user->rights->cliagcaudio->project_send_mail->mine) || $user->rights->cliagcaudio->project_send_mail->all) || (($mine && $user->rights->cliagcaudio->project_build_doc->mine) || $user->rights->cliagcaudio->project_build_doc->all)))
				print '<td align="center"><input name="TData[]" type="checkbox" class="checkbox_send" value="'.$objp->projectid.'" /></td>';

			print "</tr>\n";

		}

		$i++;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print "</table>";

print '<br />';

print '<div class="tabsAction">';

$TTitreRelance = unserialize($conf->global->CLIAGC_AUDIO_TRELANCE_TITLE);
if(!empty($TTitreRelance)) {
	if(((($mine && $user->rights->cliagcaudio->project_send_mail->mine) || $user->rights->cliagcaudio->project_send_mail->all) || (($mine && $user->rights->cliagcaudio->project_build_doc->mine) || $user->rights->cliagcaudio->project_build_doc->all))) {

		print '<select name="num_relance">';
		foreach($TTitreRelance as $num=>$titre) print '<option value="'.$num.'">'.$titre.'</option>';
		//for($i=1;$i<8;$i++) print '<option value="'.$i.'">Relance n°'.$i.'</option>';
		print '</select>';

	} else {
		print 'Vous n\'avez pas le droit de créer des relances';
	}
} else print 'Aucune relance existante';

if (($mine && $user->rights->cliagcaudio->project_send_mail->mine) || $user->rights->cliagcaudio->project_send_mail->all)
{
	print '<input class="butAction" name="sendByMail" type="submit" onclick="if (!window.confirm(\'Confimez-vous la relance par mail ?\')) return false;" class="button" value="'.$langs->trans('Relance mail').'" />';
}
if (($mine && $user->rights->cliagcaudio->project_build_doc->mine) || $user->rights->cliagcaudio->project_build_doc->all)
{
	print '<input class="butAction" name="publipostage" type="submit" class="button" value="'.$langs->trans('Relance courrier').'" />';
}
print '</div>';


$filearray=dol_dir_list(DOL_DATA_ROOT.'/cliagcaudio/publipostage',"files",0,'','(\.meta|_preview\.png)$','date',SORT_DESC,1);
$filearray2=dol_dir_list(DOL_DATA_ROOT.'/cliagcaudio/temp',"files",0,'','(\.pdf)$','date',SORT_DESC,1);

$modulepart = 'cliagcaudio';
$permission = 1;
$formfile=new FormFile($db);

$formfile->list_of_documents(
    $filearray,
    $object,
    $modulepart,
    $param,
    0,
    '/publipostage/',		// relative path with no file. For example "moduledir/0/1"
    $permission
);

$formfile->list_of_documents(
    $filearray2,
    $object,
    $modulepart,
    $param,
    0,
    '/temp/',		// relative path with no file. For example "moduledir/0/1"
    $permission
);

function get_list_etape($selected) {

	global $db;

	$TEtape = array();

	$sql = 'SELECT rowid, numetape, CASE WHEN LENGTH(descriptionetape) > 60 THEN CONCAT(SUBSTRING(descriptionetape, 1, 60), "...") ELSE descriptionetape END as descriptionetape FROM cg_etapes';
	$resql = $db->query($sql);
	while($res = $db->fetch_object($resql)) $TEtape[$res->rowid] = $res->numetape.' '.$res->descriptionetape;

	return Form::selectarray('search_etape', $TEtape, $selected, 1);

}

?>

<script type="text/javascript">

		function checkAll() {

			if($('input[name=checkall]').is(':checked')) {
				$('.checkbox_send').prop('checked', true);
			} else {
				$('.checkbox_send').prop('checked', false);
			}

		}

</script>

<?php

llxFooter();

$db->close();
