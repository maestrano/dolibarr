<?php

class ConnecUtils {
  // Select the first available User
  public static function defaultUser() {
    global $db;
    
    $result = $db->query("SELECT min(rowid) as user_id from ".MAIN_DB_PREFIX."user WHERE statut = 1 AND admin = 1");
    if($result->num_rows > 0) {
      $user_hash = $result->fetch_assoc();
      $user = new User($db);
      $user->fetch($user_hash['user_id']);
      return $user;
    }
    return null;
  }

  public static function findCountry($country_name) {
    global $db;
    
    $result = $db->query("SELECT * from ".MAIN_DB_PREFIX."c_country WHERE code = '$country_name' OR code_iso = '$country_name' OR label = '$country_name'");
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

  public static function findStateById($state_id) {
    global $db;
    
    $sql = "SELECT * FROM ".MAIN_DB_PREFIX . "c_departements WHERE rowid = $state_id";
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