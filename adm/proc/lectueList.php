<?php
include_once('./_common.php');
$sub_menu = "300980"; // 강의 관리 권한

$data = array();
$data['error'] = '';

$data['error'] = auth_check_menu($auth, $sub_menu, 'w', true);
if ($data['error'])
    die($common_util->json_encode($data));


$type = $_POST['type'];
$arr_type = array('lecture_list');
if (!in_array($type, $arr_type)) {
    $data['error'] = '올바른 방법으로 이용해 주십시오.';
    die($common_util->json_encode($data));
}


if ($type == 'lecture_list') {
    $mypage_type = isset($_POST['mypage_type']) ? $_POST['mypage_type'] : 'campus';
    $wr_id = isset($_POST['wr_id']) ? trim($_POST['wr_id']) : '';

    $mypage_table = $mypage_type == 'campus' ? 'g5_content_mypage' : 'g5_content_mypage_show';

    $resultList = array();
    
    if ($wr_id == "") {
        $data['error'] = '올바른 방법으로 이용해 주십시오.';
    } else {

        $sql = "
            SELECT ".$mypage_type.".wr_subject, ".$mypage_type.".wr_3, c.*, m.mb_id, m.mb_name 
            FROM ".$mypage_table." c 
                JOIN g5_write_".$mypage_type." ".$mypage_type." ON ".$mypage_type.".wr_id = c.content_no 
                LEFT JOIN g5_member m ON m.mb_password <> '' and m.mb_no = c.user_no 
            WHERE c.content_no = '{$wr_id}' ";
        $query = sql_query($sql);
        
        $no = 1;
        $complete = "수강신청";
        
        while ($row = sql_fetch_array($query)) 
        {
            $percent = "0";
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
            
            $resultList[] = array(
                'no' => $no++,
                'gubun' => $gubun, 
                'mb_id' => $row['mb_id'], 
                'mb_name' => $row['mb_name'], 
                'percent' => $percent, 
                'complete' => $row['complete'], 
                'complete2' => $complete,
                'subject' => $row['wr_subject']
            ); 
        }

        $data['resultList'] = $resultList;
        $data['result'] = 'success';
        $data['sql'] = $sql;
    }
    
}

die($common_util->json_encode($data));
?>