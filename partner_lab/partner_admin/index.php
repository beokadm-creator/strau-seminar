<?php
// 파트너랩 관리자 메인 페이지 (주문 목록)

// G5 공통 파일 포함
include_once('../../common.php');
include_once('../../config.php');
include_once('./config.php');

// 강력한 관리자 접근 제어 (PHP 5.2 호환)
// 1) 비로그인 사용자는 로그인 페이지로 리다이렉트
// 2) 일반 회원은 메인 페이지로 리다이렉트 (접근 불가)
// 3) 관리자만 접근 가능 (super 관리자 기준)
{
    $req_uri   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : (defined('PARTNER_ADMIN_URL') ? PARTNER_ADMIN_URL.'/index.php' : './index.php');
    $login_url = defined('G5_BBS_URL') ? (G5_BBS_URL.'/login.php') : '/bbs/login.php';
    $main_url  = defined('G5_URL') ? G5_URL : '/';

    // 로그인 여부 판단
    $is_logged_in = false;
    if (isset($is_member)) {
        $is_logged_in = $is_member ? true : false;
    } else if (isset($is_guest)) {
        $is_logged_in = $is_guest ? false : true;
    } else if (isset($_SESSION['ss_mb_id']) && $_SESSION['ss_mb_id']) {
        $is_logged_in = true;
    }

    if (!$is_logged_in) {
        alert('로그인 후 이용 가능합니다.', $login_url.'?url='.urlencode($req_uri));
        exit;
    }

    // 관리자 여부 판단
    // - super 관리자 허용
    // - 특정 권한 있는 관리자 허용: mb_level == 10
    // - 폴백: 세션 레벨이 10 이상인 경우 허용
    $admin_ok = false;
    if (isset($is_admin)) {
        if ($is_admin === 'super') {
            $admin_ok = true;
        }
    }
    if (!$admin_ok && isset($member) && is_array($member) && isset($member['mb_level'])) {
        $admin_ok = ((int)$member['mb_level'] >= 10);
    }
    if (!$admin_ok && isset($_SESSION['ss_mb_level'])) {
        $admin_ok = ((int)$_SESSION['ss_mb_level'] >= 10);
    }

    if (!$admin_ok) {
        alert('접근 권한이 없습니다. 관리자만 이용 가능합니다.', $main_url);
        exit;
    }
}

// 데이터베이스 연결 (MySQLi)
$db = get_partner_lab_db_connection();

