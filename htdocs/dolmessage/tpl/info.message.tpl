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
	@brief display message 
*/
	global $Message; 
  
        
header('Content-Type: text/html; charset='. $Message->GetCharset());


if($Message->GetHtmlMsg() != '') {
    $html = $Message->GetHtmlMsg();
    // add target to links 
    $html = preg_replace('/(<a.[^>]*href="http:[^"]+".[^>]*)>/is','\\1 target="viewMail">',$html);
    // inject subject in tab title for full screen 
    $html = preg_replace('/<head>/is',"<head>\n<title>".$Message->GetSubject().'</title>',$html);
    // inject files joined
    $pjAttached = $Message->GetAttach() ;
    //var_dump($pjAttached);
    $pjSearch = array();
    $pjReplace = array();
    foreach ($pjAttached as $key => $value) { // build list of search replace
        if($value->appType=5){
            $matches = null;
            $returnValue = preg_match('/<(.*)>/', $key, $matches);
            if($matches !== null) {
                $pjSearch[] = 'src="cid:'.$matches[1].'"';
                $path_parts = pathinfo($value->name);
                $pjReplace[] = 'src="data:image/'.strtolower($path_parts['extension']).';base64,'. base64_encode($value->data) .'"';
            }
        }
    }
    $html = str_replace($pjSearch, $pjReplace, $html);
    echo $html;
} else { 
    $html = $Message->GetPlainMsg();     
    $html = preg_replace('/https?:\/\/[\w\-\.!~#?&=+\*\'"(),\/]+/','<a href="$0" target="viewMail">$0</a>',$html);
    echo $html;
} ?>