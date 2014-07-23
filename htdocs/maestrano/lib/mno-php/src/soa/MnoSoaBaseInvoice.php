<?php

/**
 * Mno Invoice Interface
 */
class MnoSoaBaseInvoice extends MnoSoaBaseEntity
{
    protected $_mno_entity_name = "invoices";
    protected $_create_rest_entity_name = "invoices";
    protected $_create_http_operation = "POST";
    protected $_update_rest_entity_name = "invoices";
    protected $_update_http_operation = "POST";
    protected $_receive_rest_entity_name = "invoices";
    protected $_receive_http_operation = "GET";
    protected $_delete_rest_entity_name = "invoices";
    protected $_delete_http_operation = "DELETE";    
    
    protected $_id;
    protected $_type;
    protected $_dates;
    protected $_organization;
    protected $_lines;
    protected $_totals;
    protected $_notes;
    protected $_status;

    protected function pushId() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pullId() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pushType() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pullType() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pushDates() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pullDates() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pushOrganization() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pullOrganization() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pushLines() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pullLines() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pushTotals() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pullTotals() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pushNotes() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pullNotes() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pushStatus() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function pullStatus() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    public function getLocalEntityIdentifier() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoInvoice class!');
    }
    
    /**
    * Build a Maestrano invoice message
    * 
    * @return Invoice the invoice json object
    */
    protected function build() {        
	MnoSoaLogger::debug(__FUNCTION__ . " start build function");
	$this->pushId();
	MnoSoaLogger::debug(__FUNCTION__ . " after Id");
	$this->pushType();
	MnoSoaLogger::debug(__FUNCTION__ . " after Type");
        $ret_org = $this->pushOrganization();
	MnoSoaLogger::debug(__FUNCTION__ . " after Organization");
	$this->pushDates();
	MnoSoaLogger::debug(__FUNCTION__ . " after Date");
        $ret_lines = $this->pushLines();
        MnoSoaLogger::debug(__FUNCTION__ . " after Lines");
        $ret_totals = $this->pushTotals();
        MnoSoaLogger::debug(__FUNCTION__ . " after Totals");
	$this->pushNotes();
	MnoSoaLogger::debug(__FUNCTION__ . " after Notes");
	$this->pushStatus();
	MnoSoaLogger::debug(__FUNCTION__ . " after Status");
        
        if ($this->_type != null) { $msg['invoice']->type = $this->_type; }
        if ($this->_organization != null) { $msg['invoice']->organization = $this->_organization; }
        if ($this->_dates != null) { $msg['invoice']->dates = $this->_dates; }
        if ($this->_lines != null) { $msg['invoice']->lines = $this->_lines; }
        if ($this->_totals != null) { $msg['invoice']->totals = $this->_totals; }
        if ($this->_notes != null) { $msg['invoice']->notes = $this->_notes; }
        if ($this->_status != null) { $msg['invoice']->status = $this->_status; }
	
	MnoSoaLogger::debug(__FUNCTION__ . " after creating message array");
	$result = json_encode($msg['invoice']);
	
	MnoSoaLogger::debug(__FUNCTION__ . " result = " . $result);
	
        if (empty($ret_org) || empty($ret_lines) || empty($ret_totals)) { return null; }
        
	return $result;
    }
    
    protected function persist($mno_entity) {
        MnoSoaLogger::debug("start");
        
        if (!empty($mno_entity->invoice)) {
            $mno_entity = $mno_entity->invoice;
        }
                
        if (!empty($mno_entity->id)) {
            $this->_id = $mno_entity->id;
            MnoSoaLogger::debug(__FUNCTION__ . " after id");
            $this->set_if_array_key_has_value($this->_type, 'type', $mno_entity);
            MnoSoaLogger::debug(__FUNCTION__ . " after name");
            $this->set_if_array_key_has_value($this->_organization, 'organization', $mno_entity);
            $this->set_if_array_key_has_value($this->_dates, 'dates', $mno_entity);
            $this->set_if_array_key_has_value($this->_lines, 'lines', $mno_entity);
            $this->set_if_array_key_has_value($this->_totals, 'totals', $mno_entity);
            $this->set_if_array_key_has_value($this->_notes, 'notes', $mno_entity);
            $this->set_if_array_key_has_value($this->_status, 'status', $mno_entity);

            MnoSoaLogger::debug(__FUNCTION__ . " persist invoice id = " . $this->_id);

            $status = $this->pullId();
            MnoSoaLogger::debug(__FUNCTION__ . " after id");
            $is_new_id = $status == constant('MnoSoaBaseEntity::STATUS_NEW_ID');
            $is_existing_id = $status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
            
            if ($is_new_id || $is_existing_id) {
                $this->pullType();
                $this->pullDates();
                $ret_org = $this->pullOrganization();
                $ret_totals = $this->pullTotals();
                $this->pullNotes();
                $this->pullStatus();

                MnoSoaLogger::debug(__FUNCTION__ . " ret_org=" . $ret_org . " ret_totals=" . $ret_totals);
                if (empty($ret_org) || empty($ret_totals)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }
                $this->saveLocalEntity(false, $status);
            }

            $local_entity_id = $this->getLocalEntityIdentifier();
            $mno_entity_id = $this->_id;
            
            if ($is_new_id && !empty($local_entity_id) && !empty($mno_entity_id)) {
                $this->addIdMapEntry($local_entity_id, $mno_entity_id);
            }
        }
        MnoSoaLogger::debug(__FUNCTION__ . " end");
    }
}

?>