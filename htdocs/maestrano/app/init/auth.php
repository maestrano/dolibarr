<?php
//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}
require MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
define('DOL_DOCUMENT_ROOT', '/Users/arnaudlachaume/Sites/apps-dev/app-dolibarr/htdocs');
//define('DOL_DOCUMENT_ROOT', realpath(MAESTRANO_ROOT . '/../'));
require DOL_DOCUMENT_ROOT . '/conf/class/conf.php';
require DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// If you define the $opts variable then it will
// automatically be passed to the MnoSsoUser object
// for construction
// e.g:
$opts = array();

// Configure Dolibarr Database
$db=getDoliDBInstance($dolibarr_main_db_type,$dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name,$dolibarr_main_db_port);
$opts['db_connection'] = $db;


