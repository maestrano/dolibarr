<?php

/**
* Map Connec Account representation to/from Dolibarr Account
*/
class AccountMapper extends BaseMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Account';
    $this->local_entity_name = 'Account';
    $this->connec_resource_name = 'accounts';
    $this->connec_resource_endpoint = 'accounts';
  }

  // Return the Account local id
  protected function getId($account) {
    return $account->id;
  }

  // Return a local Account by id
  protected function loadModelById($local_id) {
  	global $db;

    $account = new Account($db);
    $account->fetch($local_id);
    return $account;
  }

  // Match Account by name (unique in Dolibarr)
  protected function matchLocalModel($resource_hash) {
    return AccountMapper::getAccountByName($resource_hash['name']);
  }

  // Map the Connec resource attributes onto the Dolibarr Account
  protected function mapConnecResourceToModel($account_hash, $account) {
    // Default attributes
    if($this->is_new($account)) {
      $account->date_solde = date("y-m-d");
      $account->country_id = ConnecUtils::getCompanyCountryId();
    }

    // Map account type
    $account->courant = $this->mapAccountClassificationToDolibarr($account_hash);

    // Map Account attributes
    if($this->is_set($account_hash['code'])) { $account->ref = $account_hash['code']; }
    if($this->is_set($account_hash['name'])) { $account->label = $account_hash['name']; }
    if($this->is_set($account_hash['description'])) { $account->comment = $account_hash['description']; }
    if($this->is_set($account_hash['currency'])) { $account->currency_code = $account_hash['currency']; }

    if($this->is_set($account_hash['bank_account'])) {
      $bank_hash = $account_hash['bank_account'];
      if($this->is_set($bank_hash)) { $account->bank = $bank_hash['bank_name']; }
      if($this->is_set($bank_hash)) { $account->code_banque = $bank_hash['bank_code']; }
      if($this->is_set($bank_hash)) { $account->number = $bank_hash['account_number']; }
      if($this->is_set($bank_hash)) { $account->iban_prefix = $bank_hash['iban_number']; }
      if($this->is_set($bank_hash)) { $account->bic = $bank_hash['bic_code']; }
    }
  }

  // Map the Dolibarr Account to a Connec resource hash
  protected function mapModelToConnecResource($account) {
    $account_hash = array();

    // Set accoutn type only on account creation, do not overwrite Connec! account classification
    if($this->is_new($account)) { $this->mapAccountClassificationToConnec($account, $account_hash); }

    // Map Account attributes
    if($this->is_set($account->ref)) { $account_hash['code'] = $account->ref; }
    if($this->is_set($account->label)) { $account_hash['name'] = $account->label; }
    if($this->is_set($account->comment)) { $account_hash['description'] = $account->comment; }
    if($this->is_set($account->currency_code)) { $account_hash['currency'] = $account->currency_code; }

    // Bank details
    $bank_detail_hash = array();
    if($this->is_set($account->bank)) { $bank_detail_hash['bank_name'] = $account->bank; }
    if($this->is_set($account->code_banque)) { $bank_detail_hash['bank_code'] = $account->code_banque; }
    if($this->is_set($account->number)) { $bank_detail_hash['account_number'] = $account->number; }
    if($this->is_set($account->iban_prefix)) { $bank_detail_hash['iban_number'] = $account->iban_prefix; }
    if($this->is_set($account->bic)) { $bank_detail_hash['bic_code'] = $account->bic; }
    if(!empty($bank_detail_hash)) { $account_hash['bank_account'] = $bank_detail_hash; }

    return $account_hash;
  }

  // Map Connec account classification to Dolibarr account type
  private function mapAccountClassificationToDolibarr($account_hash) {
    // Match by account sub type
    if($account_hash['sub_type'] == 'SAVINGS') { return 0; }
    if($account_hash['sub_type'] == 'CHECKING') { return 1; }
    if($account_hash['sub_type'] == 'CREDITCARD') { return 1; }
    if($account_hash['sub_type'] == 'CASHONHAND') { return 2; }

    // Match by account type
    if($account_hash['type'] == 'BANK') { return 0; }
    if($account_hash['type'] == 'OTHERCURRENTASSET') { return 1; }
    if($account_hash['type'] == 'FIXEDASSET') { return 1; }
    if($account_hash['type'] == 'OTHERASSET') { return 1; }

    // Unrecognised Account type, default to 1
    return 1;
  }

  // Naive mapping from Dolibarr to Connec
  // Dolibarr supports only 3 different account types.
  private function mapAccountClassificationToConnec($account, &$account_hash) {
    if($account->type == 0) {
      $account_hash['class'] = 'ASSET';
      $account_hash['type'] = 'BANK';
      $account_hash['sub_type'] = 'SAVINGS';
    } else if($account->type == 1) {
      $account_hash['class'] = 'LIABILITY';
      $account_hash['type'] = 'CREDITCARD';
      $account_hash['sub_type'] = 'CREDITCARD';
    } else {
      $account_hash['class'] = 'ASSET';
      $account_hash['type'] = 'BANK';
      $account_hash['sub_type'] = 'CASHONHAND';
    }
  }

  // Persist the Dolibarr Account
  protected function persistLocalModel($account, $resource_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($account)) {
      $account->id = $account->create($user, false);
    } else {
      $account->update($user, false);
    }
  }

  public static function getAccountByName($account_name) {
    global $db;

    $result = $db->query("SELECT * from ".MAIN_DB_PREFIX."bank_account WHERE label = '$account_name' ORDER BY rowid DESC LIMIT 1");
    if($result->num_rows > 0) {
      $account_hash = $result->fetch_assoc();
      $account = new Account($db);
      $account->fetch($account_hash['rowid']);
      return $account;
    }
    return null;
  }
}