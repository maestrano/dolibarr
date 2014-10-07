<?php

/**
 * Mno Invoice Class
 */
class MnoSoaInvoice extends MnoSoaBaseInvoice
{
  protected $_local_entity_name = "INVOICE";

  protected function pushInvoice()
  {
    $id = $this->getLocalEntityIdentifier();
    if (empty($id)) { return; }

    $mno_id = $this->getMnoIdByLocalIdName($id, $this->_local_entity_name);
    $this->_id = ($this->isValidIdentifier($mno_id)) ? $mno_id->_id : null;
    $this->_transaction_number = $this->push_set_or_delete_value($this->_local_entity->ref);
    $this->_transaction_date = $this->push_set_or_delete_value($this->_local_entity->date);
    $this->_due_date = $this->push_set_or_delete_value($this->_local_entity->date_lim_reglement);
    $this->_amount = floatval($this->push_set_or_delete_value($this->_local_entity->total_ttc));
    $this->_currency = $this->getMainCurrency();

    // Pull Organization ID
    $mno_id = $this->getMnoIdByLocalIdName($this->_local_entity->socid, "SOCIETE");
    $this->_organization_id = $mno_id->_id;

    // Pull Invoice lines
    $this->_invoice_lines = array();
    if(!empty($this->_local_entity->lines)) {
      foreach($this->_local_entity->lines as $line) {
        $invoice_line = array();
        
        // Find mno id if already exists
        $active = true;
        $mno_entity = $this->getMnoIdByLocalIdName($line->rowid, "INVOICE_LINE");
        if($this->isDeletedIdentifier($mno_entity)) {
          $invoice_line_mno_id = $mno_entity->_id;
          $active = false;
        } else if (!$this->isValidIdentifier($mno_entity)) {
          // Generate and save ID
          $invoice_line_mno_id = uniqid();
          $this->_mno_soa_db_interface->addIdMapEntry($line->rowid, "INVOICE_LINE", $invoice_line_mno_id, "INVOICE_LINE");
        } else {
          $invoice_line_mno_id = $mno_entity->_id;
        }

        // Pull Product
        $local_product_id = $this->push_set_or_delete_value($line->fk_product);
        $mno_id = $this->getMnoIdByLocalIdName($line->fk_product, "ITEMS");
        $invoice_line['item']->id = $mno_id->_id;

        // Pull attributes
        $invoice_line['id'] = $invoice_line_mno_id;
        $invoice_line['lineNumber'] = intval($line->rang);
        $invoice_line['amount'] = floatval($line->total_ttc);
        $invoice_line['quantity'] = intval($line->qty);
        $invoice_line['unitPrice'] = intval($line->subprice);
        $invoice_line['reductionPercent'] = intval($line->remise_percent);
        $invoice_line['taxRate'] = intval($line->tva_tx);
        $invoice_line['status'] = $active ? 'ACTIVE' : 'INACTIVE';

        $this->_invoice_lines[$invoice_line_mno_id] = $invoice_line;
      }
    }
  }

  protected function pullInvoice()
  {
    MnoSoaLogger::debug("start pullInvoice for " . json_encode($this->_id));

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
      // Generate a random invoice reference if missing
      $this->_local_entity->ref = 'INV-' . rand();
    } else {
      $this->_local_entity->ref = $this->pull_set_or_delete_value($this->_transaction_number);
    }

    $this->_local_entity->date = $this->pull_set_or_delete_value($this->_transaction_date);
    $this->_local_entity->date_lim_reglement = $this->pull_set_or_delete_value($this->_due_date);
    $this->_local_entity->total_ttc = $this->pull_set_or_delete_value($this->_amount);

    // Map local organization
    $local_id = $this->getLocalIdByMnoIdName($this->_organization_id, "organizations");
    if ($this->isValidIdentifier($local_id)) {
      MnoSoaLogger::debug(__FUNCTION__ . " local_id = " . json_encode($local_id));
      $this->_local_entity->socid = $local_id->_id;
    } else if ($this->isDeletedIdentifier($local_id)) {
      // do not update
      return;
    } else {
      // Fetch remote Organization if missing
      $notification->entity = "organizations";
      $notification->id = $this->_role->organization->id;
      $organization = new MnoSoaOrganization($this->_db);   
      $status = $organization->receiveNotification($notification);
      if ($status) {
        $this->_local_entity->socid = $organization->_local_entity->id;
        $this->_local_entity->poste = $this->pull_set_or_delete_value($this->_role->title, "");
      }
    }

    // TODO: Map other attributes

    MnoSoaLogger::debug("Returning entity " . json_encode($this->_local_entity));

    return $return_status;
  }

  protected function saveLocalEntity($push_to_maestrano, $status) {
    MnoSoaLogger::debug("start status=$status");

    $user = (object) array();
    $user->id = "1";

    if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
      $local_id = $this->_local_entity->create($user, 0, 0, false);
      if ($local_id > 0) {
        $this->addIdMapEntryName($local_id, $this->_local_entity_name, $this->_id, $this->_mno_entity_name);
        $invoice_local_id = $local_id->_id;
      }
    } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
      $this->_local_entity->update($user, 0, false);
      $invoice_local_id = $this->_local_entity->id;
    }

    $mno_invoice_line = new MnoSoaInvoiceLine($this->db);
    $mno_invoice_line->saveLocalEntity($invoice_local_id, $this->_invoice_lines, $push_to_maestrano);
  }

  public function getLocalEntityIdentifier() {
    return $this->_local_entity->id;
  }

  protected function getMainCurrency() {
    global $conf;
    return $conf->currency;
  }
}

?>