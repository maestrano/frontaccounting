<?php

/**
 * Mno Organization Class
 */
class MnoSoaPerson extends MnoSoaBasePerson
{
    protected $_local_entity_name = "crm_persons";
    
    // DONE
    protected function pushId() 
    {
        $id = $this->getLocalEntityIdentifier();

        MnoSoaLogger::debug("local_entity=" . json_encode($this->_local_entity));

        if (!empty($id)) {
            $mno_id = MnoSoaDB::getMnoIdByLocalId($id, $this->_local_entity_name, $this->_mno_entity_name);

            if (MnoSoaDB::isValidIdentifier($mno_id)) {
            MnoSoaLogger::debug("this->getMnoIdByLocalId(id) = " . json_encode($mno_id));
                            $this->_id = $mno_id->_id;
            }
        }
    }
    
    // DONE
    protected function pullId() 
    {
        if (!empty($this->_id)) {
            $local_id = MnoSoaDB::getLocalIdByMnoId($this->_id, $this->_mno_entity_name, $this->_local_entity_name);
            MnoSoaLogger::debug("this->getLocalIdByMnoId(this->_id) = " . json_encode($local_id));
            
            if (MnoSoaDB::isValidIdentifier($local_id)) {
                MnoSoaLogger::debug("is STATUS_EXISTING_ID");
                $this->_local_entity = get_crm_person($local_id->_id);
                $this->_local_entity['contacts'] = $this->get_crm_contacts_records($local_id->_id);
                MnoSoaLogger::debug("local_entity=" . json_encode($this->_local_entity));
		return constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
            } else if (MnoSoaDB::isDeletedIdentifier($local_id)) {
                MnoSoaLogger::debug("is STATUS_DELETED_ID");
                return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
            } else {
		return constant('MnoSoaBaseEntity::STATUS_NEW_ID');
            }
        }
        
        MnoSoaLogger::debug("return STATUS_ERROR");
        return constant('MnoSoaBaseEntity::STATUS_ERROR');
    }
    
    protected function pushName() {
        $this->_name->givenNames = $this->push_set_or_delete_value($this->_local_entity['name']);
        $this->_name->familyName = $this->push_set_or_delete_value($this->_local_entity['name2']);
    }
    
    protected function pullName() {
        $this->_local_entity['name'] = $this->pull_set_or_delete_value($this->_name->givenNames);
        $this->_local_entity['name2'] = $this->pull_set_or_delete_value($this->_name->familyName);
    }
    
    protected function pushBirthDate() {
        // DO NOTHING
    }
    
    protected function pullBirthDate() {
        // DO NOTHING
    }
    
    protected function pushGender() {
		// DO NOTHING
    }
    
    protected function pullGender() {
		// DO NOTHING
    }
    
    protected function pushAddresses() {
        // DO NOTHING
    }
    
    protected function pullAddresses($is_new_id) {
        // POSTAL ADDRESS -> POSTAL ADDRESS
        if ($is_new_id) {
            $str = "";
            $str .=  $this->pull_set_or_delete_value($this->_address->work->postalAddress->streetAddress). " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->work->postalAddress->locality) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->work->postalAddress->region) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->work->postalAddress->postalCode) . " ";
            $str .=  MnoSoaTransformer::transformISO3166_2ToCountryName($this->pull_set_or_delete_value($this->_address->work->postalAddress->country)) . " ";
            
            $str = trim(preg_replace('!\s+!', ' ', $str));
            
            $this->_local_entity['address'] = $str;
        }
    }
    
    protected function pushEmails() {
        $this->_email->emailAddress = $this->push_set_or_delete_value($this->_local_entity['email']);
    }
    
    protected function pullEmails() {
        $this->_local_entity['email'] = $this->pull_set_or_delete_value($this->_email->emailAddress);
    }
    
    
    protected function pushTelephones() {
        $this->_telephone->work->voice = $this->push_set_or_delete_value($this->_local_entity['phone']);
        $this->_telephone->work->voice2 = $this->push_set_or_delete_value($this->_local_entity['phone2']);
        $this->_telephone->work->fax = $this->push_set_or_delete_value($this->_local_entity['fax']);
    }
    
