<?php
// 파트너랩 전용 관리자 설정 파일

// 데이터베이스 연결 정보 (라이브 DB)
define('PARTNER_LAB_DB_HOST', '121.78.91.42');
define('PARTNER_LAB_DB_NAME', 'idb07495');
define('PARTNER_LAB_DB_USER', 'iuser07495');
define('PARTNER_LAB_DB_PASS', 'printer!@12');
define('PARTNER_LAB_DB_CHARSET', 'utf8mb4');

// 세션 시작 (PHP 5.2 호환)
if (!function_exists('session_status')) {
    if (!session_id()) {
        session_start();
    }
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// 데이터베이스 연결 함수 (MySQLi, PHP 5.2 호환)
function get_partner_lab_db_connection() {
    $host = PARTNER_LAB_DB_HOST;
    $user = PARTNER_LAB_DB_USER;
    $pass = PARTNER_LAB_DB_PASS;
    $name = PARTNER_LAB_DB_NAME;
    $charset = PARTNER_LAB_DB_CHARSET;

    $db = @mysqli_connect($host, $user, $pass, $name);
    if (!$db) {
        // PHP 5.2 환경에서 mysqli_connect_error 사용
        $err = function_exists('mysqli_connect_error') ? mysqli_connect_error() : 'unknown error';
        die("데이터베이스 연결 실패: " . $err);
    }

    // 문자셋 설정 시도 (utf8mb4 지원이 없을 수 있으므로 폴백 처리)
    $charset_set = true;
    if (function_exists('mysqli_set_charset')) {
        if (!@mysqli_set_charset($db, $charset)) {
            $charset_set = false;
        }
    } else {
        if (!@mysqli_query($db, "SET NAMES " . $charset)) {
            $charset_set = false;
        }
    }

    if (!$charset_set) {
        // utf8mb4 실패 시 utf8로 폴백
        @mysqli_set_charset($db, 'utf8');
        @mysqli_query($db, "SET NAMES utf8");
    }

    return $db;
}

// 관리자 권한 체크 함수
function check_partner_admin_permission() {
    // 로그인 여부
    if (!isset($_SESSION['ss_mb_id']) || empty($_SESSION['ss_mb_id'])) {
        return false;
    }

    // 관리자 판별 (PHP 5.2 호환)
    // - 최고관리자($is_admin === 'super') 허용
    // - 관리자 레벨 10 허용
    // - 폴백: 세션 레벨 10 이상 허용
    $admin_ok = false;

    // 전역 변수 접근 (G5 환경)
    if (isset($GLOBALS['is_admin']) && $GLOBALS['is_admin'] === 'super') {
        $admin_ok = true;
    }

    if (!$admin_ok && isset($GLOBALS['member']) && is_array($GLOBALS['member']) && isset($GLOBALS['member']['mb_level'])) {
        $admin_ok = ((int)$GLOBALS['member']['mb_level'] >= 10);
    }

    if (!$admin_ok && isset($_SESSION['ss_mb_level'])) {
        $admin_ok = ((int)$_SESSION['ss_mb_level'] >= 10);
    }

    return $admin_ok;
}

// 파트너랩 주문 상태 정의
function get_order_status_list() {
    return array(
        'pending' => '주문',
        'confirmed' => '주문접수',
        'processing' => '파트너 확인',
        'done' => '완료'
    );
}

// 주문 상태 한글명 반환
function get_order_status_name($status) {
    $status_list = get_order_status_list();
    return isset($status_list[$status]) ? $status_list[$status] : '알수없음';
}

// 주문 상태 색상 반환
function get_order_status_color($status) {
    $colors = array(
        'pending' => '#ffc107',     // 노랑
        'confirmed' => '#17a2b8',   // 청록
        'processing' => '#007bff',  // 파랑
        'done' => '#28a745'         // 초록
    );
    return isset($colors[$status]) ? $colors[$status] : '#6c757d';
}



// 기본 경로 설정
define('PARTNER_ADMIN_URL', '/partner_lab/partner_admin');
define('PARTNER_ORDER_URL', '/partner_lab/order');

// CSRF 토큰 생성 함수
// 안전한 랜덤 바이트 생성 (PHP 5.2 호환)
function partner_random_bytes($length) {
    $length = (int)$length;
    $output = '';
    // 우선 OpenSSL 사용 (사용 가능 시)
    if (function_exists('openssl_random_pseudo_bytes')) {
        $strong = false;
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if ($bytes !== false) {
            return $bytes;
        }
    }
    // 폴백: mt_rand 기반 바이트 생성 (암호학적으로 안전하지 않음)
    for ($i = 0; $i < $length; $i++) {
        $output .= chr(mt_rand(0, 255));
    }
    return $output;
}

function generate_csrf_token() {
    if (!isset($_SESSION['partner_csrf_token'])) {
        $_SESSION['partner_csrf_token'] = bin2hex(partner_random_bytes(32));
    }
    return $_SESSION['partner_csrf_token'];
}

// CSRF 토큰 검증 함수
function verify_csrf_token($token) {
    // hash_equals 대체 (PHP 5.2 호환) - 일반 비교 사용
    return isset($_SESSION['partner_csrf_token']) && $_SESSION['partner_csrf_token'] === $token;
}

// 페이지네이션 함수
function get_pagination($total_rows, $rows_per_page, $current_page, $base_url) {
    $total_pages = ceil($total_rows / $rows_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    
    $html = '<nav aria-label="페이지네이션">';
    $html .= '<ul class="pagination">';
    
    // 이전 페이지
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page - 1) . '">이전</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">이전</span></li>';
    }
    
    // 페이지 번호
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    // 다음 페이지
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page + 1) . '">다음</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">다음</span></li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

// 기본 헤더 정보
function get_partner_admin_header_info() {
    return array(
        'title' => '파트너랩 관리자',
        'version' => '1.0.0',
        'author' => '파트너랩',
        'description' => '파트너랩 주문 관리 시스템'
    );
}

?>
