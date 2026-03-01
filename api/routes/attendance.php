<?php
/**
 * Attendance API Routes
 * 
 * 출석 관련 API 엔드포인트 (스태프용)
 * - POST /api/attendance/scan - QR 스캔 출석 체크
 */

global $request_method, $resource_id;

// 스태프 권한 필요
require_staff();

// QR 스캔 (출석 체크)
if ($request_method === 'POST' && $resource_id === 'scan') {
    // JSON 데이터 파싱
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 필수 필드 검증
    validate_required($input, ['qr_token']);
    
    $qr_token = sanitize_input($input['qr_token']);
    
    // QR 토큰으로 신청 내역 찾기
    $sql = "SELECT r.id, r.seminar_id, r.mb_id, r.attendance_status, r.payment_status,
                   s.title, s.event_date,
                   m.mb_name
            FROM seminar_registration r
            INNER JOIN seminar_info s ON r.seminar_id = s.id
            INNER JOIN g5_member m ON r.mb_id = m.mb_id
            WHERE r.qr_code_token = '{$qr_token}'
            LIMIT 1";
    
    $registration = sql_fetch($sql);
    
    if (!$registration) {
        json_response(false, null, 'invalid_token', '유효하지 않은 QR코드입니다.', 404);
    }
    
    // 결제 완료 상태만 출석 체크 가능
    if ($registration['payment_status'] !== 'paid') {
        json_response(false, null, 'not_paid', '결제가 완료되지 않은 신청입니다.', 400);
    }
    
    // 이미 출석 체크된 경우
    if ($registration['attendance_status'] === 'attended') {
        json_response(false, [
            'already_attended' => true,
            'registration' => [
                'id' => $registration['id'],
                'member' => [
                    'mb_id' => $registration['mb_id'],
                    'mb_name' => $registration['mb_name']
                ],
                'seminar' => [
                    'id' => $registration['seminar_id'],
                    'title' => $registration['title']
                ]
            ]
        ], 'already_attended', '이미 출석 체크된 참여자입니다.', 400);
    }
    
    // 출석 체크 업데이트
    $now = date('Y-m-d H:i:s');
    $update_sql = "UPDATE seminar_registration 
                   SET attendance_status = 'attended'
                   WHERE id = {$registration['id']}";
    sql_query($update_sql);
    
    // 출석 로그 기록 (선택사항)
    $log_sql = "INSERT INTO seminar_attendance_log 
                (registration_id, seminar_id, mb_id, scanned_by, scanned_at)
                VALUES 
                ({$registration['id']}, {$registration['seminar_id']}, '{$registration['mb_id']}', '{$_SESSION['ss_mb_id']}', '{$now}')";
    
    // 테이블이 존재하면 로그 기록
    @sql_query($log_sql);
    
    json_response(true, [
        'registration' => [
            'id' => $registration['id'],
            'member' => [
                'mb_id' => $registration['mb_id'],
                'mb_name' => $registration['mb_name']
            ],
            'seminar' => [
                'id' => $registration['seminar_id'],
                'title' => $registration['title'],
                'event_date' => $registration['event_date']
            ],
            'attendance_status' => 'attended'
        ]
    ]);
}

// 지원하지 않는 메서드
json_response(false, null, 'method_not_allowed', '허용되지 않는 메서드입니다.', 405);
?>
