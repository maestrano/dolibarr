<?php

/**
* Map Connec Payment representation to/from Dolibarr PaiementFourn
*/
class SupplierPaymentMapper extends PaymentMapper {
  public function __construct() {
    parent::__construct();

    $this->local_entity_name = 'PaiementFourn';
  }

  protected function validate($payment_hash) {
    // Process only Customer Payments
    return $payment_hash['type'] == 'SUPPLIER';
  }

  // Map the Connec resource attributes onto the Dolibarr payment
  protected function mapConnecResourceToModel($payment_hash, $payment) {
    parent::mapConnecResourceToModel($payment_hash, $payment);
  }

  // Map the Dolibarr Payment to a Connec resource hash
  protected function mapModelToConnecResource($payment) {
    $payment_hash = parent::mapModelToConnecResource($payment);

    // Default payment type to SUPPLIER
    $payment_hash['type'] = 'SUPPLIER';

    // Map payment lines ID
    $payment_hash['payment_lines'] = array();
    $local_payment_lines = $this->getLocalPaymentLines($payment);
    foreach ($local_payment_lines as $local_payment_line) {
      // TODO: Map Payment Line ID
      $payment_line = array();
      $payment_line['amount'] = $local_payment_line['amount'];

      // Map single payment to Payment line
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($local_payment_line['fk_facture'], 'FACTUREFOURNISSEUR');
      if($mno_id_map) { $payment_line['linked_payments'] = array(array('id' => $mno_id_map['mno_entity_guid'])); }
      
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
    $sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn ';
    $sql.= ' WHERE fk_paiementfourn = '.$payment->id;
    return $db->query($sql);
  }
}