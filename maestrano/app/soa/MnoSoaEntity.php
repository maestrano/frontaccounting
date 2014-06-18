<?php

/**
 * Maestrano map table functions
 *
 * @author root
 */

class MnoSoaEntity extends MnoSoaBaseEntity {
    public function getUpdates($timestamp)
    {
        MnoSoaLogger::info("start getUpdates (timestamp=" . $timestamp . ")");
        $msg = $this->callMaestrano("GET", "updates" . '/' . $timestamp);
        if (empty($msg)) { return false; }
        MnoSoaLogger::debug("after maestrano call");
        if (!empty($msg->organizations)) {
            MnoSoaLogger::debug("has organizations");
            foreach ($msg->organizations as $organization) {
                MnoSoaLogger::debug("org id = " . $organization->id);
                process_organization_entity($organization);
            }
        }
        if (!empty($msg->persons)) {
            MnoSoaLogger::debug("has persons");
            foreach ($msg->persons as $person) {
                MnoSoaLogger::debug("person id = " . $person->id);
                try {
                    $mno_person = new MnoSoaPerson();
                    $mno_person->receive($person);
                } catch (Exception $e) {
                }
            }
        }
		if (!empty($msg->accounts)) {
            MnoSoaLogger::debug("has accounts");
            foreach ($msg->accounts as $account) {
                MnoSoaLogger::debug("account id = " . $account->id);
                try {
                    $mno_account = new MnoSoaAccount();
                    $mno_account->receive($account);
                } catch (Exception $e) {
                }
            }
		}
		if (!empty($msg->items)) {
            MnoSoaLogger::debug("has items");
            foreach ($msg->items as $item) {
                MnoSoaLogger::debug("item id = " . $item->id);
                try {
                    $mno_item = new MnoSoaItem();
                    $mno_item->receive($item);
                } catch (Exception $e) {
                }
            }
		}
        MnoSoaLogger::info("successful (timestamp=" . $timestamp . ")");
        return true;
    }
}
