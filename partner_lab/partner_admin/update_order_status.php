<?php
// 파트너랩 주문 상태 업데이트 처리 (MySQLi, PHP 5.2 호환)

// 즉각적인 오류 출력
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 에러 로깅 설정
// Production: Disable error logging to file
// ini_set('log_errors', 1);
// ini_set('error_log', 'update_order_status_errors.log');

// JSON 응답 헤더 (강제 설정)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// 출력 버퍼링 시작 (의도치 않은 출력 방지)
ob_start();

// 전역 에러 수집 및 안전한 JSON 응답 헬퍼
$php_errors = array();
$mysql_errors = array();

function partner_admin_error_handler($errno, $errstr, $errfile, $errline) {
    global $php_errors;
    $php_errors[] = array('type' => 'PHP', 'errno' => $errno, 'message' => $errstr, 'file' => $errfile, 'line' => $errline);
    return false; // 기본 핸들러에도 전달
}
set_error_handler('partner_admin_error_handler');

function respond_json_payload($payload) {
    if (function_exists('ob_get_level')) {
        while (ob_get_level() > 0) { @ob_end_clean(); }
    }
    echo json_encode($payload);
    exit;
}

function respond_json_error($message, $extra = array()) {
    global $php_errors, $mysql_errors;
    $payload = array('success' => false, 'message' => (string)$message);
    if (is_array($extra)) { foreach ($extra as $k => $v) { $payload[$k] = $v; } }
    $payload['error_debug'] = array(
        'php_errors' => $php_errors,
        'mysql_errors' => $mysql_errors
    );
    respond_json_payload($payload);
}
// 파트너랩 관리자 설정 및 루트 공통 포함 (PHP 5.2 호환)
require_once dirname(__FILE__) . '/config.php';
include_once dirname(__FILE__) . '/../../common.php';
include_once dirname(__FILE__) . '/../../config.php';

// 파트너랩 config.php의 DB 연결 사용
$db = get_partner_lab_db_connection();

// 관리자 권한 체크 함수 정의
if (!function_exists('check_partner_admin_permission')) {
    function check_partner_admin_permission() {
        // 실제 권한 체크 로직
        // 세션에서 파트너 관리자 권한 확인
        session_start();
        return isset($_SESSION['ss_partner_admin']) && $_SESSION['ss_partner_admin'] === true;
    }
}

// CSRF 토큰 검증 함수 정의
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        // 실제 CSRF 토큰 검증 로직
        session_start();
        return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
    }
}

// 주문 상태 이름 반환 함수 정의
if (!function_exists('get_order_status_name')) {
    function get_order_status_name($status) {
        $status_names = array(
            'pending' => '주문',
            'confirmed' => '주문접수',
            'processing' => '파트너 확인',
            'done' => '완료'
        );
        return isset($status_names[$status]) ? $status_names[$status] : $status;
    }
}

// 로그인 및 관리자 권한 체크 (delete_order.php와 동일 기준)
{
    $req_uri   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : (defined('PARTNER_ADMIN_URL') ? PARTNER_ADMIN_URL.'/update_order_status.php' : './update_order_status.php');
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
        respond_json_error('로그인 후 이용 가능합니다.', array('redirect' => $login_url.'?url='.urlencode($req_uri)));
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
        respond_json_error('관리자 권한이 필요합니다.', array('is_admin' => $is_admin_val, 'mb_level' => $mb_level_val, 'redirect' => $main_url));
    }
}

// POST 데이터 받기 (FormData와 JSON 모두 지원)
$input_data = array();

// FormData로 전송된 경우
if (!empty($_POST)) {
    $input_data = $_POST;
} else {
    // JSON으로 전송된 경우
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $json_data = @json_decode($raw_input, true);
        if ($json_data !== null) {
            $input_data = $json_data;
        }
    }
}

$order_id = isset($input_data['order_id']) ? trim($input_data['order_id']) : '';
$order_ids = isset($input_data['order_ids']) ? $input_data['order_ids'] : array(); // 대량 업데이트용
$new_status = isset($input_data['status']) ? trim($input_data['status']) : '';
$status_note = isset($input_data['note']) ? trim($input_data['note']) : ''; // 상태 변경 사유
$csrf_token = isset($input_data['csrf_token']) ? trim($input_data['csrf_token']) : '';

// $status_note가 정의되지 않은 경우 기본값 설정
if (!isset($status_note)) {
    $status_note = '';
}

