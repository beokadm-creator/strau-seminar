<?php
$sub_menu = "200100";
include_once("./_common.php");

$sql = " update g5_event set entry_code = license_no where no = '{$idx}' ";
sql_query($sql,true);

goto_url("./enrollment_list.php?$qstr");