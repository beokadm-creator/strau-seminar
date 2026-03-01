<?php
// 주문 내역 페이지 (PHP 5.2 호환)
// 현재 로그인한 사용자의 이전 주문, 진행중, 취소, 임시저장 목록을 조회하고 상세/재진행 기능 제공

// 공통/DB 유틸 포함 (__DIR__ 대신 dirname(__FILE__))
include_once dirname(__FILE__).'/../_common.php';
include_once dirname(__FILE__).'/db_config.php';
include_once dirname(__FILE__).'/db_utils.php';

// 로그인 체크
$req_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : './history.php';
if (isset($is_guest) ? $is_guest : (isset($is_member) ? !$is_member : (!isset($_SESSION['ss_mb_id']) || !$_SESSION['ss_mb_id']))) {
    $login_base = defined('G5_BBS_URL') ? G5_BBS_URL.'/login.php' : '/bbs/login.php';
    alert('로그인 후 이용 가능합니다.', $login_base.'?url='.urlencode($req_uri));
    exit;
}

// 현재 로그인 사용자 ID 파악
$current_mb_id = '';
if (isset($member) && is_array($member) && isset($member['mb_id'])) {
    $current_mb_id = $member['mb_id'];
} else if (isset($_SESSION['ss_mb_id'])) {
    $current_mb_id = $_SESSION['ss_mb_id'];
}

// DB 연결
$conn = getDBConnection();
if (!$conn) {
    echo '<div style="padding:20px;color:#b00020">데이터베이스 연결 오류가 발생했습니다.</div>';
    exit;
}

// 기본키 컬럼 탐지
$orderPk = dbu_get_order_pk($conn);
if (!$orderPk) { $orderPk = 'order_id'; }

// 필터와 페이지네이션 파라미터
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$page_size = 10;
$offset = ($page - 1) * $page_size;

// 상태 매핑 (UI 라벨)
$status_labels = array(
    'completed' => '주문완료',
    'processing' => '진행중',
    'cancelled' => '취소됨',
    'draft' => '임시저장',
    'submitted' => '제출됨',
    'confirmed' => '주문확인'
);

// WHERE 구성 (칼럼 동적 감지 제거, 고정 스키마 사용)
$where = array();
// 로그인 사용자 필터 (관리자 제외)
$member_col = 'mb_id';
$is_super_admin = (isset($is_admin) && $is_admin === 'super') ? true : false;
if (!$is_super_admin && !empty($current_mb_id)) {
    $safe_mb = ($conn instanceof PDO) ? $current_mb_id : mysqli_real_escape_string($conn, $current_mb_id);
    $where[] = "`{$member_col}` = '".$safe_mb."'";
}
if (!empty($status)) {
    $where[] = "order_status = '".mysqli_real_escape_string($conn, $status)."'";
}
$where_sql = count($where) ? ('WHERE '.implode(' AND ', $where)) : '';

// 총 개수
$count_sql = "SELECT COUNT(*) AS cnt FROM partner_lab_orders {$where_sql}";
$total_count = 0;
if ($conn instanceof PDO) {
    $stmt = $conn->query($count_sql);
    if ($stmt) { $row = $stmt->fetch(PDO::FETCH_ASSOC); if ($row && isset($row['cnt'])) $total_count = (int)$row['cnt']; }
} else if (is_object($conn) && (get_class($conn) === 'mysqli')) {
    $res = mysqli_query($conn, $count_sql);
    if ($res) { $row = mysqli_fetch_assoc($res); if ($row && isset($row['cnt'])) $total_count = (int)$row['cnt']; }
}
$total_pages = $total_count > 0 ? ceil($total_count / $page_size) : 1;
if ($total_pages < 1) $total_pages = 1;
if ($page > $total_pages) { $page = $total_pages; $offset = ($page - 1) * $page_size; }

// 목록 조회 (최근 생성일 순) - 고정 칼럼 사용
$created_col = 'created_at';
$order_number_col = 'order_number';
$list_sql = "SELECT * FROM partner_lab_orders {$where_sql} ORDER BY `{$created_col}` DESC LIMIT ".$offset.", ".$page_size;
$orders = array();
if ($conn instanceof PDO) {
    $stmt = $conn->query($list_sql);
    if ($stmt) { $orders = $stmt->fetchAll(PDO::FETCH_ASSOC); }
} else if (is_object($conn) && (get_class($conn) === 'mysqli')) {
    $res = mysqli_query($conn, $list_sql);
    if ($res) { while ($row = mysqli_fetch_assoc($res)) { $orders[] = $row; } }
}

