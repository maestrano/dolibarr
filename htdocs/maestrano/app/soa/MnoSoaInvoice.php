<?php

/**
 * Mno Invoice Class
 */
class MnoSoaInvoice extends MnoSoaBaseInvoice
{
    protected $_local_entity_name = "facture";
    
    // DONE
    protected function pushId() 
    {
        MnoSoaLogger::debug(__FUNCTION__ . " start");
        
	$id = $this->getLocalEntityIdentifier();
	
	if (!empty($id)) {
	    MnoSoaLogger::debug(__FUNCTION__ . " this->_local_entity->id = " . json_encode($id));
	    $mno_id = $this->getMnoIdByLocalId($id);
            
	    if ($this->isValidIdentifier($mno_id)) {
                MnoSoaLogger::debug(__FUNCTION__ . " this->getMnoIdByLocalId(id) = " . json_encode($mno_id));
		$this->_id = $mno_id->_id;
	    }
	}
        
        MnoSoaLogger::debug(__FUNCTION__ . " end");
    }
    
    // DONE
    protected function pullId() 
    {
        MnoSoaLogger::debug(__FUNCTION__ . " start " . $this->_id);
        
	if (!empty($this->_id)) {            
	    $local_id = $this->getLocalIdByMnoId($this->_id);
            MnoSoaLogger::debug(__FUNCTION__ . " this->getLocalIdByMnoId(this->_id) = " . json_encode($local_id));
            
	    if ($this->isValidIdentifier($local_id)) {
                MnoSoaLogger::debug(__FUNCTION__ . " is STATUS_EXISTING_ID");
                $this->_local_entity = new Facture($this->_db);
                $this->_local_entity->fetch($local_id->_id);
		return constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
	    } else if ($this->isDeletedIdentifier($local_id)) {
                MnoSoaLogger::debug(__FUNCTION__ . " is STATUS_DELETED_ID");
                return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
            } else {
                MnoSoaLogger::debug(__FUNCTION__ . " is STATUS_NEW_ID");
		$this->_local_entity = new Facture($this->_db);
                $this->_local_entity->date_creation = strval(time());
		return constant('MnoSoaBaseEntity::STATUS_NEW_ID');
	    }
	}
        
        MnoSoaLogger::debug(__FUNCTION__ . " return STATUS_ERROR");
        return constant('MnoSoaBaseEntity::STATUS_ERROR');
    }
    
