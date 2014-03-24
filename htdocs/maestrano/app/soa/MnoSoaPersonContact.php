<?php

/**
 * Mno Organization Class
 */
class MnoSoaPersonContact extends MnoSoaBasePerson
{
    protected $_local_entity_name = "contact";
    
    // DONE
    protected function pushId() {
        $this->_log->debug(__FUNCTION__ . " start");
        
	$id = $this->_local_entity->id;
	
	if (!empty($id)) {
	    $this->_log->debug(__FUNCTION__ . " this->_local_entity->id = " . $id);
	    $mno_id = $this->getMnoIdByLocalId($id);
	    
	    if ($this->isValidIdentifier($mno_id)) {
                $this->_log->debug(__FUNCTION__ . " this->getMnoIdByLocalId(id) = " . json_encode($mno_id));
		$this->_id = $mno_id->_id;
	    }
	}
        
        $this->_log->debug(__FUNCTION__ . " end");
    }
    
    // DONE
    protected function pullId() {
        $this->_log->debug(__FUNCTION__ . " start " . $this->_id);
        
	if (!empty($this->_id)) {
	    $this->_log->debug("id not empty". $this->_id);
	    $local_id = $this->getLocalIdByMnoId($this->_id);
	    $this->_log->debug(__FUNCTION__ . " this->getLocalIdByMnoId(this->_id) = " . json_encode($local_id));
	    
	    if ($this->isValidIdentifier($local_id)) {
                $this->_log->debug(__FUNCTION__ . " is STATUS_EXISTING_ID");
		$this->_local_entity = new Contact($this->_db);
                $this->_local_entity->fetch($local_id->_id);
		return constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
	    } else if ($this->isDeletedIdentifier($local_id)) {
                $this->_log->debug(__FUNCTION__ . " is STATUS_DELETED_ID");
                return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
            } else {
                $this->_log->debug(__FUNCTION__ . " is STATUS_NEW_ID");
		$this->_local_entity = new Contact($this->_db);		
		return constant('MnoSoaBaseEntity::STATUS_NEW_ID');
	    }
	}
        
        $this->_log->debug(__FUNCTION__ . " return STATUS_ERROR");
        return constant('MnoSoaBaseEntity::STATUS_ERROR');
    }
    
    // DONE
    protected function pushName() {
        $this->_log->debug(__FUNCTION__ . " start");
        $this->_name->givenNames = $this->push_set_or_delete_value($this->_local_entity->firstname);
        $this->_name->familyName = $this->push_set_or_delete_value($this->_local_entity->lastname);
        $this->_log->debug(__FUNCTION__ . " end");
    }
    
    // DONE
    protected function pullName() {
        $this->_log->debug(__FUNCTION__ . " start");
        
        $this->_local_entity->firstname = $this->pull_set_or_delete_value($this->_name->givenNames);
        $this->_local_entity->lastname = $this->pull_set_or_delete_value($this->_name->familyName);
        
        $hp = $this->pull_set_or_delete_value($this->_name->honorificPrefix);
        $hp = $this->mapHonorificPrefixToSalutation($this->_name->honorificPrefix);
        $this->_local_entity->civilite_id = $this->pull_set_or_delete_value($hp);
        
        $this->_log->debug(__FUNCTION__ . " end");
    }
    
    // DONE
    protected function pushBirthDate() {
        $this->_log->debug(__FUNCTION__ . " start");
        $this->_birth_date = $this->push_set_or_delete_value($this->_local_entity->birthday);
	$this->_log->debug(__FUNCTION__ . " end " . $this->_birth_date);
    }
    
    // DONE
    protected function pullBirthDate() {
        $this->_log->debug(__FUNCTION__ . " start");
        $this->_local_entity->birthday = $this->pull_set_or_delete_value($this->_birth_date);        
        $this->_log->debug(__FUNCTION__ . " end " . $this->_local_entity->birthday);
    }
    
    // DONE
    protected function pushGender() {
	// DO NOTHING
    }
    
    // DONE
    protected function pullGender() {
	// DO NOTHING
    }
    
