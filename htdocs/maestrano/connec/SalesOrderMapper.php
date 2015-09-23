<?php

/**
* Map Connec SalesOrder representation to/from Dolibarr Commande
*/
class SalesOrderMapper extends TransactionMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'SalesOrder';
    $this->local_entity_name = 'Commande';
    $this->connec_resource_name = 'sales_orders';
    $this->connec_resource_endpoint = 'sales_orders';
  }

  // Map the Connec resource attributes onto the Dolibarr SalesOrder
  protected function mapConnecResourceToModel($sales_order_hash, $sales_order) {
    parent::mapConnecResourceToModel($sales_order_hash, $sales_order);

    if($this->is_set($sales_order_hash['transaction_number'])) { $sales_order->ref_ext = $sales_order_hash['transaction_number']; }
  }

  // Map the Dolibarr SalesOrder to a Connec resource hash
  protected function mapModelToConnecResource($sales_order) {
    global $adb;

    $sales_order_hash = parent::mapModelToConnecResource($sales_order);

    // Map sales_order status
    $this->mapSalesOrderStatusToConnec($sales_order_hash, $sales_order);

    // Map first Contact
    $contacts = $sales_order->liste_contact();
    if(!empty($contacts)) {
      $contact = $contacts[0];
      $contact_id = $contact['id'];
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($contact_id, 'CONTACTS');
      if($mno_id_map) { $sales_order_hash['person_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map SalesOrder lines
    if(!empty($sales_order->lines)) {
      $sales_order_lines_hashes = array();
      $sales_order_line_mapper = new SalesOrderLineMapper($sales_order, $sales_order_hash);
      foreach($sales_order->lines as $sales_order_line) {
        array_push($sales_order_lines_hashes, $sales_order_line_mapper->mapModelToConnecResource($sales_order_line));
      }
      $sales_order_hash['lines'] = $sales_order_lines_hashes;
    }

    return $sales_order_hash;
  }

  // Persist the Dolibarr SalesOrder
  protected function persistLocalModel($sales_order, $sales_order_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($sales_order)) {
      $sales_order->id = $sales_order->create($user, 0, $sales_order->date_lim_reglement, false);
    } else {
      $sales_order->update($user, 0, false);
    }

    // Persist sales_order lines
    if(!empty($sales_order_hash['lines'])) {
      $processed_lines_local_ids = array();

      foreach($sales_order_hash['lines'] as $sales_order_line_hash) {
        $sales_order_line_mapper = new SalesOrderLineMapper($sales_order, $sales_order_hash);
        $sales_order_line = $sales_order_line_mapper->saveConnecResource($sales_order_line_hash);
        array_push($processed_lines_local_ids, $sales_order_line->rowid);
      }

      // Delete local sales_order lines that have been removed
      $local_sales_order_lines = $sales_order->lines;
      foreach ($local_sales_order_lines as $local_sales_order_line) {
        if(!in_array($local_sales_order_line->rowid, $processed_lines_local_ids)) {
          $local_sales_order_line->delete(false);
          MnoIdMap::hardDeleteMnoIdMap($local_sales_order_line->rowid, 'FACTURELIGNE');
        }
      }
    }

    // Calculate sales_order amount
    $sales_order->update_price(1);

    // Apply sales_order status
    $this->mapSalesOrderStatusToDolibarr($sales_order_hash, $sales_order);
  }

  // Map sales_order status from Connec to Dolibarr
  // Connec status: DRAFT, AUTHORISED, PAID, VOIDED
  private function mapSalesOrderStatusToDolibarr($sales_order_hash, $sales_order) {
    $user = ConnecUtils::defaultUser();
    if(is_null($user->rights)) { $user->rights = (object) array(); }
    if(is_null($user->rights->facture)) { $user->rights->facture = (object) array(); }
    $user->rights->facture->valider = true;

    switch($sales_order_hash['status']) {
      case "SUBMITTED":
        $sales_order->valid($user, 0, 0, false);
        break;
      case "CLOSED":
        $sales_order->cloture($user, false);
        break;
      case "DRAFT":
        $sales_order->set_draft($user, -1, false);
        break;
      case "VOIDED":
        $sales_order->cancel(-1, false);
        break;
    }
  }

  // Map sales_order status from Dolibarr to Connec
  private function mapSalesOrderStatusToConnec(&$sales_order_hash, $sales_order) {
    if($sales_order->statut == -1) {
      $sales_order_hash['status'] = 'VOIDED';
    } else if($sales_order->statut == 0) {
      $sales_order_hash['status'] = 'DRAFT';
    } else if($sales_order->statut == 1) {
      $sales_order_hash['status'] = 'SUBMITTED';
    } else if($sales_order->statut == 2) {
      $sales_order_hash['status'] = 'AUTHORISED';
    } else if($sales_order->statut == 3) {
      $sales_order_hash['status'] = 'CLOSED';
    }
  }
}