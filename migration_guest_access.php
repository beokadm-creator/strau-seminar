<?php
// Mock server variables for CLI execution
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SCRIPT_NAME'] = 'migration_guest_access.php';
$_SERVER['HTTP_HOST'] = 'localhost';

define('G5_DISPLAY_SQL_ERROR', true);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

include_once('./_common.php');

// 1. Add guest_access column to g5_write_campus
$table_name = $g5['write_prefix'] . 'campus';

// Check if table exists
$check_table = sql_fetch(" SHOW TABLES LIKE '{$table_name}' ");
if (!$check_table) {
    echo "Error: Table {$table_name} does not exist.\n";
    exit;
}

$row = sql_fetch(" SHOW COLUMNS FROM `{$table_name}` LIKE 'guest_access' ");
if (!$row) {
    sql_query(" ALTER TABLE `{$table_name}` ADD `guest_access` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '비회원 공개 여부 (1: 공개, 0: 비공개)' ");
    echo "Added guest_access column to {$table_name}\n";
} else {
    echo "guest_access column already exists in {$table_name}\n";
}

// 2. Create g5_campus_access_log table
$log_table = "g5_campus_access_log";
if (!sql_fetch(" SHOW TABLES LIKE '{$log_table}' ")) {
    $sql = "CREATE TABLE `{$log_table}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `wr_id` INT(11) NOT NULL,
        `mb_id` VARCHAR(20) NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `ip` VARCHAR(50) NOT NULL,
        `reg_date` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `wr_id` (`wr_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    sql_query($sql);
    echo "Created {$log_table} table\n";
} else {
    echo "{$log_table} table already exists\n";
}

echo "Migration completed.\n";
?>
