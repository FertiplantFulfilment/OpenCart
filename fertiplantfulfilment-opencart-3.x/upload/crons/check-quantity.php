<?php

	require_once('config.php');
	
	$mysqli = new mysqli('localhost', constant('DB_USERNAME'), constant('DB_PASSWORD'), constant('DB_DATABASE'));
	if ($mysqli->connect_error) {
    	die('Connection Error (' . $mysqli->connect_errno . ') '
            . $mysqli->connect_error);
	}


	$query = $mysqli->query("SELECT value FROM ". DB_PREFIX ."setting WHERE code = 'api'");
	while($object = mysqli_fetch_assoc($query)){
		$api = $object['value'];
	}


	$url	= 'https://lev.fertiplant-fulfilment.nl/api/assortment/'.''.$api;
	$json 	= file_get_contents($url);
	$obj 	= json_decode($json);


	foreach($obj->assortment as $artikel) {

		$id 		= $artikel->product_id;
		$quantity 	= $artikel->amount_available;

		$mysqli->query("UPDATE " . DB_PREFIX . "product SET quantity = '".$quantity."' WHERE oudid = '".$id."'");
	}


?>