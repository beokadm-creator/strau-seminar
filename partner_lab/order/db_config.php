<?php
// 파트너랩 주문 시스템 데이터베이스 설정 파일

// partner_admin의 config.php를 포함하여 데이터베이스 연결 함수 사용
$admin_config_path = dirname(__FILE__) . '/../partner_admin/config.php';
if (file_exists($admin_config_path)) {
    include_once($admin_config_path);
} else {
    die('관리자 설정 파일을 찾을 수 없습니다: ' . $admin_config_path);
}

// 데이터베이스 연결 함수 (order 디렉토리용)
function getDBConnection() {
    // partner_admin의 get_partner_lab_db_connection() 함수가 있다면 사용
    if (function_exists('get_partner_lab_db_connection')) {
        return get_partner_lab_db_connection();
    }
    
    // 직접 연결 (폴백)
    $host = '121.78.91.42';
    $user = 'iuser07495';
    $pass = 'printer!@12';
    $name = 'idb07495';
    $charset = 'utf8mb4';

    $db = @mysqli_connect($host, $user, $pass, $name);
    if (!$db) {
        $err = function_exists('mysqli_connect_error') ? mysqli_connect_error() : 'unknown error';
        die("데이터베이스 연결 실패: " . $err);
    }

    // 문자셋 설정
    if (function_exists('mysqli_set_charset')) {
        @mysqli_set_charset($db, $charset);
    } else {
        @mysqli_query($db, "SET NAMES " . $charset);
    }

    return $db;
}

// 기본 설정
if (!function_exists('session_status')) {
    if (!session_id()) {
        session_start();
    }
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
?>