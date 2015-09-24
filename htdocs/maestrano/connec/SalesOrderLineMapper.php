<?php

/**
* Map Connec SalesOrder Line representation to/from Dolibarr OrderLine
*/
class SalesOrderLineMapper extends BaseMapper {
  private $sales_order = null;
  private $sales_order_hash = null;

  public function __construct($sales_order=null, $sales_order_hash=null) {
    parent::__construct();

    $this->connec_entity_name = 'SalesOrderLine';
    $this->local_entity_name = 'OrderLine';
    $this->connec_resource_name = 'sales_orders/lines';
    $this->connec_resource_endpoint = 'sales_orders/lines';

    $this->sales_order = $sales_order;
    $this->sales_order_hash = $sales_order_hash;
  }

  // Invoice Line ID
  protected function getId($sales_order_line) {
    return $sales_order_line->rowid;
  }

  // Prefix the Invoice Line ID with the Invoice ID to ensure unicity
  protected function getConnecResourceId($sales_order_line_hash) {
    return $this->sales_order_hash['id'] . "#" . $sales_order_line_hash['id'];
  }

  // Return a local OrderLine by id
  protected function loadModelById($local_id) {
    global $db;

    $sales_order_line = new $this->local_entity_name($db);
    $sales_order_line->fetch($local_id);
    return $sales_order_line;
  }

  // Load by Invoice and Line number
  protected function matchLocalModel($sales_order_line_hash) {
    foreach($this->sales_order->lines as $sales_order_line) {
      if(intval($sales_order_line->rang) == intval($sales_order_line_hash['line_number'])) { return $sales_order_line; }
    }
    return null;
  }

  // Map the Connec resource attributes onto the Dolibarr OrderLine
  protected function mapConnecResourceToModel($sales_order_line_hash, $sales_order_line) {
    // Line attributes
    $sales_order_line->fk_commande = $this->sales_order->id;
    $sales_order_line->rang = $sales_order_line_hash['line_number'];
    $sales_order_line->desc = $sales_order_line_hash['description'];
    $sales_order_line->tva_tx = $sales_order_line_hash['total_price']['tax_rate'];
    if($this->is_set($sales_order_line_hash['quantity'])) { $sales_order_line->qty = $sales_order_line_hash['quantity']; }
    
    // Line amounts
    $sales_order_line->total_ht = $sales_order_line_hash['total_price']['net_amount'] ? $sales_order_line_hash['total_price']['net_amount'] : 0;
    $sales_order_line->total_tva = $sales_order_line_hash['total_price']['tax_amount'] ? $sales_order_line_hash['total_price']['tax_amount'] : 0;
    $sales_order_line->total_ttc = $sales_order_line_hash['total_price']['total_amount'] ? $sales_order_line_hash['total_price']['total_amount'] : 0;
    $sales_order_line->remise_percent = $sales_order_line_hash['reduction_percent'];
    $sales_order_line->subprice = $sales_order_line_hash['unit_price']['net_amount'];
    $sales_order_line->price = $sales_order_line_hash['unit_price']['net_amount'];

    // Map item
    if(!empty($sales_order_line_hash['item_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($sales_order_line_hash['item_id'], 'ITEM');
      $sales_order_line->fk_product = $mno_id_map['app_entity_id'];
    }
  }

  // Map the Dolibarr Invoice to a Connec resource hash
  protected function mapModelToConnecResource($sales_order_line) {
    $sales_order_line_hash = array();

    $productid = intval($sales_order_line->fk_product);
    $line_number = intval($sales_order_line->rang);
    $quantity = floatval($sales_order_line->qty);
    $unit_price = floatval($sales_order_line->subprice);
    $tax_rate = floatval($sales_order_line->tva_tx);
    $total_amount = floatval($sales_order_line->total_ht);
    $total_tax_amount = floatval($sales_order_line->total_tva);
    $total_net_amount = floatval($sales_order_line->total_ttc);
    $reduction_percent = floatval($sales_order_line->remise_percent);
    $description = $sales_order_line->desc;

    // Map Invoice Line ID
    $sales_order_line_local_id = $sales_order_line->rowid;
    $sales_order_line_mno_id = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($sales_order_line_local_id, $this->local_entity_name);
    if($sales_order_line_mno_id) {
      $sales_order_line_id_parts = explode("#", $sales_order_line_mno_id['mno_entity_guid']);
      $sales_order_line_hash['id'] = $sales_order_line_id_parts[1];
    }

    $sales_order_line_hash['status'] = 'ACTIVE';
    $sales_order_line_hash['line_number'] = $line_number;
    $sales_order_line_hash['description'] = $description;
    $sales_order_line_hash['quantity'] = $quantity;
    $sales_order_line_hash['reduction_percent'] = $reduction_percent;
    $sales_order_line_hash['unit_price'] = array('net_amount' => $unit_price, 'tax_rate' => $tax_rate);
    $sales_order_line_hash['total_price'] = array('total_amount' => $total_amount, 'tax_amount' => $total_tax_amount, 'net_amount' => $total_net_amount, 'tax_rate' => $tax_rate);

    // Map tax code by tax rate (best match)
    if($tax_rate > 0) {
      $country_taxes = ConnecUtils::fetchTaxes();
      if($country_taxes) {
        foreach ($country_taxes as $country_tax) {
          if($country_tax['taux'] == $tax_rate) {
            $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($country_tax['rowid'], 'TAXCODE');
            if($mno_id_map) { $sales_order_line_hash['tax_code_id'] = $mno_id_map['mno_entity_guid']; }
            break;
          }
        }
      }
    }

    // Map item id
    $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($productid, 'PRODUCT');
    if($mno_id_map) { $sales_order_line_hash['item_id'] = $mno_id_map['mno_entity_guid']; }

    return $sales_order_line_hash;
  }

  // Persist the Dolibarr SalesOrderLine
  protected function persistLocalModel($sales_order_line, $sales_order_line_hash) {
    if(!$this->is_set($sales_order_line->rowid)) {
      $sales_order_line->rowid = $sales_order_line->insert(0, false);
    } else {
      $user = ConnecUtils::defaultUser();
      $sales_order_line->update($user, 0, false);
    }
  }
}