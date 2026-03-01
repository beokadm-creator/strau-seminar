<?php
$sub_menu = "200100";
include_once("./_common.php");

check_demo();

auth_check_menu($auth, $sub_menu, "d");

check_admin_token();

$sql = " delete from g5_event where no = '{$idx}' ";
sql_query($sql);

goto_url("./enrollment_list.php?$qstr");