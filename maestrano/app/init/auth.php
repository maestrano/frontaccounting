<?php
//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}
require MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
//define('MY_APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
define('MY_APP_DIR', '/Users/Arnaud/Sites/apps-dev/app-frontaccounting');
require MY_APP_DIR . '/config_db.php';
//require MY_APP_DIR . '/config/some_database_config_file.php';

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// Create database connection
// Implementation notes: 
// - we assume that only one database was configured
// - we assume that tables have no prefix
$opts = array();
if ($db_connections && $db_connections[0]) {
    $db_config = $db_connections[0];
    $conn = mysqli_connect($db_config["host"], $db_config["dbuser"], $db_config["dbpassword"], $db_config["dbname"]);
    $opts['db_connection'] = $conn;
}


