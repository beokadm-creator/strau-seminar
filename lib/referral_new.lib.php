<?php
/**
 * 업데이트된 추천인 시스템 라이브러리
 * 
 * 새로운 데이터베이스 컬럼(mb_referral_code, mb_referred_by, mb_referral_count, mb_referral_link)을 사용하도록 업데이트
 */

/**
 * 고유한 추천인 코드 생성
 */
function generate_referral_code($mb_id) {
    $salt = $mb_id . uniqid('', true) . mt_rand();
    $hex = substr(sha1($salt), 0, 12);
    $base36 = strtoupper(substr(base_convert($hex, 16, 36), 0, 6));
    return $base36;
}

function generate_unique_referral_code() {
    global $g5;
    while (true) {
        $code = generate_referral_code(mt_rand());
        $row = sql_fetch("SELECT COUNT(*) AS cnt FROM {$g5['member_table']} WHERE mb_referral_code = '{$code}'");
        if ((int)$row['cnt'] === 0) {
            return $code;
        }
    }
}

/**
 * 추천인 코드로 회원 정보 조회
 */
function get_member_by_referral_code($referral_code) {
    global $g5;
    
    $sql = "SELECT mb_no, mb_id, mb_name, mb_3, mb_referral_code, mb_referral_count 
            FROM {$g5['member_table']} 
            WHERE mb_referral_code = '{$referral_code}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = '' 
            LIMIT 1";
    
    return sql_fetch($sql);
}

/**
 * 회원의 추천인 코드 조회 또는 생성
 */
function get_or_create_referral_code($mb_id) {
    global $g5;
    
    // 기존 추천인 코드 조회
    $sql = "SELECT mb_referral_code FROM {$g5['member_table']} 
            WHERE mb_id = '{$mb_id}' 
            LIMIT 1";
    
    $row = sql_fetch($sql);
    
    if ($row['mb_referral_code']) {
        return $row['mb_referral_code'];
    }
    
    $referral_code = generate_unique_referral_code();
    
    $sql = "UPDATE {$g5['member_table']} 
            SET mb_referral_code = '{$referral_code}' 
            WHERE mb_id = '{$mb_id}'";
    
    if (sql_query($sql)) {
        return $referral_code;
    }
    
    return false;
}

/**
 * 추천인 코드가 유효한지 확인
 */
function is_valid_referral_code($referral_code) {
    $member = get_member_by_referral_code($referral_code);
    return $member ? true : false;
}

/**
 * 회원의 추천인 정보 조회 (누가 이 회원을 추천했는지)
 */
function get_member_referrer_info($mb_id) {
    global $g5;
    
    $sql = "SELECT mb_referred_by FROM {$g5['member_table']} 
            WHERE mb_id = '{$mb_id}' 
            LIMIT 1";
    
    $row = sql_fetch($sql);
    
    if ($row['mb_referred_by']) {
        // 추천인 정보 조회
        $sql = "SELECT mb_id, mb_name, mb_3, mb_referral_code 
                FROM {$g5['member_table']} 
                WHERE mb_referral_code = '{$row['mb_referred_by']}' 
                LIMIT 1";
        
        return sql_fetch($sql);
    }
    
    return null;
}

/**
 * 이 회원이 추천한 회원 수 조회
 */
function get_referral_count($mb_id) {
    global $g5;
    
    // 먼저 이 회원의 추천인 코드를 조회
    $sql = "SELECT mb_referral_code FROM {$g5['member_table']} WHERE mb_id = '{$mb_id}'";
    $row = sql_fetch($sql);
    
    if (!$row['mb_referral_code']) {
        return 0;
    }
    
    // 이 추천인 코드로 가입한 회원 수 조회
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_referred_by = '{$row['mb_referral_code']}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row = sql_fetch($sql);
    return $row['cnt'];
}

/**
 * 이 회원이 추천한 회원 목록 조회
 */
function get_referral_members($mb_id, $limit = 0, $offset = 0) {
    global $g5;
    
    // 먼저 이 회원의 추천인 코드를 조회
    $sql = "SELECT mb_referral_code FROM {$g5['member_table']} WHERE mb_id = '{$mb_id}'";
    $row = sql_fetch($sql);
    
    if (!$row['mb_referral_code']) {
        return false;
    }
    
    $limit_sql = $limit > 0 ? " LIMIT {$offset}, {$limit}" : "";
    
    $sql = "SELECT mb_id, mb_name, mb_3, mb_email, mb_datetime 
            FROM {$g5['member_table']} 
            WHERE mb_referred_by = '{$row['mb_referral_code']}' 
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
    $referral_code = get_or_create_referral_code($mb_id);
    
    if ($referral_code) {
        return G5_URL . '/bbs/register_form.php?referral_code=' . $referral_code;
    }
    
    return false;
}

/**
 * 추천인 코드로부터 회원가입 처리 (새로운 시스템)
 */
function process_referral_signup_new($referral_code, $new_member_id) {
    global $g5;
    
    // 추천인 코드로 추천인 정보 조회
    $referrer = get_member_by_referral_code($referral_code);
    
    if ($referrer) {
        // 새 회원의 추천인으로 설정 (mb_referred_by에 추천인 코드 저장)
        $sql = "UPDATE {$g5['member_table']} 
                SET mb_referred_by = '{$referral_code}' 
                WHERE mb_id = '{$new_member_id}'";
        
        if (sql_query($sql)) {
            // 추천인의 추천 수 증가
            $sql = "UPDATE {$g5['member_table']} 
                    SET mb_referral_count = mb_referral_count + 1 
                    WHERE mb_id = '{$referrer['mb_id']}'";
            sql_query($sql);
            
            // 추천 기록 테이블에 기록 (별도 테이블 사용)
            $sql = "INSERT INTO {$g5['member_referral_table']} 
                    SET rf_referrer_code = '{$referral_code}',
                        rf_referred_mb_no = '{$referrer['mb_no']}',
                        rf_referrer_mb_no = '{$referrer['mb_no']}',
                        rf_created_at = '".G5_TIME_YMDHIS."',
                        rf_status = 1";
            
            sql_query($sql);
            
            return true;
        }
    }
    
    return false;
}

