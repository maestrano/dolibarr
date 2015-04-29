<?php

/**
* Map Connec Customer Invocie representation to/from Dolibarr Facture
*/
class InvocieMapper extends TransactionMapper {
  public function __construct() {
    parent::__construct();

    $this->connec_entity_name = 'Invoice';
    $this->local_entity_name = 'Facture';
    $this->connec_resource_name = 'invoices';
    $this->connec_resource_endpoint = 'invoices';
  }

  // Map the Connec resource attributes onto the Dolibarr Invocie
  protected function mapConnecResourceToModel($invoice_hash, $invoice) {
    // TODO Map/Create Currency

    // Map invoice type
    $this->mapInvocieTypeToDolibarr($invoice_hash, $invoice);

    // Map invoice status
    $this->mapInvocieStatusToDolibarr($invoice_hash, $invoice);

    // Map invoice attributes
    if($this->is_set($invoice_hash['code'])) { $invoice->ref = $invoice_hash['code']; }
    if($this->is_set($invoice_hash['transaction_number'])) { $invoice->ref_ext = $invoice_hash['transaction_number']; }
    if($this->is_set($invoice_hash['transaction_date'])) { $invoice->date = $invoice_hash['transaction_date']; }
    if($this->is_set($invoice_hash['due_date'])) { $invoice->date = $invoice_hash['due_date']; }
    if($this->is_set($invoice_hash['public_note'])) { $invoice->note_public = $invoice_hash['public_note']; }
    if($this->is_set($invoice_hash['private_note'])) { $invoice->note_private = $invoice_hash['private_note']; }

    // Map Organization
    if($this->is_set($invoice_hash['organization_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($invoice_hash['organization_id'], 'ORGANIZATION', 'SOCIETE');
      if($mno_id_map) { $invoice->socid = $mno_id_map['app_entity_id']; }
    }

    // Map Contact
    if($this->is_set($invoice_hash['person_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($invoice_hash['person_id'], 'PERSON', 'CONTACTS');
      if($mno_id_map) { $invoice->add_contact($mno_id_map['app_entity_id']. 'BILLING'); }
    }

    // TODO: Map Invocie lines
    // // The class include/utils/InventoryUtils.php expects to find a $_REQUEST object with the transaction lines populated
    // if(is_null($_REQUEST)) { $_REQUEST = array(); }
    
    // if(!empty($invoice_hash['lines'])) {
    //   $_REQUEST['subtotal'] = $invoice_hash['amount']['total_amount'];
    //   $_REQUEST['total'] = $invoice_hash['amount']['total_amount'];
      
    //   // Force tax type to individual only for new Invocies
    //   if(!$this->is_set($_REQUEST['taxtype'])) { $_REQUEST['taxtype'] = 'individual'; }

    //   $line_count = 0;
    //   foreach($invoice_hash['lines'] as $invoice_line) {
    //     $line_count++;

    //     // Map item
    //     if(!empty($invoice_line['item_id'])) {
    //       $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($invoice_line['item_id'], 'PRODUCT');
    //       $product_id = $mno_id_map['app_entity_id'];
    //       $_REQUEST['hdnProductId'.$line_count] = $product_id;

    //       // Add tax to item
    //       ProductMapper::mapConnecTaxToProduct($invoice_line['tax_code_id'], $product_id);
    //     } else {
    //       // Set default service
    //       $service = $this->serviceMapper->defaultService();
    //       $_REQUEST['hdnProductId'.$line_count] = $service['serviceid'];

    //       // Add tax to item
    //       ProductMapper::mapConnecTaxToProduct($invoice_line['tax_code_id'], $service['serviceid']);
    //     }

    //     // Map attributes
    //     $_REQUEST['comment'.$line_count] = $invoice_line['description'];
    //     $_REQUEST['qty'.$line_count] = $invoice_line['quantity'];
    //     $_REQUEST['listPrice'.$line_count] = $invoice_line['unit_price']['net_amount'];

    //     if(isset($invoice_line['reduction_percent'])) {
    //       $_REQUEST['discount_type'.$line_count] = 'percentage';
    //       $_REQUEST['discount_percentage'.$line_count] = $invoice_line['reduction_percent'];
    //     } else {
    //       $_REQUEST['discount_type'.$line_count] = '';
    //       $_REQUEST['discount_percentage'.$line_count] = 0;
    //     }

    //     // Map Invocie Line Taxes
    //     $this->mapInvocieLineTaxes($invoice_line);
    //   }
    //   $_REQUEST['totalProductCount'] = $line_count;
    // }
  }

  // Map the Dolibarr Invocie to a Connec resource hash
  protected function mapModelToConnecResource($invoice) {
    global $adb;

    $invoice_hash = array();

    // Missing transaction lines are considered as deleted by Connec!
    $invoice_hash['opts'] = array('sparse' => false);

    // TODO!!
    
    // // Map attributes
    // if($this->is_set($invoice->column_fields['subject'])) { $invoice_hash['title'] = $invoice->column_fields['subject']; }
    // if($this->is_set($invoice->column_fields['notes'])) { $invoice_hash['public_note'] = $invoice->column_fields['notes']; }

    // // Map Organization
    // if($this->is_set($invoice->column_fields['account_id'])) {
    //   $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice->column_fields['account_id'], 'ACCOUNTS');
    //   if($mno_id_map) { $invoice_hash['organization_id'] = $mno_id_map['mno_entity_guid']; }
    // }

    // // Map Vendor
    // if($this->is_set($invoice->column_fields['vendor_id'])) {
    //   $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice->column_fields['vendor_id'], 'VENDORS');
    //   if($mno_id_map) { $invoice_hash['organization_id'] = $mno_id_map['mno_entity_guid']; }
    // }

    // // Map Contact
    // if($this->is_set($invoice->column_fields['contact_id'])) {
    //   $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice->column_fields['contact_id'], 'CONTACTS');
    //   if($mno_id_map) { $invoice_hash['person_id'] = $mno_id_map['mno_entity_guid']; }
    // }

    // // Map transaction lines
    // $invoice_hash['lines'] = array();
    // $result = $adb->pquery("SELECT * FROM vtiger_inventoryproductrel WHERE id = ?", array($invoice->id));
    // while($invoice_line_detail = $adb->fetch_array($result)) {
    //   $invoice_line = array();
    //   $productid = intval($invoice_line_detail['productid']);
    //   $line_number = intval($invoice_line_detail['sequence_no']);
    //   $quantity = intval($invoice_line_detail['quantity']);
    //   $listprice = floatval($invoice_line_detail['listprice']);
    //   $discount_percent = floatval($invoice_line_detail['discount_percent']);
    //   $discount_amount = floatval($invoice_line_detail['discount_amount']);
    //   $comment = $invoice_line_detail['comment'];
    //   $description = $invoice_line_detail['description'];

    //   // Dolibarr recreates the transaction lines on every save, so local IDs are not mappable
    //   // Use InvocieID#LineNumber instead
    //   $invoice_line_id = $invoice->id . "#" . $line_number;
    //   $mno_transaction_line_id = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice_line_id, "TRANSACTION_LINE");
    //   if($mno_transaction_line_id) {
    //     // Reuse Connec Invocie Line ID
    //     $invoice_line_id_parts = explode("#", $mno_transaction_line_id['mno_entity_guid']);
    //     $invoice_line['id'] = $invoice_line_id_parts[1];
    //   }

    //   $invoice_line['status'] = 'ACTIVE';
    //   $invoice_line['line_number'] = $line_number;
    //   $invoice_line['description'] = $comment;
    //   $invoice_line['quantity'] = $quantity;
    //   $invoice_line['reduction_percent'] = $discount_percent;
    //   $invoice_line['unit_price'] = array('net_amount' => $listprice);

    //   // Line applicable tax (limit to one)
    //   if($_REQUEST['taxtype'] == 'individual') {
    //     foreach ($invoice_line_detail as $key => $value) {
    //       if(preg_match('/^tax\d+/', $key) && !is_null($value) && $value > 0) {
    //         $tax = TaxMapper::getTaxByName($key);
    //         $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($tax['taxid'], 'TAXRECORD');
    //         if($mno_id_map) {
    //           $invoice_line['tax_code_id'] = $mno_id_map['mno_entity_guid'];
    //           $individual_tax = true;
    //           break;
    //         }
    //       }
    //     }
    //   }

    //   if($_REQUEST['taxtype'] == 'group') {
    //     foreach ($invoice_line_detail as $key => $value) {
    //       if(preg_match('/^tax\d+/', $key) && !is_null($value) && $value > 0) {
    //         $tax = TaxMapper::getTaxByName($key);
    //         $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($tax['taxid'], 'TAXRECORD');
    //         if($mno_id_map) {
    //           $invoice_line['tax_code_id'] = $mno_id_map['mno_entity_guid'];
    //           $individual_tax = true;
    //           break;
    //         }
    //       }
    //     }
    //   }

    //   // Map item id
    //   $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($productid, 'PRODUCTS');
    //   if($mno_id_map) { $invoice_line['item_id'] = $mno_id_map['mno_entity_guid']; }

    //   $invoice_hash['lines'][] = $invoice_line;
    // }

    return $invoice_hash;
  }

  protected function mapInvocieLineTaxes($line_hash) {
    global $adb;

    // Set all taxes to 0 by default
    $result = $adb->pquery("SELECT * FROM vtiger_inventorytaxinfo WHERE deleted = 0");
    $numrow = $adb->num_rows($result);
    for($k=0; $k < $numrow; $k++) {
      $taxname = $adb->query_result($result, $k, 'taxname');
      $request_tax_name = $taxname."_percentage".$line_hash['line_number'];
      $_REQUEST[$request_tax_name] = 0;
    }

    // Apply tax for this transaction line
    if($this->is_set($line_hash['tax_code_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($line_hash['tax_code_id'], 'TAXCODE');
      $tax = Settings_Vtiger_TaxRecord_Model::getInstanceById($mno_id_map['app_entity_id'], Settings_Vtiger_TaxRecord_Model::PRODUCT_AND_SERVICE_TAX);
      if(isset($tax)) {
        $request_tax_name = $tax->get('taxname')."_percentage".$line_hash['line_number'];
        $_REQUEST[$request_tax_name] = $tax->get('percentage');
      }
    }
  }

  // Set default invoice type to TYPE_STANDARD
  private function mapInvocieTypeToDolibarr(&$invoice_hash, $invoice) {
    if($this->is_new($invoice)) { $invoice->type = Facture::TYPE_STANDARD; }
  }

  // Map invoice status from Connec to Dolibarr
  // Connec status: DRAFT, AUTHORISED, PAID, VOIDED
  private function mapInvocieStatusToDolibarr($invoice_hash, $invoice) {
    switch($invoice_hash['status']) {
      case "PAID":
        $invoice->paye = 1;
        $invoice->statut = 2;
        break;
      case "AUTHORISED":
        $invoice->statut = 1;
        break;
      case "DRAFT":
        $invoice->statut = 0;
        break;
      case "VOIDED":
        $invoice->statut = 3;
        break;
    }
  }

  // Map invoice status from Dolibarr to Connec
  private function mapInvocieStatusToConnec(&$invoice_hash, $invoice) {
    if($invoice->paye == 1) {
      $invoice_hash['status'] = 'PAID';
    } else {
      $status_code = $invoice->statut;
      if($status_code == 0) {
        $invoice_hash['status'] = 'DRAFT';
      } else if($status_code == 1) {
        $invoice_hash['status'] = 'AUTHORISED';
      } else if($status_code == 2) {
        $invoice_hash['status'] = 'AUTHORISED';
      } else if($status_code == 3) {
        $invoice_hash['status'] = 'VOIDED';
      } 
    }
  }
}