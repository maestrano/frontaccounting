<?php

/**
 * Mno Person Interface
 */
class MnoSoaBasePerson extends MnoSoaBaseEntity
{
    protected $_mno_entity_name = "PERSONS";
    protected $_create_rest_entity_name = "persons";
    protected $_create_http_operation = "POST";
    protected $_update_rest_entity_name = "persons";
    protected $_update_http_operation = "POST";
    protected $_receive_rest_entity_name = "persons";
    protected $_receive_http_operation = "GET";
    protected $_delete_rest_entity_name = "persons";
    protected $_delete_http_operation = "DELETE";    
    
    protected $_id;
    protected $_name;
    protected $_birth_date;
    protected $_gender;
    protected $_address;
    protected $_email;
    protected $_telephone;
    protected $_website;
    protected $_entity;
    protected $_credit_card;
    protected $_role;  

    /**************************************************************************
     *                    ABSTRACT DATA MAPPING METHODS                       *
     **************************************************************************/

    protected function pushId() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullId() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushName() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullName() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushBirthDate() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullBirthDate() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushGender() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullGender() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushAddresses() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullAddresses($is_new_id) {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushEmails() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullEmails() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushTelephones() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullTelephones() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushWebsites() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullWebsites() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushEntity() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullEntity() {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushCreditCard() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullCreditCard() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pushRole() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function pullRole() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
		throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    /**************************************************************************
     *                       ABSTRACT GET/SET METHODS                         *
     **************************************************************************/
    
    public function getLocalEntityIdentifier() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    public function getLocalEntityByLocalIdentifier($local_id) {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    public function createLocalEntity() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    public function getLocalOrganizationIdentifier() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }
    
    protected function setLocalOrganizationIdentifier($local_org_id) {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoPerson class!');
    }

    /**************************************************************************
     *                       COMMON INHERITED METHODS                         *
     **************************************************************************/

    protected function getMnoOrganizationByMap($local_org_id) {
        $organization_class = static::getRelatedOrganizationClass();
        return MnoSoaDB::getMnoIdByLocalId($local_org_id, $organization_class::getLocalEntityName(), $organization_class::getMnoEntityName());
    }
    
    public function getRelatedOrganizationClass() {
        return $this->_related_organization_class;
    }

    /**
    * Build a Maestrano organization message
    * 
    * @return Organization the organization json object
    */
    protected function build() {
		MnoSoaLogger::debug("start");
		$this->pushId();
		MnoSoaLogger::debug("after Id");
		$this->pushName();
		MnoSoaLogger::debug("after Name");
		$this->pushBirthDate();
		MnoSoaLogger::debug("after Birth Date");
		$this->pushGender();
		MnoSoaLogger::debug("after Annual Revenue");
		$this->pushAddresses();
		MnoSoaLogger::debug("after Addresses");
		$this->pushEmails();
		MnoSoaLogger::debug("after Emails");
		$this->pushTelephones();
		MnoSoaLogger::debug("after Telephones");
		$this->pushWebsites();
		MnoSoaLogger::debug("after Websites");
		$this->pushEntity();
		MnoSoaLogger::debug("after Entity");
        $this->pushCreditCard();
        MnoSoaLogger::debug("after Credit Card");
        $this->pushRole();
        MnoSoaLogger::debug("after Role");
        
        if ($this->_name != null) { $msg['person']->name = $this->_name; }
        if ($this->_birth_date != null) { $msg['person']->birthDate = $this->_birth_date; }
        if ($this->_gender != null) { $msg['person']->gender = $this->_gender; }
        if ($this->_address != null) { $msg['person']->contacts->address = $this->_address; }
        if ($this->_email != null) { $msg['person']->contacts->email = $this->_email; }
        if ($this->_telephone != null) { $msg['person']->contacts->telephone = $this->_telephone; }
        if ($this->_website != null) { $msg['person']->contacts->website = $this->_website; }
        if ($this->_entity != null) { $msg['person']->entity = $this->_entity; }
        if ($this->_credit_card != null) { $msg['person']->creditCard = $this->_credit_card; }
        if ($this->_role != null) { $msg['person']->role = $this->_role; }
	
		MnoSoaLogger::debug("after creating message array");
		$result = json_encode($msg['person']);
	
		MnoSoaLogger::debug("result = " . $result);
	
		return $result;
    }
    
    protected function persist($mno_entity) {
        MnoSoaLogger::debug("mno_entity = " . json_encode($mno_entity));
        
        if (!empty($mno_entity->person)) {
            $mno_entity = $mno_entity->person;
        }
        
        if (empty($mno_entity->id)) {
            return false;
        }
		
        $this->_id = $mno_entity->id;
        $this->set_if_array_key_has_value($this->_name, 'name', $mno_entity);
        $this->set_if_array_key_has_value($this->_birth_date, 'birthDate', $mno_entity);
        $this->set_if_array_key_has_value($this->_gender, 'gender', $mno_entity);

        if (!empty($mno_entity->contacts)) {
            $this->set_if_array_key_has_value($this->_address, 'address', $mno_entity->contacts);
            $this->set_if_array_key_has_value($this->_email, 'email', $mno_entity->contacts);
            $this->set_if_array_key_has_value($this->_telephone, 'telephone', $mno_entity->contacts);
            $this->set_if_array_key_has_value($this->_website, 'website', $mno_entity->contacts);
        }

        $this->set_if_array_key_has_value($this->_entity, 'entity', $mno_entity);
        $this->set_if_array_key_has_value($this->_role, 'role', $mno_entity);

        MnoSoaLogger::debug("persist person id = " . $this->_id);

        $status = $this->pullId();
        $is_new_id = $status == constant('MnoSoaBaseEntity::STATUS_NEW_ID');
        $is_existing_id = $status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');

        if (!$is_new_id && !$is_existing_id) {
            return true;
        }

        MnoSoaLogger::debug("is_new_id = " . $is_new_id);
        MnoSoaLogger::debug("is_existing_id = " . $is_existing_id);

        if ($is_new_id || $is_existing_id) {
            MnoSoaLogger::debug("start pull functions");
            $this->pullName();
            MnoSoaLogger::debug("after name");
            $this->pullBirthDate();
            MnoSoaLogger::debug("after birth date");
            $this->pullGender();
            MnoSoaLogger::debug("after gender");
            $this->pullAddresses($is_new_id);
            MnoSoaLogger::debug("after addresses");
            $this->pullEmails();
            MnoSoaLogger::debug("after emails");
            $this->pullTelephones();
            MnoSoaLogger::debug("after telephones");
            $this->pullWebsites();
            MnoSoaLogger::debug("after websites");
            $this->pullEntity();
            MnoSoaLogger::debug("after entity");
            $this->pullRole();
            MnoSoaLogger::debug("after role");

            $this->saveLocalEntity(false, $status);
        }

        $local_entity_id = $this->getLocalEntityIdentifier();
        $mno_entity_id = $this->_id;

        if ($is_new_id && !empty($local_entity_id) && !empty($mno_entity_id)) {
            MnoSoaDB::addIdMapEntry($local_entity_id, $this->getLocalEntityName(), $mno_entity_id, $this->getMnoEntityName());
        }
        MnoSoaLogger::debug("end persist");
        
        return true;
    }
}

?>