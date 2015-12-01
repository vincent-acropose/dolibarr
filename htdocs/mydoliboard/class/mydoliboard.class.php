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
 *	\file       htdocs/mylist/class/mylist.class.php
 *	\ingroup    base
 *	\brief      File of class to manage personnalised lists
 */


/**
 *	Class to manage page of doliboard
 */
class Mydoliboard extends CommonObject
{
	public $element='mydoliboard';
	public $table_element='mydoliboard';

	var $rowid;
	var $label;
	var $titlemenu;
	var $mainmenu;
	var $leftmenu;
	var $posmenu;
	var $elementtab;
	var $idmenu;
	var $description;
	var $paramfields;					// permet de gérer les paramètres supplémentaires
	var $perms;
	var $langs;
	var $blocAmode;
	var $blocBmode;
	var $blocCmode;
	var $blocDmode;
	var $blocAtitle;
	var $blocBtitle;
	var $blocCtitle;
	var $blocDtitle;
	var $author;
	var $active;

	var $idreftab; // id de l'élément si on est en mode onglet

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	function GenFilterInitFieldsTables ()
	{
		global $langs, $form, $conf;
		$tblInitFields=explode("#",$this->paramfields);
		foreach ($tblInitFields as $initfields ) 
		{
			$tblInitField=explode("=",$initfields);
			$fieldinit =$tblInitField[0];
			$valueinit = GETPOST($fieldinit);

			// on prend la valeur par défaut si la valeur n'est pas saisie...
			if (!$valueinit)
				$valueinit = $tblInitField[1];
			$tmp.= '<div STYLE="float:left;"><table width=100%>'; 
			$tmp.= '<tr class="liste_titre"><td>'.$langs->trans($fieldinit). ' : '.'</td>';
			$tmp.= '<td>';
			$tmp.='<input type="text" name='.$tblInitField[0]." value='".$valueinit."'>";				
			$tmp.= '</td></tr>';
			$tmp.= '</table></div>'; 
		}
		return $tmp;
	}

	function GenParamFilterInitFields ()
	{
		global $langs, $form, $conf;
		$tblInitFields=explode("#",$this->paramfields);
		foreach ($tblInitFields as $initfields ) 
		{
			$tblInitField=explode("=",$initfields);
			$fieldinit =$tblInitField[0];
			$valueinit = GETPOST($fieldinit);

			// on prend la valeur par défaut si la valeur n'est pas saisie...
			if (!$valueinit)
				$valueinit = $tblInitField[1];
			$tmp.='&'.$tblInitField[0]."=".$valueinit;
		}
		return $tmp;
	}

	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.dol_buildpath('/mydoliboard/fiche.php?rowid='.$this->rowid,1).'">';
		$lienfin='</a>';

		$picto='list';

