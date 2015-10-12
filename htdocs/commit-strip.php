<?php
	
	$data = file_get_content('http://www.commitstrip.com/fr/feed/');

	$xml = DOMDocument::loadXML($data);

var_dump($xml);
