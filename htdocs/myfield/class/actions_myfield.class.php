<?php
/* Copyright (C) 2015		charlie Benke	<charlie@patas-monkey.com>
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
 * 	\file       htdocs/myfield/class/actions_myfield.class.php
 * 	\ingroup    myfield
 * 	\brief      Fichier de la classe des actions/hooks de myfield
 */
 
class ActionsMyfield // extends CommonObject 
{

	/** Overloading the formObjectOptions function : replacing the parent's function with the one below 
	 *  @param      parameters  meta datas of the hook (context, etc...) 
	 *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
	 *  @param      action             current action (if set). Generally create or edit or null 
	 *  @return       void 
	 */
	 
// sur les fiches en mise à jour
function formObjectOptions($parameters, $object, $action)
{

	if ($action=='create' || substr ($action,0,4) =='edit' )
	{
		global $conf, $langs, $db, $user;
		
		require_once DOL_DOCUMENT_ROOT.'/myfield/class/myfield.class.php';
		$myField = new Myfield($db);
		
		$listfield = $myField->get_all_myfield($parameters['context']);

		$bvisibility=false;
		print '<script src="'.DOL_MAIN_URL_ROOT.'/myfield/js/jquery.maskedinput.min.js"></script>';
		print "<script>\n";
		print "jQuery(document).ready(function () {\n";
		foreach ($listfield  as $currfield)
		{
			$user_specials_rights = $myField->getUserSpecialsRights($currfield['rowid'], $user);
		$user_specials_rights['read'] = 1;
// 		$user_specials_rights['write'] = 1;
			if ($user_specials_rights['read'])
			{
				$label = $currfield['label'];
				if ($currfield['replacement'])
				{
					print 'textchange=$("td:contains(\''.$currfield['label'].'\')").html();'."\n";
					print 'if (textchange) {';
					print 'textchange=textchange.replace(/'.$currfield['label'].'/g,\''.$currfield['replacement'].'\');';
					print '$("td:contains(\''.$currfield['label'].'\')").html(textchange);';
					print '}'."\n";
					$label = $currfield['replacement'];
				}

				if ($currfield['active'] == 1)
					print '$("td:contains(\''.$label.'\')").parent().each(function(){$(this).remove();});'."\n";
				if ($currfield['active'] == 2)  // invisibility mode with reappear feature
				{	// visibility hidden
					print '$("td:contains(\''.$label.'\')").parent().each(function(){$(this).css("visibility","hidden");});'."\n";
					// if click on the empty area : they reappear
					//print '$("td:contains(\''.$label.'\')").parent().click(function(){ $(this).toggle(); });'."\n";
					print '$("td:contains(\''.$label.'\')").parent().attr("class","fieldvisible");'."\n";
					$bvisibility=true;
				}
				if ($currfield['color'])
					print '$("td:contains(\''.$label.'\')").parent().each(function(){$(this).attr("bgcolor","'.$currfield['color'].'");});'."\n";
				if ($currfield['initvalue'] && $action=='create')
					print '$("td:contains(\''.$label.'\')").parent().find("input").val("'.$currfield['initvalue'].'");'."\n";
				if ($currfield['sizefield'] > 0) // change size of input field
					print '$("td:contains(\''.$label.'\')").parent().find("input").attr("size", "'.$currfield['sizefield'].'");'."\n";
				// on désactive la zone de saisie si on y a pas l'accès
				if ($user_specials_rights['write'] ==0)
					print '$("td:contains(\''.$label.'\')").parent().find("input").attr("disabled","disabled");'."\n";
				else
				{	
					if ($currfield['sizefield'] > 0) // change size of input field
						print '$("td:contains(\''.$label.'\')").parent().find("input").attr("size", "'.$currfield['sizefield'].'");';

					// si la zone n'est pas désactivé et quelle est obligatoire
					if ($currfield['compulsory'] == 1)
					{
						print '$("td:contains(\''.$label.'\')").parent().find("input").change('."\n";
						print 'function () {'."\n";
						print 'if ($("td:contains(\''.$label.'\')").parent().find("input").val()== ""){'."\n";
						print 'alert ("'.$label.' '.$langs->trans("Compulsory").'");'."\n";
						print '$("td:contains(\''.$label.'\')").parent().find("input").focus();'."\n";
						print '}});'."\n";
					} 
					if ($currfield['formatfield'])
					{
						print '$("td:contains(\''.$label.'\')").parent().find("input").mask("'.$currfield['formatfield'].'")'."\n";
					}
				}
			}
			else
				print '$("td:contains(\''.$currfield['label'].'\')").parent().each(function(){$(this).css("display","none");});'."\n";
		}
		print "$('#fieldshow').click(function(){ $('.fieldvisible').css('visibility','visible'); });"."\n";
		print "$('#fieldhide').click(function(){ $('.fieldvisible').css('visibility','hidden'); });"."\n";
		print "});";
		print "</script>";
		if ($bvisibility)
		{
			print "<div id='fieldshow' style='float:left;' href=#>Show /</div>"."\n";
			print "<div id='fieldhide' style='float:left;' href=#>&nbsp;Hide</div>"."\n";
		}
	}

}
	
// sur les fiches en affichage
function addMoreActionsButtons($parameters, $object, $action)
{
	global $conf, $langs, $db, $user;
	if ($action != 'create' && substr ($action,0,4) != 'edit' )
	{
	
		require_once DOL_DOCUMENT_ROOT.'/myfield/class/myfield.class.php';
		$myField = new Myfield($db);
		$bvisibility=false;
		$listfield = $myField->get_all_myfield($parameters['context']);

		print '<script src="'.DOL_MAIN_URL_ROOT.'/myfield/js/jquery.maskedinput.min.js"></script>';
		print "<script>";
		print 'jQuery(document).ready(function () {';
		foreach ($listfield  as $currfield)
		{
			$user_specials_rights = $myField->getUserSpecialsRights($currfield['rowid'], $user);
			if ($user_specials_rights['read'])
			{
				$label = $currfield['label'];
				if ($currfield['replacement'])
				{
					print 'textchange=$("td:contains(\''.$currfield['label'].'\')").html();';
					print 'if (textchange) {';
					print 'textchange=textchange.replace(/'.$currfield['label'].'/g,\''.$currfield['replacement'].'\');';
					print '$("td:contains(\''.$currfield['label'].'\')").html(textchange);';
					print '}';
					$label = $currfield['replacement'];

					//print '$("td:contains(\''.$label.'\')").text("'.$currfield['replacement'].'");';
					//$label = $currfield['replacement'];
				}
				if ($currfield['active'] == 1)
					print '$("td:contains(\''.$label.'\')").parent().each(function(){$(this).remove();});';
				if ($currfield['active'] == 2)  // invisibility mode with reappear feature
				{	// visibility hidden
					print '$("td:contains(\''.$label.'\')").parent().each(function(){$(this).css("visibility","hidden");});';
					// if click on the empty area : they reappear
					//print '$("td:contains(\''.$label.'\')").parent().click(function(){ $(this).toggle(); });';
					print '$("td:contains(\''.$label.'\')").parent().attr("class","fieldvisible");';
					$bvisibility=true;
				}
				if ($currfield['color'])
					print '$("td:contains(\''.$label.'\')").parent().each(function(){$(this).attr("bgcolor","'.$currfield['color'].'");});';
				// no init value on display mode
				// on désactive la zone de saisie si on y a pas l'accès
				if ($user_specials_rights['write'] ==0)
					print '$("td:contains(\''.$label.'\')").parent().find("input").attr("disabled","disabled");';
				else
				{
					if ($currfield['sizefield'] > 0) // change size of input field
						print '$("td:contains(\''.$label.'\')").parent().find("input").attr("size", "'.$currfield['sizefield'].'");';
					if ($currfield['compulsory'] == 1)
					{
						print '$("td:contains(\''.$label.'\')").parent().find("input").change(';
						print 'function () {';
						print 'if ($("td:contains(\''.$label.'\')").parent().find("input").val()== "") {';
						print 'alert ("'.$label.' '.$langs->trans("Compulsory").'");';
						print '$("td:contains(\''.$label.'\')").parent().find("input").focus();';
						print '}});';
					} 
				}
				if ($currfield['formatfield'])
				{
					print '$("td:contains(\''.$label.'\')").parent().find("input").mask("'.$currfield['formatfield'].'")'."\n";
				}

			}
			else
				print '$("td:contains(\''.$currfield['label'].'\')").parent().each(function(){$(this).css("display","none");});';
		}

		print "$('#fieldshow').click(function(){ $('.fieldvisible').css('visibility','visible'); });";
		print "$('#fieldhide').click(function(){ $('.fieldvisible').css('visibility','hidden'); });";
		print "});";
		print "</script>";
		if ($bvisibility)
		{
			print "<div id='fieldshow' style='float:left;' href=#>Show /</div>";
			print "<div id='fieldhide' style='float:left;' href=#>&nbsp;Hide</div>";
		}
	}
}
}
?>