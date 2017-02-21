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
?>
<form method="POST">
    <table class="border" width="100%">
        <tr>
            <td width="150px" class="text-right"><?= $langs->trans("DolimaillSender") ?></td>
            <td colspan="2"><input name="from"  readonly="readonly" class="fullInputs" value="<?= $user->firstname . ' ' . $user->lastname . '<' . (!empty($conf->global->MAIN_MAIL_EMAIL_FROM) ? $conf->global->MAIN_MAIL_EMAIL_FROM : $langs->trans("NoEmailInSetup")) . '>' ?>" /></td>
        </tr>
        <tr>
            <td class="text-right"><?= $langs->trans("DolimaillRecipiant") ?></td>
            <td colspan="2"><input name="to" class="fullInputs" value="<?= GETPOST('to', 'alpha', 2) ?>" /></td>
        </tr>
        <tr>
            <td class="infoMessage"><?= $langs->trans("DolimaillCopyRecipiant") ?></td>
            <td colspan="2"><input name="cc" class="fullInputs" value="<?= GETPOST('cc', 'alpha', 2) ?>" /></td>
        </tr>
        <tr>
            <td class="infoMessage"><?= $langs->trans("DolimaillCopyHydden") ?></td>
            <td colspan="2"><input name="cci" class="fullInputs" value="<?= GETPOST('cci', 'alpha', 2) ?>" /></td>
        </tr>
        <tr>
            <td class="text-right"><strong><?= $langs->trans("DolimaillSubject") ?></strong></td>
            <td colspan="2"><input name="subject" class="fullInputs" value="<?= GETPOST('subject', '', 2) ?>" /></td>
        </tr>

        <tr>
            <td class="text-right"><?= $langs->trans("DolimaillTemplate") ?><br /><br />
                <?php
                $dir = "/dolmessage/templates/";
                foreach (scandir(dol_buildpath($dir)) as $key => $value) {
                    if (!in_array($value, array(".", ".."))) {
                        if (!is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                            echo '<button type="button" class="InsertModel fullInputs" url="' . dol_buildpath($dir, 2) . $value . '">' . str_replace(array('_', '.html'), ' ', $value) . '</button>';
                        }
                    }
                }
                ?>
                <script>
                    $('.InsertModel').click(function () {
                        url = $(this).attr('url');
                        extension = url.split('.').pop();
                        $.ajax({
                            url: url,
                            dataType: "html"
                        }).done(function (msg) {
                            if (extension == 'txt') {
                                CKEDITOR.instances.bodyMessage.insertText(msg);
                            } else if (extension == 'html' || extension == 'htm') {
                                CKEDITOR.instances.bodyMessage.insertHtml(msg);
                            }
                        })

                        //return false;
                    });
                </script>
            </td>
            <td colspan="2"><?php
                $doleditor = new DolEditor('bodyMessage', ((GETPOST('bodyMessage', '', 2) != "") ? GETPOST('bodyMessage', '', 2) : "<br /><br />--&nbsp;\n<br />" . $user->signature), '', 400, 'dolibarr_notes', '', true, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, 10, 80);
                echo $doleditor->Create(1);
                ?>
            </td>
            <!-- td>
                <select id="substit" name="substit">
                    <option value="">__FACREF__</option>
                </select>
                <script>
    
                </script>
            </td -->
        </tr>
        <tr>
            <td></td>
            <td colspan="2"><button type="submit" name="action" class="butAction" value="send" ><?= $langs->trans("DolimaillSendEmail") ?></button></td>
        </tr>
    </table>
</form>