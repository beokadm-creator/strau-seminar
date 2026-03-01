<?php
// 파트너랩 주문 삭제 처리 (PHP 5.2 / MySQLi 호환)

// 즉각적인 오류 표시 (개발 중에만 사용)
@ini_set('display_errors', 1);
@ini_set('display_startup_errors', 1);
@error_reporting(E_ALL);

// JSON 응답 헤더 강제 설정
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// 의도치 않은 출력 방지를 위해 버퍼링 시작
ob_start();

// 관리자 공통 설정/권한/DB 유틸 포함 (파트너랩 관리자 시스템 기준)
require_once dirname(__FILE__) . '/config.php';
// 루트 공통 파일 포함 (index.php와 동일 경로 기준)
include_once dirname(__FILE__) . '/../../common.php';
include_once dirname(__FILE__) . '/../../config.php';

// 전역 에러 수집기
$php_errors = array();
$mysql_errors = array();

// PHP 에러 핸들러 (PHP 5.2 호환)
function partner_admin_error_handler($errno, $errstr, $errfile, $errline) {
    global $php_errors;
    $php_errors[] = array(
        'type' => 'PHP',
        'errno' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    );
    // 기본 핸들러에도 전달
    return false;
}
set_error_handler('partner_admin_error_handler');

// 응답 헬퍼
function respond_json($success, $message, $extra = array()) {
    global $php_errors, $mysql_errors, $orders_pk_col, $soft_delete, $order_id;
    $payload = array('success' => $success ? true : false, 'message' => (string)$message);
    if (is_array($extra)) {
        foreach ($extra as $k => $v) { $payload[$k] = $v; }
    }
    // 실패 시 디버깅 정보 포함
    if (!$success) {
        $payload['error_debug'] = array(
            'php_errors' => $php_errors,
            'mysql_errors' => $mysql_errors,
            'orders_pk_col' => isset($orders_pk_col) ? $orders_pk_col : 'order_id',
            'soft_delete' => isset($soft_delete) ? (bool)$soft_delete : false,
            'input' => array('order_id' => isset($order_id) ? $order_id : ''),
            'output_buffer' => (function_exists('ob_get_contents') ? substr(ob_get_contents(), 0, 500) : '')
        );
    }
    // 버퍼 정리 후 출력
    if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
    echo json_encode($payload);
    exit;
}

// 로그인 및 관리자 권한 체크 (index.php 로직과 동일하게)
{
    $req_uri   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : (defined('PARTNER_ADMIN_URL') ? PARTNER_ADMIN_URL.'/delete_order.php' : './delete_order.php');
    $login_url = defined('G5_BBS_URL') ? (G5_BBS_URL.'/login.php') : '/bbs/login.php';
    $main_url  = defined('G5_URL') ? G5_URL : '/';

    // 로그인 여부 판단
    $is_logged_in = false;
    if (isset($is_member)) {
        $is_logged_in = $is_member ? true : false;
    } else if (isset($is_guest)) {
        $is_logged_in = $is_guest ? false : true;
    } else if (isset($_SESSION['ss_mb_id']) && $_SESSION['ss_mb_id']) {
        $is_logged_in = true;
    }

    if (!$is_logged_in) {
        respond_json(false, '로그인 후 이용 가능합니다.', array('redirect' => $login_url.'?url='.urlencode($req_uri)));
    }

    // 관리자 여부 판단
    $admin_ok = false;
    if (isset($is_admin) && $is_admin === 'super') { $admin_ok = true; }
    if (!$admin_ok && isset($member) && is_array($member) && isset($member['mb_level'])) {
        $admin_ok = ((int)$member['mb_level'] >= 10);
    }
    if (!$admin_ok && isset($_SESSION['ss_mb_level'])) {
        $admin_ok = ((int)$_SESSION['ss_mb_level'] >= 10);
    }

    if (!$admin_ok) {
        $is_admin_val = isset($is_admin) ? $is_admin : '';
        $mb_level_val = (isset($member) && is_array($member) && isset($member['mb_level'])) ? (int)$member['mb_level'] : 0;
        respond_json(false, '관리자 권한이 필요합니다.', array('is_admin' => $is_admin_val, 'mb_level' => $mb_level_val, 'redirect' => $main_url));
    }
}

