<?php
/**
 * Surveys API Routes
 * 
 * 설문조사 관련 API 엔드포인트
 * - POST /api/surveys - 설문조사 제출
 * - GET /api/surveys/my/:registration_id - 내 설문조사 조회
 */

global $request_method, $resource_id, $action, $current_member;

// 모든 엔드포인트는 인증 필요
require_auth();

// 설문조사 제출
if ($request_method === 'POST' && $resource_id === null) {
    // JSON 데이터 파싱
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 필수 필드 검증
    validate_required($input, ['registration_id']);
    
    $registration_id = (int) $input['registration_id'];
    
    // 본인의 신청인지 확인
    $registration = sql_fetch("SELECT r.id, r.seminar_id, r.mb_id, r.attendance_status,
                                     s.title, s.event_date
                              FROM seminar_registration r
                              INNER JOIN seminar_info s ON r.seminar_id = s.id
                              WHERE r.id = {$registration_id}
                              AND r.mb_id = '{$current_member['mb_id']}'
                              LIMIT 1");
    
    if (!$registration) {
        json_response(false, null, 'not_found', '신청 내역을 찾을 수 없습니다.', 404);
    }
    
    // 출석 완료만 설문조사 가능
    if ($registration['attendance_status'] !== 'attended') {
        json_response(false, null, 'not_attended', '출석이 완료되지 않아 설문조사를 작성할 수 없습니다.', 400);
    }
    
    // 이미 제출한 설문조사 확인
    $existing = sql_fetch("SELECT id FROM seminar_survey 
                           WHERE registration_id = {$registration_id} 
                           LIMIT 1");
    
    if ($existing) {
        json_response(false, null, 'already_submitted', '이미 제출한 설문조사가 있습니다.', 400);
    }
    
    // 만족도 점수 검증 (1-5)
    $satisfaction_fields = ['content_satisfaction', 'instructor_satisfaction', 'facility_satisfaction', 'overall_satisfaction'];
    
    foreach ($satisfaction_fields as $field) {
        $value = isset($input[$field]) ? (int) $input[$field] : null;
        if ($value < 1 || $value > 5) {
            json_response(false, null, 'validation_error', "{$field}은 1-5 사이의 값이어야 합니다.", 400);
        }
        $validated_data[$field] = $value;
    }
    
    // 건의사항 sanitize
    $suggestions = isset($input['suggestions']) ? sanitize_input($input['suggestions']) : '';
    
    // 설문조사 저장
    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO seminar_survey 
            (registration_id, content_satisfaction, instructor_satisfaction, facility_satisfaction, overall_satisfaction, suggestions, created_at)
            VALUES 
            ({$registration_id}, {$validated_data['content_satisfaction']}, {$validated_data['instructor_satisfaction']}, 
             {$validated_data['facility_satisfaction']}, {$validated_data['overall_satisfaction']}, '{$suggestions}', '{$now}')";
    
    sql_query($sql);
    $survey_id = sql_insert_id();
    
    // 생성된 설문조사 조회
    $survey = sql_fetch("SELECT * FROM seminar_survey WHERE id = {$survey_id}");
    
    json_response(true, $survey, null, null, 201);
}

// 내 설문조사 조회
if ($request_method === 'GET' && $resource_id === 'my' && $action !== null) {
    $registration_id = (int) $action;
    
    // 본인의 신청인지 확인
    $registration = sql_fetch("SELECT id FROM seminar_registration 
                              WHERE id = {$registration_id}
                              AND mb_id = '{$current_member['mb_id']}'
                              LIMIT 1");
    
    if (!$registration) {
        json_response(false, null, 'not_found', '신청 내역을 찾을 수 없습니다.', 404);
    }
    
    // 설문조사 조회
    $survey = sql_fetch("SELECT * FROM seminar_survey WHERE registration_id = {$registration_id}");
    
    if (!$survey) {
        json_response(false, null, 'not_found', '제출된 설문조사가 없습니다.', 404);
    }
    
    json_response(true, $survey);
}

// 지원하지 않는 메서드
json_response(false, null, 'method_not_allowed', '허용되지 않는 메서드입니다.', 405);
?>
