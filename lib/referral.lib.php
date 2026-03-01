<?php
// 추천인 링크 생성 및 관리 함수

/**
 * 고유한 추천인 코드 생성
 */
function generate_referral_code($mb_id) {
    // 회원 ID를 기반으로 고유한 코드 생성
    $timestamp = time();
    $random = mt_rand(1000, 9999);
    $hash = substr(md5($mb_id . $timestamp . $random), 0, 8);
    return strtoupper($hash);
}

/**
 * 추천인 코드로 회원 ID 조회
 */
function get_member_by_referral_code($referral_code) {
    global $g5;
    
    $sql = "SELECT mb_id, mb_name, mb_3 FROM {$g5['member_table']} 
            WHERE mb_referral_code = '{$referral_code}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = '' 
            LIMIT 1";
    
    return sql_fetch($sql);
}

/**
 * 회원의 추천인 코드 조회
 */
function get_member_referral_code($mb_id) {
    global $g5;
    
    $sql = "SELECT mb_referral_code FROM {$g5['member_table']} 
            WHERE mb_id = '{$mb_id}' 
            LIMIT 1";
    
    $row = sql_fetch($sql);
    return $row['mb_referral_code'];
}

/**
 * 추천인 코드가 유효한지 확인
 */
function is_valid_referral_code($referral_code) {
    $member = get_member_by_referral_code($referral_code);
    return $member ? true : false;
}

/**
 * 회원의 추천인 정보 조회
 */
function get_member_referral_info($mb_id) {
    global $g5;
    
    $sql = "SELECT mb_recommend FROM {$g5['member_table']} 
            WHERE mb_id = '{$mb_id}' 
            LIMIT 1";
    
    $row = sql_fetch($sql);
    
    if ($row['mb_recommend']) {
        // 추천인 정보 조회
        $sql = "SELECT mb_id, mb_name, mb_3 FROM {$g5['member_table']} 
                WHERE mb_id = '{$row['mb_recommend']}' 
                LIMIT 1";
        
        return sql_fetch($sql);
    }
    
    return null;
}

/**
 * 추천인으로 등록된 회원 수 조회
 */
function get_referral_count($mb_id) {
    global $g5;
    
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_recommend = '{$mb_id}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row = sql_fetch($sql);
    return $row['cnt'];
}

/**
 * 추천인으로 등록된 회원 목록 조회
 */
function get_referral_members($mb_id, $limit = 0, $offset = 0) {
    global $g5;
    
    $limit_sql = $limit > 0 ? " LIMIT {$offset}, {$limit}" : "";
    
    $sql = "SELECT mb_id, mb_name, mb_3, mb_email, mb_datetime 
            FROM {$g5['member_table']} 
            WHERE mb_recommend = '{$mb_id}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = '' 
            ORDER BY mb_datetime DESC 
            {$limit_sql}";
    
    return sql_query($sql);
}

/**
 * 추천인 링크 생성
 */
function get_referral_link($mb_id) {
    $referral_code = get_member_referral_code($mb_id);
    
    if (!$referral_code) {
        // 추천인 코드가 없으면 생성
        global $g5;
        $referral_code = generate_referral_code($mb_id);
        
        $sql = "UPDATE {$g5['member_table']} 
                SET mb_referral_code = '{$referral_code}' 
                WHERE mb_id = '{$mb_id}'";
        
        sql_query($sql);
    }
    
    return G5_URL . '/bbs/register.php?ref=' . $referral_code;
}

/**
 * 추천인 코드로부터 회원가입 처리
 */
function process_referral_signup($referral_code, $new_member_id) {
    global $g5;
    
    // 추천인 코드로 추천인 정보 조회
    $referrer = get_member_by_referral_code($referral_code);
    
    if ($referrer) {
        // 새 회원의 추천인으로 설정
        $sql = "UPDATE {$g5['member_table']} 
                SET mb_recommend = '{$referrer['mb_id']}' 
                WHERE mb_id = '{$new_member_id}'";
        
        sql_query($sql);
        
        // 추천인 로그 기록
        $sql = "INSERT INTO {$g5['referral_log_table']} 
                SET referrer_mb_id = '{$referrer['mb_id']}',
                    referred_mb_id = '{$new_member_id}',
                    rl_datetime = '".G5_TIME_YMDHIS."',
                    rl_ip = '{$_SERVER['REMOTE_ADDR']}'";
        
        sql_query($sql);
        
        return true;
    }
    
    return false;
}

/**
 * 추천인 통계 조회
 */
function get_referral_stats($mb_id) {
    global $g5;
    
    $stats = array(
        'total_referrals' => 0,
        'this_month' => 0,
        'last_month' => 0,
        'this_year' => 0
    );
    
    // 총 추천인 수
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_recommend = '{$mb_id}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row = sql_fetch($sql);
    $stats['total_referrals'] = $row['cnt'];
    
    // 이번 달 추천인 수
    $this_month_start = date('Y-m-01 00:00:00');
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_recommend = '{$mb_id}' 
            AND mb_datetime >= '{$this_month_start}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row = sql_fetch($sql);
    $stats['this_month'] = $row['cnt'];
    
    // 지난 달 추천인 수
    $last_month_start = date('Y-m-01 00:00:00', strtotime('-1 month'));
    $last_month_end = date('Y-m-01 00:00:00');
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_recommend = '{$mb_id}' 
            AND mb_datetime >= '{$last_month_start}' 
            AND mb_datetime < '{$last_month_end}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row = sql_fetch($sql);
    $stats['last_month'] = $row['cnt'];
    
    // 올해 추천인 수
    $this_year_start = date('Y-01-01 00:00:00');
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_recommend = '{$mb_id}' 
            AND mb_datetime >= '{$this_year_start}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row = sql_fetch($sql);
    $stats['this_year'] = $row['cnt'];
    
    return $stats;
}

/**
 * 추천인 통계 및 추천받은 회원 목록 조회
 */
function get_referral_statistics($mb_id) {
    global $g5;
    
    $stats = array(
        'total_referrals' => 0,
        'referral_code' => '',
        'referrals' => array()
    );
    
    // 추천 코드 조회
    $sql = "SELECT mb_referral_code FROM {$g5['member_table']} WHERE mb_id = '{$mb_id}'";
    $row = sql_fetch($sql);
    $stats['referral_code'] = $row['mb_referral_code'];
    
    // 총 추천인 수
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_recommend = '{$mb_id}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row = sql_fetch($sql);
    $stats['total_referrals'] = $row['cnt'];
    
    // 추천받은 회원 목록 조회 (개인정보 보호를 위해 이름과 치과명만)
    if ($stats['total_referrals'] > 0) {
        $sql = "SELECT mb_name, mb_3, mb_datetime 
                FROM {$g5['member_table']} 
                WHERE mb_recommend = '{$mb_id}' 
                AND mb_leave_date = '' 
                AND mb_intercept_date = ''
                ORDER BY mb_datetime DESC
                LIMIT 20";
        
        $result = sql_query($sql);
        while ($row = sql_fetch_array($result)) {
            $stats['referrals'][] = $row;
        }
    }
    
    return $stats;
}
?>