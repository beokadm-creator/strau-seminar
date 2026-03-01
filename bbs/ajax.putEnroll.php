<?php
include_once('./_common.php');
include_once(G5_LIB_PATH.'/json.lib.php');

$user_no     = isset($_REQUEST['user_no']) ? preg_replace('/[^0-9]/', '', $_REQUEST['user_no']) : 0;
$license_no = isset($_REQUEST['license_no']) ? trim($_REQUEST['license_no']) : '';
$entry_code = isset($_REQUEST['entry_code']) ? trim($_REQUEST['entry_code']) : '';

$user_level = isset($_REQUEST['user_level']) ? trim($_REQUEST['user_level']) : '';

$check_auth1 = isset($_REQUEST['check_auth1']) ? trim($_REQUEST['check_auth1']) : '';

$check_auth2 = isset($_REQUEST['check_auth2']) ? trim($_REQUEST['check_auth2']) : '';

$check_auth3 = isset($_REQUEST['check_auth3']) ? trim($_REQUEST['check_auth3']) : '';

$check_auth4 = isset($_REQUEST['check_auth4']) ? trim($_REQUEST['check_auth4']) : '';

$check_auth5 = isset($_REQUEST['check_auth5']) ? trim($_REQUEST['check_auth5']) : '';

$content3 = isset($_REQUEST['content3']) ? trim($_REQUEST['content3']) : '';


$sql = " update g5_event set license_no = '$license_no'
,content3 = '$content3'
, user_level = '$user_level'
, check_auth1 = '$check_auth1'
, check_auth2 = '$check_auth2'
, check_auth3 = '$check_auth3'
, check_auth4 = '$check_auth4'
, check_auth5 = '$check_auth5'
where  no = '$user_no'  ";

$result = sql_query($sql, true);

echo $sql ;
		