    protected function pullTelephones() {
        $this->_local_entity['phone'] = $this->pull_set_or_delete_value($this->_telephone->work->voice);
        $this->_local_entity['phone2'] = $this->pull_set_or_delete_value($this->_telephone->work->voice2);
        $this->_local_entity['fax'] = $this->pull_set_or_delete_value($this->_telephone->work->fax);
    }
    
    protected function pushWebsites() {
		// DO NOTHING
    }
    
    protected function pullWebsites() {
		// DO NOTHING
    }
    
    protected function pushEntity() {
        // DO NOTHING
    }
    
    protected function pullEntity() {
        // DO NOTHING
    }
    
    protected function pushCreditCard() {
        // DO NOTHING
    }
    
    protected function pullCreditCard() {
        // DO NOTHING
    }
    
    protected function pushRole() {
        foreach ($this->_local_entity['contacts'] as $contact) {
            switch ($contact['type']) {
                case "customer":
                    $this->pushRoleEntity($contact['entity_id'], true, false);
                    break;
                case "supplier":
                    $this->pushRoleEntity($contact['entity_id'], false, true);
                    break;
            }
        }
    }
    
    protected function pushRoleEntity($local_org_id, $isCustomer, $isSupplier) {
		if (empty($local_org_id)) { return; }
            $mno_org_id = null;
            
        if ($isCustomer) {
            $mno_org_id = MnoSoaDB::getMnoIdByLocalId($local_org_id, "DEBTORS_MASTER", "ORGANIZATIONS");
        } else if ($isSupplier) {
            $mno_org_id = MnoSoaDB::getMnoIdByLocalId($local_org_id, "SUPPLIERS", "ORGANIZATIONS");
        } else {
            return;
        }
        
        MnoSoaLogger::debug(__FUNCTION__ . " mno_id = " . json_encode($mno_org_id));

        if (MnoSoaDB::isValidIdentifier($mno_org_id)) {    
            MnoSoaLogger::debug("is valid identifier");
            $this->_role->organization->id = $mno_org_id->_id;
        } else if (MnoSoaDB::isDeletedIdentifier($mno_org_id)) {
            MnoSoaLogger::debug(__FUNCTION__ . " deleted identifier");
            // do not update
            return;
        } else {
            $org_contact = null;
            $organization = null;

            // IN THE EVENT AN ORGANIZATION IS BOTH A CUSTOMER AND SUPPLIER
            // THE ORGANIZATION DETAILS WOULD HAVE BEEN SYNCED ACROSS C&S RECORDS
            // PUSHING EITHER ONE WILL UPDATE THE MAESTRANO ENTITY
            if ($isCustomer) {
                $org_contact = get_customer_organization($local_org_id);
                $organization = new MnoSoaOrganizationCustomer();
                $status = $organization->send($org_contact);

                if ($status) {
                    $mno_org_id = MnoSoaDB::getMnoIdByLocalId($local_org_id, "DEBTORS_MASTER", $this->_mno_entity_name);

                    if (MnoSoaDB::isValidIdentifier($mno_org_id)) {
                        $this->_role->organization->id = $mno_org_id->_id;
                    }
                }
            } else if ($isSupplier) {
                $org_contact = get_supplier_organization($local_org_id);
                $organization = new MnoSoaOrganizationSupplier();
                $status = $organization->send($org_contact);

                if ($status) {
                    $mno_org_id = MnoSoaDB::getMnoIdByLocalIdName($local_org_id, "SUPPLIERS", $this->_mno_entity_name);

                    if (MnoSoaDB::isValidIdentifier($mno_org_id)) {
                        $this->_role->organization->id = $mno_org_id->_id;
                    }
                }
            }
        }
    }
    
