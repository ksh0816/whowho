<?php

	$path = $_SERVER['DOCUMENT_ROOT']."/sms-20210901181130.xml";
	$xml = simplexml_load_file($path);
	
	//echo $xml->smses[0]['count'];
	//echo "<br>";
	/*
	echo $xml->sms[0]['address'];
	echo $xml->sms[0]['body'];
	echo "<br>";
	echo $xml->sms[1]['address'];
	echo $xml->sms[1]['body'];	
	*/
	
	foreach($xml->sms as $sms){
		echo $sms['readable_date'];
		echo ' | ';
		echo $sms['address'];	
		echo ' | ';
		echo $sms['body'];	
		echo '<br>';
	}
	
	exit;

?>
