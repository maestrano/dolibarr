<?php

//-----------------------------------------------
// Define root folder
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../'));
}

require_once(MAESTRANO_ROOT . '/app/init/soa.php');

// Activate default modules
MnoSoaLogger::debug('ACTIVATE MODULES');

// unActivateModule('modSociete');
// unActivateModule('modPropale');
// unActivateModule('modCommande');
// unActivateModule('modContrat');
// unActivateModule('modFicheinter');
// unActivateModule('modExpedition');
// unActivateModule('modProduct');
// unActivateModule('modFacture');
// unActivateModule('modBanque');
// unActivateModule('modStock');
// unActivateModule('modFournisseur');
// unActivateModule('modService');
// unActivateModule('modExport');
// unActivateModule('modImport');
// unActivateModule('modCashDesk');
// unActivateModule('modTax');
// unActivateModule('modPrelevement');
// unActivateModule('modDon');
// unActivateModule('modDeplacement');

activateModule('modSociete');
activateModule('modPropale');
activateModule('modCommande');
activateModule('modContrat');
activateModule('modFicheinter');
activateModule('modExpedition');
activateModule('modComptabilite');
activateModule('modTax');
activateModule('modFournisseur');
activateModule('modProduct');
activateModule('modStock');

$maestrano = MaestranoService::getInstance();
if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
    $filepath = MAESTRANO_ROOT . '/var/_data_sequence';
    $status = false;
    
    if (file_exists($filepath)) {
        $timestamp = trim(file_get_contents($filepath));
        $current_timestamp = round(microtime(true) * 1000);
        
        if (empty($timestamp)) { $timestamp = 0; } 

        $mno_entity = new MnoSoaEntity($opts['db_connection']);
        $status = $mno_entity->getUpdates($timestamp);
    }
    
    if ($status) {
        file_put_contents($filepath, $current_timestamp);
    }
}

?>
