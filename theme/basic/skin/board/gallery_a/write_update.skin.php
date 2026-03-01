<?php
if (!defined('_GNUBOARD_')) exit;

//  비회원 공개 설정 처리
// guest_access 컬럼이 g5_write_campus 테이블에 존재해야 함.

$guest_access = (isset($_POST['guest_access']) && $_POST['guest_access']) ? 1 : 0;
$log_table = "g5_campus_access_log";

// 현재 값 조회 (변경 여부 확인용)
$row = sql_fetch(" select guest_access from {$write_table} where wr_id = '{$wr_id}' ");
$old_access = isset($row['guest_access']) ? $row['guest_access'] : 0;

// 값 업데이트
$sql = " update {$write_table} set guest_access = '{$guest_access}' where wr_id = '{$wr_id}' ";
sql_query($sql);

// 로깅 처리
$log_action = '';
if ($w == 'u') {
    if ($old_access != $guest_access) {
        $log_action = $guest_access ? '비회원 공개로 변경' : '비회원 비공개로 변경';
    }
} else if ($w == '') {
    // 신규 작성
    if ($guest_access) {
        $log_action = '신규 등록 (비회원 공개)';
    }
}

if ($log_action) {
    // 로그 테이블 존재 여부 확인 (혹시 마이그레이션이 안되었을 경우 대비)
    // 매번 체크하는건 비효율적이나 안전을 위해.. 또는 생략하고 에러나게 둠. 
    // 여기서는 그냥 insert 시도.
    
    $sql_log = " insert into {$log_table}
        set wr_id = '{$wr_id}',
            mb_id = '{$member['mb_id']}',
            action = '{$log_action}',
            ip = '{$_SERVER['REMOTE_ADDR']}',
            reg_date = '".G5_TIME_YMDHIS."' ";
    sql_query($sql_log, false); // false to suppress error if table doesn't exist
}
?>
