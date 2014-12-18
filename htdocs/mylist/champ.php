<?php
/* Copyright (C) 2013-2014	Charles-Fr BENKE		<charles.fr@benke.fr>
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
 *	\file       htdocs/mylist/champ.php
 *	\ingroup    mylist
 *	\brief      Page of a list fields
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
    $res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
require_once DOL_DOCUMENT_ROOT.'/mylist/class/mylist.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

$langs->load("mylist@mylist");

$codeTable = GETPOST("code",'string');
$keyField = GETPOST("key",'string');
$action = GETPOST("action",'string');


// Security check
$socid=0;
if (! $user->rights->mylist->lire) accessforbidden();

$object = new MyList($db);
$ret=$object->fetch($codeTable);
$object->getChampsArray($codeTable);

/*
 * Actions
 */

if ($action == 'edit' && $user->rights->mylist->creer)
{
	$error=0;

	if (empty($_POST["nameField"]))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Name")).'</div>';
	}
	
	if (! $error)
	{
		$object->name		= $_POST["nameField"];
		$object->alias		= $_POST["alias"];
		$object->field		= $_POST["field"];
		$object->type		= $_POST["type"];
		$object->elementfield= $_POST["elementfield"];
		$object->alias		= $_POST["alias"];
		$object->align		= $_POST["align"];
		$object->enabled	= $_POST["enabled"];
		$object->visible	= $_POST["visible"];
		$object->filter		= $_POST["filter"];
		$object->filterinit	= $_POST["filterinit"];
		$object->width		= $_POST["width"];

		if ($object->updateField($user,$keyField) == 1)
		{
			header("Location: ".DOL_URL_ROOT.'/mylist/fiche.php?code='.$codeTable);
			exit;
		}
	}
	$action='';
}

if ($action == 'add' && ! $_POST["cancel"] && $user->rights->mylist->creer)
{
	$error=0;

	if (empty($_POST["field"]))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("fieldName")).'</div>';
	}
	
	if (empty($_POST["nameField"]))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Name")).'</div>';
	}
	if (! $error)
	{
		$object->name		= $_POST["nameField"];
		$object->field		= $_POST["field"];
		$object->alias		= $_POST["alias"];
		$object->type		= $_POST["type"];
		$object->elementfield= $_POST["elementfield"];
		$object->alias		= $_POST["alias"];
		$object->align		= $_POST["align"];
		$object->enabled	= $_POST["enabled"];
		$object->visible	= $_POST["visible"];
		$object->filter		= $_POST["filter"];
		$object->width		= $_POST["width"];
		$object->filterinit	= $_POST["filterinit"];
		$result = $object->addField($user,$_POST["key"]);
		if ($result == 1)
		{
			header("Location: ".DOL_URL_ROOT.'/mylist/fiche.php?code='.$codeTable);
			exit;
		}
		else
		{
			print "ress=".$result;
		}
	}
	else
	{
		$action='';
	}
}

if ($action == 'confirm_delete' && GETPOST('confirm')== "yes" && $user->rights->mylist->supprimer)
{
	if ($object->deleteField($user, $keyField) ==1)
	{
		header("Location: ".DOL_URL_ROOT.'/mylist/fiche.php?code='.$codeTable);
		exit;
	}
	else
	{
		$langs->load("errors");
		$mesg='<div class="error">'.$langs->trans($object->error).'</div>';
		$action='';
	}
}

$help_url="EN:Module_mylist|FR:Module_mylist|ES:M&oacute;dulo_mylist";
llxHeader("",$langs->trans("ListFields"),$help_url);


dol_htmloutput_mesg($mesg);


if (! empty($keyField))
{
	print_fiche_titre($langs->trans("EditField"));
}
else
{
	print_fiche_titre($langs->trans("AddField"));
}

$form = new Form($db);
if ($action == 'delete')
{
	$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?code=".$_GET["code"].'&key='.$keyField, 
		$langs->trans("DeleteAField"),$langs->trans("ConfirmDeleteAField"),"confirm_delete");
	if ($ret == 'html') print '<br>';
}
/*
 * View
 */


dol_fiche_head($head, 'list', $langs->trans("Mylist"),0,'list');

print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/mylist/liste.php">'.$langs->trans("BackToList").'</a>';

// Code
print '<tr><td width="30%">'.$langs->trans("Code").'</td><td>'.$codeTable.'</td></tr>';

// Label
print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

print '</table>';

dol_fiche_end();



$form = new Form($db);
//$formother = new FormOther($db);

print '<form action="'.$_SERVER["PHP_SELF"].'?code='.$codeTable.'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

