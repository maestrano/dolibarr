<?php

/**
* Map Connec Customer Invoice Line representation to/from Dolibarr FactureLigne
*/
class InvoiceLineMapper extends BaseMapper {
  private $invoice = null;

  public function __construct($invoice) {
    parent::__construct();

    $this->connec_entity_name = 'InvoiceLine';
    $this->local_entity_name = 'FactureLigne';
    $this->connec_resource_name = 'invoices/lines';
    $this->connec_resource_endpoint = 'invoices/lines';

    $this->invoice = $invoice;
  }

  // Invoice Line ID includes Invoice ID for unicity
  protected function getId($invoice_line) {
    return $invoice->id . "#" . $invoice_line->id;
  }

  // Return a local FactureLigne by id
  protected function loadModelById($local_id) {
    global $db;

    $invoice_line = new $this->local_entity_name($db);
    $invoice_line->fetch($local_id);
    return $invoice_line;
  }

  // Map the Connec resource attributes onto the Dolibarr FactureLigne
  protected function mapConnecResourceToModel($invoice_line_hash, $invoice_line) {
    // Line attributes
    $invoice_line->fk_facture = $this->invoice->id;
    $invoice_line->rang = $invoice_line_hash['line_number'];
    $invoice_line->desc = $invoice_line_hash['description'];
    $invoice_line->tva_tx = $invoice_line_hash['total_price']['tax_rate'];
    if($this->is_set($invoice_line_hash['quantity'])) { $invoice_line->qty = $invoice_line_hash['quantity']; }
    
    // Line amounts
    $invoice_line->total_ht = $invoice_line_hash['total_price']['net_amount'];
    $invoice_line->total_tva = $invoice_line_hash['total_price']['tax_amount'];
    $invoice_line->total_ttc = $invoice_line_hash['total_price']['total_amount'];
    $invoice_line->remise_percent = $invoice_line_hash['reduction_percent'];
    $invoice_line->subprice = $invoice_line_hash['unit_price']['net_amount'];

    // Map item
    if(!empty($invoice_line_hash['item_id'])) {
      $mno_id_map = MnoIdMap::findMnoIdMapByMnoIdAndEntityName($invoice_line_hash['item_id'], 'PRODUCT');
      $invoice_line->fk_product = $mno_id_map['app_entity_id'];
    }
  }

  // Map the Dolibarr Invoice to a Connec resource hash
  protected function mapModelToConnecResource($invoice_line) {
    global $adb;

    $invoice_line_hash = array();

    // TODO!!
    
    // // Map attributes
    // if($this->is_set($invoice_line->column_fields['subject'])) { $invoice_line_hash['title'] = $invoice_line->column_fields['subject']; }
    // if($this->is_set($invoice_line->column_fields['notes'])) { $invoice_line_hash['public_note'] = $invoice_line->column_fields['notes']; }

    // // Map Organization
    // if($this->is_set($invoice_line->column_fields['account_id'])) {
    //   $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice_line->column_fields['account_id'], 'ACCOUNTS');
    //   if($mno_id_map) { $invoice_line_hash['organization_id'] = $mno_id_map['mno_entity_guid']; }
    // }

    // // Map Vendor
    // if($this->is_set($invoice_line->column_fields['vendor_id'])) {
    //   $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice_line->column_fields['vendor_id'], 'VENDORS');
    //   if($mno_id_map) { $invoice_line_hash['organization_id'] = $mno_id_map['mno_entity_guid']; }
    // }

    // // Map Contact
    // if($this->is_set($invoice_line->column_fields['contact_id'])) {
    //   $mno_id_map = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice_line->column_fields['contact_id'], 'CONTACTS');
    //   if($mno_id_map) { $invoice_line_hash['person_id'] = $mno_id_map['mno_entity_guid']; }
    // }

    // // Map transaction lines
    // $invoice_line_hash['lines'] = array();
    // $result = $adb->pquery("SELECT * FROM vtiger_inventoryproductrel WHERE id = ?", array($invoice_line->id));
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
    //   // Use InvoiceID#LineNumber instead
    //   $invoice_line_id = $invoice_line->id . "#" . $line_number;
    //   $mno_transaction_line_id = MnoIdMap::findMnoIdMapByLocalIdAndEntityName($invoice_line_id, "TRANSACTION_LINE");
    //   if($mno_transaction_line_id) {
    //     // Reuse Connec Invoice Line ID
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

    //   $invoice_line_hash['lines'][] = $invoice_line;
    // }

    return $invoice_line_hash;
  }

  // Persist the Dolibarr InvoiceLine
  protected function persistLocalModel($invoice_line, $invoice_line_hash) {
    if($this->is_new($invoice_line)) {
      $invoice_line->id = $invoice_line->insert(0, false);
    } else {
      $user = ConnecUtils::defaultUser();
      $invoice_line->update($user, 0, false);
    }
  }
}