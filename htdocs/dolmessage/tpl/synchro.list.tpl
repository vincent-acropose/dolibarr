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
global $db, $menus, $mails, $pagination, $info, $form, $nbr_mess, $pagination, $err;

error_reporting(E_ALL);

if ($err != '') {
    print $err;
} else {

    echo "<div style='display:inline-block; width:18%; margin:0 1%'>";
    /**
      Action button
     */
    if (!isset($NoDisplayNewCompose) || $NoDisplayNewCompose == false)
        echo '<h3 style="text-align: center"><a href="' . dol_buildpath('/dolmessage/compose.php', 1) . '?action=compose&number=' . GETPOST('number') . '' . '&identifiid=' . GETPOST('identifiid') . '" class="butAction">' . $langs->trans('Compose') . '</a></h3>';

    dol_include_once('/dolmessage/tpl/synchro.mail.tree.tpl');
    echo "</div>";

    print '<div style="display:inline-block; width:79%; vertical-align:top;">';

    print '<table style="width:100%;">';
    print '    <tr>';
    print '      <th style="text-align:right;">';



    $page_precedente = GETPOST("num_page") - 1;
    $page_suivante = GETPOST("num_page") + 1;
    if ($page_precedente > 0)
        print '<a href="' . dol_buildpath('/dolmessage/synchro.php', 1) . '?number=' . GETPOST('number') . '&folder=' . GETPOST('folder') . '&identifiid=' . GETPOST('identifiid') . '&num_page=' . $page_precedente . '">Precedente</a> ';

    for ($num_page = 1; $num_page <= ceil($info->Nmsgs / $pagination); $num_page++) {
        if ($num_page != GETPOST("num_page"))
            print '<a href="' . dol_buildpath('/dolmessage/synchro.php', 1) . '?number=' . GETPOST('number') . '&folder=' . GETPOST('folder') . '&identifiid=' . GETPOST('identifiid') . '&num_page=' . $num_page . '">' . $num_page . '</a> ';
        else
            print $num_page. ' ';

        if ($num_page < ceil($nbr_mess / $pagination))
            print ', ';
    }

    if ($page_suivante < ceil($nbr_mess / $pagination))
        print '<a href="' . dol_buildpath('/dolmessage/synchro.php', 1) . '?number=' . GETPOST('number') . '&folder=' . GETPOST('folder') . '&num_page=' . $page_suivante . '">Suivante</a> ';
    print '      </th>';
    print '    </tr>';
    print '</table>';
    print '<table class="noborder listingEmail" width="100%">';
    print '<thead><tr class="liste_titre">';
    print '      <td class="liste_titre" align="center" colspan="3">' . $langs->trans("DolimaillFrom") . '</td>';
    print '      <td class="liste_titre" align="center">' . $langs->trans("DolimaillObject") . '</td>';
    print '      <td class="liste_titre" align="center">' . $langs->trans("DolimaillDate") . '</td>';
    print '      <td class="liste_titre" align="center">' . $langs->trans("DolimaillTaille") . '</td>';
    print '      <td class="liste_titre" align="center">' . $langs->trans("DolimaillFlagged") . '</td>';

    print '      <td colspan="2" class="liste_titre"  align="center">' . $langs->trans("DolimaillLinked") . '</td>';
//     print '      <td class="liste_titre"  align="center">' . $langs->trans("DolimaillAction") . '</td>';
    print '    </tr></thead><tbody>';
    $nbr_mess = 0;
    foreach ($mails as $i => $mail) {

        if ($mail->GetDeleted() == 0 || (int) $mail->GetId() <= 0/* && !$mail->seen */) {

            print '    <tr class="';
            if ($i % 2 == 0)
                print 'pair ';
            else
                print 'impair ';
            echo (!$mail->GetUnseen() ? 'seen ' : 'unseen ');

            print '">';



            print '<td>';
            if (count($mail->GetLinked()) > 0)
                print img_picto('sync', 'sync@dolmessage');
            print '</td>';



            print '      <td style="text-align:center;width:30px;">';
            if ($mail->GetAnswered())
                print img_picto('answered', 'answered@dolmessage');

            print '      </td>';
            print '      <td class="hiddenInfo">';
            if (count($mail->GetLinked()) > 0) {
                $base = $mail->GetLinked();

                foreach ($base as $type => $list)
                    foreach ($list as $obj)
                        if (in_array($type, array('societe'))) {
//                         print_r($obj);
//                         }
// 														$Societe = new Societe($db);
//                             $socid = $obj->id;
                            print $obj->getNomUrl(1, '', 16);
                        } elseif (in_array($type, array(/* 'societe', */'contact'))) {
//                         print_r($obj);
//                         }
                            dol_include_once('/' . $type . '/class/' . $type . '.class.php');
                            $class = ucwords($type);
                            $subobj = new Contact($db);
                            $subobj->fetch(GETPOST('reference_rowid', 'int'));
// 														$Societe = new Societe($db);
//                             $socid = $obj->id;
                            print $obj->getNomUrl(1, '', 16);
                        }
            } else { // from imap
                print '<span>' . $mail->GetFromMail() . '</span>' . $mail->GetFromName();
            }
            print '</td>';

            $dolmessage = new dolmessage($db);
            $dolmessage->message_id = $mail->GetSubject();
            if (count($mail->GetLinked()) > 0)
                $dolmessage->id = $mail->GetId();
            else
                $dolmessage->uid = $mail->GetUid();
//             var_dump(__file__); 
            print '      <td>' . $dolmessage->getNomUrl(0, 'dolmessage', 0, 'number=' . GETPOST('number') . '&identifiid=' . GETPOST('identifiid') . '&folder=' . urlencode(GETPOST('folder'))) . '</td>';


            print '      <td style="text-align:center;width: 115px;" class="hiddenInfo"><span>' . date("d/m/Y H:i", strtotime($mail->GetDate())) . '</span>' . $mail->GetDate(true) . '</td>';
            print '      <td style="text-align:right;">' . $mail->GetSize(true) . '</td>';
            print '      <td align="center">';

            if ($mail->GetFlagged())
                print img_picto('flagged', 'flagged@dolmessage');
            else
                print img_picto('unflagged', 'unflagged@dolmessage');



            print '</td>';
            print '<td width="180" align="center">';
//             if ($mail->GetId() > 0) {
//                 $DolMsg = new DolMessage($db, $user);
//             } else {
            print '<form name="link_' . $i . '" method="POST">';
            print '<table><tr><td>';
            $out = '';
// 								if ($conf->use_javascript_ajax)
            $out .= ajax_multiautocompleter('reference_' . $i, array('reference_folder_' . $i, 'reference_rowid_' . $i, 'reference_type_element_' . $i, 'reference_fk_socid_' . $i), dol_buildpath('/dolmessage/core/ajax/ajax.php', 1), 'num_ligne=' . $i . '&action=ownerlink') . "\n";

            $out.= '<input id="reference_' . $i . '" type="text" name="reference" value="';
            print $out . '">' . "\n";
            print '<input id="reference_rowid_' . $i . '" type="hidden" name="reference_rowid" value="' . '">' . "\n";
            print '<input id="reference_type_element_' . $i . '" type="hidden" name="reference_type_element" value="' . '">' . "\n";
            print '<input id="reference_fk_socid_' . $i . '" type="hidden" name="reference_fk_socid" value="' . '">' . "\n";
            print '<input id="reference_mail_uid_' . $i . '" type="hidden" name="reference_mail_uid" value="' . $mail->GetUid() . '">' . "\n";
            print '<input id="reference_folder_' . $i . '" type="hidden" name="reference_folder" value="' . GETPOST('folder') . '">' . "\n";

            if (GETPOST('identifiid') > 0)
                print '<input  type="hidden" name="identifiid" value="' . GETPOST('identifiid') . '">' . "\n";

            print '<input  type="hidden" name="folder" value="' . GETPOST('folder') . '">' . "\n";
            print '<input  type="hidden" name="num_page" value="' . GETPOST('num_page') . '">' . "\n";
            print '<input  type="hidden" name="number" value="' . GETPOST('number') . '">' . "\n";
            print '</td><td>';
            print '<a href="javascript:;" onclick="link_' . $i . '.submit();">';
            print img_picto('attacher', 'lock');
            print '</a>';
            print '</td></tr></table>';
            print '</form>';


//             }
            print '</td>';

            print '<td align="center">';
            print '</td>';

            print '</tr>';

            $nbr_mess++;
        }
    }
    print '</tbody></table>';
    print '<table style="width:100%;">';
    print '    <tr>';
    print '      <th style="text-align:right;">';



    $page_precedente = GETPOST("num_page") - 1;
    $page_suivante = GETPOST("num_page") + 1;
    if ($page_precedente > 0)
        print '<a href="' . dol_buildpath('/dolmessage/synchro.php', 1) . '?number=' . GETPOST('number') . '&folder=' . GETPOST('folder') . '&identifiid=' . GETPOST('identifiid') . '&num_page=' . $page_precedente . '">Precedente</a> ';

    for ($num_page = 1; $num_page <= ceil($info->Nmsgs / $pagination); $num_page++) {
        if ($num_page != GETPOST("num_page"))
            print '<a href="' . dol_buildpath('/dolmessage/synchro.php', 1) . '?number=' . GETPOST('number') . '&folder=' . GETPOST('folder') . '&identifiid=' . GETPOST('identifiid') . '&num_page=' . $num_page . '">' . $num_page . '</a> ';
        else
            print $num_page;

        if ($num_page < ceil($nbr_mess / $pagination))
            print ', ';
    }

    if ($page_suivante < ceil($nbr_mess / $pagination))
        print '<a href="' . dol_buildpath('/dolmessage/synchro.php', 1) . '?number=' . GETPOST('number') . '&folder=' . GETPOST('folder') . '&num_page=' . $page_suivante . '">Suivante</a> ';
    print '      </th>';
    print '    </tr>';
    print '</table>';
    print '</div>';
    print '<div style="clear:both;"></div>';
}
?>


