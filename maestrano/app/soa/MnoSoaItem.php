<?php

/**
 * Mno Organization Class
 */
class MnoSoaItem extends MnoSoaBaseItem
{
    protected $_local_entity_name = "STOCK_MASTER";
    public $_local_item_id = null;
    public $_tb_pref = null;
    public $_is_new = false;
    
    protected function pushItem()
    {
        // PUSH ID
        $id = $this->getLocalEntityIdentifier();
        if (empty($id)) { return; }
        
        $mno_id = MnoSoaDB::getMnoIdByLocalId($id, $this->getLocalEntityName(), $this->getMnoEntityName());
        $this->_id = (MnoSoaDB::isValidIdentifier($mno_id)) ? $mno_id->_id : null;
        $this->_is_new = (MnoSoaDB::isNewIdentifier($mno_id)) ? true : false;
        
        $item = $this->_local_entity;

        MnoSoaLogger::debug("item=".json_encode($item));
        
        // PUSH CODE
        $this->_code = $this->push_set_or_delete_value($item->code);
        // PUSH NAME
        $this->_name = $this->push_set_or_delete_value($item->name);
        $this->_name = html_entity_decode($this->_name);
        // PUSH DESCRIPTION
        $this->_description = $this->push_set_or_delete_value($item->description);
        $this->_description = html_entity_decode($this->_description);
        // PUSH TYPE
        $this->_type = $this->mapItemTypeToMnoFormat($item->type);
        // PUSH UNIT
        $this->_unit = strtoupper($this->push_set_or_delete_value($item->unit));
        // PUSH SALE->PRICE
        if (!empty($item->salePrice) && $item->salePrice !== 0) {
            $this->_sale->price = $this->push_set_or_delete_value($item->salePrice);
        }
        // PUSH SALE->CURRENCY
        if (!empty($item->saleCurrency)) {
            $this->_sale->currency = $this->push_set_or_delete_value($item->saleCurrency);
        }
        // PUSH PURCHASE->PRICE
        if (!empty($item->purchasePrice) && $item->purchasePrice !== 0) {
            $this->_purchase->price = $this->push_set_or_delete_value($item->purchasePrice);
        }
        // PUSH PURCHASE->CURRENCY
        if (!empty($item->purchaseCurrency)) {
            $this->_purchase->currency = $this->push_set_or_delete_value($item->purchaseCurrency);
        }
        // PUSH STATUS
        $this->_status = $this->mapStatusToMnoFormat($item->status);
    }
    
    protected function post_send_hook($mno_response_id) {
        $local_id = $this->getLocalEntityIdentifier();
        
        $update_item_codes_mno_id_query = "UPDATE ".TB_PREF."item_codes SET mno_id='$mno_response_id' WHERE stock_id='$local_id'";
        $this->dbQuery($update_item_codes_mno_id_query);
        
        if ($this->_is_new) {
            if (!empty($this->_local_entity->supplier_id)) {
                $update_purch_data_query = "UPDATE ".TB_PREF."purch_data SET mno_id='$mno_response_id' WHERE id='{$this->_local_entity->purchase_price_id}'";
                MnoSoaLogger::debug("Update purchase data=".$update_purch_data_query);
                $this->dbQuery($update_purch_data_query);
            }

            if (!empty($this->_local_entity->sale_price_id)) {
                $update_purch_data_query = "UPDATE ".TB_PREF."prices SET mno_id='$mno_response_id' WHERE id='{$this->_local_entity->sale_price_id}'";
                $this->dbQuery($update_purch_data_query);
            }
        }
        
        return true;
    }
    