    // DONE
    protected function pushAddresses() {
        $this->_log->debug(__FUNCTION__ . " start ");
        // POSTAL ADDRESS
        $this->_address->postalAddress->postalCode = $this->push_set_or_delete_value($this->_local_entity->zip, "");
        $this->_address->postalAddress->locality = $this->push_set_or_delete_value($this->_local_entity->town, "");
        if (empty($this->_local_entity->state_id)) {
            $this->_address->postalAddress->region = "";
        } else {
            $state = getState($this->_local_entity->state_id,0,$this->_db);
            if (!empty($state) && array_key_exists('label', $state)) {
                $this->_address->postalAddress->region = $this->push_set_or_delete_value($state['label'], "");
            } else {
                $this->log->error(__FUNCTION__ . " failed to lookup state " . $this->_local_entity->state_id);
            }
        }
        
        if (empty($this->_local_entity->country_id)) {
            $this->_address->postalAddress->country = "";
        } else {
            $country_code = getCountry($this->_local_entity->country_id,2,$this->_db);
            if (!empty($country_code) && $country_code != 'NotDefined' && $country_code != 'Error' && strlen($country_code) == 2) {
                $this->_address->postalAddress->country = $this->push_set_or_delete_value(strtoupper($country_code));
            } else {
                $this->log->error(__FUNCTION__ . " failed to lookup country " . $this->_address->postalAddress->country);
            }
            
        }
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullAddresses() {
        $this->_log->debug(__FUNCTION__ . " start ");
	// POSTAL ADDRESS
        $this->_local_entity->address = $this->pull_set_or_delete_value($this->_address->postalAddress->streetAddress, "");
        $this->_local_entity->town = $this->pull_set_or_delete_value($this->_address->postalAddress->locality, "");
        $this->_local_entity->zip = $this->pull_set_or_delete_value($this->_address->postalAddress->postalCode, "");
        if ($this->_address->postalAddress->country != null) {
            if (empty($this->_address->postalAddress->country)) {
                $this->_local_entity->country_id = "";
            } else {
                $country_code = getCountry($this->_address->postalAddress->country,3,$this->_db);
                if (!empty($country_code) && $country_code != 'NotDefined' && $country_code != 'Error') {
                    $this->_local_entity->country_id = $country_code;
                } else {
                    $this->log->error(__FUNCTION__ . " failed to lookup country " . $this->_address->postalAddress->country);
                }
            }
        }
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushEmails() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $this->_email->emailAddress = $this->push_set_or_delete_value($this->_local_entity->email, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullEmails() {      
	$this->_log->debug(__FUNCTION__ . " start ");
        $this->_local_entity->email = $this->pull_set_or_delete_value($this->_email->emailAddress, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushTelephones() {
        $this->_log->debug(__FUNCTION__ . " start ");
        $this->_telephone->work->voice = $this->push_set_or_delete_value($this->_local_entity->phone_pro, "");
        $this->_telephone->home->voice = $this->push_set_or_delete_value($this->_local_entity->phone_perso, "");
        $this->_telephone->work->mobile = $this->push_set_or_delete_value($this->_local_entity->phone_mobile, "");
        $this->_telephone->work->fax = $this->push_set_or_delete_value($this->_local_entity->fax, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullTelephones() {
        $this->_log->debug(__FUNCTION__ . " end ");
        $this->_local_entity->phone_pro = $this->pull_set_or_delete_value($this->_telephone->work->voice, "");
        $this->_local_entity->phone_perso = $this->pull_set_or_delete_value($this->_telephone->home->voice, "");
        $this->_local_entity->phone_mobile = $this->pull_set_or_delete_value($this->_telephone->work->mobile, "");
        $this->_local_entity->fax = $this->pull_set_or_delete_value($this->_telephone->work->fax, "");
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushWebsites() {
	// DO NOTHING
    }
    
    // DONE
    protected function pullWebsites() {
	// DO NOTHING
    }
    
    // DONE
    protected function pushEntity() {
        $this->_log->debug(__FUNCTION__ . " start ");
        
        if (!empty($this->_local_entity->socid)) {
            $mno_id = $this->getMnoIdByLocalIdName($this->_local_entity->socid, "societe");
	    
	    if ($this->isValidIdentifier($mno_id)) {
                $this->_log->debug(__FUNCTION__ . " mno_id = " . json_encode($mno_id));
                $organization = new Societe($this->_db);
                $organization->fetch($mno_id->_id);
                
                if (!empty($organization->client)) {
                    switch($organization->client) {
                        case 0: $this->_entity->customer = false; $this->_entity->lead = false; break;
                        case 1: $this->_entity->customer = true; break;
                        case 2: $this->_entity->lead = true; break;
                        case 3: $this->_entity->customer = true; $this->_entity->lead = true; break;
                    }
                } else {
                    $this->_entity->customer = false; 
                    $this->_entity->lead = false;
                }

                if (!empty($organization->fournisseur)) {
                    switch($organization->fournisseur) {
                        case 0: $this->_entity->supplier = false; break;
                        case 1: $this->_entity->supplier = true; break;
                    }
                } else {
                    $this->_entity->supplier = false;
                }
	    }
	}
        
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullEntity() {
        $this->_log->debug(__FUNCTION__ . " start ");
    
        if (!empty($this->_local_entity->socid)) {
            $mno_id = $this->getMnoIdByLocalIdName($this->_local_entity->socid, "societe");
	    
	    if ($this->isValidIdentifier($mno_id)) {
                $this->_log->debug(__FUNCTION__ . " mno_id = " . json_encode($mno_id));
                $organization = new Societe($this->_db);
                $organization->fetch($mno_id->_id);
                
                if (!empty($this->_entity->customer) && !empty($this->_entity->lead)) {
                    $organization->client = 3;
                } else if (!empty($this->_entity->customer)) {
                    $organization->client = 1;
                } else if (!empty($this->_entity->lead)) {
                    $organization->client = 2;
                } else {
                    $organization->client = 0;
                }

                if (!empty($this->_entity->supplier)) {
                    $organization->fournisseur = 1;
                } else {
                    $organization->fournisseur = 0;
                }
                
                $organization->update($this->_local_entity->id, '', 1, 0, 0, 'update', 1, false);
            }
        }
        
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushRole() {
        $this->_log->debug(__FUNCTION__ . " start ");
        
        if (!empty($this->_local_entity->socid)) {
            $mno_id = $this->getMnoIdByLocalIdName($this->_local_entity->socid, "societe");
	    
	    if ($this->isValidIdentifier($mno_id)) {
                $this->_log->debug(__FUNCTION__ . " mno_id = " . json_encode($mno_id));
		$this->_role->organization->id = $mno_id->_id;
                $this->_role->title = $this->push_set_or_delete_value($this->_local_entity->poste, "");
            } else if ($this->isDeletedIdentifier($mno_id)) {
                // do not update
                return;
	    } else {
                $soc = new Societe($this->_db);
                $soc->fetch($this->_local_entity->socid);
                
                $organization = new MnoSoaOrganization($this->_db, $this->_log);		
                $organization->send($soc);
                
                $mno_id = $this->getMnoIdByLocalIdName($this->_local_entity->socid, "societe");
                
                if ($this->isValidIdentifier($mno_id)) {
                    $this->_role->organization->id = $mno_id->_id;
                    $this->_role->title = $this->push_set_or_delete_value($this->_local_entity->poste, "");
                }
            }
	} else {
            $this->_role = (object) array();
        }
        
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullRole() {
        if (empty($this->_role->organization->id)) {
            $this->_local_entity->socid = null;
            $this->_local_entity->poste = "";
        } else {
            $local_id = $this->getLocalIdByMnoIdName($this->_role->organization->id, "organizations");
            if ($this->isValidIdentifier($local_id)) {
                $this->_log->debug(__FUNCTION__ . " local_id = " . json_encode($local_id));
                $this->_local_entity->socid = $local_id->_id;
            } else if ($this->isDeletedIdentifier($local_id)) {
                // do not update
                return;
            } else {
                $notification->entity = "organizations";
                $notification->id = $this->_role->organization->id;
                $organization = new MnoSoaOrganization($this->_db, $this->_log);		
                $organization->receiveNotification($notification);
                $this->_local_entity->socid = $organization->_local_entity->id;
                $this->_local_entity->poste = $this->pull_set_or_delete_value($this->_role->title, "");
            }
        }
    }
    
    // DONE
    protected function saveLocalEntity($push_to_maestrano, $status) {
        $this->_log->debug(__FUNCTION__ . " start ");
	// status = 2 update status = 1 new
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            $this->_local_entity->create(0, $push_to_maestrano);
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            $this->_local_entity->update($this->_local_entity->id, 0, 0, 'update', $push_to_maestrano);
        }
        $this->_log->debug(__FUNCTION__ . " end ");
    }
    
    // DONE
    protected function getLocalEntityIdentifier() {
        return $this->_local_entity->id;
    }
    
    // DONE
    protected function mapHonorificPrefixToSalutation($in) {
        $in_form = strtoupper(trim($in));
        
        switch ($in_form) {
            case "MR": return "MR";
            case "MS": return "MLE";
            case "MRS": return "MME";
            case "DR": return "DR";
            case "MASTER": return "MTRE";
            default: return null;
        }
    }
}

?>