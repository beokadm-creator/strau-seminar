<?php
include_once('./_common.php');

$referral_code = isset($_POST['referral_code']) ? trim($_POST['referral_code']) : '';
$test_mode = isset($_POST['test_mode']) ? intval($_POST['test_mode']) : 0;

if (!$referral_code) {
    echo json_encode(array('success' => false, 'message' => '추천인 코드를 입력해주세요.'));
    exit;
}

// 테스트 모드인 경우 테스트 데이터베이스 연결
if ($test_mode) {
    $mysql_host = '121.78.91.42';
    $mysql_user = 'iuser07495';
    $mysql_password = 'printer!@12';
    $mysql_db = 'idb07495';

    $test_conn = mysql_connect($mysql_host, $mysql_user, $mysql_password);
    if (!$test_conn) {
        echo json_encode(array('success' => false, 'message' => '데이터베이스 연결 실패'));
        exit;
    }

    mysql_select_db($mysql_db, $test_conn);
    mysql_query("SET NAMES utf8", $test_conn);
    
    // 추천인 코드로 회원 검색
    $sql = "SELECT mb_id, mb_name, mb_nick FROM g5_member WHERE mb_referral_code = '$referral_code'";
    $result = mysql_query($sql, $test_conn);
    
    if ($result && mysql_num_rows($result) > 0) {
        $row = mysql_fetch_assoc($result);
        echo json_encode(array(
            'success' => true, 
            'message' => '추천인 확인됨: ' . $row['mb_name'] . ' (' . $row['mb_id'] . ')',
            'member_id' => $row['mb_id']
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => '유효하지 않은 추천인 코드입니다.'));
    }
    
    mysql_close($test_conn);
} else {
    // 라이브 모드 - 기존 g5_member 테이블 사용
    include_once(G5_LIB_PATH.'/referral_new.lib.php');
    
    $referrer = get_member_by_referral_code($referral_code);
    if ($referrer) {
        echo json_encode(array(
            'success' => true, 
            'message' => '추천인 확인됨: ' . $referrer['mb_name'] . ' (' . $referrer['mb_id'] . ')',
            'member_id' => $referrer['mb_id']
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => '유효하지 않은 추천인 코드입니다.'));
    }
}
?>