<?php
/**
 * API Router - 스트라우만 세미나 시스템 RESTful API
 * 
 * Base URL: /api
 * 
 * Endpoints:
 * - GET    /api/seminars           - 세미나 목록 조회
 * - GET    /api/seminars/:id       - 세미나 상세 조회
 * - POST   /api/registrations      - 세미나 신청
 * - GET    /api/registrations/my   - 내 신청 내역
 * - GET    /api/registrations/:id/qr - QR코드 조회
 * - POST   /api/registrations/:id/cancel - 취소/환불
 * - POST   /api/attendance/scan    - QR 스캔 출석 체크 (스태프용)
 * - GET    /api/certificates/:id   - 수료증 PDF 다운로드
 * - POST   /api/surveys            - 설문조사 제출
 * - GET    /api/surveys/my/:id     - 내 설문조사 조회
 */

// CORS 헤더 설정 (React 개발 서버를 위한 CORS 허용)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// OPTIONS 요청 처리 (Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// JSON 응답 헬퍼 함수
function json_response($success, $data = null, $error = null, $message = null, $status_code = 200) {
    http_response_code($status_code);
    $response = ['success' => $success];
    
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    if ($message !== null) $response['message'] = $message;
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 경로 파싱
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];

// 쿼리 스트링 제거
$uri_path = parse_url($request_uri, PHP_URL_PATH);

// /api 경로 제거
$api_path = str_replace('/api', '', $uri_path);
$api_path = rtrim($api_path, '/');

// 경로 부분 나누기
$path_parts = explode('/', trim($api_path, '/'));

// 경고 출력 방지
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// 그누보드 공통 파일 포함
include_once('../_common.php');

// API 요청 라우팅
try {
    // 헬퍼 함수 포함
    include_once(__DIR__ . '/helpers/auth.php');
    include_once(__DIR__ . '/helpers/validation.php');
    
    // 인증 체크 (선택적 - 일부 엔드포인트는 비회원 접근 가능)
    $is_authenticated = false;
    $current_member = null;
    
    if (isset($member) && isset($member['mb_id'])) {
        $is_authenticated = true;
        $current_member = $member;
    }
    
    // 라우팅 처리
    $resource = $path_parts[0] ?? '';
    $resource_id = $path_parts[1] ?? null;
    $action = $path_parts[2] ?? null;
    
    switch ($resource) {
        case 'auth':
            include_once(__DIR__ . '/routes/auth.php');
            break;
            
        case 'seminars':
            include_once(__DIR__ . '/routes/seminars.php');
            break;
            
        case 'registrations':
            include_once(__DIR__ . '/routes/registrations.php');
            break;
            
        case 'attendance':
            include_once(__DIR__ . '/routes/attendance.php');
            break;
            
        case 'certificates':
            include_once(__DIR__ . '/routes/certificates.php');
            break;
            
        case 'surveys':
            include_once(__DIR__ . '/routes/surveys.php');
            break;
            
        default:
            json_response(false, null, 'not_found', 'API endpoint not found', 404);
    }
    
} catch (Exception $e) {
    json_response(false, null, 'server_error', $e->getMessage(), 500);
}
?>
