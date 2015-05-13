<?php

/**
* Map Connec Payment representation to/from Dolibarr Paiement
*/
class PaymentMapper extends BaseMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Payment';
    $this->local_entity_name = 'Paiement';
    $this->connec_resource_name = 'payments';
    $this->connec_resource_endpoint = 'payments';
  }

  protected function validate($payment_hash) {
    // Process only Customer Payments
    return $payment_hash['type'] == 'CUSTOMER';
  }

  // Return the Payment local id
  protected function getId($payment) {
    return $payment->id;
  }

  // Return a local Payment by id
  protected function loadModelById($local_id) {
    global $db;

    $payment = new $this->local_entity_name($db);
    $payment->fetch($local_id);
    return $payment;
  }

  // Map the Connec resource attributes onto the Dolibarr payment
  protected function mapConnecResourceToModel($payment_hash, $payment) {
    // TODO Map/Create Currency

    // Map invoice attributes
    if($this->is_set($payment_hash['code'])) { $payment->ref = $payment_hash['code']; }
    if($this->is_set($payment_hash['payment_reference'])) { $payment->num_paiement = $payment_hash['payment_reference']; }
    if($this->is_set($payment_hash['transaction_date'])) { $payment->datepaye = $payment_hash['transaction_date']; }
    if($this->is_set($payment_hash['public_note'])) { $payment->note = $payment_hash['public_note']; }

    // Map Account
    if($this->is_set($payment_hash['deposit_account_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($payment_hash['deposit_account_id'], 'ACCOUNT');
      if($mno_id_map) { $payment->fk_account = $mno_id_map['app_entity_id']; }
    }

    // TODO: Map Payment Method
    $payment->paiementid = 6;

    // Map Payment lines
    $payment->amounts = array();
    // Map each linked invoice of each payment line to a local payment
    foreach($payment_hash['payment_lines'] as $payment_line_hash) {
      $payment_line_amount = floatval($payment_line_hash['amount']);
      foreach($payment_line_hash['linked_transactions'] as $transaction_hash) {
        // Map invoice and amount paid
        $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($transaction_hash['id'], 'INVOICE');
        if($mno_id_map) {
          $payment->ac_inv_id = $mno_id_map['app_entity_id'];
          $payment->amounts[$mno_id_map['app_entity_id']] = $payment_line_amount;
        }
      }
    }
  }

  // Map the Dolibarr Payment to a Connec resource hash
  protected function mapModelToConnecResource($payment) {
    global $adb;

    $payment_hash = array();

    // Missing payment lines are considered as deleted by Connec!
    $payment_hash['opts'] = array('sparse' => false);

    // // Map attributes
    if($this->is_set($payment->ref)) { $payment_hash['code'] = $payment->ref; }
    if($this->is_set($payment->num_paiement)) { $payment_hash['payment_reference'] = $payment->num_paiement; }
    if($this->is_set($payment->datepaye)) { $payment_hash['transaction_date'] = date('c', $payment->datepaye); }
    if($this->is_set($payment->note)) { $payment_hash['public_note'] = $payment->note; }
    if($this->is_set($payment->amount)) { $payment_hash['total_amount'] = $payment->amount; }

    // Map Account
    if($this->is_set($payment->fk_account)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($payment->fk_account, 'ACCOUNT');
      if($mno_id_map) { $payment_hash['deposit_account_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map payment lines ID
    $payment_hash['payment_lines'] = array();
    $local_payment_lines = $this->getLocalPaymentLines($payment);
    foreach ($local_payment_lines as $local_payment_line) {
      // TODO: Map Payment Line ID
      $payment_line = array();
      $payment_line['amount'] = $local_payment_line['amount'];

      // Map single Invoice to Payment line
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($local_payment_line['fk_facture'], 'FACTURE');
      if($mno_id_map) { $payment_line['linked_transactions'] = array(array('id' => $mno_id_map['mno_entity_guid'])); }
      
      $payment_hash['payment_lines'][] = $payment_line;
    }

    return $payment_hash;
  }

  // Persist the Dolibarr Payment. Process only new Payments.
  protected function persistLocalModel($payment, $payment_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($payment)) {
      $payment->id = $payment->create($user, 1, false);
    }
  }

  private function getLocalPaymentLines($payment) {
    global $db;

    $sql = 'SELECT *';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture ';
    $sql.= ' WHERE fk_paiement = '.$payment->id;
    return $db->query($sql);
  }
}