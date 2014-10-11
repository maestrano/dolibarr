<?php

/**
 * Mno Company Interface
 */
class MnoSoaBaseCompany extends MnoSoaBaseEntity
{
    protected $_mno_entity_name = "company";
    protected $_create_rest_entity_name = "company";
    protected $_create_http_operation = "POST";
    protected $_update_rest_entity_name = "company";
    protected $_update_http_operation = "POST";
    protected $_receive_rest_entity_name = "company";
    protected $_receive_http_operation = "GET";
    protected $_delete_rest_entity_name = "company";
    protected $_delete_http_operation = "DELETE";    
    
    protected $_name;
    protected $_currency;
    protected $_logo;

    protected function pushCompany() {
      throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoCompany class!');
    }
    
    protected function pullCompany() {
      throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoCompany class!');
    }
  
    /**
    * Build a Maestrano Company message
    */
    protected function build() {        
      MnoSoaLogger::debug(__FUNCTION__ . " start");

      $this->pushCompany();

      if ($this->_name != null) { $msg['company']->name = $this->_name; }
      if ($this->_currency != null) { $msg['company']->currency = $this->_currency; }
      if ($this->_logo != null) { $msg['company']->logo = $this->_logo; }

    	$result = json_encode($msg['company']);
      MnoSoaLogger::debug(__FUNCTION__ . " result = " . $result);

      return $result;
    }
    
    /**
    * Persists the Maestrano Company from message
    */
    protected function persist($mno_entity) {
      MnoSoaLogger::debug(__FUNCTION__ . " start");
      
      if (!empty($mno_entity->company)) {
          $mno_entity = $mno_entity->company;
      }
              
      if (!empty($mno_entity->id)) {
          $this->set_if_array_key_has_value($this->_name, 'name', $mno_entity);
          $this->set_if_array_key_has_value($this->_currency, 'currency', $mno_entity);
          $this->set_if_array_key_has_value($this->_logo, 'logo', $mno_entity);

          $this->saveLocalEntity(false);
      }
      MnoSoaLogger::debug(__FUNCTION__ . " end");
    }
}

?>