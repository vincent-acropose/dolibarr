<?php
/* Copyright (C) 2013-2014	Charles-Fr BENKE	<charles.fr@benke.fr>
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
 *	\file       htdocs/mydoliboard/fiche.php
 *	\ingroup    listes
 *	\brief      List card
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
require_once 'class/mydoliboard.class.php';
require_once 'core/lib/mydoliboard.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("mydoliboard@mydoliboard");

$rowid=GETPOST('rowid','alpha');
$action=GETPOST('action','alpha');
$backtopage=GETPOST('backtopage','alpha');

if (!$user->rights->mydoliboard->lire) accessforbidden();

$object = new Mydoliboard($db);

/*
 * Actions
 */


if ($action == 'add' && $user->rights->mydoliboard->creer)
{
	$error=0;
	if (empty($_POST["label"]))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("label")),'errors');
		$error++;
	}

	if (! $error)
	{
		$object->label			= $_POST["label"];
		$object->description	= $_POST["description"];
		$object->titlemenu		= $_POST["titlemenu"];
		$object->mainmenu		= $_POST["mainmenu"];
		$object->leftmenu		= $_POST["leftmenu"];
		$object->elementtab		= $_POST["elementtab"];
		$object->perms			= $_POST["perms"];
		$object->langs			= $_POST["langs"];
		$object->author			= $_POST["author"];
		$object->active			= $_POST["active"];
		$object->blocAmode		= $_POST["blocAmode"];
		$object->blocBmode		= $_POST["blocBmode"];
		$object->blocCmode		= $_POST["blocCmode"];
		$object->blocDmode		= $_POST["blocDmode"];
		$object->blocAtitle		= $_POST["blocAtitle"];
		$object->blocBtitle		= $_POST["blocBtitle"];
		$object->blocCtitle		= $_POST["blocCtitle"];
		$object->blocDtitle		= $_POST["blocDtitle"];
		$object->paramfields	= $_POST["paramfields"];
		
		$result = $object->create($user);
		if ($result == 0)
		{
			$langs->load("errors");
			setEventMessage($object->error,'errors');
			$error++;
		}

		if (! $error)
		{
			header("Location:fiche.php?rowid=".$object->rowid);
			exit;
		}
		else
			$action = 'create';
	}
	else
		$action = 'create';
}
elseif ($action == 'validate' && $user->rights->mydoliboard->creer)
{
	// met à jour la liste
	$object->rowid=			GETPOST("rowid");
	$object->label=			GETPOST("label");
	$object->description=	GETPOST("description");
	$object->titlemenu=		GETPOST("titlemenu");
	$object->mainmenu=		GETPOST("mainmenu");
	$object->leftmenu=		GETPOST("leftmenu");
	$object->elementtab=	GETPOST("elementtab");
	$object->perms=			GETPOST("perms");
	$object->langs=			GETPOST("langs");
	$object->author=		GETPOST("author");
	$object->active=		GETPOST("active");
	$object->blocAmode=		GETPOST("blocAmode");
	$object->blocBmode=		GETPOST("blocBmode");
	$object->blocCmode=		GETPOST("blocCmode");
	$object->blocDmode=		GETPOST("blocDmode");
	$object->blocAtitle=		GETPOST("blocAtitle");
	$object->blocBtitle=		GETPOST("blocBtitle");
	$object->blocCtitle=		GETPOST("blocCtitle");
	$object->blocDtitle=		GETPOST("blocDtitle");

	$object->paramfields=	GETPOST("paramfields");


	$result=$object->update();
	if ($result<0) {
		setEventMessage($object->error,'errors');
	}
}
elseif ($action == 'importation' && $user->rights->mydoliboard->creer)
{
	if (GETPOST("importexport"))
	{
		if (GETPOST("rowid"))
			$object->fetch(GETPOST("rowid"));
		
		$result=$object->importlist(GETPOST("importexport"));
		
		if ($result<0) {
			setEventMessage($object->error,'errors');
		} else {
			$rowid=$result;
			$object->fetch($rowid);
		}
	}
	else
	{
		header("Location:liste.php");
		exit;
	}
}
elseif ($action == 'delete' && $user->rights->mydoliboard->supprimer)
{
	$ret=$object->fetch($rowid);
	$ret=$object->delete($user);
	// retour à la liste
	header("Location:liste.php");
	exit;
	
}
/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);


