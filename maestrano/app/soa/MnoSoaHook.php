<?php

function get_customer_organization($customer_id)
{
        $sql = "SELECT * FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($customer_id);
        $result = db_query($sql,"The customer could not be found");

        return db_fetch($result);
}

function get_supplier_organization($supplier_id)
{
        $sql = "SELECT * FROM ".TB_PREF."suppliers WHERE supplier_id=".db_escape($supplier_id);
        $result = db_query($sql,"The supplier could not be found");

        return db_fetch($result);
}

function mno_hook_push_supplier_organization($supplier_id)
{
    global $opts;

    $arr = get_supplier_organization($supplier_id);

            MnoSoaLogger::debug("suppliers=" . json_encode($arr));

    try {
        // Get Maestrano Service
        $maestrano = MaestranoService::getInstance();

        if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
             // SEND SUPPLIER
             $mno_org=new MnoSoaOrganizationSupplier();
             $mno_org->send($arr);
             $mno_response_id = $mno_org->getMnoResponseId();
             $mno_request_message = $mno_org->getMnoRequestMessageAsObject();

             // CHECK IF ALSO CUSTOMER
             if (!empty($mno_response_id) && !empty($mno_request_message)) {
                $local_entities = $mno_org->getLocalIdsByMnoIdName($mno_response_id, "organizations");

                $customer_id = null;
                foreach ($local_entities as $local_id) {
                   if (MnoSoaDB::isValidIdentifier($local_id) && $local_id->_entity == "DEBTORS_MASTER") {
                       $customer_id = $local_id->_id;
                       break;
                   }
                }

                if(!empty($customer_id)) {
                    $mno_request_message->id = $mno_response_id;
                    $mno_org=new MnoSoaOrganizationCustomer();
                    $mno_org->receive($mno_request_message);
                }
             }
        }
    } catch (Exception $ex) {
        // skip
    }
}

function mno_hook_push_customer_organization($customer_id) 
{
    global $opts;

    $arr = get_customer_organization($customer_id);

    try {
        // Get Maestrano Service
        $maestrano = MaestranoService::getInstance();

        if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
             $mno_org=new MnoSoaOrganizationCustomer();
             $mno_org->send($arr);
             $mno_response_id = $mno_org->getMnoResponseId();
             $mno_request_message = $mno_org->getMnoRequestMessageAsObject();

             // CHECK IF ALSO SUPPLIER
             if (!empty($mno_response_id) && !empty($mno_request_message)) {
                $local_entities = $mno_org->getLocalIdsByMnoIdName($mno_response_id, "organizations");

                $supplier_id = null;
                foreach ($local_entities as $local_id) {
                   if (MnoSoaDB::isValidIdentifier($local_id) && $local_id->_entity == "SUPPLIERS") {
                       $supplier_id = $local_id->_id;
                       break;
                   }
                }

                if(!empty($supplier_id)) {
                    $mno_request_message->id = $mno_response_id;
                    $mno_org=new MnoSoaOrganizationSupplier();
                    $mno_org->receive($mno_request_message);
                }
             }
        }
    } catch (Exception $ex) {
        // skip
    }
}

function process_notification ($notification) {
    global $opts;
    
    $notification_entity = strtoupper(trim($notification->entity));
    
    MnoSoaLogger::debug("Notification = ". json_encode($notification));
    
    switch ($notification_entity) {
	    case "ORGANIZATIONS":
                if (class_exists('MnoSoaOrganizationCustomer')) {
                    MnoSoaLogger::debug("Notification is an organization");
                    $mno_soa_base_organization = new MnoSoaBaseOrganization();
                    MnoSoaLogger::debug("Notification after instantiation");
                    $mno_entity = $mno_soa_base_organization->getMnoEntity($notification);
                    process_organization_entity($mno_entity);
                }
                break;
            case "PERSONS":
                if (class_exists('MnoSoaPerson')) {
                    MnoSoaLogger::debug("Notification is a person");
                    $mno_person = new MnoSoaPerson();
                    MnoSoaLogger::debug("Notification after instantiation");
                    $mno_person->receiveNotification($notification);
                    return $mno_person;
                }
                break;
            case "ACCOUNTS":
                if (class_exists('MnoSoaAccount')) {
                        MnoSoaLogger::debug("Notification is an account");
                        $mno_account = new MnoSoaAccount();
                        MnoSoaLogger::debug("Notification after instantiation");
                        $mno_account->receiveNotification($notification);
                        return $mno_account;
                }
                break;
            case "ITEMS":
                if (class_exists('MnoSoaItem')) {
                        MnoSoaLogger::debug("Notification is an item");
                        $mno_item = new MnoSoaItem();
                        MnoSoaLogger::debug("Notification after instantiation");
                        $mno_item->receiveNotification($notification);
                        return $mno_item;
                }
                break;
    }
    MnoSoaLogger::debug("Notification processed");

    return true;
}

