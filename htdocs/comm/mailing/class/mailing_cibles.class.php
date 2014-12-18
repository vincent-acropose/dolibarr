<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/comm/mailing/class/mailing.class.php
 *	\ingroup    mailing
 *	\brief      File of class to manage emailings module
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *	Class to manage emailings module
 */
class Mailing_cibles extends CommonObject
{
	public $element='mailing_cibles';
	public $table_element='mailing_cibles';


	/**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		// List of language codes for status
		//$this->statuts[1] = 'Envoyé';
		//$this->statuts[2] = 'Ouvert';
		//$this->statuts[3] = 'Désinscrit';
		//$this->statuts[4] = 'Click';
		$this->statuts[5] = 'HardBounce';
		$this->statuts[6] = 'SoftBounce';

		//$this->statutslogo[1] = 'statut0';
		//$this->statutslogo[2] = 'statut1';
		//$this->statutslogo[3] = 'statut5';
		//$this->statutslogo[4] = 'statut6';
		$this->statutslogo[5] = 'statut4';
		$this->statutslogo[6] = 'statut3';
	}


	/**
	 *  Retourne le libelle du statut d'un mailing (brouillon, validee, ...
	 *
	 *  @param	int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *  @return string        			Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$statut        	Id statut
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string        			Label
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('mails');

		if ($mode == 0)	return $langs->trans($this->statuts[$statut]);
		if ($mode == 1)	return $langs->trans($this->statuts[$statut]);
		if ($mode == 2)	return img_picto($langs->trans($this->statuts[$statut]),$this->statutslogo[$statut]).' '.$langs->trans($this->statuts[$statut]);
		if ($mode == 3)	return img_picto($langs->trans($this->statuts[$statut]),$this->statutslogo[$statut]);
		if ($mode == 4)	return img_picto($langs->trans($this->statuts[$statut]),$this->statutslogo[$statut]).' '.$langs->trans($this->statuts[$statut]);
		if ($mode == 5)	return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),$this->statutslogo[$statut]);

	}

}

