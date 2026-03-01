<?php
include_once('./_common.php');
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

// 테스트 모드 확인
if (!isset($_POST['test_mode']) || !$_POST['test_mode']) {
    alert('테스트 모드가 아닙니다.');
}

// 기본 회원가입 처리 로직 (register_form_update.php에서 복사)
$mb_id = isset($_POST['mb_id']) ? trim($_POST['mb_id']) : '';
$mb_password = isset($_POST['mb_password']) ? trim($_POST['mb_password']) : '';
$mb_name = isset($_POST['mb_name']) ? trim($_POST['mb_name']) : '';
$mb_nick = isset($_POST['mb_nick']) ? trim($_POST['mb_nick']) : '';
$mb_email = isset($_POST['mb_email']) ? trim($_POST['mb_email']) : '';
$mb_hp = isset($_POST['mb_hp']) ? trim($_POST['mb_hp']) : '';

// 추천인 코드 처리
$mb_referral_code = isset($_POST['mb_referral_code']) ? trim($_POST['mb_referral_code']) : '';
$mb_referred_by = isset($_POST['mb_referred_by']) ? trim($_POST['mb_referred_by']) : '';

// 기본 유효성 검사
if (!$mb_id) alert('회원아이디가 넘어오지 않았습니다.');
if (!$mb_password) alert('비밀번호가 넘어오지 않았습니다.');
if (!$mb_name) alert('이름이 넘어오지 않았습니다.');
if (!$mb_nick) alert('닉네임이 넘어오지 않았습니다.');
if (!$mb_email) alert('이메일이 넘어오지 않았습니다.');

// 추천인 코드 검증 (있는 경우)
if ($mb_referral_code && !$mb_referred_by) {
    // 코드는 있지만 referred_by가 없는 경우 (직접 코드 입력한 경우)
    include_once(G5_LIB_PATH.'/referral_new.lib.php');
    
    $referrer = get_member_by_referral_code($mb_referral_code);
    if ($referrer) {
        $mb_referred_by = $referrer['mb_id'];
    } else {
        alert('유효하지 않은 추천인 코드입니다.');
    }
}

// 테스트용 데이터베이스 연결
$mysql_host = '121.78.91.42';
$mysql_user = 'iuser07495';
$mysql_password = 'printer!@12';
$mysql_db = 'idb07495';

$test_conn = mysql_connect($mysql_host, $mysql_user, $mysql_password);
if (!$test_conn) {
    alert('테스트 데이터베이스 연결 실패: ' . mysql_error());
}

mysql_select_db($mysql_db, $test_conn);
mysql_query("SET NAMES utf8", $test_conn);

// 회원가입 처리 (테스트용)
$sql = " INSERT INTO g5_member
            SET mb_id = '$mb_id',
                mb_password = PASSWORD('$mb_password'),
                mb_name = '$mb_name',
                mb_nick = '$mb_nick',
                mb_email = '$mb_email',
                mb_hp = '$mb_hp',
                mb_level = '2',
                mb_datetime = NOW(),
                mb_ip = '{$_SERVER['REMOTE_ADDR']}',
                mb_email_certify = NOW(),
                mb_referral_code = '" . generate_referral_code() . "',
                mb_referred_by = '$mb_referred_by' ";

$result = mysql_query($sql, $test_conn);

if ($result) {
    // 추천인이 있는 경우 추천 수 증가
    if ($mb_referred_by) {
        $update_sql = "UPDATE g5_member SET mb_referral_count = mb_referral_count + 1 WHERE mb_id = '$mb_referred_by'";
        mysql_query($update_sql, $test_conn);
        
        // 추천 기록 저장
        $insert_sql = "INSERT INTO g5_member_referral (mb_id, referral_mb_id, referral_datetime) VALUES ('$mb_referred_by', '$mb_id', NOW())";
        mysql_query($insert_sql, $test_conn);
    }
    
    mysql_close($test_conn);
    
    // 테스트 완료 메시지
    alert("테스트 회원가입이 완료되었습니다.\\n\\n아이디: $mb_id\\n이름: $mb_name\\n추천인: " . ($mb_referred_by ? $mb_referred_by : '없음'), './test_register_result.php?mb_id=' . $mb_id);
} else {
    mysql_close($test_conn);
    alert('테스트 회원가입 중 오류가 발생했습니다: ' . mysql_error());
}

function generate_referral_code() {
    return substr(strtoupper(md5(uniqid(rand(), true))), 0, 8);
}
?>