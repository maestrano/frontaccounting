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
    
    public static function addIdMapEntry($local_id, $local_entity_name, $mno_id, $mno_entity_name) 
	{
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        MnoSoaLogger::debug("start");
		// Fetch record
		$query = "INSERT INTO ".TB_PREF."mno_id_map (mno_entity_guid, mno_entity_name, app_entity_id, app_entity_name, db_timestamp) VALUES ("
                . db_escape($mno_id) . ", "
                . db_escape(strtoupper($mno_entity_name)) . ", "
                . db_escape($local_id) . ", "
                . db_escape(strtoupper($local_entity_name)) . ", "
                . "UTC_TIMESTAMP)";

		$result = @db_query($query);

        MnoSoaLogger::debug("addIdMapEntry query = ".$query);
        
        $db_last_inserted_id = $tmp_db_last_inserted_id;
        return (!$result) ? false : true;
    }
    
    /**
    * Get Maestrano GUID when provided with a local identifier
    * @param  	string 	local_id                Local entity identifier
    * @param    string  local_entity_name       Local entity name
    *
    * @return 	boolean Record found	
    */
    public static function getMnoIdByLocalId($local_id, $local_entity_name, $mno_entity_name)
    {
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        MnoSoaLogger::debug("start");
        $mno_entity = null;
        
	// Fetch record
	$query = "SELECT mno_entity_guid, mno_entity_name, deleted_flag FROM ".TB_PREF."mno_id_map WHERE app_entity_id="
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
        
        MnoSoaLogger::debug("returning mno_entity = ".json_encode($mno_entity));
        $db_last_inserted_id = $tmp_db_last_inserted_id;
		return $mno_entity;
    }
    
    public static function getLocalIdsByMnoId($mno_id, $mno_entity_name)
    {
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        MnoSoaLogger::debug("start");
		$local_entities = array();
        
		// Fetch record
		$query = "SELECT app_entity_id, app_entity_name, deleted_flag FROM ".TB_PREF."mno_id_map WHERE mno_entity_guid="
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
	
        MnoSoaLogger::debug("returning mno_entities = ".json_encode($local_entities));
        $db_last_inserted_id = $tmp_db_last_inserted_id;
		return $local_entities;
    }
    
    public static function getLocalIdByMnoId($mno_id, $mno_entity_name, $app_entity_name)
    {
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        MnoSoaLogger::debug("start");
		$local_entity = null;
        
		// Fetch record
		$query = "SELECT app_entity_id, app_entity_name, deleted_flag FROM ".TB_PREF."mno_id_map WHERE mno_entity_guid="
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
	
        MnoSoaLogger::debug("returning mno_entity = ".json_encode($local_entity));
        $db_last_inserted_id = $tmp_db_last_inserted_id;
		return $local_entity;
    }  
    
    public static function deleteIdMapEntry($local_id, $local_entity_name) 
    {
        global $db_last_inserted_id;
        
        $tmp_db_last_inserted_id = $db_last_inserted_id;
        
        MnoSoaLogger::debug("start");
        
        // Logically delete record
        $query = "UPDATE ".TB_PREF."mno_id_map SET deleted_flag=1 WHERE app_entity_id="
                . db_escape($local_id)
                . " and app_entity_name="
                . db_escape(strtoupper($local_entity_name));

        $result = @db_query($query);
        
        MnoSoaLogger::debug("deleteIdMapEntry query = ".$query);
        
		$db_last_inserted_id = $tmp_db_last_inserted_id;

        return (!$result) ? false : true;
    }
}

?>