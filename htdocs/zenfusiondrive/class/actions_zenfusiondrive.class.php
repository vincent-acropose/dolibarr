<?php
/*
 * ZenFusion Drive - A Google Drive module for Dolibarr
 * Copyright (C) 2013   Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014   Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
dol_include_once('/zenfusionoauth/class/TokenStorage.class.php');

use \zenfusion\oauth\TokenStorage;

/**
 * Class ActionsZenFusionDrive
 *
 * Hook actions for Google Drive support
 */
class ActionsZenFusionDrive
{

    /**
     * @var string Hookmanager returns
     */
    public $resprints;

    /**
     * @var DoliDB Database handler
     */
    private $db;

    /**
     * Constructor
     *
     * @param    DoliDB $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    /**
     * Adds the export to Drive option to the builddoc form
     *
     * @param array $parameters Hook metadata
     * @param CommonObject &$object Hook relatedobject
     * @param string &$action Hook action
     * @return int|string Status or print value
     */
    public function formBuilddocLineOptions($parameters, &$object, &$action)
    {
        global $langs, $user, $db;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

        $dolibarr_version = versiondolibarrarray();

        $tokenstorage = TokenStorage::getUserToken($db, $user, true);
        if ($tokenstorage === false) {
            // We didn't get a token, bail out without error
            return 0;
        }

        $langs->load('zenfusiondrive@zenfusiondrive');
        $img = dol_buildpath('/zenfusiondrive/img/object_drive.png', 1);
        $scriptpath = dol_buildpath('/zenfusiondrive/upload.php', 1);

        // We only need the access token
        $tokenstorage = $tokenstorage->token->getAccessToken();
        if (($user->rights->zenfusiondrive->use || $user->admin) && $tokenstorage) {
            $title = $langs->trans('SelectTargetFolder');
            // TODO: factorize javascript
            $js = '
                <script type="text/javascript">
                // Google Picker API for the Google Drive upload
                function googlePicker(e) {
                    // Event normalizing
                    if (!e) var e = window.event;
                    // Which button has been pressed
                    source = e.id;
                    gapi.load("picker", {"callback": createPicker});
                }

                // Create and render a Picker object for selecting files
                function createPicker() {
                    var view = new google.picker.DocsView(google.picker.ViewId.FOLDERS)
                        .setIncludeFolders(true)
                        .setSelectFolderEnabled(true)
                        .setParent("root");
                    var picker = new google.picker.PickerBuilder()
                        .addView(view)
                        .setSelectableMimeTypes("application/vnd.google-apps.folder")
                        .setTitle("' . $title . '")
                        .setOAuthToken("' . $tokenstorage . '")
                        .setLocale("' . $this->getLocale() . '")
                        .setCallback(pickerCallback)
                        .build();
                    picker.setVisible(true);
                }

                // Process the callback data
                function pickerCallback(data) {
                    switch (data.action) {
                        case google.picker.Action.PICKED:
                            var fileId = data.docs[0].id;
                            destination = "' . $scriptpath
                                . '?modulepart=' . $parameters['modulepart']
                                . '&file=' . $parameters['relativepath']
                                . '&parent_id=" + fileId
                            window.location = destination;
                        break;
                    }
                }
                </script>
                <script type="text/javascript" src="https://apis.google.com/js/api.js"></script>';

            $this->resprints = '<td align="right">';
            $this->resprints .= '<a href="#" onclick="googlePicker(this);"'
                . 'title="' . $langs->trans("SaveToGoogleDrive"). '">';
            $this->resprints .= '<img src="' . $img . '" alt=""></a>';
            $this->resprints .= '</td>';
            $this->resprints .= $js;
        }
        if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.3
            return 0;
        } else {
            return $this->resprints;
        }
    }

    /**
     * Adds the Google Drive picker to the Attach files feature
     *
     * @param array $parameters Hook metadata
     * @param CommonObject &$object Hook relatedobject
     * @param string &$action Hook action
     * @return int|string Status or print value
     */
    public function formattachOptions($parameters, &$object, &$action)
    {
        global $langs, $user, $db;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

        $dolibarr_version = versiondolibarrarray();

        $tokenstorage = TokenStorage::getUserToken($db, $user, true);
        if ($tokenstorage === false) {
            // We didn't get a token, bail out without error
            return 0;
        }

        $langs->load('zenfusiondrive@zenfusiondrive');
        $process = dol_buildpath('/zenfusiondrive/download.php', 1);

        // We only need the access token
        $tokenstorage = $tokenstorage->token->getAccessToken();
        if (($user->rights->zenfusiondrive->use || $user->admin) && $tokenstorage && $parameters['perm']) {
            $img = dol_buildpath('/zenfusiondrive/img/object_drive.png', 1);
            $filetitle = $langs->trans('ChooseFile');
            $filefoldertitle = $langs->trans('ChooseFileFolder');
            // TODO: factorize javascript
            $js = '
                <script type="text/javascript">
                // Google Picker API for the Google Drive import
                function googlePicker(e) {
                    // Event normalizing
                    if (!e) var e = window.event;
                    // Which button has been pressed
                    source = e.id;
                    gapi.load("picker", {"callback": createPicker});
                }

                // Create and render a Picker object for selecting files
                function createPicker() {
                    var view = new google.picker.DocsView()
                        .setIncludeFolders(true);

                    var pickerbuilder = new google.picker.PickerBuilder()
                        .addView(view)
                        .setTitle("' . $filetitle . '")
                        .setOAuthToken("' . $tokenstorage . '")
                        .setLocale("' . $this->getLocale() . '")
                        .setCallback(pickerCallback);

                    if (source == "linkfromdrive"){
                        view.setSelectFolderEnabled(true);
                        pickerbuilder.setTitle("' . $filefoldertitle . '");
                    }

                    picker = pickerbuilder.build();
                    picker.setVisible(true);
                }

                // Process the callback data
                function pickerCallback(data) {
                    switch (data.action) {
                        case google.picker.Action.PICKED:
                            destination = "' . $process
                                . '?id=" + encodeURIComponent(data.docs[0].id) + '
                                . '"&element=' . $object->element . ''
                                . '&ref=' . $object->ref . '";
                            if (source == "linkfromdrive") {
                                destination += "&objectid=' . $object->id . '&mode=link";
                            }
                            window.location = destination;
                        break;
                    }
                }
                </script>
                <script type="text/javascript" src="https://apis.google.com/js/api.js"></script>';

            $this->resprints = '<table width="100%" class="nobordernopadding"><tr><td>';
            $this->resprints .=
                '<button type="button" class="button" id="importfromdrive" onclick="googlePicker(this);">';
            $this->resprints .= '<img src="' . $img . '" alt="">&nbsp;' . $langs->trans("ImportFromGoogleDrive");
            $this->resprints .= '</button>';
            // Only if the link feature is available
            if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 5) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.5
                $this->resprints .= '<br>&nbsp;<br>';
                $this->resprints .= '<button type="button" class="button" id="linkfromdrive" onclick="googlePicker(this);">';
                $this->resprints .= '<img src="' . $img . '" alt="">&nbsp;' . $langs->trans("LinkFromGoogleDrive");
                $this->resprints .= '</button>';
            }
            $this->resprints .= '</td></tr></table><br>';
            $this->resprints .= $js;
        }
        if (($dolibarr_version[0] == 3 && $dolibarr_version[1] >= 3) || $dolibarr_version[0] > 3) { // DOL_VERSION >= 3.3
            return 0;
        } else {
            return $this->resprints;
        }
    }

    /**
     * Translate Dolibarr locale to Google Picker locale
     *
     * @return string Google Picker formatted locale
     */
    private function getLocale()
    {
        global $langs;
        $lang = $langs->getDefaultLang();
        switch ($lang) {
            case 'ar_SA':
                $locale = 'ar';
                break;
            case 'bg_BG':
                $locale = 'bg';
                break;
            case 'ca_ES':
                $locale = 'ca';
                break;
            case 'da_DK':
                $locale = 'da';
                break;
            case 'de_AT':
            case 'de_DE':
                $locale = 'de';
                break;
            case 'el_GR':
                $locale = 'el';
                break;
            case 'en_AU':
            case 'en_IN':
            case 'en_NZ':
            case 'en_SA':
            case 'en_US':
                $locale = 'en';
                break;
            case 'en_GB':
                $locale = 'en-GB';
                break;
            case 'es_AR':
            case 'es_HN':
            case 'es_MX':
            case 'es_PE':
            case 'es_PR':
                $locale = 'es-419';
                break;
            case 'es_ES':
                $locale = 'es';
                break;
            case 'et_EE':
                $locale = 'et';
                break;
            case 'fa_IR':
                $locale = 'fa';
                break;
            case 'fi_FI':
                $locale = 'fi';
                break;
            case 'fr_BE':
            case 'fr_CH':
            case 'fr_FR':
                $locale = 'fr';
                break;
            case 'fr_CA':
                $locale = 'fr-CA';
                break;
            case 'he_IL':
                $locale = 'iw';
                break;
            case 'hu_HU':
                $locale = 'hu';
                break;
            case 'is_IS':
                $locale = 'is';
                break;
            case 'it_IT':
                $locale = 'it';
                break;
            case 'ja_JP':
                $locale = 'ja';
                break;
            case 'nb_NO':
                $locale = 'no';
                break;
            case 'nl_BE':
            case 'nl_NL':
                $locale = 'nl';
                break;
            case 'pl_PL':
                $locale = 'pl';
                break;
            case 'pt_BR':
                $locale = 'pt-BR';
                break;
            case 'pt_PT':
                $locale = 'pt-PT';
                break;
            case 'ro_RO':
                $locale = 'ro';
                break;
            case 'ru_UA':
            case 'ru_RU':
                $locale = 'ru';
                break;
            case 'sl_SI':
                $locale = 'sl';
                break;
            case 'sv_SE':
                $locale = 'sv';
                break;
            case 'tr_TR':
                $locale = 'tr';
                break;
            case 'zh_CN':
                $locale = 'zh-CN';
                break;
            case 'zh_TW':
                $locale = 'zh-tw';
                break;
            default:
                $locale = 'en';
        }
        return $locale;
    }
}
