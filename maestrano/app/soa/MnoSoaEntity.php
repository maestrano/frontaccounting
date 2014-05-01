<?php

/**
 * Maestrano map table functions
 *
 * @author root
 */

class MnoSoaEntity extends MnoSoaBaseEntity {    
    public function getUpdates($timestamp)
    {
        $this->_log->info(__FUNCTION__ .  " start getUpdates (timestamp=" . $timestamp . ")");
        $msg = $this->callMaestrano("GET", "updates" . '/' . $timestamp);
        if (empty($msg)) { return false; }
        $this->_log->debug(__FUNCTION__ .  " after maestrano call");
        if (!empty($msg->organizations)) {
            $this->_log->debug(__FUNCTION__ .  " has organizations");
            foreach ($msg->organizations as $organization) {
                $this->_log->debug(__FUNCTION__ .  " org id = " . $organization->id);
                process_organization_entity($organization);
            }
        }
        if (!empty($msg->persons)) {
            $this->_log->debug(__FUNCTION__ . " has persons");
            foreach ($msg->persons as $person) {
                $this->_log->debug(__FUNCTION__ .  " person id = " . $person->id);
                try {
                    $mno_person = new MnoSoaPerson($this->_db, $this->_log);
                    $mno_person->receive($person);
                } catch (Exception $e) {
                }
            }
        }
        $this->_log->info(__FUNCTION__ .  " successful (timestamp=" . $timestamp . ")");
        return true;
    }
}
