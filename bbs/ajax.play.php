<?php
include_once('./_common.php');
include_once(G5_LIB_PATH.'/json.lib.php');

// if($_SERVER['REMOTE_ADDR'] == '58.229.223.164' ){
//     echo "success";
// 	exit;    
// }

$mypage_type = isset($_REQUEST['mypage_type']) ? $_REQUEST['mypage_type'] : 'campus';
$user_no = isset($_REQUEST['user_no']) ? preg_replace('/[^0-9]/', '', $_REQUEST['user_no']) : 0;
$content_no = isset($_REQUEST['content_no']) ? trim($_REQUEST['content_no']) : 0;
$duration = isset($_REQUEST['duration']) ? trim($_REQUEST['duration']) : '';
$seconds = isset($_REQUEST['seconds']) ? trim($_REQUEST['seconds']) : '';
$percent = isset($_REQUEST['percent']) ? trim($_REQUEST['percent']) : '';

$mypage_table = $mypage_type == 'campus' ? 'g5_content_mypage' : 'g5_content_mypage_show';

$sql = " update ".$mypage_table." set seconds = '$seconds' , percent = '$percent' , total_time = '$duration' , update_datetime = '".G5_TIME_YMDHIS."' where  user_no = '$user_no' and content_no = '$content_no' ";
$result = sql_query($sql, true);

echo "success";
?>