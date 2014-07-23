<?php

//-----------------------------------------------
// Define root folder
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../'));
}

error_log("start subscribe");

require_once(MAESTRANO_ROOT . '/app/init/soa.php');

error_log("after soa.php");

$maestrano = MaestranoService::getInstance();

if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
    $notification = json_decode(file_get_contents('php://input'), false);
    $notification_entity = strtoupper(trim($notification->entity));
    
    MnoSoaLogger::debug("Notification = ". json_encode($notification));
    
    switch ($notification_entity) {
        case "ORGANIZATIONS":
                if (class_exists('MnoSoaOrganization')) {
                    $mno_org = new MnoSoaOrganization($opts['db_connection']);		
                    $mno_org->receiveNotification($notification);
                }
                break;
        case "PERSONS":
                if (class_exists('MnoSoaPersonContact')) {
                    $mno_person = new MnoSoaPersonContact($opts['db_connection']);		
                    $mno_person->receiveNotification($notification);
                }
		break;
        case "ITEMS":
                if (class_exists('MnoSoaItem')) {
                    error_log("received mnosoaitem");
                    $mno_item = new MnoSoaItem($opts['db_connection']);
                    $mno_item->receiveNotification($notification);
                }
                break;
        case "ACCOUNTS":
                if (class_exists('MnoSoaAccount')) {
                    $mno_account = new MnoSoaAccount($opts['db_connection']);
                    $mno_account->receiveNotification($notification);
                }
                break;
        case "INVOICES":
                if (class_exists('MnoSoaInvoice')) {
                    $mno_invoice = new MnoSoaInvoice($opts['db_connection']);
                    $mno_invoice->receiveNotification($notification);
                }
                break;
    }
}

?>
