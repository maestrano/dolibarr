<?php

/**
* Map Connec Person representation to/from Dolibarr Contact
*/
class ContactMapper extends BaseMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Person';
    $this->local_entity_name = 'Contact';
    $this->connec_resource_name = 'people';
    $this->connec_resource_endpoint = 'people';
  }

  // Return the Organization local id
  protected function getId($person) {
    return $person->id;
  }

  // Return a local Organization by id
  protected function loadModelById($local_id) {
  	global $db;

    $person = new Contact($db);
    $person->fetch($local_id);
    return $person;
  }

  // Map the Connec resource attributes onto the Dolibarr Contact
  protected function mapConnecResourceToModel($person_hash, $person) {
    // Map Contact unique code
    if($this->is_set($person_hash['code'])) { $person->code = $person_hash['code']; }
    if($this->is_set($person_hash['description'])) { $person->note_public = $person_hash['description']; }
    if($this->is_set($person_hash['first_name'])) { $person->firstname = $person_hash['first_name']; }
    if($this->is_set($person_hash['last_name'])) { $person->lastname = $person_hash['last_name']; }
    // TODO: Title?

    if($this->is_set($person_hash['job_title'])) { $person->poste = $person_hash['job_title']; }
  
    // Map Organization
    if($this->is_set($person_hash['organization_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($person_hash['organization_id'], 'Organization');
      if($mno_id_map) { $person->socid = $mno_id_map['app_entity_id']; }
    }

    // Map Address
    if($this->is_set($person_hash['address'])) {
      $address = $this->is_set($person_hash['address']['billing']) ? $person_hash['address']['billing'] : $person_hash['address']['shipping'];
      if($this->is_set($address)) {
        if($this->is_set($address['line1'])) { $person->address = $address['line1']; }
        if($this->is_set($address['city'])) { $person->town = $address['city']; }
        if($this->is_set($address['postal_code'])) { $person->zip = $address['postal_code']; }

        // Map Country and state
        $state = $address['region'];
        $country = $address['country'];

        // Map country
        if(isset($country)) {
          $country_hash = ConnecUtils::findCountry($country);
          if($country_hash) {
            $person->country_id = $country_hash['rowid'];
            $person->country_code = $country_hash['code'];

            // Map state
            if (isset($state)) {
              $state_hash = ConnecUtils::findState($country_hash['rowid'], $state);
              if($state_hash) {
                $person->state_id = $state_hash['rowid'];
                $person->state_code = $state_hash['code_departement'];
              }
            }
          }
        }
      }
    }

    if($this->is_set($person_hash['phone_work'])) {
      if($this->is_set($person_hash['phone_work']['landline'])) { $person->phone_pro = $person_hash['phone_work']['landline']; }
      if($this->is_set($person_hash['phone_work']['fax'])) { $person->fax = $person_hash['phone_work']['fax']; }
    }

    if($this->is_set($person_hash['phone_home'])) {
      if($this->is_set($person_hash['phone_home']['landline'])) { $person->phone_perso = $person_hash['phone_home']['landline']; }
      if($this->is_set($person_hash['phone_home']['mobile'])) { $person->phone_mobile = $person_hash['phone_home']['mobile']; }
    }

    if($this->is_set($person_hash['email']['address'])) { $person->email = $person_hash['email']['address']; }
    if($this->is_set($person_hash['contact_channel']['skype'])) { $person->skype = $person_hash['contact_channel']['skype']; }
  }

  // Map the Dolibarr Organization to a Connec resource hash
  protected function mapModelToConnecResource($person) {
    global $db;

    $person_hash = array();

    // Organization type (customer/supplier/lead)
    $societe = new Societe($db);
    $societe->fetch($person->socid);
    $person_hash['is_customer'] = ($societe->client == 1);
    $person_hash['is_supplier'] = ($societe->fournisseur == 1);
    $person_hash['is_lead'] = ($societe->prospect == 1);

    // Map Organization code as customer or supplier unique code
    if($this->is_set($person->code)) { $person_hash['code'] = $person->code; }
    if($this->is_set($person->civility_id)) { $person_hash['title'] = $person->getCivilityLabel(); }
    if($this->is_set($person->firstname)) { $person_hash['first_name'] = $person->firstname; }
    if($this->is_set($person->lastname)) { $person_hash['last_name'] = $person->lastname; }
    if($this->is_set($person->note_public)) { $person_hash['description'] = $person->note_public; }
    if($this->is_set($person->poste)) { $person_hash['job_title'] = $person->poste; }

    // Map Organization
    if($this->is_set($person->socid)) {
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($person->socid, 'Societe');
      if($mno_id_map) { $person_hash['organization_id'] = $mno_id_map['mno_entity_guid']; }
    }

    // Map address
    $address = array();
    $billing_address = array();
    if($this->is_set($person->address)) { $billing_address['line1'] = $person->address; }
    if($this->is_set($person->town)) { $billing_address['city'] = $person->town; }
    if($this->is_set($person->state_id)) {
      $state_hash = ConnecUtils::findStateById($person->state_id);
      $billing_address['region'] = $state_hash['code_departement'];
    }
    if($this->is_set($person->zip)) { $billing_address['postal_code'] = $person->zip; }
    if($this->is_set($person->country_id)) {
      global $db;
      $country = new Ccountry($db);
      $country->fetch($person->country_id);
      $billing_address['country'] = $country->code;
    }
    if(!empty($billing_address)) { $address['billing'] = $billing_address; }
    if(!empty($address)) { $person_hash['address'] = $address; }

    // Map phones
    $work_phone_hash = array();
    if($this->is_set($person->phone_pro)) { $work_phone_hash['landline'] = $person->phone_pro; }
    if($this->is_set($person->fax)) { $work_phone_hash['fax'] = $person->fax; }
    if(!empty($work_phone_hash)) { $person_hash['phone_work'] = $work_phone_hash; }

    $home_phone_hash = array();
    if($this->is_set($person->phone_perso)) { $home_phone_hash['landline'] = $person->phone_perso; }
    if($this->is_set($person->phone_mobile)) { $home_phone_hash['mobile'] = $person->phone_mobile; }
    if(!empty($home_phone_hash)) { $person_hash['phone_home'] = $home_phone_hash; }

    if($this->is_set($person->email)) { $person_hash['email'] = array('address' => $person->email); }
    if($this->is_set($person->url)) { $person_hash['website'] = array('url' => $person->url); }
    if($this->is_set($person->skype)) { $person_hash['contact_channel'] = array('skype' => $person->skype); }

    return $person_hash;
  }

  // Persist the Dolibarr Contact
  protected function persistLocalModel($person, $resource_hash) {
    $user = ConnecUtils::defaultUser();
    if($this->is_new($person)) {
      $person->id = $person->create($user, false);
    } else {
      $person->update($person->id, $user, 0, 'update', false);
    }
  }
}