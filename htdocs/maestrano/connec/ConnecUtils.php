<?php

class ConnecUtils {
  public static function findCountry($country_name) {
    global $db;
    
    $result = $db->query("SELECT * from llx_c_country WHERE code = '$country_name' OR code_iso = '$country_name' OR label = '$country_name'");
    if($result->num_rows > 0) { return $result->fetch_assoc(); }
    return null;
  }

  public static function mapCountryToISO3166($country_name) {
    $country_hash = ConnecUtils::findCountry($country_name);
    if(is_null($country_hash)) { return null; }
    return $country_hash['code_iso'];
  }
      
  public static function mapISO3166ToCountry($country_name) {
    $country_hash = ConnecUtils::findCountry($country_name);
    if(is_null($country_hash)) { return null; }
    return $country_hash['label'];
  }
}