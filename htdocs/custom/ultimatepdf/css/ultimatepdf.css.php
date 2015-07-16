<?php
/* Copyright (C) 2011 Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2012 Philippe Grand <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       /ultimatepdf/css/ultimatepdf.css.php
 *		\brief      Fichier de style CSS complementaire du module Ultimatepdf
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');


$res=0;
$res=@include("../../../master.inc.php");								// For "custom" directory
if (! $res) $res=@include("../../master.inc.php");						// For root directory
if (! $res) @include("../../../../../dolibarr/htdocs/master.inc.php");	// Used on dev env only

require_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");

// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


if (! empty($_GET["lang"])) $langs->setDefaultLang($_GET["lang"]);	// If language was forced on URL by the main.inc.php
$langs->load("main",0,1);
$right=($langs->direction=='rtl'?'left':'right');
$left=($langs->direction=='rtl'?'right':'left');
?>

img.switchdesign {
	cursor:pointer;
	padding: <?php echo ($conf->browser->phone?'0':'8')?>px 0px 0px 0px;
	margin: 0px 0px 0px 8px;
	text-decoration: none;
	color: white;
	font-weight: bold;
}
<!-- Set Logo height -->
.ui-widget-header {
	background:#b9cd6d;
	border: 1px solid #b9cd6d;
	color: #FFFFFF;
	font-weight: bold;
}
.ui-widget-content {
	background: #cedc98;
	border: 1px solid #DDDDDD;
	color: #333333;
}
.ui-state-active {
	border: 1px solid #fbd850;
	color: #eb8f00;
	font-weight: bold;
}
.ui-icon-gripsmall-diagonal-sw { 
    background-image: url('<?php echo dol_buildpath("/ultimatepdf/img/ui-icons_sw_256x240.png",1); ?>')!important;
}
.ui-resizable-sw {
    bottom: 1px;
    left: 1px;
}
#container { width: 350px; height: 280px; }
#container2,#container3,#container4,#container5 { width: 210px; height: 297px; }
#resizable-1, #resizable-3 {background-position: top left; 
width: 150px; height: 150px; } 
#resizable-1, #resizable-3, #container, #container2 { padding: 0; }
#resizable-5 {
	left: 10px;
	right: 10px;
	top : 10px; 
	bottom : 10px;
	width: 190px; 
	height: 277px;
}
#resizable-7 {
	background-position: top left;
	width: 30px; height: 297px;
}
#resizable-9 {
	left: 100px;
	background-position: top 100px;
	width: 30px; height: 297px;
}
#resizable-11 {

	left: 0;
	right: 0;
	top : 287px; 
	bottom : 10px;
}
::-webkit-input-placeholder {
   color: #003f7f;
}
:-moz-placeholder { /* Firefox 18- */
   color: #003f7f;  
}
::-moz-placeholder {  /* Firefox 19+ */
   color: #003f7f;  
}
:-ms-input-placeholder {  
   color: #003f7f;  
}

<!-- End set Logo height -->