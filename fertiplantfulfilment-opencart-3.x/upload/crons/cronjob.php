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

	$datumvandaag 	= date('Y-m-d H:i:s');
	$datevandaag 	= date('Y-m-d');
	$url 			= 'https://lev.fertiplant-fulfilment.nl/api/assortment/'.''.$api;
	$json 			= file_get_contents($url);
	$obj 			= json_decode($json);
	$deleteArray 	= array();
	$counter 		= 0;

	foreach($obj->assortment as $artikel) {
		$id = $artikel->product_id;
		array_push($deleteArray, $id);
		$counter++;
	}


	$selectLangugae = $mysqli->query("SELECT language_id FROM " . DB_PREFIX . "language");
	while($languageObject = mysqli_fetch_assoc($selectLangugae)){
		$language_id = $languageObject['language_id'];
	}

	$deleteQuery = $mysqli->query("SELECT oudid, product_id FROM " . DB_PREFIX . "product");
	while($deleteObject = mysqli_fetch_assoc($deleteQuery)){
		$product_id = $deleteObject['product_id'];
		$oudid 		= $deleteObject['oudid'];

		if($oudid != 0 && $counter != 0){
			if(!in_array($oudid, $deleteArray)){
				$mysqli->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '".$product_id."'");
				$mysqli->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '".$product_id."'");
				$mysqli->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '".$product_id."'");
				$mysqli->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '".$product_id."'");
				$mysqli->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '".$product_id."'");
				$mysqli->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '".$product_id."'");
				$mysqli->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '".$product_id."'");
			}
		}
	}


	$check_extra_info 	= $mysqli->query("SELECT name FROM " . DB_PREFIX . "attribute_group_description WHERE name = 'Extra informatie'");
	$aantal_groups 		= $check_extra_info->num_rows;

	if($aantal_groups == 0){
		$mysqli->query("INSERT INTO " . DB_PREFIX . "attribute_group (sort_order) VALUES ('0');");

		$laatste_att_id = $mysqli->query("SELECT attribute_group_id FROM " . DB_PREFIX . "attribute_group ORDER BY attribute_group_id DESC LIMIT 0,1");
		while($att_object = mysqli_fetch_assoc($laatste_att_id)){
			$attribute_group_id = $att_object['attribute_group_id'];
		}

		$mysqli->query("INSERT INTO " . DB_PREFIX . "attribute_group_description (attribute_group_id, language_id, name) VALUES ('".$attribute_group_id."', '".$language_id."', 'Extra informatie');");
	}

	$attribute_group 	= $mysqli->query("SELECT attribute_group_id FROM " . DB_PREFIX . "attribute_group_description WHERE name = 'Extra informatie' ");
	while($attribute_group_object = mysqli_fetch_assoc($attribute_group)){
		$attribute_group_id = $attribute_group_object['attribute_group_id'];
	}

	$attribute_id_delete = $mysqli->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute WHERE attribute_group_id = '".$attribute_group_id."'");
	while($delete_object = mysqli_fetch_assoc($attribute_id_delete)){
		$attributeId = $delete_object['attribute_id'];
		$mysqli->query("DELETE FROM " . DB_PREFIX . "attribute WHERE attribute_id = '".$attributeId."'");
		$mysqli->query("DELETE FROM " . DB_PREFIX . "attribute_description WHERE attribute_id = '".$attributeId."'");
		$mysqli->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE attribute_id = '".$attributeId."'");
	}

	foreach($obj->assortment as $artikel) {

	  	$id 					= $artikel->product_id;
	  	$product_name 			= addslashes($artikel->product_name);
	  	$product_description 	= addslashes($artikel->product_description);
	  	$quantity 				= $artikel->amount_available;
	  	$price 					= $artikel->price_info->price;
	 	$product_idOphalen 		= $mysqli->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE oudid = '".$id."'");
		$aantal 				= $product_idOphalen->num_rows;

		 if($aantal == 0){
		 	$mysqli->query("INSERT INTO " . DB_PREFIX . "product (model, quantity, stock_status_id, shipping, price, image, tax_class_id, date_available, weight_class_id, length_class_id, subtract, minimum, sort_order, status, date_added, date_modified, oudid) VALUES ('".$product_name."', '".$quantity."', '5', '1', '".$price."', '', '9', '".$datevandaag."', '1', '1', '1', '1', '1', '0', '".$datumvandaag."', '".$datumvandaag."', '".$id."')");

		    $query = $mysqli->query("SELECT product_id FROM " . DB_PREFIX . "product ORDER BY product_id DESC LIMIT 0,1");
		    while($productidObject = mysqli_fetch_assoc($query)){
		   		$product_id = $productidObject['product_id'];
		    }

	        $mysqli->query("INSERT INTO " . DB_PREFIX . "product_option (product_id, option_id, required) VALUES('".$product_id."', '8', '1') ");
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_reward (product_id, customer_group_id, points) VALUES ('".$product_id."', '1', '1')");
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_to_store (product_id, store_id) VALUES ('".$product_id."', '0')");
            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_description (product_id, language_id, name, description, meta_title, meta_description) VALUES ('".$product_id."', '".$language_id."', '".$product_name."', '".$product_description."', '".$product_name."', '".$product_description."')");


            //extra_information
            foreach($artikel->extra_information as $info) {

            	$naam 					= addslashes($info->name);
             	$attribute_description 	= addslashes($info->description);
                
                $checkAttribute 		= $mysqli->query("SELECT name FROM " . DB_PREFIX . "attribute_description WHERE name = '".$naam."'");
                
                $aantalAttributes 		= $checkAttribute->num_rows;
 				
 				$mysqli->query("INSERT INTO " . DB_PREFIX . "attribute (attribute_group_id, sort_order) VALUES('".$attribute_group_id."', '0')");
                
                $laatste_att_id	= $mysqli->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute ORDER BY attribute_id DESC LIMIT 0,1");
                while($att_result = mysqli_fetch_assoc($laatste_att_id)){
               		$attribute_id 		= $att_result['attribute_id'];
                }
          
               	$mysqli->query("INSERT INTO " . DB_PREFIX . "attribute_description (attribute_id, language_id, name) VALUES ('".$attribute_id."', '".$language_id."', '".$naam."');");

              	$query = $mysqli->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute_description WHERE name = '".$naam."'");
              	while($attribute_id_object = mysqli_fetch_assoc($query)){
              		$attribute_id2 = $attribute_id_object['attribute_id'];
              	}
        
              	$mysqli->query("INSERT INTO " . DB_PREFIX . "product_attribute (product_id, attribute_id, language_id, text) VALUES('".$product_id."', '".$attribute_id2."', '".$language_id."', '".$attribute_description."')");

            }
	            
	          //fotos
	        $imageteller = 1;
	        foreach($artikel->images as $image) {

	      		$url = $image->uri;
	            copy($url, '../image/data/import/'.$product_id.'-'.$imageteller.'.jpg');

	            $mysqli->query("INSERT INTO " . DB_PREFIX . "product_image (product_id, image, sort_order) VALUES ('".$product_id."', 'data/import/".$product_id."-".$imageteller.".jpg', '".$imageteller."')");

	            $imageteller++;

	        }

	        $mysqli->query("UPDATE " . DB_PREFIX . "product SET image='data/import/".$product_id."-1.jpg' WHERE product_id='".$product_id."'");

		 }else{

			if($quantity == 0){
				$mysqli->query("UPDATE " . DB_PREFIX . "product SET quantity = '".$quantity."', status = '0' WHERE oudid = '".$id."'");
			}

			$mysqli->query("UPDATE " . DB_PREFIX . "product SET model = '".$product_name."', quantity = '".$quantity."' WHERE oudid = '".$id."'");
			
			$query = $mysqli->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE oudid = '".$id."' LIMIT 0,1");
			while($object = mysqli_fetch_assoc($query)){
				$product_id = $object['product_id'];
			}

			foreach($artikel->extra_information as $info) {

              	$naam 					= addslashes($info->name);
              	$attribute_description 	= addslashes($info->description);

            	$checkAttribute 		= $mysqli->query("SELECT name FROM " . DB_PREFIX . "attribute_description WHERE name = '".$naam."'");
                $aantalAttributes 		= $checkAttribute->num_rows;

                if($aantalAttributes == 0){

                	$mysqli->query("INSERT INTO " . DB_PREFIX . "attribute (attribute_group_id, sort_order) VALUES('".$attribute_group_id."', '0')");

                	$laatste_att_id = $mysqli->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute ORDER BY attribute_id DESC LIMIT 0,1");
                	while($att_result = mysqli_fetch_assoc($laatste_att_id)){
                		$attribute_id = $att_result['attribute_id'];
                  	}
          
                 	$mysqli->query("INSERT INTO " . DB_PREFIX . "attribute_description (attribute_id, language_id, name) VALUES ('".$attribute_id."', '".$language_id."', '".$naam."');");
                }

             	$query = $mysqli->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute_description WHERE name = '".$naam."'");
             	while($attribute_id_object = mysqli_fetch_assoc($query)){
              		$attribute_id2 = $attribute_id_object['attribute_id'];
              	}
        
              	$mysqli->query("INSERT INTO " . DB_PREFIX . "product_attribute (product_id, attribute_id, language_id, text) VALUES('".$product_id."', '".$attribute_id2."', '".$language_id."', '".$attribute_description."')");

            }

			$mysqli->query("UPDATE " . DB_PREFIX . "product_description SET name = '".$product_name."', language_id = '".$language_id."',  description = '".$product_description."', meta_title = '".$product_name."', meta_description = '".$product_description."' WHERE product_id = '".$product_id."'");
		}
	}

?>