// 유효성 검사
if ((empty($order_id) && empty($order_ids)) || empty($new_status)) {
    respond_json_error('필수 파라미터가 누락되었습니다.');
}

// 대량 업데이트 처리
if (!empty($order_ids) && is_array($order_ids)) {
    $success_count = 0;
    $error_messages = array();
    
    foreach ($order_ids as $single_order_id) {
        $single_order_id = trim($single_order_id);
        if (empty($single_order_id)) continue;
        
        // 각 주문에 대해 개별 처리
        $result = updateSingleOrderStatus($single_order_id, $new_status, $csrf_token, $db);
        if ($result['success']) {
            $success_count++;
        } else {
            $error_messages[] = "주문 $single_order_id: " . $result['message'];
        }
    }
    
    $response = array(
        'success' => ($success_count > 0),
        'message' => "총 " . count($order_ids) . "개 주문 중 " . $success_count . "개 성공",
        'success_count' => $success_count,
        'total_count' => count($order_ids),
        'errors' => $error_messages
    );
    
    respond_json_payload($response);
}

// CSRF 토큰 검증
if (!verify_csrf_token($csrf_token)) {
    respond_json_error('보안 토큰이 유효하지 않습니다.');
}

// 상태값 유효성 검사 (PHP 5.2 호환)
$valid_statuses = array('pending', 'confirmed', 'processing', 'done');
if (!in_array($new_status, $valid_statuses)) {
    respond_json_error('유효하지 않은 상태값입니다.');
}

// 데이터베이스 연결 (MySQLi) - 오류 처리 강화
try {
    $db = get_partner_lab_db_connection();
    if (!$db) {
        throw new Exception('데이터베이스 연결 실패');
    }
} catch (Exception $e) {
    respond_json_error('데이터베이스 연결 오류: ' . $e->getMessage());
}

