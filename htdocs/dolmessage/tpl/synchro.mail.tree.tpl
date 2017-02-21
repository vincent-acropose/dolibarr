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
	global $menus,$langs,$folder,$info,$dolimap; 

  foreach ($menus as $k=>$o)
  {

    $cible = $o->name;
    # Subfolders
    $ex = explode('/', $o->name);
    $m =''; 
    while (count($ex) > 1)
    {
      $p = array_shift($ex);
      $m .= '&nbsp;&nbsp;&raquo;&nbsp;&nbsp;';
    }
      // extract name
      $n = $ex[sizeof($ex)-1];
      // recover accent
      $n = utf8_encode($n);
      // hide server {server_adress} in folder name
      $n = preg_replace('/\\{.*\\}/', '', $n); 
      if (($folder=='INBOX' && 'INBOX'==$n) || $folder == imap_utf7_encode($cible)){
          $classAdd='tabactive';
      } else{
          $classAdd='';
      }
      // translate INBOX -> Boite de reception
      $n = $langs->trans($n);
      echo "<div class='mailFolder $classAdd'>"
              . "<a href='" . dol_buildpath('/dolmessage/synchro.php', 1) . 
              "?number=" . GETPOST('number') .'&identifiid='.GETPOST('identifiid') . 
              "&folder=" . urlencode(imap_utf7_encode(str_replace($user->mailbox_imap_ref, '', $cible))) . "'"
              . " class=' $classAdd'><span>". $m ."</span>";   
      echo $n;
      if(!isset($info)){
                $info = $dolimap->Check();
      }
      if($classAdd!=''){
          echo "&nbsp;({$info->Nmsgs})";
      }
      echo "</a>";
      echo "</div>";//var_dump(urlencode(imap_utf7_encode($cible)));
  }

?>


