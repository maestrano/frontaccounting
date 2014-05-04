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

        error_log("suppliers=" . json_encode($arr));

        try {
            // Get Maestrano Service
            $maestrano = MaestranoService::getInstance();

            if ($maestrano->isSoaEnabled() and $maestrano->getSoaUrl()) {
                 // SEND SUPPLIER
                 $mno_org=new MnoSoaOrganizationSupplier($opts['db_connection'], new MnoSoaBaseLogger());
                 $mno_org->send($arr);
                 $mno_response_id = $mno_org->getMnoResponseId();
                 $mno_request_message = $mno_org->getMnoRequestMessageAsObject();

                 // CHECK IF ALSO CUSTOMER
                 if (!empty($mno_response_id) && !empty($mno_request_message)) {
                    $local_entities = $mno_org->getLocalIdsByMnoIdName($mno_response_id, "organizations");

                    $customer_id = null;
                    foreach ($local_entities as $local_id) {
                       if ($mno_org->isValidIdentifier($local_id) && $local_id->_entity == "DEBTORS_MASTER") {
                           $customer_id = $local_id->_id;
                           break;
                       }
                    }

                    if(!empty($customer_id)) {
                        $mno_request_message->id = $mno_response_id;
                        $mno_org=new MnoSoaOrganizationCustomer($opts['db_connection'], new MnoSoaBaseLogger());
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
                 $mno_org=new MnoSoaOrganizationCustomer($opts['db_connection'], new MnoSoaBaseLogger());
                 $mno_org->send($arr);
                 $mno_response_id = $mno_org->getMnoResponseId();
                 $mno_request_message = $mno_org->getMnoRequestMessageAsObject();

                 // CHECK IF ALSO SUPPLIER
                 if (!empty($mno_response_id) && !empty($mno_request_message)) {
                    $local_entities = $mno_org->getLocalIdsByMnoIdName($mno_response_id, "organizations");

                    $supplier_id = null;
                    foreach ($local_entities as $local_id) {
                       if ($mno_org->isValidIdentifier($local_id) && $local_id->_entity == "SUPPLIERS") {
                           $supplier_id = $local_id->_id;
                           break;
                       }
                    }

                    if(!empty($supplier_id)) {
                        $mno_request_message->id = $mno_response_id;
                        $mno_org=new MnoSoaOrganizationCustomer($opts['db_connection'], new MnoSoaBaseLogger());
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
    
    $log = new MnoSoaBaseLogger();
    
    $notification_entity = strtoupper(trim($notification->entity));
    
    $log->debug("Notification = ". json_encode($notification));
    
    switch ($notification_entity) {
	    case "ORGANIZATIONS":
                if (class_exists('MnoSoaOrganizationCustomer')) {
                    $log->debug("Notification is an organization");
                    $mno_soa_base_organization = new MnoSoaBaseOrganization($opts['db_connection'], $log);
                    $log->debug("Notification after instantiation");
                    $mno_entity = $mno_soa_base_organization->getMnoEntity($notification);
                    process_organization_entity($mno_entity);
                }
		break;
            case "PERSONS":
                if (class_exists('MnoSoaPerson')) {
                    $log->debug("Notification is a person");
                    $mno_person = new MnoSoaPerson($opts['db_connection'], $log);
                    $log->debug("Notification after instantiation");
                    $mno_person->receiveNotification($notification);
                    return $mno_person;
                }
		break;
    }
    $log->debug("Notification processed");
}

function process_organization_entity($mno_entity)
{
    global $opts;
    
    $log = new MnoSoaBaseLogger();
    
    if (!empty($mno_entity->entity)) {
        if (!empty($mno_entity->entity->customer)) {
            $mno_organization = new MnoSoaOrganizationCustomer($opts['db_connection'], $log);
            $mno_organization->receive($mno_entity);
        }
        if (!empty($mno_entity->entity->supplier)) {
            $mno_organization = new MnoSoaOrganizationSupplier($opts['db_connection'], $log);
            $mno_organization->receive($mno_entity);
        }
    }
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
                     $mno_org=new MnoSoaPerson($opts['db_connection'], new MnoSoaBaseLogger());
                     $mno_org->send($arr);
                }
            } catch (Exception $ex) {
                // skip
            }
        }
    }
}
    
?>