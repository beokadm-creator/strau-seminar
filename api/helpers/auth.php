<?php
/**
 * Authentication Helpers
 * 
 * 사용자 인증 및 권한 체크 관련 헬퍼 함수
 */

/**
 * 현재 사용자가 로그인되어 있는지 확인
 * 
 * @return bool 로그인 여부
 */
function is_logged_in() {
    global $member;
    return isset($member) && isset($member['mb_id']) && $member['mb_id'];
}

/**
 * 로그인이 필요한 엔드포인트에서 인증 체크
 * 로그인되어 있지 않으면 401 응답
 * 
 * @return array|null 로그인된 회원 정보, 로그인되지 않으면 종료
 */
function require_auth() {
    global $member;
    
    if (!isset($member) || !$member['mb_id']) {
        json_response(false, null, 'unauthorized', '로그인이 필요합니다.', 401);
    }
    
    return $member;
}

/**
 * 스태프 권한 확인
 * 
 * @return bool 스태프 권한 여부
 */
function is_staff() {
    global $member, $is_admin;
    
    // 최고관리자는 모든 권한 가짐
    if ($is_admin == 'super') {
        return true;
    }
    
    if (!isset($member['mb_id'])) {
        return false;
    }
    
    // g5_auth 테이블에서 seminar_staff 권한 확인
    global $g5;
    
    $sql = " SELECT COUNT(*) AS cnt 
            FROM {$g5['auth_table']} 
            WHERE mb_id = '{$member['mb_id']}' 
            AND au_auth LIKE '%seminar_staff%' ";
    $row = sql_fetch($sql);
    
    return $row['cnt'] > 0;
}

/**
 * 스태프 권한이 필요한 엔드포인트에서 권한 체크
 * 권한이 없으면 403 응답
 * 
 * @return void
 */
function require_staff() {
    require_auth(); // 먼저 로그인 확인
    
    if (!is_staff()) {
        json_response(false, null, 'forbidden', '스태프 권한이 필요합니다.', 403);
    }
}

/**
 * 관리자 권한 확인
 * 
 * @return bool 관리자 여부
 */
function is_admin_user() {
    global $is_admin;
    return $is_admin == 'super' || $is_admin == 'group';
}

/**
 * 관리자 권한이 필요한 엔드포인트에서 권한 체크
 * 권한이 없으면 403 응답
 * 
 * @return void
 */
function require_admin() {
    if (!is_admin_user()) {
        json_response(false, null, 'forbidden', '관리자 권한이 필요합니다.', 403);
    }
}
?>
