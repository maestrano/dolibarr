<?php

/**
* Map Connec Supplier Invoice representation to/from Dolibarr FactureFournisseur
*/
class SupplierInvoiceMapper extends TransactionMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Invoice';
    $this->local_entity_name = 'FactureFournisseur';
    $this->connec_resource_name = 'invoices';
    $this->connec_resource_endpoint = 'invoices';
  }

  protected function validate($invoice_hash) {
    // Process only Supplier Invoices
    return $invoice_hash['type'] == 'SUPPLIER' && $this->is_set($invoice_hash['organization_id']);
  }

  // Map the Connec resource attributes onto the Dolibarr Invoice
  protected function mapConnecResourceToModel($invoice_hash, $invoice) {
    parent::mapConnecResourceToModel($invoice_hash, $invoice);

    // Map invoice type
    $this->mapInvoiceTypeToDolibarr($invoice_hash, $invoice);

    // Map invoice status
    $this->mapInvoiceStatusToDolibarr($invoice_hash, $invoice);

    // Map attributes
    if($this->is_set($invoice_hash['title'])) { $invoice->libelle = $invoice_hash['title']; }
    if($this->is_set($invoice_hash['due_date'])) { $invoice->date_echeance = $invoice_hash['due_date']; }

    // Find of generate a supplier reference
    if($this->is_set($invoice_hash['transaction_number'])) { $invoice->ref_supplier = $invoice_hash['transaction_number']; }
    else if($this->is_set($invoice_hash['code'])) { $invoice->ref_supplier = $invoice_hash['code']; }
    else { $invoice->ref_supplier = mt_rand(); }
  }

  // Map the Dolibarr Invoice to a Connec resource hash
  protected function mapModelToConnecResource($invoice) {
    global $adb;

    $invoice_hash = parent::mapModelToConnecResource($invoice);

    // Default invoice type to SUPPLIER
    $invoice_hash['type'] = 'SUPPLIER';

    if($this->is_set($invoice->libelle)) { $invoice_hash['title'] = $invoice->libelle; }
    if($this->is_set($invoice->date_echeance)) { $invoice_hash['due_date'] = date('c', $invoice->date_echeance); }

    // Map invoice status
    $this->mapInvoiceStatusToConnec($invoice_hash, $invoice);

    // Map first Contact
    $contacts = $invoice->liste_contact();
    if(!empty($contacts)) {
      $contact = $contacts[0];
      $contact_id = $contact['id'];
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($contact_id, 'CONTACT');
      if($mno_id_map) { $invoice_hash['person_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map Invoice lines
    if(!empty($invoice->lines)) {
      $invoice_lines_hashes = array();
      $invoice_line_mapper = new SupplierInvoiceLineMapper($invoice, $invoice_hash);
      foreach($invoice->lines as $invoice_line) {
        $invoice_line_hash = $invoice_line_mapper->mapModelToConnecResource($invoice_line);
        $invoice_line_hash['line_number'] = count($invoice_lines_hashes) + 1;
        array_push($invoice_lines_hashes, $invoice_line_hash);
      }
      $invoice_hash['lines'] = $invoice_lines_hashes;
    }

    return $invoice_hash;
  }

  // Persist the Dolibarr Invoice
  protected function persistLocalModel($invoice, $invoice_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($invoice)) {
      $invoice->create($user, false);
    } else {
      $invoice->update($user, 0, false);
      $invoice->fetch($invoice->id);
    }

    // Persist invoice lines
    if(!empty($invoice_hash['lines'])) {
      $processed_lines_local_ids = array();

      foreach($invoice_hash['lines'] as $invoice_line_hash) {
        $invoice_line_mapper = new SupplierInvoiceLineMapper($invoice, $invoice_hash);
        $invoice_line = $invoice_line_mapper->saveConnecResource($invoice_line_hash);
        array_push($processed_lines_local_ids, $invoice_line->rowid);
      }

      // Delete local invoice lines that have been removed
      $local_invoice_lines = $invoice->lines;
      foreach ($local_invoice_lines as $local_invoice_line) {
        if(!in_array($local_invoice_line->rowid, $processed_lines_local_ids)) {
          $invoice->deleteline($local_invoice_line->rowid, 0, false);
          MnoIdMap::hardDeleteMnoIdMap($local_invoice_line->rowid, 'FACTURELIGNE');
        }
      }
    }

    // Calculate invoice amount
    $invoice->update_price(1);
  }

  // Set default invoice type to TYPE_STANDARD
  private function mapInvoiceTypeToDolibarr(&$invoice_hash, $invoice) {
    if($this->is_new($invoice)) { $invoice->type = FactureFournisseur::TYPE_STANDARD; }
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