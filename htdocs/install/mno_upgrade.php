<?php

/*
 * Database migration script to be executed from the command line
 * @author: Maestrano
*/

include_once 'inc.php';

// Database setup
require_once $dolibarr_main_document_root.'/core/lib/admin.lib.php';
$conf->db->type = $dolibarr_main_db_type;
$conf->db->host = $dolibarr_main_db_host;
$conf->db->port = $dolibarr_main_db_port;
$conf->db->name = $dolibarr_main_db_name;
$conf->db->user = $dolibarr_main_db_user;
$conf->db->pass = $dolibarr_main_db_pass;
$db = getDoliDBInstance($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);
$conf->setValues($db);

$migrations = array(
  array('from'=>'2.6.0', 'to'=>'2.7.0'),
  array('from'=>'2.7.0', 'to'=>'2.8.0'),
  array('from'=>'2.8.0', 'to'=>'2.9.0'),
  array('from'=>'2.9.0', 'to'=>'3.0.0'),
  array('from'=>'3.0.0', 'to'=>'3.1.0'),
  array('from'=>'3.1.0', 'to'=>'3.2.0'),
  array('from'=>'3.2.0', 'to'=>'3.3.0'),
  array('from'=>'3.3.0', 'to'=>'3.4.0'),
  array('from'=>'3.4.0', 'to'=>'3.5.0'),
  array('from'=>'3.5.0', 'to'=>'3.6.0'),
  array('from'=>'3.6.0', 'to'=>'3.7.0'),
  array('from'=>'3.7.0', 'to'=>'3.8.0')
);

$source_version = DOL_VERSION;

foreach($migrations as $migration) {
  $installed_version = preg_split('/[\.-]/',isset($conf->global->MAIN_VERSION_LAST_UPGRADE) ? $conf->global->MAIN_VERSION_LAST_UPGRADE : (isset($conf->global->MAIN_VERSION_LAST_INSTALL)?$conf->global->MAIN_VERSION_LAST_INSTALL:''));
  $dolibarr_version_from = preg_split('/[\.-]/', $migration['from']);
  $dolibarr_version_to = preg_split('/[\.-]/', $migration['to']);

  if(versioncompare($dolibarr_version_to, $installed_version) > 0 || versioncompare($dolibarr_version_to, $source_version) < -2) {
    $migration_script1 = "php upgrade.php " . $migration['from'] . " " . $migration['to'];
    $migration_script2 = "php upgrade2.php " . $migration['from'] . " " . $migration['to'];
    $migration_script3 = "php step5.php " . $migration['from'] . " " . $migration['to'];

    exec($migration_script1);
    exec($migration_script2);
    exec($migration_script3);
  }
}
