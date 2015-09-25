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

    if($this->is_set($person_hash['job_title'])) { $person->poste = $person_hash['job_title']; }
  
    // Map Organization
    if(array_key_exists('organization_id', $person_hash)) {
      $organizationMapper = new OrganizationMapper();
      $organization = $organizationMapper->loadModelByConnecId($person_hash['organization_id']);
      if($organization) { $person->socid = $organization->id; }
    }

    // Map Address giveing precedence to work address
    $address = null;
    if($this->is_set($person_hash['address_work']) && $this->is_set($person_hash['address_work']['shipping'])) {
      $address = $person_hash['address_work']['shipping'];
    } else if($this->is_set($person_hash['address_work']) && $this->is_set($person_hash['address_work']['billing'])) {
      $address = $person_hash['address_work']['billing'];
    } else if($this->is_set($person_hash['address_home']) && $this->is_set($person_hash['address_home']['shipping'])) {
      $address = $person_hash['address_home']['shipping'];
    } else if($this->is_set($person_hash['address_home']) && $this->is_set($person_hash['address_home']['billing'])) {
      $address = $person_hash['address_home']['billing'];
    }

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

    // Map phones with precedence givent to work phones
    if($this->is_set($person_hash['phone_home'])) {
      if($this->is_set($person_hash['phone_home']['landline'])) { $person->phone_perso = $person_hash['phone_home']['landline']; }
      else if($this->is_set($person_hash['phone_home']['landline2'])) { $person->phone_perso = $person_hash['phone_home']['landline2']; }
      if($this->is_set($person_hash['phone_home']['fax'])) { $person->fax = $person_hash['phone_home']['fax']; }
      if($this->is_set($person_hash['phone_home']['mobile'])) { $person->phone_mobile = $person_hash['phone_home']['mobile']; }
    }

    if($this->is_set($person_hash['phone_work'])) {
      if($this->is_set($person_hash['phone_work']['landline'])) { $person->phone_pro = $person_hash['phone_work']['landline']; }
      else if($this->is_set($person_hash['phone_work']['landline2'])) { $person->phone_pro = $person_hash['phone_work']['landline2']; }
      if($this->is_set($person_hash['phone_work']['fax'])) { $person->fax = $person_hash['phone_work']['fax']; }
      if($this->is_set($person_hash['phone_work']['mobile'])) { $person->phone_mobile = $person_hash['phone_work']['mobile']; }
    } 

    if($this->is_set($person_hash['email']['address'])) { $person->email = $person_hash['email']['address']; }
    if($this->is_set($person_hash['contact_channel']['skype'])) { $person->skype = $person_hash['contact_channel']['skype']; }
  }

  // Map the Dolibarr Organization to a Connec resource hash
  protected function mapModelToConnecResource($person) {
    global $db;

    $person_hash = array();

    // Organization and type (customer/supplier/lead)
    if($person->socid > 0) {
      $societe = new Societe($db);
      $societe->fetch($person->socid);

      $person_hash['is_customer'] = ($societe->client == 1);
      $person_hash['is_supplier'] = ($societe->fournisseur == 1);
      $person_hash['is_lead'] = ($societe->prospect == 1);

      // Map Organization reference
      $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($person->socid, 'Societe');
      if($mno_id_map) { $person_hash['organization_id'] = $mno_id_map['mno_entity_guid']; }
    } else {
      // Default contact to Customer if not belonging to an Organization
      $person_hash['is_customer'] = true;
      $person_hash['is_supplier'] = false;
      $person_hash['is_lead'] = false;
    }
    
    // Map contact attributes
    $person_hash['code'] = $person->code;
    $person_hash['title'] = $person->getCivilityLabel();
    $person_hash['first_name'] = $person->firstname;
    $person_hash['last_name'] = $person->lastname;
    $person_hash['description'] = $person->note_public;
    $person_hash['job_title'] = $person->poste;

    // Map address
    $address = array();
    $shipping_address = array();
    $shipping_address['line1'] = $person->address;
    $shipping_address['city'] = $person->town;
    if($this->is_set($person->state_id)) {
      $state_hash = ConnecUtils::findStateById($person->state_id);
      $shipping_address['region'] = $state_hash['code_departement'];
    }
    $shipping_address['postal_code'] = $person->zip;
    if($this->is_set($person->country_id)) {
      $country = ConnecUtils::findCountryById($person->country_id);
      $shipping_address['country'] = $country->code;
    }
    if(!empty($shipping_address)) { $address['shipping'] = $shipping_address; }
    if(!empty($address)) { $person_hash['address_work'] = $address; }

    // Map phones
    $work_phone_hash = array();
    $work_phone_hash['landline'] = $person->phone_pro;
    $work_phone_hash['fax'] = $person->fax;
    $work_phone_hash['mobile'] = $person->phone_mobile;
    if(!empty($work_phone_hash)) { $person_hash['phone_work'] = $work_phone_hash; }

    $home_phone_hash = array();
    $home_phone_hash['landline'] = $person->phone_perso;
    if(!empty($home_phone_hash)) { $person_hash['phone_home'] = $home_phone_hash; }

    $person_hash['email'] = array('address' => $person->email);
    $person_hash['website'] = array('url' => $person->url);
    $person_hash['contact_channel'] = array('skype' => $person->skype);

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