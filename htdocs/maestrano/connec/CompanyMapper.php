<?php

/**
* Map Connec Company representation to/from Dolibarr Company
*/
class CompanyMapper extends BaseMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Company';
    $this->local_entity_name = 'Company';
    $this->connec_resource_name = 'company';
    $this->connec_resource_endpoint = 'company';
  }

  // No company entity saved locally
  public function getId($company) {
    return null;
  }

  // No company entity saved locally
  public function loadModelById($local_id) {
    return (object) array();
  }

  // No company entity saved locally
  public function matchLocalModel($company_hash) {
    return (object) array();
  }

  // Map the Connec resource attributes onto the Dolibarr Company
  protected function mapConnecResourceToModel($company_hash, $company) {
    $this->saveLogo($company_hash['logo']['logo']);
  }

  // Map the Dolibarr Company to a Connec resource hash
  protected function mapModelToConnecResource($company) {
    global $db;

    $company_hash = array();

    // Map Company to Connec hash
    $name = dolibarr_get_const($db, "MAIN_INFO_SOCIETE_NOM");
    $currency = dolibarr_get_const($db, "MAIN_MONNAIE");
    $tax_number = dolibarr_get_const($db, "MAIN_INFO_TVAINTRA");

    if($this->is_set($name)) { $company_hash['name'] = $name; }
    if($this->is_set($currency)) { $company_hash['currency'] = $currency; }
    if($this->is_set($tax_number)) { $company_hash['tax_number'] = $tax_number; }

    // Map address
    $address = dolibarr_get_const($db, "MAIN_INFO_SOCIETE_ADDRESS");
    $city = dolibarr_get_const($db, "MAIN_INFO_SOCIETE_TOWN");
    $postal_code = dolibarr_get_const($db, "MAIN_INFO_SOCIETE_ZIP");
    $telephone = dolibarr_get_const($db, "MAIN_INFO_SOCIETE_TEL");
    $fax = dolibarr_get_const($db, "MAIN_INFO_SOCIETE_FAX");
    $email = dolibarr_get_const($db, "MAIN_INFO_SOCIETE_MAIL");
    $website = dolibarr_get_const($db, "MAIN_INFO_SOCIETE_WEB");

    $company_hash['address'] = array('shipping' => array());
    if($this->is_set($address)) { $company_hash['address']['shipping']['line1'] = $address; }
    if($this->is_set($city)) { $company_hash['address']['shipping']['city'] = $city; }
    if($this->is_set($postal_code)) { $company_hash['address']['shipping']['postal_code'] = $postal_code; }
    if($this->is_set($city)) { $company_hash['address']['shipping']['region'] = $city; }
    if($this->is_set($city)) { $company_hash['address']['shipping']['country'] = $city; }

    // Map phone
    $company_hash['phone'] = array();
    $company_hash['phone']['landline'] = $company['phone'];
    $company_hash['phone']['fax'] = $company['fax'];

    $company_hash['website'] = array('url' => $company['website']);

    return $company_hash;
  }

  // Save company details as Dolibarr constants
  protected function persistLocalModel($company, $company_hash) {
    global $db;

    // Map company name
    if($this->is_set($company_hash['name'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM", $company_hash['name']); }
    if($this->is_set($company_hash['currency'])) { dolibarr_set_const($db, "MAIN_MONNAIE", $company_hash['currency']); }
    if($this->is_set($company_hash['tax_number'])) { dolibarr_set_const($db, "MAIN_INFO_TVAINTRA", $company_hash['tax_number']); }

    // Map company name
    $address = $this->is_set($company_hash['address']['shipping']) ? $company_hash['address']['shipping'] : $company_hash['address']['billing'];
    if($this->is_set($address['line1'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ADDRESS", $address['line1']); }
    if($this->is_set($address['city'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TOWN", $address['city']); }
    if($this->is_set($address['postal_code'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ZIP", $address['postal_code']); }

    // Map Country and state
    $state = $address['region'];
    $country = $address['country'];

    // Map country
    if(isset($country)) {
      $country_hash = ConnecUtils::findCountry($country);
      $s = $country_hash['rowid'] . ':'. $country_hash['code'] .':'.$country_hash['label'];
      dolibarr_set_const($db, "MAIN_INFO_SOCIETE_COUNTRY", $s);

      // Map state
      if (isset($state)) {
        $sql = "SELECT dep.rowid as state_id";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_departements as dep";
        $sql.= " JOIN ".MAIN_DB_PREFIX."c_regions as reg ON (reg.code_region = dep.fk_region)";
        $sql.= " WHERE reg.fk_pays = ".$country_hash['rowid'];
        $sql.= " AND (dep.code_departement = '" .$state."' OR dep.nom = '" .$state."' OR dep.ncc = '" .$state."')";
        $result = $db->query($sql);
        if($result->num_rows > 0){
          $obj = $result->fetch_assoc();
          dolibarr_set_const($db, "MAIN_INFO_SOCIETE_STATE", $obj['state_id']);
        }
      }
    }

    if($this->is_set($company_hash['phone']['landline'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TEL", $company_hash['phone']['landline']); }
    if($this->is_set($company_hash['email']['address'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_MAIL", $company_hash['email']['address']); }
    if($this->is_set($company_hash['website']['url'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_WEB", $company_hash['website']['url']); }
  }

  public function saveLogo($logo_url) {
    global $db;
    global $maxwidthsmall;
    global $maxheightsmall;
    global $maxwidthmini;
    global $maxheightmini;
    global $quality;
    global $conf;

    if(isset($logo_url)) {
      // Save logo file locally
      $filename = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10) . '.jpg';
      $dir = $conf->mycompany->dir_output . '/logos/';
      if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
      }
      $tmpLogoFilePath = $dir . $filename;
      MnoSoaLogger::debug("saving company logo " . $tmpLogoFilePath);
      file_put_contents($tmpLogoFilePath, file_get_contents($logo_url));

      dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO", $filename);
      $imgThumbSmall = vignette($tmpLogoFilePath, $maxwidthsmall, $maxheightsmall, '_small', $quality);
      if (preg_match('/([^\\/:]+)$/i',$imgThumbSmall,$reg)) {
          $imgThumbSmall = $reg[1];
          dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO_SMALL",$imgThumbSmall);
      } else dol_syslog($imgThumbSmall);

      $imgThumbMini = vignette($tmpLogoFilePath, $maxwidthmini, $maxheightmini, '_mini', $quality);
      if (preg_match('/([^\\/:]+)$/i',$imgThumbMini,$reg)) {
          $imgThumbMini = $reg[1];
          dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LOGO_MINI",$imgThumbMini);
      } else dol_syslog($imgThumbMini);
    }
  }
}
