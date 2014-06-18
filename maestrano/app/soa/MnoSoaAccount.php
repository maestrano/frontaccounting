<?php

/**
 * Mno Organization Class
 */
class MnoSoaAccount extends MnoSoaBaseAccount
{
    protected $_local_entity_name = "CHART_MASTER";
    protected $_local_account_id = null;
    public $_tb_pref = null;
    
    protected function pushAccount()
    {
        // PUSH ID
        $id = $this->getLocalEntityIdentifier();
        if (empty($id)) { return; }
        $mno_id = MnoSoaDB::getMnoIdByLocalId($id, $this->getLocalEntityName(), $this->getMnoEntityName());
        $this->_id = (MnoSoaDB::isValidIdentifier($mno_id)) ? $mno_id->_id : null;
        
        $account = $this->_local_entity;
        
        // PUSH CODE
        $this->_code = $this->push_set_or_delete_value($account->code);
        // PUSH NAME
        $this->_name = $this->push_set_or_delete_value($account->name);
        $this->_name = html_entity_decode($this->_name);
        // PUSH DESCRIPTION - DO NOTHING
        // PUSH CLASSIFICATION
        $this->_classification = $this->push_set_or_delete_value($account->classification);
        // PUSH STATUS
        $this->_status = $this->mapStatusToMnoFormat($account->status);
    }
    
    protected function pullAccount()
    {
        // PULL ID
        if (empty($this->_id)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }
        $local_id = MnoSoaDB::getLocalIdByMnoId($this->_id, $this->getMnoEntityName(), $this->getLocalEntityName());
        if (MnoSoaDB::isDeletedIdentifier($local_id)) { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
        
        // DETERMINE RETURN STATUS
        $return_status = (MnoSoaDB::isValidIdentifier(($local_id))) ? constant('MnoSoaBaseEntity::STATUS_EXISTING_ID') : constant('MnoSoaBaseEntity::STATUS_NEW_ID');
        // PULL CODE
        $code = $this->pull_set_or_delete_value($this->_code);
        // PULL NAME
        $name = $this->pull_set_or_delete_value($this->_name);
        // PULL DESCRIPTION - DO NOTHING
        // PULL CLASSIFICATION
        $classification = $this->pull_set_or_delete_value($this->_classification);
        // PULL STATUS
        $status = $this->mapStatusToLocalFormat($this->_status);
        // MAP TYPE
        $type = $this->mapClassificationToLocalGroup($classification);
        
        if (MnoSoaDB::isValidIdentifier(($local_id))) { 
            $this->_local_account_id = $local_id->_id;
            
            $account_query = " UPDATE ".TB_PREF."chart_master
                            SET account_name='$name', mno_classification='$classification', inactive='$status', account_type='$type'
                            WHERE account_code = '{$this->_local_account_id}'";
            $this->dbQuery($account_query);
        // INSERT ITEM
        } else {
            $return_status = constant('MnoSoaBaseEntity::STATUS_NEW_ID');
            
            $account_query = " INSERT ".TB_PREF."chart_master
                            (account_code, account_code2, account_name, mno_classification, inactive, account_type)
                            VALUES
                            ('$code', '', '$name', '$classification', '$status', '$type')
                            ";
            if (!$this->dbQuery($account_query)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }
            MnoSoaDB::addIdMapEntry($code, $this->getLocalEntityName(), $this->_id, $this->getMnoEntityName());
        }
        
        
        $bank_account_classification = $this->mapClassificationToLocalBankFormat($classification);
        if (!empty($this->_bank_account)) {
            $bank_account_query = "SELECT ".TB_PREF."chart_master FROM account_code='{$this->_local_account_id}'";
            $result = $this->dbQuery($bank_account_query);
            
            if (!$result) { return; }
            
            $bank_account = db_fetch_assoc($result);
            if ($bank_account) {
                $bank_account_upsert_query = "  INSERT ".TB_PREF."bank_accounts 
                                                (account_code, account_type, bank_account_name, bank_account_number, bank_name, bank_curr_code, biccode, inactive)
                                                VALUES  
                                                ('$code', '$bank_account_classification', '$name', '', '', 'USD', '', '$status')  
                                                ";
                
            } else {
                $bank_account_upsert_query = "  UPDATE ".TB_PREF."bank_accounts 
                                                SET account_type='$bank_account_classification', bank_account_name='$name', inactive='$status'
                                                WHERE account_code = '$code'
                                                ";
            }
            $this->dbQuery($bank_account_upsert_query);
        }
        
        return $return_status;
    }
    
    protected function mapClassificationToLocalGroup($classification) {
        if (empty($classification)) return 1;
        $classification_format = strtoupper($classification);
        
        if (substr($classification_format, 0, 5) == 'ASSET') { 
            if (strpos($classification_format, 'ASSET_FIXEDASSET') !== false) {
                return "3";
            } else if ($classification_format == 'ASSET_OTHERCURRENTASSET_INVENTORY') {
                return "2";
            } else {
                return "1";
            }
        } else if (substr($classification_format, 0, 6) == 'EQUITY') { 
            return "6"; 
        } else if (substr($classification_format, 0, 7) == 'EXPENSE') {
            if ($classification_format == 'EXPENSE_EXPENSE_PAYROLLEXPENSES') { 
                return "10"; 
            } else if ($classification_format == 'EXPENSE_EXPENSE_AUTO') { 
                return "12"; 
            } else if (strpos($classification_format, 'EXPENSE_COSTOFGOODSSOLD') !== false) {
                return "9";
            } else { 
                return "11"; 
            }
        } else if (substr($classification_format, 0, 9) == 'LIABILITY') {
            if (substr($classification_format, 0, 27) == 'LIABILITY_LONGTERMLIABILITY') {
                return "5";
            } else {
                return "4";
            }
        } else if (substr($classification_format, 0, 7) == 'REVENUE') {
            if ($classification_format == 'REVENUE_INCOME_SALESOFPRODUCTINCOME') {
                return "7";
            } else {
                return "8";
            }
        }
        
        return 1;
    }
    
    protected function mapClassificationToLocalBankFormat($classification) {
        $classification_format = strtoupper($classification);
        
        switch ($classification_format) {
            case "ASSET_BANK_SAVINGS": return "0"; break;
            case "ASSET_BANK_CHECKING": return "1"; break;
            case "LIABILITY_CREDITCARD_CREDITCARD": return "2"; break;
            case "ASSET_BANK_CASHONHAND": return "3"; break;
        }
        
        return 0;
    }
    
    // DONE
    protected function saveLocalEntity($push_to_maestrano, $status) {
        // DO NOTHING
    }
    
    public function setLocalEntityIdentifier($local_identifier)
    {
        $this->_local_account_id = $local_identifier;
    }
    
    // DONE
    public function getLocalEntityIdentifier() {
        return $this->_local_account_id;
    }
    
    public function setTB_PREF($tb_pref)
    {
        $this->_tb_pref = $tb_pref;
    }
    
    protected function mapStatusToMnoFormat($local_status) 
    {
        switch ($local_status) {
            case 0: return "ACTIVE";
            case 1: return "INACTIVE";
        }
        
        return "INACTIVE";
    }
    
    protected function mapStatusToLocalFormat($mno_status)
    {
        $mno_status_format = $this->pull_set_or_delete_value($mno_status);
        switch($mno_status_format) {
            case "ACTIVE": return 0;
            case "INACTIVE": return 1;
        }
        return 0;
    }
}

?>