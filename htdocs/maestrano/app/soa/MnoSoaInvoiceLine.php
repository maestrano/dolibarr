<?php

/**
 * Mno InvoiceLine Class
 */
class MnoSoaInvoiceLine extends MnoSoaBaseInvoiceLine
{
  protected $_local_entity_name = "INVOICE_LINE";

  public function saveLocalEntity($invoice_local_id, $invoice_lines, $push_to_maestrano) {
MnoSoaLogger::debug("start saving invoice lines for invoice " . $invoice_local_id . ", invoice_lines: " . json_encode($invoice_lines));
    if(!empty($invoice_lines)) {
MnoSoaLogger::debug("processing invoice lines");
      foreach($invoice_lines as $line_id => $line) {
MnoSoaLogger::debug("processing line " . json_encode($line));
        $local_line_id = $this->getLocalIdByMnoId($line_id);
MnoSoaLogger::debug("persisting " . json_encode($line) . " mno_id=" . json_encode($local_line_id));
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
        $invoice_line->total_ht = $line->totalPrice->netAmount;
        $invoice_line->total_tva = $line->totalPrice->taxAmount;
        $invoice_line->total_ttc = $line->totalPrice->price;
        $invoice_line->qty = $line->quantity;
        $invoice_line->subprice = $line->unitPrice->price;

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
MnoSoaLogger::debug("end");
  }

  protected function getMainCurrency() {
    global $conf;
    return $conf->currency;
  }
}

?>