<?php
// 이메일 확인 버튼/픽셀 응답: 주문 상태를 'processing'(파트너 확인)으로 변경
include_once dirname(__FILE__) . '/config.php';
include_once dirname(__FILE__) . '/../../common.php';
include_once dirname(__FILE__) . '/../../config.php';

$db = get_partner_lab_db_connection();
if (!$db) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'DB 연결 실패';
    exit;
}

$order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$mode = isset($_GET['mode']) ? strtolower(trim($_GET['mode'])) : 'click';
if ($order_id === '' || $token === '') {
    header('HTTP/1.1 400 Bad Request');
    echo '잘못된 요청';
    exit;
}

$order_id_esc = mysqli_real_escape_string($db, $order_id);
$token_esc = mysqli_real_escape_string($db, $token);

// 토큰 검증: 로그 테이블에서 발급된 토큰 확인
$chk_sql = "SELECT 1 FROM partner_lab_order_logs WHERE order_id = '".$order_id_esc."' AND action = 'email_confirm_token' AND description = '".$token_esc."' LIMIT 1";
$chk_res = mysqli_query($db, $chk_sql);
if (!$chk_res || !mysqli_fetch_row($chk_res)) {
    // 토큰 없음 → 픽셀 모드에서는 1x1 픽셀 반환, 클릭 모드에서는 안내
    if ($mode === 'open') {
        header('Content-Type: image/gif');
        echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xFF\xFF\xFF\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B";
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo '유효하지 않은 요청입니다.';
    exit;
}

// 실제 PK 컬럼 확인
$orders_pk_col = 'order_id';
$desc = @mysqli_query($db, "DESCRIBE partner_lab_orders");
if ($desc) {
    while ($r = mysqli_fetch_assoc($desc)) { if (isset($r['Key']) && $r['Key'] === 'PRI') { $orders_pk_col = $r['Field']; break; } }
    mysqli_free_result($desc);
}

// 현재 상태 조회
$cur_sql = "SELECT order_status FROM partner_lab_orders WHERE `".$orders_pk_col."` = '".$order_id_esc."' LIMIT 1";
$cur_res = mysqli_query($db, $cur_sql);
$cur_row = $cur_res ? mysqli_fetch_assoc($cur_res) : null;
$cur_status = $cur_row ? $cur_row['order_status'] : '';

// 상태 업데이트: 'processing'으로 설정 (이미 처리된 경우는 유지)
if ($cur_row && $cur_status !== 'processing') {
    $upd_sql = "UPDATE partner_lab_orders SET order_status = 'processing', updated_at = NOW() WHERE `".$orders_pk_col."` = '".$order_id_esc."'";
    @mysqli_query($db, $upd_sql);
    // 로그 기록
    $src = ($mode === 'open') ? 'email_open' : 'email_click';
    $log_sql = "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES ('".$order_id_esc."', 'partner_confirmed', '".mysqli_real_escape_string($db, $src)."', NOW())";
    @mysqli_query($db, $log_sql);
}

// 응답: 모드별 처리
if ($mode === 'open') {
    // 1x1 투명 GIF 픽셀
    header('Content-Type: image/gif');
    echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xFF\xFF\xFF\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B";
    exit;
} else {
    // 클릭: 관리자 목록으로 리다이렉트
    header('Location: ' . (defined('PARTNER_ADMIN_URL') ? PARTNER_ADMIN_URL.'/index.php' : '/partner_lab/partner_admin/index.php') );
    exit;
}

