<?php
/* Copyright (C) 2014 Oscim 	<support@oscim.fr>
 * Copyright (C) 2015 Oscss-Shop Team <support@oscss-shop.fr>
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
 */
global $Message, $form, $folder, $number, $menus, $NoDisplayNewCompose, $NoDisplayTree, $NoDisplayReply;



 echo "<div style='display:inline-block; width:18%; margin:0 1%'>";
 
 /**
		Action button 
 */
	if(!isset($NoDisplayNewCompose) || $NoDisplayNewCompose == false)
		echo '<h3 style="text-align: center"><a href="'.dol_buildpath('/dolmessage/compose.php', 1).'?action=compose&number='.GETPOST('number').''.'&identifiid='.GETPOST('identifiid').'" class="butAction">'.$langs->trans('Compose').'</a></h3>';
	
// 	if(!isset($NoDisplayReply) || $NoDisplayReply == false)
// 		echo '<h3 style="text-align: center"><a href="'.dol_buildpath('/dolmessage/compose.php', 1).'?action=reply&number='.GETPOST('number').''.'&identifiid='.GETPOST('identifiid').'" class="butAction">'.$langs->trans('Reply').'</a></h3>';
 
 /**
	Display Tree 
 */
	if(!isset($NoDisplayTree) || $NoDisplayTree == false)
    dol_include_once('/dolmessage/tpl/synchro.mail.tree.tpl');

  echo "</div>";
  
  
    print '<div style="display:inline-block; width:79%; vertical-align:top;">';
?>



<table class="messageHeader" width="100%">

    <tr>
        <td width="5%" class="infoMessage"><?php echo $langs->trans('MessageFromTxt'); ?></td>
        <td><?php echo $Message->GetFromName() . "&nbsp;&lt;" . $Message->GetFromMail() . "&gt;"; ?></td>
        <td width="7%" class="infoMessage"><?php echo $langs->trans('MessageDateTxt'); ?></td>
        <td width="12%"><?php echo $Message->GetDate(); ?></td>
    </tr>

    <tr>
        <td class="infoMessage"><?php echo $langs->trans('MessageSubjectTxt'); ?></td>
        <td colspan="3" class="fieldrequired"><?php echo $Message->GetSubject(); ?></td>
    </tr>
    <tr>
        <td class="infoMessage"><?php echo $langs->trans('MessageFromTo'); ?></td>
        <td colspan="3">
		  <?php echo "" . $Message->GetToMail() . ""; ?>
        </td>
    </tr>
    <?php if(get_class($Message) == 'dolimapmessage'): ?>
    <tr>
        <td class="infoMessage"><?php echo $langs->trans('MessageId'); ?></td>
        <td colspan="3">
		  <?php echo "" . $Message->GetMessageId() . ""; ?>
        </td>
    </tr>
    <?php endif; ?>

</table>

<article >
    <iframe class="iframeBody" src="<?php echo dol_buildpath('/dolmessage/core/ajax/ajax.php', 1) . '?action=' . ((get_class($Message) == 'dollocalmessage') ? 'local&id=' . $Message->GetId() : 'imap&uid=' . $Message->GetUid() . '&folder=' . urlencode($folder) . '&number=' . $number.'&identifiid='.GETPOST('identifiid') ) ?>" ></iframe> 
</article>



<section class="tabsAction">
<?php
if (!empty($conf->use_javascript_ajax)) {
    print "\n" . '<div class="tabsAction">' . "\n";
    print '<a class="butAction" href="' . dol_buildpath('/dolmessage/core/ajax/ajax.php', 1) . '?action=' . (($Message->GetId() > 0) ? 'local&id=' . $Message->GetId() : 'imap&uid=' . $Message->GetUid() . '&folder=' . $folder . '&number=' . $number.'&identifiid='.GETPOST('identifiid') ) . '" target="_blank">' . $langs->trans('ViewFullScreen') . '</a>';

    
    if (get_class($Message) == 'dollocalmessage') {
        print '<span id="action-deleteLocal" class="butActionDelete">' . $langs->trans('DolMessageDeleteLocal') . '</span>' . "\n";
        print $form->formconfirm(dol_buildpath('/dolmessage/info.php', 1) . "?number=" . GETPOST('number') . '&id=' . $Message->GetId().'&identifiid='.GETPOST('identifiid'), $langs->trans("DeleteDolMessage"), $langs->trans("ConfirmDeleteDolMessaget"), "confirm_delete", '', 0, "action-deleteLocal");
    }
// dolimapmessage
    if ($Message->GetUid() > 0) {
        print '<span id="action-deleteOnline" class="butActionDelete">' . $langs->trans('DolMessageDeleteOnline') . '</span>' . "\n";
        print $form->formconfirm(dol_buildpath('/dolmessage/info.php', 1) . "?number=" . GETPOST('number') . '&uid=' . $Message->GetUid().'&identifiid='.GETPOST('identifiid'), $langs->trans("DeleteDolMessage"), $langs->trans("ConfirmDeleteDolMessaget"), "confirm_delete", '', 0, "action-deleteOnline");
    }

    print "</div>";
} else {
    print '<a class="butActionDelete" href="' . dol_buildpath('/dolmessage/info.php', 1) . '?number=' . GETPOST('number') . '&' . (($Message->GetId() > 0) ? 'id=' . $Message->GetId() : 'uid=' . $Message->GetUid().'&identifiid='.GETPOST('identifiid') ) . '&action=delete" >' . $langs->trans("Delete") . '</a>';
}
?>
</section>