function process_organization_entity($mno_entity)
{    
    if (!empty($mno_entity->entity)) {
        if (!empty($mno_entity->entity->customer)) {
            $mno_organization = new MnoSoaOrganizationCustomer();
            $mno_organization->receive($mno_entity);
        }
        if (!empty($mno_entity->entity->supplier)) {
            $mno_organization = new MnoSoaOrganizationSupplier();
            $mno_organization->receive($mno_entity);
        }
    }

	return true;
}

function mno_hook_push_person($person_id, $entity_id, $type) {
    global $opts;
    
    $sql = "SELECT * FROM ".TB_PREF."crm_contacts WHERE person_id=".db_escape($person_id)." and entity_id=". db_escape($entity_id) . " and type=" . db_escape($type);

    $result = db_query($sql,"The contact could not be found");

    if ($result) {

        $contacts = array();
        while ($contact = db_fetch($result)) {
            array_push($contacts, $contact);
        }

        $sql = "SELECT * FROM ".TB_PREF."crm_persons WHERE id=".db_escape($person_id);
        $result = db_query($sql,"The person could not be found");

        if ($result) {
            $arr = db_fetch($result);
            $arr["contacts"] = $contacts;

            try {
                // Get Maestrano Service
                $maestrano = MaestranoService::getInstance();

                if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
                     $mno_org=new MnoSoaPerson();
                     $mno_org->send($arr);
                }
            } catch (Exception $ex) {
                // skip
            }
        }
    }
}

function mno_hook_push_item($item_id) {
    global $opts;
    
    if (empty($item_id)) { return; }
    
    $sql = "SELECT  sm.stock_id as id, sm.ref as code, sm.description as name, sm.long_description as description, 
                    sm.mb_flag as type, units.name as unit, pr.price as salePrice, pr.curr_abrev as saleCurrency, 
                    prdata.price as purchasePrice, supp.curr_code as purchaseCurrency, sm.inactive as status,
                    prdata.suppId as supplier_id, pr.id as sale_price_id, prdata.id as purchase_price_id
            FROM    ".TB_PREF."stock_master sm
                    LEFT OUTER JOIN (SELECT min(id) as id, supplier_id as suppId, stock_id, price FROM ".TB_PREF."purch_data GROUP BY stock_id) prdata ON prdata.stock_id=sm.stock_id
                    LEFT OUTER JOIN (SELECT min(id) as id, stock_id, price, curr_abrev FROM ".TB_PREF."prices GROUP BY stock_id) pr ON sm.stock_id = pr.stock_id 
                    LEFT OUTER JOIN ".TB_PREF."suppliers supp ON prdata.suppId = supp.supplier_id
                    LEFT OUTER JOIN ".TB_PREF."item_units units ON units.abbr = sm.units
            WHERE   sm.stock_id = '$item_id'";
    
    $result = db_query($sql);
    if (!$result) { return; }

    $item = db_fetch($result);
    if (empty($item)) { return; }
    
    $item = (object) $item;

    try {
        // Get Maestrano Service
        $maestrano = MaestranoService::getInstance();

        if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
             $mno_item=new MnoSoaItem();
             $mno_item->setLocalEntityIdentifier($item_id);
             $mno_item->setTB_PREF(TB_PREF);
             $mno_item->send($item);
        }
    } catch (Exception $ex) {
        // skip
    }
}

function mno_hook_push_account($account_id) {
    global $opts;
    
    if (empty($account_id)) { return; }
    
    $sql = "SELECT  chart.account_code as code, chart.account_name as name, chart.mno_classification as classification, chart.inactive as status
            FROM    ".TB_PREF."chart_master chart
            WHERE   chart.account_code = '$account_id'";
    
    $result = db_query($sql);
    if (!$result) { return; }

    $account = db_fetch($result);
    if (empty($account)) { return; }
    
    $account = (object) $account;

    try {
        // Get Maestrano Service
        $maestrano = MaestranoService::getInstance();

        if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
             $mno_account=new MnoSoaAccount();
             $mno_account->setLocalEntityIdentifier($account_id);
             $mno_account->setTB_PREF(TB_PREF);
             $mno_account->send($account);
        }
    } catch (Exception $ex) {
        // skip
    }
}

?>