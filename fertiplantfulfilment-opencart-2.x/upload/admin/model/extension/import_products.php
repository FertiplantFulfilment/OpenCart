<?php 
class ModelExtensionImportProducts extends Model {

    public function edit_head($code, $data, $store_id = 0)
    {

      ini_set('display_errors', 1);
      ini_set('display_startup_errors', 1);
      error_reporting(E_ALL);

       
        $query = $this->db->query("DESC ".DB_PREFIX."product oudid");
        if (!$query->num_rows) { 
          $this->db->query("ALTER TABLE `" . DB_PREFIX . "product` ADD `oudid` int(11) default '0'");
        }

        $query = $this->db->query("DESC ".DB_PREFIX."order share");
        if (!$query->num_rows) { 
          $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `share` int(11) default '0'");
        }

         $test = json_encode($data);
         $obj = json_decode($test);
         $api = $obj->api;
        
         $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");
         foreach ($data as $key => $value) {
           if (substr($key, 0, strlen($code)) == $code) {
              $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
            }
        }

         $datumvandaag = date('Y-m-d H:i:s');
         $datevandaag = date('Y-m-d');

         $url = 'https://lev.fertiplant-fulfilment.nl/api/assortment/'.''.$api;
         $json = file_get_contents($url);
         $obj = json_decode($json);

         $languageQuery = $this->db->query("SELECT language_id FROM " . DB_PREFIX . "language");
         foreach ($languageQuery->rows as $language) {
              $language_id = $language['language_id'];
            }


        $check_extra_info = $this->db->query("SELECT name FROM " . DB_PREFIX . "attribute_group_description WHERE name = 'Extra informatie'");
        $aantal_groups = $check_extra_info->num_rows;

        if($aantal_groups == 0){

          $this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group (sort_order) VALUES ('0');");

          $laatste_att_id = $this->db->query("SELECT attribute_group_id FROM " . DB_PREFIX . "attribute_group ORDER BY attribute_group_id DESC LIMIT 0,1");
          foreach ($laatste_att_id->rows as $att_result) {
              $attribute_group_id = $att_result['attribute_group_id'];
          }

          $this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group_description (attribute_group_id, language_id, name) VALUES ('".$attribute_group_id."', '".$language_id."', 'Extra informatie');");

       }

         foreach($obj->assortment as $artikel) {

          $id = $artikel->product_id;
          $product_name = addslashes($artikel->product_name);
          $product_description = addslashes($artikel->product_description);
          $quantity = $artikel->amount_available;
          $price = $artikel->price_info->price;

          $aantal_products_query = $this->db->query("SELECT oudid FROM " . DB_PREFIX . "product WHERE oudid = '".$id."'");
          $aantal_products = $aantal_products_query->num_rows;

          if($aantal_products == 0){

          $this->db->query("INSERT INTO " . DB_PREFIX . "product (model, quantity, stock_status_id, shipping, price, image, tax_class_id, date_available, weight_class_id, length_class_id, subtract, minimum, sort_order, status, date_added, date_modified, oudid) VALUES ('".$product_name."', '".$quantity."', '5', '1', '".$price."', '', '9', '".$datevandaag."', '1', '1', '1', '1', '1', '0', '".$datumvandaag."', '".$datumvandaag."', '".$id."')");



          $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product ORDER BY product_id DESC LIMIT 0,1");
            foreach ($query->rows as $result) {
              $product_id = $result['product_id'];
            }
           

            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description (product_id, language_id, name, description, meta_title, meta_description) VALUES ('".$product_id."', '".$language_id."', '".$product_name."', '".$product_description."', '".$product_name."', '".$product_description."')");
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_reward (product_id, customer_group_id, points) VALUES ('".$product_id."', '1', '1')");
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store (product_id, store_id) VALUES ('".$product_id."', '0')");
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option (product_id, option_id, required) VALUES('".$product_id."', '8', '1') ");


            //extra_information
            foreach($artikel->extra_information as $info) {

              $naam = addslashes($info->name);
              $attribute_description = addslashes($info->description);

                $checkAttribute = $this->db->query("SELECT name FROM " . DB_PREFIX . "attribute_description WHERE name = '".$naam."'");
                $aantalAttributes = $checkAttribute->num_rows;

                if($aantalAttributes == 0){

                  $this->db->query("INSERT INTO " . DB_PREFIX . "attribute (attribute_group_id, sort_order) VALUES('".$attribute_group_id."', '0')");

                  $laatste_att_id = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute ORDER BY attribute_id DESC LIMIT 0,1");
                  foreach ($laatste_att_id->rows as $att_result) {
                      $attribute_id = $att_result['attribute_id'];
                  }

                  $this->db->query("INSERT INTO " . DB_PREFIX . "attribute_description (attribute_id, language_id, name) VALUES ('".$attribute_id."', '".$language_id."', '".$naam."');");

                }

              $query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute_description WHERE name = '".$naam."'");
              foreach ($query->rows as $result) {
                $attribute_id2 = $result['attribute_id'];
              }

              $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute (product_id, attribute_id, language_id, text) VALUES('".$product_id."', '".$attribute_id2."', '".$language_id."', '".$attribute_description."')");

            }

          //fotos
          $imageteller = 1;
          foreach($artikel->images as $image) {

            $url = $image->uri;

            copy($url, '../image/data/import/'.$product_id.'-'.$imageteller.'.jpg');

            $this->db->query("INSERT INTO " . DB_PREFIX . "product_image (product_id, image, sort_order) VALUES ('".$product_id."', 'data/import/".$product_id."-".$imageteller.".jpg', '".$imageteller."')");


            $imageteller++;

          }

          $this->db->query("UPDATE " . DB_PREFIX . "product SET image='data/import/".$product_id."-1.jpg' WHERE product_id='".$product_id."'");

      }
    }
  }
}


?>