// 헤더 출력 (G5 테마 사용)
if (isset($g5) && is_array($g5)) {
    $g5['title'] = '주문내역';
    if (defined('G5_PATH') && file_exists(G5_PATH.'/head.php')) {
        include_once G5_PATH.'/head.php';
    }
}
?>
<style>
#hd, #hd_wrap, #tnb, #snb, #gnb, .gnb, .lnb, .menu, .gnb_wrap, .hd, .top_menu, #hd_menu_all { display: none !important; }
</style>

<script>
// 페이지 전용 body 클래스 추가 (테마와의 충돌 방지)
document.addEventListener('DOMContentLoaded', function(){
    if (document && document.body) {
        document.body.classList.add('partner-lab-history-page');
    }
});
</script>

<style>
/* 공통 헤더 스타일 (index.php와 동일한 톤/레퍼런스) */
.straumann-header {
    background: #fff;
    border-bottom: 1px solid #e0e0e0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    margin-bottom: 20px;
}
.header-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
}
.straumann-logo {
    height: 40px;
    width: auto;
}
.header-title {
    display: inline-block;
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
}
.header-user .history-link,
.header-user .logout-btn {
    background: #2d7663;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    transition: background-color 0.3s ease;
    margin-left: 12px;
}
.header-user .history-link:hover,
.header-user .logout-btn:hover,
.header-user .login-btn:hover,
.header-user .new-order-btn:hover,
.header-user .campus-btn:hover { background: #1f5a4a; }
.header-user { display: flex; align-items: center; gap: 15px; }
.header-user .user-name { color: #333; font-weight: 500; }
.header-user .history-link, .header-user .logout-btn, .header-user .login-btn, .header-user .new-order-btn, .header-user .campus-btn {
    background: #2d7663;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    transition: background-color 0.3s ease;
    margin-left: 12px;
}

/* 통일 버튼 스타일 (index의 "새 주문하기" 기준) */
.pl-btn {
    font-size: 14px;
    padding: 6px 10px;
    border: 1px solid #2a7f62;
    border-radius: 4px;
    color: #2a7f62;
    text-decoration: none;
    font-weight: 600;
    background: #fff;
    display: inline-block;
}
.pl-btn:hover { background: #e8f3ef; }
.pl-btn--current {
    background: #2a7f62;
    color: #fff;
    border-color: #2a7f62;
}
.pl-btn--disabled {
    opacity: 0.6;
    pointer-events: none;
}

/* 페이지 베이스 스타일 (index.php 톤 맞춤) */
body.partner-lab-history-page {
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 17px;
    line-height: 1.6;
    color: #333;
    background-color: #f8f9fa;
}

/* 컨테이너 너비/여백 일치 */
.order-history-container {
    max-width: 1200px;
    margin: 0 auto;
    padding-top: 60px; /* sticky header 공간 확보 */
    padding-bottom: 60px; /* 푸터와 간격 확보 */
}

/* 필터 폼 일관화 */
.sch_frm {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
}
.sch_frm label { font-weight: 500; color: #2c3e50; }
.sch_frm select {
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}
.btn.btn_01 {
    background: #2d7663;
    color: #fff;
    padding: 8px 14px;
    border-radius: 4px;
    border: none;
}
.btn.btn_01:hover { background: #1f5a4a; }
.btn.btn_02 {
    background: #fff;
    color: #2d7663;
    border: 1px solid #2d7663;
    padding: 8px 14px;
    border-radius: 4px;
}
.btn.btn_02:hover { background: #e8f3ef; }

/* 테이블/페이지네이션 톤 보정 */
.tbl_head01 caption { display: none; }
.pg_wrap { margin-top: 16px; text-align: center; }
.pg_wrap .pl-btn { margin: 0 4px; }

/* 리스트 가운데 정렬 및 셀 정렬 */
.tbl_wrap table { margin: 0 auto; }
.tbl_wrap table th, .tbl_wrap table td { text-align: center; }

.empty_list {
    padding: 20px;
    border: 1px dashed #ddd;
    background: #fff;
    border-radius: 6px;
}

/* 반응형 헤더 */
@media (max-width: 768px) {
    .header-container { flex-direction: column; gap: 15px; padding: 15px; }
}
</style>

<!-- 공통 헤더 영역 -->
<div class="straumann-header">
    <div class="header-container">
        <div class="header-logo">
            <span class="header-title">스트라우만 코리아 주문 시스템</span>
        </div>
        <div class="header-user">
            <?php if ($is_member) { ?>
                <span class="user-name"><?php echo $member['mb_name'] ?>님</span>
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

<div class="order-history-container">
    <h2 class="sound_only">주문내역</h2>

    <form method="get" action="history.php" class="sch_frm">
        <label for="status">상태 필터</label>
        <select name="status" id="status">
            <option value="">전체</option>
            <?php foreach ($status_labels as $code => $label) { $sel = ($status === $code) ? ' selected' : ''; ?>
            <option value="<?php echo htmlspecialchars($code); ?>"<?php echo $sel; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php } ?>
        </select>
        <button type="submit" class="pl-btn" style="background:#fff;">적용</button>
    </form>

    <?php if (empty($orders)) { ?>
        <div class="empty_list">표시할 주문이 없습니다.</div>
    <?php } else { ?>
        <div class="tbl_head01 tbl_wrap">
            <table>
                <caption>주문내역</caption>
                <thead>
                    <tr>
                        <th scope="col">주문번호</th>
                        <th scope="col">고객명</th>
                        <th scope="col">주문상태</th>
                        <th scope="col">주문일</th>
                        <th scope="col">주문서 확인</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $row) {
                    $oid_pk = isset($row[$orderPk]) ? $row[$orderPk] : null;
                    $oid_display = ($order_number_col && isset($row[$order_number_col])) ? $row[$order_number_col] : $oid_pk;
                    $cust = isset($row['customer_name']) ? $row['customer_name'] : '';
                    $st = isset($row['order_status']) ? $row['order_status'] : '';
                    $dt = isset($row[$created_col]) ? $row[$created_col] : '';
                    $st_name = isset($status_labels[$st]) ? $status_labels[$st] : $st;
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($oid_display); ?></td>
                        <td><?php echo htmlspecialchars($cust); ?></td>
                        <td><?php echo htmlspecialchars($st_name); ?></td>
                        <td><?php echo htmlspecialchars($dt); ?></td>
                        <td>
                            <a class="pl-btn" href="confirm.php?order_id=<?php echo urlencode($oid_pk); ?>">상세보기</a>
                            <?php if ($st === 'draft' || $st === 'submitted') { ?>
                                <a class="pl-btn pl-btn--current" href="index.php?order_id=<?php echo urlencode($oid_pk); ?>">재진행</a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="pg_wrap" style="margin-top:16px">
            <?php
            $base = 'history.php?'.http_build_query(array('status' => $status));
            if ($page > 1) {
                echo '<a class="pl-btn" href="'.$base.'&page='.($page-1).'">이전</a> ';
            } else {
                echo '<span class="pl-btn pl-btn--disabled">이전</span> ';
            }
            for ($i=1;$i<=$total_pages;$i++) {
                if ($i == $page) echo '<strong class="pl-btn pl-btn--current">'.$i.'</strong>';
                else echo '<a class="pl-btn" href="'.$base.'&page='.$i.'">'.$i.'</a>';
            }
            if ($page < $total_pages) {
                echo ' <a class="pl-btn" href="'.$base.'&page='.($page+1).'">다음</a>';
            } else {
                echo ' <span class="pl-btn pl-btn--disabled">다음</span>';
            }
            ?>
        </div>
    <?php } ?>
</div>

<?php
// 푸터 (G5 테마 사용)
?>
<div class="partner-footer" style="margin-top:24px;border-top:1px solid #e0e0e0;background:#fff;font-family:inherit">
  <div style="max-width:1200px;margin:0 auto;padding:16px;display:flex;gap:16px;align-items:flex-start">
    <div style="min-width:160px">
      <img src="/img/p_logo.png" alt="쓰리포인트덴탈 로고" style="height:48px;width:auto">
    </div>
    <div style="flex:1 1 auto">
      <p style="margin:0 0 10px 0;line-height:1.7;color:#333;font-size:15px">(주)쓰리포인트덴탈은 15년 이상 디지털 덴티스트리의 경험을 기반으로 정밀하고 일관된 품질의 보철물을 제작하는 프리미엄 디지털 밀링 센터입니다.</p>
      <div style="background:#f9fbff;border:1px solid #e6edf8;padding:12px 14px;font-size:15px;line-height:1.8;color:#0f3060;display:flex;align-items:center;justify-content:space-between;gap:16px">
        <div>
          <div style="margin-bottom:6px"><strong>기공소명</strong> : ㈜쓰리포인트덴탈</div>
          <div style="margin-bottom:6px"><strong>주소</strong> : 대전 유성구 테크노8로 44 2동 쓰리포인트덴탈</div>
          <div><strong>제품 문의</strong> : 1855-2804, E-MAIL : order@3pointdental.com</div>
        </div>
        <a href="https://kr.3pointdental.com/?redirect=no" class="btn btn-secondary price-download-btn" style="min-width:200px;min-height:60px;display:inline-block;text-align:center;border:2px solid #2a7f62;color:#2a7f62;background:#fff;padding:11px 22px 32px 22px;font-size:15px;line-height:2.35">홈페이지 바로가기</a>
      </div>
    </div>
  </div>
</div>
<?php
?>
