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
chdir(MY_APP_DIR);
$path_to_root = MY_APP_DIR;
require 'config_db.php';
require 'includes/db/sql_functions.inc';
require 'includes/errors.inc';
require 'includes/db/connect_db.inc';
require 'admin/db/security_db.inc';
require 'admin/db/users_db.inc';
require 'includes/lang/gettext.php';
require 'includes/lang/language.php';
require 'includes/current_user.inc';
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

// Set the frontaccounting session name
$session_name = 'FA' . md5(dirname(MY_APP_DIR . "/includes/session.inc"));
session_name($session_name);


