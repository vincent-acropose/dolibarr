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

$langs->load('projects');
$langs->load('bills');
$langs->load('cliagcaudio@cliagcaudio');

$form = new Form($db);
$action = GETPOST('action');
$confirm = GETPOST('confirm');

$object = new stdClass();
$object->id = 1;

$upload_dir = DOL_DATA_ROOT.'/cliagcaudio/publipostage';
require_once DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_pre_headers.tpl.php';

if (GETPOST('sendByMail'))
{
	$TContactByFkProject = GETPOST('sendByMailToServicePaiement');
	if (!empty($TContactByFkProject)) sendMail($db, $TContactByFkProject);
}

if (GETPOST('publipostage'))
{
	$TContactByFkProject = GETPOST('builddocPublipostage');
	if (!empty($TContactByFkProject)) generateDoc($db, $TContactByFkProject);
}

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
$moreparams='';
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
$sql.= ", s.nom, s.rowid as socid";
$sql.= ', CONCAT(cge.numetape, " ", cge.descriptionetape) AS projectEtape, pcf.choixmutuelleprojet_idprof6_nom AS mutuelleid';
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

if($search_etape)
{
	$sql .= natural_search(array('cge.numetape', 'cge.descriptionetape'), $search_etape);
	$moreparams.='&search_etape='.$search_etape;
}

