<?php

/**
* Map Connec Organization representation to/from Dolibarr Societe
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
    if($this->is_set($organization_hash['code'])) {
      if($organization_hash['is_customer']) { $organization->code_client = $organization_hash['code']; } else
      if($organization_hash['is_supplier']) { $organization->code_fournisseur = $organization_hash['code']; }
    }
    
    if($this->is_set($organization_hash['name'])) { $organization->name = $organization_hash['name']; }
    
    if($this->is_set($organization_hash['description'])) { $organization->note_public = $organization_hash['description']; }
    if($this->is_set($organization_hash['capital'])) { $organization->capital = $organization_hash['capital']; }
    if($this->is_set($organization_hash['reference'])) { $organization->tva_intra = $organization_hash['reference']; }

    if($this->is_set($organization_hash['address'])) {
      $address = $this->is_set($organization_hash['address']['billing']) ? $organization_hash['address']['billing'] : $organization_hash['address']['shipping'];
      if($this->is_set($address)) {
        if($this->is_set($address['line1'])) { $organization->address = $address['line1']; }
        if($this->is_set($address['city'])) { $organization->town = $address['city']; }
        if($this->is_set($address['postal_code'])) { $organization->zip = $address['postal_code']; }

        // Map Country and state
        $state = $address['region'];
        $country = $address['country'];

        // Map country
        if(isset($country)) {
          $country_hash = ConnecUtils::findCountry($country);
          if($country_hash) {
            $organization->country_id = $country_hash['rowid'];
            $organization->country_code = $country_hash['code'];

            // Map state
            if (isset($state)) {
              $state_hash = ConnecUtils::findState($country_hash['rowid'], $state);
              if($state_hash) {
                $organization->state_id = $state_hash['rowid'];
                $organization->state_code = $state_hash['code_departement'];
              }
            }
          }
        }
      }
    }

    if($this->is_set($organization_hash['phone'])) {
      if($this->is_set($organization_hash['phone']['landline'])) { $organization->phone = $organization_hash['phone']['landline']; }
      if($this->is_set($organization_hash['phone']['fax'])) { $organization->fax = $organization_hash['phone']['fax']; }
    }

    if($this->is_set($organization_hash['email']['address'])) { $organization->email = $organization_hash['email']['address']; }
    if($this->is_set($organization_hash['contact_channel']['skype'])) { $organization->skype = $organization_hash['contact_channel']['skype']; }
    if($this->is_set($organization_hash['website']['url'])) { $organization->url = $organization_hash['website']['url']; }
  }

  // Map the Dolibarr Organization to a Connec resource hash
  protected function mapModelToConnecResource($organization) {
    $organization_hash = array();

    // Organization type (customer/supplier/lead)
    $organization_hash['is_customer'] = ($organization->client == 1) || ($organization->client == 3);
    $organization_hash['is_supplier'] = ($organization->fournisseur == 1);
    $organization_hash['is_lead'] = ($organization->prospect == 1) || ($organization->client == 3);

    // Map Organization code as customer or supplier unique code
    if($this->is_set($organization->code_client)) {
      $organization_hash['code'] = $organization->code_client;
    } else {
      $organization_hash['code'] = $organization->code_fournisseur;
    }

    $organization_hash['name'] = $organization->name;
    $organization_hash['description'] = $organization->note_public;
    $organization_hash['capital'] = $organization->capital;
    $organization_hash['reference'] = $organization->tva_intra;

    // Map billing address
    $address = array();
    $billing_address = array();
    $billing_address['line1'] = $organization->address;
    $billing_address['city'] = $organization->town;
    if($this->is_set($organization->state_id)) {
      $state_hash = ConnecUtils::findStateById($organization->state_id);
      $billing_address['region'] = $state_hash['code_departement'];
    }
    $billing_address['postal_code'] = $organization->zip;
    if($this->is_set($organization->country_id)) {
      global $db;
      $country = new Ccountry($db);
      $country->fetch($organization->country_id);
      $billing_address['country'] = $country->code;
    }
    if(!empty($billing_address)) { $address['billing'] = $billing_address; }
    if(!empty($address)) { $organization_hash['address'] = $address; }

    
    $phone_hash = array();
    $phone_hash['landline'] = $organization->phone;
    $phone_hash['fax'] = $organization->fax;
    if(!empty($phone_hash)) { $organization_hash['phone'] = $phone_hash; }

    $organization_hash['email'] = array('address' => $organization->email);
    $organization_hash['website'] = array('url' => $organization->url);
    $organization_hash['contact_channel'] = array('skype' => $organization->skype);

    return $organization_hash;
  }

  // Persist the Dolibarr Organization
  protected function persistLocalModel($organization, $resource_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($organization)) {
      $organization->id = $organization->create($user, false);
    } else {
      $organization->update($organization->id, $user, 1, 0, 0, 'update', 1, false);
    }
  }
}