<section>
    <table class="noborder allwidth">
        <tr class="liste_titre">
            <td colspan="2"><?= $langs->trans('Liens') ?></td>
        </tr>
	<?php

    foreach ($Message->Getlinked() as $type => $list)
        foreach ($list as $obj){
//             if ($type == 'societe') {
							echo '<tr>';
								echo '<td>';
									echo $type; 
								echo '</td>';
								echo '<td>';
// 										=$socid = $obj->id;
									print $obj->getNomUrl(1,'',16);
								echo '</td>';
							echo '</tr>';
            }
            ?>
		</table>
		<br /><br />
</section>

<?php
if (sizeof($Message->GetAttach()) > 0) { ?>
<section id="Master" class="" >

    <div class="titre"><?= $langs->trans('PieceJointes') ?><sup class="label"><?= sizeof($Message->GetAttach())?></sup></div>
    <table class="noborder allwidth">
        <tr class="liste_titre">
            <td><?= $langs->trans('Refer') ?></td>
<!-- 			<td align="center">Date</td> -->
<!-- 			<td align="right"> </td> -->
            <td align="right"><?= $langs->trans('State') ?></td>
        </tr>
<?php

    foreach ($Message->linkedObjects as $type => $list)
        foreach ($list as $obj)
            if ($type == 'societe') {
                $socid = $obj->id;
// 		print $obj->getNomUrl(1,'',16);
            }



    $i = 0;
    foreach ($Message->GetAttach() as $att_name => $value) {
        //var_dump($att_name,$value);
        echo '<tr>';

        if ($Message->GetId() > 0)
            print '<td><a href="' . dol_buildpath('/dolmessage/attachment.php', 1) . '?id=' . $Message->GetId() . '&attach=' . $i.'&identifiid='.GETPOST('identifiid') . '" target="_parent">' . /* img_picto('pdf', 'dellocal@commo,') . */ $value->name . '</a></td>';
        else
            print '<td><a href="' . dol_buildpath('/dolmessage/attachment.php', 1) . '?uid=' . $Message->GetUid() . '&attach=' . $i .'&identifiid='.GETPOST('identifiid'). '" target="_parent">' . $value->name . '</a>   </td>';
        $i++;




        print '<td>';
        print '<form name="link_' . $i . '" method="POST">';
        print '<table><tr><td>';
        $out = '';
        if ($conf->use_javascript_ajax)
            $out .= ajax_multiautocompleter('reference_' . $i, array('reference_attach_num_' . $i, 'action_' . $i, 'reference_rowid_' . $i, 'reference_type_element_' . $i, 'reference_fk_socid_' . $i), dol_buildpath('/dolmessage/core/ajax/ajax.php', 1), 'num_ligne=' . $i . '&action=attachlink&socid=' . $socid.'&identifiid='.GETPOST('identifiid')) . "\n";

        $out.= '<input id="reference_' . $i . '" type="text" name="reference" value="';
        print $out . '">' . "\n";
        print '<input id="action_' . $i . '" type="hidden" name="action" value="linkattach">' . "\n";
        print '<input id="reference_rowid_' . $i . '" type="hidden" name="reference_rowid" value="' . '">' . "\n";
        print '<input id="reference_type_element_' . $i . '" type="hidden" name="reference_type_element" value="' . '">' . "\n";
        print '<input id="reference_fk_socid_' . $i . '" type="hidden" name="reference_fk_socid" value="' . '">' . "\n";
        print '<input id="reference_attach_num_' . $i . '" type="hidden" name="reference_attach_num" value="' . $i . '">' . "\n";
        print '<input  type="hidden" name="folder" value="' . GETPOST('folder') . '">' . "\n";
        print '<input  type="hidden" name="num_page" value="' . GETPOST('num_page') . '">' . "\n";
        print '</td><td>';
        print '<a href="javascript:;" onclick="link_' . $i . '.submit();">';
        print img_picto('attacher', 'lock');
        print '</a>';
        print '</td></tr></table>';
        print '</form>';

        print '</td> ';

        echo '</tr>';
    }?>
    </table>
</section>
<?php
}
?></div>