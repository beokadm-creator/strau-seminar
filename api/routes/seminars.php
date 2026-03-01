<?php
/**
 * Seminars API Routes
 * 
 * 세미나 관련 API 엔드포인트
 * - GET /api/seminars - 세미나 목록 조회
 * - GET /api/seminars/:id - 세미나 상세 조회
 */

global $request_method, $resource_id, $is_authenticated, $current_member;

// 목록 조회
if ($request_method === 'GET' && $resource_id === null) {
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 10;
    $status_filter = isset($_GET['status']) ? $_GET['status'] : null;
    $offset = ($page - 1) * $limit;
    
    $where_clauses = ["1=1"];
    $params = [];
    
    // 상태 필터
    if ($status_filter) {
        $allowed_statuses = ['draft', 'published', 'closed'];
        if (in_array($status_filter, $allowed_statuses)) {
            $where_clauses[] = "status = ?";
            $params[] = $status_filter;
        }
    }
    
    // 비회원은 published만 조회 가능
    if (!$is_authenticated) {
        $where_clauses[] = "status = 'published'";
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    // 전체 개수 조회
    $count_sql = "SELECT COUNT(*) AS total FROM seminar_info WHERE {$where_sql}";
    $count_result = sql_fetch($count_sql);
    $total = (int) $count_result['total'];
    $total_pages = ceil($total / $limit);
    
    // 목록 조회
    $sql = "SELECT 
                id, title, description, event_date, location, capacity, price,
                thumbnail_url, poster_url, registration_start, registration_end, status,
                created_at
            FROM seminar_info 
            WHERE {$where_sql}
            ORDER BY event_date ASC
            LIMIT {$limit} OFFSET {$offset}";
    
    $result = sql_query($sql);
    $seminars = [];
    
    while ($row = sql_fetch_array($result)) {
        // 현재 신청 인원 조회
        $reg_count_sql = "SELECT COUNT(*) AS cnt FROM seminar_registration 
                          WHERE seminar_id = '{$row['id']}' AND payment_status = 'paid'";
        $reg_count_result = sql_fetch($reg_count_sql);
        $row['current_registrations'] = (int) $reg_count_result['cnt'];
        
        $seminars[] = $row;
    }
    
    json_response(true, [
        'seminars' => $seminars,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $total_pages
        ]
    ]);
}

// 상세 조회
if ($request_method === 'GET' && $resource_id !== null) {
    $seminar_id = (int) $resource_id;
    
    // 세미나 조회
    $sql = "SELECT * FROM seminar_info WHERE id = {$seminar_id}";
    $seminar = sql_fetch($sql);
    
    if (!$seminar) {
        json_response(false, null, 'not_found', '세미나를 찾을 수 없습니다.', 404);
    }
    
    // 비회원은 published만 조회 가능
    if (!$is_authenticated && $seminar['status'] !== 'published') {
        json_response(false, null, 'forbidden', '조회 권한이 없습니다.', 403);
    }
    
    // 현재 신청 인원 조회
    $reg_count_sql = "SELECT COUNT(*) AS cnt FROM seminar_registration 
                      WHERE seminar_id = {$seminar_id} AND payment_status = 'paid'";
    $reg_count_result = sql_fetch($reg_count_sql);
    $seminar['current_registrations'] = (int) $reg_count_result['cnt'];
    
    // 현재 회원의 신청 여부 확인
    $seminar['is_registered'] = false;
    if ($is_authenticated) {
        $check_sql = "SELECT id FROM seminar_registration 
                      WHERE seminar_id = {$seminar_id} 
                      AND mb_id = '{$current_member['mb_id']}'
                      AND payment_status IN ('paid', 'pending')
                      LIMIT 1";
        $check_result = sql_fetch($check_sql);
        $seminar['is_registered'] = $check_result !== false;
    }
    
    // description의 HTML 태그 허용 (에디터로 작성된 내용)
    $seminar['description'] = $seminar['description'];
    
    json_response(true, $seminar);
}

// 지원하지 않는 메서드
json_response(false, null, 'method_not_allowed', '허용되지 않는 메서드입니다.', 405);
?>
