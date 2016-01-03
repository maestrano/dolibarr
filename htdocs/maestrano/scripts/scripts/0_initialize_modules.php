<?php

// Activate default modules

error_log('Initialization script - Activate default modules');

installModule('modSociete');
installModule('modPropale');
installModule('modCommande');
installModule('modContrat');
installModule('modFicheinter');
installModule('modExpedition');
installModule('modComptabilite');
installModule('modTax');
installModule('modFournisseur');
installModule('modProduct');
installModule('modService');
installModule('modStock');

function installModule($module_name) {
  $module_to_activate = preg_replace('/\.class\.php$/i', '', $module_name);
  $file = $module_to_activate.'.class.php';
  dol_include_once("/core/modules/".$file);
  activateModule($module_to_activate, 1);
}