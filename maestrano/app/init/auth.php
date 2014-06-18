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
//$path_to_root = '.';
require_once 'config_db.php';
include_once 'includes/db/sql_functions.inc';
include_once 'includes/errors.inc';
include_once 'includes/db/connect_db.inc';
include_once 'admin/db/security_db.inc';
include_once 'admin/db/users_db.inc';
include_once 'includes/lang/gettext.php';
$path_to_root = '.';
error_log("path_to_root=$path_to_root");
require_once 'includes/lang/language.php';
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
    $conn = mysqli_connect($db_config["host"], $db_config["dbuser"], $db_config["dbpassword"], $db_config["dbname"]);
    $opts['db_connection'] = $conn;
}

// Set the frontaccounting session name
$session_name = 'FA' . md5(dirname(MY_APP_DIR . "/includes/session.inc"));
session_name($session_name);
session_start();

error_log("POST=".json_encode($_POST));

// If POST then put it in session (access via index.php)
// If $_SESSION then put it in opts (access via consume.php)
if ($_POST['company_login_name']) {
  $_SESSION['company_login_name'] = $_POST['company_login_name'];
}

if ($_SESSION['company_login_name']) {
  $opts['company_id'] = $_SESSION['company_login_name'];
}



