<?php

class ConnecUtils {
  public static function findCountry($country_name) {
    global $db;
    
    $result = $db->query("SELECT * from llx_c_country WHERE code = '$country_name' OR code_iso = '$country_name' OR label = '$country_name'");
    if($result->num_rows > 0) { return $result->fetch_assoc(); }
    return null;
  }

  public static function findState($country_id, $state_name) {
    global $db;
    
    $sql = "SELECT dep.*";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_departements as dep";
    $sql.= " JOIN ".MAIN_DB_PREFIX."c_regions as reg ON (reg.code_region = dep.fk_region)";
    $sql.= " WHERE reg.fk_pays = ".$country_id;
    $sql.= " AND (dep.code_departement = '" .$state_name."' OR dep.nom = '" .$state_name."' OR dep.ncc = '" .$state_name."')";
    $result = $db->query($sql);
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