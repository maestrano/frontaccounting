<?php

/**
 * Maestrano map table functions
 *
 * @author root
 */

class MnoSoaDB extends MnoSoaBaseDB {
    /**
    * Update identifier map table
    * @param  	string 	local_id                Local entity identifier
    * @param    string  local_entity_name       Local entity name
    * @param	string	mno_id                  Maestrano entity identifier
    * @param	string	mno_entity_name         Maestrano entity name
    *
    * @return 	boolean Record inserted
    */
    
    public function __construct($db, $log)
    {
	parent::__construct($db, $log);
    }
    
    public function addIdMapEntry($local_id, $local_entity_name, $mno_id, $mno_entity_name) {	
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
	// Fetch record
	$query = "INSERT INTO mno_id_map (mno_entity_guid, mno_entity_name, app_entity_id, app_entity_name, db_timestamp) VALUES ("
                . db_escape($mno_id) . ", "
                . db_escape(strtoupper($mno_entity_name)) . ", "
                . db_escape($local_id) . ", "
                . db_escape(strtoupper($local_entity_name)) . ", "
                . "UTC_TIMESTAMP)";

        $result = @db_query($query);

        $this->_log->debug("addIdMapEntry query = ".$query);
        
	if(!$result) {
            $db_last_inserted_id = $tmp_db_last_inserted_id;
            return false;
        }
        
        $db_last_inserted_id = $tmp_db_last_inserted_id;
        return true;
    }
    
    /**
    * Get Maestrano GUID when provided with a local identifier
    * @param  	string 	local_id                Local entity identifier
    * @param    string  local_entity_name       Local entity name
    *
    * @return 	boolean Record found	
    */
    public function getMnoIdByLocalIdName($local_id, $local_entity_name, $mno_entity_name)
    {
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
        $mno_entity = null;
        
	// Fetch record
	$query = "SELECT mno_entity_guid, mno_entity_name, deleted_flag from mno_id_map where app_entity_id="
                . db_escape($local_id)
                . " and app_entity_name="
                . db_escape(strtoupper($local_entity_name))
                . " and mno_entity_name="
                . db_escape(strtoupper($mno_entity_name));
        
        $result = @db_query($query);
        if ($result) {
            $row = db_fetch_assoc($result);

            // Return id value
            if ($row) {
                $mno_entity_guid = trim($row["mno_entity_guid"]);
                $mno_entity_name = trim($row["mno_entity_name"]);
                $deleted_flag = trim($row["deleted_flag"]);

                if (!empty($mno_entity_guid) && !empty($mno_entity_name)) {
                    $mno_entity = (object) array (
                        "_id" => $mno_entity_guid,
                        "_entity" => $mno_entity_name,
                        "_deleted_flag" => $deleted_flag
                    );
                }
            }
        }
        
        $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . "returning mno_entity = ".json_encode($mno_entity));
        $db_last_inserted_id = $tmp_db_last_inserted_id;
	return $mno_entity;
    }
    
    public function getLocalIdsByMnoIdName($mno_id, $mno_entity_name)
    {
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
	$local_entities = array();
        
	// Fetch record
	$query = "SELECT app_entity_id, app_entity_name, deleted_flag from mno_id_map where mno_entity_guid="
                . db_escape($mno_id)
                . " and mno_entity_name="
                . db_escape(strtoupper($mno_entity_name));

        $result = @db_query($query);
        if ($result) {
            
            while ($row = db_fetch_assoc($result)) {
                // Return id value
                $app_entity_id = trim($row["app_entity_id"]);
                $app_entity_name = trim($row["app_entity_name"]);
                $deleted_flag = trim($row["deleted_flag"]);

                if (!empty($app_entity_id) && !empty($app_entity_name)) {
                    $local_entity = (object) array (
                        "_id" => $app_entity_id,
                        "_entity" => $app_entity_name,
                        "_deleted_flag" => $deleted_flag
                    );
                    array_push($local_entities, $local_entity);
                }
            }
        }
	
        $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . "returning mno_entities = ".json_encode($local_entities));
        $db_last_inserted_id = $tmp_db_last_inserted_id;
	return $local_entities;
    }
    
    public function getLocalIdByMnoIdName($mno_id, $mno_entity_name, $app_entity_name)
    {
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
	$local_entity = null;
        
	// Fetch record
	$query = "SELECT app_entity_id, app_entity_name, deleted_flag from mno_id_map where mno_entity_guid="
                . db_escape($mno_id)
                . " and mno_entity_name="
                . db_escape(strtoupper($mno_entity_name))
                . " and app_entity_name="
                . db_escape(strtoupper($app_entity_name));

        $result = @db_query($query);
        if ($result) {
            $row = db_fetch_assoc($result);

            // Return id value
            if ($row) {
                $app_entity_id = trim($row["app_entity_id"]);
                $app_entity_name = trim($row["app_entity_name"]);
                $deleted_flag = trim($row["deleted_flag"]);

                if (!empty($app_entity_id) && !empty($app_entity_name)) {
                    $local_entity = (object) array (
                        "_id" => $app_entity_id,
                        "_entity" => $app_entity_name,
                        "_deleted_flag" => $deleted_flag
                    );
                }
            }
        }
	
        $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . "returning mno_entity = ".json_encode($local_entity));
        $db_last_inserted_id = $tmp_db_last_inserted_id;
	return $local_entity;
    }  
    
    public function deleteIdMapEntry($local_id, $local_entity_name) 
    {
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
        
        // Logically delete record
        $query = "UPDATE mno_id_map SET deleted_flag=1 WHERE app_entity_id="
                . db_escape($local_id)
                . " and app_entity_name="
                . db_escape(strtoupper($local_entity_name));

        $result = @db_query($query);
        
        $this->_log->debug("deleteIdMapEntry query = ".$query);
        
        if(!$result) {
            $db_last_inserted_id = $tmp_db_last_inserted_id;
            return false;
        }
        
        $db_last_inserted_id = $tmp_db_last_inserted_id;
        return true;
    }
}

?>