    // DONE
    protected function pushType() 
    {
        MnoSoaLogger::debug(__FUNCTION__ . " local_entity = " . json_encode($this->_local_entity));
        $this->_type = "CUSTOMER";
	MnoSoaLogger::debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullType() 
    {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
        //! 0=Standard invoice, 1=Replacement invoice, 2=Credit note invoice, 3=Deposit invoice, 4=Proforma invoice
        switch ($this->_local_entity->type) {
            case "CUSTOMER": $this->_local_entity->type = 0; break;
            case "SUPPLIER": $this->_local_entity->type = 1; break;
            default: $this->_local_entity->type = 0; break;
        }
                
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushDates() {
	MnoSoaLogger::debug(__FUNCTION__ . " start ");
        $issue_date = strval($this->_local_entity->date * 1000);
        $date_lim_reglement = strval($this->_local_entity->date_lim_reglement * 1000);
        $this->_dates->issue = $this->push_set_or_delete_value($issue_date);
        $this->_dates->due = $this->push_set_or_delete_value($date_lim_reglement);
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullDates() {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
        $created = strval(round(intval($this->_dates->issue) / 1000));
        $due = strval(round(intval($this->_dates->due) / 1000));
        $this->_local_entity->date = $this->pull_set_or_delete_value($created);
        $this->_local_entity->date_lim_reglement = $this->pull_set_or_delete_value($due);
	MnoSoaLogger::debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushOrganization() {
	MnoSoaLogger::debug(__FUNCTION__ . " start ");
        if (empty($this->_local_entity->socid)) { return 0; }
        $mno_id = $this->getMnoIdByLocalIdName($this->_local_entity->socid, "SOCIETE");
        if (!$this->isValidIdentifier($mno_id)) { return 0; }
        $this->_organization->id = $mno_id->_id;
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
        return 1;
    }
    
    // DONE
    protected function pullOrganization() {
        if (empty($this->_organization->id)) { return 0; }
        $local_id = $this->getLocalIdByMnoIdName($this->_organization->id, "ORGANIZATIONS");
        if (!$this->isValidIdentifier($local_id)) { return 0; }
        $this->_local_entity->socid = $local_id->_id;
        
        return 1;
    }
    
    // DONE
    protected function pushLines() {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
        if (empty($this->_local_entity->lines)) { return 0; } 
        $iter = 1;
        foreach ($this->_local_entity->lines as $row) {
            $mno_id = $this->getMnoIdByLocalIdName($row->fk_product, "ITEMS");
            if (!$this->isValidIdentifier($mno_id)) { $iter++; continue; }
            $this->_lines->{line . "$iter"}->item->id = $mno_id->_id;
            $this->_lines->{line . "$iter"}->item->description = $row->desc;
            $this->_lines->{line . "$iter"}->item->startDate = $this->push_set_or_delete_value(strval($row->date_start * 1000));
            $this->_lines->{line . "$iter"}->item->endDate = $this->push_set_or_delete_value(strval($row->date_end * 1000));
            $this->_lines->{line . "$iter"}->item->quantity = strval($row->qty);
            $this->_lines->{line . "$iter"}->item->price = strval($row->subprice);
            $this->_lines->{line . "$iter"}->item->currency = $this->getMainCurrency();
            
            $this->_lines->{line . "$iter"}->discount->rate = strval($row->remise_percent);
            $this->_lines->{line . "$iter"}->discount->overrideAmount = strval($row->remise_percent);
            
            $this->_lines->{line . "$iter"}->tax->rate = strval($row->tva_tx);
            $iter++;
        }
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
        return 1;
    }
    
    // DONE
    protected function pullLines() {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
        if (empty($this->_lines)) { return 1; }
        if (empty($this->_local_entity->id)) { return 1; }
        
        for ($index=1; !empty($this->_lines->{"line".$index}); $index++) {
            MnoSoaLogger::debug("local_entity->id=" . $this->_local_entity->id . " index=" . $index);
            $line = $this->_lines->{"line".$index};
            if (empty($line->item->id)) { continue; }
            MnoSoaLogger::debug("after item id check");
            $line_currency = $this->pull_set_or_delete_value($line->item->currency);
            if ($line_currency != $this->getMainCurrency()) { continue; }
            MnoSoaLogger::debug("after line currency check");
            $local_product_id = $this->getLocalIdByMnoIdName($line->item->id, "ITEMS");
            if (!$this->isValidIdentifier($local_product_id)) { continue; }
            MnoSoaLogger::debug("after product id check");
            
            $obj = new FactureLigne($this->_db);
            $ret_status = $obj->fetch_by_order($this->_local_entity->id, $index);
            
            if (!empty($ret_status)) {
                MnoSoaLogger::debug("ret_status=" . $ret_status . " index=" . $index);
                if ($obj->product_type !== 0 && empty($obj->product_type)) { $obj->product_type = 0; }
            } else {
                $obj->fk_facture = $this->_local_entity->id;
                $obj->product_type = 0;
            }
            
            $obj->fk_product = $local_product_id->_id;
            $obj->desc = $this->pull_set_or_delete_value($line->item->description);

            $start_date = (empty($line->item->date_start)) ? 0 : round(intval($this->pull_set_or_delete_value($line->item->startDate)) / 1000);
            $obj->date_start = $this->pull_set_or_delete_value($start_date);

            $end_date = (empty($line->item->date_end)) ? 0 : round(intval($this->pull_set_or_delete_value($line->item->endDate)) / 1000);
            $obj->date_end = $this->pull_set_or_delete_value($end_date);

            $obj->qty = $this->pull_set_or_delete_value($line->item->quantity);
            
            if (!empty($line->item->overrideAmount)) {
                $obj->subprice = $line->item->overrideAmount;
            } else if (!empty($line->item->price) && !empty($line->item->quantity)) {
                $obj->subprice = floatval($line->item->price) * floatval($line->item->quantity);
            } else {
                $obj->subprice = 0;
            }

            if (!empty($line->tax) && !empty($line->tax->rate) && !empty(trim($line->tax->rate))) {
                $obj->tva_tx = $this->pull_set_or_delete_value($line->tax->rate);
            } else {
                $obj->tva_tx = 0;
            }
            
            if (!empty($line->discount) && !empty($line->discount) && !empty(trim($line->discount->rate))) {
                $obj->remise_percent = $this->pull_set_or_delete_value($line->discount->rate);
            } else {
                $obj->remise_percent = 0;
            }
            
            $obj->rang = $index;
            
            if (!empty($obj->remise_percent)) {
                $obj->total_ht = $obj->subprice * (1.00 - ($obj->remise_percent / 100.00));
            } else {
                $obj->total_ht = $obj->subprice;
            }
            
            $obj->total_tva = $obj->total_ht * ($obj->tva_tx / 100.00);
            $obj->total_ttc = $obj->total_ht + $obj->total_tva;
            
            if (!empty($ret_status)) {
                $obj->update();
            } else {
                $obj->insert();
            }
        }
        
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
        return 1;
    }
    
    protected function pushTotals() {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
        
        $this->_totals->net = strval($this->push_set_or_delete_value($this->_local_entity->total_ht));
        $this->_totals->tax = strval($this->push_set_or_delete_value($this->_local_entity->total_tva));
        $this->_totals->gross = strval($this->push_set_or_delete_value($this->_local_entity->total_ttc));
        $this->_totals->currency = $this->push_set_or_delete_value($this->getMainCurrency());
        if (empty($this->_totals->net) || empty($this->_totals->tax) || empty($this->_totals->gross)) { return 0; }
        return 1;
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
    }
    
    protected function pullTotals() {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
        $this->_local_entity->total_ht = floatval($this->pull_set_or_delete_value($this->_totals->net));
        $this->_local_entity->total_tva = floatval($this->pull_set_or_delete_value($this->_totals->tax));
        $this->_local_entity->total_ttc = floatval($this->pull_set_or_delete_value($this->_totals->gross));
        MnoSoaLogger::debug("totals->currency=" . $this->_totals->currency . " this->getMainCurrency()=" . $this->getMainCurrency());
        if ($this->_totals->currency != $this->getMainCurrency()) { return 0; }
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
        return 1;
    }
    
    // DONE
    protected function pushNotes() {
        $this->_notes->public->note1 = $this->push_set_or_delete_value ($this->_local_entity->note_public);
        $this->_notes->private->note1 = $this->push_set_or_delete_value ($this->_local_entity->note_private);
    }
    
    // DONE
    protected function pullNotes() {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
        
        $this->_local_entity->note_public = $this->pull_set_or_delete_value($this->_notes->public->note1);
        $this->_local_entity->note_private = $this->pull_set_or_delete_value($this->_notes->private->note1);
        
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushStatus() {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
        
        /*
        //! 0=draft,
	//! 1=validated (need to be paid),
	//! 2=classified paid partially (close_code='discount_vat','badcustomer') or completely (close_code=null),
	//! 3=classified abandoned and no payment done (close_code='badcustomer','abandon' or 'replaced'
         */
        MnoSoaLogger::debug(__FUNCTION__ . " statut " . $this->_local_entity->statut);
        if ($this->_local_entity->statut !== '0' && empty($this->_local_entity->statut)) { return; }
        $status = "";
        switch ($this->_local_entity->statut) {
            case '0': $status = 'DRAFT'; break;
            case '1': $status = 'AUTHORISED'; break;
            case '2': $status = 'PAID'; break;
            case '3': $status = 'DELETED'; break;            
        
            default: $status = "DRAFT";
        }
        $this->_status = $this->push_set_or_delete_value($status);
        
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullStatus() {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
	
        if (empty($this->_status)) { return; }
        
        $status = $this->pull_set_or_delete_value($this->_status);
        
        switch ($status) {
            case "DRAFT": $this->_local_entity->statut = '0'; break;
            case "AUTHORISED": $this->_local_entity->statut = '1'; break;
            case "PAID": $this->_local_entity->statut = '2'; break;
            case "DELETED": $this->_local_entity->statut = '3'; break;
            
            default: $this->_local_entity->statut = '0';
        }
        
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function saveLocalEntity($push_to_maestrano, $status) {
        MnoSoaLogger::debug(__FUNCTION__ . " start ");
        // status = 2 update status = 1 new
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            MnoSoaLogger::debug(json_encode($this->_local_entity));
            $user = (object) array();
            $user->id = "1";
            $retval = $this->_local_entity->create($user,0,$this->_local_entity->date_lim_reglement,false);
            MnoSoaLogger::debug("retval=" . $retval);
            MnoSoaLogger::debug(__FUNCTION__ . " create ");
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            MnoSoaLogger::debug(__FUNCTION__ . " local entity = " . json_encode($this->_local_entity));
            $this->_local_entity->update($user,0,false);
            MnoSoaLogger::debug(__FUNCTION__ . " update ");
        }
        $this->pullLines();
        $this->_local_entity->update_price(1);
        MnoSoaLogger::debug(__FUNCTION__ . " end ");
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
}

?>