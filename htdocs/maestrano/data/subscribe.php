<?php

//-----------------------------------------------
// Define root folder
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../'));
}

require_once(MAESTRANO_ROOT . '/app/init/soa.php');

$maestrano = MaestranoService::getInstance();

$log = $opts['logger'];

$log->debug("Subscribe received a notification");

if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
    $notification = json_decode(file_get_contents('php://input'), false);
    $notification_entity = strtoupper(trim($notification->entity));
    
    $log->debug("Notification = ". json_encode($notification));
    
    switch ($notification_entity) {
	    case "ORGANIZATIONS":
		$mno_org = new MnoSoaOrganization($opts['db_connection'], $opts['logger']);		
		$mno_org->receiveNotification($notification);
		break;
            case "PERSONS":
                $mno_person = new MnoSoaPersonContact($opts['db_connection'], $opts['logger']);		
		$mno_person->receiveNotification($notification);
		break;
    }
}

?>
