<?php

//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}
require_once MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
define('MY_APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
chdir(MY_APP_DIR);

require_once 'config_db.php';
require_once 'includes/errors.inc';
require_once 'includes/db/sql_functions.inc';
require_once 'includes/db/connect_db.inc';
require_once 'sales/includes/db/customers_db.inc';
require_once 'purchasing/includes/db/suppliers_db.inc';
require_once 'includes/db/crm_contacts_db.inc';
$path_to_root = ".";
require_once 'includes/current_user.inc';
        
//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// Create database connection
// Implementation notes: 
// - we assume that only one database was configured
// - we assume that tables only have prefix 0_
$opts = array();
if ($db_connections && $db_connections[0]) {
    $db_config = $db_connections[0];
    error_log("db_config=" . json_encode($db_config));
    
    $conn = mysql_connect($db_config["host"], $db_config["dbuser"], $db_config["dbpassword"], 0);
    if (!$conn) {
        die ("Failed to connect to database : " . mysql_error());
    }
    
    $db_selected = mysql_select_db($db_config["dbname"]);
    
    if (!$db_selected) {
        die ("Failed to open database name : " . mysql_error());
    }
    
    $opts['db_connection'] = $conn;
    $db = $conn;
}