if ($search_lieu)
{
	$sql .= ' AND pcf.magasins_ville = '.$search_lieu;
	$moreparams.='&search_lieu='.$search_lieu;
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
	print_barre_liste($text, $page, $_SERVER["PHP_SELF"], $option, $sortfield, $sortorder, "", $num);

	// Show description of content
	if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
	else
	{
		if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
		else print $langs->trans("ProjectsPublicDesc").'<br><br>';
	}
	

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

	if ($mine) print '<input type="hidden" name="mode" value="mine" />';
	
	
	print selectLieuProjet($db, $search_lieu);
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.ref",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"p.title",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Invoices"));
	print_liste_field_titre($langs->trans("Visibility"),$_SERVER["PHP_SELF"],"p.public",$moreparams,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],'p.fk_statut',$moreparams,"",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Etape"));
	if (($mine && $user->rights->cliagcaudio->project_send_mail->mine) || $user->rights->cliagcaudio->project_send_mail->all) print_liste_field_titre($langs->trans('Relancer par mail'), '', '', '', '', 'align="right"');
	if (($mine && $user->rights->cliagcaudio->project_build_doc->mine) || $user->rights->cliagcaudio->project_build_doc->all) print_liste_field_titre($langs->trans('Publipostage'), '', '', '', '', 'align="right"');
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
	print '<td class="liste_titre" align="right">&nbsp;</td>';
	print '<td class="liste_titre" align="right">&nbsp;</td>';
	print '<td class="liste_titre" align="right">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="text" class="flat" style="float:left" name="search_etape" value="'.$search_etape.'">';
	print '<input class="liste_titre" type="image" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	if (($mine && $user->rights->cliagcaudio->project_send_mail->mine) || $user->rights->cliagcaudio->project_send_mail->all) print '<td class="liste_titre" align="right"><span style="cursor:pointer;" onclick="$(\'.checkbox_sendmail\').attr(\'checked\', true);">Tout cocher</span>/<span style="cursor:pointer;" onclick="$(\'.checkbox_sendmail\').attr(\'checked\', false);">Décocher</span></td>';
	if (($mine && $user->rights->cliagcaudio->project_build_doc->mine) || $user->rights->cliagcaudio->project_build_doc->all) print '<td class="liste_titre" align="right"><span style="cursor:pointer;" onclick="$(\'.checkbox_builddoc\').attr(\'checked\', true);">Tout cocher</span>/<span style="cursor:pointer;" onclick="$(\'.checkbox_builddoc\').attr(\'checked\', false);">Décocher</span></td>';
	print "</tr>\n";

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);

		$projectstatic->id = $objp->projectid;
		$projectstatic->user_author_id = $objp->fk_user_creat;
		$projectstatic->public = $objp->public;

		$userAccess = $projectstatic->restrictedProjectArea($user);

		if ($userAccess >= 0)
		{
			$var=!$var;
			print "<tr ".$bc[$var].">";

			// Project url
			print "<td>";
			$projectstatic->ref = $objp->ref;
			print $projectstatic->getNomUrl(1);
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

			print '<td class="nowrap">';
			if ($objp->socid)
			{
				print factureLink($db, $objp);
			}
			print '</td>';

			// Visibility
			print '<td align="left">';
			if ($objp->public) print $langs->trans('SharedProject');
			else print $langs->trans('PrivateProject');
			print '</td>';

			// Status
			$projectstatic->statut = $objp->fk_statut;
			print '<td align="right">'.$projectstatic->getLibStatut(3).'</td>';

			// Etape
			print '<td align="left">'.$objp->projectEtape.'</td>';
			
			if (($mine && $user->rights->cliagcaudio->project_send_mail->mine) || $user->rights->cliagcaudio->project_send_mail->all)
			{
				$TContact = getProjectContact($db, $objp->projectid);
				if (!empty($TContact)) 
				{
					print '<td align="right"><select style="max-width:150px;" name="sendByMailToServicePaiement['.$objp->projectid.']"><option selected value="">&nbsp;</option>';
					foreach ($TContact as &$Tab)
					{
						print '<option value="'.$Tab['fk_socpeople'].'">'.$Tab['name'].' ('.$Tab['email'].')</option>';
					}
					print '</select></td>';
				}
				else
				{
					print '<td align="right">'.img_picto($langs->transnoentitiesnoconv('Contact non trouvé'), 'warning').'</td>';
				}
			}
			
			if (($mine && $user->rights->cliagcaudio->project_build_doc->mine) || $user->rights->cliagcaudio->project_build_doc->all) 
			{
				$TContact = getProjectContact($db, $objp->projectid, false);
				if (!empty($TContact)) 
				{
					print '<td align="right"><select style="max-width:150px;" name="builddocPublipostage['.$objp->projectid.']"><option selected value="">&nbsp;</option>';
					foreach ($TContact as &$Tab)
					{
						print '<option value="'.$Tab['fk_socpeople'].'">'.$Tab['name'].'</option>';
					}
					print '</select></td>';
				}
				else
				{
					print '<td align="right">'.img_picto($langs->transnoentitiesnoconv('Contact non trouvé'), 'warning').'</td>';
				}
				//print '<td align="right"><input name="builddocPublipostage[]" type="checkbox" class="checkbox_builddoc" value="'.$objp->projectid.'" /></td>';
			}

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


$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

$modulepart = 'cliagcaudio';
$permission = 1;
$formfile=new FormFile($db);
/*echo' <pre>';
print_r($conf->cliagcaudio);
echo' </pre>';exit;*/
$formfile->list_of_documents(
    $filearray,
    $object,
    $modulepart,
    $param,
    0,
    '/publipostage/',		// relative path with no file. For example "moduledir/0/1"
    $permission
);

print '<div class="tabsAction">';
if (($mine && $user->rights->cliagcaudio->project_send_mail->mine) || $user->rights->cliagcaudio->project_send_mail->all)
{
	print '<input name="sendByMail" type="submit" onclick="if (!window.confirm(\'Confimez-vous la relance par mail ?\')) return false;" class="button" value="'.$langs->trans('Relancer Mail').'" />';
}
if (($mine && $user->rights->cliagcaudio->project_build_doc->mine) || $user->rights->cliagcaudio->project_build_doc->all) 
{
	print '<input name="publipostage" type="submit" class="button" value="'.$langs->trans('Créer un publipostage').'" />';
}
print '</div>';

llxFooter();

$db->close();
