<?php

/**
* Base mapper for transactions (customer invoices, supplier invoices, proposals, orders)
*/
class TransactionMapper extends BaseMapper {
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

  // ??
  protected function mapConnecResourceToModel($transaction_hash, $transaction) {
    
  }

  // ??
  protected function mapModelToConnecResource($transaction) {
    
  }

  // Persist the Dolibarr Transaction
  protected function persistLocalModel($transaction, $transaction_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($transaction)) {
      $transaction->id = $transaction->create($user, 0, 0, false);
    } else {
      $transaction->update($user, 0, false);
    }
  }
}