<?php
include_once('./_common.php');
include_once(G5_LIB_PATH.'/json.lib.php');
require_once G5_LIB_PATH.'/ray_util.lib.php';

$common_util = new Ray_Util();

$user_no = isset($_REQUEST['user_no']) ? preg_replace('/[^0-9]/', '', $_REQUEST['user_no']) : 0;
$ret = array();

$sql = "
	select campus.wr_subject, campus.wr_3, m.*  
	from g5_content_mypage m , g5_write_campus campus 
	where m.content_no = campus.wr_id 
		and m.user_no = '{$user_no}'
";
$query = sql_query($sql);

$no = 1;

while ($row = sql_fetch_array($query)) 
{
	$percent = "0";
	$complete = "수강전";
	
	if ($row['percent'] > 0) {
		$percent = $row['percent'];
		$complete = "수강중";
	}

	if ($row['percent'] == "100") {
		$complete = "수강완료";
	}

	$gubun = $row['pre_apply'] == "Y" ? '사전':'일반';

	if ($row['type'] == "live") {
		$complete = "수강완료(실시간)";
		if ($common_util->start_day_check($row['wr_3']) == false) {
			$complete = "수강전(실시간)";
		}
	}
	$complete = iconv("EUC-KR", "UTF8", $complete);
	$sayList[] = array(
		'no' => $no++,
		'percent' => $percent, 
		'complete' => $complete,
		'subject' => $row['wr_subject'],
		'type' => 'C'
	);
}

$sql = "
	select s.wr_subject, s.wr_3, m.*  
	from g5_content_mypage_show m , g5_write_launchingShow s 
	where m.content_no = s.wr_id 
		and m.user_no = '{$user_no}'
";
$query = sql_query($sql);

$no = 1;

for ($i=0; $row=sql_fetch_array($query); $i++) {
	$percent = "0";
	$complete = "수강전";
	
	if ($row['percent'] > 0) {
		$percent = $row['percent'];
		$complete = "수강중";
	}
	
	if ($row['percent'] == "100") {
		$complete = "수강완료";
	}
	
	$gubun = $row['pre_apply'] == "Y" ? '사전':'일반';
	
	if ($row['type'] == "live") {
		$complete = "수강완료(실시간)";
		if ($common_util->start_day_check($row['wr_3']) == false) {
			$complete = "수강전(실시간)";
		}
	}
	$complete = iconv("EUC-KR", "UTF8", $complete);
	$sayList[] = array(
		'no' => $no++,
		'percent' => $percent, 
		'complete' => $complete,
		'subject' => $row['wr_subject'],
		'type' => 'L'
	);
}

echo json_encode($sayList);
?>

