<?php

/**
* Base mapper for transactions (customer invoices, supplier invoices, proposals, orders)
*/
abstract class TransactionMapper extends BaseMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Transaction';
    $this->local_entity_name = 'Transaction';
    $this->connec_resource_name = 'transactions';
    $this->connec_resource_endpoint = 'transactions';
  }

  // Return the Transaction local id
  protected function getId($transaction) {
    return $transaction->id;
  }

  // Return a local Transaction by id
  protected function loadModelById($local_id) {
    global $db;

    $transaction = new $this->local_entity_name($db);
    $transaction->fetch($local_id);
    return $transaction;
  }

  // Map the Connec resource attributes onto the Dolibarr transaction
  protected function mapConnecResourceToModel($transaction_hash, $transaction) {
    // TODO Map/Create Currency
error_log("INVOICE DUE DATE: " . json_encode($transaction_hash['due_date']));
    // Map invoice attributes
    if($this->is_set($transaction_hash['code'])) { $transaction->ref = $transaction_hash['code']; }
    if($this->is_set($transaction_hash['transaction_date'])) { $transaction->date = $transaction_hash['transaction_date']; }
    if($this->is_set($transaction_hash['due_date'])) { $transaction->date_lim_reglement = $transaction_hash['due_date']; }
    if($this->is_set($transaction_hash['public_note'])) { $transaction->note_public = $transaction_hash['public_note']; }
    if($this->is_set($transaction_hash['private_note'])) { $transaction->note_private = $transaction_hash['private_note']; }
    if($this->is_set($transaction_hash['discount_percent'])) { $transaction->remise_percent = $transaction_hash['discount_percent']; }
    if($this->is_set($transaction_hash['discount_amount'])) { $transaction->remise_absolue = $transaction_hash['discount_amount']; }

    // Map Organization
    if($this->is_set($transaction_hash['organization_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($transaction_hash['organization_id'], 'ORGANIZATION', 'SOCIETE');
      if($mno_id_map) { $transaction->socid = $mno_id_map['app_entity_id']; }
    }

    // Map Contact
    if($this->is_set($transaction_hash['person_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($transaction_hash['person_id'], 'PERSON', 'CONTACTS');
      if($mno_id_map) { $transaction->add_contact($mno_id_map['app_entity_id']. 'BILLING'); }
    }
  }

  // Map the Dolibarr Transaction to a Connec resource hash
  protected function mapModelToConnecResource($transaction) {
    global $adb;

    $transaction_hash = array();

    // Missing transaction lines are considered as deleted by Connec!
    $transaction_hash['opts'] = array('sparse' => false);

    // // Map attributes
    if($this->is_set($transaction->ref)) { $transaction_hash['code'] = $transaction->ref; }
    if($this->is_set($transaction->ref_ext)) { $transaction_hash['transaction_number'] = $transaction->ref_ext; }
    if($this->is_set($transaction->date)) { $transaction_hash['transaction_date'] = date('c', $transaction->date); }
    if($this->is_set($transaction->date_lim_reglement)) { $transaction_hash['due_date'] = date('c', $transaction->date_lim_reglement); }
    if($this->is_set($transaction->note_public)) { $transaction_hash['public_note'] = $transaction->note_public; }
    if($this->is_set($transaction->note_private)) { $transaction_hash['private_note'] = $transaction->note_private; }
    if($this->is_set($transaction->remise_percent)) { $transaction_hash['discount_percent'] = $transaction->remise_percent; }
    if($this->is_set($transaction->remise_absolue)) { $transaction_hash['discount_amount'] = $transaction->remise_absolue; }

    // Map Organization
    if($this->is_set($transaction->socid)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($transaction->socid, 'SOCIETE');
      if($mno_id_map) { $transaction_hash['organization_id'] = $mno_id_map['mno_entity_guid']; }
    }

    return $transaction_hash;
  }
}