// 검색 조건
$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : '';
$search_keyword = isset($_GET['search_keyword']) ? $_GET['search_keyword'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// 페이지네이션
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$rows_per_page = 20;
$offset = ($page - 1) * $rows_per_page;

// 실제 컬럼 구조 확인 (DESCRIBE)
$orders_pk_col = 'order_id';
$orders_created_col = 'created_at';
$customer_name_col = 'customer_name';
$patient_name_col = 'patient_name';
$work_type_col = 'work_type';
// 가격/결제 관련 컬럼 제거: total_amount 미사용

$orders_cols = array();
$teeth_fk_col = 'order_id';
$files_fk_col = 'order_id';

$desc_orders_res = @mysqli_query($db, "DESCRIBE partner_lab_orders");
if ($desc_orders_res) {
    while ($r = mysqli_fetch_assoc($desc_orders_res)) {
        $orders_cols[] = $r['Field'];
        if (isset($r['Key']) && $r['Key'] === 'PRI') {
            $orders_pk_col = $r['Field'];
        }
    }
}
// 컬럼 존재 여부에 따른 폴백 처리
if (!in_array($orders_created_col, $orders_cols)) {
    // 자주 쓰이는 대체 컬럼 후보들
    $candidates = array('regdate', 'reg_date', 'wr_datetime', 'write_time', 'created');
    foreach ($candidates as $c) {
        if (in_array($c, $orders_cols)) { $orders_created_col = $c; break; }
    }
}
if (!in_array($customer_name_col, $orders_cols)) { $customer_name_col = null; }
if (!in_array($patient_name_col, $orders_cols)) { $patient_name_col = null; }
if (!in_array($work_type_col, $orders_cols)) { $work_type_col = null; }
// 금액/결제 관련 컬럼은 사용하지 않으므로 제거

// 상세 테이블 컬럼 확인
$desc_teeth_res = @mysqli_query($db, "DESCRIBE partner_lab_order_teeth");
if ($desc_teeth_res) {
    $teeth_cols = array();
    while ($r = mysqli_fetch_assoc($desc_teeth_res)) { $teeth_cols[] = $r['Field']; }
    if (!in_array($teeth_fk_col, isset($teeth_cols) ? $teeth_cols : array())) {
        // orders_pk_col이 동일 이름으로 존재할 수도 있음
        if (in_array($orders_pk_col, $teeth_cols)) { $teeth_fk_col = $orders_pk_col; }
        else {
            // 후보 찾기
            foreach ($teeth_cols as $c) { if (strpos($c, 'order') !== false) { $teeth_fk_col = $c; break; } }
        }
    }
}
$teeth_has_tooth_number = false;
if (isset($teeth_cols) && is_array($teeth_cols)) { $teeth_has_tooth_number = in_array('tooth_number', $teeth_cols); }
$teeth_details_fk_col = 'order_id';
$teeth_details_has_tooth_number = false;
$desc_teeth_details_res = @mysqli_query($db, "DESCRIBE partner_lab_order_teeth_details");
if ($desc_teeth_details_res) {
    $teeth_details_cols = array();
    while ($r = mysqli_fetch_assoc($desc_teeth_details_res)) { $teeth_details_cols[] = $r['Field']; }
    if (!in_array($teeth_details_fk_col, isset($teeth_details_cols) ? $teeth_details_cols : array())) {
        if (in_array($orders_pk_col, $teeth_details_cols)) { $teeth_details_fk_col = $orders_pk_col; }
        else { foreach ($teeth_details_cols as $c) { if (strpos($c, 'order') !== false) { $teeth_details_fk_col = $c; break; } } }
    }
    $teeth_details_has_tooth_number = in_array('tooth_number', $teeth_details_cols);
}
$desc_files_res = @mysqli_query($db, "DESCRIBE partner_lab_order_files");
if ($desc_files_res) {
    $files_cols = array();
    while ($r = mysqli_fetch_assoc($desc_files_res)) { $files_cols[] = $r['Field']; }
    if (!in_array($files_fk_col, isset($files_cols) ? $files_cols : array())) {
        if (in_array($orders_pk_col, $files_cols)) { $files_fk_col = $orders_pk_col; }
        else {
            foreach ($files_cols as $c) { if (strpos($c, 'order') !== false) { $files_fk_col = $c; break; } }
        }
    }
}

// 운영 모드: 디버그 수집/출력 제거

// 쿼리 작성 (MySQLi, 직접 이스케이프)
$where_conditions = array();

if ($search_type && $search_keyword) {
    $kw = mysqli_real_escape_string($db, $search_keyword);
    switch ($search_type) {
        case 'order_id':
            $where_conditions[] = "o.`" . $orders_pk_col . "` LIKE '%" . $kw . "%'";
            break;
        case 'customer_name':
            if ($customer_name_col) { $where_conditions[] = "o.`" . $customer_name_col . "` LIKE '%" . $kw . "%'"; }
            break;
        case 'patient_name':
            if ($patient_name_col) { $where_conditions[] = "o.`" . $patient_name_col . "` LIKE '%" . $kw . "%'"; }
            break;
    }
}

if ($status_filter) {
    $status = mysqli_real_escape_string($db, $status_filter);
    $where_conditions[] = "o.order_status = '" . $status . "'";
}

if ($date_from) {
    $df = mysqli_real_escape_string($db, $date_from);
    $where_conditions[] = "DATE(o.created_at) >= '" . $df . "'";
}

if ($date_to) {
    $dt = mysqli_real_escape_string($db, $date_to);
    $where_conditions[] = "DATE(o.created_at) <= '" . $dt . "'";
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// 운영 모드: 디버그 수집/출력 제거

// 전체 레코드 수 조회 (MySQLi)
$count_sql = "SELECT COUNT(*) as total FROM partner_lab_orders o $where_sql";
$count_res = mysqli_query($db, $count_sql);
$count_row = $count_res ? mysqli_fetch_assoc($count_res) : null;
$total_rows = ($count_row && isset($count_row['total'])) ? intval($count_row['total']) : 0;
$total_pages = ceil($total_rows / $rows_per_page);

// 주문 목록 조회 (MySQLi)
$teeth_count_main_sel = ($teeth_fk_col ? "(SELECT " . ($teeth_has_tooth_number ? "COUNT(DISTINCT ot.`tooth_number`)" : "COUNT(*)") . " FROM partner_lab_order_teeth ot WHERE ot.`" . $teeth_fk_col . "` = o.`" . $orders_pk_col . "`)" : "0");
$teeth_count_details_sel = ($desc_teeth_details_res && $teeth_details_fk_col ? "(SELECT " . ($teeth_details_has_tooth_number ? "COUNT(DISTINCT td.`tooth_number`)" : "COUNT(*)") . " FROM partner_lab_order_teeth_details td WHERE td.`" . $teeth_details_fk_col . "` = o.`" . $orders_pk_col . "`)" : "0");
$teeth_count_sel = "GREATEST(" . $teeth_count_main_sel . ", " . $teeth_count_details_sel . ")";
$file_count_sel = ($files_fk_col ? "(SELECT COUNT(*) FROM partner_lab_order_files of WHERE of.`" . $files_fk_col . "` = o.`" . $orders_pk_col . "`)" : "0");
$order_by_col = $orders_created_col ? $orders_created_col : $orders_pk_col;
$list_sql = "SELECT o.*, 
             " . $teeth_count_sel . " as teeth_count,
             " . $file_count_sel . " as file_count
             FROM partner_lab_orders o 
             $where_sql 
             ORDER BY o.`" . $order_by_col . "` DESC 
             LIMIT " . intval($offset) . ", " . intval($rows_per_page);

$orders = array();
$list_res = mysqli_query($db, $list_sql);
if ($list_res) {
    while ($row = mysqli_fetch_assoc($list_res)) {
        $orders[] = $row;
    }
}

if (!empty($orders)) {
    foreach ($orders as $i => $order) {
        $tc = isset($order['teeth_count']) ? intval($order['teeth_count']) : 0;
        if ($tc > 0) { $orders[$i]['teeth_count'] = $tc; continue; }
        $nums = array();
        if (isset($order['selected_teeth']) && trim((string)$order['selected_teeth']) !== '') {
            $sel = @json_decode($order['selected_teeth'], true);
            if (is_array($sel)) { foreach ($sel as $v) { if (is_numeric($v)) { $nums[] = (int)$v; } } }
            else {
                $parts = preg_split('/\s*,\s*/', $order['selected_teeth']);
                foreach ($parts as $p) { if (is_numeric($p)) { $nums[] = (int)$p; } }
            }
        }
        if (empty($nums) && isset($order['auto_save_data']) && trim((string)$order['auto_save_data']) !== '') {
            $snap = @json_decode($order['auto_save_data'], true);
            if (is_array($snap)) {
                if (isset($snap['selected_teeth']) && is_array($snap['selected_teeth'])) { foreach ($snap['selected_teeth'] as $v) { if (is_numeric($v)) { $nums[] = (int)$v; } } }
                if (empty($nums)) { foreach ($snap as $k => $v) { if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[/', $k, $m)) { $nums[] = (int)$m[1]; } } }
            }
        }
        if (!empty($nums)) { $tmp = array(); foreach ($nums as $__n) { $__n = (int)$__n; if ($__n > 0) { $tmp[] = $__n; } } $nums = array_values(array_unique($tmp)); }
        $orders[$i]['teeth_count'] = !empty($nums) ? count($nums) : 0;
    }
}

// 주문 상태 목록
$status_list = get_order_status_list();

// 페이지 제목
$g5['title'] = '파트너랩 관리자 - 주문 목록';

include_once('../../head.php');
?>

 

<style>
.partner-admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.search-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.search-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.search-form .form-control {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.search-form .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.stats-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    color: #666;
    margin-top: 5px;
}

.order-list-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.order-table {
    width: 100%;
    border-collapse: collapse;
}

.order-table th {
    background: #f8f9fa;
    padding: 14px;
    text-align: left;
    border-bottom: 2px solid #dee2e6;
    font-weight: bold;
    font-size: 15px;
}

.order-table td {
    padding: 14px;
    border-bottom: 1px solid #dee2e6;
    font-size: 15px;
}

.order-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: bold;
    color: white;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    min-width: 60px;
    display: inline-block;
    text-align: center;
}

.status-pending {
    background: linear-gradient(135deg, #ffc107, #ffb300);
    color: #212529;
    text-shadow: 0 1px 2px rgba(255,255,255,0.5);
}

.status-confirmed {
    background: linear-gradient(135deg, #17a2b8, #138496);
}

.status-processing {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

.status-shipping {
    background: linear-gradient(135deg, #6f42c1, #59359a);
}

.status-completed {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}
.status-done {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}

.status-cancelled {
    background: linear-gradient(135deg, #dc3545, #bd2130);
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-success {
    background: #28a745;
    color: white;
}

.pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
    gap: 5px;
}

.page-link {
    padding: 8px 12px;
    border: 1px solid #ddd;
    background: white;
    color: #007bff;
    text-decoration: none;
    border-radius: 4px;
}

.page-link:hover {
    background: #f8f9fa;
}

.page-link.active {
    background: #007bff;
    color: white;
}

.no-data {
    text-align: center;
    padding: 40px;
    color: #666;
}

@media (max-width: 768px) {
    .search-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .order-table {
        font-size: 14px;
    }
    
    .order-table th,
    .order-table td {
        padding: 8px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<!-- 공통 헤더 영역 (history/index와 동일한 구성) -->
<style>
.straumann-header { background:#fff; border-bottom:1px solid #e0e0e0; box-shadow:0 2px 4px rgba(0,0,0,0.1); position:sticky; top:0; z-index:1000; margin-bottom:20px; }
.header-container { max-width:1200px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; padding:15px 20px; }
.straumann-logo { height:40px; width:auto; }
.header-title { display:inline-block; font-size:24px; font-weight:600; color:#2c3e50; }
.header-user .history-link, .header-user .logout-btn, .header-user .login-btn, .header-user .new-order-btn, .header-user .campus-btn { background:#2d7663; color:#fff; padding:8px 16px; border-radius:4px; text-decoration:none; font-size:14px; transition:background-color 0.3s ease; margin-left:12px; }
.header-user .history-link:hover, .header-user .logout-btn:hover, .header-user .login-btn:hover, .header-user .new-order-btn:hover, .header-user .campus-btn:hover { background:#1f5a4a; }
.header-user { display:flex; align-items:center; gap:15px; }
.header-user .user-name { color:#333; font-weight:500; }
</style>

<div class="straumann-header">
  <div class="header-container">
    <div class="header-logo">
      <span class="header-title">스트라우만 코리아 주문 시스템</span>
    </div>
    <div class="header-user">
      <?php if (isset($is_member) && $is_member) { ?>
        <span class="user-name"><?php echo isset($member['mb_name']) ? $member['mb_name'] : ''; ?>님</span>
        <a href="/partner_lab/order/index.php" class="new-order-btn">신규주문</a>
        <a href="/partner_lab/order/history.php" class="history-link">주문내역</a>
        <a href="https://stkr-edu.com/" class="campus-btn" target="_blank">캠퍼스홈</a>
        <a href="<?php echo defined('G5_BBS_URL') ? G5_BBS_URL : '/bbs' ?>/logout.php" class="logout-btn">로그아웃</a>
      <?php } else { ?>
        <a href="/partner_lab/order/index.php" class="new-order-btn">신규주문</a>
        <a href="/partner_lab/order/history.php" class="history-link">주문내역</a>
        <a href="https://stkr-edu.com/" class="campus-btn" target="_blank">캠퍼스홈</a>
        <a href="<?php echo defined('G5_BBS_URL') ? G5_BBS_URL : '/bbs' ?>/login.php" class="login-btn">로그인</a>
      <?php } ?>
    </div>
  </div>
</div>

<div class="partner-admin-container">
    <h2>파트너랩 관리자</h2>
    <style>
    .pl-btn { font-size:14px; padding:6px 10px; border:1px solid #2a7f62; border-radius:4px; color:#2a7f62; text-decoration:none; font-weight:600; background:#fff; display:inline-block; }
    .pl-btn:hover { background:#e8f3ef; }
    .pl-btn--current { background:#2a7f62; color:#fff; border-color:#2a7f62; }
    .order-table { margin: 0 auto; }
    .order-table th, .order-table td { text-align: center; }
    .partner-admin-container { padding-bottom: 60px; }
    </style>
    <?php /* 운영에서는 디버그 패널 제거 */ ?>
    
    <!-- 검색 섹션 -->
    <div class="search-section">
        <form method="get" class="search-form">
            <select name="search_type" class="form-control">
                <option value="">검색조건</option>
                <option value="order_id" <?php echo $search_type === 'order_id' ? 'selected' : ''; ?>>주문번호</option>
                <option value="customer_name" <?php echo $search_type === 'customer_name' ? 'selected' : ''; ?>>고객명</option>
                <option value="patient_name" <?php echo $search_type === 'patient_name' ? 'selected' : ''; ?>>환자명</option>
            </select>
            
            <input type="text" name="search_keyword" class="form-control" placeholder="검색어" value="<?php echo htmlspecialchars($search_keyword); ?>">
            
            <select name="status" class="form-control">
                <option value="">전체상태</option>
                <?php foreach ($status_list as $key => $value): ?>
                <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" placeholder="시작일">
            <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" placeholder="종료일">
            
            <button type="submit" class="pl-btn" style="background:#fff;">검색</button>
            <a href="?" class="pl-btn">초기화</a>
        </form>
    </div>
    
    <!-- 통계 섹션 -->
    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($total_rows); ?></div>
            <div class="stat-label">전체 주문</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $pending_count = 0;
                foreach ($orders as $order) {
                    $pending_count += ($order['order_status'] === 'pending') ? 1 : 0;
                }
                echo $pending_count;
                ?>
            </div>
            <div class="stat-label">대기중</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $processing_count = 0;
                foreach ($orders as $order) {
                    $processing_count += (in_array($order['order_status'], array('confirmed', 'processing')) ? 1 : 0);
                }
                echo $processing_count;
                ?>
            </div>
            <div class="stat-label">처리중</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                $done_count = 0;
                foreach ($orders as $order) {
                    $done_count += ($order['order_status'] === 'done') ? 1 : 0;
                }
                echo $done_count;
                ?>
            </div>
            <div class="stat-label">완료</div>
        </div>
    </div>
    
    <!-- 주문 목록 -->
    <div class="order-list-section">
        <h3>주문 목록
            <?php
            $q = http_build_query(array(
                'search_type' => $search_type,
                'search_keyword' => $search_keyword,
                'status' => $status_filter,
                'date_from' => $date_from,
                'date_to' => $date_to,
            ));
            ?>
            <a href="export_orders_excel.php?<?php echo $q; ?>" class="pl-btn" style="float:right; margin-top:-4px;">엑셀 다운로드</a>
        </h3>
        
        <?php if (empty($orders)): ?>
        <div class="no-data">
            등록된 주문이 없습니다.
        </div>
        <?php else: ?>
        <table class="order-table">
            <thead>
                <tr>
                    <th>주문번호</th>
                    <th>주문일시</th>
                    <th>고객명</th>
                    <th>환자명</th>
                    <th>치아수</th>
                    <th>파일</th>
                    <th>상태</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): 
                    $status_class = 'status-' . $order['order_status'];
                    $status_name = get_order_status_name($order['order_status']);
                    $pk_value = isset($order[$orders_pk_col]) ? $order[$orders_pk_col] : '';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($pk_value); ?></td>
                    <td><?php echo isset($order['created_at']) ? date('Y-m-d H:i', strtotime($order['created_at'])) : (isset($order[$orders_created_col]) ? date('Y-m-d H:i', strtotime($order[$orders_created_col])) : ''); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['patient_name']); ?></td>
                    <td><?php echo $order['teeth_count']; ?></td>
                    <td><?php echo $order['file_count']; ?></td>
                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_name; ?></span></td>
                    <td>
                        <div class="action-buttons">
                            <a href="order_detail.php?order_id=<?php echo urlencode($pk_value); ?>" class="pl-btn">상세</a>
                            <button type="button" class="pl-btn" onclick="showStatusModal('<?php echo htmlspecialchars($pk_value); ?>', '<?php echo htmlspecialchars($order['order_status']); ?>')">상태변경</button>
                            <?php if (isset($is_admin) && $is_admin === 'super'): ?>
                            <button type="button" class="pl-btn" onclick="deleteOrder('<?php echo htmlspecialchars($pk_value); ?>')">삭제</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- 페이지네이션 -->
        <div class="pagination" style="text-align:center; margin-top:16px;">
            <?php
            $base_url = "?search_type=$search_type&search_keyword=" . urlencode($search_keyword) . "&status=$status_filter&date_from=$date_from&date_to=$date_to";
            
            if ($page > 1): ?>
            <a href="<?php echo $base_url; ?>&page=<?php echo $page - 1; ?>" class="pl-btn">이전</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo $base_url; ?>&page=<?php echo $i; ?>" class="pl-btn <?php echo $i === $page ? 'pl-btn--current' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="<?php echo $base_url; ?>&page=<?php echo $page + 1; ?>" class="pl-btn">다음</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// 상태 변경 모달 표시
function showStatusModal(orderId, currentStatus) {
    const modalHtml = `
        <div id="statusModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
            <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); max-width: 400px; width: 90%;">
                <h3 style="margin-top: 0; color: #333;">주문 상태 변경</h3>
                <p style="color: #666; margin-bottom: 20px;">주문번호: <strong>${orderId}</strong></p>
                <p style="color: #666; margin-bottom: 20px;">현재 상태: <strong>${getStatusName(currentStatus)}</strong></p>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">새로운 상태 선택:</label>
                    <select id="modalNewStatus" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                        <option value="">상태 선택</option>
                        <option value="pending">주문</option>
                        <option value="confirmed">주문접수</option>
                        <option value="processing">파트너 확인</option>
                        <option value="done">완료</option>
                    </select>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">변경 사유 (선택사항):</label>
                    <input type="text" id="modalStatusNote" placeholder="예: 고객 요청, 제작 완료 등" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                </div>
                
                <div style="text-align: right;">
                    <button type="button" onclick="closeStatusModal()" class="pl-btn" style="margin-right: 10px;">취소</button>
                    <button type="button" onclick="confirmStatusChange('${orderId}')" class="pl-btn pl-btn--current">변경</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

// 상태 이름 가져오기
function getStatusName(status) {
    const statusNames = {
        'pending': '주문',
        'confirmed': '주문접수',
        'processing': '파트너 확인',
        'done': '완료'
    };
    return statusNames[status] || status;
}

// 모달 닫기
function closeStatusModal() {
            const modal = document.getElementById('statusModal');
            if (modal) {
                modal.remove();
            }
        }

        function viewStatusHistory(orderId) {
            alert('상태 변경 이력 기능은 현재 사용할 수 없습니다.');
        }





// 상태 변경 확인
function confirmStatusChange(orderId) {
    const newStatus = document.getElementById('modalNewStatus').value;
    const statusNote = document.getElementById('modalStatusNote').value;
    
    if (!newStatus) {
        alert('새로운 상태를 선택해주세요.');
        return;
    }
    
    if (!confirm('주문 상태를 변경하시겠습니까?')) {
        return;
    }
    
    closeStatusModal();
    
    // 기존 updateOrderStatus 함수 호출
    updateOrderStatus(orderId, newStatus, statusNote);
}

function updateOrderStatus(orderId, newStatus, statusNote) {
    // 상태 변경 사유가 없으면 입력받기
    if (!statusNote) {
        statusNote = prompt('상태 변경 사유를 입력하세요 (선택사항):');
        if (statusNote === null) {
            return; // 취소
        }
    }
    
    if (!confirm('주문 상태를 변경하시겠습니까?')) {
        return;
    }
    
    // 폼 데이터로 전송 (PHP 5.2 호환성)
    var formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('status', newStatus);
    formData.append('note', statusNote);
    formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
    
    fetch('update_order_status.php', {
        method: 'POST',
        // Content-Type 헤더 제거 - FormData가 자동으로 설정
        body: formData
    })
    .then(function(response) {
        // 응답이 JSON인지 확인 (더 유연한 처리)
        var contentType = response.headers.get('content-type');
        
        // 응답을 먼저 텍스트로 읽기
        return response.text().then(function(text) {
            // JSON 형식인지 확인 (시작과 끝이 {} 또는 [] 인지)
            text = text.trim();
            if ((text.startsWith('{') && text.endsWith('}')) || (text.startsWith('[') && text.endsWith(']'))) {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('JSON 파싱 오류: ' + e.message + ', 응답: ' + text.substring(0, 200));
                }
            } else {
                // JSON이 아닌 경우
                throw new Error('서버에서 올바르지 않은 응답 형식이 반환되었습니다. 응답 형식: ' + contentType + ', 내용: ' + text.substring(0, 200));
            }
        });
    })
    .then(function(data) {
        if (data.success) {
            alert('주문 상태가 변경되었습니다.');
            location.reload();
        } else {
            alert('오류가 발생했습니다: ' + (data.message ? data.message : '알 수 없는 오류'));
        }
    })
    .catch(function(error) {
        if (error.message) {
            alert('오류가 발생했습니다: ' + error.message);
        } else {
            alert('네트워크 오류가 발생했습니다. 인터넷 연결을 확인해주세요.');
        }
    });
}

function deleteOrder(orderId) {
    if (!confirm('정말로 이 주문을 삭제하시겠습니까?\n관련 데이터(치아/파일/로그)도 함께 삭제됩니다.')) {
        return;
    }

    fetch('delete_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            order_id: orderId,
            csrf_token: '<?php echo generate_csrf_token(); ?>',
            soft_delete: false
        })
    })
    .then(function(response){ return response.json(); })
    .then(function(data){
        if (data && data.success) {
            alert('주문이 삭제되었습니다.');
            location.reload();
        } else {
            alert('삭제 실패: ' + (data && data.message ? data.message : '알 수 없는 오류'));
        }
    })
    .catch(function(error){
        alert('삭제 처리 중 오류가 발생했습니다.');
    });
}
</script>

<?php include_once('../../tail.php'); ?>
