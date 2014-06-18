<?php

/**
 * Mno Account Interface
 */
class MnoSoaBaseAccount extends MnoSoaBaseEntity
{
    protected $_mno_entity_name = "accounts";
    protected $_create_rest_entity_name = "accounts";
    protected $_create_http_operation = "POST";
    protected $_update_rest_entity_name = "accounts";
    protected $_update_http_operation = "POST";
    protected $_receive_rest_entity_name = "accounts";
    protected $_receive_http_operation = "GET";
    protected $_delete_rest_entity_name = "accounts";
    protected $_delete_http_operation = "DELETE";
    
    protected $_id;
    protected $_code;
    protected $_name;
    protected $_description;
    
    protected $_type;
    protected $_subtype;
    protected $_classification;
    
    protected $_currency;
    protected $_bank_account;
    
    protected $_parent;
    protected $_status;

    protected function pushAccount() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoAccount class!');
    }
    
    protected function pullAccount() {
	throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoAccount class!');
    }

    protected function saveLocalEntity($push_to_maestrano, $status) {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoAccount class!');
    }
    
    public function getLocalEntityIdentifier() {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in MnoAccount class!');
    }
    
    /**
    * Build a Maestrano organization message
    * 
    * @return Organization the organization json object
    */
    protected function build() {
        MnoSoaLogger::debug("start");
        $this->pushAccount();
        
        $msg = array();
        $msg['account'] = (object) array();
        
        if ($this->_code != null) { $msg['account']->code = $this->_code; }
        if ($this->_name != null) { $msg['account']->name = $this->_name; }
        if ($this->_description != null) { $msg['account']->description = $this->_description; }
        
        if ($this->_type != null) { $msg['account']->type = $this->_type; }
        if ($this->_subtype != null) { $msg['account']->subtype = $this->_subtype; }
        if ($this->_classification != null) { $msg['account']->classification = $this->_classification; }
        
        if ($this->_currency != null) { $msg['account']->currency = $this->_currency; }
        if ($this->_bank_account != null) { $msg['account']->bankAccount = $this->_bank_account; }
        
        if ($this->_parent != null) { $msg['account']->parent = $this->_parent; }
        if ($this->_status != null) { $msg['account']->status = $this->_status; }
	
        $result = json_encode($msg['account']);

        MnoSoaLogger::debug("result = " . json_encode($result));

        return $result;
    }
    
    protected function persist($mno_entity) {
        MnoSoaLogger::debug("start");
        
        if (!empty($mno_entity->account)) {
            $mno_entity = $mno_entity->account;
        }
        
        if (!empty($mno_entity->id)) {
            $this->_id = $mno_entity->id;
            $this->set_if_array_key_has_value($this->_code, 'code', $mno_entity);
            $this->set_if_array_key_has_value($this->_name, 'name', $mno_entity);
            $this->set_if_array_key_has_value($this->_description, 'description', $mno_entity);
            
            $this->set_if_array_key_has_value($this->_type, 'type', $mno_entity);
            $this->set_if_array_key_has_value($this->_subtype, 'subtype', $mno_entity);
            $this->set_if_array_key_has_value($this->_classification, 'classification', $mno_entity);
            
            $this->set_if_array_key_has_value($this->_currency, 'currency', $mno_entity);
            
            if (!empty($mno_entity->bankAccount)) {
                $this->set_if_array_key_has_value($this->_bank_account, 'bankAccount', $mno_entity->bankAccount);
            }
            
            $this->set_if_array_key_has_value($this->_parent, 'parent', $mno_entity);
            $this->set_if_array_key_has_value($this->_status, 'status', $mno_entity);

            MnoSoaLogger::debug("id = " . $this->_id);

            $status = $this->pullAccount();
            MnoSoaLogger::debug("after account");
            
            if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID') || $status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
                $this->saveLocalEntity(false, $status);
            }
        }
        MnoSoaLogger::debug("end");
    }
}

?>