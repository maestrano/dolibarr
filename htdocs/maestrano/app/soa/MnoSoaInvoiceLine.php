<?php

/**
 * Mno InvoiceLine Class
 */
class MnoSoaInvoiceLine extends MnoSoaBaseInvoiceLine
{
  protected $_local_entity_name = "INVOICE_LINE";

  public function saveLocalEntity($invoice_local_id, $invoice_lines, $push_to_maestrano) {
    MnoSoaLogger::debug("Saving invoice lines for invoice $invoice_local_id => " . json_encode($invoice_lines));
    
    if(!empty($invoice_lines)) {
      foreach($invoice_lines as $line_id => $line) {
        $local_line_id = $this->getLocalIdByMnoId($line_id);
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
        $invoice_line->description = $line->description;
        $invoice_line->total_ht = $line->totalPrice->netAmount;
        $invoice_line->total_tva = $line->totalPrice->taxAmount;
        $invoice_line->total_ttc = $line->totalPrice->price;
        $invoice_line->tva_tx = $line->totalPrice->taxRate;
        $invoice_line->qty = $line->quantity;
        $invoice_line->subprice = $line->unitPrice->netAmount;
        $invoice_line->remise_percent = $line->reductionPercent;

        // Map item
        if(!empty($line->item)) {
          $local_item_id = $this->getLocalIdByMnoIdName($line->item->id, "ITEMS");
          $invoice_line->fk_product = $local_item_id->_id;
        }

        if($new_record) {
          $local_id = $invoice_line->insert(0, $push_to_maestrano);
          if ($local_id > 0) {
            $this->addIdMapEntry($local_id, $line_id);
          }
        } else {
          $invoice_line->update('', 0, $push_to_maestrano);
        }
      }
    }

    MnoSoaLogger::debug("Finished saving invoice lines");
  }

  protected function getMainCurrency() {
    global $conf;
    return $conf->currency;
  }
}

?>