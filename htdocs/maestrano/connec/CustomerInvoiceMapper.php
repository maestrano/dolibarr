<?php

/**
* Map Connec Customer Invoice representation to/from Dolibarr Facture
*/
class CustomerInvoiceMapper extends TransactionMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Invoice';
    $this->local_entity_name = 'Facture';
    $this->connec_resource_name = 'invoices';
    $this->connec_resource_endpoint = 'invoices';
  }

  protected function validate($invoice_hash) {
    // Process only Customer Invoices
    return $invoice_hash['type'] == 'CUSTOMER' && $this->is_set($invoice_hash['organization_id']);
  }

  // Map the Connec resource attributes onto the Dolibarr Invoice
  protected function mapConnecResourceToModel($invoice_hash, $invoice) {
    parent::mapConnecResourceToModel($invoice_hash, $invoice);

    // Map invoice type
    $this->mapInvoiceTypeToDolibarr($invoice_hash, $invoice);

    if($this->is_set($invoice_hash['transaction_number'])) { $invoice->ref_int = $invoice_hash['transaction_number']; }
    if($this->is_set($invoice_hash['title'])) { $invoice->ref_ext = $invoice_hash['title']; }
  }

  // Map the Dolibarr Invoice to a Connec resource hash
  protected function mapModelToConnecResource($invoice) {
    global $adb;

    $invoice_hash = parent::mapModelToConnecResource($invoice);

    // Default invoice type to CUSTOMER
    $invoice_hash['type'] = 'CUSTOMER';

    // Map attributes
    if($this->is_set($invoice->ref_int)) { $invoice_hash['transaction_number'] = $invoice->ref_int; }
    if($this->is_set($invoice->ref_ext)) { $invoice_hash['title'] = $invoice->ref_ext; }

    // Map invoice status
    $this->mapInvoiceStatusToConnec($invoice_hash, $invoice);

    // Map sales order
    $invoice->fetchObjectLinked();
    if (count($invoice->linkedObjectsIds['commande']) > 0) {
      $sales_order_id = $invoice->linkedObjectsIds['commande'][0];
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($sales_order_id, 'COMMANDE');
      if($mno_id_map) { $invoice_hash['sales_order_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map Contact
    $customer_ids = $invoice->getIdBillingContact();
    if(empty($customer_ids)) { $customer_ids = $invoice->getIdShippingContact(); }
    if(!empty($customer_ids)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($customer_ids[0], 'CONTACT');
      if($mno_id_map) { $invoice_hash['person_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map Invoice lines
    if(!empty($invoice->lines)) {
      $invoice_lines_hashes = array();
      $invoice_line_mapper = new CustomerInvoiceLineMapper($invoice, $invoice_hash);
      foreach($invoice->lines as $invoice_line) {
        array_push($invoice_lines_hashes, $invoice_line_mapper->mapModelToConnecResource($invoice_line));
      }
      $invoice_hash['lines'] = $invoice_lines_hashes;
    }

    return $invoice_hash;
  }

  // Persist the Dolibarr Invoice
  protected function persistLocalModel($invoice, $invoice_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($invoice)) {
      $invoice->id = $invoice->create($user, 0, $invoice->date_lim_reglement, false);
    } else {
      $invoice->update($user, 0, false);
    }

    // Persist invoice lines
    if(!empty($invoice_hash['lines'])) {
      $processed_lines_local_ids = array();

      foreach($invoice_hash['lines'] as $invoice_line_hash) {
        $invoice_line_mapper = new CustomerInvoiceLineMapper($invoice, $invoice_hash);
        $invoice_line = $invoice_line_mapper->saveConnecResource($invoice_line_hash);
        array_push($processed_lines_local_ids, $invoice_line->rowid);
      }

      // Delete local invoice lines that have been removed
      $local_invoice_lines = $invoice->lines;
      foreach ($local_invoice_lines as $local_invoice_line) {
        if(!in_array($local_invoice_line->rowid, $processed_lines_local_ids)) {
          $local_invoice_line->delete(false);
          MnoIdMap::hardDeleteMnoIdMap($local_invoice_line->rowid, 'FACTURELIGNE');
        }
      }
    }

    // Calculate invoice amount
    $invoice->update_price(1);

    // Apply invoice status
    $this->mapInvoiceStatusToDolibarr($invoice_hash, $invoice);

    // Map Contact
    if(array_key_exists('person_id', $invoice_hash)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($invoice_hash['person_id'], 'PERSON', 'CONTACT');
      $customer_id = $invoice->getIdcontact('external', 'BILLING');
      if($mno_id_map && !in_array($mno_id_map['app_entity_id'], $customer_id)) { $invoice->add_contact($mno_id_map['app_entity_id'], 'BILLING', 'external'); }
    }

    // Map sales order reference
    if(array_key_exists('sales_order_id', $invoice_hash)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($invoice_hash['sales_order_id'], 'SALESORDER', 'COMMANDE');
      if($mno_id_map && !is_null($mno_id_map)) {
        // Find if object is already linked
        $invoice->fetchObjectLinked();
        if (count($invoice->linkedObjectsIds['commande']) == 0) {
          // Add object link
          $invoice->add_object_linked('commande', $mno_id_map['app_entity_id']);
        }
      }
    }
  }

  // Set default invoice type to TYPE_STANDARD
  private function mapInvoiceTypeToDolibarr(&$invoice_hash, $invoice) {
    if($this->is_new($invoice)) { $invoice->type = Facture::TYPE_STANDARD; }
  }

  // Map invoice status from Connec to Dolibarr
  // Connec status: DRAFT, AUTHORISED, PAID, VOIDED
  private function mapInvoiceStatusToDolibarr($invoice_hash, $invoice) {
    $user = ConnecUtils::defaultUser();
    if(is_null($user->rights)) { $user->rights = (object) array(); }
    if(is_null($user->rights->facture)) { $user->rights->facture = (object) array(); }
    if(is_null($user->rights->commande)) { $user->rights->commande = (object) array(); }
    $user->rights->facture->valider = true;
    $user->rights->commande->valider = true;
    
    switch($invoice_hash['status']) {
      case "PAID":
        $invoice->set_paid($user, '', '', false);
        break;
      case "AUTHORISED":
        $invoice->validate($user, '', 0, 0, false);
        break;
      case "SUBMITTED":
        $invoice->validate($user, '', 0, 0, false);
        break;
      case "DRAFT":
        $invoice->set_draft($user, -1, false);
        break;
      case "VOIDED":
        $invoice->set_canceled($user, '', '', false);
        break;
    }
  }

  // Map invoice status from Dolibarr to Connec
  private function mapInvoiceStatusToConnec(&$invoice_hash, $invoice) {
    if($invoice->paye == 1) {
      $invoice_hash['status'] = 'PAID';
    } else {
      $status_code = $invoice->statut;
      if($status_code == 0) {
        $invoice_hash['status'] = 'DRAFT';
      } else if($status_code == 1) {
        $invoice_hash['status'] = 'AUTHORISED';
      } else if($status_code == 2) {
        $invoice_hash['status'] = 'AUTHORISED';
      } else if($status_code == 3) {
        $invoice_hash['status'] = 'VOIDED';
      } 
    }
  }
}