// 입력 파싱 (FormData 또는 JSON)
$input = array();
if (!empty($_POST)) {
    $input = $_POST;
} else {
    $raw = @file_get_contents('php://input');
    if (!empty($raw)) {
        $dec = @json_decode($raw, true);
        if (is_array($dec)) { $input = $dec; }
    }
}

$order_id = isset($input['order_id']) ? trim($input['order_id']) : '';
$csrf_token = isset($input['csrf_token']) ? trim($input['csrf_token']) : '';
$soft_delete = isset($input['soft_delete']) ? (bool)$input['soft_delete'] : false;

// 필수값 확인
if ($order_id === '') {
    respond_json(false, '필수 파라미터가 누락되었습니다: order_id');
}

// CSRF 토큰 검증
if (!verify_csrf_token($csrf_token)) {
    respond_json(false, '보안 토큰이 유효하지 않습니다.');
}

// DB 연결
$db = get_partner_lab_db_connection();
if (!$db) {
    respond_json(false, '데이터베이스 연결 실패');
}

// 안전 실행용 쿼리 래퍼 (오류 수집)
function pa_run_query($db, $sql, $stage) {
    global $mysql_errors;
    $res = @mysqli_query($db, $sql);
    if ($res === false) {
        $mysql_errors[] = array('stage' => $stage, 'sql' => $sql, 'error' => mysqli_error($db));
    }
    return $res;
}

// 실제 PK 컬럼 확인 (기본값: order_id)
$orders_pk_col = 'order_id';
$desc_orders_res = pa_run_query($db, 'DESCRIBE partner_lab_orders', 'describe_orders');
if ($desc_orders_res) {
    while ($r = mysqli_fetch_assoc($desc_orders_res)) {
        if (isset($r['Key']) && $r['Key'] === 'PRI') { $orders_pk_col = $r['Field']; break; }
    }
}

$order_id_esc = mysqli_real_escape_string($db, $order_id);

// 주문 존재 확인 및 주문번호 조회(감사 로그용)
$order_number = '';
$check_sql = "SELECT `".$orders_pk_col."` AS pk, IFNULL(order_number, '') AS order_number FROM partner_lab_orders WHERE `".$orders_pk_col."`='".$order_id_esc."' LIMIT 1";
$check_res = pa_run_query($db, $check_sql, 'check_order');
if (!$check_res) {
    respond_json(false, '주문 조회 중 오류가 발생했습니다.', array('stage' => 'check_order'));
}
$order_row = mysqli_fetch_assoc($check_res);
if (!$order_row) {
    respond_json(false, '해당 주문을 찾을 수 없습니다. 주문번호: ' . $order_id);
}
$order_number = isset($order_row['order_number']) ? $order_row['order_number'] : '';

// 트랜잭션 시작
$tx_begin = @mysqli_query($db, 'START TRANSACTION');
if ($tx_begin === false) {
    $mysql_errors[] = array('stage' => 'transaction_begin', 'sql' => 'START TRANSACTION', 'error' => mysqli_error($db));
    respond_json(false, '트랜잭션 시작 중 오류가 발생했습니다.', array('stage' => 'transaction_begin'));
}