    protected function pullItem()
    {
        // PULL ID
        if (empty($this->_id)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }
        $local_id = MnoSoaDB::getLocalIdByMnoId($this->_id, $this->getMnoEntityName(), $this->getLocalEntityName());
        if (MnoSoaDB::isDeletedIdentifier($local_id)) { return constant('MnoSoaBaseEntity::STATUS_DELETED_ID'); }
        
        // PULL CODE
        $code = $this->pull_set_or_delete_value($this->_code);
        if (empty($code)) { return constant('MnoSoaBaseEntity::STATUS_ERROR'); }
        // PULL NAME
        $name = $this->pull_set_or_delete_value($this->_name);
        // PULL DESCRIPTION
        $description = $this->pull_set_or_delete_value($this->_description);
        // PULL TYPE
        $type = $this->mapItemTypeToLocalFormat($this->_type);
        // PULL UNIT
        $unit = strtoupper($this->pull_set_or_delete_value($this->_unit));
        
        if (!empty($unit)) {
            $unit_query = "SELECT * FROM ".TB_PREF."item_units WHERE UPPER(name)='$unit'";
            $unit_query_result = $this->dbQuery($unit_query);

            if (!$unit_query_result || !($unit_query_record = db_fetch_assoc($unit_query_result))) {
                $unit_query = "INSERT INTO ".TB_PREF."item_units (abbr, name, decimals, inactive) VALUES ('$unit','$unit', '0', '0')";
                $this->dbQuery($unit_query);
            } else {
                $unit = $unit_query_record['abbr'];
            }
        }
        
        // PULL SALE PRICE/CURRENCY
        $salePrice = floor(floatval($this->pull_set_or_delete_value($this->_sale->price, 0)));
        $saleCurrency = $this->pull_set_or_delete_value($this->_sale->currency);
        $saleCurrency = (empty($saleCurrency)) ? "USD" : $saleCurrency;
        // PULL PURCHASE PRICE/CURRENCY
        $purchasePrice = floor(floatval($this->pull_set_or_delete_value($this->_purchase->price, 0)));
        $purchaseCurrency = $this->pull_set_or_delete_value($this->_purchase->currency);
        $purchaseCurrency = (empty($purchaseCurrency)) ? "USD" : $purchaseCurrency;
        // PULL STATUS
        $status = $this->mapStatusToLocalFormat($this->_status);
        
        // UPDATE STOCK MASTER
        if (MnoSoaDB::isValidIdentifier($local_id)) { 
            $return_status = constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
            $this->_local_item_id = $local_id->_id;
            
            $stock_master_query = " UPDATE ".TB_PREF."stock_master 
                                    SET
                                        description='$name',
                                        long_description='$description',
                                        mb_flag='$type',
                                        units='$unit',
                                        inactive='$status',
                                        ref='$code'
                                    WHERE 
                                        stock_id='{$this->_local_item_id}'";
            
            $this->dbQuery($stock_master_query);
        // INSERT STOCK MASTER
        } else {
            $return_status = constant('MnoSoaBaseEntity::STATUS_NEW_ID');
            
            $timestamp = (string) time();
            $timestamp .= (string) rand(100,999);
            $this->_local_item_id = strtoupper(base_convert($timestamp, 10, 36));
            
            $stock_master_query = " INSERT ".TB_PREF."stock_master
                            (stock_id, description, long_description, units, mb_flag, sales_account, cogs_account, inventory_account, adjustment_account, assembly_account, inactive, ref, category_id, tax_type_id)
                            SELECT  '{$this->_local_item_id}', '$name', '$description', '$unit', '$type',
                                    sales.sales_account AS sales_account, 
                                    cogs.cogs_account AS cogs_account,
                                    inv.inventory_account AS inventory_account, 
                                    adj.adjustment_account AS adjustment_account,
                                    asm.assembly_account AS assembly_account,
                                    '$status', '$code', '1', '1'
                            FROM    (SELECT value as sales_account FROM ".TB_PREF."sys_prefs WHERE name='default_inv_sales_act') sales,
                                    (SELECT value as cogs_account FROM ".TB_PREF."sys_prefs WHERE name='default_cogs_act') cogs,
                                    (SELECT value as inventory_account FROM ".TB_PREF."sys_prefs WHERE name='default_inventory_act') inv,
                                    (SELECT value as adjustment_account FROM ".TB_PREF."sys_prefs WHERE name='default_adj_act') adj,
                                    (SELECT value as assembly_account FROM ".TB_PREF."sys_prefs WHERE name='default_assembly_act') asm
                            ";
            $this->dbQuery($stock_master_query);
            MnoSoaDB::addIdMapEntry($this->_local_item_id, $this->getLocalEntityName(), $this->_id, $this->getMnoEntityName());
        }
        
        $item_code_query = "SELECT * FROM ".TB_PREF."item_codes WHERE mno_id='{$this->_id}'";
        $item_code_query_result = $this->dbQuery($item_code_query);
        
        if (!$item_code_query_result || !($item_code_query_record = db_fetch_assoc($item_code_query_result))) {
            $item_insert_query = "  INSERT ".TB_PREF."item_codes
                                    (item_code, stock_id, description, inactive, mno_id)
                                    VALUES
                                    ('{$this->_local_item_id}', '{$this->_local_item_id}', '$name', '$status', '{$this->_id}')";
            $this->dbQuery($item_insert_query);
        }

        // CREATE DEFAULT SUPPLIER WITH PURCHASE CURRENCY
        $supplier_id = null;
        $supplier_name = "Default supplier ({$purchaseCurrency})";
        
        $supplier_query = "SELECT supplier_id FROM ".TB_PREF."suppliers WHERE supp_name='$supplier_name'";
        MnoSoaLogger::debug("supplier_query=$supplier_query");
        $supplier_query_result = $this->dbQuery($supplier_query);
        MnoSoaLogger::debug("supplier_query_result=$supplier_query_result");
        
        if ($supplier_query_result && ($supplier_query_record = db_fetch_assoc($supplier_query_result))) {
            MnoSoaLogger::debug("supplier_query_record=$supplier_query_record");
            $supplier_id = $supplier_query_record['supplier_id'];
            MnoSoaLogger::debug("supplier_id=$supplier_id");
        } else {
            MnoSoaLogger::debug("supplier_id=$supplier_id");
            $supplier_id = add_supplier("$supplier_name", "$supplier_name", "", "", 
                                        "","", "", "", 
                                        0, 0, 0, $purchaseCurrency, 
                                        1, "2100", "", "5060", 
                                        "", null, 0);
        }

        // CREATE PURCHASE DATA WITH PURCHASE PRICE
        $purch_data_query = "SELECT * FROM ".TB_PREF."purch_data WHERE mno_id='{$this->_id}'";
        MnoSoaLogger::debug("purch_data_query=$purch_data_query");
        $purch_data_query_result = $this->dbQuery($purch_data_query);
        MnoSoaLogger::debug("purch_data_query_result=$purch_data_query_result");

        if (!$purch_data_query_result || !($purch_data_query_record = db_fetch_assoc($purch_data_query_result))) {
            $purch_data_upsert_query = "INSERT ".TB_PREF."purch_data
                                        (supplier_id, stock_id, price, suppliers_uom, conversion_factor, supplier_description, mno_id)
                                        VALUES
                                        ('$supplier_id', '{$this->_local_item_id}', $purchasePrice, '', '1', '', '{$this->_id}')
                                        ";
        } else {
            $purch_data_upsert_query = "UPDATE ".TB_PREF."purch_data
                                        SET price='$purchasePrice', supplier_id='$supplier_id'
                                        WHERE mno_id='{$this->_id}'
                                       ";
        }
        MnoSoaLogger::debug("purch_data_upsert_query=$purch_data_upsert_query");
        $this->dbQuery($purch_data_upsert_query);
        
        // CREATE PRICE DATA WITH SALE PRICE/CURRENCY
        $price_data_query = "SELECT * FROM ".TB_PREF."prices WHERE mno_id='{$this->_id}'";
        MnoSoaLogger::debug("price_data_query=$price_data_query");
        $price_data_query_result = $this->dbQuery($price_data_query);
        MnoSoaLogger::debug("price_data_query_result=$price_data_query_result");
        
        if (!$price_data_query_result || !($price_data_query_record = db_fetch_assoc($price_data_query_result))) {
            $price_data_upsert_query = "INSERT ".TB_PREF."prices
                                        (stock_id, sales_type_id, curr_abrev, price, mno_id)
                                        VALUES
                                        ('{$this->_local_item_id}','1','$saleCurrency','$salePrice','{$this->_id}')
                                       ";
        } else {
            $price_data_upsert_query = "UPDATE ".TB_PREF."prices
                                        SET stock_id='{$this->_local_item_id}', curr_abrev='$saleCurrency',
                                            price='$salePrice'
                                        WHERE mno_id='{$this->_id}'
                                       ";
        }
        MnoSoaLogger::debug("price_data_upsert_query=$price_data_upsert_query");
        $this->dbQuery($price_data_upsert_query);
        
        /*
         * TODO - ALSO UPDATE PURCH_DATA, PRICES, SUPPLIERS(currency?)
         * TODO - FIND OTHER TABLES THAT ARE AFFECTED BY ITEM CODE UPDATE
         */
        
        return $return_status;
    }
    
