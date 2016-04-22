<?php
/* Copyright (C) 2014-2015	Charlie BENLE		<charlie@patas-monkey.com>
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
 * \file htdocs/custom-parc/class/customtabs.class.php
 * \ingroup custom-tabs
 * \brief File of class to manage tabs
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class to manage members type
 */
class Customtabs extends CommonObject
{
	public $table_element = 'customtabs';
	var $rowid;
	var $id; // id de l'extrafields
	var $label;
	var $fk_statut; // complement actif ou pas
	var $tablename; // nom de la table à créer (0=non 1=oui)
	var $element; // nom de L'élément lié à la table
	var $mode; // mode d'affichage de la table ( fiche=1, liste=2)
	var $files; // permet d'ajouter des fichiers (0=non 1=oui)
	var $fk_parent; // clé du menu principal
	var $template; // template de l'onglet
	var $colnameline; // numero de ligne des nom de colonne du fichier d'import
	var $exportenabled; // l'exportation sur cette liste est autorisé
	var $csvseparator; // séparateur du fichier CSV à importer
	var $csvenclosure; // encadrement pour les chaines de caractères
	var $parentname; // nom du menu principal
	var $parenttablename; // nom de la table du parent (pour gérer les onglets
	
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	function __construct($db) {
		$this->db = $db;
		$this->fk_statut = 1;
	}
	