		$label=$langs->trans("Show").': '.$this->label;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->label.$lienfin;
		return $result;
	}

	/**
	 *      Build the conditionnal string from filter the query
	 *
	 *      @param		string	$TypeField		Type of Field to filter
	 *      @param		string	$NameField		Name of the field to filter
	 *      @param		string	$ValueField		Initial value of the field to filter
	 *      @return		string					sql string of then field ex : "field='xxx'>"
	 */
	function build_filterQuery($TypeField, $NameField, $ValueField)
	{
	//print $TypeField."=".$NameField."=".$ValueField.'<br>';

	if ($ValueField!="")
	{	
		// build the input field on depend of the type of file
		switch ($TypeField) {
			case 'Text':
				if (! (strpos($ValueField, '%') == false))
					$szFilterQuery.=" and ".$NameField." LIKE '".$ValueField."'";
				else
					$szFilterQuery.=" and ".$NameField." LIKE '%".$ValueField."%'";
				break;
			case 'Date':
				if (strpos($ValueField, "+") > 0)
				{
					// mode plage
					$ValueArray = explode("+", $ValueField);
					$szFilterQuery =" and (".$this->conditionDate($NameField,$ValueArray[0],">=");
					$szFilterQuery.=" AND ".$this->conditionDate($NameField,$ValueArray[1],"<=").")";
				}
				else
				{
					if (is_numeric(substr($ValueField,0,1)))
						$szFilterQuery=" and ".$this->conditionDate($NameField,$ValueField,"=");
					else
						$szFilterQuery=" and ".$this->conditionDate($NameField,substr($ValueField,1),substr($ValueField,0,1));
				}
				break;
			case 'Duree':
			case 'Number':
			case 'Sum':
				// si le signe -
				if (strpos($ValueField, "+") > 0)
				{
					// mode plage
					$ValueArray = explode("+", $ValueField);
					$szFilterQuery =" AND (".$NameField.">=".$ValueArray[0];
					$szFilterQuery.=" AND ".$NameField."<=".$ValueArray[1].")";
				}
				else
				{
					if (is_numeric(substr($ValueField,0,1)))
						$szFilterQuery=" and ".$NameField."=".$ValueField;
					else
						$szFilterQuery=" and ".$NameField.substr($ValueField,0,1).substr($ValueField,1);
				}
				break;

			case 'Boolean':
				$szFilterQuery=" and ".$NameField."=".(is_numeric($ValueField) ? $ValueField : ($ValueField =='yes' ? 1: 0) );
				break;
			case 'Statut':
				$szFilterQuery=" and ".$NameField."=".$ValueField;
				break;
			case 'List':
				if (is_numeric($ValueField))
					$szFilterQuery=" and ".$NameField."=".$ValueField;
				else
					$szFilterQuery=" and ".$NameField."='".$ValueField."'";
				break;
		}
	}
	return $szFilterQuery;
	}

	/**
	 * 	Load doliboard into memory from database
	 *
	 * 	@param		int		$code		code of listable
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		global $conf;

		$sql = "SELECT label, description, paramfields, mainmenu, leftmenu, elementtab, perms, titlemenu, langs, author, active";
		$sql.= " ,blocAmode, blocBmode, blocCmode, blocDmode ,blocAtitle, blocBtitle, blocCtitle, blocDtitle";
		$sql.= " FROM ".MAIN_DB_PREFIX."mydoliboard";
		$sql.= " WHERE rowid = ".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);

				$this->rowid		= $rowid;
				$this->label		= $res['label'];
				$this->description	= $res['description'];
				$this->mainmenu		= $res['mainmenu'];
				$this->leftmenu		= $res['leftmenu'];
				$this->elementtab	= $res['elementtab'];
				$this->titlemenu	= $res['titlemenu'];
				$this->perms		= $res['perms'];
				$this->langs		= $res['langs'];
				$this->author		= $res['author'];
				$this->blocAmode	= $res['blocAmode'];
				$this->blocBmode	= $res['blocBmode'];
				$this->blocCmode	= $res['blocCmode'];
				$this->blocDmode	= $res['blocDmode'];
				$this->blocAtitle	= $res['blocAtitle'];
				$this->blocBtitle	= $res['blocBtitle'];
				$this->blocCtitle	= $res['blocCtitle'];
				$this->blocDtitle	= $res['blocDtitle'];
				$this->active		= $res['active'];
				$this->paramfields	= $res['paramfields'];
				$this->db->free($resql);

				return 1;
			}
			else
				return 0;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/*  Get the right position menu value for new */
	function getposmenu($titlemenu, $mainmenu, $leftmenu)
	{
		// gestion de la position du menu
		$sql="SELECT max(position) as posmenu FROM ".MAIN_DB_PREFIX."menu";
		$sql.=" WHERE fk_mainmenu ='".trim($mainmenu)."'";
		$sql.=" AND fk_leftmenu ='".trim($leftmenu)."'";
		$sql.=" AND titre <> '".trim($titlemenu)."'";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				// on rajoute 1 à la derniere liste présente
				if ($res['posmenu'] >= 100)
					return $res['posmenu']+1;
			}
		}
		// on renvoie la valeur par défaut dans tous les autres cas
		return 100;
	}


	/**
	 * 	Add mydoliboard into database
	 *
	 * 	@param	User	$user		Object user
	 * 	@return	int 				-1 : erreur SQL

	 */
	function create($user='')
	{
		global $conf, $langs, $user;
		$langs->load('mydoliboard@mydoliboard');

		$error=0;

		$this->label=(!is_array($this->label)?trim($this->label):'');
		$this->description=(!is_array($this->description)?trim($this->description):'');
		$this->perms=(!is_array($this->perms)?trim($this->perms):'');
		$this->langs=(!is_array($this->langs)?trim($this->langs):'');
		$this->titlemenu = trim($this->titlemenu);
		$this->mainmenu = trim($this->mainmenu);
		$this->leftmenu = trim($this->leftmenu);
		$this->elementtab = trim($this->elementtab);
		$this->author=(!is_array($this->author)?trim($this->author):'');
		$this->paramfields=(!is_array($this->paramfields)?trim($this->paramfields):'');
		$this->blocAtitle=(!is_array($this->blocAtitle)?trim($this->blocAtitle):'');
		$this->blocBtitle=(!is_array($this->blocBtitle)?trim($this->blocBtitle):'');
		$this->blocCtitle=(!is_array($this->blocCtitle)?trim($this->blocCtitle):'');
		$this->blocDtitle=(!is_array($this->blocDtitle)?trim($this->blocDtitle):'');

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mydoliboard (";
		$sql.= " label,";
		$sql.= " description,";
		$sql.= " titlemenu,";
		$sql.= " perms,";
		$sql.= " langs,";
		$sql.= " mainmenu,";
		$sql.= " leftmenu,";
		$sql.= " elementtab,";
		$sql.= " author,";
		$sql.= " active,";
		$sql.= " blocAmode,";
		$sql.= " blocBmode,";
		$sql.= " blocCmode,";
		$sql.= " blocDmode,";
		$sql.= " blocAtitle,";
		$sql.= " blocBtitle,";
		$sql.= " blocCtitle,";
		$sql.= " blocDtitle,";
		$sql.= " paramfields";
		$sql.= ") VALUES (";
		$sql.= " ".(! isset($this->label)?'NULL':"'".$this->db->escape($this->label)."'");
		$sql.= ", ".(! isset($this->description)?'NULL':"'".$this->db->escape($this->description)."'");
		$sql.= ", ".(! isset($this->titlemenu)?'NULL':"'".$this->db->escape($this->titlemenu)."'");
		$sql.= ", ".(! isset($this->perms)?'NULL':"'".$this->db->escape($this->perms)."'");
		$sql.= ", ".(! isset($this->langs)?'NULL':"'".$this->db->escape($this->langs)."'");
		$sql.= ", ".(! isset($this->mainmenu)?'NULL':"'".$this->db->escape($this->mainmenu)."'");
		$sql.= ", ".(! isset($this->leftmenu)?'NULL':"'".$this->db->escape($this->leftmenu)."'");
		$sql.= ", ".(! isset($this->elementtab)?'NULL':"'".$this->db->escape($this->elementtab)."'");
		$sql.= ", ".(! isset($this->author)?'NULL':"'".$this->db->escape($this->author)."'");
		$sql.= ", ".(! isset($this->blocAmode)?'NULL':$this->blocAmode);
		$sql.= ", ".(! isset($this->blocBmode)?'NULL':$this->blocBmode);
		$sql.= ", ".(! isset($this->blocCmode)?'NULL':$this->blocCmode);
		$sql.= ", ".(! isset($this->blocDmode)?'NULL':$this->blocDmode);
		$sql.= ", ".(! isset($this->blocAtitle)?'NULL':"'".$this->db->escape($this->blocAtitle)."'");
		$sql.= ", ".(! isset($this->blocBtitle)?'NULL':"'".$this->db->escape($this->blocBtitle)."'");
		$sql.= ", ".(! isset($this->blocCtitle)?'NULL':"'".$this->db->escape($this->blocCtitle)."'");
		$sql.= ", ".(! isset($this->blocDtitle)?'NULL':"'".$this->db->escape($this->blocDtitle)."'");
		$sql.= ", 0";  // by default the new list is not active
		$sql.= ", ".(! isset($this->paramfields)?'NULL':"'".$this->db->escape($this->paramfields)."'");
		$sql.= ")";
//print $sql;
		dol_syslog(get_class($this).'::create sql='.$sql);
		if ($this->db->query($sql))
		{
			// récup du dernier rowid
			$this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."mydoliboard");
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::create error ".$this->error." sql=".$sql, LOG_ERR);
			$this->db->rollback();
			return 0;
		}
	}

	function genboard($bloc)
	{
		global $langs, $dolibarr_main_db_prefix;
		// récupération des requetes de ce bloc
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mydoliboardsheet";
		$sql.= " WHERE fk_mdbpage = ".$this->rowid;
		$sql.= " AND displaycell = '".$bloc."'";
		$sql.= " AND active = 1";
		$sql.= " ORDER BY cellorder";

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$sz="";
			$cats = array ();
			
			// boucle sur les colonnes
			$nbcol=0;

			while ($rec = $this->db->fetch_array($resql))
			{
				$sz.="<h3>".$rec['titlesheet']."</h3>";
				$sz.="<p>".$rec['description']."</p><br>";
				// on  récupère le fichier de lang
				if ($rec['langs'])
					foreach(explode(":", $rec['langs']) as $newlang)
						$langs->load($newlang);
				// exécution de la requete
				$sqlquerydisp=$rec['querydisp'];
				// gestion du llx_
				if ($dolibarr_main_db_prefix != 'llx_')
					$sqlquerydisp= " ".preg_replace('/llx_/i',$dolibarr_main_db_prefix, $sqlquerydisp);

				// on récupère si besoin les valeurs saisies
				if ($this->paramfields)
				{
					$tblInitFields=explode("#",$this->paramfields);
					foreach ($tblInitFields as $initfields ) 
					{
						$tblInitField=explode("=",$initfields);
						$fieldinit =$tblInitField[0];
						if (GETPOST($fieldinit))
							$valueinit = GETPOST($fieldinit);
						else
							$valueinit = $tblInitField[1];
						$sqlquerydisp=str_replace("#".$fieldinit."#", $valueinit, $sqlquerydisp);
					}
				}

				// filtre sur l'id de l'élément en mode tabs
				if (!empty($this->elementtab) && $this->idreftab != "")
				{
					switch($this->elementtab) {
						case 'Societe' :
							$sqlquerydisp=str_replace("#FILTERTAB#", $sqlfilter.=" AND s.rowid=".$this->idreftab, $sqlquerydisp);
							break;

						case 'Product' :
							$sqlquerydisp=str_replace("#FILTERTAB#", " AND p.rowid=".$this->idreftab, $sqlquerydisp);
							break;

						case 'CategProduct' :
						case 'CategSociete' :
							//$sql.=", srowid as elementrowid";
							$sqlquerydisp=str_replace("#FILTERTAB#", " AND c.rowid=".$this->idreftab, $sqlquerydisp);
							break;
					}
				}
				else
					$sqlquerydisp=str_replace("#FILTERTAB#", "", $sqlquerydisp);

				if (GETPOST("sqltest")=="1")
					print $sqlquerydisp;
				$resboardsql = $this->db->query($sqlquerydisp);

				$num = $this->db->num_rows($resboardsql);
				if ($num>0)
				{	
					$sz.='<table class="border" width="100%"><tr class="liste_titre">';
					// boucle sur les colonnes
					$nbcol=0;
					while ($finfo = $resboardsql->fetch_field())
					{
						$sz.="<th>".$langs->trans($finfo->name)."</th>";
						$tabletype[$nbcol]=$finfo->type;
						$nbcol++;
					}
					$sz.="</tr>";
					$width=round(85/($nbcol-1),0);
					while ($rec = $this->db->fetch_object($resboardsql))
					{	
						$sz.="<tr>";
						// boucle sur les colonnes
						$nbcol=0;
						foreach ($rec as $valfield ) 
						{
							if ($tabletype[$nbcol]==1) // boolean
								$sz.="<td align=center width=".$width."% >".yn($valfield)."</td>";
							elseif ($tabletype[$nbcol]==10) // date
								$sz.="<td align=center width='70px' >".dol_print_date($this->db->jdate($valfield),'day')."</td>";
							elseif ($tabletype[$nbcol]==253) // text
								$sz.="<td align=left width=".$width."% >".$valfield."</td>";

							elseif ($tabletype[$nbcol]==5 || $tabletype[$nbcol]==3 || $tabletype[$nbcol]==246) // numérique
								$sz.="<td align=right width=".$width."% >".round($valfield,2)."</td>";
							else 
								// all the other type
								$sz.="<td align=center width=".$width."% >".$tabletype[$nbcol]."--".$valfield."</td>";
							
								
							$nbcol++;
						}
						$sz.="</tr>";
					}
					$sz.="</table></br>";
				}
			}
		}
		return $sz;
	}

	/**
	 * 	Generate Graphical board
	 *
	 * 	@param		text		$bloc	display cell (A to D)
	 * 	@param		int			$mode	display mode  1 = sheet+ graph, 2 = graph only, 3 = sheet only
	 * 	@return		void
	 */
	function gengraph($bloc, $mode, $title)
	{
		global $langs, $conf, $dolibarr_main_db_prefix;
		require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

		// Definition de $width et $height
		$width = 600;
		$height = 300;
		// récupération des requetes de ce bloc
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mydoliboardsheet";
		$sql.= " WHERE fk_mdbpage = ".$this->rowid;
		$sql.= " AND displaycell = '".$bloc."'";
		$sql.= " AND active = 1";
		$sql.= " order by cellorder";

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$tablesgraph = array ();
			$ligne=0;
			while ($rec = $this->db->fetch_array($resql))
			{
				$titreligne[$ligne]=$rec['titlesheet'];
				$descligne[$ligne]=$rec['description'];
				// on  récupère le fichier de lang
				if ($rec['langs'])
					foreach(explode(":", $rec['langs']) as $newlang)
						$langs->load($newlang);
				// exécution de la requete
				$sqlquerydisp=$rec['querydisp'];
				// gestion du llx_
				if ($dolibarr_main_db_prefix != 'llx_')
					$sqlquerydisp= " ".preg_replace('/llx_/i',$dolibarr_main_db_prefix, $sqlquerydisp);

				// on récupère si besoin les valeurs saisies
				if ($this->paramfields)
				{
					$tblInitFields=explode(":",$this->paramfields);
					foreach ($tblInitFields as $initfields ) 
					{
						$tblInitField=explode("=",$initfields);
						$fieldinit =$tblInitField[0];
						if (GETPOST($fieldinit))
							$valueinit = GETPOST($fieldinit);
						else
						$valueinit = $tblInitField[1];
						$sqlquerydisp=str_replace("#".$fieldinit."#", $valueinit, $sqlquerydisp);
					}
				}
				
				// filtre sur l'id de l'élément en mode tabs
				if (!empty($this->elementtab) && $this->idreftab != "")
				{
					switch($myliststatic->elementtab) {
						case 'Societe' :
							//$sql.=", srowid as elementrowid";
							// il faut la table societe as s
							$sqlquerydisp=str_replace("#FILTERTAB#", $sqlfilter.=" AND s.rowid=".$this->idreftab, $sqlquerydisp);
							break;
						case 'Product' :
							// il faut la table product as p
							$sqlquerydisp=str_replace("#FILTERTAB#", $sqlfilter.=" AND p.rowid=".$this->idreftab, $sqlquerydisp);
							break;
						case 'CategProduct' :
						case 'CategSociete' :
							// il faut la table categories as c
							$sqlquerydisp=str_replace("#FILTERTAB#", $sqlfilter.=" AND c.rowid=".$this->idreftab, $sqlquerydisp);
							break;
					}
				}
				else
					$sqlquerydisp=str_replace("#FILTERTAB#", "", $sqlquerydisp);

				if (GETPOST("sqltest")=="1")
					print $sqlquerydisp.'<br>';
				$resboardsql = $this->db->query($sqlquerydisp);
				if ($resboardsql)
				{
					$num = $this->db->num_rows($resboardsql);
					if ($num > 0)
					{	
						// boucle sur les colonnes
						$nbcol=0;
						while ($finfo = $resboardsql->fetch_field())
						{
							$header[$nbcol] =$langs->trans($finfo->name);
							$headertype[$nbcol] =$finfo->type;
							$nbcol++;
						}
						
						while ($rec = $this->db->fetch_object($resboardsql))
						{	
							$nbcol=0;
							// boucle sur les colonnes
							foreach ($rec as $valfield ) 
							{
								$tblligne[$nbcol]=$valfield;
								$nbcol++;
							}
						}
					}
					$tablesgraph[$ligne]=$tblligne;
					$ligne++;
				}
				

			}
			if ($nbcol > 1)
				$withcol=($width-100)/($nbcol-1);
			else
				$withcol=$width;
			// on affiche le tableau 
			$graph_datas=array();

			$szhead='<table class="border" width="'.$width.'px"><tr class="liste_titre"><th width=100px>'.$langs->trans("label").'</th>';
			$legend=array();
			$totalline=array();
			for($j=0;$j < $ligne;$j++)
			{
				$line[$j]='<tr><td title="'.$descligne[$j].'">'.$titreligne[$j].'</td>';
				array_push($legend,$titreligne[$j]);
				$totalline[$j]=0;
			}
			for($i=0;$i < $nbcol;$i++)
			{
				$szhead.="<th width='".$withcol."px'>".$header[$i]."</th>";
				$graph_datas[$i]=array($header[$i]);
				
				for($j=0;$j < $ligne;$j++)
				{
					if ($headertype[$i]==1) // boolean
						$line[$j].="<td align=center>".yn($tablesgraph[$j][$i])."</td>";
					elseif ($headertype[$i]==10) // date
						$line[$j].="<td align=right>".dol_print_date($this->db->jdate($tablesgraph[$j][$i]),'day')."</td>";
					elseif ($headertype[$i]==5 || $headertype[$i]==8 || $headertype[$i]==3) // numérique
					{
						$line[$j].="<td align=right>".$tablesgraph[$j][$i]."</td>";
						$totalline[$j]=$totalline[$j]+$tablesgraph[$j][$i];
					}
					else   // all the other type
						$line[$j].="<td align=left>".$tablesgraph[$j][$i]."</td>";
					array_push($graph_datas[$i], $tablesgraph[$j][$i]);
				}
			}

			// display board
			if ($mode == 1 || $mode == 3)
			{
				$sz.=$szhead."<th >Total</th></tr>";
				for($j=0;$j < $ligne;$j++)
					$sz.=$line[$j]."<td align=right><b>".$totalline[$j]."</b></td></tr>";
				$sz.="</table><br>";
			}
			// display graph
			if ($mode == 1 || $mode == 2)
			{
				// Fabrication du graphique
				$file= $conf->mydoliboard->dir_temp."/page-".$this->rowid."-".$bloc.".png";
				$fileurl=DOL_URL_ROOT.'/viewimage.php?modulepart=mydoliboard_temp&file='."/page-".$this->rowid."-".$bloc.".png";
				$px1 = new DolGraph();
				$px1->SetData($graph_datas);
				$px1->SetLegend($legend);
				$px1->SetLegendWidthMin(180);
				$px1->SetMaxValue($px1->GetCeilMaxValue()<0?0:$px1->GetCeilMaxValue());
				$px1->SetMinValue($px1->GetFloorMinValue()>0?0:$px1->GetFloorMinValue());
				$px1->SetTitle($title);
				$px1->SetWidth($width);
				$px1->SetHeight($height);
				$px1->SetType(array('line','line','line'));
				$px1->SetShading(3);
				$px1->setBgColor('onglet');
				$px1->setBgColorGrid(array(255,255,255));
				$px1->SetHorizTickIncrement(1);
				$px1->SetPrecisionY(0);
				$px1->draw($file,$fileurl);
			
				$sz.= $px1->show();
			
				unset($graph_datas);
				unset($px1);
				unset($tablesgraph);

			}
		}
		return $sz;
	}

	/**
	 * 	Update mydoliboard
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 *          					-2 : invalid category
	 */
	function update($user='')
	{
		global $conf, $langs;
		$this->db->begin();

		$error=0;
		$positionsave=0;

		// on commence par récupérer l'id du menu à supprimer
		$sql="select m.rowid from ".MAIN_DB_PREFIX."menu as m, ".MAIN_DB_PREFIX."mydoliboard as mdb";
		$sql .= " WHERE mdb.rowid = ".$this->rowid;
		$sql .= " and mdb.titlemenu=m.titre";
		$sql .= " and m.module='mydoliboard'";
		$sql .= " and mdb.mainmenu=m.fk_mainmenu";
		$sql .= " and mdb.leftmenu=m.fk_leftmenu";
		
		dol_syslog(get_class($this)."::update sql=".$sql);
		if ($this->db->query($sql))
		{	
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				$sql="delete from ".MAIN_DB_PREFIX."menu where rowid=".$res['rowid'];
				$this->db->query($sql);
			}
		}
		
		$this->posmenu=$this->getposmenu($this->titlemenu, $this->mainmenu, $this->leftmenu);

		// on supprime l'onglet si il est present ou pas
		$sql="DELETE FROM ".MAIN_DB_PREFIX."const where name =".$this->db->encrypt('MAIN_MODULE_MYDOLIBOARD_TABS_'.$this->rowid,1);
		$this->db->query($sql);

		
		$sql = "UPDATE ".MAIN_DB_PREFIX."mydoliboard";
		$sql .= " SET label = '".$this->db->escape($this->label)."'";
		$sql .= ", description ='".$this->db->escape($this->description)."'";
		$sql .= ", perms ='".$this->db->escape($this->perms)."'";
		$sql .= ", langs ='".$this->db->escape($this->langs)."'";
		$sql .= ", titlemenu ='".$this->db->escape($this->titlemenu)."'";
		$sql .= ", mainmenu ='".$this->db->escape($this->mainmenu)."'";
		$sql .= ", leftmenu ='".$this->db->escape($this->leftmenu)."'";
		$sql .= ", posmenu ='".$positionsave."'";
		$sql .= ", elementtab ='".$this->db->escape($this->elementtab)."'";
		$sql .= ", paramfields ='".$this->db->escape($this->paramfields)."'";
		$sql .= ", author ='".$this->db->escape($this->author)."'";
		$sql .= ", blocAmode =".$this->db->escape($this->blocAmode);
		$sql .= ", blocBmode =".$this->db->escape($this->blocBmode);
		$sql .= ", blocCmode =".$this->db->escape($this->blocCmode);
		$sql .= ", blocDmode =".$this->db->escape($this->blocDmode);
		$sql .= ", blocAtitle ='".$this->db->escape($this->blocAtitle)."'";
		$sql .= ", blocBtitle ='".$this->db->escape($this->blocBtitle)."'";
		$sql .= ", blocCtitle ='".$this->db->escape($this->blocCtitle)."'";
		$sql .= ", blocDtitle ='".$this->db->escape($this->blocDtitle)."'";
		$sql .= ", active =".$this->db->escape($this->active);
		$sql .= " WHERE rowid = ".$this->rowid;
		dol_syslog(get_class($this)."::update sql=".$sql);
		
		if ($this->db->query($sql))
		{
			// si la liste est active
			if ($this->active)
			{
				// on met à jour la table des menus
				// on ajoute le menu
				require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';
				$menu = new Menubase($this->db);
				$menu->menu_handler='all';
				$menu->module='mydoliboard';
				$menu->type='left';
				$menu->fk_menu=-1;
				$menu->fk_mainmenu=$this->mainmenu;
				$menu->fk_leftmenu=$this->leftmenu;
				$menu->titre=$this->titlemenu;
				$menu->url='/mydoliboard/mydoliboard.php?idboard='.$this->rowid;
				$menu->langs=$this->langs;
				$menu->position=$positionsave;
				$menu->perms=$this->perms;
				$menu->target="";
				$menu->user=2;
				$menu->enabled=1;
				$result=$menu->create($user);
				
				// on crée l'onglet 
				if ($this->elementtab)
				{
					switch($this->elementtab) {
						case 'Societe' :
							$tabinfo='thirdparty';
							break;
						case 'Product' :
							$tabinfo='product';
							break;
						case 'CategProduct' :
							$tabinfo='categories_0';
							break;
						case 'CategSociete' :
							$tabinfo='categories_2';
							break;
					}
					$tabinfo.=':+mydoliboard_'.$this->rowid.':'.$this->titlemenu.':@mydoliboard:/mydoliboard/mydoliboard.php?idboard='.$this->rowid.'&id=__ID__';

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."const ";
					$sql.= " ( name, type, value, note, visible, entity)";
					$sql.= " VALUES (";
					$sql.= $this->db->encrypt('MAIN_MODULE_MYDOLIBOARD_TABS_'.$this->rowid,1);
					$sql.= ", 'chaine'";
					$sql.= ", ".$this->db->encrypt($tabinfo,1);
					$sql.= ", null";
					$sql.= ", '0'";
					$sql.= ", ".$conf->entity;
					$sql.= ")";

					dol_syslog(get_class($this)."::update insert_const_tabs sql=".$sql);
					$resql=$this->db->query($sql);
				}
			}
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Delete a list from database
	 *
	 * 	@param	User	$user		Object user that ask to delete
	 *	@return	void
	 */
	function delete($user)
	{
		global $conf, $langs;

		$error=0;
		dol_syslog(get_class($this)."::remove");

		// on vire le menu si il existe
		// on commence par r�cup�rer l'id du menu � supprimer
		$sql="select m.rowid from ".MAIN_DB_PREFIX."menu as m, ".MAIN_DB_PREFIX."mydoliboard as mdb";
		$sql .= " WHERE mbd.rowid = ".$this->rowid;
		$sql .= " and mdb.titlemenu=m.titre";
		$sql .= " and m.module='mydoliboard'";
		$sql .= " and mdb.mainmenu=m.fk_mainmenu";
		$sql .= " and mdb.leftmenu=m.fk_leftmenu";

		dol_syslog(get_class($this)."::delete sql=".$sql);
		if ($this->db->query($sql))
		{	
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				$sql="delete from ".MAIN_DB_PREFIX."menu where rowid=".$res['rowid'];
				$this->db->query($sql);
			}
		}
		// on vire ensuite le la page
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."mydoliboard";
		$sql .= " WHERE rowid =".$this->rowid;
		if (!$this->db->query($sql))
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}
		
		// on vire aussi les tableaux
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."mydoliboardsheet";
		$sql .= " WHERE fk_mdbpage =".$this->rowid;
		if (!$this->db->query($sql))
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}
	}

	/**
	 * 	Retourne toutes les listes
	 *
	 *	@return	array					Tableau d'objet list
	 */
	function get_all_mydoliboard()
	{
		$sql = "SELECT rowid, label, description, perms, langs, titlemenu, mainmenu, leftmenu, elementtab, author, active, paramfields";
		$sql.=" FROM ".MAIN_DB_PREFIX."mydoliboard";

		$res = $this->db->query($sql);
		if ($res)
		{
			$cats = array ();
			while ($rec = $this->db->fetch_array($res))
			{
				$cat = array ();
				$cat['rowid']		= $rec['rowid'];
				$cat['label']		= $rec['label'];
				$cat['description']	= $rec['description'];
				$cat['titlemenu']	= $rec['titlemenu'];
				$cat['mainmenu']	= $rec['mainmenu'];
				$cat['leftmenu']	= $rec['leftmenu'];
				$cat['elementtab']	= $rec['elementtab'];
				$cat['perms']		= $rec['perms'];
				$cat['langs']		= $rec['langs'];
				$cat['author']		= $rec['author'];
				$cat['blocAmode']	= $rec['blocAmode'];
				$cat['blocBmode']	= $rec['blocBmode'];
				$cat['blocCmode']	= $rec['blocCmode'];
				$cat['blocDmode']	= $rec['blocDmode'];
				$cat['blocAtitle']	= $rec['blocAtitle'];
				$cat['blocBtitle']	= $rec['blocBtitle'];
				$cat['blocCtitle']	= $rec['blocCtitle'];
				$cat['blocDtitle']	= $rec['blocDtitle'];
				$cat['active']		= $rec['active'];
				$cat['paramfields']	= $rec['paramfields'];
				$cat['nbBoard']	= $this->nbBoardInPage($rec['rowid']);
				$cats[$rec['rowid']] = $cat;
			}
			return $cats;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	function nbBoardInPage($rowid)
	{
		$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."mydoliboardsheet";
		$sql.= " where fk_mdbpage=".$rowid;

		$res = $this->db->query($sql);
		if ($res)
		{
			$obj = $this->db->fetch_object($res);
			return $obj->nb;
		}
		return 0;
	}

	// $sens = -1 on monte  +1 on descend
	function moveline($sens, $cellorder, $displaycell)
	{
		// récupération de l'id de la ligne � d�placer 
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mydoliboardsheet";
		$sql.= " where fk_mdbpage=".$this->rowid;
		$sql.= " and displaycell='".$displaycell."'";
		$sql.= " and cellorder=".$cellorder;

		$res = $this->db->query($sql);
		if ($res)
		{
			$obj = $this->db->fetch_object($res);
			$rowidori = $obj->rowid;
		}

		// récupération de l'id de la ligne de destination
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."mydoliboardsheet";
		$sql.= " where fk_mdbpage=".$this->rowid;
		$sql.= " and displaycell='".$displaycell."'";
		if ($sens=="up")
			$sql.= " and cellorder=".$cellorder."-1";
		else
			$sql.= " and cellorder=".$cellorder."+1";
		$res = $this->db->query($sql);
		if ($res)
		{
			$obj = $this->db->fetch_object($res);
			$rowiddest = $obj->rowid;
		}

		$sql  = "UPDATE ".MAIN_DB_PREFIX."mydoliboardsheet";
		if ($sens=="up")
			$sql.= " SET cellorder=cellorder-1";
		else
			$sql.= " SET cellorder=cellorder+1";
		$sql .= " WHERE rowid =".$rowidori;

		if (!$this->db->query($sql))
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}

		$sql  = "UPDATE ".MAIN_DB_PREFIX."mydoliboardsheet";
		if ($sens=="up")
			$sql.= " SET cellorder=cellorder+1";
		else
			$sql.= " SET cellorder=cellorder-1";
		$sql .= " WHERE rowid =".$rowiddest;

		if (!$this->db->query($sql))
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
			$error++;
		}
		return 0;
	}

	/**
	 * Return list of tasks for all sheet 
	 * Sort order is on project, then of position of task, and last on title of first level task
	 *
	 * @param	char	$pageid 	id of the main page
	 * @return 	array				Array of Sheet
	 */
	function getSheetsArray( $pageid=0 )
	{
		// List of fields (does not care about permissions. Filtering will be done later)
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."mydoliboardsheet ";
		if ($pageid) $sql.= " where fk_mdbpage = ".$pageid;
		$sql.= " order by cellorder";
		//print $sql;
		dol_syslog(get_class($this)."::getSheetArray sql=".$sql, LOG_DEBUG);

		$res = $this->db->query($sql);
		if ($res)
		{
			$cats = array ();
			while ($rec = $this->db->fetch_array($res))
			{
				$cat = array ();
				$cat['rowid']		= $rec['rowid'];
				$cat['fk_mdbpage']	= $rec['fk_mdbpage'];
				$cat['titlesheet']	= $rec['titlesheet'];
				$cat['description']	= $rec['description'];
				$cat['displaycell']	= $rec['displaycell'];
				$cat['cellorder']	= $rec['cellorder'];
				$cat['perms']		= $rec['perms'];
				$cat['langs']		= $rec['langs'];
				$cat['author']		= $rec['author'];
				$cat['active']		= $rec['active'];
				$cat['querymaj']	= $rec['querymaj'];
				$cat['querydisp']	= $rec['querydisp'];
				$cats[$rec['rowid']] = $cat;
			}
			$this->listsUsed= $cats;
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	function getexporttable($code)
	{
		$this->fetch($code);
		$tmp.="<?xml version='1.0' encoding='ISO-8859-1'?><mydoliboard>\n";
		$tmp.="<label>".$this->label."</label>\n";
		$tmp.="<description>".$this->label."</description>\n";
		$tmp.="<titlemenu>".$this->titlemenu."</titlemenu>\n";
		$tmp.="<mainmenu>".$this->mainmenu."</mainmenu>\n";
		$tmp.="<leftmenu>".$this->leftmenu."</leftmenu>\n";
		$tmp.="<elementtab>".$this->leftmenu."</elementtab>\n";
		$tmp.="<perms>".$this->perms."</perms>\n";
		$tmp.="<langs>".$this->langs."</langs>\n";
		$tmp.="<author>".$this->author."</author>\n";
		$tmp.="<blocAmode> "."\n".$this->blocAmode."\n "."</blocAmode>"."\n";
		$tmp.="<blocAtitle>"."\n".$this->blocAtitle."\n"."</blocAtitle>"."\n";
		$tmp.="<blocBmode> "."\n".$this->blocBmode."\n "."</blocBmode>"."\n";
		$tmp.="<blocBtitle>"."\n".$this->blocBtitle."\n"."</blocBtitle>"."\n";
		$tmp.="<blocCmode> "."\n".$this->blocCmode."\n "."</blocCmode>"."\n";
		$tmp.="<blocCtitle>"."\n".$this->blocCtitle."\n"."</blocCtitle>"."\n";
		$tmp.="<blocDmode> "."\n".$this->blocDmode."\n "."</blocDmode>"."\n";
		$tmp.="<blocDtitle>"."\n".$this->blocDtitle."\n"."</blocDtitle>"."\n";
		$tmp.="<paramfields>"."\n".htmlentities($this->paramfields)."\n"."</paramfields>"."\n";
		$tmp.="<mydoliboardsheets>\n";
		$this->getSheetsArray($code);
		foreach ($this->listsUsed as $key=> $value )
		{
			$tmp.="\t".'<mydoliboardsheet >'."\n";
			$tmp.="\t \t<titlesheet>".$value['titlesheet']."</titlesheet>\n";
			$tmp.="\t \t<descriptionsheet>".$value['description']."</descriptionsheet>\n";
			$tmp.="\t \t<displaycell>".$value['displaycell']."</displaycell>\n";
			$tmp.="\t \t<cellorder>".$value['cellorder']."</cellorder>\n";
			$tmp.="\t \t<authorsheet>".$value['author']."</authorsheet>\n";
			$tmp.="\t \t<permssheet>".$value['perms']."</permssheet>\n";
			$tmp.="\t \t<langssheet>".$value['langs']."</langssheet>\n";
			$tmp.="\t \t<querymaj>".$value['querymaj']."</querymaj>\n";
			$sqlquerydisp=str_replace("SELECT", "#SEL#",$value['querydisp']);
			$tmp.="\t \t<querydisp>".$sqlquerydisp."</querydisp>\n";
			$tmp.="\t</mydoliboardsheet>\n";
		}
		$tmp.="</mydoliboardsheets>\n";
		$tmp.="</mydoliboard>\n";
		return $tmp;
	}

	function importlist($xml)
	{
		// on récupère le fichier et on le parse
		libxml_use_internal_errors(true);
		$sxe = simplexml_load_string($xml);
		if ($sxe === false) {
			echo "Erreur lors du chargement du XML\n";
			foreach(libxml_get_errors() as $error) 
				echo "\t", $error->message;
		}
		else
			$arraydata = json_decode(json_encode($sxe), TRUE);

		$this->label=		$arraydata['label'];
		$this->description= $arraydata['description'];
		$this->titlemenu=	$arraydata['titlemenu'];
		$this->mainmenu=	$arraydata['mainmenu'];
		$this->leftmenu=	$arraydata['leftmenu'];
		$this->elementtab=	$arraydata['elementtab'];
		$this->perms= 		$arraydata['perms'];
		$this->langs= 		$arraydata['langs'];
		$this->author= 		$arraydata['author'];
		$this->blocAmode= 	$arraydata['blocAmode'];
		$this->blocBmode= 	$arraydata['blocBmode'];
		$this->blocCmode= 	$arraydata['blocCmode'];
		$this->blocDmode= 	$arraydata['blocDmode'];
		$this->blocAtitle= 	$arraydata['blocAtitle'];
		$this->blocBtitle= 	$arraydata['blocBtitle'];
		$this->blocCtitle= 	$arraydata['blocCtitle'];
		$this->blocDtitle= 	$arraydata['blocDtitle'];
		$this->paramfields= $arraydata['paramfields'];

		$result  = $this->create($user);
		if ($result)
		{
			$tblsheets=$arraydata['mydoliboardsheets']['mydoliboardsheet'];
			foreach($tblsheets as $sheet)
			{
				$newsheet = new Mydoliboardsheet($this->db);
				$newsheet->fk_mdbpage = $this->rowid;
				$newsheet->titlesheet = $sheet['titlesheet'];
				$newsheet->description = $sheet['descriptionsheet'];
				$newsheet->displaycell = $sheet['displaycell'];
				$newsheet->cellorder = $sheet['cellorder'];
				$newsheet->author = $sheet['authorsheet'];
				$newsheet->perms = $sheet['permssheet'];
				$newsheet->langs = $sheet['langssheet'];
				$newsheet->querymaj = $sheet['querymaj'];
				$newsheet->querydisp = $sheet['querydisp'];
				$newsheet->create($user);
			}
		}
		return $result;
	}
}

/**
 *	Class to manage page of doliboard sheet
 */
class Mydoliboardsheet extends CommonObject
{
	public $element='mydoliboardsheet';
	public $table_element='mydoliboardshett';

	var $fk_mdbpage;
	var $name;
	var $alias;
	var $elementfield; // permet de gérer les liste et les clées
	var $cellorder;
	var $type;
	var	$align;
	var $enabled;
	var	$visible;
	var $filter;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	function get_all_mydoliboardsheet()
	{
		$sql = "SELECT rowid,  description, perms, langs, titlesheet, fk_mdbpage, displaycell, author, active";
		$sql.=" FROM ".MAIN_DB_PREFIX."mydoliboardsheet";

		$res = $this->db->query($sql);
		if ($res)
		{
			$cats = array ();
			while ($rec = $this->db->fetch_array($res))
			{
				$cat = array ();
				$cat['rowid']		= $rec['rowid'];
				$cat['description']	= $rec['description'];
				$cat['titlesheet']	= $rec['titlesheet'];
				$cat['fk_mdbpage']	= $rec['fk_mdbpage'];
				$cat['displaycell']	= $rec['displaycell'];
				$cat['perms']		= $rec['perms'];
				$cat['langs']		= $rec['langs'];
				$cat['author']		= $rec['author'];
				$cat['active']		= $rec['active'];
				$cats[$rec['rowid']] = $cat;
			}
			return $cats;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	function genDefaultTD($keyName, $Arrayfields, $objvalue)
	{
		$tmp= "<td align=".$Arrayfields['align'].">";
		// pour gérer l'aliassing des champs
		if (!empty($Arrayfields['alias']))
			$codFields=$Arrayfields['alias'];
		else
			$codFields=str_replace(array('.', '-'),"_",$keyName);

		// selon le type de données
		switch($Arrayfields['type'])
		{
//			case "Number":
//				$tmp.= price($objvalue->$codFields);
//				break;

			case "Date":
				$tmp.= dol_print_date($this->db->jdate($objvalue->$codFields),'day');
				break;
				
			case "Boolean":
				$tmp.= yn($objvalue->$codFields);
				break;
			default:
				$tmp.= $objvalue->$codFields;
				break;
		}
		$tmp.= '</td>';
		return $tmp;
	}

	/**
	 * 	Load Listables into memory from database
	 *
	 * 	@param		int		$code		code of listable
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		global $conf;

		$sql = "SELECT fk_mdbpage, titlesheet, description, displaycell, cellorder, perms, langs, author, active, querymaj, querydisp";
		$sql.= " FROM ".MAIN_DB_PREFIX."mydoliboardsheet";
		$sql.= " WHERE rowid = '".$rowid."'";

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				$this->rowid		= $rowid;
				$this->fk_mdbpage	= $res['fk_mdbpage'];
				$this->titlesheet	= $res['titlesheet'];
				$this->description	= $res['description'];
				$this->displaycell	= $res['displaycell'];
				$this->cellorder	= $res['cellorder'];
				$this->perms		= $res['perms'];
				$this->langs		= $res['langs'];
				$this->author		= $res['author'];
				$this->active		= $res['active'];
				$this->querymaj		= $res['querymaj'];
				$this->querydisp	= $res['querydisp'];
				$this->db->free($resql);

				return 1;
			}
			else
				return 0;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';
		$lien = '<a href="'.dol_buildpath('/mydoliboard/board.php?rowid='.$this->rowid,1).'">';
		$lienfin='</a>';
		$picto='list';

		$label=$langs->trans("Show").': '.$this->titlesheet;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->titlesheet.$lienfin;
		return $result;
	}
	
	function getNomUrlPage($withpicto=0)
	{
		global $langs;
		$result='';
		$lien = '<a href="'.dol_buildpath('/mydoliboard/fiche.php?rowid='.$this->fk_mdbpage,1).'">';
		$lienfin='</a>';

		$picto='generic';
		$sql="select label FROM ".MAIN_DB_PREFIX."mydoliboard";
		$sql.= " where rowid=".$this->fk_mdbpage;
		$resql = $this->db->query($sql);

		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);
				$label=$res['label'];
			}
		}
		$labelshow=$langs->trans("Show").': '.$label;

		if ($withpicto) $result.=($lien.img_object($labelshow,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$label.$lienfin;
		return $result;
	}
	/**
	 * 	Add sheet into database
	 *
	 * 	@param	User	$user		Object user
	 * 	@return	int 				-1 : erreur SQL

	 */
	function create($user='')
	{
		global $conf, $langs, $user;
		$langs->load('mydoliboard@mydoliboard');

		$error=0;

		$this->titlesheet=(!is_array($this->titlesheet)?trim($this->titlesheet):'');
		$this->description=(!is_array($this->label)?trim($this->label):'');
		$this->perms=(!is_array($this->perms)?trim($this->perms):'');
		$this->langs=(!is_array($this->langs)?trim($this->langs):'');
		$this->displaycell = trim($this->displaycell);
		$this->cellorder =(!is_array($this->cellorder )?trim($this->cellorder ):'');
		$this->author=(!is_array($this->author)?trim($this->author):'');
		$this->querymaj=(!is_array($this->querymaj)?trim($this->querymaj):'');
		$this->querydisp=(!is_array($this->querydisp)?trim($this->querydisp):'');

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mydoliboardsheet (";
		$sql.= " fk_mdbpage,";
		$sql.= " description,";
		$sql.= " titlesheet,";
		$sql.= " displaycell,";
		$sql.= " cellorder,";
		$sql.= " perms,";
		$sql.= " langs,";
		$sql.= " author,";
		$sql.= " active,";
		$sql.= " querymaj,";
		$sql.= " querydisp";
		$sql.= ") VALUES (";
		$sql.= " ".$this->db->escape($this->fk_mdbpage);
		$sql.= ", '".$this->db->escape($this->description)."'";
		$sql.= ", '".$this->db->escape($this->titlesheet)."'";
		$sql.= ", '".$this->db->escape($this->displaycell)."'";
		$sql.= ", ".$this->db->escape($this->cellorder);
		$sql.= ", '".$this->db->escape($this->perms)."'";
		$sql.= ", '".$this->db->escape($this->langs)."'";
		$sql.= ", '".$this->db->escape($this->author)."'";
		$sql.= ", 0";  // by default the new list is not active
		$sql.= ", '".$this->db->escape($this->querymaj)."'";
		$sql.= ", '".$this->db->escape($this->querydisp)."'";
		$sql.= ")";
//print $sql.'<br>';
		dol_syslog(get_class($this).'::create sql='.$sql);
		if ($this->db->query($sql))
		{
			// la récupé de l'id avant le commit sinon ca déconne
			$this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."mydoliboardsheet");
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::create error ".$this->error." sql=".$sql, LOG_ERR);
			$this->db->rollback();
			return 0;
		}
	}
	
		/**
	 * 	Update mydoliboard
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 *          					-2 : invalid category
	 */
	function update($user='')
	{
		global $conf, $langs;

		$this->db->begin();

		$error=0;

		// pour bypass le sql injection
		$this->querydisp=str_replace("#SEL#","SELECT", $this->querydisp);

		$sql = "UPDATE ".MAIN_DB_PREFIX."mydoliboardsheet";
		$sql .= " SET titlesheet = '".$this->db->escape($this->titlesheet)."'";
		$sql .= ", description ='".$this->db->escape($this->description)."'";
		$sql .= ", perms ='".$this->db->escape($this->perms)."'";
		$sql .= ", langs ='".$this->db->escape($this->langs)."'";
		$sql .= ", fk_mdbpage ='".$this->db->escape($this->fk_mdbpage)."'";
		$sql .= ", displaycell ='".$this->db->escape($this->displaycell)."'";
		$sql .= ", querymaj ='".$this->db->escape($this->querymaj)."'";
		$sql .= ", querydisp ='".$this->db->escape($this->querydisp)."'";
		$sql .= ", author ='".$this->db->escape($this->author)."'";
		$sql .= ", active =".$this->db->escape($this->active);
		$sql .= " WHERE rowid = ".$this->rowid;
		dol_syslog(get_class($this)."::update sql=".$sql);

		if ($this->db->query($sql))
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}
}
?>
