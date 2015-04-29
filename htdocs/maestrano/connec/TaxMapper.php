<?php

/**
* Map Connec Tax Tax representation to/from Dolibarr Tax
*/
class TaxMapper extends BaseMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'TaxCode';
    $this->local_entity_name = 'TaxCode';
    $this->connec_resource_name = 'tax_codes';
    $this->connec_resource_endpoint = 'tax_codes';
  }

  // Return the Tax local id
  protected function getId($tax) {
    return $tax->rowid;
  }

  // Return a local Tax by id
  protected function loadModelById($local_id) {
    return TaxMapper::getTaxById($local_id);
  }

  // Match tax by name otherwise return a generic object as no Tax model exists in Dolibarr
  protected function matchLocalModel($resource_hash) {
    $tax = TaxMapper::getTaxByName($resource_hash['name']);
    if(is_null($tax)) { $tax = (object) array(); }
    return $tax;
  }

  // Map the Connec resource attributes onto the Dolibarr Tax
  protected function mapConnecResourceToModel($tax_hash, $tax) {
    // Default tax country to company country
    if(!$this->is_set($tax->fk_pays)) { $tax->fk_pays = ConnecUtils::getCompanyCountryId(); }

    // Map tax rate and name
    if($this->is_set($tax_hash['name'])) { $tax->note = $tax_hash['name']; }
    if($this->is_set($tax_hash['sale_tax_rate'])) { $tax->taux = $tax_hash['sale_tax_rate']; }
  }

  // Map the Dolibarr Tax to a Connec resource hash
  protected function mapModelToConnecResource($tax) {
    $tax_hash = array();
    if($this->is_set($tax->note)) { $tax_hash['name'] = $tax->note; }
    if($this->is_set($tax->taux)) { $tax_hash['sale_tax_rate'] = $tax->taux; }
    return $tax_hash;
  }

  // Persist the Dolibarr Tax
  protected function persistLocalModel($tax, $resource_hash) {
    if(!$this->is_set($tax->rowid)) {
      $updated_tax = TaxMapper::insertTax($tax);
      $tax->rowid = $updated_tax->rowid;
    } else {
      TaxMapper::updateTax($tax);
    }
  }

  public static function getTaxById($tax_id) {
    global $db;

    $result = $db->query("SELECT * from ".MAIN_DB_PREFIX."c_tva WHERE rowid = $tax_id LIMIT 1");
    if($result->num_rows > 0) { return (object) $result->fetch_assoc(); }
    return null;
  }

  public static function getTaxByName($tax_name) {
    global $db;

    $result = $db->query("SELECT * from ".MAIN_DB_PREFIX."c_tva WHERE note = $tax_name AND active = 1 ORDER BY rowid DESC LIMIT 1");
    if($result->num_rows > 0) { return (object) $result->fetch_assoc(); }
    return null;
  }

  private static function insertTax($tax) {
    global $db;
    
    // Insert tax
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_tva (taux, note, fk_pays, active, recuperableonly, localtax1, localtax1_type, localtax2, localtax2_type)";
    $sql.= " VALUES (".$tax->taux.", '".$tax->note."', ".$tax->fk_pays.", 1, 0, 0, 0, 0, 0)";
    $result = $db->query($sql);

    // Fetch the last created tax to return its ID
    $result = $db->query("SELECT * from ".MAIN_DB_PREFIX."c_tva ORDER BY rowid DESC LIMIT 1");
    return (object) $result->fetch_assoc();
  }

  private static function updateTax($tax) {
    global $db;

    // Update tax
    $sql = "UPDATE ".MAIN_DB_PREFIX."c_tva";
    $sql.= " SET taux=".$tax->taux.", note='".$tax->note."', fk_pays=".$tax->fk_pays;
    $sql.= " WHERE rowid=".$tax->rowid;
    $result = $db->query($sql);
    return $tax;
  }
}