$help_url="EN:Module_mydoliboard|FR:Module_mydoliboard|ES:M&oacute;dulo_mydoliboard";
llxHeader("",$langs->trans("Mydoliboard"),$help_url);


if ($action == 'create' && $user->rights->mydoliboard->creer)
{
	/*
	 * Create
	 */
	print_fiche_titre($langs->trans("NewPage"));
	
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	
	print '<table class="border" width="100%">';
	
	// label
	print '<tr><td><span class="fieldrequired">'.$langs->trans("PageTitle").'</span></td><td><input size="30" type="text" name="label" value="'.$_POST["label"].'"></td></tr>';

	// description
	print '<tr><td>'.$langs->trans("Description").'</td><td><input size="50" type="text" name="description" value="'.$_POST["description"].'"></td></tr>';
	
	// TitleMenu
	print '<tr><td>'.$langs->trans("TitleMenu").'</td><td><input size="30" type="text" name="titlemenu" value="'.$_POST["titlemenu"].'"></td></tr>';

	// Mainmenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("MainMenu").'</span></td><td><input size="30" type="text" name="mainmenu" value="'.$_POST["mainmenu"].'"></td></tr>';

	// Leftmenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("LeftMenu").'</span></td><td><input size="30" type="text" name="leftmenu" value="'.$_POST["leftmenu"].'"></td></tr>';

	// elementtab
	print '<tr><td>'.$langs->trans("ElementTab").'</td><td>'.select_elementTab("").'</td></tr>';

	// perms
	print '<tr><td><span >'.$langs->trans("perms").'</span></td><td><input size="30" type="text" name="perms" value="'.$_POST["perms"].'"></td></tr>';

	// langs
	print '<tr><td><span >'.$langs->trans("langfiles").'</span></td><td><input size="30" type="text" name="langs" value="'.$_POST["langs"].'"></td></tr>';

	// author
	print '<tr><td><span >'.$langs->trans("author").'</span></td><td><input size="30" type="text" name="author" value="'.$_POST["author"].'"></td></tr>';

	// blocmodes
	print '<tr><td>'.$langs->trans("blocAmode").'</td><td>'.select_blocmode("blocAmode" ,$_POST["blocAmode"]);
	print '&nbsp;<input size="10" type="text" name="blocAtitle" value="'.$_POST["blocAtitle"].'"></td></tr>';
	print '<tr><td>'.$langs->trans("blocBmode").'</td><td>'.select_blocmode("blocBmode" ,$_POST["blocBmode"]);
	print '&nbsp;<input size="10" type="text" name="blocBtitle" value="'.$_POST["blocBtitle"].'"></td></tr>';
	print '<tr><td>'.$langs->trans("blocCmode").'</td><td>'.select_blocmode("blocCmode" ,$_POST["blocCmode"]);
	print '&nbsp;<input size="10" type="text" name="blocCtitle" value="'.$_POST["blocCtitle"].'"></td></tr>';
	print '<tr><td>'.$langs->trans("blocDmode").'</td><td>'.select_blocmode("blocDmode" ,$_POST["blocDmode"]);
	print '&nbsp;<input size="10" type="text" name="blocDtitle" value="'.$_POST["blocDtitle"].'"></td></tr>';


	// params
	print '<tr><td ><span >'.$langs->trans("paramfields").'</span></td>';
	print '<td ><textarea name="paramfields" cols=100 rows=10>'.$_POST["paramfields"].'</textarea></td></tr>';
	print '</table>';

	
	print '<br><center>';
	print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
	if (! empty($backtopage))
	{
	    print ' &nbsp; &nbsp; ';
	    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	}
	print '</center>';
	print '</form>';
}
elseif ($action == 'importexport' && $user->rights->mydoliboard->setup)
{
	/*
	 * Import/export list data
	 */
	print_fiche_titre($langs->trans("ImportBoard"));
	
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="importation">';
	print '<input type="hidden" name="code" value="'.GETPOST("code").'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	
	print '<table class="border" width="100%">';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("FillImportExportData").'</span></td></tr>';
	print '<td><textarea name=importexport cols=132 rows=20>';
	if($rowid)
		print $object->getexporttable($rowid);
	print '</textarea></td></tr>';	
	print '</table>';
	print '<br><center>';
	print '<input type="submit" class="button" value="'.$langs->trans("LaunchImport").'">';
	print '</center>';
	print '</form>';
}
elseif ($action == 'update' && $user->rights->mydoliboard->creer)
{
	print_fiche_titre($langs->trans("UpdateMydoliboard"));

	$ret=$object->fetch($rowid);
	
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="validate">';
	print '<input type="hidden" name="rowid" value="'.$rowid.'">';
	
	print '<table class="border" width="100%">';
	
	// label
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td><td><input size="30" type="text" name="label" value="'.$object->label.'"></td></tr>';

	// description
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Description").'</span></td><td><input size="50" type="text" name="description" value="'.$object->description.'"></td></tr>';

	// TitleMenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("MenuTitle").'</span></td><td><input size="30" type="text" name="titlemenu" value="'.$object->titlemenu.'"></td></tr>';

	// Mainmenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("MainMenu").'</span></td><td><input size="30" type="text" name="mainmenu" value="'.$object->mainmenu.'"></td></tr>';

	// Leftmenu
	print '<tr><td><span class="fieldrequired">'.$langs->trans("LeftMenu").'</span></td><td><input size="30" type="text" name="leftmenu" value="'.$object->leftmenu.'"></td></tr>';

	// elementtab
	print '<tr><td>'.$langs->trans("ElementTab").'</td><td>'.select_elementtab($object->elementtab).'</td></tr>';

	// perms
	print '<tr><td>'.$langs->trans("perms").'</td><td><input size="30" type="text" name="perms" value="'.$object->perms.'"></td></tr>';

	// langs
	print '<tr><td>'.$langs->trans("langs").'</td><td><input size="30" type="text" name="langs" value="'.$object->langs.'"></td></tr>';

	// author
	print '<tr><td>'.$langs->trans("author").'</td><td>';
	// non modifiable si il est renseigné
	if ($object->author)
		print '<input type="hidden" name="author" value="'.$object->author.'">'.$object->author;
	else
		print '<input size="30" type="text" name="author" value="'.$object->author.'">';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("blocAmode").'</td><td>'.select_blocmode("blocAmode", $object->blocAmode);
	print '&nbsp;<input size="10" type="text" name="blocAtitle" value="'.$object->blocAtitle.'"></td></tr>';
	print '<tr><td>'.$langs->trans("blocBmode").'</td><td>'.select_blocmode("blocBmode", $object->blocBmode);
	print '&nbsp;<input size="10" type="text" name="blocBtitle" value="'.$object->blocBtitle.'"></td></tr>';
	print '<tr><td>'.$langs->trans("blocCmode").'</td><td>'.select_blocmode("blocCmode", $object->blocCmode);
	print '&nbsp;<input size="10" type="text" name="blocCtitle" value="'.$object->blocCtitle.'"></td></tr>';
	print '<tr><td>'.$langs->trans("blocDmode").'</td><td>'.select_blocmode("blocDmode", $object->blocDmode);
	print '&nbsp;<input size="10" type="text" name="blocDtitle" value="'.$object->blocDtitle.'"></td></tr>';



	print '<tr><td>'.$langs->trans("active").'</td><td align=left >';
	print $form->selectyesno('active',$object->active,1);
	print '</td></tr>';
	
	// paramfields
	print '<tr><td >'.$langs->trans("paramfields").'</td>';
	print '<td ><textarea name="paramfields" cols=100 rows=5>'.$object->paramfields.'</textarea></td></tr>';

	print '</table>';
	
	print '<br><center>';
	print '<input type="submit" class="button" value="'.$langs->trans("Update").'">';
	if (! empty($backtopage))
	{
	    print ' &nbsp; &nbsp; ';
	    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	}
	print '</center>';
	
	print '</form>';

}
else
{

	/*
	 * Show
	 */
	$ret=$object->fetch($rowid);
	// charge les langues
	if ($object->langs)
		foreach(explode(":",$object->langs) as $newlang)
			$langs->load($newlang);
			
	print_fiche_titre($langs->trans("EditMyPage"));

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.dol_buildpath('/mydoliboard/liste.php',1).'">'.$langs->trans("BackToList").'</a>';
	
	// Define a complementary filter for search of next/prev ref.
	print $code;
	//print $form->showrefnav($object, 'code', $linkback, 1, 'code', 'code');
	print '</td></tr>';
	
	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td >'.$object->label.'</td></tr>';
	print '<tr><td>'.$langs->trans("Description").'</td><td >'.$object->description.'</td></tr>';

	// Menu
	print '<tr><td>'.$langs->trans("MenuTitle").'</td><td >'.$object->titlemenu.'</td></tr>';
	print '<tr><td>'.$langs->trans("MainMenu").'</td><td >'.$object->mainmenu.'</td></tr>';
	print '<tr><td>'.$langs->trans("LeftMenu").'</td><td >'.$object->leftmenu.'</td></tr>';
	print '<tr><td>'.$langs->trans("ElementTab").'</td><td >'.$langs->trans($object->elementtab).'</td></tr>';
	print '<tr><td>'.$langs->trans("perms").'</td><td >'.$object->perms.'</td></tr>';
	print '<tr><td>'.$langs->trans("langs").'</td><td >'.$object->langs.'</td></tr>';
	print '<tr><td>'.$langs->trans("author").'</td><td >'.$object->author.'</td></tr>';
	print '<tr><td>'.$langs->trans("blocAmode").'</td><td >'.$langs->trans($object->blocAmode."mode").' : '.$object->blocAtitle.'</td></tr>';
	print '<tr><td>'.$langs->trans("blocBmode").'</td><td >'.$langs->trans($object->blocBmode."mode").' : '.$object->blocBtitle.'</td></tr>';
	print '<tr><td>'.$langs->trans("blocCmode").'</td><td >'.$langs->trans($object->blocCmode."mode").' : '.$object->blocCtitle.'</td></tr>';
	print '<tr><td>'.$langs->trans("blocDmode").'</td><td >'.$langs->trans($object->blocDmode."mode").' : '.$object->blocDtitle.'</td></tr>';
	
	print '<tr><td>'.$langs->trans("paramfields").'</td><td >'.$object->paramfields.'</td></tr>';
	print '<tr><td>'.$langs->trans("active").'</td><td >'.yn($object->active).'</td></tr>';
	print '</table>';

	
	/*
	 * Boutons actions de la liste
	 */
	print '<div class="tabsAction">';
	
	if ($user->rights->mydoliboard->creer)
	{
		print '<a class="butAction" href="fiche.php?rowid='.$object->rowid.'&action=update">'.$langs->trans('Update').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Update').'</a>';
	}
	
	if ($user->rights->mydoliboard->setup)
	{
		print '<a class="butAction" href="fiche.php?rowid='.$object->rowid.'&action=importexport">'.$langs->trans('ImportExport').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('ImportExport').'</a>';
	}
	
	if ($user->rights->mydoliboard->supprimer)
	{
		print '<a class="butAction" href="fiche.php?rowid='.$object->rowid.'&action=delete">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
	}
	print "<br>\n";
	print '</div>';

	// display list of board associated to the page
	print_fiche_titre($langs->trans("EditMyBoard"));
	
	$object->getSheetsArray($rowid);

	if(is_array($object->listsUsed))
	{
		if (! empty($conf->use_javascript_ajax))
		{
			include 'tpl/ajaxrow.tpl.php';
		}
		print "<table width=100%>";
		print "<tr><td colspan=2 width=100% valign=top>".blocsheet($rowid, $object->listsUsed, "A")."</td></tr>";
		print "<tr><td width=50%  valign=top>".blocsheet($rowid, $object->listsUsed, "B")."</td>";
		print "    <td width=50%  valign=top>".blocsheet($rowid, $object->listsUsed, "C")."</td></tr>";
		print "<tr><td colspan=2 width=100% valign=top>".blocsheet($rowid, $object->listsUsed, "D")."</td></tr>";
		print "</table><br>\n";
	}
		
	/*
	 * Boutons actions des champs
	 */
	print '<div class="tabsAction">';
	
	if ($user->rights->mydoliboard->creer)
	{
		print '<a class="butAction" href="board.php?pageid='.$object->rowid.'&action=create'.$param.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?rowid='.$rowid).'">'.$langs->trans('NewBoard').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('NewBoard').'</a>';
	}
	print "</div>";
	
	print "</div>";
}
llxFooter();
$db->close();
?>