/**
 * 추천인 통계 조회 (새로운 시스템)
 */
function get_referral_stats_new($mb_id) {
    global $g5;
    
    // 먼저 이 회원의 추천인 코드를 조회
    $sql = "SELECT mb_referral_code, mb_referral_count FROM {$g5['member_table']} WHERE mb_id = '{$mb_id}'";
    $row = sql_fetch($sql);
    
    if (!$row['mb_referral_code']) {
        return array(
            'referral_code' => '',
            'total_referrals' => 0,
            'this_month' => 0,
            'last_month' => 0,
            'this_year' => 0,
            'referral_link' => ''
        );
    }
    
    $stats = array(
        'referral_code' => $row['mb_referral_code'],
        'total_referrals' => $row['mb_referral_count'],
        'this_month' => 0,
        'last_month' => 0,
        'this_year' => 0,
        'referral_link' => G5_URL . '/bbs/register_form.php?referral_code=' . $row['mb_referral_code']
    );
    
    // 이번 달 추천인 수
    $this_month_start = date('Y-m-01 00:00:00');
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_referred_by = '{$row['mb_referral_code']}' 
            AND mb_datetime >= '{$this_month_start}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row_month = sql_fetch($sql);
    $stats['this_month'] = $row_month['cnt'];
    
    // 지난 달 추천인 수
    $last_month_start = date('Y-m-01 00:00:00', strtotime('-1 month'));
    $last_month_end = date('Y-m-01 00:00:00');
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_referred_by = '{$row['mb_referral_code']}' 
            AND mb_datetime >= '{$last_month_start}' 
            AND mb_datetime < '{$last_month_end}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row_last_month = sql_fetch($sql);
    $stats['last_month'] = $row_last_month['cnt'];
    
    // 올해 추천인 수
    $this_year_start = date('Y-01-01 00:00:00');
    $sql = "SELECT COUNT(*) as cnt FROM {$g5['member_table']} 
            WHERE mb_referred_by = '{$row['mb_referral_code']}' 
            AND mb_datetime >= '{$this_year_start}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''";
    
    $row_year = sql_fetch($sql);
    $stats['this_year'] = $row_year['cnt'];
    
    return $stats;
}

/**
 * 추천인 통계 및 추천받은 회원 목록 조회 (새로운 시스템)
 */
function get_referral_statistics_new($mb_id) {
    global $g5;
    
    // 먼저 이 회원의 추천인 코드를 조회
    $sql = "SELECT mb_referral_code, mb_referral_count FROM {$g5['member_table']} WHERE mb_id = '{$mb_id}'";
    $row = sql_fetch($sql);
    
    $stats = array(
        'total_referrals' => isset($row['mb_referral_count']) ? $row['mb_referral_count'] : 0,
        'referral_code' => isset($row['mb_referral_code']) ? $row['mb_referral_code'] : '',
        'referrals' => array()
    );
    
    if (!$row['mb_referral_code']) {
        return $stats;
    }
    
    // 추천받은 회원 목록 조회 (개인정보 보호를 위해 이름과 치과명만)
    $sql = "SELECT mb_name, mb_3, mb_datetime 
            FROM {$g5['member_table']} 
            WHERE mb_referred_by = '{$row['mb_referral_code']}' 
            AND mb_leave_date = '' 
            AND mb_intercept_date = ''
            ORDER BY mb_datetime DESC
            LIMIT 20";
    
    $result = sql_query($sql);
    while ($row = sql_fetch_array($result)) {
        $stats['referrals'][] = $row;
    }
    
    return $stats;
}

/**
 * 회원가입 시 추천인 코드 처리 통합 함수
 */
function process_member_referral($mb_id, $referral_code = '') {
    global $g5;
    
    // 1. 새 회원의 추천인 코드 생성
    $new_referral_code = get_or_create_referral_code($mb_id);
    
    if (!$new_referral_code) {
        return false;
    }
    
    // 2. 추천인 코드가 제공되었으면 처리
    if ($referral_code && is_valid_referral_code($referral_code)) {
        // 추천인 관계 설정
        $sql = "UPDATE {$g5['member_table']} 
                SET mb_referred_by = '{$referral_code}' 
                WHERE mb_id = '{$mb_id}'";
        
        sql_query($sql);
        
        // 추천인의 추천 수 증가
        $referrer = get_member_by_referral_code($referral_code);
        if ($referrer) {
            $sql = "UPDATE {$g5['member_table']} 
                    SET mb_referral_count = mb_referral_count + 1 
                    WHERE mb_referral_code = '{$referral_code}'";
            sql_query($sql);
            
            // 추천 기록 테이블에 기록
            $sql = "INSERT INTO {$g5['member_referral_table']} 
                    SET rf_referrer_code = '{$referral_code}',
                        rf_referred_mb_no = (SELECT mb_no FROM {$g5['member_table']} WHERE mb_id = '{$mb_id}'),
                        rf_created_at = '".date('Y-m-d H:i:s')."',
                        rf_status = 1";
            
            sql_query($sql);
        }
    }
    
    return $new_referral_code;
}
?>
