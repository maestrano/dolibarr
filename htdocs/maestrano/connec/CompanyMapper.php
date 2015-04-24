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
    $company_hash = array();

    // Map Company to Connec hash
    $company_hash['name'] = $company['organizationname'];
    $company_hash['tax_number'] = $company['vatid'];

    $company_hash['address'] = array('shipping' => array());
    $company_hash['address']['shipping']['line1'] = $company['address'];
    $company_hash['address']['shipping']['city'] = $company['city'];
    $company_hash['address']['shipping']['region'] = $company['state'];
    $company_hash['address']['shipping']['postal_code'] = $company['code'];
    $company_hash['address']['shipping']['country'] = $company['country'];

    $company_hash['phone'] = array();
    $company_hash['phone']['landline'] = $company['phone'];
    $company_hash['phone']['fax'] = $company['fax'];

    $company_hash['website'] = array('url' => $company['website']);

    return $company_hash;
  }

  // Save company details as Dolibarr constants
  protected function persistLocalModel($company, $resource_hash) {
    global $db;

    // Map company name
    if($this->is_set($resource_hash['name'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM", $resource_hash['name']); }
    if($this->is_set($resource_hash['currency'])) { dolibarr_set_const($db, "MAIN_MONNAIE", $resource_hash['currency']); }
    if($this->is_set($resource_hash['tax_number'])) { dolibarr_set_const($db, "MAIN_INFO_TVAINTRA", $resource_hash['tax_number']); }

    // Map company name
    if($this->is_set($company_hash['address']['shipping']['line1'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ADDRESS", $company_hash['address']['shipping']['line1']); }
    if($this->is_set($company_hash['address']['shipping']['city'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TOWN", $company_hash['address']['shipping']['city']); }
    if($this->is_set($company_hash['address']['shipping']['postal_code'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ZIP", $company_hash['address']['shipping']['postal_code']); }
    if($this->is_set($company_hash['phone']['landline'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TEL", $company_hash['phone']['landline']); }
    if($this->is_set($company_hash['email']['address'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_MAIL", $company_hash['email']['address']); }
    if($this->is_set($company_hash['website']['url'])) { dolibarr_set_const($db, "MAIN_INFO_SOCIETE_WEB", $company_hash['website']['url']); }

    // Map Country and state
    $state = $company_hash['address']['shipping']['region'];
    $country = $company_hash['address']['shipping']['country'];

    // Map country
    if(isset($country)) {
      $country_code = mapCountryToISO3166($country);
      $country_name = mapISO3166ToCountry($country);

      $cpays = new Cpays($db);
      $cpays->fetch(false, $country_code);
      $s = $cpays->id.':'.$country_code.':'.$country_name;
      dolibarr_set_const($db, "MAIN_INFO_SOCIETE_COUNTRY", $s);

      // Map state
      if (isset($state)) {
        $sql = "SELECT rowid as state_id";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_departements as dep";
        $sql.= " JOIN ".MAIN_DB_PREFIX."llx_c_regions as reg ON (reg.rowid = dep.fk_region)";
        $sql.= " WHERE reg.fk_pays = ".$cpays->id;
        $sql.= " AND (dep.code_departement = '" .$state."' OR dep.nom = '" .$state."')";

        $result = $db->query($sql);
        if ($result) {
          $obj = $db->fetch_object($result);
          dolibarr_set_const($db, "MAIN_INFO_SOCIETE_STATE", $obj->rowid);
        }
      }
    }
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
