<?php

/**
* Map Connec Customer Organization representation to/from Dolibarr Societe
*/
class OrganizationMapper extends BaseMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Organization';
    $this->local_entity_name = 'Societe';
    $this->connec_resource_name = 'organizations';
    $this->connec_resource_endpoint = 'organizations';
  }

  // Return the Organization local id
  protected function getId($organization) {
    return $organization->id;
  }

  // Return a local Organization by id
  protected function loadModelById($local_id) {
  	global $db;

    $organization = new Societe($db);
    $organization->fetch($local_id);
    return $organization;
  }

  // Map the Connec resource attributes onto the Dolibarr Societe
  protected function mapConnecResourceToModel($organization_hash, $organization) {
    // Organization type (customer/supplier/lead)
    if($organization_hash['is_customer']) { $organization->client = 1; }
    if($organization_hash['is_supplier']) { $organization->fournisseur = 1; }
    if($organization_hash['is_lead']) { $organization->prospect = 1; }

    // Map Organization code as customer or supplier unique code
    $code = $organization_hash['code'];
    if($this->is_set($code)) {
      if($organization_hash['is_customer']) { $organization->code_client = $code; } else
      if($organization_hash['is_supplier']) { $organization->code_fournisseur = $code; }
    }
    
    if($this->is_set($organization_hash['name'])) { $organization->name = $organization_hash['name']; }
    
    if($this->is_set($organization_hash['description'])) { $organization->note_public = $organization_hash['description']; }
    // if($this->is_set($organization_hash['industry'])) { $organization->column_fields['industry'] = $organization_hash['industry']; }
    // if($this->is_set($organization_hash['annual_revenue'])) { $organization->column_fields['annualrevenue'] = $organization_hash['annual_revenue']; }
    // if($this->is_set($organization_hash['reference'])) { $organization->column_fields['siccode'] = $organization_hash['reference']; }
    // if($this->is_set($organization_hash['number_of_employees'])) { $organization->column_fields['employees'] = $organization_hash['number_of_employees']; }

    // if($this->is_set($organization_hash['address'])) {
    //   if($this->is_set($organization_hash['address']['billing'])) {
    //     $billing_address = $organization_hash['address']['billing'];
    //     if($this->is_set($billing_address['line1'])) { $organization->column_fields['bill_street'] = $billing_address['line1']; }
    //     if($this->is_set($billing_address['line2'])) { $organization->column_fields['bill_pobox'] = $billing_address['line2']; }
    //     if($this->is_set($billing_address['city'])) { $organization->column_fields['bill_city'] = $billing_address['city']; }
    //     if($this->is_set($billing_address['region'])) { $organization->column_fields['bill_state'] = $billing_address['region']; }
    //     if($this->is_set($billing_address['postal_code'])) { $organization->column_fields['bill_code'] = $billing_address['postal_code']; }
    //     if($this->is_set($billing_address['country'])) { $organization->column_fields['bill_country'] = $billing_address['country']; }
    //   }

    //   if($this->is_set($organization_hash['address']['shipping'])) {
    //     $shipping_address = $organization_hash['address']['shipping'];
    //     if($this->is_set($shipping_address['line1'])) { $organization->column_fields['ship_street'] = $shipping_address['line1']; }
    //     if($this->is_set($shipping_address['line2'])) { $organization->column_fields['ship_pobox'] = $shipping_address['line2']; }
    //     if($this->is_set($shipping_address['city'])) { $organization->column_fields['ship_city'] = $shipping_address['city']; }
    //     if($this->is_set($shipping_address['region'])) { $organization->column_fields['ship_state'] = $shipping_address['region']; }
    //     if($this->is_set($shipping_address['postal_code'])) { $organization->column_fields['ship_code'] = $shipping_address['postal_code']; }
    //     if($this->is_set($shipping_address['country'])) { $organization->column_fields['ship_country'] = $shipping_address['country']; }
    //   }
    // }

    // if($this->is_set($organization_hash['phone'])) {
    //   if($this->is_set($organization_hash['phone']['landline'])) { $organization->column_fields['phone'] = $organization_hash['phone']['landline']; }
    //   if($this->is_set($organization_hash['phone']['landline2'])) { $organization->column_fields['otherphone'] = $organization_hash['phone']['landline2']; }
    //   if($this->is_set($organization_hash['phone']['fax'])) { $organization->column_fields['fax'] = $organization_hash['phone']['fax']; }
    // }

    // if($this->is_set($organization_hash['email']['address'])) { $organization->column_fields['email1'] = $organization_hash['email']['address']; }
    // if($this->is_set($organization_hash['email']['address2'])) { $organization->column_fields['email2'] = $organization_hash['email']['address2']; }
    // if($this->is_set($organization_hash['website']['url'])) { $organization->column_fields['website'] = $organization_hash['website']['url']; }
  }

  // Map the Dolibarr Organization to a Connec resource hash
  protected function mapModelToConnecResource($organization) {
    $organization_hash = array();

    // Map attributes
    // if($this->is_set($organization->column_fields['account_no'])) { $organization_hash['code'] = $organization->column_fields['account_no']; }
    if($this->is_set($organization->name)) { $organization_hash['name'] = $organization->name; }
    // if($this->is_set($organization->column_fields['description'])) { $organization_hash['description'] = $organization->column_fields['description']; }
    // if($this->is_set($organization->column_fields['industry'])) { $organization_hash['industry'] = $organization->column_fields['industry']; }
    // if($this->is_set($organization->column_fields['annualrevenue'])) { $organization_hash['annual_revenue'] = $organization->column_fields['annualrevenue']; }
    // if($this->is_set($organization->column_fields['siccode'])) { $organization_hash['reference'] = $organization->column_fields['siccode']; }
    // if($this->is_set($organization->column_fields['employees'])) { $organization_hash['number_of_employees'] = $organization->column_fields['employees']; }
    
    // $address = array();
    // $billing_address = array();
    // if($this->is_set($organization->column_fields['bill_street'])) { $billing_address['line1'] = $organization->column_fields['bill_street']; }
    // if($this->is_set($organization->column_fields['bill_pobox'])) { $billing_address['line2'] = $organization->column_fields['bill_pobox']; }
    // if($this->is_set($organization->column_fields['bill_city'])) { $billing_address['city'] = $organization->column_fields['bill_city']; }
    // if($this->is_set($organization->column_fields['bill_state'])) { $billing_address['region'] = $organization->column_fields['bill_state']; }
    // if($this->is_set($organization->column_fields['bill_code'])) { $billing_address['postal_code'] = $organization->column_fields['bill_code']; }
    // if($this->is_set($organization->column_fields['bill_country'])) { $billing_address['country'] = $organization->column_fields['bill_country']; }
    // if(!empty($billing_address)) { $address['billing'] = $billing_address; }

    // $shipping_address = array();
    // if($this->is_set($organization->column_fields['ship_street'])) { $shipping_address['line1'] = $organization->column_fields['ship_street']; }
    // if($this->is_set($organization->column_fields['ship_pobox'])) { $shipping_address['line2'] = $organization->column_fields['ship_pobox']; }
    // if($this->is_set($organization->column_fields['ship_city'])) { $shipping_address['city'] = $organization->column_fields['ship_city']; }
    // if($this->is_set($organization->column_fields['ship_state'])) { $shipping_address['region'] = $organization->column_fields['ship_state']; }
    // if($this->is_set($organization->column_fields['ship_code'])) { $shipping_address['postal_code'] = $organization->column_fields['ship_code']; }
    // if($this->is_set($organization->column_fields['ship_country'])) { $shipping_address['country'] = $organization->column_fields['ship_country']; }
    // if(!empty($shipping_address)) { $address['shipping'] = $shipping_address; }
    // if(!empty($address)) { $organization_hash['address'] = $address; }

    
    // $phone_hash = array();
    // if($this->is_set($organization->column_fields['phone'])) { $phone_hash['landline'] = $organization->column_fields['phone']; }
    // if($this->is_set($organization->column_fields['otherphone'])) { $phone_hash['landline2'] = $organization->column_fields['otherphone']; }
    // if($this->is_set($organization->column_fields['fax'])) { $phone_hash['fax'] = $organization->column_fields['fax']; }
    // if(!empty($phone_hash)) { $organization_hash['phone'] = $phone_hash; }

    // $email_hash = array();
    // if($this->is_set($organization->column_fields['email1'])) { $email_hash['address'] = $organization->column_fields['email1']; }
    // if($this->is_set($organization->column_fields['email2'])) { $email_hash['address2'] = $organization->column_fields['email2']; }
    // if(!empty($email_hash)) { $organization_hash['email'] = $email_hash; }

    // if($this->is_set($organization->column_fields['website'])) { $organization_hash['website'] = array('url' => $organization->column_fields['website']); }

    return $organization_hash;
  }

  // Persist the Dolibarr Organization
  protected function persistLocalModel($organization, $resource_hash) {
    $user = new User(null);
    if($this->is_new($organization)) {
      $organization->create($user);
    } else {
      $organization->update($organization->id, $user);
    }
  }
}