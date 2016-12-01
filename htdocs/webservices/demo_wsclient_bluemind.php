<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file htdocs/webservices/demo_wsclient_other.php
 * \brief Demo page to make a client call to Dolibarr WebServices "server_other"
 */

// This is to make Dolibarr working with Plesk
set_include_path ( $_SERVER ['DOCUMENT_ROOT'] . '/htdocs' );

require_once '../master.inc.php';
require_once NUSOAP_PATH . '/nusoap.php'; // Include SOAP
header ( "Content-type: text/html; charset=utf8" );

$WS_DOL_URL = 'https://192.168.0.159/soap/proxy'; // If not a page, should end with /
$ns = 'http://server.soap.bluemind.net/';

// Set the WebService URL
dol_syslog ( "Create nusoap_client for URL=" . $WS_DOL_URL );
$soapclient = new nusoap_client ( $WS_DOL_URL );
if ($soapclient) {
	$soapclient->soap_defencoding = 'UTF-8';
	$soapclient->decodeUTF8 ( false );
}
				


// $soapclient->debugLevel=9;

// Call the WebService method and store its result in $result.
$datatest = array (
	'arg0' => 'dolibarr@localdomain.pro','arg1' => '160182f6-857f-4e93-addf-32690f0ca7d6','arg2' => 'dolibarr' 
);

$resultlogin = $soapclient->call ( 'login', $datatest, $ns, '' );
if (! $resultlogin) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {
	
	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' . "\n";
	echo '<html>' . "\n";
	echo '<head>';
	echo '</head>' . "\n";
	
	echo '<body>' . "\n";
	echo 'NUSOAP_PATH=' . NUSOAP_PATH . '<br>';
	
	// print $soapclient->debug_str;
	
	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'LOGIN';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';
	
	echo '<hr>';
	
	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $resultlogin );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}



/*
$datatest = array (
	'arg0' => $resultlogin,'arg1' => 'jdupond@localdomain.pro' 
);


$resultsudo = $soapclient->call ( 'sudo', $datatest, $ns, '' );
if (! $resultsudo) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {
	
	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'SUDO';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';
	
	echo '<hr>';
	
	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $resultsudo );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}
*/

$datatest = array (
'arg0' => $resultlogin,'arg1' => array('email'=>'fhenry@localdomain.pro')
);


$resultfinduser = $soapclient->call ( 'findUsers', $datatest, $ns, '' );
if (! $resultfinduser) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'findUsers';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $resultfinduser );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}

/*

$datatest = array (
'arg0' => $resultsudo
);
$resultfindme = $soapclient->call ( 'findMe', $datatest, $ns, '' );
if (! $resultfindme) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'findMe';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $resultfindme );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}


$datatest = array (
'arg0' => $resultsudo,
'arg1' => '18'
);
$result = $soapclient->call ( 'getEventFromId', $datatest, $ns, '' );
if (! $result) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'getEventFromId';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $result );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}

*/
/*
$eventTocreate['allday']=0;
$eventTocreate['duration']=60;
$eventTocreate['alert']=600;
$eventTocreate['date']='2013-11-06T10:00:00Z';
$eventTocreate['userCreate']=4;
$eventTocreate['ownerId']=1;
$eventTocreate['owner']=1;
$eventTocreate['description']='FromDolibarrr!!!';
$eventTocreate['title']='FromDolibarrr!!!';


$attendees['calendarInfo']=array('id'=>3);
$attendees['displayName']='Florian HEnry';
$attendees['id']=4;
$attendees['notify']=1;
$attendees['percent']=0;
$attendees['required']='CHAIR';
$attendees['state']='ACCEPTED';
$attendees['type']='user';

$eventTocreate['attendees']=$attendees;


$datatest = array (
'arg0' => $resultsudo,
'arg1' => $eventTocreate);


 $resultcreateEvent = $soapclient->call ( 'createEvent', $datatest, $ns, '' );
if (! $resultcreateEvent) {
print 'ERROROROROROR';
print $soapclient->error_str;
print "<br>\n\n";
print $soapclient->request;
print "<br>\n\n";
print $soapclient->response;
exit ();
} else {

echo "<h2>Request:</h2>";
echo '<h4>Function</h4>';
echo 'createEvent';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

echo '<hr>';

echo "<h2>Response:</h2>";
echo '<h4>Result</h4>';
echo '<pre>';
print_r ( $resultcreateEvent );
echo '</pre>';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}
*/


/*

$datatest = array (
'arg0' => $resultuser
);
$result = $soapclient->call ( 'findCalendar', $datatest, $ns, '' );
if (! $result) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'findCalendar';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $result );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}


$datatest = array (
'arg0' => $resultuser
);
$result = $soapclient->call ( 'listCalendars', $datatest, $ns, '' );
if (! $result) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'listCalendars';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $result );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}
*/







/*
$datatest = array (
'arg0' => $resultuser,
'arg1' => $result['id'],
'arg2' => 'VEVENT',
);
$result = $soapclient->call ( 'getAllEvents', $datatest, $ns, '' );
if (! $result) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'getAllEvents';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $result );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}
*/


