<?php

/**
* Map Connec Warehouse representation to/from Dolibarr Entrepot
* TODO: Does not exist in Connec! yet
*/
class WarehouseMapper extends BaseMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Warehouse';
    $this->local_entity_name = 'Entrepot';
    $this->connec_resource_name = 'warehouses';
    $this->connec_resource_endpoint = 'warehouses';
  }

  // Return the Entrepot local id
  protected function getId($warehouse) {
    return $warehouse->id;
  }

  // Return a local Entrepot by id
  protected function loadModelById($local_id) {
    global $db;

    $warehouse = new Entrepot($db);
    $warehouse->fetch($local_id);
    return $warehouse;
  }

  // Map the Connec resource attributes as a Dolibarr Entrepot
  protected function mapConnecResourceToModel($warehouse_hash, $warehouse) {
    // Map Contact unique code
    if($this->is_set($warehouse_hash['code'])) { $warehouse->libelle = $warehouse_hash['code']; }
    if($this->is_set($warehouse_hash['name'])) { $warehouse->lieu = $warehouse_hash['name']; }
    if($this->is_set($warehouse_hash['description'])) { $warehouse->description = $warehouse_hash['description']; }

    // Map Address
    if($this->is_set($warehouse_hash['address'])) {
      $address = $this->is_set($warehouse_hash['address']['shipping']) ? $warehouse_hash['address']['shipping'] : $warehouse_hash['address']['shipping'];
      if($this->is_set($address)) {
        if($this->is_set($address['line1'])) { $warehouse->address = $address['line1']; }
        if($this->is_set($address['city'])) { $warehouse->town = $address['city']; }
        if($this->is_set($address['postal_code'])) { $warehouse->zip = $address['postal_code']; }

        // Map Country
        if(isset($address['country'])) {
          $country_hash = ConnecUtils::findCountry($address['country']);
          if($country_hash) {
            $warehouse->country_id = $country_hash['rowid'];
            $warehouse->country_code = $country_hash['code'];
          }
        }
      }
    }

    // Default country
    if(!$this->is_set($warehouse->country_id)) { $warehouse->country_id = 28; }
    if(!$this->is_set($warehouse->country_code)) { $warehouse->country_code = 'AU'; }

    // Map status
    if($this->is_set($warehouse_hash['status'])) {
      if($warehouse_hash['status'] == 'INACTIVE' || $warehouse_hash['status'] == 'CLOSED') {
        $warehouse->statut = 0;
      } else {
        $warehouse->statut = 1;
      }
    }
  }

  // Map the Dolibarr Entrepot to a Connec resource hash
  protected function mapModelToConnecResource($warehouse) {
    $warehouse_hash = array();

    // Map Organization code as customer or supplier unique code
    if($this->is_set($warehouse->libelle)) { $warehouse_hash['code'] = $warehouse->libelle; }
    if($this->is_set($warehouse->lieu)) { $warehouse_hash['name'] = $warehouse->lieu; }
    if($this->is_set($warehouse->description)) { $warehouse_hash['description'] = $warehouse->description; }

    // Map address
    $address = array();
    $shipping_address = array();
    if($this->is_set($warehouse->address)) { $shipping_address['line1'] = $warehouse->address; }
    if($this->is_set($warehouse->town)) { $shipping_address['city'] = $warehouse->town; }
    if($this->is_set($warehouse->zip)) { $shipping_address['postal_code'] = $warehouse->zip; }
    if($this->is_set($warehouse->country_id)) {
      global $db;
      $country = new Ccountry($db);
      $country->fetch($warehouse->country_id);
      $shipping_address['country'] = $country->code;
    }
    if(!empty($shipping_address)) { $address['shipping'] = $shipping_address; }
    if(!empty($address)) { $warehouse_hash['address'] = $address; }

    // Map status
    if($warehouse->statut == 0) { $warehouse_hash['status'] = 'CLOSED'; }
    if($warehouse->statut == 1) { $warehouse_hash['status'] = 'ACTIVE'; }

    return $warehouse_hash;
  }

  // Persist the Dolibarr Entrepot
  protected function persistLocalModel($warehouse, $resource_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($warehouse)) {
      $warehouse->id = $warehouse->create($user, false);
    } else {
      $warehouse->update($warehouse->id, $user, false);
    }
  }

  // Get the first Warehouse or crrate a new one
  public static function getDefault() {
    global $db;

    $entrepot = new Entrepot($db);
    $warehouses = $entrepot->list_array();
    if(count($warehouses) >= 1) {
      $warehouseMapper = new WarehouseMapper();
      return $warehouseMapper->loadModelById(key($warehouses));
    }

    // Create a default Warehouse
    $user = ConnecUtils::defaultUser();
    $entrepot->libelle = 'Default';
    $entrepot->description = 'Default warehouse for stock inventory';
    $entrepot->statut = 1;
    $entrepot->country_id = ConnecUtils::getCompanyCountryId();
    $entrepot->create($user);
    return $entrepot;
  }
}