	/**
	 * Fonction qui permet de creer le customtabs
	 *
	 * @param User $user User making creation
	 * @return >0 if OK, < 0 if KO
	 */
	function create($user) {
		global $conf;
		
		$this->statut = trim($this->statut);
		
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "customtabs (";
		$sql .= "libelle, fk_statut, tablename, element, mode, files, fk_parent";
		$sql .= ") VALUES (";
		$sql .= "'" . $this->db->escape($this->label) . "'";
		$sql .= ", 0"; // par défaut l'onglet est inactifs
		$sql .= ", '" . $this->db->escape($this->tablename) . "'";
		$sql .= ", '" . $this->db->escape($this->element) . "'";
		$sql .= ", " . $this->mode;
		$sql .= ", " . ($this->fk_files == - 1 ? " 0" : $this->files);
		$sql .= ", " . ($this->fk_parent == - 1 ? " Null" : $this->fk_parent);
		$sql .= ")";
		
		dol_syslog("customtabs::create sql=" . $sql);
		$result = $this->db->query($sql);
		if ($result) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "customtabs");
			return $this->id;
		} else {
			$this->error = $this->db->error() . ' sql=' . $sql;
			return - 1;
		}
	}
	
	/**
	 * Met a jour en base donnees du template
	 *
	 * @param User $user Object user making change
	 * @return int >0 if OK, < 0 if KO
	 */
	function updateTemplate($user) {
		$sql = "UPDATE " . MAIN_DB_PREFIX . "customtabs ";
		$sql .= " SET template= '" . $this->db->escape($this->template) . "'";
		$sql .= " WHERE rowid =" . $this->rowid;
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error = $this->db->error() . ' sql=' . $sql;
			return - 1;
		}
	}
	
	/**
	 * active la fonction d'export pour cette liste
	 *
	 * @param User $user Object user making change
	 * @return int >0 if OK, < 0 if KO
	 */
	function setExport($enabled, $user) {
		$sql = "UPDATE " . MAIN_DB_PREFIX . "customtabs ";
		$sql .= " SET exportenabled= " . $enabled;
		$sql .= " WHERE rowid =" . $this->rowid;
		
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error = $this->db->error() . ' sql=' . $sql;
			return - 1;
		}
	}
	
	/**
	 * active la fonction d'import pour cette liste
	 *
	 * @param User $user Object user making change
	 * @return int >0 if OK, < 0 if KO
	 */
	function setImport($enabled, $user) {
		$sql = "UPDATE " . MAIN_DB_PREFIX . "customtabs ";
		$sql .= " SET importenabled= " . $enabled;
		$sql .= " WHERE rowid =" . $this->rowid;
		
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error = $this->db->error() . ' sql=' . $sql;
			return - 1;
		}
	}
	
	/**
	 * active défini le paramétrage de l'import pour le customtabs
	 *
	 * @param User $user Object user making change
	 * @return int >0 if OK, < 0 if KO
	 */
	function updateImport($user) {
		$sql = "UPDATE " . MAIN_DB_PREFIX . "customtabs ";
		$sql .= " SET colnameline= " . $this->colnameline;
		$sql .= " , colnamebased = " . $this->colnamebased;
		$sql .= " , csvseparator= '" . $this->csvseparator . "'";
		$sql .= " , csvenclosure= '" . $this->csvenclosure . "'";
		$sql .= " WHERE rowid =" . $this->rowid;
		
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error = $this->db->error() . ' sql=' . $sql;
			return - 1;
		}
	}
	
	/**
	 * Met a jour en base donnees du type
	 *
	 * @param User $user Object user making change
	 * @return int >0 if OK, < 0 if KO
	 */
	function update($user) {
		global $conf;
		$this->label = trim($this->label);
		
		$sql = "UPDATE " . MAIN_DB_PREFIX . "customtabs ";
		$sql .= " SET fk_statut = " . $this->fk_statut;
		$sql .= ", libelle = '" . $this->db->escape($this->label) . "'";
		$sql .= ", mode = " . $this->mode;
		$sql .= ", element = '" . $this->element . "'";
		$sql .= ", files = " . $this->files;
		$sql .= ", fk_parent = " . ($this->fk_parent > 0 ? $this->fk_parent : 'null');
		$sql .= " WHERE rowid =" . $this->rowid;
		$result = $this->db->query($sql);
		
		if ($result) {
			// on supprime l'onglet si il est present ou pas
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "const where name =" . $this->db->encrypt('MAIN_MODULE_CUSTOMTABS_TABS_' . $this->rowid, 1);
			$this->db->query($sql);
			
			// print "==".$this->fk_parent;
			// si il y a un onglet à positionner (actif et que ce n'et pas un sous-menu)
			if ($this->fk_statut == 1 && $this->fk_parent < 0) {
				if ($this->element == 'commande')
					$contacttab = 'order';
				else
					$contacttab = $this->element;
					// on paramètre selon le type d'onglet les choix possibles
				switch ($this->mode) {
					case 1 :
						$tabinfo = $contacttab . ':+customtabs_' . $this->rowid . ':' . $this->label . ':@customtabs:/customtabs/tabs/card.php?tabsid=' . $this->rowid . '&id=__ID__';
						break;
					case 2 :
						$tabinfo = $contacttab . ':+customtabs_' . $this->rowid . ':' . $this->label . ':@customtabs:/customtabs/tabs/list.php?tabsid=' . $this->rowid . '&id=__ID__';
						break;
				}
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "const ";
				$sql .= " ( name, type, value, note, visible, entity)";
				$sql .= " VALUES (";
				$sql .= $this->db->encrypt('MAIN_MODULE_CUSTOMTABS_TABS_' . $this->rowid, 1);
				$sql .= ", 'chaine'";
				$sql .= ", " . $this->db->encrypt($tabinfo, 1);
				$sql .= ", null";
				$sql .= ", '0'";
				$sql .= ", " . $conf->entity;
				$sql .= ")";
				
				dol_syslog(get_class($this) . "::update insert_const_tabs sql=" . $sql);
				$resql = $this->db->query($sql);
			}
			return 1;
		} else {
			$this->error = $this->db->error() . ' sql=' . $sql;
			return - 1;
		}
	}
	
	/**
	 * Fonction qui permet de supprimer
	 *
	 * @param int $rowid Id of member type to delete
	 * @return int >0 if OK, < 0 if KO
	 */
	function delete($rowid) {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "customtabs WHERE rowid = " . $rowid;
		$ressql = $this->db->query($sql);
		
		// on supprime aussi l'onglet
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "const where name =" . $this->db->encrypt('MAIN_MODULE_CUSTOMTABS_TABS_' . $rowid, 1);
		
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->affected_rows($resql))
				return 1;
			else
				return 0;
		} else {
			print "Err : " . $this->db->error();
			return 0;
		}
	}
	
	/**
	 * Fonction qui permet d'importer une ligne dans un customTabs
	 *
	 * @param int $rowid Id of member type to load
	 * @param string $tablename table
	 * @return int <0 if KO, >0 if OK
	 */
	function importLine($fieldssource, $arrayrecord, $id) {
		$error = 0;
		
		$this->db->begin();
		
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . "_extrafields (fk_object";
		foreach ( $fieldssource as $key => $value ) {
			// seulement si le champs est renseigné
			if ($value['colname'] != '')
				$sql .= "," . $value['colname'];
		}
		$sql .= ") VALUES (" . $id;
		foreach ( $fieldssource as $key => $value ) {
			if ($value['colname'] != '') {
				if ($arrayrecord[$key - 1]['val']) {
					switch ($value['type']) {
						case 'int' :
							$sql .= " ," . $arrayrecord[$key - 1]['val'];
							break;
						case 'price' :
							$sql .= " ," . price2num($arrayrecord[$key - 1]['val']);
							break;
						case 'date' :
							$tmpdate = dol_mktime(- 1, - 1, - 1, substr($arrayrecord[$key - 1]['val'], 3, 2), substr($arrayrecord[$key - 1]['val'], 0, 2), substr($arrayrecord[$key - 1]['val'], 6, 4));
							// print substr($arrayrecord[$key-1]['val'],3,2)."-".substr($arrayrecord[$key-1]['val'],0,2)."-".substr($arrayrecord[$key-1]['val'],6,4)."==".$tmpdate."<br>";
							$sql .= ", '" . $this->db->idate($tmpdate) . "'";
							break;
						case 'link' :
							$param_list = array_keys($attributeParam['options']);
							// 0 : ObjectName
							// 1 : classPath
							$InfoFieldList = explode(":", $param_list[0]);
							dol_include_once($InfoFieldList[1]);
							$object = new $InfoFieldList[0]($this->db);
							if ($value) {
								$object->fetch(0, $value);
								$sql .= ", " . $object->id;
							}
							break;
						default :
							$sql .= ", '" . $this->db->escape($arrayrecord[$key - 1]['val']) . "'";
					}
				} else {
					$sql .= ", null";
				}
			}
		}
		$sql .= ")";
		
		dol_syslog(get_class($this) . "::importLine insert sql=" . $sql);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this) . "::insert " . $this->error, LOG_ERR);
			$this->db->rollback();
			return - 1;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 * Fonction qui permet de les infos de table
	 *
	 * @param int $rowid Id of member type to load
	 * @param string $tablename table
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($rowid, $tablename = '') {
		$sql = "SELECT c.rowid, c.libelle, c.mode, c.files, c.tablename, c.template, c.fk_statut, c.fk_parent, c.element";
		$sql .= ", c.colnameline, c.csvseparator, c.exportenabled, c.importenabled, c.csvenclosure";
		$sql .= " FROM " . MAIN_DB_PREFIX . "customtabs as c";
		if ($rowid > 0)
			$sql .= " WHERE c.rowid = " . $rowid;
		else
			$sql .= " WHERE c.tablename = '" . $tablename . "'";
		dol_syslog("customtabs::fetch sql=" . $sql);
		
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				
				$this->rowid = $obj->rowid;
				$this->libelle = $obj->libelle;
				$this->element = $obj->element;
				$this->fk_statut = $obj->fk_statut;
				$this->tablename = $obj->tablename;
				$this->mode = $obj->mode;
				$this->files = $obj->files;
				$this->template = $obj->template;
				$this->fk_parent = $obj->fk_parent;
				$this->colnameline = $obj->colnameline;
				$this->csvseparator = $obj->csvseparator;
				$this->csvenclosure = $obj->csvenclosure;
				$this->exportenabled = $obj->exportenabled;
				$this->importenabled = $obj->importenabled;
				
				$this->getcustomtabsname();
				$this->table_element = "cust_" . $obj->tablename;
			}
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("customtabs::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Fonction qui permet de recuperer le nom du complement parent
	 *
	 * @param int $rowid Id of member type to load
	 * @return int <0 if KO, >0 if OK
	 */
	function getcustomtabsname() {
		$this->parentname = "";
		$sql = "SELECT c.libelle, c.tablename";
		$sql .= " FROM " . MAIN_DB_PREFIX . "customtabs as c";
		$sql .= " WHERE c.rowid = " . $this->fk_parent;
		
		dol_syslog("customtabs::getcustomtabsname sql=" . $sql, LOG_DEBUG);
		
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->parentname = $obj->libelle;
				$this->parenttablename = $obj->tablename;
			}
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("customtabs::getcustomtabsname " . $this->error, LOG_ERR);
		}
	}
	
	/**
	 * Renvoie nom clickable (avec eventuellement le picto)
	 *
	 * @param int $withpicto 0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 * @param int $maxlen length max libelle
	 * @return string String with URL
	 */
	
	// QUESTION : cette fonction est-elle utilisé? le dossier complément n'existe pas?
	function getNomUrl($withpicto = 0, $maxlen = 0) {
		global $langs;
		
		$result = '';
		
		$lien = '<a href="' . DOL_URL_ROOT . '/customtabs/complement/fiche.php?rowid=' . $this->id . '">';
		$lienfin = '</a>';
		
		$picto = 'group';
		$label = $langs->trans("ShowTypeCard", $this->libelle);
		
		if ($withpicto)
			$result .= ($lien . img_object($label, $picto) . $lienfin);
		if ($withpicto && $withpicto != 2)
			$result .= ' ';
		$result .= $lien . ($maxlen ? dol_trunc($this->libelle, $maxlen) : $this->libelle) . $lienfin;
		return $result;
	}
	
	/**
	 * Renvoie la liste des tables
	 *
	 * @param string $elementname name of element to filter
	 * @param integer $filtermode mode of element to select (0= all, 1=card, 2=list)
	 * @param integer $statut statut of element to select (-1= all, 0=inactive, 1=enabled)
	 * @return array String with URL
	 */
	function liste_array($elementname = '', $filtermode = 0, $fk_statut = -1) {
		global $conf, $langs;
		
		$projets = array ();
		
		$sql = "SELECT rowid, libelle, mode, fk_parent, element, tablename, fk_statut ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "customtabs";
		$sql .= " WHERE 1=1";
		if ($elementname)
			$sql .= " AND element ='" . $elementname . "'";
		if ($filtermode > 0)
			$sql .= " AND mode =" . $filtermode;
		if ($fk_statut >= 0)
			$sql .= " AND fk_statut =" . $fk_statut;
		
		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$i = 0;
				while ( $i < $nump ) {
					$obj = $this->db->fetch_object($resql);
					$customtabsarray = array ();
					$customtabsarray['rowid'] = $obj->rowid;
					$customtabsarray['libelle'] = $langs->trans($obj->libelle);
					$customtabsarray['mode'] = $obj->mode;
					$customtabsarray['tablename'] = $obj->tablename;
					$customtabsarray['element'] = $obj->element;
					$customtabsarray['fk_parent'] = $obj->fk_parent;
					$customtabsarray['fk_statut'] = $obj->fk_statut;
					$liste_array[$obj->rowid] = $customtabsarray;
					$i ++;
				}
			}
			// var_dump($liste_array);
			return $liste_array;
		} else {
			print $this->db->error();
		}
	}
	function setShowTabs($fk_usergroup, $status) {
		if ($status == 1) // activate
{
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "customtabsgroup (fk_customtabs, fk_usergroup)";
			$sql .= " VALUES (" . $this->rowid . ", " . $fk_usergroup . ")";
		} else // desactivate
{
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "customtabsgroup";
			$sql .= " WHERE fk_customtabs =" . $this->rowid;
			$sql .= " AND fk_usergroup =" . $fk_usergroup;
		}
		$resql = $this->db->query($sql);
		if ($resql) {
			return 1;
		} else {
			print "Err : " . $this->db->error();
			return 0;
		}
	}
	function getShowCustomtabs($fk_user) {
		// on vérifie qu'il y a des groupes d'utilisateur,
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "usergroup_user as ugu";
		$resql = $this->db->query($sql);
		if ($resql) { // si pas de groupe, c'est ouvert à tous
			if ($this->db->num_rows($resql) == 0)
				return 1;
		}
		
		// on regarde ensuite si on est habilité
		$sql = "SELECT 1 FROM " . MAIN_DB_PREFIX . "customtabs_usergroup_rights as cur, " . MAIN_DB_PREFIX . "usergroup_user as ugu";
		$sql .= " WHERE fk_customtabs =" . $this->rowid;
		$sql .= " AND ugu.fk_user =" . $fk_user;
		$sql .= " AND ugu.fk_usergroup = cur.fk_usergroup";
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql))
				return 1;
			else
				return 0;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("customtabsgroup::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Determine user rights on object ressourtype type
	 *
	 * @param User $user User to determine the specials rights
	 * @param int $ressource_type ressource type to test
	 * @param int $table_name If it's a complement we chack regarding table name
	 * @return array Return an array ('edit'=>val,'create'=>val,'delete'=>val); where val is 1 if right is ok or 0 if not
	 */
	function getUserSpecialsRights($user) {
		global $langs;
		
		$array_return = array ();
		
		if (! empty($user->id)) {
			// If user is admin he get all rights by default
			if ($user->admin) {
				$array_return = array (
						'edit' => 1,
						'create' => 1,
						'delete' => 1 
				);
			} else {
				require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
				$usr_group = new UserGroup($this->db);
				$group_array = $usr_group->listGroupsForUser($user->id);
				if (is_array($group_array) && count($group_array) > 0) {
					
					$sql = 'SELECT rights FROM ' . MAIN_DB_PREFIX . 'customtabs_usergroup_rights WHERE fk_customtabs=' . $this->rowid;
					$sql .= ' AND fk_usergroup IN (' . implode(", ", array_keys($group_array)) . ')';
					
					dol_syslog(get_class($this) . '::getUserSpecialsRights sql=' . $sql);
					$resql = $this->db->query($sql);
					if ($resql) {
						$nump = $this->db->num_rows($resql);
						if ($nump) {
							$array_return = array (
									'edit' => 0,
									'create' => 0,
									'delete' => 0 
							);
							while ( $obj = $this->db->fetch_object($resql) ) {
								// User in in group that allow creation of this type of ressource
								if (strpos($obj->rights, 'A') !== false) {
									$array_return['create'] = 1;
								}
								// User in in group that allow update of this type of ressource
								if (strpos($obj->rights, 'U') !== false) {
									$array_return['edit'] = 1;
								}
								// User in in group that allow delete of this type of ressource
								if (strpos($obj->rights, 'D') !== false) {
									$array_return['delete'] = 1;
								}
							}
						}
						$this->db->free($resql);
					} else {
						print $this->db->error();
					}
				}
			}
		}
		return $array_return;
	}
	
	/**
	 * Return list of types of notes
	 *
	 * @param string $selected Preselected type
	 * @param string $htmlname Name of field in form
	 * @return void
	 */
	function selectparent($selected = '', $htmlname = 'fk_parent', $rowid) {
		global $user, $langs;
		
		dol_syslog(get_class($this) . "::select_customtabs" . $selected . ", " . $htmlname, LOG_DEBUG);
		
		$sql = "SELECT c.rowid, c.libelle";
		$sql .= " FROM " . MAIN_DB_PREFIX . "customtabs as c";
		$sql .= " WHERE c.fk_parent is null";
		$sql .= " AND c.mode = 1"; // uniquement les onglets de type fiche (pas les listes)
		if ($rowid > 0) // pour ne pas se sélectionner soit-même
			$sql .= " AND c.rowid <> " . $rowid;
		dol_syslog(get_class($this) . "::selectparent sql=" . $sql);
		
		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				print '<select class="flat" name="' . $htmlname . '">';
				print '<option value="-1"';
				if ($selected == - 1)
					print ' selected="selected"';
				print '>&nbsp;</option>';
				
				$i = 0;
				while ( $i < $nump ) {
					$obj = $this->db->fetch_object($resql);
					print '<option value="' . $obj->rowid . '"';
					if ($obj->rowid == $selected)
						print ' selected="selected"';
					print '>' . $obj->libelle . '</option>';
					$i ++;
				}
				print '</select>';
			} else {
				// si pas de complément, par défaut la zone est alimenté à -1
				print $langs->trans("NoParentDefined") . '<input type=hidden name="' . $htmlname . '" value=-1>';
			}
		}
	}
	
	/**
	 * Function to get extra fields of a member into $this->array_options List
	 *
	 * @param int $rowid Id of line
	 * @param array $optionsArray Array resulting of call of extrafields->fetch_name_optionals_label()
	 * @return void
	 */
	function fetch_optionalslist($rowid, $optionsArray = '') {
		if (! is_array($optionsArray)) {
			// optionsArray not already loaded, so we load it
			require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
			$optionsArray = $extrafields->fetch_name_optionals_label();
		}
		
		// Request to get complementary values
		if (count($optionsArray) > 0) {
			$sql = "SELECT rowid";
			foreach ( $optionsArray as $name => $label ) {
				$sql .= ", " . $name;
			}
			$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . "_extrafields";
			$sql .= " WHERE fk_object = " . $rowid;
			$sql .= " ORDER BY 2"; // on trie sur le premier champs de la liste
			dol_syslog(get_class($this) . "::fetch_optionals sql=" . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $tab = $this->db->fetch_object($resql) ) {
						$rowidextrafields = $tab->rowid;
						foreach ( $tab as $key => $value ) {
							if ($key != 'rowid' && $key != 'tms' && $key != 'fk_member') {
								// we can add this attribute to adherent object
								$this->array_options[$rowidextrafields]["options_$key"] = $value;
							}
						}
					}
				}
				$this->db->free($resql);
			} else {
				dol_print_error($this->db);
			}
		}
	}
	
	/**
	 * Add/Update all extra fields values for the current object.
	 * All data to describe values to insert are stored into $this->array_options=array('keyextrafield'=>'valueextrafieldtoadd')
	 *
	 * @return void
	 */
	function insertExtraFields_line($id, $line = '') {
		global $langs;
		
		$error = 0;
		
		if (! empty($this->array_options)) {
			// Check parameters
			$langs->load('admin');
			require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
			
			$optionsArray = $extrafields->fetch_name_optionals_label($this->table_element);
			foreach ( $this->array_options as $key => $value ) {
				$attributeKey = substr($key, 8); // Remove 'options_' prefix
				$attributeKey = substr($attributeKey, 0, - 1); // pour enlever la derniere valeur (1)
				$attributeType = $extrafields->attribute_type[$attributeKey];
				$attributeSize = $extrafields->attribute_size[$attributeKey];
				$attributeLabel = $extrafields->attribute_label[$attributeKey];
				$attributeParam = $extrafields->attribute_param[$attributeKey];
				// print $attributeKey.' : '.$attributeType." - ".$attributeSize." - ".$attributeLabel." - ".$attributeParam." - <br>";
				switch ($attributeType) {
					case 'int' :
						if (! is_numeric($value) && $value != '') {
							$error ++;
							$this->errors[] = $langs->trans("ExtraFieldHasWrongValue", $attributeLabel);
							return - 1;
						} elseif ($value == '') {
							$this->array_options[$key] = null;
						}
						break;
					case 'price' :
						$this->array_options[$key] = price2num($this->array_options[$key]);
						break;
					case 'date' :
						if (is_numeric($this->array_options[$key]))
							$this->array_options[$key] = $this->db->idate($this->array_options[$key]);
						else {
							$tmpdate = dol_mktime(- 1, - 1, - 1, substr($this->array_options[$key], 3, 2), substr($this->array_options[$key], 0, 2), substr($this->array_options[$key], 6, 4));
							$this->array_options[$key] = $this->db->idate($tmpdate);
						}
						break;
					case 'datetime' :
						if (is_numeric($this->array_options[$key]))
							$this->array_options[$key] = $this->db->idate($this->array_options[$key]);
						else {
							print "===" . $this->array_options[$key];
							$tmpdate = dol_mktime(substr($this->array_options[$key], 10, 2), - 1, - 1, substr($this->array_options[$key], 3, 2), substr($this->array_options[$key], 0, 2), substr($this->array_options[$key], 6, 4));
							$this->array_options[$key] = $this->db->idate($tmpdate);
						}
						
						break;
					case 'link' :
						$param_list = array_keys($attributeParam['options']);
						// 0 : ObjectName
						// 1 : classPath
						$InfoFieldList = explode(":", $param_list[0]);
						dol_include_once($InfoFieldList[1]);
						$object = new $InfoFieldList[0]($this->db);
						if ($value) {
							$object->fetch(0, $value);
							$this->array_options[$key] = $object->id;
						}
						break;
					case 'varchar' :
					case 'text' :
						$this->array_options[$key] = $this->db->escape($this->array_options[$key]);
						break;
				}
			}
			$this->db->begin();
			
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . "_extrafields (fk_object";
			foreach ( $optionsArray as $key => $value ) {
				// $attributeKey = substr($key,8); // Remove 'options_' prefix
				// Add field of attribut
				if ($extrafields->attribute_type[$key] != 'separate') // Only for other type of separate
					if (! is_array($this->array_options[$key]))
						$sql .= "," . $key;
			}
			$sql .= ") VALUES (" . $id;
			foreach ( $optionsArray as $key => $value ) {
				
				// Add field of attribut
				if ($extrafields->attribute_type[$key] != 'separate') // Only for other type of separate)
{
					if (! is_array($this->array_options['options_' . $key . $line])) {
						if ($this->array_options['options_' . $key . $line] != '') {
							$sql .= ",'" . $this->array_options['options_' . $key . $line] . "'";
						} else {
							$sql .= ",null";
						}
					}
				}
			}
			$sql .= ")";
			// print $sql.'<br>';
			// exit;
			dol_syslog(get_class($this) . "::insertExtraFields insert sql=" . $sql);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->error = $this->db->lasterror();
				dol_syslog(get_class($this) . "::update " . $this->error, LOG_ERR);
				$this->db->rollback();
				return - 1;
			} else {
				$this->db->commit();
				return 1;
			}
		} else
			return 0;
	}
	
	/**
	 * Add/Update all extra fields values for the current object.
	 * All data to describe values to insert are stored into $this->array_options=array('keyextrafield'=>'valueextrafieldtoadd')
	 *
	 * @return void
	 */
	function editExtraFields_line($id, $linerowid) {
		global $langs;
		
		$error = 0;
		
		if (! empty($this->array_options)) {
			// Check parameters
			$langs->load('admin');
			require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
			$optionsArray = $extrafields->fetch_name_optionals_label($this->table_element);
			
			foreach ( $this->array_options as $key => $value ) {
				$attributeKey = substr($key, 8); // Remove 'options_' prefix
				$attributeType = $extrafields->attribute_type[$attributeKey];
				$attributeSize = $extrafields->attribute_size[$attributeKey];
				$attributeLabel = $extrafields->attribute_label[$attributeKey];
				$attributeParam = $extrafields->attribute_param[$attributeKey];
				
				switch ($attributeType) {
					case 'int' :
						if (! is_numeric($value) && $value != '') {
							$error ++;
							$this->errors[] = $langs->trans("ExtraFieldHasWrongValue", $attributeLabel);
							return - 1;
						} elseif ($value == '') {
							$this->array_options[$key] = null;
						}
						break;
					
					case 'price' :
						$this->array_options[$key] = price2num($this->array_options[$key]);
						break;
					
					case 'date' :
						if (is_numeric($this->array_options[$key]))
							$this->array_options[$key] = $this->db->idate($this->array_options[$key]);
						else {
							$tmpdate = dol_mktime(- 1, - 1, - 1, substr($this->array_options[$key], 3, 2), substr($this->array_options[$key], 0, 2), substr($this->array_options[$key], 6, 4));
							$this->array_options[$key] = $this->db->idate($tmpdate);
						}
						break;
					case 'datetime' :
						if (is_numeric($this->array_options[$key]))
							$this->array_options[$key] = $this->db->idate($this->array_options[$key]);
						break;
					case 'link' :
						$param_list = array_keys($attributeParam['options']);
						// 0 : ObjectName
						// 1 : classPath
						$InfoFieldList = explode(":", $param_list[0]);
						dol_include_once($InfoFieldList[1]);
						$object = new $InfoFieldList[0]($this->db);
						if ($value) {
							$object->fetch(0, $value);
							$this->array_options[$key] = $object->id;
						}
						break;
					case 'varchar' :
					case 'text' :
						$this->array_options[$key] = $this->db->escape($this->array_options[$key]);
						break;
				}
			}
			$this->db->begin();
			
			// on fait un update de la ligne au lieu d'un annule et remplace
			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . "_extrafields";
			$btopset = true;
			foreach ( $this->array_options as $key => $value ) {
				$attributeKey = substr($key, 8); // Remove 'options_' prefix
				
				if ($extrafields->attribute_type[$attributeKey] != 'separate') // Only for other type of separate)
{
					if (! is_array($this->array_options[$key])) {
						if ($btopset) {
							$sql .= " SET ";
							$btopset = false;
						} else
							$sql .= " , ";
						
						if ($this->array_options[$key] != '') {
							$sql .= $attributeKey . " = '" . $this->array_options[$key] . "'";
						} else {
							
							$sql .= $attributeKey . " = null";
						}
					}
				}
			}
			$sql .= " WHERE rowid = " . $linerowid;
			
			$resql = $this->db->query($sql);
			if (! $resql) {
				$this->error = $this->db->lasterror();
				print $sql . " : " . $this->error;
				dol_syslog(get_class($this) . "::update " . $this->error, LOG_ERR);
				$this->db->rollback();
				return - 1;
			} else {
				$this->db->commit();
				return 1;
			}
		} else
			return 0;
	}
	
	/**
	 * Add/Update all extra fields values for the current object.
	 * All data to describe values to insert are stored into $this->array_options=array('keyextrafield'=>'valueextrafieldtoadd')
	 *
	 * @return void
	 */
	function deleteExtraFields_line($linerowid) {
		$error = 0;
		
		$this->db->begin();
		
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element . "_extrafields WHERE rowid = " . $linerowid;
		
		dol_syslog(get_class($this) . "::deleteExtraFields_line delete sql=" . $sql);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this) . "::update " . $this->error, LOG_ERR);
			$this->db->rollback();
			return - 1;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 * Export customTabs definition in XML format
	 *
	 * @return void
	 */
	function getexporttable($id) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		
		$this->fetch($id);
		$tmp .= "<?xml version='1.0' encoding='ISO-8859-1'?><customtabs>\n";
		$tmp .= "<label>" . $this->label . "</label>\n";
		$tmp .= "<element>" . $this->element . "</element>\n";
		$tmp .= "<tablename>" . $this->tablename . "</tablename>\n";
		$tmp .= "<mode>" . $this->mode . "</mode>\n";
		$tmp .= "<files>" . $this->files . "</files>\n";
		$tmp .= "<template>" . htmlentities($this->template) . "</template>\n";
		
		// récupération des champs associés au customtabs
		$elementtype = "cust_" . $this->tablename;
		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label($elementtype);
		$tmp .= "<customextrafields>\n";
		foreach ( $extrafields->attribute_type as $key => $value ) {
			$tmp .= "\t" . '<customextrafield>' . "\n";
			$tmp .= "\t \t<key>" . $key . "</key>\n";
			$tmp .= "\t \t<label>" . $extrafields->attribute_label[$key] . "</label>\n";
			$tmp .= "\t \t<type>" . $extrafields->attribute_type[$key] . "</type>\n";
			$tmp .= "\t \t<size>" . $extrafields->attribute_size[$key] . "</size>\n";
			$tmp .= "\t \t<unique>" . $extrafields->attribute_unique[$key] . "</unique>\n";
			$tmp .= "\t \t<required>" . $extrafields->attribute_required[$key] . "</required>\n";
			$tmp .= "\t" . '</customextrafield>' . "\n";
		}
		$tmp .= "</customextrafields>\n";
		$tmp .= "</customtabs>\n";
		return $tmp;
	}
	
	/**
	 * Export importTabs definition in XML format
	 *
	 * @return void
	 */
	function importlist($xml) {
		global $langs;
		$mesg = Array ();
		
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
		
		// on récupère le fichier et on le parse
		libxml_use_internal_errors(true);
		$sxe = simplexml_load_string($xml);
		if ($sxe === false) {
			echo "Erreur lors du chargement du XML\n";
			foreach ( libxml_get_errors() as $error )
				echo "\t", $error->message;
		} else
			$arraydata = json_decode(json_encode($sxe), TRUE);
		
		$this->label = $arraydata['label'];
		$this->element = $arraydata['element'];
		$this->tablename = $arraydata['tablename'];
		$this->mode = $arraydata['mode'];
		$this->files = $arraydata['files'];
		$this->template = $arraydata['template'];
		$this->fk_parent = - 1; // par défaut en import xml, pas de récusivité
		
		$elementtype = "cust_" . $this->tablename;
		$extrafields = new ExtraFields($this->db);
		
		$result = $this->create($user, true);
		if ($result > 0) {
			if ($this->tablename) { // la saisie du nom de la table est obligatoire sinon on ne crée pas la table
			  
				// définition de la table à créer
				$table = MAIN_DB_PREFIX . "cust_" . $this->tablename . '_extrafields';
				$fields = array (
						'rowid' => array (
								'type' => 'int',
								'value' => '11',
								'null' => 'not null',
								'extra' => 'AUTO_INCREMENT' 
						),
						'tms' => array (
								'type' => 'timestamp',
								'attribute' => 'on update CURRENT_TIMESTAMP',
								'default' => 'CURRENT_TIMESTAMP',
								'null' => 'not null',
								'extra' => 'ON UPDATE CURRENT_TIMESTAMP' 
						),
						'fk_element' => array (
								'type' => 'int',
								'value' => '11',
								'null' => 'not null' 
						), // clé de l'élément
						'fk_customtabs_parent' => array (
								'type' => 'int',
								'value' => '11',
								'null' => 'not null' 
						),
						'fk_object' => array (
								'type' => 'int',
								'value' => '11',
								'null' => 'not null' 
						),
						'import_key' => array (
								'type' => 'varchar',
								'value' => '14',
								'default' => 'NULL',
								'null' => 'null' 
						) 
				);
				$result = $this->db->DDLCreateTable($table, $fields, 'rowid', 'InnoDB');
			}
			
			$tblsheets = $arraydata['customextrafields']['customextrafield'];
			
			foreach ( $tblsheets as $sheet ) {
				// Check values
				if (! ($sheet['type'])) {
					$error ++;
					$langs->load("errors");
					$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->trans("Type"));
					$action = 'import';
				}
				
				if ($sheet['type'] == 'select' && ! $sheet['param']) {
					$error ++;
					$langs->load("errors");
					$this->errors[] = $langs->trans("ErrorNoValueForSelectType");
					$action = 'import';
				}
				if ($sheet['type'] == 'sellist' && ! $sheet['param']) {
					$error ++;
					$langs->load("errors");
					$this->errors[] = $langs->trans("ErrorNoValueForSelectListType");
					$action = 'import';
				}
				if ($sheet['type'] == 'checkbox' && ! $sheet['param']) {
					$error ++;
					$langs->load("errors");
					$this->errors[] = $langs->trans("ErrorNoValueForCheckBoxType");
					$action = 'import';
				}
				if ($sheet['type'] == 'radio' && ! $sheet['param']) {
					$error ++;
					$langs->load("errors");
					$this->errors[] = $langs->trans("ErrorNoValueForRadioType");
					$action = 'import';
				}
				if ((($sheet['type'] == 'radio') || ($sheet['type'] == 'checkbox') || ($sheet['type'] == 'radio')) && $sheet['param']) {
					// Construct array for parameter (value of select list)
					$parameters = $sheet['param'];
					$parameters_array = explode("\r\n", $sheet['param']);
					foreach ( $parameters_array as $param_ligne ) {
						if (! empty($param_ligne)) {
							if (preg_match_all('/,/', $param_ligne, $matches)) {
								if (count($matches[0]) > 1) {
									$error ++;
									$langs->load("errors");
									$this->errors[] = $langs->trans("ErrorBadFormatValueList", $param_ligne);
									$action = 'import';
								}
							} else {
								$error ++;
								$langs->load("errors");
								$this->errors[] = $langs->trans("ErrorBadFormatValueList", $param_ligne);
								$action = 'import';
							}
						}
					}
				}
				if (! $error) {
					var_dump($sheet);
					$pos = $sheet['pos'];
					// Construct array for parameter (value of select list)
					$parameters = $sheet['param'];
					$parameters_array = explode("\r\n", $parameters);
					foreach ( $parameters_array as $param_ligne ) {
						list ( $key, $value ) = explode(',', $param_ligne);
						$params['options'][$key] = $value;
					}
					if (isset($sheet["key"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/", $sheet["key"])) {
						
						$result = $extrafields->addExtraField($sheet["key"], $sheet["label"], $sheet["type"], $pos, $sheet["size"], $elementtype, ($sheet["unique"] ? 1 : 0), ($sheet["required"] ? 1 : 0), $params);
						if ($result <= 0) {
							$error ++;
							$this->errors[] = $extrafields->error;
							var_dump($extrafields);
						}
					} else {
						$error ++;
						$langs->load("errors");
						$this->errors[] = $langs->trans("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("AttributeCode"));
					}
				} else {
					var_dump($this->errors);
					exit();
				}
			}
		}
		
		return $result;
	}
	function element_setting() {
		global $head;
		global $title;
		global $langs;
		
		// selon l'onglet on affiche les données de l'onglet
		switch ($this->element) {
			
			case 'dictionary' :
				require_once DOL_DOCUMENT_ROOT . '/customtabs/class/dictionary.class.php';
				require_once DOL_DOCUMENT_ROOT . '/customtabs/core/lib/customtabs.lib.php';
				$object = new Dictionary($this->db);
				return $object;
				break;
			
			case 'thirdparty' :
				require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
				$object = new Societe($this->db);
				return $object;
				break;
			
			case 'stock' :
				require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/stock.lib.php';
				$object = new Entrepot($this->db);
				return $object;
				break;
			
			case 'member' :
				require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/member.lib.php';
				$object = new Adherent($this->db);
				return $object;
				break;
			
			case 'contract' :
				require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
				$object = new Contrat($this->db);
				return $object;
				break;
			
			case 'intervention' :
				require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/fichinter.lib.php';
				$object = new Fichinter($this->db);
				return $object;
				break;
			
			case 'delivery' :
				require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/sendings.lib.php';
				$object = new Expedition($this->db);
				return $object;
				break;
			
			case 'user' :
				require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/usergroups.lib.php';
				$object = new User($this->db);
				return $object;
				break;
			
			case 'commande' :
				require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
				$object = new Commande($this->db);
				return $object;
				break;
			
			case 'invoice' :
				require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
				$object = new Facture($this->db);
				return $object;
				break;
			
			case 'propal' :
				require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
				$object = new Propal($this->db);
				return $object;
				break;
			
			case 'supplier_invoice' :
				require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
				require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
				require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
				$object = new FactureFournisseur($this->db);
				return $object;
				break;
			
			case 'supplier_order' :
				require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
				require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
				require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
				$object = new CommandeFournisseur($this->db);
				return $object;
				break;
			
			case 'project' :
				require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
				require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
				$object = new Project($this->db);
				return $object;
				break;
			
			case 'bank' :
				require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
				require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
				$object = new Account($this->db);
				return $object;
				break;
			
			case 'payment_salaries' :
				require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
				require_once DOL_DOCUMENT_ROOT . '/compta/salaries/class/paymentsalary.class.php';
				$object = new PaymentSalary($this->db);
				return $object;
				break;
			
			case 'tax' :
				require_once DOL_DOCUMENT_ROOT . '/core/lib/tax.lib.php';
				require_once DOL_DOCUMENT_ROOT . '/compta/sociales/class/chargesociales.class.php';
				$object = new ChargeSociales($this->db);
				return $object;
				break;
			
			case 'payment_vat' :
				require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
				require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/tva.class.php';
				$object = new Tva($this->db);
				return $object;
				break;
			
			// AGEFODD module definition
			case 'agefodd_session' :
				require_once DOL_DOCUMENT_ROOT . '/agefodd/lib/agefodd.lib.php';
				require_once DOL_DOCUMENT_ROOT . '/agefodd/class/agsession.class.php';
				$object = new Agsession($this->db);
				return $object;
				break;
			
			// case 'usergroup' : standard naming
			// case 'product' : standard naming
			// case 'project' : standard naming
			// case 'fichinter' : standard naming
			// case 'contact' : standard naming
			
			// specific module with standard naming
			default :
				if (file_exists(DOL_DOCUMENT_ROOT . '/' . $this->element . '/class/' . $this->element . '.class.php'))
					require_once DOL_DOCUMENT_ROOT . '/' . $this->element . '/class/' . $this->element . '.class.php';
				else
					require_once DOL_DOCUMENT_ROOT . '/custom/' . $this->element . '/class/' . $this->element . '.class.php';
					
					// gère le cas des modules internes posé dans le /core ou pas
				if (file_exists(DOL_DOCUMENT_ROOT . '/' . $this->element . '/lib/' . $this->element . '.lib.php'))
					require_once DOL_DOCUMENT_ROOT . '/' . $this->element . '/lib/' . $this->element . '.lib.php';
				elseif (file_exists(DOL_DOCUMENT_ROOT . '/core/lib/' . $this->element . '.lib.php'))
					require_once DOL_DOCUMENT_ROOT . '/core/lib/' . $this->element . '.lib.php';
				else // 3 essais comme au JO...
					require_once DOL_DOCUMENT_ROOT . '/' . $this->element . '/core/lib/' . $this->element . '.lib.php';
				$classname = ucfirst($this->element);
				$object = new $classname($this->db);
				return $object;
				break;
		}
	}
	function tabs_head_element($tabsid) {
		global $langs;
		global $object;
		global $form;
		global $user;
		global $conf;
		
		$help_url = 'EN:Module_customtabs|FR:Module_customtabs|ES:M&oacute;dulo_customtabs';
		llxHeader('', $langs->trans("CustomTabs"), $help_url);
		
		// selon l'onglet on affiche les données de l'onglet
		switch ($this->element) {
			case 'thirdparty' :
				$head = societe_prepare_head($object);
				$title = $langs->trans("ThirdParty");
				dol_fiche_head($head, "customtabs_" . $tabsid, $title, 0, 'company');
				print '<table class="border"width="100%">';
				print '<tr><td width="25%">' . $langs->trans("ThirdPartyName") . '</td>';
				print '<td colspan="3">' . $form->showrefnav($object, 'id', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&tabsid=' . $tabsid) . '</td></tr>';
				
				// Prefix
				if (! empty($conf->global->SOCIETE_USEPREFIX)) // Old not used prefix field
					print '<tr><td>' . $langs->trans('Prefix') . '</td><td colspan="3">' . $object->prefix_comm . '</td></tr>';
				
				if ($object->client) {
					print '<tr><td>';
					print $langs->trans('CustomerCode') . '</td><td colspan="3">';
					print $object->code_client;
					if ($object->check_codeclient() != 0)
						print ' <font class="error">(' . $langs->trans("WrongCustomerCode") . ')</font>';
					print '</td></tr>';
				}
				
				if ($object->fournisseur) {
					print '<tr><td>';
					print $langs->trans('SupplierCode') . '</td><td colspan="3">';
					print $object->code_fournisseur;
					if ($object->check_codefournisseur() != 0)
						print ' <font class="error">(' . $langs->trans("WrongSupplierCode") . ')</font>';
					print '</td></tr>';
				}
				break;
			
			case 'contact' :
				$head = contact_prepare_head($object);
				$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
				dol_fiche_head($head, "customtabs_" . $tabsid, $title, 0, 'contact');
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/contact/list.php">' . $langs->trans("BackToList") . '</a>';
				
				// Ref
				print '<tr><td width="20%">' . $langs->trans("Ref") . '</td><td colspan="3">' . $form->showrefnav($object, 'id', $linkback) . '</td></tr>';
				
				// Name
				print '<tr><td width="20%">' . $langs->trans("Lastname") . ' / ' . $langs->trans("Label") . '</td><td width="30%">' . $object->lastname . '</td>';
				print '<td width="20%">' . $langs->trans("Firstname") . '</td><td width="30%">' . $object->firstname . '</td></tr>';
				
				// Company
				if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
					if ($object->socid > 0) {
						$objsoc = new Societe($this->db);
						$objsoc->fetch($object->socid);
						print '<tr><td>' . $langs->trans("Company") . '</td><td colspan="3">' . $objsoc->getNomUrl(1) . '</td></tr>';
					} else {
						print '<tr><td>' . $langs->trans("Company") . '</td><td colspan="3">';
						print $langs->trans("ContactNotLinkedToCompany");
						print '</td></tr>';
					}
				}
				
				// Civility
				print '<tr><td>' . $langs->trans("UserTitle") . '</td><td colspan="3">';
				print $object->getCivilityLabel();
				print '</td></tr>';
				break;
			
			case 'product' :
				$head = product_prepare_head($object, $user);
				$titre = $langs->trans("CardProduct" . $object->type);
				$picto = ($object->type == 1 ? 'service' : 'product');
				dol_fiche_head($head, "customtabs_" . $tabsid, $titre, 0, $picto);
				print '<table class="border" width="100%">';
				
				print '<tr>';
				print '<td width="30%">' . $langs->trans("Ref") . '</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', '', 1, 'ref', '', '', '&tabsid=' . $tabsid);
				print '</td>';
				print '</tr>';
				
				// Label
				print '<tr><td>' . $langs->trans("Label") . '</td><td colspan="3">' . $object->libelle . '</td></tr>';
				
				// Status (to sell)
				print '<tr><td>' . $langs->trans("Status") . ' (' . $langs->trans("Sell") . ')</td><td>';
				print $object->getLibStatut(2, 0);
				print '</td></tr>';
				
				// Status (to buy)
				print '<tr><td>' . $langs->trans("Status") . ' (' . $langs->trans("Buy") . ')</td><td>';
				print $object->getLibStatut(2, 1);
				print '</td></tr>';
				break;
			
			case 'stock' :
				$head = stock_prepare_head($object);
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("Warehouse"), 0, 'stock');
				
				print '<table class="border" width="100%">';
				$linkback = '<a href="' . DOL_URL_ROOT . '/product/stock/liste.php">' . $langs->trans("BackToList") . '</a>';
				// Ref
				print '<tr><td width="25%">' . $langs->trans("Ref") . '</td><td colspan="3">';
				print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'libelle');
				print '</td>';
				
				print '<tr><td>' . $langs->trans("LocationSummary") . '</td><td colspan="3">' . $object->lieu . '</td></tr>';
				// Description
				print '<tr><td valign="top">' . $langs->trans("Description") . '</td><td colspan="3">' . nl2br($object->description) . '</td></tr>';
				// Address
				print '<tr><td>' . $langs->trans('Address') . '</td><td colspan="3">' . $object->address . '</td></tr>';
				
				// Town
				print '<tr><td width="25%">' . $langs->trans('Zip') . '</td><td width="25%">' . $object->zip . '</td>';
				print '<td width="25%">' . $langs->trans('Town') . '</td><td width="25%">' . $object->town . '</td></tr>';
				
				// Country
				print '<tr><td>' . $langs->trans('Country') . '</td><td colspan="3">';
				if (! empty($object->country_code)) {
					$img = picto_from_langcode($object->country_code);
					print($img ? $img . ' ' : '');
				}
				print $object->country;
				print '</td></tr>';
				break;
			
			case 'member' :
				$head = member_prepare_head($object);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("Member"), 0, 'user');
				
				$adht = new Adherent($this->db);
				$result = $adht->fetch($object->typeid);
				
				print '<table class="border" width="100%">';
				$linkback = '<a href="' . DOL_URL_ROOT . '/adherents/liste.php">' . $langs->trans("BackToList") . '</a>';
				// Reference
				print '<tr><td width="20%">' . $langs->trans('Ref') . '</td>';
				print '<td colspan="3">';
				print $form->showrefnav($object, 'id', $linkback, 1, 'ref', '', '', '&tabsid=' . $tabsid);
				
				print '</td></tr>';
				
				// Login
				if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
					print '<tr><td>' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</td><td class="valeur">' . $object->login . '&nbsp;</td></tr>';
					// Morphy
				print '<tr><td>' . $langs->trans("Nature") . '</td><td class="valeur" >' . $object->getmorphylib() . '</td></tr>';
				// Type
				print '<tr><td>' . $langs->trans("Type") . '</td><td class="valeur">' . $adht->getNomUrl(1) . "</td></tr>\n";
				// Company
				print '<tr><td>' . $langs->trans("Company") . '</td><td class="valeur">' . $object->societe . '</td></tr>';
				// Civility
				print '<tr><td>' . $langs->trans("UserTitle") . '</td><td class="valeur">' . $object->getCivilityLabel() . '&nbsp;</td></tr>';
				// Lastname
				print '<tr><td>' . $langs->trans("Lastname") . '</td><td class="valeur" colspan="3">' . $object->lastname . '&nbsp;</td></tr>';
				// Firstname
				print '<tr><td>' . $langs->trans("Firstname") . '</td><td class="valeur" colspan="3">' . $object->firstname . '&nbsp;</td></tr>';
				// Status
				print '<tr><td>' . $langs->trans("Status") . '</td><td class="valeur">' . $object->getLibStatut(4) . '</td></tr>';
				break;
			
			case 'project' :
				$head = project_prepare_head($object);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('Project'), 0, ($object->public ? 'projectpub' : 'project'));
				
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/projet/liste.php">' . $langs->trans("BackToList") . '</a>';
				
				// Ref
				print '<tr><td width="30%">' . $langs->trans("Ref") . '</td><td>';
				// Define a complementary filter for search of next/prev ref.
				if (! $user->rights->projet->all->lire) {
					$mine = $_REQUEST['mode'] == 'mine' ? 1 : 0;
					$projectsListId = $object->getProjectsAuthorizedForUser($user, $mine, 0);
					$object->next_prev_filter = " rowid in (" . (count($projectsListId) ? join(',', array_keys($projectsListId)) : '0') . ")";
				}
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref') . '</td></tr>';
				
				// Label
				print '<tr><td>' . $langs->trans("Label") . '</td><td>' . $object->title . '</td></tr>';
				
				print '<tr><td>' . $langs->trans("Company") . '</td><td>';
				if ($object->socid > 0) {
					$objsoc = new Societe($this->db);
					$objsoc->fetch($object->socid);
					print $objsoc->getNomUrl(1);
				} else
					print '&nbsp;';
				print '</td></tr>';
				
				// Visibility
				print '<tr><td>' . $langs->trans("Visibility") . '</td><td>';
				if ($object->public)
					print $langs->trans('SharedProject');
				else
					print $langs->trans('PrivateProject');
				print '</td></tr>';
				
				// Statut
				print '<tr><td>' . $langs->trans("Status") . '</td><td>' . $object->getLibStatut(4) . '</td></tr>';
				break;
			
			case 'contrat' :
				$object->fetch_thirdparty();
				$head = contract_prepare_head($object);
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("Contract"), 0, 'contract');
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/contrat/liste.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
				// Reference
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="5">' . $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '') . '</td></tr>';
				
				// Societe
				print '<tr><td>' . $langs->trans("Customer") . '</td><td colspan="3">' . $object->thirdparty->getNomUrl(1) . '</td></tr>';
				break;
			
			case 'intervention' :
				$object->fetch_thirdparty();
				$head = fichinter_prepare_head($object, $user);
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("InterventionCard"), 0, 'intervention');
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/fichinter/list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
				// Reference
				print '<tr><td width="30%">' . $langs->trans("Ref") . '</td>';
				print '<td>' . $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid=' . $tabsid) . '</td></tr>';
				
				// Societe
				print "<tr><td>" . $langs->trans("Company") . "</td><td>" . $object->client->getNomUrl(1) . "</td></tr>";
				break;
			
			case 'shipping' :
				$soc = new Societe($this->db);
				$soc->fetch($object->socid);
				
				$head = shipping_prepare_head($object);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("Shipment"), 0, 'sending');
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/expedition/liste.php">' . $langs->trans("BackToList") . '</a>';
				// Ref
				print '<tr><td width="20%">' . $langs->trans("Ref") . '</td>';
				print '<td colspan="3">' . $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid=' . $tabsid) . '</td></tr>';
				
				// Customer
				print '<tr><td width="20%">' . $langs->trans("Customer") . '</td><td colspan="3">' . $soc->getNomUrl(1) . '</td></tr>';
				
				// Linked documents
				if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled)) {
					print '<tr><td>';
					$objectsrc = new Commande($this->db);
					$objectsrc->fetch($object->$typeobject->id);
					print $langs->trans("RefOrder") . '</td>';
					print '<td colspan="3">';
					print $objectsrc->getNomUrl(1, 'commande');
					print "</td>\n";
					print '</tr>';
				}
				if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled)) {
					print '<tr><td>';
					$objectsrc = new Propal($db);
					$objectsrc->fetch($object->$typeobject->id);
					print $langs->trans("RefProposal") . '</td>';
					print '<td colspan="3">';
					print $objectsrc->getNomUrl(1, 'expedition');
					print "</td>\n";
					print '</tr>';
				}
				// Ref customer
				print '<tr><td>' . $langs->trans("RefCustomer") . '</td><td colspan="3">' . $object->ref_customer . "</a></td></tr>";
				break;
			
			case 'user' :
				$head = user_prepare_head($object);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("User"), 0, 'user');
				print '<table class="border" width="100%">';
				
				// Reference
				print '<tr><td width="20%">' . $langs->trans('Ref') . '</td>';
				print '<td colspan="3">';
				print $form->showrefnav($object, 'id', '', $user->rights->user->user->lire || $user->admin);
				print '</td></tr>';
				
				// Lastname
				print '<tr><td>' . $langs->trans("Lastname") . '</td><td class="valeur" colspan="3">' . $object->lastname . '&nbsp;</td></tr>';
				// Firstname
				print '<tr><td>' . $langs->trans("Firstname") . '</td><td class="valeur" colspan="3">' . $object->firstname . '&nbsp;</td></tr>';
				// Login
				print '<tr><td>' . $langs->trans("Login") . '</td><td class="valeur" colspan="3">' . $object->login . '&nbsp;</td></tr>';
				break;
			
			case 'usergroup' :
				$head = group_prepare_head($object);
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("Group"), 0, 'group');
				
				print '<table class="border" width="100%">';
				
				// Ref
				print '<tr><td width="25%" valign="top">' . $langs->trans("Ref") . '</td>';
				print '<td colspan="2">' . $form->showrefnav($object, 'id', '', $canreadperms);
				print '</td></tr>';
				
				// Name
				print '<tr><td width="25%" valign="top">' . $langs->trans("Name") . '</td>';
				print '<td width="75%" class="valeur">' . $object->nom;
				if (! $object->entity)
					print img_picto($langs->trans("GlobalGroup"), 'redstar');
				print "</td></tr>\n";
				break;
			
			case 'payment_salaries' :
				$head = payment_salaries_prepare_head($object);
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("SalaryPayment"), 0, 'payment');
				
				print '<table class="border" width="100%">';
				
				// Ref
				print '<tr><td width="25%" valign="top">' . $langs->trans("Ref") . '</td>';
				print '<td colspan="2">' . $form->showrefnav($object, 'id', '', $canreadperms);
				print '</td></tr>';
				
				// Person
				print '<tr><td>' . $langs->trans("Person") . '</td><td>';
				$usersal = new User($this->db);
				$usersal->fetch($object->fk_user);
				print $usersal->getNomUrl(1);
				print '</td></tr>';
				
				// Label
				print '<tr><td>' . $langs->trans("Label") . '</td><td>' . $object->label . '</td></tr>';
				
				print "<tr>";
				print '<td>' . $langs->trans("DateStartPeriod") . '</td><td colspan="3">';
				print dol_print_date($object->datesp, 'day');
				print '</td></tr>';
				
				print '<tr><td>' . $langs->trans("DateEndPeriod") . '</td><td colspan="3">';
				print dol_print_date($object->dateep, 'day');
				print '</td></tr>';
				
				print '<tr><td>' . $langs->trans("Amount") . '</td><td colspan="3">' . price($object->amount, 0, $outputlangs, 1, - 1, - 1, $conf->currency) . '</td></tr>';
				break;
			
			case 'payment_vat' :
				$head = payment_vat_prepare_head($object);
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("VATPayment"), 0, 'payment');
				
				print '<table class="border" width="100%">';
				
				// Ref
				print '<tr><td width="25%" valign="top">' . $langs->trans("Ref") . '</td>';
				print '<td colspan="2">' . $form->showrefnav($object, 'id', '', $canreadperms);
				print '</td></tr>';
				
				print '<tr><td>' . $langs->trans("Amount") . '</td><td colspan="3">' . price($object->amount, 0, $outputlangs, 1, - 1, - 1, $conf->currency) . '</td></tr>';
				break;
			
			case 'tax' :
				$head = tax_prepare_head($object);
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans("SocialContribution"), 0, 'bill');
				
				print '<table class="border" width="100%">';
				
				// Ref
				print '<tr><td width="25%" valign="top">' . $langs->trans("Ref") . '</td>';
				print '<td colspan="2">' . $form->showrefnav($object, 'id', '', $canreadperms);
				print '</td></tr>';
				
				// Label
				print '<tr><td>' . $langs->trans("Label") . '</td><td colspan="2">' . $object->lib . '</td></tr>';
				
				// Type
				print "<tr><td>" . $langs->trans("Type") . "</td><td>" . $object->type_libelle . "</td>";
				
				// Amount
				print '<tr><td>' . $langs->trans("AmountTTC") . '</td><td>' . price($object->amount, 0, $outputlangs, 1, - 1, - 1, $conf->currency) . '</td></tr>';
				
				break;
			
			case 'equipement' :
				$soc = new Societe($this->db);
				$head = equipement_prepare_head($object);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('EquipementCard'), 0, 'equipement@equipement');
				
				print '<table class="border" width="100%">';
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref', '', '&tabsid=' . $tabsid);
				print '</td></tr>';
				require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
				$prod = new Product($this->db);
				$prod->fetch($object->fk_product);
				print '<tr><td >' . $langs->trans("Product") . '</td><td>' . $prod->getNomUrl(1) . " : " . $prod->label . '</td></tr>';
				
				// fournisseur
				print '<tr><td >' . $langs->trans("Fournisseur") . '</td><td>';
				if ($object->fk_soc_fourn > 0) {
					$soc->fetch($object->fk_soc_fourn);
					print $soc->getNomUrl(1);
				}
				print '</td></tr>';
				
				// client
				print '<tr><td >' . $langs->trans("Client") . '</td><td>';
				if ($object->fk_soc_client > 0) {
					$soc->fetch($object->fk_soc_client);
					print $soc->getNomUrl(1);
				}
				print '</td></tr>';
				break;
			
			case 'factory' :
				$head = factory_prepare_head($object);
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('FactoryCard'), 0, 'factory@factory');
				
				print '<table class="border" width="100%">';
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="3">';
				print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'ref', '');
				print '</td></tr>';
				
				require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
				require_once DOL_DOCUMENT_ROOT . "/product/stock/class/entrepot.class.php";
				$prod = new Product($this->db);
				$prod->fetch($object->fk_product);
				print '<tr><td >' . $langs->trans("Product") . '</td><td>' . $prod->getNomUrl(1) . " : " . $prod->label . '</td></tr>';
				
				// Lieu de stockage
				print '<tr><td>' . $langs->trans("EntrepotStock") . '</td><td>';
				if ($object->fk_entrepot > 0) {
					$entrepotStatic = new Entrepot($this->db);
					$entrepotStatic->fetch($object->fk_entrepot);
					print $entrepotStatic->getNomUrl(1) . " - " . $entrepotStatic->lieu . " (" . $entrepotStatic->zip . ")";
				}
				print '</td></tr>';
				
				// Date start planned
				print '<tr><td width=20% >' . $langs->trans("DateStartPlanned") . '</td><td width=30% valign=top>';
				print dol_print_date($object->date_start_planned, 'day');
				print '</td>';
				// Date start made
				print '<td valign=top  width=20%>' . $langs->trans("DateStartMade") . '</td>';
				print '<td width=30% >';
				print dol_print_date($object->date_start_made, 'day');
				print '</td></tr>';
				break;
			
			case 'lead' :
				$soc = new Societe($this->db);
				$head = lead_prepare_head($object);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('LeadCard'), 0, 'lead@lead');
				
				print '<table class="border" width="100%">';
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
				print '</td></tr>';
				break;
			
			case 'dictionary' :
				$head = dictionary_prepare_head($object);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('Dictionarys'), 0, 'customtabs@customtabs');
				break;
			
			case 'propal' :
				$head = propal_prepare_head($object);
				
				$soc = new Societe($this->db);
				$soc->fetch($object->socid);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('Proposal'), 0, 'propal');
				
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/comm/propal/list.php' . (! empty($object->socid) ? '?socid=' . $object->socid : '') . '">' . $langs->trans("BackToList") . '</a>';
				
				// Ref
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td>';
				print '<td colspan="3">';
				
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid=' . $tabsid);
				print '</td></tr>';
				
				// Ref customer
				print '<tr><td width="20%">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RefCustomer');
				print '</td>';
				print '</tr></table>';
				print '</td>';
				print '<td colspan="5">';
				print $object->ref_client;
				print '</td></tr>';
				
				// Company
				print '<tr><td>' . $langs->trans("Company") . '</td>';
				print '<td colspan="3">' . $soc->getNomUrl(1) . '</td>';
				
				break;
			
			case 'commande' :
				$head = commande_prepare_head($object);
				
				$soc = new Societe($this->db);
				$soc->fetch($object->socid);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('CustomerOrder'), 0, 'order');
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/commande/liste.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
				
				// Ref
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td>';
				print '<td colspan="3">';
				
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid=' . $tabsid);
				print '</td></tr>';
				
				// Ref customer
				print '<tr><td width="20%">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RefCustomer');
				print '</td>';
				print '</tr></table>';
				print '</td>';
				print '<td colspan="5">';
				print $object->ref_client;
				print '</td></tr>';
				
				// Company
				print '<tr><td>' . $langs->trans("Company") . '</td>';
				print '<td colspan="3">' . $soc->getNomUrl(1, 'compta') . '</td>';
				
				break;
			
			case 'invoice' :
				$head = facture_prepare_head($object);
				
				$soc = new Societe($this->db);
				$soc->fetch($object->socid);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('InvoiceCustomer'), 0, 'bill');
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/compta/facture/list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
				
				// Ref
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td>';
				print '<td colspan="3">';
				
				print $form->showrefnav($object, 'ref', $linkback, 1, 'facnumber', 'ref', '', '&tabsid=' . $tabsid);
				print '</td></tr>';
				
				// Ref customer
				print '<tr><td width="20%">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RefCustomer');
				print '</td>';
				print '</tr></table>';
				print '</td>';
				print '<td colspan="5">';
				print $object->ref_client;
				print '</td></tr>';
				
				// Company
				print '<tr><td>' . $langs->trans("Company") . '</td>';
				print '<td colspan="3">' . $soc->getNomUrl(1, 'compta') . '</td>';
				
				break;
			
			case 'supplier_invoice' :
				
				$head = facturefourn_prepare_head($object);
				$titre = $langs->trans('SupplierInvoice');
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $titre, 0, 'bill');
				
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/fourn/facture/list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
				
				// Ref
				print '<tr><td width="20%" class="nowrap">' . $langs->trans("Ref") . '</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
				print '</td>';
				print "</tr>\n";
				
				// Ref supplier
				print '<tr><td class="nowrap">' . $langs->trans("RefSupplier") . '</td><td colspan="3">' . $object->ref_supplier . '</td>';
				print "</tr>\n";
				
				// Company
				print '<tr><td>' . $langs->trans('Supplier') . '</td><td colspan="3">' . $object->thirdparty->getNomUrl(1, 'supplier') . '</td></tr>';
				
				// Type
				print '<tr><td>' . $langs->trans('Type') . '</td><td colspan="4">';
				print $object->getLibType();
				if ($object->type == 1) {
					$facreplaced = new FactureFournisseur($db);
					$facreplaced->fetch($object->fk_facture_source);
					print ' (' . $langs->transnoentities("ReplaceInvoice", $facreplaced->getNomUrl(1)) . ')';
				}
				if ($object->type == 2) {
					$facusing = new FactureFournisseur($db);
					$facusing->fetch($object->fk_facture_source);
					print ' (' . $langs->transnoentities("CorrectInvoice", $facusing->getNomUrl(1)) . ')';
				}
				
				$facidavoir = $object->getListIdAvoirFromInvoice();
				if (count($facidavoir) > 0) {
					print ' (' . $langs->transnoentities("InvoiceHasAvoir");
					$i = 0;
					foreach ( $facidavoir as $fid ) {
						if ($i == 0)
							print ' ';
						else
							print ',';
						$facavoir = new FactureFournisseur($db);
						$facavoir->fetch($fid);
						print $facavoir->getNomUrl(1);
					}
					print ')';
				}
				if ($facidnext > 0) {
					$facthatreplace = new FactureFournisseur($db);
					$facthatreplace->fetch($facidnext);
					print ' (' . $langs->transnoentities("ReplacedByInvoice", $facthatreplace->getNomUrl(1)) . ')';
				}
				print '</td></tr>';
				// Label
				print '<tr><td>' . $langs->transnoentities("Label") . '</td><td colspan="3">' . $object->label . '</td></tr>';
			
			case 'supplier_order' :
				$head = ordersupplier_prepare_head($object);
				
				$soc = new Societe($this->db);
				$soc->fetch($object->socid);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('SupplierOrder'), 0, 'order');
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/fourn/commande/liste.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
				
				// Ref
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td>';
				print '<td colspan="3">';
				
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', '&tabsid=' . $tabsid);
				print '</td></tr>';
				
				// Ref customer
				print '<tr><td width="20%">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('RefSupplier');
				print '</td>';
				print '</tr></table>';
				print '</td>';
				print '<td colspan="5">';
				print $object->ref_supplier;
				print '</td></tr>';
				
				// Company
				print '<tr><td>' . $langs->trans("Company") . '</td>';
				print '<td colspan="3">' . $soc->getNomUrl(1, 'compta') . '</td>';
				
				break;
			
			case 'bank' :
				$head = bank_prepare_head($object);
				
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('FinancialAccount'), 0, 'account');
				
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/compta/bank/index.php">' . $langs->trans("BackToList") . '</a>';
				
				// Ref
				print '<tr><td valign="top" width="25%">' . $langs->trans("Ref") . '</td>';
				print '<td colspan="3">';
				print $form->showrefnav($object, 'ref', $linkback, 1, 'ref');
				print '</td></tr>';
				
				// Label
				print '<tr><td valign="top">' . $langs->trans("Label") . '</td>';
				print '<td colspan="3">' . $object->label . '</td></tr>';
				
				print '</table>';
				
				break;
			
			case 'agefodd_session' :
				$head = session_prepare_head($object);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans('AgfSessionDetail'), 0, 'calendarday');
				
				print '<table class="border" width="100%">';
				
				$linkback = '<a href="' . DOL_URL_ROOT . '/agefodd/session/list.php">' . $langs->trans("BackToList") . '</a>';
				
				// Ref
				print '<tr><td valign="top" width="25%">' . $langs->trans("Ref") . '</td>';
				print '<td colspan="3">';
				print $object->id;
				print '</td></tr>';
				
				print '<tr><td>' . $langs->trans("AgfFormIntitule") . '</td>';
				print '<td>' . $object->formintitule . '</td></tr>';
				
				print '<tr><td>' . $langs->trans("AgfFormIntituleCust") . '</td>';
				print '<td>' . $object->intitule_custo . '</td></tr>';
				
				// Label
				print '<tr><td valign="top">' . $langs->trans("AgfFormRef") . '</td>';
				print '<td colspan="3">' . $object->formref . '</td></tr>';
				
				print '</table>';
				
				break;
			
			default :
				$fct_headname = $this->element . "_prepare_head";
				$elementname = ucfirst($this->element);
				$head = $fct_headname($object);
				dol_fiche_head($head, "customtabs_" . $tabsid, $langs->trans($elementname), 0, $this->element . '@' . $this->element);
				
				print '<table class="border" width="100%">';
				print '<tr><td width="25%">' . $langs->trans('Ref') . '</td><td colspan="3">';
				print $form->showrefnav($object, 'ref', '', 1, 'ref', 'ref');
				print '</td></tr>';
				break;
		}
		print '</table>';
		print '</div>';
	}
	function setOptionalsFromPost_line($extralabels, &$object, $onlykey = '', $i) {
		global $extrafields;
		global $_POST, $langs;
		$nofillrequired = ''; // For error when required field left blank
		$error_field_required = array ();
		
		if (is_array($extralabels)) {
			// Get extra fields
			foreach ( $extralabels as $key => $value ) {
				if (! empty($onlykey) && $key != $onlykey)
					continue;
				
				$key_type = $extrafields->attribute_type[$key];
				
				if ($extrafields->attribute_required[$key] && ! GETPOST("options_$key", 2)) {
					$nofillrequired ++;
					$error_field_required[] = $value;
				}
				
				if (in_array($key_type, array (
						'date',
						'datetime' 
				))) {
					// Clean parameters
					$value_key = dol_mktime($_POST["options_" . $key . $i . "hour"], $_POST["options_" . $key . $i . "min"], 0, $_POST["options_" . $key . $i . "month"], $_POST["options_" . $key . $i . "day"], $_POST["options_" . $key . $i . "year"]);
				} else if (in_array($key_type, array (
						'checkbox' 
				))) {
					$value_arr = GETPOST("options_" . $key . $i);
					if (! empty($value_arr)) {
						$value_key = implode($value_arr, ',');
					} else {
						$value_key = '';
					}
				} else if (in_array($key_type, array (
						'price',
						'double' 
				))) {
					$value_arr = GETPOST("options_" . $key . $i);
					$value_key = price2num($value_arr);
				} else {
					$value_key = GETPOST("options_" . $key . $i);
				}
				$object->array_options["options_" . $key . $i] = $value_key;
			}
			
			if ($nofillrequired) {
				$langs->load('errors');
				setEventMessage($langs->trans('ErrorFieldsRequired') . ' : ' . implode(', ', $error_field_required), 'errors');
				return - 1;
			} else {
				return 1;
			}
		} else {
			return 0;
		}
	}
}
?>