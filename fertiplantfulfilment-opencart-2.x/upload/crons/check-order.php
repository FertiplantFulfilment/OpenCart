<?php 

require_once('../config.php');
$mysqli = new mysqli('localhost', constant('DB_USERNAME'), constant('DB_PASSWORD'), constant('DB_DATABASE'));
if ($mysqli->connect_error) {
    die('Connection Error (' . $mysqli->connect_errno . ') '
            . $mysqli->connect_error);
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$query = $mysqli->query("SELECT value FROM " . DB_PREFIX . "setting WHERE code = 'api'");
while($object = mysqli_fetch_assoc($query)){
	$api = $object['value'];
}


$getstatus = $mysqli->query("SELECT order_status_id FROM " .DB_PREFIX. "order_status WHERE name = 'Processing' ORDER BY order_status_id ASC LIMIT 0,1");
while($statusobject = mysqli_fetch_assoc($getstatus)){
	$order_status_id = $statusobject['order_status_id'];
}

$order = $mysqli->query("SELECT * FROM " .DB_PREFIX. "order WHERE order_status_id = '".$order_status_id."' AND share = 0");
$aantalorders = $order->num_rows;
while($orderobject = mysqli_fetch_assoc($order)){
	
	$order_id = $orderobject['order_id'];
	$voornaam = $orderobject['shipping_firstname'];
	$achternaam = $orderobject['shipping_lastname'];
	$city = $orderobject['shipping_city'];
	$datum = $orderobject['shipping_address_1'];
	$postcode = $orderobject['shipping_postcode'];

	// 		echo "orderid = ";
	// echo $order_id;

	$deliverdate = $mysqli->query("SELECT value FROM " .DB_PREFIX. "order_option WHERE order_id = '".$order_id."' ORDER BY order_option_id ASC LIMIT 0,1");
	while($deliveryobject = mysqli_fetch_assoc($deliverdate)){
		$delivery_date_value = $deliveryobject['value'];
	}

	$eersteArray['orderlines'] = array();
	$arrayteller = 0;

	$orderproducten = $mysqli->query("SELECT product_id, quantity FROM " .DB_PREFIX. "order_product WHERE order_id = '".$order_id."'");
	while($productidobject = mysqli_fetch_assoc($orderproducten)){
		$product_id = $productidobject['product_id'];
		$quantity = $productidobject['quantity'];


	$checkproduct = $mysqli->query("SELECT oudid FROM " .DB_PREFIX. "product WHERE product_id = '".$product_id."'");
	// echo "SELECT oudid FROM " .DB_PREFIX. "product WHERE product_id = '".$product_id."'";
	// echo "<br />";
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
	

	$eersteArray['orderlines'] = array_merge($producten);



 		$input_string = $datum;
            $address = "";
            $houseNumber = "";
            $matches = array();
            if(preg_match('/(?P<address>[^\d]+) (?P<number>\d+.?)/', $input_string, $matches)){
                $address = $matches['address'];
                $houseNumber = $matches['number'];
            } else { // no number found, it is only address
                $address = $input_string;
            }

          $eersteArray['orderlines'] = array_merge($producten);

          $deliver_date = date('d-m-Y', strtotime($delivery_date_value));

          $delivery_array["delivery"] = array(
            'name'            => $voornaam." ".$achternaam,
            'street'          => $address,
            'housenumber'         => $houseNumber,
            'housenumber_additional'  => 'int',
            'zipcode'           => $postcode,
            'city'            => $city,
            'delivery_date'       => $deliver_date,
            'delivery_note'       => 'string',
          );

          $kaartje["card"] = array(
	            'id'    => '1',
	            'message' => 'string',
	          );

        $samenvoegen = array_merge($eersteArray, $delivery_array, $kaartje);
		$post_data = json_encode($samenvoegen);


		  print_r($post_data);

          $url = 'https://lev.fertiplant-fulfilment.nl/api/order/'.''.$api.'/true';

          $curl = curl_init($url);
          curl_setopt($curl, CURLOPT_HEADER, false);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          //curl_setopt($curl, CURLOPT_HTTPHEADER,
              //array("Content-type: application/json"));
          curl_setopt($curl, CURLOPT_POST, true);
          curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

          $json_response = curl_exec($curl);
          print_r($json_response);

          
          curl_close($curl);


          $mysqli->query("UPDATE " .DB_PREFIX. "order SET share = '1' WHERE order_id = '".$order_id."'");


}










?>