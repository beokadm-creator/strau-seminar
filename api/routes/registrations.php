<?php
/**
 * Registrations API Routes
 * 
 * 세미나 신청 관련 API 엔드포인트
 * - POST /api/registrations - 세미나 신청
 * - GET /api/registrations/my - 내 신청 내역
 * - GET /api/registrations/:id/qr - QR코드 조회
 * - POST /api/registrations/:id/cancel - 취소/환불
 */

global $request_method, $resource_id, $action, $is_authenticated, $current_member;

// 모든 엔드포인트는 인증 필요
require_auth();

// 새 신청 생성
if ($request_method === 'POST' && $resource_id === null) {
    // JSON 데이터 파싱
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 필수 필드 검증
    validate_required($input, ['seminar_id', 'payment_method']);
    
    $seminar_id = (int) $input['seminar_id'];
    $payment_method = sanitize_input($input['payment_method']);
    
    // 세미나 존재 여부 확인
    $seminar = sql_fetch("SELECT * FROM seminar_info WHERE id = {$seminar_id}");
    if (!$seminar) {
        json_response(false, null, 'not_found', '세미나를 찾을 수 없습니다.', 404);
    }
    
    // 상태 확인 (published만 신청 가능)
    if ($seminar['status'] !== 'published') {
        json_response(false, null, 'invalid_status', '현재 신청할 수 없는 세미나입니다.', 400);
    }
    
    // 신청 기간 확인
    $now = date('Y-m-d H:i:s');
    if ($now < $seminar['registration_start'] || $now > $seminar['registration_end']) {
        json_response(false, null, 'invalid_period', '신청 기간이 아닙니다.', 400);
    }
    
    // 중복 신청 확인
    $existing = sql_fetch("SELECT id, payment_status FROM seminar_registration 
                           WHERE seminar_id = {$seminar_id} 
                           AND mb_id = '{$current_member['mb_id']}'
                           AND payment_status IN ('paid', 'pending')
                           LIMIT 1");
    
    if ($existing) {
        json_response(false, null, 'already_registered', '이미 신청한 세미나입니다.', 400);
    }
    
    // 정원 확인
    $count_sql = "SELECT COUNT(*) AS cnt FROM seminar_registration 
                  WHERE seminar_id = {$seminar_id} AND payment_status = 'paid'";
    $count_result = sql_fetch($count_sql);
    $current_count = (int) $count_result['cnt'];
    
    if ($current_count >= $seminar['capacity']) {
        json_response(false, null, 'capacity_full', '정원이 가득찼습니다.', 400);
    }
    
    // QR 토큰 생성
    $qr_token = bin2hex(random_bytes(16)) . time();
    
    // 신청 생성
    $sql = "INSERT INTO seminar_registration 
            (seminar_id, mb_id, payment_status, attendance_status, payment_method, payment_amount, qr_code_token, created_at)
            VALUES 
            ({$seminar_id}, '{$current_member['mb_id']}', 'pending', 'pending', '{$payment_method}', {$seminar['price']}, '{$qr_token}', '{$now}')";
    
    sql_query($sql);
    $registration_id = sql_insert_id();
    
    // TODO: 실제 결제 모듈 연동 (PG사)
    // 현재는 바로 paid 처리 (테스트용)
    $update_sql = "UPDATE seminar_registration SET payment_status = 'paid', paid_at = '{$now}' WHERE id = {$registration_id}";
    sql_query($update_sql);
    
    // 생성된 신청 조회
    $registration = sql_fetch("SELECT * FROM seminar_registration WHERE id = {$registration_id}");
    
    json_response(true, $registration, null, null, 201);
}

// 내 신청 내역 조회
if ($request_method === 'GET' && $resource_id === null && $action === 'my') {
    $status_filter = isset($_GET['status']) ? $_GET['status'] : null;
    
    $where_clauses = ["r.mb_id = '{$current_member['mb_id']}'"];
    
    if ($status_filter) {
        $allowed_statuses = ['pending', 'paid', 'cancelled', 'refunded'];
        if (in_array($status_filter, $allowed_statuses)) {
            $where_clauses[] = "r.payment_status = '{$status_filter}'";
        }
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    $sql = "SELECT 
                r.id, r.seminar_id, r.payment_status, r.attendance_status,
                r.qr_code_token, r.created_at, r.paid_at,
                s.title, s.event_date, s.location, s.thumbnail_url
            FROM seminar_registration r
            INNER JOIN seminar_info s ON r.seminar_id = s.id
            WHERE {$where_sql}
            ORDER BY r.created_at DESC";
    
    $result = sql_query($sql);
    $registrations = [];
    
    while ($row = sql_fetch_array($result)) {
        $registrations[] = $row;
    }
    
    json_response(true, $registrations);
}

// QR코드 조회
if ($request_method === 'GET' && $resource_id !== null && $action === 'qr') {
    $registration_id = (int) $resource_id;
    
    // 본인의 신청인지 확인
    $sql = "SELECT r.*, s.title, s.event_date
            FROM seminar_registration r
            INNER JOIN seminar_info s ON r.seminar_id = s.id
            WHERE r.id = {$registration_id}
            AND r.mb_id = '{$current_member['mb_id']}'";
    
    $registration = sql_fetch($sql);
    
    if (!$registration) {
        json_response(false, null, 'not_found', '신청 내역을 찾을 수 없습니다.', 404);
    }
    
    // QR코드 URL 생성 (qrserver API 사용)
    $qr_data = $registration['qr_code_token'];
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);
    
    json_response(true, [
        'qr_code_url' => $qr_url,
        'token' => $registration['qr_code_token'],
        'seminar_title' => $registration['title'],
        'event_date' => $registration['event_date'],
        'payment_status' => $registration['payment_status'],
        'attendance_status' => $registration['attendance_status']
    ]);
}

// 취소/환불
if ($request_method === 'POST' && $resource_id !== null && $action === 'cancel') {
    $registration_id = (int) $resource_id;
    
    // 본인의 신청인지 확인
    $registration = sql_fetch("SELECT * FROM seminar_registration 
                              WHERE id = {$registration_id}
                              AND mb_id = '{$current_member['mb_id']}'");
    
    if (!$registration) {
        json_response(false, null, 'not_found', '신청 내역을 찾을 수 없습니다.', 404);
    }
    
    // 이미 취소/환불된 경우
    if (in_array($registration['payment_status'], ['cancelled', 'refunded'])) {
        json_response(false, null, 'already_cancelled', '이미 취소된 신청입니다.', 400);
    }
    
    // 결제 완료 상태만 취소 가능
    if ($registration['payment_status'] !== 'paid') {
        json_response(false, null, 'invalid_status', '취소할 수 없는 상태입니다.', 400);
    }
    
    // 세미나 일시 확인 (당일 취소 불가 등의 규칙 적용 가능)
    // 현재는 간단히 24시간 전까지만 취소 가능
    $seminar = sql_fetch("SELECT event_date FROM seminar_info WHERE id = {$registration['seminar_id']}");
    $event_time = strtotime($seminar['event_date']);
    $now = time();
    $hours_until_event = ($event_time - $now) / 3600;
    
    if ($hours_until_event < 24) {
        json_response(false, null, 'too_late', '세미나 24시간 전에는 취소할 수 없습니다.', 400);
    }
    
    // TODO: 실제 환불 처리 (PG사 연동)
    // 현재는 상태만 변경
    $now = date('Y-m-d H:i:s');
    $update_sql = "UPDATE seminar_registration 
                   SET payment_status = 'refunded', refunded_at = '{$now}'
                   WHERE id = {$registration_id}";
    sql_query($update_sql);
    
    json_response(true, [
        'id' => $registration_id,
        'payment_status' => 'refunded'
    ]);
}

// 지원하지 않는 메서드
json_response(false, null, 'method_not_allowed', '허용되지 않는 메서드입니다.', 405);
?>
