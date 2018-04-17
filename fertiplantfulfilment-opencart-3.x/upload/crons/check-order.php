<?php 

  require_once('../config.php');
  $mysqli = new mysqli('localhost', constant('DB_USERNAME'), constant('DB_PASSWORD'), constant('DB_DATABASE'));
  if ($mysqli->connect_error) {
      die('Connection Error (' . $mysqli->connect_errno . ') '
              . $mysqli->connect_error);
  }

  $query = $mysqli->query("SELECT value FROM " . DB_PREFIX . "setting WHERE code = 'api'");
  while($object = mysqli_fetch_assoc($query)){
    $api = $object['value'];
  }



  $order = $mysqli->query("SELECT * FROM " .DB_PREFIX. "order WHERE (order_status_id = '2' OR order_status_id = '5') AND share = '0'");
  $aantalorders = $order->num_rows;
  while($orderobject = mysqli_fetch_assoc($order)){
    
    $order_id       = $orderobject['order_id'];
    $voornaam       = $orderobject['shipping_firstname'];
    $achternaam     = $orderobject['shipping_lastname'];
    $city           = $orderobject['shipping_city'];
    $datum          = $orderobject['shipping_address_1'];
    $postcode       = $orderobject['shipping_postcode'];

    $deliverdate = $mysqli->query("SELECT value FROM " .DB_PREFIX. "order_option WHERE order_id = '".$order_id."' ORDER BY order_option_id ASC LIMIT 0,1");
    while($deliveryobject = mysqli_fetch_assoc($deliverdate)){
      $delivery_date_value = $deliveryobject['value'];
    }

    $eersteArray['orderlines']  = array();
    $arrayteller                = 0;

    $orderproducten = $mysqli->query("SELECT product_id, quantity FROM " .DB_PREFIX. "order_product WHERE order_id = '".$order_id."'");
    while($productidobject = mysqli_fetch_assoc($orderproducten)){
      $product_id   = $productidobject['product_id'];
      $quantity     = $productidobject['quantity'];


      $checkproduct = $mysqli->query("SELECT oudid FROM " .DB_PREFIX. "product WHERE product_id = '".$product_id."'");
      $checkaantal = $checkproduct->num_rows;
      while($oudidobject = mysqli_fetch_assoc($checkproduct)){
        $oudid = $oudidobject['oudid'];
      }

      if($oudid != 0){
        $producten[$arrayteller] = array(
          'productcode'   => $oudid,
          'amount'    => $quantity,
      );
                   
      $arrayteller++;
    }
  }
  

  if (!empty($producten)) {
    
    $eersteArray['orderlines']  = array_merge($producten);
    $input_string               = $datum;
    $address                    = "";
    $houseNumber                = "";
    $matches                    = array();
    
    if(preg_match('/(?P<address>[^\d]+) (?P<number>\d+.?)/', $input_string, $matches)){
      $address                  = $matches['address'];
      $houseNumber              = $matches['number'];
    } else { 
      $address                  = $input_string;
    }

    $eersteArray['orderlines'] = array_merge($producten);

    $deliver_date = date('d-m-Y', strtotime($delivery_date_value));

    $delivery_array["delivery"] = array(
      'name'            => $voornaam." ".$achternaam,
      'street'          => $address,
      'housenumber'     => $houseNumber,
      'housenumber_additional'  => 'int',
      'zipcode'         => $postcode,
      'city'            => $city,
      'delivery_date'   => $deliver_date,
      'delivery_note'   => '',
    );

    $kaartje["card"] = array(
        'id'      => '1',
        'message' => 'string',
      );

    $samenvoegen  = array_merge($eersteArray, $delivery_array, $kaartje);
    $post_data    = json_encode($samenvoegen);
    $url          = 'https://lev.fertiplant-fulfilment.nl/api/order/'.''.$api.'/false';

    $curl         = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

    $json_response = curl_exec($curl);
    $json_response = json_decode($json_response);
    curl_close($curl);

    if(!empty($json_response)){
      if($json_response->status == "success"){
        $mysqli->query("UPDATE " .DB_PREFIX. "order SET share = '1' WHERE order_id = '".$order_id."'");
      }
    }
  }



?>