if (! empty($keyField))
{
	/*
	 * Fiche champ en mode edit
	 */
	 
	print '<input type="hidden" name="action" value="edit">';
	print '<input type="hidden" name="key" value="'.$keyField.'">';
	
	print '<table class="border" width="50%">';
	
	// database fieldname = key
	print '<tr><td class="fieldrequired">'.$langs->trans("fieldName").'</td>';
	print '<td><input type=hidden name="field" value="'.$object->listsUsed[$keyField]['field'].'">'.$object->listsUsed[$keyField]['field'].'</td></tr>'."\n";

	// database alias
	print '<tr><td >'.$langs->trans("Alias").'</td>';
	print '<td><input type=text name="alias" value="'.$object->listsUsed[$keyField]['alias'].'"></td></tr>'."\n";

	// FieldName
	print '<tr><td >'.$langs->trans("Name").'</td>';
	print '<td><input type=text name="nameField" value="'.$object->listsUsed[$keyField]['name'].'"></td></tr>'."\n";

	// type of Fields
	print '<tr><td >'.$langs->trans("Type").'</td>';
	print '<td>'.$object->getSelectTypeFields($keyField).'</td></tr>'."\n";
	
	// element of Fields
	print '<tr><td >'.$langs->trans("ElementField").'</td>';
	print '<td><input type=text size=60 name="elementfield" value="'.$object->listsUsed[$keyField]['elementfield'].'"></td></tr>'."\n";

	// width cols
	print '<tr><td >'.$langs->trans("Width").'</td>';
	print '<td><input type=text size=6 name="width" value="'.$object->listsUsed[$keyField]['width'].'"></td></tr>'."\n";

	// align fields
	print '<tr><td >'.$langs->trans("align").'</td><td>';
	print $form->selectarray('align', array('left'=>'gauche','center'=>'milieu','right'=>'droite'), $object->listsUsed[$keyField]['align']);
	print '</td></tr>'."\n";
	
	print '<tr><td >'.$langs->trans("enabled").'</td>';
	print '<td>';
	print $form->selectyesno('enabled', ($object->listsUsed[$keyField]['enabled']=='true'?'yes':'no'), 0);
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("visible").'</td>';
	print '<td>';
	print $form->selectyesno('visible', ($object->listsUsed[$keyField]['visible']=='true'?'yes':'no'), 0);
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("filter").'</td>';
	print '<td>';
	print $form->selectyesno('filter', ($object->listsUsed[$keyField]['filter']=='true'?'yes':'no'), 0);
	print '</td></tr>'."\n";

	// filter init
	print '<tr><td >'.$langs->trans("FilterInit").'</td>';
	print '<td><input type=text size=15 name="filterinit" value="'.$object->listsUsed[$keyField]['filterinit'].'"></td></tr>'."\n";


}
else
{
	print '<input type="hidden" name="action" value="add">';
	print '<table class="border" width="50%">';
	
	// database fieldname = key
	print '<tr><td class="fieldrequired">'.$langs->trans("fieldName").'</td>';
	print '<td><input type=text name="field" value=""></td></tr>'."\n";

	// database alias
	print '<tr><td >'.$langs->trans("Alias").'</td>';
	print '<td><input type=text name="alias" value=""></td></tr>'."\n";

	// FieldName
	print '<tr><td class="fieldrequired">'.$langs->trans("Name").'</td>';
	print '<td><input type=text name="nameField" value=""></td></tr>'."\n";

	// type of Fields
	print '<tr><td >'.$langs->trans("Type").'</td>';
	print '<td>'.$object->getSelectTypeFields("").'</td></tr>'."\n";
	
	// element of Fields
	print '<tr><td >'.$langs->trans("ElementField").'</td>';
	print '<td><input type=text name="elementfield" value=""></td></tr>'."\n";

	// width cols
	print '<tr><td >'.$langs->trans("Width").'</td>';
	print '<td><input type=text size=6 name="width" value=""></td></tr>'."\n";


	// align fields
	print '<tr><td >'.$langs->trans("align").'</td><td>';
	print $form->selectarray('align', array('left'=>$langs->trans("left"),'center'=>$langs->trans("center"),'right'=>$langs->trans("right")), 'left');
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("enabled").'</td>';
	print '<td>';
	print $form->selectyesno('enabled', 'yes', 0);
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("visible").'</td>';
	print '<td>';
	print $form->selectyesno('visible', 'yes', 0);
	print '</td></tr>'."\n";

	print '<tr><td >'.$langs->trans("filter").'</td>';
	print '<td>';
	print $form->selectyesno('filter', 'yes', 0);
	print '</td></tr>'."\n";

	// filter init
	print '<tr><td >'.$langs->trans("FilterInit").'</td>';
	print '<td><input type=text size=15 name="filterinit" value=""></td></tr>'."\n";

}
print '</table>';

dol_fiche_end();


/*
 * Actions
 */
print '<div class="tabsAction">';

print '<a class="butAction" href="fiche.php?code='.$codeTable.'">'.$langs->trans('Cancel').'</a>';

// Modify
if (! empty($keyField))
{
	if ($user->rights->mylist->creer )
	{
		print '<input type="submit" class="butAction" name="save" value="'.$langs->trans("Modify").'">';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Modify').'</a>';
	}

	// Delete
	if ($user->rights->mylist->supprimer )
	{
		print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?code='.$codeTable.'&amp;action=delete&amp;key='.$keyField.'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
	}
}
else
{
	if ($user->rights->mylist->creer )
	{
		print '<input type="submit" class="button" value="'.$langs->trans('Save').'">';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Add').'</a>';
	}
}
print '</div>';

print '</form>';

llxFooter();
$db->close();
?>