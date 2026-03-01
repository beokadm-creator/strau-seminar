<?php
/**
 * Authentication API
 * 
 * PHP 세션 기반 인증 정보를 React 앱에 제공
 */

/**
 * 현재 로그인된 사용자 정보 반환
 * GET /api/auth/me
 */
if ($request_method === 'GET' && $api_path === '/auth/me') {
    $user_data = null;
    $is_authenticated = false;
    
    if (isset($member) && isset($member['mb_id'])) {
        $is_authenticated = true;
        
        $user_data = [
            'id' => $member['mb_id'],
            'name' => $member['mb_name'] ?? '',
            'email' => $member['mb_email'] ?? '',
            'level' => $member['mb_level'] ?? 0,
            'is_admin' => ($is_admin == 'super' || $is_admin == 'group'),
            'is_staff' => is_staff(),
            'phone' => $member['mb_tel'] ?? '',
        ];
    }
    
    json_response(true, [
        'authenticated' => $is_authenticated,
        'user' => $user_data,
    ]);
    exit;
}

/**
 * 로그아웃
 * POST /api/auth/logout
 */
if ($request_method === 'POST' && $api_path === '/auth/logout') {
    if (isset($member) && $member['mb_id']) {
        
        $sql = " update {$g5['login_table']} set lo_logout = '".G5_TIME_YMDHIS."' where mb_id = '{$member['mb_id']}' and lo_ip = '{$_SERVER['REMOTE_ADDR']}' ";
        sql_query($sql);
    }

    session_destroy();
    
    json_response(true, null, null, '로그아웃 되었습니다.');
    exit;
}
