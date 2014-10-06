<?php

/**
 * Mno Invoice Class
 */
class MnoSoaInvoice extends MnoSoaBaseInvoice
{
    protected $_local_entity_name = "INVOICE";
    public $_is_delete;
    
    protected function pushInvoice()
    {
        $id = $this->getLocalEntityIdentifier();
        if (empty($id)) { return; }
        $mno_id = $this->getMnoIdByLocalIdName($id, $this->_local_entity_name);
        $this->_id = ($this->isValidIdentifier($mno_id)) ? $mno_id->_id : null;
        $this->_transaction_number = $this->push_set_or_delete_value($this->_local_entity->ref);
        $this->_transaction_date = $this->push_set_or_delete_value($this->_local_entity->date);
        $this->_due_date = $this->push_set_or_delete_value($this->_local_entity->date_lim_reglement);
        $this->_amount = $this->push_set_or_delete_value($this->_local_entity->total_ttc);

        $local_organization_id = $this->push_set_or_delete_value($this->_local_entity->client->id);
        $mno_id = $this->getMnoIdByLocalIdName($this->_local_entity->client->id, "SOCIETE");
        $this->_organization_id = $mno_id->_id;
    }
    
    protected function pullInvoice()
    {
        if (empty($this->_id)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }
        
        $local_id = $this->getLocalIdByMnoIdName($this->_id, $this->_mno_entity_name);
        if ($this->isDeletedIdentifier($local_id)) { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
        $mno_status_format = strtoupper($this->pull_set_or_delete_value($this->_status));
        
        $this->_local_entity = new Facture($this->_db);
        if ($this->isValidIdentifier(($local_id))) { 
            $return_status = constant('MnoSoaBaseEntity::STATUS_EXISTING_ID'); 
            $this->_local_entity->fetch($local_id->_id);
            
            if ($mno_status_format == "INACTIVE") { 
                $this->_local_entity->delete(false);
                $this->deleteIdMapEntryName($local_id, $this->_local_entity_name);
                return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
            }
        } else {
            if ($mno_status_format == "INACTIVE") { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
            $return_status = constant('MnoSoaBaseEntity::STATUS_NEW_ID');
            // TODO: Any default attributes
            // $this->_local_entity->country_id = '11';
        }
        
        if (empty($this->_transaction_number)) {
            # Generate a random invoice reference if missing
            $this->_local_entity->ref = 'INV-' . rand();
        } else {
            $this->_local_entity->ref = $this->pull_set_or_delete_value($this->_transaction_number);
        }

        $this->_local_entity->date = $this->pull_set_or_delete_value($this->_transaction_date);
        $this->_local_entity->date_lim_reglement = $this->pull_set_or_delete_value($this->_due_date);
        $this->_local_entity->total_ttc = $this->pull_set_or_delete_value($this->_amount);

        // TODO: Map other attributes
        
        return $return_status;
    }
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
        MnoSoaLogger::debug("start status=$status");
        
        $user = (object) array();
        $user->id = "1";
        
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            $local_id = $this->_local_entity->create(false);
            if ($local_id > 0) {
                $this->addIdMapEntryName($local_id, $this->_local_entity_name, $this->_id, $this->_mno_entity_name);
            }
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            $this->_local_entity->update($user, false);
        }
    }
    
    public function getLocalEntityIdentifier() {
        return $this->_local_entity->id;
    }
}

?>