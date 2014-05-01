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
define('MY_APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
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

// If POST then put it in session (access via index.php)
// If $_SESSION then put it in opts (access via consume.php)
if ($_POST['company_login_name']) {
  $_SESSION['company_login_name'] = $_POST['company_login_name'];
}

if ($_SESSION['company_login_name']) {
  $opts['company_id'] = $_SESSION['company_login_name'];
}



