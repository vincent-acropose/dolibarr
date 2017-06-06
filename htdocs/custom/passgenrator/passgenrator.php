<?php

	chdir(__DIR__);

  $Tab1 = file('fruit.txt') ;
  $Tab2 = file('verbe.txt') ;
  $Tab3 = file('animal.txt') ;

  $not = true;
  while($not) {
	
	shuffle($Tab1);
	shuffle($Tab2);
	shuffle($Tab3);

	//$pwd = strtolower(trim($Tab3[0]).trim($Tab2[0]).trim($Tab1[1]));
	$pwd = strtolower(trim($Tab3[0]).rand(10,99).trim($Tab1[1]));

	if(strpos($pwd, ',') === false && strpos($pwd, ' ') === false) $not=false;

 }

  $i = (int)rand(0, strlen($pwd)-1);
  $pwd[$i] = strtoupper($pwd[$i]);

  echo $pwd;

  file_put_contents('monster.txt', $pwd);