// 소프트 삭제 지원: 스키마에 is_deleted 또는 deleted_at이 있을 경우만 적용
if ($soft_delete) {
    $has_is_deleted = false; $has_deleted_at = false;
    $desc = pa_run_query($db, 'DESCRIBE partner_lab_orders', 'soft_delete_describe');
    if ($desc) {
        while ($r = mysqli_fetch_assoc($desc)) {
            if ($r['Field'] === 'is_deleted') { $has_is_deleted = true; }
            if ($r['Field'] === 'deleted_at') { $has_deleted_at = true; }
        }
    }
    if ($has_is_deleted || $has_deleted_at) {
        $upd = 'UPDATE partner_lab_orders SET ';
        $parts = array();
        if ($has_is_deleted) { $parts[] = "is_deleted = 1"; }
        if ($has_deleted_at) { $parts[] = "deleted_at = NOW()"; }
        $upd .= implode(', ', $parts) . " WHERE `".$orders_pk_col."`='".$order_id_esc."'";
        $ok = pa_run_query($db, $upd, 'soft_delete_update');
        if (!$ok) {
            $rb = @mysqli_query($db, 'ROLLBACK');
            if ($rb === false) {
                $mysql_errors[] = array('stage' => 'transaction_rollback', 'sql' => 'ROLLBACK', 'error' => mysqli_error($db));
            }
            respond_json(false, '소프트 삭제 중 오류가 발생했습니다.', array('stage' => 'soft_delete_update'));
        }
        // 감사 로그만 남기고 커밋
        $cm = @mysqli_query($db, 'COMMIT');
        if ($cm === false) {
            $mysql_errors[] = array('stage' => 'transaction_commit', 'sql' => 'COMMIT', 'error' => mysqli_error($db));
            respond_json(false, '트랜잭션 커밋 중 오류가 발생했습니다.', array('stage' => 'transaction_commit'));
        }
        // 파일 감사 로그
        $actor = isset($_SESSION['ss_mb_id']) ? $_SESSION['ss_mb_id'] : 'admin';
        $log_line = date('Y-m-d H:i:s') . "\tsoft_delete\torder_id=" . $order_id . "\torder_number=" . $order_number . "\tactor=" . $actor . "\n";
        $log_path = dirname(__FILE__) . '/delete_order_audit.log';
        $w = @file_put_contents($log_path, $log_line, FILE_APPEND);
        if ($w === false) {
            $php_errors[] = array('type' => 'PHP', 'errno' => 0, 'message' => '감사 로그 기록 실패', 'file' => $log_path, 'line' => 0);
        }
        respond_json(true, '주문이 소프트 삭제되었습니다.', array('order_id' => $order_id));
    }
    // 스키마에 컬럼이 없으면 하드 삭제로 진행
}

// 하드 삭제: 자식 테이블 먼저 삭제
$errors = array();

// 안전한 삭제를 위해 순서를 준수
$tables = array(
    'partner_lab_order_teeth_details',
    'partner_lab_order_teeth',
    'partner_lab_order_files',
    'partner_lab_order_logs'
);

foreach ($tables as $t) {
    // 각 테이블에 order_id 컬럼이 있다고 가정. 없을 경우 오류가 날 수 있으므로 에러 수집
    $sql = "DELETE FROM `".$t."` WHERE order_id='".$order_id_esc."'";
    $ok = pa_run_query($db, $sql, 'delete_' . $t);
    if (!$ok) {
        $errors[] = $t . ': ' . mysqli_error($db);
    }
}

// 최종적으로 주문 테이블 삭제
$del_order_sql = "DELETE FROM partner_lab_orders WHERE `".$orders_pk_col."`='".$order_id_esc."' LIMIT 1";
$del_ok = pa_run_query($db, $del_order_sql, 'delete_orders');
if (!$del_ok) {
    $errors[] = 'partner_lab_orders: ' . mysqli_error($db);
}

if (!empty($errors)) {
    $rb = @mysqli_query($db, 'ROLLBACK');
    if ($rb === false) {
        $mysql_errors[] = array('stage' => 'transaction_rollback', 'sql' => 'ROLLBACK', 'error' => mysqli_error($db));
    }
    respond_json(false, '삭제 중 오류가 발생했습니다.', array('errors' => $errors, 'stage' => 'delete_children_or_order'));
}

// 커밋
$cm = @mysqli_query($db, 'COMMIT');
if ($cm === false) {
    $mysql_errors[] = array('stage' => 'transaction_commit', 'sql' => 'COMMIT', 'error' => mysqli_error($db));
    respond_json(false, '트랜잭션 커밋 중 오류가 발생했습니다.', array('stage' => 'transaction_commit'));
}

// 파일 감사 로그 기록 (테이블 로그는 함께 삭제되므로 별도 파일에 남김)
$actor = isset($_SESSION['ss_mb_id']) ? $_SESSION['ss_mb_id'] : 'admin';
$log_line = date('Y-m-d H:i:s') . "\tdelete\torder_id=" . $order_id . "\torder_number=" . $order_number . "\tactor=" . $actor . "\n";
$log_path = dirname(__FILE__) . '/delete_order_audit.log';
$w = @file_put_contents($log_path, $log_line, FILE_APPEND);
if ($w === false) {
    $php_errors[] = array('type' => 'PHP', 'errno' => 0, 'message' => '감사 로그 기록 실패', 'file' => $log_path, 'line' => 0);
}

respond_json(true, '주문이 삭제되었습니다.', array('order_id' => $order_id));
?>