    protected function saveLocalEntity($push_to_maestrano, $status) {
        // DO NOTHING
    }
    
    public function setLocalEntityIdentifier($local_identifier)
    {
        $this->_local_item_id = $local_identifier;
    }
    
    public function getLocalEntityIdentifier() {
        return $this->_local_item_id;
    }
    
    public function setTB_PREF($tb_pref)
    {
        $this->_tb_pref = $tb_pref;
    }
    
    protected function mapStatusToMnoFormat($local_status) 
    {
        switch ($local_status) {
            case 1: return "INACTIVE";
            case 0: return "ACTIVE";
        }
        
        return "INACTIVE";
    }
    
    protected function mapStatusToLocalFormat($mno_status)
    {
        $mno_status_format = $this->pull_set_or_delete_value($mno_status);
        switch($mno_status_format) {
            case "INACTIVE": return 1;
            case "ACTIVE": return 0;
        }
        return 1;
    }
    
    protected function mapItemTypeToMnoFormat($local_item_type) {
        $local_item_type_format = strtoupper($this->push_set_or_delete_value($local_item_type));
        switch ($local_item_type_format) {
            case "M": return "MANUFACTURED";
            case "B": return "PURCHASED";
            case "D": return "SERVICE";
        }
        return "PURCHASED";
    }
    
    protected function mapItemTypeToLocalFormat($mno_item_type) {
        $mno_item_type_format = strtoupper($this->pull_set_or_delete_value($mno_item_type));
        switch ($mno_item_type_format) {
            case "MANUFACTURED": return "M";
            case "PURCHASED": return "B";
            case "SERVICE": return "D";
        }
        return "B";
    }
}

?>