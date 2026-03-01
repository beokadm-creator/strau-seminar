<?php
include_once('./_common.php');

// 1. Add guest_hit column to g5_write_campus
$table_name = $g5['write_prefix'] . 'campus';
$row = sql_fetch(" SHOW COLUMNS FROM `{$table_name}` LIKE 'guest_hit' ");
if (!$row) {
    sql_query(" ALTER TABLE `{$table_name}` ADD `guest_hit` INT(11) NOT NULL DEFAULT '0' COMMENT '비회원 조회수' AFTER `wr_hit` ");
    echo "Added guest_hit column to {$table_name}<br>";
} else {
    echo "guest_hit column already exists in {$table_name}<br>";
}

// 2. Re-create VIEW view_board with guest_hit support
echo "Re-creating VIEW view_board...<br>";

// Drop existing view
sql_query('DROP VIEW IF EXISTS view_board', false);

$arrQuery = array();
$sql = "SHOW TABLES";
$res = sql_query($sql, true);

while ($tb = sql_fetch_array($res)) {
    $tableName = reset($tb);
    
    // Check if it's a write table
    if (preg_match('/^g5_write_/', $tableName)) {
        $boardName = str_replace('g5_write_', '', $tableName);
        
        // Check if guest_hit exists in this table
        $has_guest_hit = false;
        $cols = sql_query(" SHOW COLUMNS FROM `{$tableName}` LIKE 'guest_hit' ");
        if (sql_num_rows($cols) > 0) {
            $has_guest_hit = true;
        }
        
        // Build SELECT field for guest_hit
        $field_guest_hit = $has_guest_hit ? "guest_hit" : "0 AS guest_hit";
        
        $arrQuery[] = "
            (
                SELECT wr_id, mb_id, ca_name, wr_parent, wr_is_comment, wr_subject, wr_content, wr_name, wr_hit, {$field_guest_hit}, wr_datetime, '{$boardName}' AS tbl_name
                FROM  {$tableName}
            )
        ";
    }
}

if (count($arrQuery) > 0) {
    $sql = 'CREATE VIEW view_board AS ' . implode(' UNION ALL ', $arrQuery);
    $result = sql_query($sql, false);
    
    if ($result) {
        echo "Successfully updated VIEW view_board.<br>";
    } else {
        echo "Failed to create VIEW: " . sql_error_info() . "<br>";
        echo "Query: " . $sql . "<br>";
    }
} else {
    echo "No board tables found.<br>";
}

echo "Migration completed.";
?>
