<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('_GNUBOARD_', true); // Define constant to bypass security check

echo "Step 1: Start\n";

include_once('./data/dbconfig.php');
echo "Step 2: Config Loaded\n";
echo "Host: " . G5_MYSQL_HOST . "\n";

// Try connecting with a timeout
$conn = mysqli_init();
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$connect = mysqli_real_connect($conn, G5_MYSQL_HOST, G5_MYSQL_USER, G5_MYSQL_PASSWORD, G5_MYSQL_DB);

if (!$connect) {
    echo "Step 3: Connection Failed: " . mysqli_connect_error() . "\n";
} else {
    echo "Step 3: Connection Success\n";
    
    // Check column manually
    $result = mysqli_query($conn, "SHOW COLUMNS FROM g5_write_campus LIKE 'guest_access'");
    if (mysqli_num_rows($result) > 0) {
        echo "[OK] 'guest_access' column exists.\n";
    } else {
        echo "[FAIL] 'guest_access' column MISSING. Attempting to add...\n";
        
        // Try migration here directly if connected
        $sql = "ALTER TABLE `g5_write_campus` ADD `guest_access` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '비회원 공개 여부 (1: 공개, 0: 비공개)'";
        if (mysqli_query($conn, $sql)) {
            echo "[SUCCESS] Column added.\n";
        } else {
            echo "[ERROR] Failed to add column: " . mysqli_error($conn) . "\n";
        }
    }
    
    // Check log table
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'g5_campus_access_log'");
    if (mysqli_num_rows($result) > 0) {
         echo "[OK] Log table exists.\n";
    } else {
        echo "[FAIL] Log table MISSING. Attempting to create...\n";
        $sql = "CREATE TABLE `g5_campus_access_log` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `wr_id` INT(11) NOT NULL,
            `mb_id` VARCHAR(20) NOT NULL,
            `action` VARCHAR(50) NOT NULL,
            `ip` VARCHAR(50) NOT NULL,
            `reg_date` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `wr_id` (`wr_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        if (mysqli_query($conn, $sql)) {
            echo "[SUCCESS] Log table created.\n";
        } else {
            echo "[ERROR] Failed to create log table: " . mysqli_error($conn) . "\n";
        }
    }
}
?>
