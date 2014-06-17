<?php

/**
 * Mno Organization Class
 */
class MnoSoaItem extends MnoSoaBaseItem
{
    protected $_local_entity_name = "ITEMS";
    // PRODUCT OR SERVICE
    public $_local_element_type;
    public $_is_delete;
    
    protected function pushItem()
    {
        // PUSH ID
        $id = $this->getLocalEntityIdentifier();
        if (empty($id)) { return; }
        $mno_id = $this->getMnoIdByLocalIdName($id, $this->_local_entity_name);
        $this->_id = ($this->isValidIdentifier($mno_id)) ? $mno_id->_id : null;
        // PUSH CODE
        $this->_code = $this->push_set_or_delete_value($this->_local_entity->ref);
        // PUSH NAME
        $this->_name = $this->push_set_or_delete_value($this->_local_entity->libelle);
        // PUSH DESCRIPTION
        $this->_description = $this->push_set_or_delete_value($this->_local_entity->description);
        // PUSH STATUS
        $this->_status = ($this->_is_delete) ? "INACTIVE" : "ACTIVE";
        
        switch ($this->_local_element_type) {
            case "PRODUCT":
                // PUSH UNIT
                $this->_type = $this->mapLocalProductNatureToMnoType($this->_local_entity->finished);
                break;
            case "SERVICE":
                $this->_type = "SERVICE";
                break;
        }
    }
    
    protected function pullItem()
    {
        $return_status = null;
        // PULL SALE
        if (empty($this->_id)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }
        
        $mno_type_format = strtoupper($this->pull_set_or_delete_value($this->_type));
        
        switch ($mno_type_format) {
            case "PURCHASED": $this->_local_element_type = "PRODUCT"; break;
            case "MANUFACTURED": $this->_local_element_type = "PRODUCT"; break;
            case "SERVICE": $this->_local_element_type = "SERVICE"; break;
            default: break;
        }
        
        $local_id = $this->getLocalIdByMnoIdName($this->_id, $this->_mno_entity_name);
        if ($this->isDeletedIdentifier($local_id)) { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
        $mno_status_format = strtoupper($this->pull_set_or_delete_value($this->_status));        
        
        MnoSoaLogger::debug("before local entity");
        $this->_local_entity = new Product($this->_db);
        MnoSoaLogger::debug("after local entity");
        if ($this->isValidIdentifier(($local_id))) { 
            $return_status = constant('MnoSoaBaseEntity::STATUS_EXISTING_ID'); 
            $this->_local_entity->fetch($local_id->_id);
            
            if ($mno_status_format == 'INACTIVE') { 
                $this->_local_entity->delete($local_id->_id, false);
                $this->deleteIdMapEntryName($local_id, $this->_local_entity_name);
                return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); 
            }
        } else {
            $return_status = constant('MnoSoaBaseEntity::STATUS_NEW_ID');
            if ($mno_status_format == 'INACTIVE') { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
        }
        
        $this->_local_entity->ref = $this->pull_set_or_delete_value($this->_code);
        $this->_local_entity->label = $this->_local_entity->libelle = $this->pull_set_or_delete_value($this->_name);
        $this->_local_entity->description = $this->pull_set_or_delete_value($this->_description);
        $this->_local_entity->type = '0';
        if ($this->_local_element_type == "PRODUCT") {
            $this->_local_entity->finished = $this->mapMnoTypeToLocalProductNature($this->_type);
        } else if ($this->_local_element_type == "SERVICE") {
            MnoSoaLogger::debug("is service");
            $this->_local_entity->type = '1';
        }
        
        return $return_status;
    }
    
    protected function pushParent()
    {
        // DO NOTHING
    }
    
    protected function pullParent()
    {
        // DO NOTHING
    }
    
    protected function pushSale()
    {   
        // DO NOTHING
    }
    
    protected function pullSale()
    {
        // DO NOTHING
    }
    
    protected function pushPurchase()
    {
        // DO NOTHING
    }
    
    protected function pullPurchase()
    {   
        // DO NOTHING
    }
    
    // DONE
    protected function saveLocalEntity($push_to_maestrano, $status) {
        MnoSoaLogger::debug("start");
        $user = (object) array();
        $user->id = "1";
        $temp = json_encode($this->_local_entity);
        MnoSoaLogger::debug("this->_local_entity={$temp}");
        
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            MnoSoaLogger::debug("new id");
            $local_id = $this->_local_entity->create($user,true,false);
            if ($local_id > 0) {
                $this->addIdMapEntryName($local_id, $this->_local_entity_name, $this->_id, $this->_mno_entity_name);
            }
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            MnoSoaLogger::debug("existing id");
            $this->_local_entity->update($this->getLocalEntityIdentifier(), $user, true, 'update', $push_to_maestrano);
        }
    }
    
    // DONE
    public function getLocalEntityIdentifier() {
        return $this->_local_entity->id;
    }
    
    protected function getMainCurrency()
    {
        global $conf;
        return $conf->currency;
    }
    
    protected function mapLocalProductNatureToMnoType($local_product_nature)
    {
        switch ($local_product_nature) {
            case "0": return "PURCHASED";
            case "1": return "MANUFACTURED";
        }
        
        return null;
    }
    
    protected function mapMnoTypeToLocalProductNature($mno_item_type)
    {
        $mno_item_type_format = $this->pull_set_or_delete_value($mno_item_type);
        
        switch ($mno_item_type_format) {
            case "PURCHASED": return "0";
            case "MANUFACTURED": return "1";
        }
        
        return null;
    }
}

?>