// 단일 주문 상태 업데이트 함수
function updateSingleOrderStatus($order_id, $new_status, $csrf_token, $db) {
    global $member;
    
    // CSRF 토큰 검증
    if (!verify_csrf_token($csrf_token)) {
        return array('success' => false, 'message' => '보안 토큰이 유효하지 않습니다.');
    }
    
    // 상태값 유효성 검사 (PHP 5.2 호환)
    $valid_statuses = array('pending', 'confirmed', 'processing', 'done');
    if (!in_array($new_status, $valid_statuses)) {
        return array('success' => false, 'message' => '유효하지 않은 상태값입니다.');
    }
    
    // 트랜잭션 시작 (수동)
    @mysqli_query($db, "START TRANSACTION");
    
    // 실제 PK 컬럼 확인 후 사용
    $orders_pk_col = 'order_id';
    $desc_orders_res = @mysqli_query($db, "DESCRIBE partner_lab_orders");
    if ($desc_orders_res) {
        while ($r = mysqli_fetch_assoc($desc_orders_res)) {
            if (isset($r['Key']) && $r['Key'] === 'PRI') { $orders_pk_col = $r['Field']; break; }
        }
    }
    
    $order_id_esc = mysqli_real_escape_string($db, $order_id);
    $new_status_esc = mysqli_real_escape_string($db, $new_status);
    $updated_by = isset($member['mb_id']) ? $member['mb_id'] : 'admin';
    $updated_by_esc = mysqli_real_escape_string($db, $updated_by);
    
    // 현재 주문 상태 조회 (락)
    $check_sql = "SELECT order_status, customer_name, patient_name FROM partner_lab_orders WHERE `".$orders_pk_col."` = '" . $order_id_esc . "' LIMIT 1";
    $check_res = mysqli_query($db, $check_sql);
    
    if (!$check_res) {
        $error = mysqli_error($db);
        @mysqli_query($db, "ROLLBACK");
        return array('success' => false, 'message' => '주문 조회 중 오류가 발생했습니다. 오류: ' . $error);
    }
    
    $current_order = mysqli_fetch_assoc($check_res);
    
    if (!$current_order) {
        @mysqli_query($db, "ROLLBACK");
        return array('success' => false, 'message' => '해당 주문을 찾을 수 없습니다. 주문번호: ' . $order_id);
    }
    
    $current_status = $current_order['order_status'];

// 주문 상태 업데이트 - 사용 가능한 컬럼 확인
    $update_sql = "UPDATE partner_lab_orders SET 
                   order_status = '" . $new_status_esc . "',
                   updated_at = NOW()";
    
    // updated_by 컬럼이 있는지 확인하고 사용
    $desc_res = @mysqli_query($db, "DESCRIBE partner_lab_orders");
    $has_updated_by = false;
    $has_status_updated_by = false;
    
    if ($desc_res) {
        while ($r = mysqli_fetch_assoc($desc_res)) {
            if ($r['Field'] === 'updated_by') {
                $has_updated_by = true;
            }
            if ($r['Field'] === 'status_updated_by') {
                $has_status_updated_by = true;
            }
        }
        mysqli_free_result($desc_res);
    }
    
    // 사용 가능한 컬럼에 따라 업데이트
    if ($has_status_updated_by) {
        $update_sql .= ", status_updated_by = '" . $updated_by_esc . "'";
    } elseif ($has_updated_by) {
        $update_sql .= ", updated_by = '" . $updated_by_esc . "'";
    }
    
    $update_sql .= " WHERE `".$orders_pk_col."` = '" . $order_id_esc . "'";
    
    if (!mysqli_query($db, $update_sql)) {
        $error = mysqli_error($db);
        @mysqli_query($db, "ROLLBACK");
        return array('success' => false, 'message' => '상태 업데이트 중 오류가 발생했습니다. 오류: ' . $error . ', SQL: ' . $update_sql);
    }
    
    // 상태 변경 이력 기록 (테이블이 없으면 실패할 수 있음)
    $status_note_esc = mysqli_real_escape_string($db, $status_note);
    
    // order_status_history 테이블의 컬럼 구조 확인
    $desc_history_res = @mysqli_query($db, "DESCRIBE order_status_history");
    $history_columns = array();
    if ($desc_history_res) {
        while ($r = mysqli_fetch_assoc($desc_history_res)) {
            $history_columns[] = $r['Field'];
        }
        mysqli_free_result($desc_history_res);
    }
    
    // 사용 가능한 컬럼에 따라 INSERT 쿼리 구성
    if (!empty($history_columns)) {
        $insert_columns = array();
        $insert_values = array();
        
        // 기본 필수 컬럼
        if (in_array('order_id', $history_columns)) {
            $insert_columns[] = 'order_id';
            $insert_values[] = "'" . $order_id_esc . "'";
        }
        if (in_array('previous_status', $history_columns)) {
            $insert_columns[] = 'previous_status';
            $insert_values[] = "'" . mysqli_real_escape_string($db, $current_status) . "'";
        }
        if (in_array('new_status', $history_columns)) {
            $insert_columns[] = 'new_status';
            $insert_values[] = "'" . $new_status_esc . "'";
        }
        if (in_array('changed_by', $history_columns)) {
            $insert_columns[] = 'changed_by';
            $insert_values[] = "'" . $updated_by_esc . "'";
        }
        if (in_array('changed_at', $history_columns)) {
            $insert_columns[] = 'changed_at';
            $insert_values[] = 'NOW()';
        }
        
        // 선택적 컬럼
        if (in_array('note', $history_columns) && !empty($status_note)) {
            $insert_columns[] = 'note';
            $insert_values[] = "'" . $status_note_esc . "'";
        }
        
        if (!empty($insert_columns)) {
            $history_sql = "INSERT INTO order_status_history (" . implode(', ', $insert_columns) . ") 
                           VALUES (" . implode(', ', $insert_values) . ")";
            @mysqli_query($db, $history_sql);
        }
    }
    
    // 커밋
    @mysqli_query($db, "COMMIT");
    
    // Production: Remove detailed logging
    // $log_message = sprintf(
    //     "파트너랩 주문 상태 변경: 주문번호=%s, 고객=%s, 환자=%s, 이전상태=%s, 새상태=%s, 변경자=%s",
    //     $order_id,
    //     $current_order['customer_name'],
    //     $current_order['patient_name'],
    //     $current_order['order_status'],
    //     $new_status,
    //     $updated_by
    // );
    
    // 로그 기록 제거 (프로덕션용)
    // if (function_exists('write_log')) {
    //     write_log($log_message);
    // }
    
    return array(
        'success' => true,
        'message' => '주문 상태가 성공적으로 변경되었습니다.',
        'data' => array(
            'order_id' => $order_id,
            'previous_status' => $current_status,
            'new_status' => $new_status,
            'status_name' => get_order_status_name($new_status)
        )
    );
}

// 단일 주문 업데이트 처리
$result = updateSingleOrderStatus($order_id, $new_status, $csrf_token, $db);

// 디버그 정보 제거 - 에러 로그 기록하지 않음

// 출력 버퍼 정리 후 JSON 응답
respond_json_payload($result);
?>
