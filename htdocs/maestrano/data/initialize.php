<?php

require_once '../init.php';
require_once MAESTRANO_ROOT . '/init/init_script.php';

if(!Maestrano::param('connec.enabled')) { return false; }

require_once MAESTRANO_ROOT . '/connec/init.php';

$filepath = MAESTRANO_ROOT . '/var/_data_sequence';
$status = false;

// Last update timestamp
$timestamp = trim(openAndReadFile($filepath));
$current_timestamp = round(microtime(true) * 1000);
if (empty($timestamp)) { $timestamp = 0; } 

error_log('Fetch connec updates since ' . $timestamp);
// Fetch updates
$client = new Maestrano_Connec_Client();
$msg = $client->get("updates/$timestamp?\$filter[entity]=Company,TaxCode,Account,Organization,Person,Item,Invoice,Quote,PurchaseOrder,Payment");
$code = $msg['code'];
$body = $msg['body'];

if($code != 200) {
  error_log("Cannot fetch connec updates code=$code, body=$body");
} else {
  error_log("Receive updates body=$body");
  $result = json_decode($body, true);

  // Dynamically find mappers and map entities
  foreach(BaseMapper::getMappers() as $mapperClass) {
  	if (class_exists($mapperClass)) {
      $test_class = new ReflectionClass($mapperClass);
      if($test_class->isAbstract()) { continue; }

      $mapper = new $mapperClass();
      $mapper->persistAll($result[$mapper->getConnecResourceName()]);
    }
  }
}

error_log('Finished processing updates');
$status = true;

if ($status) {
  file_put_contents($filepath, $current_timestamp);
}
