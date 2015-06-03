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
    if($this->is_new($person)) { $product->price_base_type = 'HT';}

    // Map product price
    if($this->is_set($item_hash['sale_price'])) {
      $product->status = 1;
      if($this->is_set($item_hash['sale_price']['net_amount'])) { $product->price = $item_hash['sale_price']['net_amount']; }
      if($this->is_set($item_hash['sale_price']['total_amount'])) { $product->price_ttc = $item_hash['sale_price']['total_amount']; } else { $product->price_ttc = 0; }
      if($this->is_set($item_hash['sale_price']['tax_rate'])) { $product->tva_tx = $item_hash['sale_price']['tax_rate']; } else { $product->tva_tx = 0; }
    }

    if($this->is_set($item_hash['purchase_price'])) { $product->status_buy = 1; }
  }

  // Map the Dolibarr Product to a Connec resource hash
  protected function mapModelToConnecResource($product) {
    global $db;
    global $conf;

    $item_hash = array();

    // Map Product code as customer or supplier unique code
    if($this->is_set($product->ref)) { $item_hash['code'] = $product->ref; }
    if($this->is_set($product->libelle)) { $item_hash['name'] = $product->libelle; }
    if($this->is_set($product->description)) { $item_hash['description'] = $product->description; }

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
    $product_hash['quantity_on_hand'] = $product->stock_reel;

    return $item_hash;
  }

  // Persist the Dolibarr Product
  protected function persistLocalModel($product, $product_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($product)) {
      $product->id = $product->create($user, 0, false);

      // Set initial stock into default Warehouse
      if($this->is_set($product_hash['is_inventoried']) && $product_hash['is_inventoried']) {
        $quantity = is_null($product_hash['initial_quantity']) ? $product_hash['quantity_on_hand'] : $product_hash['initial_quantity'];
        $warehouse = WarehouseMapper::getDefault();
        $product->correct_stock($user, $warehouse->id, $quantity, 0);
      }
    } else {
      $product->update($product->id, $user, false, 'update', false);
    }
  }
}
