<?php 

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';

class FormProjets2o
  extends FormProjets
{

	function selected_projects($socid=-1, $selected='', $htmlname='projectid')
	{
		global $user,$conf,$langs;

		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

		$out='';

		$hideunselectables = false;
		if (! empty($conf->global->PROJECT_HIDE_UNSELECTABLES)) $hideunselectables = true;

		$projectsListId = false;
		if (empty($user->rights->projet->all->lire))
		{
			$projectstatic=new Project($this->db);
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1);
		}

		// Search all projects
		$sql = 'SELECT p.rowid, p.ref, p.title, p.fk_soc, p.fk_statut, p.public';
		$sql.= ' FROM '.MAIN_DB_PREFIX .'projet as p';
		$sql.= " WHERE p.entity = ".$conf->entity;
		if ($projectsListId !== false) $sql.= " AND p.rowid IN (".$projectsListId.")";
// 		if ($socid == 0) $sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
		if ($socid > 0)  $sql.= " AND (p.fk_soc=".$socid." )";
		$sql.= " ORDER BY p.ref ASC";
// echo $sql; 
		dol_syslog(get_class($this)."::select_projects sql=".$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{

// 			if (empty($option_only)) {
// 				$out.= '<select class="flat" name="'.$htmlname.'">';
// 			}
// 			if (!empty($show_empty)) {
// 				$out.= '<option value="0">&nbsp;</option>';
// 			}
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
					if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && ! $user->rights->societe->lire)
					{
						// Do nothing
					}
					else
					{
						$labeltoshow=dol_trunc($obj->ref,18);
						//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
						//else $labeltoshow.=' ('.$langs->trans("Private").')';
// 						if (!empty($selected) && $selected == $obj->rowid && $obj->fk_statut > 0)
// 						{
							$out.= '<input type="hidden" name="projectid" value="'.$obj->rowid.'" />'.$labeltoshow.' - '.dol_trunc($obj->title,$maxlength).'';
// 						}
// 						else
// 						{
// 							$disabled=0;
// 							$labeltoshow.=' '.dol_trunc($obj->title,$maxlength);
// 							if (! $obj->fk_statut > 0)
// 							{
// 								$disabled=1;
// 								$labeltoshow.=' - '.$langs->trans("Draft");
// 							}
// 							if ($socid > 0 && (! empty($obj->fk_soc) && $obj->fk_soc != $socid))
// 							{
// 								$disabled=1;
// 								$labeltoshow.=' - '.$langs->trans("LinkedToAnotherCompany");
// 							}
// 
// 							if ($hideunselectables && $disabled)
// 							{
// 								$resultat='';
// 							}
// 							else
// 							{
// 								$resultat='<input type="hidden" value="'.$obj->rowid.'"';
// 								if ($disabled) $resultat.=' disabled="disabled"';
// 								//if ($obj->public) $labeltoshow.=' ('.$langs->trans("Public").')';
// 								//else $labeltoshow.=' ('.$langs->trans("Private").')';
// 								$resultat.='';
// 								$resultat.='/>';
// 								$resultat.=$labeltoshow;
// 								
// 							}
// 							$out.= $resultat;
// 						}
					}
					$i++;
				}
			}
// 			if (empty($option_only)) {
// 				$out.= '</select>';
// 			}
			print $out;

			$this->db->free($resql);
			return $obj->rowid;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}
}