<?php
include_once('./_common.php');

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$clinic = isset($_POST['clinic']) ? trim($_POST['clinic']) : '';
$test_mode = isset($_POST['test_mode']) ? intval($_POST['test_mode']) : 0;
$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$page_size = isset($_POST['page_size']) ? intval($_POST['page_size']) : 20;
if ($page < 1) $page = 1;
if ($page_size < 1) $page_size = 20;

if (!$name && !$clinic) {
    echo json_encode(array('success' => false, 'message' => '이름 또는 치과명을 입력해주세요.'));
    exit;
}

// 테스트 모드인 경우 테스트 데이터베이스 연결
if ($test_mode) {
    $mysql_host = '121.78.91.42';
    $mysql_user = 'iuser07495';
    $mysql_password = 'printer!@12';
    $mysql_db = 'idb07495';

    $test_conn = mysql_connect($mysql_host, $mysql_user, $mysql_password);
    if (!$test_conn) {
        echo json_encode(array('success' => false, 'message' => '데이터베이스 연결 실패'));
        exit;
    }

    mysql_select_db($mysql_db, $test_conn);
    mysql_query("SET NAMES utf8", $test_conn);
    
    $search_keyword = '';
    if ($name && strlen($name) >= 2) {
        $search_keyword = mysql_real_escape_string($name);
    } elseif ($clinic && strlen($clinic) >= 2) {
        $search_keyword = mysql_real_escape_string($clinic);
    } else {
        echo json_encode(array('success' => false, 'message' => '검색어를 2글자 이상 입력해주세요.'));
        mysql_close($test_conn);
        exit;
    }
    
    // 퍼지 검색을 위한 공백 제거 및 LIKE 패턴 생성
    $search_pattern = str_replace(' ', '', $search_keyword);
    $like_pattern = '%' . $search_pattern . '%';
    
    // 추천인 검색 쿼리 (퍼지 검색 적용)
    $where = array();
    $where[] = "mb_referral_code != ''";
    
    if ($name) {
        // 이름 검색 - 공백 제거하고 LIKE 검색
        $where[] = "REPLACE(mb_name, ' ', '') LIKE '%" . str_replace(' ', '', mysql_real_escape_string($name)) . "%'";
    } elseif ($clinic) {
        // 소속 검색 - 공백 제거하고 LIKE 검색
        $where[] = "REPLACE(mb_3, ' ', '') LIKE '%" . str_replace(' ', '', mysql_real_escape_string($clinic)) . "%'";
    }
    
    $where_sql = implode(' AND ', $where);
    
    $count_sql = "SELECT COUNT(*) AS cnt FROM g5_member WHERE " . $where_sql;
    $count_result = mysql_query($count_sql, $test_conn);
    $total_count = 0;
    if ($count_result) {
        $count_row = mysql_fetch_assoc($count_result);
        $total_count = intval($count_row['cnt']);
    }

    $offset = ($page - 1) * $page_size;
    $list_sql = "SELECT mb_id, mb_name, mb_nick, mb_3, mb_referral_code FROM g5_member WHERE " . $where_sql . " ORDER BY mb_name LIMIT " . intval($page_size) . " OFFSET " . intval($offset);
    $result = mysql_query($list_sql, $test_conn);
    
    if ($result && mysql_num_rows($result) > 0) {
        $members = array();
        while ($row = mysql_fetch_assoc($result)) {
            $members[] = $row;
        }
        
        $html = '';
        foreach ($members as $member) {
                $html .= '<div style="border: 1px solid #ddd; padding: 12px; margin: 6px 0; border-radius: 3px; background: #fff; text-align: center; font-size:15px; letter-spacing:0.5px;">';
                $html .= '<div style="font-weight:700; margin-bottom:4px;">' . $member['mb_name'] . ' (' . $member['mb_id'] . ')</div>';
                $html .= '<div style="margin-bottom:4px;">치과명: ' . $member['mb_3'] . '</div>';
                $html .= '<button type="button" onclick="selectReferrer(\'' . $member['mb_id'] . '\', \'' . $member['mb_name'] . '\', \'' . $member['mb_referral_code'] . '\')" style="margin-top: 4px; padding: 6px 12px; font-size: 15px; display: inline-block;" class="btn_frmline">선택</button>';
                $html .= '</div>';
        }
        echo json_encode(array(
            'success' => true,
            'html' => $html,
            'count' => count($members),
            'total_count' => $total_count,
            'page' => $page,
            'page_size' => $page_size,
            'total_pages' => $page_size > 0 ? ceil($total_count / $page_size) : 1,
            'message' => '총 ' . $total_count . '명의 추천인을 찾았습니다.'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => '검색 조건에 맞는 추천인을 찾을 수 없습니다.'));
    }
    
    mysql_close($test_conn);
} else {
    // 라이브 모드 - 기존 g5_member 테이블 사용
    include_once(G5_LIB_PATH.'/referral_new.lib.php');
    
    $search_keyword = '';
    if ($name && strlen($name) >= 2) {
        $search_keyword = $name;
    } elseif ($clinic && strlen($clinic) >= 2) {
        $search_keyword = $clinic;
    } else {
        echo json_encode(array('success' => false, 'message' => '검색어를 2글자 이상 입력해주세요.'));
        exit;
    }
    
    // 퍼지 검색을 위한 공백 제거 및 LIKE 패턴 생성
    $search_pattern = str_replace(' ', '', $search_keyword);
    
    // 기본 검색 조건 설정 (퍼지 검색 적용)
    $sql_search = "";
    if ($name) {
        // 이름 검색 - 공백 제거하고 LIKE 검색
        $name_pattern = str_replace(' ', '', $name);
        $sql_search .= " AND REPLACE(mb_name, ' ', '') LIKE '%{$name_pattern}%'";
    }
    if ($clinic) {
        // 소속 검색 - 공백 제거하고 LIKE 검색
        $clinic_pattern = str_replace(' ', '', $clinic);
        $sql_search .= " AND REPLACE(mb_3, ' ', '') LIKE '%{$clinic_pattern}%'";
    }
    
    $count_sql = " SELECT COUNT(*) AS cnt 
             FROM {$g5['member_table']} 
             WHERE mb_referral_code != '' {$sql_search} ";
    $count_row = sql_fetch($count_sql);
    $total_count = intval($count_row['cnt']);

    $offset = ($page - 1) * $page_size;
    $list_sql = " SELECT mb_id, mb_name, mb_nick, mb_3, mb_referral_code 
             FROM {$g5['member_table']} 
             WHERE mb_referral_code != '' {$sql_search} 
             ORDER BY mb_name 
             LIMIT {$page_size} OFFSET {$offset} ";
    
    $result = sql_query($list_sql);
    $members = array();
    
    while ($row = sql_fetch_array($result)) {
        $members[] = $row;
    }
    
    if (count($members) > 0) {
        $html = '';
        foreach ($members as $member) {
            $html .= '<div style="border: 1px solid #ddd; padding: 12px; margin: 6px 0; border-radius: 3px; background: #fff; text-align: center; font-size:15px; line-height:1.8; letter-spacing:0.5px;">';
                $html .= '<div style="font-weight:700; margin-bottom:4px;">' . $member['mb_name'] . ' (' . $member['mb_id'] . ')</div>';
                $html .= '<div style="margin-bottom:4px;">치과명: ' . $member['mb_3'] . '</div>';
                $html .= '<button type="button" onclick="selectReferrer(\'' . $member['mb_id'] . '\', \'' . $member['mb_name'] . '\', \'' . $member['mb_referral_code'] . '\')" style="margin-top: 4px; padding: 6px 12px; font-size: 15px; display: inline-block;" class="btn_frmline">선택</button>';
            $html .= '</div>';
        }
        echo json_encode(array(
            'success' => true,
            'html' => $html,
            'count' => count($members),
            'total_count' => $total_count,
            'page' => $page,
            'page_size' => $page_size,
            'total_pages' => $page_size > 0 ? ceil($total_count / $page_size) : 1,
            'message' => '총 ' . $total_count . '명의 추천인을 찾았습니다.'
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => '검색 조건에 맞는 추천인을 찾을 수 없습니다.'));
    }
}

?>
