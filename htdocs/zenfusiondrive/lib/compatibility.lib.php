<?php
/*
 * ZenFusion Drive - A Google Drive module for Dolibarr
 * Copyright (C) 2013 Cédric Salvador <csalvador@gpcsolutions.fr>
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
 * \file lib/compatibility.lib.php
 * \ingroup zenfusiondrive
 * \brief Functions used to keep compatibility with Dolibarr < 3.4
 */

/**
 * Security check when accessing to a document (used by document.php, viewimage.php and webservices)
 *
 * @param	string      $modulepart		Module of document
 * @param	string      $original_file	Relative path with filename
 * @param	string      $entity			Restrict onto entity
 * @param  	User|null   $fuser			User object (forced)
 * @param	string      $refname		Ref of object to check permission for external users (autodetect if not provided)
 * @return	array|string                Array with access information:
 *                                      accessallowed & sqlprotectagainstexternals & original_file (as full path name)
 */
function dol_check_secure_access_document($modulepart, $original_file, $entity, $fuser = null, $refname = '')
{
    global $user, $conf, $db;

    if (! is_object($fuser)) {
        $fuser=$user;
    }

    if (empty($modulepart)) {
        return 'ErrorBadParameter';
    }
    if (empty($entity)) {
        $entity=0;
    }
    dol_syslog('modulepart='.$modulepart.' original_file= '.$original_file);
    // We define $accessallowed and $sqlprotectagainstexternals
    $accessallowed=0;
    $sqlprotectagainstexternals='';

    // find the subdirectory name as the reference
    if (empty($refname)) {
        $refname=basename(dirname($original_file)."/");
    }

    if ($modulepart == 'companylogo') {
        // Wrapping for some images
        $accessallowed=1;
        $original_file=$conf->mycompany->dir_output.'/logos/'.$original_file;
    } elseif ($modulepart == 'userphoto') {
        // Wrapping for users photos
        $accessallowed=1;
        $original_file=$conf->user->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'memberphoto') {
        // Wrapping for members photos
        $accessallowed=1;
        $original_file=$conf->adherent->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'apercufacture') {
        // Wrapping for invoices previews
        if ($fuser->rights->facture->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'apercupropal') {
        // Wrapping for proposals previews
        if ($fuser->rights->propale->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->propal->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'apercucommande') {
        // Wrapping for orders previews
        if ($fuser->rights->commande->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->commande->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'apercufichinter') {
        // Wrapping for interventions previews
        if ($fuser->rights->ficheinter->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->ficheinter->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'propalstats') {
        // Wrapping for proposals statistic images
        if ($fuser->rights->propale->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->propal->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'orderstats') {
        // Wrapping for orders statistic images
        if ($fuser->rights->commande->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->commande->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'orderstatssupplier') {
        if ($fuser->rights->fournisseur->commande->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->dir_output.'/commande/temp/'.$original_file;
    } elseif ($modulepart == 'billstats') {
        // Wrapping for invoices statistic images
        if ($fuser->rights->facture->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'billstatssupplier') {
        if ($fuser->rights->fournisseur->facture->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->dir_output.'/facture/temp/'.$original_file;
    } elseif ($modulepart == 'expeditionstats') {
        // Wrapping for expeditions statistic images
        if ($fuser->rights->expedition->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->expedition->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'tripsexpensesstats') {
        if ($fuser->rights->deplacement->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->deplacement->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'memberstats') {
        if ($fuser->rights->adherent->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->adherent->dir_temp.'/'.$original_file;
    } elseif (preg_match('/^productstats_/i', $modulepart)) {
        if ($fuser->rights->produit->lire || $fuser->rights->service->lire) {
            $accessallowed=1;
        }
        $original_file=(!empty($conf->product->multidir_temp[$entity])?$conf->product->multidir_temp[$entity]:$conf->service->multidir_temp[$entity]).'/'.$original_file;
    } elseif ($modulepart == 'tax') {
        if ($fuser->rights->tax->charges->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->tax->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'actions') {
        if ($fuser->rights->agenda->myactions->read) {
            $accessallowed=1;
        }
        $original_file=$conf->agenda->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'category') {
        // Wrapping for categories
        if ($fuser->rights->categorie->lire) {
            $accessallowed=1;
        }
        $original_file=$conf->categorie->multidir_output[$entity].'/'.$original_file;
    } elseif ($modulepart == 'prelevement') {
        // Wrapping pour les prelevements
        if ($fuser->rights->prelevement->bons->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->prelevement->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'graph_stock') {
        // Wrapping for stock graphics
        $accessallowed=1;
        $original_file=$conf->stock->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'graph_fourn') {
        // Wrapping for suppliers graphics
        $accessallowed=1;
        $original_file=$conf->fournisseur->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'graph_product') {
        // Wrapping for products graphics
        $accessallowed=1;
        $original_file=$conf->product->multidir_temp[$entity].'/'.$original_file;
    } elseif ($modulepart == 'barcode') {
        // Wrapping for barcodes
        $accessallowed=1;
        // If viewimage is called for barcode, we try to output an image on the fly,
        // with not build of file on disk.
        //$original_file=$conf->barcode->dir_temp.'/'.$original_file;
        $original_file='';
    } elseif ($modulepart == 'iconmailing') {
        // Wrapping pour les icones de background des mailings
        $accessallowed=1;
        $original_file=$conf->mailing->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'scanner_user_temp') {
        $accessallowed = 1;
        $original_file = $conf->scanner->dir_temp . '/' . $fuser->id . '/' . $original_file;
    } elseif ($modulepart == 'fckeditor') {
        // Wrapping fckeditor images
        $accessallowed=1;
        $original_file=$conf->fckeditor->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'company' || $modulepart == 'societe') {
        // Wrapping for third parties
        if ($fuser->rights->societe->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->societe->multidir_output[$entity].'/'.$original_file;
        $sqlprotectagainstexternals = "SELECT rowid as fk_soc FROM ".MAIN_DB_PREFIX."societe WHERE rowid='".$db->escape($refname)."' AND entity IN (".getEntity('societe', 1).")";
    } elseif ($modulepart == 'facture' || $modulepart == 'invoice') {
        // Wrapping for invoices
        if ($fuser->rights->facture->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_output.'/'.$original_file;
        $sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
    } elseif ($modulepart == 'unpaid') {
        if ($fuser->rights->facture->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_output.'/unpaid/temp/'.$original_file;
    } elseif ($modulepart == 'ficheinter') {
        // Wrapping pour les fiches intervention
        if ($fuser->rights->ficheinter->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->ficheinter->dir_output.'/'.$original_file;
        $sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
    } elseif ($modulepart == 'deplacement') {
        // Wrapping pour les deplacements et notes de frais
        if ($fuser->rights->deplacement->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->deplacement->dir_output.'/'.$original_file;
        //$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
    } elseif ($modulepart == 'propal') {
        // Wrapping for proposals
        if ($fuser->rights->propale->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->propal->dir_output.'/'.$original_file;
        $sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."propal WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
    } elseif ($modulepart == 'commande' || $modulepart == 'order') {
        // Wrapping for orders
        if ($fuser->rights->commande->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->commande->dir_output.'/'.$original_file;
        $sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
    } elseif ($modulepart == 'project') {
        // Wrapping for projects
        if ($fuser->rights->projet->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->projet->dir_output.'/'.$original_file;
        $sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."projet WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
    } elseif ($modulepart == 'commande_fournisseur' || $modulepart == 'order_supplier') {
        if ($fuser->rights->fournisseur->commande->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->commande->dir_output.'/'.$original_file;
        $sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
    } elseif ($modulepart == 'facture_fournisseur' || $modulepart == 'invoice_supplier') {
        // Wrapping for supplier invoices
        if ($fuser->rights->fournisseur->facture->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->facture->dir_output.'/'.$original_file;
        $sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture_fourn WHERE facnumber='".$db->escape($refname)."' AND entity=".$conf->entity;
    } elseif ($modulepart == 'facture_paiement') {
        // Wrapping pour les rapport de paiements
        if ($fuser->rights->facture->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        if ($fuser->societe_id > 0) {
            $original_file=$conf->facture->dir_output.'/payments/private/'.$fuser->id.'/'.$original_file;
        } else {
            $original_file=$conf->facture->dir_output.'/payments/'.$original_file;
        }
    } elseif ($modulepart == 'export_compta') {
        // Wrapping pour les exports de compta
        if ($fuser->rights->compta->ventilation->creer || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->compta->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'expedition') {
        // Wrapping pour les expedition
        if ($fuser->rights->expedition->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed = 1;
        }
        $original_file = $conf->expedition->dir_output . "/sending/" . $original_file;
    } elseif ($modulepart == 'livraison') {
        // Wrapping pour les bons de livraison
        if ($fuser->rights->expedition->livraison->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->expedition->dir_output."/receipt/".$original_file;
    } elseif ($modulepart == 'actions') {
        // Wrapping for actions
        if ($fuser->rights->agenda->myactions->read || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->agenda->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'actionsreport') {
        // Wrapping for actions reports
        if ($fuser->rights->agenda->allactions->read || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file = $conf->agenda->dir_temp."/".$original_file;
    } elseif ($modulepart == 'product' || $modulepart == 'produit' || $modulepart == 'service') {
        // Wrapping for products and services
        if (
            ($fuser->rights->produit->lire || $fuser->rights->service->lire) ||
            preg_match('/^specimen/i', $original_file)
        ) {
            $accessallowed=1;
        }
        if (! empty($conf->product->enabled)) {
            $original_file=$conf->product->multidir_output[$entity].'/'.$original_file;
        } elseif (! empty($conf->service->enabled)) {
            $original_file=$conf->service->multidir_output[$entity].'/'.$original_file;
        }
    } elseif ($modulepart == 'contract') {
        // Wrapping for contrats
        if ($fuser->rights->contrat->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->contrat->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'donation') {
        // Wrapping for donations
        if ($fuser->rights->don->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->don->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'remisecheque') {
        // Wrapping pour les remises de cheques
        if ($fuser->rights->banque->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->banque->dir_output . '/bordereau/' . get_exdir(basename($original_file, ".pdf"), 2, 1) . $original_file;
    } elseif ($modulepart == 'export') {
        // Wrapping for export module
        // Aucun test necessaire car on force le rep de download sur
        // le rep export qui est propre a l'utilisateur
        $accessallowed=1;
        $original_file=$conf->export->dir_temp.'/'.$fuser->id.'/'.$original_file;
    } elseif ($modulepart == 'import') {
        // Wrapping for import module
        // Aucun test necessaire car on force le rep de download sur
        // le rep export qui est propre a l'utilisateur
        $accessallowed=1;
        $original_file=$conf->import->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'editor') {

        // Wrapping pour l'editeur wysiwyg
        // Aucun test necessaire car on force le rep de download sur
        // le rep export qui est propre a l'utilisateur
        $accessallowed = 1;
        $original_file = $conf->fckeditor->dir_output . '/' . $original_file;
    } elseif ($modulepart == 'systemtools') {
        // Wrapping pour les backups
        if ($fuser->admin) {
            $accessallowed=1;
        }
        $original_file=$conf->admin->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'admin_temp') {
        // Wrapping for upload file test
        if ($fuser->admin) {
            $accessallowed = 1;
        }
        $original_file=$conf->admin->dir_temp.'/'.$original_file;
    } elseif ($modulepart == 'bittorrent') {
        // Wrapping pour BitTorrent
        $accessallowed=1;
        $dir='files';
        if ($type == 'application/x-bittorrent') {
            $dir='torrents';
        }
        $original_file=$conf->bittorrent->dir_output.'/'.$dir.'/'.$original_file;
    } elseif ($modulepart == 'member') {
        // Wrapping pour Foundation module
        if ($fuser->rights->adherent->lire || preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        $original_file=$conf->adherent->dir_output.'/'.$original_file;
    } elseif ($modulepart == 'scanner_user_temp') {
        // Wrapping for Scanner
        $accessallowed=1;
        $original_file=$conf->scanner->dir_temp.'/'.$fuser->id.'/'.$original_file;
    } else {
        // GENERIC Wrapping
        // If modulepart=module_user_temp	Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart/temp/iduser
        // If modulepart=module_temp		Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart/temp
        // If modulepart=module_user		Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart/iduser
        // If modulepart=module				Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart

        // Define $accessallowed
        if (preg_match('/^([a-z]+)_user_temp$/i', $modulepart, $reg)) {
            if ($fuser->rights->$reg[1]->lire || $fuser->rights->$reg[1]->read || ($fuser->rights->$reg[1]->download)) {
                $accessallowed=1;
            }
            $original_file=$conf->$reg[1]->dir_temp.'/'.$fuser->id.'/'.$original_file;
        } elseif (preg_match('/^([a-z]+)_temp$/i', $modulepart, $reg)) {
            if ($fuser->rights->$reg[1]->lire || $fuser->rights->$reg[1]->read || ($fuser->rights->$reg[1]->download)) {
                $accessallowed=1;
            }
            $original_file=$conf->$reg[1]->dir_temp.'/'.$original_file;
        } elseif (preg_match('/^([a-z]+)_user$/i', $modulepart, $reg)) {
            if ($fuser->rights->$reg[1]->lire || $fuser->rights->$reg[1]->read || ($fuser->rights->$reg[1]->download)) {
                $accessallowed=1;
            }
            $original_file=$conf->$reg[1]->dir_output.'/'.$fuser->id.'/'.$original_file;
        } else {
            $perm=GETPOST('perm');
            $subperm=GETPOST('subperm');
            if ($perm || $subperm) {
                if (($perm && ! $subperm && $fuser->rights->$modulepart->$perm) || ($perm && $subperm && $fuser->rights->$modulepart->$perm->$subperm)) {
                    $accessallowed=1;
                }
                $original_file=$conf->$modulepart->dir_output.'/'.$original_file;
            } else {
                if ($fuser->rights->$modulepart->lire || $fuser->rights->$modulepart->read) {
                    $accessallowed=1;
                }
                $original_file=$conf->$modulepart->dir_output.'/'.$original_file;
            }
        }
        // If link to a specimen
        if (preg_match('/^specimen/i', $original_file)) {
            $accessallowed=1;
        }
        // If user is admin
        if ($fuser->admin) {
            $accessallowed=1;
        }

        // For modules who wants to manage different levels of permissions for documents
        $subPermCategoryConstName = strtoupper($modulepart).'_SUBPERMCATEGORY_FOR_DOCUMENTS';
        if (! empty($conf->global->$subPermCategoryConstName)) {
            $subPermCategory = $conf->global->$subPermCategoryConstName;
            if (
                ! empty($subPermCategory) &&
                (
                    ($fuser->rights->$modulepart->$subPermCategory->lire) ||
                    ($fuser->rights->$modulepart->$subPermCategory->read) ||
                    ($fuser->rights->$modulepart->$subPermCategory->download)
                )
            ) {
                $accessallowed=1;
            }
        }

        // Define $sqlprotectagainstexternals for modules who want to protect access using a SQL query.
        $sqlProtectConstName = strtoupper($modulepart).'_SQLPROTECTAGAINSTEXTERNALS_FOR_DOCUMENTS';
        // If module want to define its own $sqlprotectagainstexternals
        if (! empty($conf->global->$sqlProtectConstName)) {
            // Example: mymodule__SQLPROTECTAGAINSTEXTERNALS_FOR_DOCUMENTS = "SELECT fk_soc FROM ".MAIN_DB_PREFIX.$modulepart." WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
            eval('$sqlprotectagainstexternals = "'.$conf->global->$sqlProtectConstName.'";');
        }
    }

    $ret = array(
        'accessallowed' => $accessallowed,
        'sqlprotectagainstexternals'=>$sqlprotectagainstexternals,
        'original_file'=>$original_file
    );

    return $ret;
}
