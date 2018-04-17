<?php

  require_once('../config.php');
  
  $mysqli = new mysqli('localhost', constant('DB_USERNAME'), constant('DB_PASSWORD'), constant('DB_DATABASE'));
    if ($mysqli->connect_error) {
      die('Connection Error (' . $mysqli->connect_errno . ') '
            . $mysqli->connect_error);
  }

  $query = $mysqli->query("SELECT value FROM ". DB_PREFIX . "setting WHERE code = 'api'");
  while($object = mysqli_fetch_assoc($query)){
    $api = $object['value'];
  }

  $datumvandaag = date('Y-m-d H:i:s');
  $url          = 'https://lev.fertiplant-fulfilment.nl/api/assortment/'.''.$api;
  $json         = file_get_contents($url);
  $obj          = json_decode($json);

  foreach($obj->assortment as $artikel) {

    $id     = $artikel->product_id;
    $query  = $mysqli->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE oudid = '".$id."' LIMIT 0,1");
    while($object = mysqli_fetch_assoc($query)){
      $product_id = $object['product_id']; 
    }

    $mysqli->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '".$product_id."'");
    
    $imageteller = 1;
    foreach($artikel->images as $image) {

      $url = $image->uri;
      copy($url, '../image/data/import/'.$product_id.'-'.$imageteller.'.jpg');
      $mysqli->query("INSERT INTO " . DB_PREFIX . "product_image (product_id, image, sort_order) VALUES ('".$product_id."', 'data/import/".$product_id."-".$imageteller.".jpg', '".$imageteller."')");
      $imageteller++;

    }

    $mysqli->query("UPDATE " . DB_PREFIX . "product SET image='data/import/".$product_id."-1.jpg' WHERE product_id='".$product_id."' WHERE product_id = '".$product_id."'");
  }

?>