/*
$datatest = array (
'arg0' => 'fhenry@localdomain.pro','arg1' => 'ec7cd145-78f9-4791-9f95-c553002b021c','arg2' => 'dolibarr'
);

$resultuser = $soapclient->call ( 'login', $datatest, $ns, '' );
if (! $resultuser) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">' . "\n";
	echo '<html>' . "\n";
	echo '<head>';
	echo '</head>' . "\n";

	echo '<body>' . "\n";
	echo 'NUSOAP_PATH=' . NUSOAP_PATH . '<br>';

	// print $soapclient->debug_str;

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'LOGIN';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $resultuser );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}


$datatest = array (
'arg0' => $resultuser
);
$resultfindMe = $soapclient->call ( 'findMe', $datatest, $ns, '' );
if (! $resultfindMe) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'findMe';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $resultfindMe );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}

*/
/*
$datatest = array (
'arg0' => $resultuser,
'arg1' => 'fhenry',
'arg2' => 'VEVENT',
);
$result = $soapclient->call ( 'getAllEvents', $datatest, $ns, '' );
if (! $result) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'getAllEvents';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $result );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}

/*
$datatest = array (
'arg0' => $resultuser,
'arg1' => '2'
);
$result = $soapclient->call ( 'getEventFromId', $datatest, $ns, '' );
if (! $result) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'getEventFromId';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $result );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}
*/
/*
$datatest = array (
'arg0' => $resultuser,
'arg1' => '2'
);
$result = $soapclient->call ( 'getEventFromId', $datatest, $ns, '' );
if (! $result) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'getEventFromId';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $result );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}

*/


//$eventTocreate['customProperties']=$resultfindMe['calendarRights']['customProperties'];
//$eventTocreate['entityId']=$resultfindMe['calendarRights']['customProperties']['entityId'];
//$eventTocreate['id']=$resultfindMe['calendarRights']['customProperties']['id'];
//$eventTocreate['timeCreate']=dol_now();
//$eventTocreate['alert']=1;
$eventTocreate['allday']=0;
$eventTocreate['duration']=600;
//$eventTocreate['date']=dol_now();
//$eventTocreate['date']='2013-11-02 14:00:00';
//$eventTocreate['date']=dol_mktime(14, 0, 0, 11, 02, 2014);
//$eventTocreate['date']=$db->idate(dol_mktime(14, 0, 0, 11, 02, 2014));
$eventTocreate['date']='2013-11-08T11:00:00Z';
$eventTocreate['userCreate']='4';
$eventTocreate['ownerId']=$resultuser['userId'];
$eventTocreate['owner']=1;
$eventTocreate['description']='FromDolibarrr!!!';
$eventTocreate['title']='FromDolibarrr!!!';

/*
[calendarInfo] => Array
(
		[entityId] => 0
		[id] => 3
		[userCreateId] => 0
		[manageable] => false
		[readable] => false
		[writable] => false
		[dayEnd] => 18
		[dayStart] => 8
		[default] => false
		[domainId] => 0
		[mail] =>
		[minDuration] => 0
		[picture] => 0
		[read] => false
		[workingDays] => thu,tue,wed,fri,mon
		[write] => false
)

[displayName] => Florian HEnry
[email] => fhenry@localdomain.pro
[id] => 4
[notify] => true
[percent] => 0
[required] => CHAIR
[state] => ACCEPTED
[type] => user
*/

$attendees['calendarInfo']=array('id'=>3);
$attendees['displayName']='Florian HEnry';
$attendees['id']=4;
$attendees['notify']=1;
$attendees['percent']=0;
$attendees['required']='CHAIR';
$attendees['state']='ACCEPTED';
$attendees['type']='user';

$eventTocreate['attendees']=$attendees;


$datatest = array (
'arg0' => $resultuser,
'arg1' => $eventTocreate);

/*
$result = $soapclient->call ( 'createEvent', $datatest, $ns, '' );
if (! $result) {
	print 'ERROROROROROR';
	print $soapclient->error_str;
	print "<br>\n\n";
	print $soapclient->request;
	print "<br>\n\n";
	print $soapclient->response;
	exit ();
} else {

	echo "<h2>Request:</h2>";
	echo '<h4>Function</h4>';
	echo 'createEvent';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

	echo '<hr>';

	echo "<h2>Response:</h2>";
	echo '<h4>Result</h4>';
	echo '<pre>';
	print_r ( $result );
	echo '</pre>';
	echo '<h4>SOAP Message</h4>';
	echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}
*/



/*

$datatest = array (
'arg0' => $resultuser,
'arg1' => '3',//$result['id'],
'arg2' => dol_now(),
'arg3' => dol_now(),
);
$result = $soapclient->call ( 'getListEventsFromIntervalDate', $datatest, $ns, '' );
if (! $result) {
print 'ERROROROROROR';
print $soapclient->error_str;
print "<br>\n\n";
print $soapclient->request;
print "<br>\n\n";
print $soapclient->response;
exit ();
} else {

echo "<h2>Request:</h2>";
echo '<h4>Function</h4>';
echo 'getListEventsFromIntervalDate';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

echo '<hr>';

echo "<h2>Response:</h2>";
echo '<h4>Result</h4>';
echo '<pre>';
print_r ( $result );
echo '</pre>';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}

$datatest = array (
'arg0' => $resultuser);
$result = $soapclient->call ( 'getWaitingEvents', $datatest, $ns, '' );
if (! $result) {
print 'ERROROROROROR';
print $soapclient->error_str;
print "<br>\n\n";
print $soapclient->request;
print "<br>\n\n";
print $soapclient->response;
exit ();
} else {

echo "<h2>Request:</h2>";
echo '<h4>Function</h4>';
echo 'getWaitingEvents';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars ( $soapclient->request, ENT_QUOTES ) . '</pre>';

echo '<hr>';

echo "<h2>Response:</h2>";
echo '<h4>Result</h4>';
echo '<pre>';
print_r ( $result );
echo '</pre>';
echo '<h4>SOAP Message</h4>';
echo '<pre>' . htmlspecialchars ( $soapclient->response, ENT_QUOTES ) . '</pre>';
}

*/
echo '</body>' . "\n";
echo '</html>' . "\n";

?>
