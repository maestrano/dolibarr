<?php

/**
 * Mno Company Class
 */
class MnoSoaCompany extends MnoSoaBaseCompany
{
  protected $_local_entity_name = "company";

  protected function pushCompany() {
    MnoSoaLogger::debug(__FUNCTION__ . " start");

    MnoSoaLogger::debug(__FUNCTION__ . " end");
  }

  protected function pullCompany() {
    MnoSoaLogger::debug(__FUNCTION__ . " start " . $this->_id);

    MnoSoaLogger::debug(__FUNCTION__ . " end " . $this->_id);
  }

  protected function saveLocalEntity($push_to_maestrano) {
    MnoSoaLogger::debug(__FUNCTION__ . " start " . json_encode($this->_local_entity));

    // Persist

    MnoSoaLogger::debug(__FUNCTION__ . " end ");
  }

}

?>