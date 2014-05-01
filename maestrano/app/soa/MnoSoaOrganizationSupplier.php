<?php

/**
 * Mno Organization Class
 */
class MnoSoaOrganizationSupplier extends MnoSoaBaseOrganization
{
    protected $_local_entity_name = "suppliers";
    
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
                $this->_local_entity = get_supplier_organization($local_id->_id);
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
        $this->_name = $this->push_set_or_delete_value($this->_local_entity['supp_name']);
	$this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end " . $this->_name);
    }
    
    // DONE
    protected function pullName($is_new_id) 
    {
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start ");
        $this->_local_entity['supp_name'] = $this->pull_set_or_delete_value($this->_name);
        if ($is_new_id) {
            $this->_local_entity['supp_ref'] = $this->pull_set_or_delete_value($this->_name);
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
	
        if ($is_new_id) {
            // POSTAL ADDRESS -> MAILING ADDRESS
            $str = "";
            $str .=  $this->pull_set_or_delete_value($this->_address->postalAddress->streetAddress) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->postalAddress->locality) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->postalAddress->region) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->postalAddress->postalCode) . " ";
            $str .=  $this->pull_set_or_delete_value($this->mapISO3166ToCountry($this->_address->postalAddress->country)) . " ";
            
            $str = trim(preg_replace('!\s+!', ' ', $str));
            
            $this->_local_entity['address'] = $str;
            
            // STREET ADDRESS -> PHYSICAL ADDRESS
            $str = "";
            $str .=  $this->pull_set_or_delete_value($this->_address->streetAddress->streetAddress) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->streetAddress->locality) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->streetAddress->region) . " ";
            $str .=  $this->pull_set_or_delete_value($this->_address->streetAddress->postalCode) . " ";
            $str .=  $this->pull_set_or_delete_value($this->mapISO3166ToCountry($this->_address->streetAddress->country)) . " ";
            
            $str = trim(preg_replace('!\s+!', ' ', $str));
            
            $this->_local_entity['supp_address'] = $str;
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
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start ");
        $this->_website->url = $this->push_set_or_delete_value($this->_local_entity['website']);
	$this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pullWebsites() {
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start ");
        $this->_local_entity['website'] = $this->pull_set_or_delete_value($this->_website->url);
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end ");
    }
    
    // DONE
    protected function pushEntity() {
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " start ");
        $this->_entity->supplier = true;
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
            $id = $this->add_supplier_by_array($this->_local_entity, $push_to_maestrano);
            $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " id=" . $id);
            $this->setLocalEntityIdentifier($id);
        } else if ($status == constant('MnoSoaBaseEntity::STATUS_EXISTING_ID')) {
            $this->update_supplier_by_array($this->_local_entity, $push_to_maestrano);
        }
        $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " end ");
    }
    
    // DONE
    public function getLocalEntityIdentifier() 
    {
        return $this->_local_entity['supplier_id'];
    }
    
    public function setLocalEntityIdentifier($id)
    {
        $this->_local_entity['supplier_id'] = $id;
    }

    /*
     * HELPER FUNCTIONS
     */
    
    public function add_supplier_by_array($arr, $push_to_maestrano=true) 
    {
            $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " arr=" . json_encode($arr));
            
            $address = "";
            $supp_address = "";
            $gst_no = "";
            $contact = "";
            $supp_account_no = "";
            $website = "";
            $bank_account = "";
            $curr_code = null;
            $payment_terms = 1;
            $tax_included = 0;
            $dimension_id = 0;
            $dimension2_id = 0;
            $tax_group_id = null;
            $credit_limit = 0;
            $purchase_account = "";
            $payable_account = "2100";
            $payment_discount_account = "5060";
            $notes = "";            
            
            extract($arr);
            
            if (!isset($supp_name)) { $this->_log->error("Failed to insert organization " . $this->_id . " - supp name not provided"); return false; }
            if (!isset($supp_ref)) { $this->_log->error("Failed to insert organization " . $this->_id . " - supp ref not provided"); return false; }
            
            return add_supplier(    $supp_name, $supp_ref, $address, $supp_address, $gst_no,
                                    $website, $supp_account_no, $bank_account, $credit_limit, $dimension_id, $dimension2_id, 
                                    $curr_code, $payment_terms, $payable_account, $purchase_account, $payment_discount_account, 
                                    $notes, $tax_group_id, $tax_included);
    }

    public function update_supplier_by_array($arr, $push_to_maestrano=true)
    {
            $this->_log->debug(__CLASS__ . '.' . __FUNCTION__ . " arr=" . json_encode($arr));
            
            $address = "";
            $supp_address = "";
            $gst_no = "";
            $contact = "";
            $supp_account_no = "";
            $website = "";
            $bank_account = "";
            $curr_code = null;
            $payment_terms = 1;
            $tax_included = 0;
            $dimension_id = 0;
            $dimension2_id = 0;
            $tax_group_id = null;
            $credit_limit = 0;
            $purchase_account = "";
            $payable_account = "2100";
            $payment_discount_account = "5060";
            $notes = "";
            
            extract($arr);

            if (!isset($supplier_id)) { $this->_log->error("Failed to insert organization " . $this->_id . " - supplier id not provided"); return false; }
            if (!isset($supp_name)) { $this->_log->error("Failed to insert organization " . $this->_id . " - supp name not provided"); return false; }
            if (!isset($supp_ref)) { $this->_log->error("Failed to insert organization " . $this->_id . " - supp ref not provided"); return false; }
            
            return update_supplier( $supplier_id, $supp_name, $supp_ref, $address, $supp_address, $gst_no, 
                                    $website, $supp_account_no, $bank_account, $credit_limit, $dimension_id, $dimension2_id, 
                                    $curr_code, $payment_terms, $payable_account, $purchase_account, $payment_discount_account, 
                                    $notes, $tax_group_id, $tax_included);
    }    
}

?>