<?php
// Mock server variables for CLI
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SCRIPT_NAME'] = 'check_db.php';
$_SERVER['HTTP_HOST'] = 'localhost';

define('G5_DISPLAY_SQL_ERROR', true);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

include_once('./_common.php');

echo "DB Host: " . G5_MYSQL_HOST . "\n";
echo "DB User: " . G5_MYSQL_USER . "\n";
echo "DB Name: " . G5_MYSQL_DB . "\n";

// 1. Check guest_access column
$table_name = $g5['write_prefix'] . 'campus';
$row = sql_fetch(" SHOW COLUMNS FROM `{$table_name}` LIKE 'guest_access' ");
if ($row) {
    echo "[OK] 'guest_access' column exists in {$table_name}.\n";
} else {
    echo "[FAIL] 'guest_access' column MISSING in {$table_name}.\n";
}

// 2. Check log table
$log_table = "g5_campus_access_log";
if (sql_fetch(" SHOW TABLES LIKE '{$log_table}' ")) {
    echo "[OK] Table '{$log_table}' exists.\n";
} else {
    echo "[FAIL] Table '{$log_table}' MISSING.\n";
}
?>
