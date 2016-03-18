<?php

/**
* Base mapper for payments
*/
abstract class PaymentMapper extends BaseMapper {
  protected $connec_entity_line_name = 'PAYMENTLINE';
  protected $local_entity_line_name = 'PAYMENTLINE';
  protected $default_label = '(CustomerInvoicePayment)';
  protected $default_operation = 'payment';

  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Payment';
    $this->local_entity_name = 'Payment';
    $this->connec_resource_name = 'payments';
    $this->connec_resource_endpoint = 'payments';
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

  // Map the Connec resource attributes onto the Dolibarr Payment
  protected function mapConnecResourceToModel($payment_hash, $payment) {
    // Map payment attributes
    if($this->is_set($payment_hash['code'])) { $payment->ref = $payment_hash['code']; }
    if($this->is_set($payment_hash['payment_reference'])) { $payment->num_paiement = $payment_hash['payment_reference']; }
    if($this->is_set($payment_hash['transaction_date'])) { $payment->datepaye = $payment_hash['transaction_date']; }
    if($this->is_set($payment_hash['public_note'])) { $payment->note = $payment_hash['public_note']; }

    // Map Account
    if($this->is_set($payment_hash['account_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($payment_hash['account_id'], 'ACCOUNT');
      if($mno_id_map) { $payment->fk_account = $mno_id_map['app_entity_id']; }
    }

    // TODO: Map Payment Method
    $payment->paiementid = 6;

    // Map Payment lines
    $payment->amounts = array();
    // Map each linked payment of each payment line to a local invoice
    foreach($payment_hash['payment_lines'] as $payment_line_hash) {
      // Payment amount
      $payment_line_amount = floatval($payment_line_hash['amount']);
      foreach($payment_line_hash['linked_transactions'] as $payment_hash) {
        // Map payment and amount paid
        $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($payment_hash['id'], 'INVOICE');
        if($mno_id_map) { $payment->amounts[$mno_id_map['app_entity_id']] = $payment_line_amount; }
      }
    }
  }

  // Map the Dolibarr Payment to a Connec resource hash
  protected function mapModelToConnecResource($payment) {
    $payment_hash = array();

    // Missing payment lines are considered as deleted by Connec!
    $payment_hash['opts'] = array('sparse' => false);

    // // Map attributes
    if($this->is_set($payment->ref)) { $payment_hash['code'] = $payment->ref; }
    if($this->is_set($payment->num_paiement)) { $payment_hash['payment_reference'] = $payment->num_paiement; }
    if($this->is_set($payment->datepaye)) { $payment_hash['transaction_date'] = date('c', $payment->datepaye); }
    if($this->is_set($payment->note)) { $payment_hash['public_note'] = $payment->note; }
    if($this->is_set($payment->amount)) { $payment_hash['amount'] = array('total_amount' => $payment->amount); }

    // Map Account
    if($this->is_set($payment->fk_account)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($payment->fk_account, 'ACCOUNT');
      if($mno_id_map) { $payment_hash['account_id'] = $mno_id_map['mno_entity_guid']; }
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
        MnoIdMap::addMnoIdMap($local_payment_line['rowid'], $this->local_entity_line_name, $payment_line_id, $this->connec_entity_line_name);
      }
    }

    // Add payment entry to general ledger
    if($this->is_set($payment_hash['account_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($payment_hash['account_id'], 'ACCOUNT');
      if($mno_id_map) {
        $accountMapper = new AccountMapper();
        $account = $accountMapper->loadModelById(intval($mno_id_map['app_entity_id']));
        // Accounts of type Cash accepts only payment method 'LIQ'
        if($account->courant == 2) { $payment->paiementid = 'LIQ'; }

        $payment->addPaymentToBank($user, $this->default_operation, $this->default_label, $account->rowid, '', '');
      }
    }
  }

  protected function getLocalPaymentLines($payment) {}
}