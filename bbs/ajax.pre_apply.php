<?php
include_once('./_common.php');
include_once(G5_LIB_PATH.'/json.lib.php');
require_once G5_LIB_PATH.'/ray_util.lib.php';

$common_util = new Ray_Util();
$common_util->chkUrl();

$data = array();
$data['error'] = "";

if ($data['error'])
    die($common_util->json_encode($data));


$type = $_POST['type'];
$arr_type = array('pre_apply');
if (!in_array($type, $arr_type)) {
    $data['error'] = '올바른 방법으로 이용해 주십시오.';
    die($common_util->json_encode($data));
}

if ($type == 'pre_apply') {
    $wr_id = isset($_POST['no']) ? preg_replace('/[^0-9]/', '', $_POST['no']) : 0;
    $mypage_type = isset($_POST['mypage_type']) ? $_POST['mypage_type'] : 'campus';

    $mypage_table = $mypage_type == 'campus' ? 'g5_content_mypage' : 'g5_content_mypage_show';

    if ($wr_id == 0 || !$is_member) {
        $data['error'] = '올바른 방법으로 이용해 주십시오.';
    } else {
        $mb_no = isset($member['mb_no']) ? preg_replace('/[^0-9]/', '', $member['mb_no']) : 0;
        $sql = "select count(no) as cnt from ".$mypage_table." where content_no = {$wr_id} and user_no = {$mb_no} order by no ";
        $row = sql_fetch($sql);
        
        if ($row['cnt'] == 0) {
            $sql = " SELECT * FROM g5_write_".$mypage_type." WHERE wr_is_comment = 0 AND wr_id = '{$wr_id}' ";
            $lec = sql_fetch($sql, true);

            $set_data = array();
            $set_data[] = " content_no = '{$wr_id}' ";
            if ($lec['wr_4'] == "") {
                $set_data[] = " type = 'live' ";
            }
            $set_data[] = " user_no = '{$mb_no}' ";
            $set_data[] = " name = '{$user_name}' ";
            $set_data[] = " pre_apply = 'Y' ";
            $set_data[] = " reg_datetime = '".G5_TIME_YMDHIS."' ";
            $sql_set = ' set '.implode(' , ', $set_data);

            $sql = " insert into ".$mypage_table." {$sql_set} "; 
            sql_query($sql,true);

            // 사전 신청 후 메일 발송 처리.
            if ($lec['wr_id'] > 0) {
                $to_mail = $member['mb_email']; // 메일 받는 사람
                $subject = '[스트라우만] 강의 사전신청이 완료되었습니다.';
                include_once(G5_LIB_PATH.'/mailer.lib.php');
    
                ob_start();
                include_once ('./email_apply.php');
                $content = ob_get_contents();
                ob_end_clean();
    
                $content = str_replace("{강의제목}", $lec['wr_subject'], $content);
                $content = str_replace("{강의날짜}", $lec['wr_3'], $content);
                
                mailer("스트라우만", COMMON_SEND_EMAIL, $to_mail, $subject, $content, 1);    
            }
            
            $data['result'] = 'success'; // 신규 신청 처리
        } else {
            $data['result'] = 'already'; // 이미 신청된 상태
        }
    }
}

die($common_util->json_encode($data));

?>