    protected function pullRole() {
        if (empty($this->_role->organization->id)) {
            // EXCEPTION - PERSON (CLIENT CONTACT) MUST BE RELATED TO AN ORGANIZATION (CLIENT)
            throw new Exception("MNO_000: Message not persisted - person must be related to an organization (MNOID=" . $this->_id . ")");
        } else {
            // CONSTRUCT NOTIFICATION
            $notification = (object) array();
            $notification->entity = "organizations";
            $notification->id = $this->_role->organization->id;
            // GET ORGANIZATION
            process_notification($notification);
            $local_entities = MnoSoaDB::getLocalIdsByMnoId($notification->id, $notification->entity);          
            
            if (empty($local_entities)) {
                throw new Exception("MNO_000: Message not persisted - person must be related to an organization (MNOID=" . $this->_id . ")");
            }
            
            $customer_id = null;
            $supplier_id = null;
            foreach ($local_entities as $local_id) {
                if ($local_id->_entity == "DEBTORS_MASTER" && MnoSoaDB::isValidIdentifier($local_id)) {
                    $customer_id = $local_id->_id;
                } else if ($local_id->_entity == "SUPPLIERS" && MnoSoaDB::isValidIdentifier($local_id)) {
                    $supplier_id = $local_id->_id;
                }
            }
            
            if (empty($customer_id) && empty($supplier_id)) {
                throw new Exception("MNO_000: Message not persisted - person must be related to an organization (MNOID=" . $this->_id . ")");
            }

            $person_id = $this->getLocalEntityIdentifier();
            
            if (!empty($person_id)) {
                if (!empty($customer_id) && !$this->contact_record_exists($this->_local_entity['contacts'], $person_id, $customer_id)) {
                    $no_of_contacts = count($this->_local_entity['contacts']);
                    $this->_local_entity['contacts'][$no_of_contacts] = array("person_id" => $person_id, "type" => "customer", "action" => "general", "entity_id" => $customer_id);
                }

                if (!empty($supplier_id) && !$this->contact_record_exists($this->_local_entity['contacts'], $person_id, $supplier_id)) {
                    $no_of_contacts = count($this->_local_entity['contacts']);
                    $this->_local_entity['contacts'][$no_of_contacts] = array("person_id" => $person_id, "type" => "supplier", "action" => "general", "entity_id" => $supplier_id);
                }
            } else {
                if (!empty($customer_id)) {
                    $no_of_contacts = count($this->_local_entity['contacts']);
                    $this->_local_entity['contacts'][$no_of_contacts] = array("type" => "customer", "action" => "general", "entity_id" => $customer_id);
                }

                if (!empty($supplier_id)) {
                    $no_of_contacts = count($this->_local_entity['contacts']);
                    $this->_local_entity['contacts'][$no_of_contacts] = array("type" => "supplier", "action" => "general", "entity_id" => $supplier_id);
                }
            }
        }
    }
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            $id = $this->add_crm_person_by_array($this->_local_entity, $push_to_maestrano);
            $this->setLocalEntityIdentifier($id);
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            $this->update_crm_person_by_array($this->_local_entity, $push_to_maestrano);
        }
    }
    
    public function getLocalEntityIdentifier() {
        if (empty($this->_local_entity) || !array_key_exists('id', $this->_local_entity)) { return null; }
        return $this->_local_entity['id'];
    }
    
    public function setLocalEntityIdentifier($id) {
        $this->_local_entity['id'] = $id;
    }
    
    /*
     * HELPER FUNCTIONS
     */
    
    private function add_crm_person_by_array($arr, $push_to_maestrano=true) 
    {       
        $ref = "";
        $name2 = null;
        $address = null;
        $phone = null;
        $phone2 = null;
        $fax = null;
        $email = null;
        $lang = null;
        $notes = '';
        
        extract($arr);

        if (!isset($name)) { MnoSoaLogger::error("Failed to insert person " . $this->_id . " - first name not provided"); return false; }
        if (!isset($contacts) || empty($contacts)) { MnoSoaLogger::error("Failed to insert person " . $this->_id . " - contacts not provided"); return false; }
        
        MnoSoaLogger::debug("before add_crm_person ");
                    
        $person_id = add_crm_person($ref, $name, $name2, $address, $phone, $phone2, 
                                 $fax, $email, $lang, $notes);
        MnoSoaLogger::debug("person_id=".$person_id);
        if (empty($person_id)) { return false; }
        
        foreach ($contacts as $contact)
        {
            MnoSoaLogger::debug("add_crm_contact type=".$contact['type']." action=".$contact['action']." entity_id=".$contact['entity_id']." person_id=$person_id");
            $result = add_crm_contact($contact['type'], $contact['action'], $contact['entity_id'], $person_id);
            if (empty($result)) { return false; }
        }
        
        return $person_id;
    }

    private function update_crm_person_by_array($arr, $push_to_maestrano=true)
    {
        $ref = "";
        $name2 = null;
        $address = null;
        $phone = null;
        $phone2 = null;
        $fax = null;
        $email = null;
        $lang = null;
        $notes = '';        
    
        extract($arr);

        if (!isset($id)) { MnoSoaLogger::error("Failed to update person " . $this->_id . " - id not provided"); return false; }
        if (!isset($name)) { MnoSoaLogger::error("Failed to update person " . $this->_id . " - first name not provided"); return false; }
        if (!isset($contacts) || empty($contacts)) { MnoSoaLogger::error("Failed to insert person " . $this->_id . " - contacts not provided"); return false; }
        
        $result = update_crm_person($id, $ref, $name, $name2, $address, $phone, $phone2, 
                                    $fax, $email, $lang, $notes);
        
        $person_id = $id;
        if (empty($result)) { return false; }
        
        $sql = "DELETE FROM ".TB_PREF."crm_contacts WHERE person_id=".db_escape($person_id)." and (type='customer' or type='cust_branch' or type='supplier')";
            
        begin_transaction();

        $ret = db_query($sql, "Can't delete person contacts");
        
        if ($ret) {
            foreach ($contacts as $contact)
            {
                if ($contact['type'] == 'customer' || $contact['type'] == 'cust_branch' || $contact['type'] == 'supplier')
                {
                    $sql = "INSERT INTO ".TB_PREF."crm_contacts (person_id,type,action,entity_id) VALUES
                            ("
                            .db_escape($person_id).","
                            .db_escape($contact['type']).", "
                            .db_escape($contact['action']).","
                            .db_escape($contact['entity_id'])
                            .")";
                    $ret = db_query($sql, "Can't update person contacts");
                }
            }
        }
        
        commit_transaction();
        
        return $ret;
    }
    
    /*
     * function update_person_contacts($id, $cat_ids, $entity_id=null, $type=null)
     */
    
    private function crm_contacts_records_contains_supplier($contacts)
    {
        foreach ($contacts as $contact) {
            if ($contact['type'] == "supplier") return true;
        }
        return false;
    }
    
    private function crm_contacts_records_contains_customer($contacts)
    {
        foreach ($contacts as $contact) {
            if ($contact['type'] == "customer") return true;
        }
        return false;
    }
    
    private function contact_record_exists($contacts, $person_id, $entity_id)
    {
        if (empty($contacts)) { return false; }
        foreach ($contacts as $contact) {
            if ($contact['person_id'] == $person_id && $contact['entity_id'] == $entity_id) return true;
        }
        return false;
    }
    
    private function get_crm_contacts_records($person_id)
    {
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
		$sql = "SELECT * FROM " . TB_PREF . "crm_contacts WHERE person_id='" . $person_id . "'";
		$result = db_query($sql, "get crm_contacts failed");
	
        $contacts = array();
        while($row = db_fetch($result)) {
            array_push($contacts, $row);
        }
        
        $db_last_inserted_id = $tmp_db_last_inserted_id;
        
	return $contacts;
    }
}

?>