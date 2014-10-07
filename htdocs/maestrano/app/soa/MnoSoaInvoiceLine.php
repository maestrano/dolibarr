<?php

/**
 * Mno InvoiceLine Class
 */
class MnoSoaInvoiceLine extends MnoSoaBaseInvoiceLine
{
  protected $_local_entity_name = "INVOICE_LINE";

  protected function saveLocalEntity($invoice_local_id, $invoice_lines, $push_to_maestrano) {
    if(!empty($invoice_lines)) {
      foreach($invoice_lines as $line_id => $line) {
        $local_line_id = $this->getLocalIdByMnoIdName($line_id, "INVOICE_LINE");
        if($this->isDeletedIdentifier($local_line_id)) {
          continue;
        }

        $invoice_line = new FactureLigne($this->_db);
        $new_record = true;
        if ($this->isValidIdentifier($local_line_id)) {
          $new_record = false;
          $invoice_line->fetch($local_line_id->_id);
        }

        $invoice_line->fk_facture = $invoice_local_id;
        $invoice_line->rang = $line->lineNumber;
        $invoice_line->total_ht = $line->amount;
        $invoice_line->total_tva = $line->amount;
        $invoice_line->total_ttc = $line->amount;
        $invoice_line->qty = $line->quantity;
        $invoice_line->subprice = $line->unit_price;

        // Map item
        if(!empty($line->item)) {
          $local_item_id = $this->getLocalIdByMnoIdName($line->item->id, "ITEMS");
          $invoice_line->fk_product = $local_item_id->_id;
        }

        if($new_record) {
          $local_id = $invoice_line->insert(0, $push_to_maestrano);
          if ($local_id > 0) {
            $this->addIdMapEntryName($local_id, 'INVOICE_LINE', $this->_id, 'INVOICE_LINE');
          }
        } else {
          $invoice_line->update('', 0, $push_to_maestrano);
        }
      }
    }
  }

  protected function getMainCurrency() {
    global $conf;
    return $conf->currency;
  }
}

?>