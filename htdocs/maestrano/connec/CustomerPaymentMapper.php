<?php

/**
* Map Connec Payment representation to/from Dolibarr Paiement
*/
class CustomerPaymentMapper extends PaymentMapper {
  public function __construct() {
    parent::__construct();

    $this->local_entity_name = 'Paiement';
  }

  protected function validate($payment_hash) {
    // Process only Customer Payments
    return $payment_hash['type'] == 'CUSTOMER';
  }

  // Map the Connec resource attributes onto the Dolibarr payment
  protected function mapConnecResourceToModel($payment_hash, $payment) {
    parent::mapConnecResourceToModel($payment_hash, $payment);
  }

  // Map the Dolibarr Payment to a Connec resource hash
  protected function mapModelToConnecResource($payment) {
    $payment_hash = parent::mapModelToConnecResource($payment);

    // Default payment type to CUSTOMER
    $payment_hash['type'] = 'CUSTOMER';

    // Map payment lines ID
    $payment_hash['payment_lines'] = array();
    $local_payment_lines = $this->getLocalPaymentLines($payment);
    foreach ($local_payment_lines as $local_payment_line) {
      $payment_line = array();

      // Map Payment Line ID
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($local_payment_line['rowid'], 'PAIEMENTLIGNE');
      if($mno_id_map) { $payment_line['id'] = array(array('id' => $mno_id_map['mno_entity_guid'])); }

      // Payment line attributes
      $payment_line['amount'] = $local_payment_line['amount'];

      // Map single payment to Payment line
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

    // Map payment lines IDs
    $local_payment_lines = $this->getLocalPaymentLines($payment);
    if(!empty($payment_hash['payment_lines'])) {
      foreach($payment_hash['payment_lines'] as $payment_line_hash) {
        $local_payment_line = $local_payment_lines->fetch_assoc();
        $payment_line_id = $payment_line_hash['id'];
        MnoIdMap::addMnoIdMap($local_payment_line['rowid'], 'PAIEMENTLIGNE', $payment_line_id, 'PAYMENTLINE');
      }
    }
  }

  private function getLocalPaymentLines($payment) {
    global $db;

    $sql = 'SELECT *';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture ';
    $sql.= ' WHERE fk_paiement = '.$payment->id;
    $sql.= ' ORDER BY rowid';
    return $db->query($sql);
  }
}