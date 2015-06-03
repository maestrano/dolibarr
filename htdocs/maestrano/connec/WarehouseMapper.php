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
    $this->connec_resource_name = 'warehouse';
    $this->connec_resource_endpoint = 'warehouse';
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
    
  }

  // Map the Dolibarr Entrepot to a Connec resource hash
  protected function mapModelToConnecResource($warehouse) {
    $warehouse_hash = array();

    return $warehouse_hash;
  }

  // Persist the Dolibarr Entrepot
  protected function persistLocalModel($warehouse, $resource_hash) {
    
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
