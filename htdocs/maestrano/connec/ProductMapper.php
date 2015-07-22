<?php

/**
* Map Connec Item representation to/from Dolibarr Product
*/
class ProductMapper extends BaseMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Item';
    $this->local_entity_name = 'Product';
    $this->connec_resource_name = 'items';
    $this->connec_resource_endpoint = 'items';
  }

  // Return the Product local id
  protected function getId($product) {
    return $product->id;
  }

  // Return a local Product by id
  protected function loadModelById($local_id) {
    global $db;

    $product = new Product($db);
    $product->fetch($local_id);
    return $product;
  }

  // Map the Connec resource attributes as a Dolibarr Product
  protected function mapConnecResourceToModel($item_hash, $product) {
    // Map Product unique code
    if($this->is_set($item_hash['code'])) { $product->ref = $item_hash['code']; } else { $product->ref = 'IT-' . rand(); }
    if($this->is_set($item_hash['name'])) { $product->libelle = $item_hash['name']; }
    if($this->is_set($item_hash['description'])) { $product->description = $item_hash['description']; }
    
    // Map product type
    if($this->is_set($item_hash['type']) && $item_hash['type'] == 'SERVICE') { $product->type = 1; } else { $product->type = 0; }

    // Set item pricing excluding taxes by default
    if($this->is_new($product)) { $product->price_base_type = 'HT';}

    // Map product price
    if($this->is_set($item_hash['sale_price'])) {
      $product->status = 1;
      if($this->is_set($item_hash['sale_price']['net_amount'])) { $product->price = $item_hash['sale_price']['net_amount']; }
      if($this->is_set($item_hash['sale_price']['total_amount'])) { $product->price_ttc = $item_hash['sale_price']['total_amount']; } else { $product->price_ttc = 0; }
      if($this->is_set($item_hash['sale_price']['tax_rate'])) { $product->tva_tx = $item_hash['sale_price']['tax_rate']; } else { $product->tva_tx = 0; }
    }

    if($this->is_set($item_hash['purchase_price'])) { $product->status_buy = 1; }
    if($this->is_set($item_hash['minimum_quantity'])) { $product->seuil_stock_alerte = $item_hash['minimum_quantity']; }

    // Item origin country
    if($this->is_set($item_hash['country'])) {
      $country_hash = ConnecUtils::findCountry($country);
      if($country_hash) {
        $product->country_id = $country_hash['rowid'];
        $product->country_code = $country_hash['code'];
      }
    }

    // Weight, size, area and volume
    if($this->is_set($item_hash['weight'])) { $product->weight = $item_hash['weight']; }
    if($this->is_set($item_hash['weight_units'])) { $product->weight_units = $item_hash['weight_units']; }
    if($this->is_set($item_hash['length'])) { $product->length = $item_hash['length']; }
    if($this->is_set($item_hash['length_units'])) { $product->length_units = $item_hash['length_units']; }
    if($this->is_set($item_hash['surface'])) { $product->surface = $item_hash['surface']; }
    if($this->is_set($item_hash['surface_units'])) { $product->surface_units = $item_hash['surface_units']; }
    if($this->is_set($item_hash['volume'])) { $product->volume = $item_hash['volume']; }
    if($this->is_set($item_hash['volume_units'])) { $product->volume_units = $item_hash['volume_units']; }
  }

  // Map the Dolibarr Product to a Connec resource hash
  protected function mapModelToConnecResource($product) {
    global $db;
    global $conf;

    $item_hash = array();

    // Map Product code as customer or supplier unique code
    $item_hash['code'] = $product->ref;
    $item_hash['name'] = $product->libelle;
    $item_hash['description'] = $product->description;

    // Map product type
    if($product->type == 0) { $item_hash['type'] = 'PRODUCT'; }
    else if($product->type == 1) { $item_hash['type'] = 'SERVICE'; }

     // Map product price
    if($product->status == 1) {
      $sale_price = array(
        'total_amount' => $product->price_ttc,
        'net_amount' => $product->price,
        'tax_rate' => $product->tva_tx,
        'tax_amount' => $product->price_ttc - $product->price,
        'currency' => $conf->currency
      );
      $item_hash['sale_price'] = $sale_price;
    }

    // Minimum purchase price
    if($product->status_buy == 1) {
      $product_fourn = new ProductFournisseur($db);
      $price_found = $product_fourn->find_min_price_product_fournisseur($product->id);
      if(isset($product_fourn->fourn_unitprice)) {
        $purchase_price = array(
          'net_amount' => $product_fourn->fourn_unitprice,
          'tax_rate' => $product_fourn->fourn_tva_tx,
          'currency' => $conf->currency
        );
        $item_hash['purchase_price'] = $purchase_price;
      }
    }

    // Product stock
    $item_hash['quantity_on_hand'] = $product->stock_reel;
    $item_hash['minimum_quantity'] = $product->seuil_stock_alerte;

    // Product origin country
    if($this->is_set($product->country_id)) {
      $country = ConnecUtils::findCountryById($product->country_id);
      $item_hash['country'] = $country->code;
    }

    // Weight, size, area and volume
    $item_hash['weight'] = $product->weight;
    $item_hash['weight_units'] = $product->weight_units;
    $item_hash['length'] = $product->length;
    $item_hash['length_units'] = $product->length_units;
    $item_hash['surface'] = $product->surface;
    $item_hash['surface_units'] = $product->surface_units;
    $item_hash['volume'] = $product->volume;
    $item_hash['volume_units'] = $product->volume_units;

    return $item_hash;
  }

  // Persist the Dolibarr Product
  protected function persistLocalModel($product, $item_hash) {
    $new_product = $this->is_new($product);

    // Save the Product before updating inventory
    $user = ConnecUtils::defaultUser();
    if($new_product) {
      $product->id = $product->create($user, 0, false);
    } else {
      $product->update($product->id, $user, false, 'update', false);
      $product->updatePrice($product->price, 'HT', $user, $product->tva_tx);
    }

    // Adjust product inventory
    if($this->is_set($item_hash['is_inventoried']) && $item_hash['is_inventoried']) {
      $quantity = 0;
      if(!is_null($item_hash['quantity_on_hand'])) { $quantity = $item_hash['quantity_on_hand']; }
      else if(!is_null($item_hash['initial_quantity'])) { $quantity = $item_hash['initial_quantity']; }
      $warehouse = WarehouseMapper::getDefault();
      
      if($new_product) {
        // For new Products, set initial stock into default Warehouse
        $product->correct_stock($user, $warehouse->id, $quantity, 0, 'Automatic stock adjustment', $product->price, false);
      } else {
        // Existing product, adjust quantity
        $current_quantity = $product->stock_reel;
        $adjustment = $quantity - $current_quantity;
        if($adjustment != 0) { $product->correct_stock($user, $warehouse->id, $adjustment, 0, 'Automatic stock adjustment', $product->price, false); }
      }
    }
  }
}
