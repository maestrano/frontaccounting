<?php

/**
 * Mno Organization Class
 */
class MnoSoaOrganizationCustomer extends MnoSoaBaseOrganization
{
    protected $_local_entity_name = "debtors_master";
    
    public function __construct($db, $log)
    {
	parent::__construct($db, $log);
    }
    
    // DONE
    protected function pushId() 
    {
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start");
	$id = $this->getLocalEntityIdentifier();
	$this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " localentityidentifier=".$id);
        
	if (!empty($id)) {
	    $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " this->_local_entity->id = " . json_encode($id));
	    $mno_id = $this->getMnoIdByLocalId($id, $this->_local_entity_name, $this->_mno_entity_name);
            
	    if ($this->isValidIdentifier($mno_id)) {
                $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " this->getMnoIdByLocalId(id) = " . json_encode($mno_id));
		$this->_id = $mno_id->_id;
	    }
	}
        
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end");
    }
    
    // DONE
    protected function pullId() 
    {
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start " . $this->_id);
        
	if (!empty($this->_id)) {            
	    $local_id = $this->getLocalIdByMnoId($this->_id, $this->_mno_entity_name, $this->_local_entity_name);
            $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " this->getLocalIdByMnoId(this->_id) = " . json_encode($local_id));
            
	    if ($this->isValidIdentifier($local_id)) {
                $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " is STATUS_EXISTING_ID");
                $this->_local_entity = get_customer_organization($local_id->_id);
		return constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
	    } else if ($this->isDeletedIdentifier($local_id)) {
                $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " is STATUS_DELETED_ID");
                return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
            } else {
                $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " is STATUS_NEW_ID");
		return constant('MnoSoaBaseEntity::STATUS_NEW_ID');
	    }
	}
        
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " return STATUS_ERROR");
        return constant('MnoSoaBaseEntity::STATUS_ERROR');
    }
    
    // DONE
    protected function pushName() 
    {
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start ");
        $this->_name = $this->push_set_or_delete_value($this->_local_entity['name']);
	$this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end " . $this->_name);
    }
    
    // DONE
    protected function pullName($is_new_id) 
    {
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start ");
        $this->_local_entity['name'] = $this->pull_set_or_delete_value($this->_name);
        if ($is_new_id) {
            $this->_local_entity['debtor_ref'] = $this->pull_set_or_delete_value($this->_name);
        }
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushIndustry() {
	// DO NOTHING
    }
    
    // DONE
    protected function pullIndustry() {
	// DO NOTHING
    }
    
    // DONE
    protected function pushAnnualRevenue() {
	// DO NOTHING
    }
    
    // DONE
    protected function pullAnnualRevenue() {
	// DO NOTHING
    }
    
    // DONE
    protected function pushCapital() {
        // DO NOTHING
    }
    
    // DONE
    protected function pullCapital() {
        // DO NOTHING
    }
    
    // DONE
    protected function pushNumberOfEmployees() {
	// DO NOTHING
    }
    
    // DONE
    protected function pullNumberOfEmployees() {
       // DO NOTHING
    }
    
    // DONE
    protected function pushAddresses() {
        // DO NOTHING
    }
    
    // DONE
    protected function pullAddresses($is_new_id) {
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start ");
	// POSTAL ADDRESS -> POSTAL ADDRESS
        if ($is_new_id) {
            $str = "";
            $str .=  $this->pull_set_or_delete_value($this->_address->postalAddress->streetAddress) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->postalAddress->locality) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->postalAddress->region) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->postalAddress->postalCode) . " ";
            $str .=  $this->pull_set_or_delete_value($this->mapISO3166ToCountry($this->_address->postalAddress->country)) . " ";
            
            $str = trim(preg_replace('!\s+!', ' ', $str));
            
            $this->_local_entity['address'] = $str;
        }
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushEmails() {
        // DO NOTHING
    }
    
    // DONE
    protected function pullEmails() {
        // DO NOTHING
    }
    
    // DONE
    protected function pushTelephones() {
        // DO NOTHING
    }
    
    // DONE
    protected function pullTelephones() {
        // DO NOTHING
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
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start ");
        $this->_entity->customer = true;
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullEntity() {
        // DO NOTHING
    }
    
    // DONE
    protected function saveLocalEntity($push_to_maestrano, $status) 
    {
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start ");
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " status=" . $status);
        if ($status == constant('MnoSoaBaseEntity::STATUS_NEW_ID')) {
            $id = $this->add_customer_by_array($this->_local_entity, $push_to_maestrano);
            $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " id=" . $id);
            $this->setLocalEntityIdentifier($id);
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            $this->update_customer_by_array($this->_local_entity, $push_to_maestrano);
        }
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end ");
    }
    
    // DONE
    public function getLocalEntityIdentifier() 
    {
        return $this->_local_entity['debtor_no'];
    }
    
    public function setLocalEntityIdentifier($id)
    {
        $this->_local_entity['debtor_no'] = $id;
    }

    /*
     * HELPER FUNCTIONS
     */
    
    public function add_customer_by_array($arr, $push_to_maestrano=true) 
    {
            $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " arr=" . json_encode($arr));
        
            $address = "";
            $tax_id = "";
            $curr_code = null;
            $dimension_id = 0;
            $dimension2_id = 0;
            $credit_status = 1;
            $payment_terms = 1;
            $discount = 0;
            $pymt_discount = 0;
            $credit_limit = 0; 
            $sales_type = 1;
            $notes = "";
            
            extract($arr);

            $this->_id;
            
            if (!isset($name)) { $this->_log->error("Failed to insert organization " . $this->_id . " - name not provided"); return false; }
            if (!isset($debtor_ref)) { $this->_log->error("Failed to insert organization " . $this->_id . " - debtor_ref not provided"); return false; }
            
            return add_customer($name, $debtor_ref, $address, $tax_id, $curr_code,
            $dimension_id, $dimension2_id, $credit_status, $payment_terms, $discount, $pymt_discount, 
            $credit_limit, $sales_type, $notes);
    }

    public function update_customer_by_array($arr, $push_to_maestrano=true)
    {
            $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " arr=" . json_encode($arr));
            
            $address = "";
            $tax_id = "";
            $curr_code = null;
            $dimension_id = 0;
            $dimension2_id = 0;
            $credit_status = 1;
            $payment_terms = 1;
            $discount = 0;
            $pymt_discount = 0;
            $credit_limit = 0; 
            $sales_type = 1;
            $notes = "";
            
            extract($arr);

            if (!isset($debtor_no)) { $this->_log->error("Failed to update organization " . $this->_id . " - debtor_no not provided"); return false; }
            if (!isset($name)) { $this->_log->error("Failed to update organization " . $this->_id . " - name not provided"); return false; }
            if (!isset($debtor_ref)) { $this->_log->error("Failed to update organization " . $this->_id . " - debtor_ref not provided"); return false; }
            
            return update_customer($debtor_no, $name, $debtor_ref, $address, $tax_id, $curr_code,
            $dimension_id, $dimension2_id, $credit_status, $payment_terms, $discount, $pymt_discount,
            $credit_limit, $sales_type, $notes);
    }
}

?>