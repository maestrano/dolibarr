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
define('DOL_DOCUMENT_ROOT', realpath(MAESTRANO_ROOT . '/../'));
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/conf/conf.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT . '/includes/adodbtime/adodb-time.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

$opts = array();

// Define DOL_URL_ROOT (no url prefix on mno)
define('DOL_URL_ROOT', '');
define('MAIN_DB_PREFIX', $dolibarr_main_db_prefix);

// Dolibarr configuration (global)
$conf = new Conf();

// DB configuration
$conf->db->host							= $dolibarr_main_db_host;
$conf->db->port							= $dolibarr_main_db_port;
$conf->db->name							= $dolibarr_main_db_name;
$conf->db->user							= $dolibarr_main_db_user;
$conf->db->pass							= $dolibarr_main_db_pass;
$conf->db->type							= $dolibarr_main_db_type;
$conf->db->prefix						= $dolibarr_main_db_prefix;
$conf->db->character_set                                        = $dolibarr_main_db_character_set;
$conf->db->dolibarr_main_db_collation                           = $dolibarr_main_db_collation;

$langs=new Translate("",$conf);

// Configure Dolibarr Database
$db=getDoliDBInstance($dolibarr_main_db_type,$dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name,$dolibarr_main_db_port);
$opts['db_connection'] = $db;

// Configure Logger
include_once MAESTRANO_ROOT . '/lib/apache-log4php-2.3.0/src/main/php/Logger.php';
Logger::configure(MAESTRANO_ROOT . '/app/config/log_config.xml');
$logger = Logger::getLogger('main');
$opts['logger'] = $logger;

$conf->setValues($db);

// Dolibarr hookmanager (global)
$hookmanager=new HookManager($db);

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// If you define the $opts variable then it will
// automatically be passed to the MnoSsoUser object
// for construction
