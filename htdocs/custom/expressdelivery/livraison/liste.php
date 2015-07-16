<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014	   Florian HENRY        <florian.henry@open-concept.pro>
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
 *      \file       expressdelivery/livraison/liste.php
 *      \ingroup    expedition
 *      \brief      Page to list all shipments
 */

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die ( "Include of main fails" );
require_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (! empty($conf->expedition_bon->enabled))
	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
if (! empty($conf->stock->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

$langs->load("sendings");
$langs->load('companies');
$langs->load('deliveries');

// Security check
$expeditionid = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expedition',$expeditionid,'');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');

if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortfield) $sortfield="e.ref";
if (! $sortorder) $sortorder="DESC";
$limit = $conf->liste_limit;


/*
 * View
 */

$companystatic=new Societe($db);
$shipment=new Expedition($db);

$helpurl='EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('',$langs->trans('ListOfSendings'),$helpurl);

$sql = "SELECT e.rowid, e.ref, e.date_delivery, e.fk_statut";
$sql.= ", s.nom as socname, s.rowid as socid";
$sql.= " FROM (".MAIN_DB_PREFIX."livraison as e";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql.= ")";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
$sql.= " WHERE e.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= " AND e.fk_soc = sc.fk_soc";
	$sql.= " AND sc.fk_user = " .$user->id;
}
if ($socid)
{
	$sql.= " AND e.fk_soc = ".$socid;
}
if (GETPOST('bl_ref','alpha'))
{
	$sql.= " AND e.ref like '%".$db->escape(GETPOST('bl_ref','alpha'))."%'";
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1,$offset);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$delivery = new Livraison($db);

	$param="&amp;socid=$socid";

	print_barre_liste($langs->trans('ListOfDelivery'), $page, "liste.php",$param,$sortfield,$sortorder,'',$num);


	$i = 0;
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"liste.php","e.ref","",$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),"liste.php","s.nom", "", $param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateReceived"),"liste.php","e.date_delivery","",$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"liste.php","e.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";
	$var=True;

	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);

		$var=!$var;
		print "<tr ".$bc[$var].">";
		print "<td>";
		$delivery->id=$objp->rowid;
		$delivery->ref=$objp->ref;
		print $delivery->getNomUrl(1);
		print "</td>\n";
		// Third party
		print '<td>';
		$companystatic->id=$objp->socid;
		$companystatic->ref=$objp->socname;
		$companystatic->nom=$objp->socname;
		print $companystatic->getNomUrl(1);
		print '</td>';
		// Date delivery  planed
		print "<td align=\"center\">";
		print dol_print_date($db->jdate($objp->date_delivery),"day");
		/*$now = time();
		if ( ($now - $db->jdate($objp->date_expedition)) > $conf->warnings->lim && $objp->statutid == 1 )
		{
		}*/
		print "</td>\n";
		// Date real

		print '<td align="right">'.$delivery->LibStatut($objp->fk_statut,4).'</td>';
		print "</tr>\n";

		$i++;
	}

	print "</table>";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter();
?>
