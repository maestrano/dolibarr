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
    return $invoice_hash['type'] == 'CUSTOMER';
  }

  // Map the Connec resource attributes onto the Dolibarr Invoice
  protected function mapConnecResourceToModel($invoice_hash, $invoice) {
    // TODO Map/Create Currency

    // Map invoice type
    $this->mapInvoiceTypeToDolibarr($invoice_hash, $invoice);

    // Map invoice status
    $this->mapInvoiceStatusToConnec($invoice_hash, $invoice);

    // Map invoice attributes
    if($this->is_set($invoice_hash['code'])) { $invoice->ref = $invoice_hash['code']; }
    if($this->is_set($invoice_hash['transaction_number'])) { $invoice->ref_ext = $invoice_hash['transaction_number']; }
    if($this->is_set($invoice_hash['transaction_date'])) { $invoice->date = $invoice_hash['transaction_date']; }
    if($this->is_set($invoice_hash['due_date'])) { $invoice->date_lim_reglement = $invoice_hash['due_date']; }
    if($this->is_set($invoice_hash['public_note'])) { $invoice->note_public = $invoice_hash['public_note']; }
    if($this->is_set($invoice_hash['private_note'])) { $invoice->note_private = $invoice_hash['private_note']; }
    if($this->is_set($invoice_hash['discount_percent'])) { $invoice->remise_percent = $invoice_hash['discount_percent']; }
    if($this->is_set($invoice_hash['discount_amount'])) { $invoice->remise_absolue = $invoice_hash['discount_amount']; }

    // Map Organization
    if($this->is_set($invoice_hash['organization_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($invoice_hash['organization_id'], 'ORGANIZATION', 'SOCIETE');
      if($mno_id_map) { $invoice->socid = $mno_id_map['app_entity_id']; }
    }

    // Map Contact
    if($this->is_set($invoice_hash['person_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($invoice_hash['person_id'], 'PERSON', 'CONTACTS');
      if($mno_id_map) { $invoice->add_contact($mno_id_map['app_entity_id']. 'BILLING'); }
    }
  }

  // Map the Dolibarr Invoice to a Connec resource hash
  protected function mapModelToConnecResource($invoice) {
    global $adb;

    $invoice_hash = array();

    // Missing transaction lines are considered as deleted by Connec!
    $invoice_hash['opts'] = array('sparse' => false);

    // Default invoice type to CUSTOMER
    $invoice_hash['type'] = 'CUSTOMER';

    // Map invoice status
    $this->mapInvoiceStatusToDolibarr($invoice_hash, $invoice);

    // // Map attributes
    if($this->is_set($invoice->ref)) { $invoice_hash['code'] = $invoice->ref; }
    if($this->is_set($invoice->ref_ext)) { $invoice_hash['transaction_number'] = $invoice->ref_ext; }
    if($this->is_set($invoice->date)) { $invoice_hash['transaction_date'] = date('c', $invoice->date); }
    if($this->is_set($invoice->date_lim_reglement)) { $invoice_hash['due_date'] = date('c', $invoice->date_lim_reglement); }
    if($this->is_set($invoice->note_public)) { $invoice_hash['public_note'] = $invoice->note_public; }
    if($this->is_set($invoice->note_private)) { $invoice_hash['private_note'] = $invoice->note_private; }
    if($this->is_set($invoice->remise_percent)) { $invoice_hash['discount_percent'] = $invoice->remise_percent; }
    if($this->is_set($invoice->remise_absolue)) { $invoice_hash['discount_amount'] = $invoice->remise_absolue; }

    // Map Organization
    if($this->is_set($invoice->socid)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice->socid, 'SOCIETE');
      if($mno_id_map) { $invoice_hash['organization_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map first Contact
    $contacts = $invoice->liste_contact();
    if(!empty($contacts)) {
      $contact = $contacts[0];
      $contact_id = $contact['id'];
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($contact_id, 'CONTACTS');
      if($mno_id_map) { $invoice_hash['person_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map Invoice lines
    if(!empty($invoice->lines)) {
      $invoice_lines_hashes = array();
      $invoice_line_mapper = new InvoiceLineMapper($invoice);
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
      $invoice->id = $invoice->create($user, 0, 0, false);
    } else {
      $invoice->update($user, 0, false);
    }

    // Persist Invoice Lines
    if(!empty($invoice_hash['lines'])) {
      foreach($invoice_hash['lines'] as $invoice_line_hash) {
        $invoice_line_mapper = new InvoiceLineMapper($invoice);
        $invoice_line_mapper->saveConnecResource($invoice_line_hash);
      }
    }

    // Calculate invoice amount
    $invoice->update_price(1);
  }

  // Set default invoice type to TYPE_STANDARD
  private function mapInvoiceTypeToDolibarr(&$invoice_hash, $invoice) {
    if($this->is_new($invoice)) { $invoice->type = Facture::TYPE_STANDARD; }
  }

  // Map invoice status from Connec to Dolibarr
  // Connec status: DRAFT, AUTHORISED, PAID, VOIDED
  private function mapInvoiceStatusToDolibarr($invoice_hash, $invoice) {
    switch($invoice_hash['status']) {
      case "PAID":
        $invoice->paye = 1;
        $invoice->statut = 2;
        break;
      case "AUTHORISED":
        $invoice->statut = 1;
        break;
      case "DRAFT":
        $invoice->statut = 0;
        break;
      case "VOIDED":
        $invoice->statut = 3;
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