<?php
// 공통 파일 인클루드
include_once('../../common.php');
include_once('../../config.php');
include_once('./config.php');
include_once(G5_PATH.'/head.sub.php');

// 데이터베이스 연결
$db = get_partner_lab_db_connection();

// 로그인 체크
if (!$is_member) {
    alert('로그인이 필요합니다.', G5_BBS_URL.'/login.php');
}

// 테이블 구조 확인 (기본키 찾기)
$orders_columns = array();
$desc_orders_res = @mysqli_query($db, "DESCRIBE partner_lab_orders");
if ($desc_orders_res) {
    while ($r = mysqli_fetch_assoc($desc_orders_res)) {
        $orders_columns[] = $r;
    }
}

// 기본키 컬럼 찾기
$orders_pk_col = 'order_id';
foreach ($orders_columns as $col) {
    if (isset($col['Key']) && $col['Key'] === 'PRI') {
        $orders_pk_col = $col['Field'];
        break;
    }
}

// 주문 ID 확인
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';
if (!$order_id) {
    alert('잘못된 접근입니다.');
}

// 주문 정보 조회 (동적 기본키 컬럼 사용)
$order_id_esc = mysqli_real_escape_string($db, $order_id);
$order_sql = "SELECT * FROM partner_lab_orders WHERE `" . $orders_pk_col . "` = '" . $order_id_esc . "'";

$order_res = mysqli_query($db, $order_sql);
$order = $order_res ? mysqli_fetch_assoc($order_res) : null;

if (!$order) {
    alert('주문을 찾을 수 없습니다.');
}

// 상태 색상 반환 함수
function get_status_color($status) {
    $colors = array(
        'pending' => '#ffc107',
        'confirmed' => '#17a2b8',
        'processing' => '#007bff',
        'shipping' => '#ff7f50',
        'completed' => '#28a745',
        'cancelled' => '#dc3545'
    );
    return isset($colors[$status]) ? $colors[$status] : '#6c757d';
}

// 외래키 컬럼 찾기
$teeth_fk_col = 'order_id';
$files_fk_col = 'order_id';

foreach ($orders_columns as $col) {
    if ($col['Field'] === 'order_id') {
        $teeth_fk_col = 'order_id';
        $files_fk_col = 'order_id';
        break;
    }
}

// 주문 상세 정보 조회 (치아 정보)
$teeth_sql = "SELECT * FROM partner_lab_order_teeth WHERE `" . $teeth_fk_col . "` = '" . $order_id_esc . "' ORDER BY tooth_number";
$teeth_res = mysqli_query($db, $teeth_sql);
$teeth = array();
if ($teeth_res) {
    while ($row = mysqli_fetch_assoc($teeth_res)) {
        $teeth[] = $row;
    }
}

// 첨부 파일 조회
$files_sql = "SELECT * FROM partner_lab_order_files WHERE `" . $files_fk_col . "` = '" . $order_id_esc . "' ORDER BY file_id";
$files_res = mysqli_query($db, $files_sql);
$files = array();
if ($files_res) {
    while ($row = mysqli_fetch_assoc($files_res)) {
        $files[] = $row;
    }
}

// 상태 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = isset($_POST['order_status']) ? $_POST['order_status'] : '';
    $status_note = isset($_POST['status_note']) ? $_POST['status_note'] : '';
    
    if ($new_status && in_array($new_status, array('pending', 'confirmed', 'processing', 'done'))) {
        $new_status_esc = mysqli_real_escape_string($db, $new_status);
        $status_note_esc = mysqli_real_escape_string($db, $status_note);
        $mb_id_esc = isset($member['mb_id']) ? mysqli_real_escape_string($db, $member['mb_id']) : '';
        
        // 주문 상태 업데이트 (동적 기본키 컬럼 사용)
        $update_sql = "UPDATE partner_lab_orders SET order_status = '" . $new_status_esc . "', updated_at = NOW() WHERE `" . $orders_pk_col . "` = '" . $order_id_esc . "'";
        if (mysqli_query($db, $update_sql)) {
            // 상태 변경 이력 기록 (동적 기본키 컬럼 사용)
            if ($status_note) {
                $history_sql = "INSERT INTO partner_lab_order_status_history (`" . $orders_pk_col . "`, status, note, created_by, created_at) VALUES ('" . $order_id_esc . "', '" . $new_status_esc . "', '" . $status_note_esc . "', '" . $mb_id_esc . "', NOW())";
                @mysqli_query($db, $history_sql);
            }
            
            // 주문 정보 새로고침 (동적 기본키 컬럼 사용)
            $refresh_sql = "SELECT * FROM partner_lab_orders WHERE `" . $orders_pk_col . "` = '" . $order_id_esc . "'";
            $refresh_res = mysqli_query($db, $refresh_sql);
            if ($refresh_res) {
                $order = mysqli_fetch_assoc($refresh_res);
            }
            
            echo '<script>alert("상태가 변경되었습니다.");</script>';
        } else {
            echo '<script>alert("상태 변경 중 오류가 발생했습니다.");</script>';
        }
    }
}

// 동적으로 테이블 구조 확인
function get_table_structure($db, $table_name) {
    $table_name_esc = mysqli_real_escape_string($db, $table_name);
    $sql = "SHOW COLUMNS FROM `$table_name_esc`";
    $res = mysqli_query($db, $sql);
    $columns = array();
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $columns[] = $row;
        }
    }
    return $columns;
}

// 테이블 구조 확인
$orders_columns = get_table_structure($db, 'partner_lab_orders');
$teeth_columns = get_table_structure($db, 'partner_lab_order_teeth');
$files_columns = get_table_structure($db, 'partner_lab_order_files');

// 기본키 찾기
function find_primary_key($columns) {
    foreach ($columns as $col) {
        if ($col['Key'] === 'PRI') {
            return $col['Field'];
        }
    }
    return 'id';
}

$orders_pk = find_primary_key($orders_columns);
$teeth_pk = find_primary_key($teeth_columns);
$files_pk = find_primary_key($files_columns);

// 외래키 찾기 (order_id)
function find_order_id_column($columns) {
    foreach ($columns as $col) {
        if ($col['Field'] === 'order_id') {
            return 'order_id';
        }
    }
    return null;
}

$teeth_order_fk = find_order_id_column($teeth_columns);
$files_order_fk = find_order_id_column($files_columns);

// teeth_configurations JSON 파싱
$teeth_configurations = array();
if (isset($order['teeth_configurations']) && !empty($order['teeth_configurations'])) {
    $teeth_configurations = json_decode($order['teeth_configurations'], true);
    if (!is_array($teeth_configurations)) {
        $teeth_configurations = array();
    }
}

// 페이지 타이틀 설정
$g5['title'] = '주문 상세보기 - ' . htmlspecialchars($order['order_number']);
?>

<script>
// index와 동일하게 body에 페이지 전용 클래스를 추가하여 폰트/타이포그래피 적용
document.addEventListener('DOMContentLoaded', function(){
  document.body.classList.add('partner-lab-order-page');
  document.body.classList.add('hydrated');
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function downloadPDF(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('p','pt','a4');
  const node = document.querySelector('.letter-container');
  const margin = 24;
  html2canvas(node,{scale:2,background:'#ffffff'}).then(function(canvas){
    const imgData = canvas.toDataURL('image/png');
    const pageWidth = doc.internal.pageSize.getWidth() - margin*2;
    const pageHeight = doc.internal.pageSize.getHeight() - margin*2;
    const imgWidth = pageWidth;
    const imgHeight = canvas.height * (imgWidth / canvas.width);
    let heightLeft = imgHeight;
    doc.addImage(imgData,'PNG',margin,margin,imgWidth,imgHeight);
    heightLeft -= pageHeight;
    while (heightLeft > 0) {
      doc.addPage();
      const position = margin - (imgHeight - heightLeft);
      doc.addImage(imgData,'PNG',margin,position,imgWidth,imgHeight);
      heightLeft -= pageHeight;
    }
    doc.save('order-detail.pdf');
  });
}
</script>
<style>
.grid-title{font-size:16px;font-weight:700;margin:0 0 8px 0}
.grid-section{margin-top:16px}
.grid-table{width:100%;border-collapse:collapse;border:1px solid #d0d7de;border-radius:6px;overflow:hidden}
.grid-table th{background:#f6f8fa;color:#0f3060;font-weight:600;padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
.grid-table td{padding:10px;border-bottom:1px solid #e5e7eb}
.grid-table tr:nth-child(even){background:#fbfdff}
</style>
<style>
.grid-table th, .grid-table--compact th { font-size: inherit !important; line-height: 1.2 !important; padding: 4px 6px !important; width: auto !important; white-space: nowrap; vertical-align: middle; }
.table th { font-size: inherit !important; line-height: 1.2 !important; padding: 6px !important; }
.grid-chip { padding: 2px 6px !important; line-height: 1.1 !important; font-size: inherit !important; }
/* Reduce header column width by half (compact two-column tables) */
.grid-table--compact { table-layout: fixed !important; }
.grid-table--compact th { width: 20% !important; }
.grid-table--compact td { width: 80% !important; }
/* Center align buttons and table text; remove compact header area */
.pl-btn { text-align: center !important; }
.grid-table th, .grid-table td, .table th, .table td { text-align: center !important; }
.grid-table--compact th { display: table-cell !important; }
.grid-chip { display: inline-block !important; }
</style>
<style>
/* Edge shape overrides: remove all border radii */
.block,
.block h2,
.table,
.table th,
.table td,
.file-list li,
.notes,
.tooth-summary,
.unified-tooth-chart .utc-tooth,
.status-badge,
.tooth-pill,
.tooth-pill .type,
.grid-table,
.grid-chip,
.pl-btn,
.pl-input { border-radius: 0 !important; }
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
.pl-btn { font-size:15px; padding:10px 16px; border:2px solid #2a7f62; border-radius:8px; color:#2a7f62; text-decoration:none; font-weight:600; background:#fff; display:inline-block; transition: all .15s ease; }
.pl-btn:hover { background:#2a7f62; color:#fff; box-shadow:0 4px 12px rgba(42,127,98,.2); }
.pl-btn--current { background:#2a7f62; color:#fff; border-color:#2a7f62; }
.pl-btn--disabled { opacity:.6; pointer-events:none; }
.letter-container { max-width:1200px; margin:30px auto 60px; padding:0 16px; }
</style>

<script>
function updateOrderStatus() {
    // 상태 변경 사유 입력받기
    const statusNote = document.getElementById('status_note').value;
    const newStatus = document.getElementById('order_status').value;
    
    if (!confirm('주문 상태를 변경하시겠습니까?')) {
        return;
    }
    
    fetch('update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: '<?php echo $order_id; ?>',
            status: newStatus,
            note: statusNote,
            csrf_token: '<?php echo generate_csrf_token(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('주문 상태가 변경되었습니다.');
            location.reload();
        } else {
            alert('오류가 발생했습니다: ' + data.message);
        }
    })
    .catch(error => {
        alert('네트워크 오류가 발생했습니다. 인터넷 연결을 확인해주세요.');
    });
}
</script>

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

<div class="letter-container">
  <div class="letter-head">
    <h1 class="letter-title">주문 확인서 (Confirmation Letter)</h1>
    <div class="letter-meta">
      <div><strong>주문번호</strong> <span><?php echo htmlspecialchars($order['order_number']); ?></span></div>
      <div><strong>작성일</strong> <span><?php echo htmlspecialchars(substr($order['created_at'],0,16)); ?></span></div>
      <div><strong>상태</strong> <span class="status-badge <?php echo $order['order_status']==='confirmed'?'ok':'pending'; ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></div>
    </div>
  </div>

  <div class="letter-body">
    <section class="block two-col">
      <div>
        <h2 class="grid-title">고객정보</h2>
        <table class="grid-table grid-table--compact"><tbody>
          <tr><th><span class="grid-chip">이름</span></th><td><?php echo htmlspecialchars($order['customer_name']); ?></td></tr>
          <tr><th><span class="grid-chip">연락처</span></th><td><?php echo htmlspecialchars($order['customer_phone']); ?></td></tr>
          <?php if (!empty($order['customer_email'])) { ?>
          <tr><th><span class="grid-chip">이메일</span></th><td><?php echo htmlspecialchars($order['customer_email']); ?></td></tr>
          <?php } ?>
        </tbody></table>
      </div>
      <div>
        <h2 class="grid-title">배송정보</h2>
        <table class="grid-table grid-table--compact"><tbody>
          <tr><th><span class="grid-chip">이름</span></th><td><?php echo htmlspecialchars($order['shipping_name']); ?></td></tr>
          <tr><th><span class="grid-chip">연락처</span></th><td><?php echo htmlspecialchars($order['shipping_phone']); ?></td></tr>
          <tr><th><span class="grid-chip">주소</span></th><td>(<?php echo htmlspecialchars($order['shipping_postcode']); ?>) <?php echo htmlspecialchars($order['shipping_address']); ?> <?php echo htmlspecialchars($order['shipping_detail']); ?></td></tr>
        </tbody></table>
      </div>
    </section>

    <section class="block two-col">
      <div>
        <h2 class="grid-title">환자정보</h2>
        <table class="grid-table grid-table--compact"><tbody>
          <tr><th><span class="grid-chip">환자명</span></th><td><?php echo htmlspecialchars($order['patient_name']); ?></td></tr>
          <tr><th><span class="grid-chip">환자 나이</span></th><td><?php echo htmlspecialchars($order['patient_birth']); ?></td></tr>
          <tr><th><span class="grid-chip">성별</span></th><td><?php echo htmlspecialchars($order['patient_gender']); ?></td></tr>
          <tr><th><span class="grid-chip">납기 희망일</span></th><td><?php echo htmlspecialchars(isset($order['delivery_date']) ? substr($order['delivery_date'],0,10) : ''); ?></td></tr>
        </tbody></table>
      </div>
      <div>
        <h2 class="grid-title">작업/발송 정보</h2>
        <table class="grid-table grid-table--compact"><tbody>
          <?php $method_label = ''; if (isset($order['delivery_method'])) { $dm = strtolower(trim($order['delivery_method'])); $method_label = ($dm==='pickup' ? '택배 픽업 신청' : ($dm==='delivery' ? '배송 진행' : '')); } if ($method_label==='') { if (!empty($order['rubber_impression_delivery']) || !empty($order['delivery_hope_date']) || !empty($order['delivery_address']) || !empty($order['delivery_detail_address'])) { $method_label = '택배 픽업 신청'; } else { $method_label = '배송 진행'; } } ?>
          <tr><th><span class="grid-chip">수령/배송</span></th><td><?php echo htmlspecialchars($method_label); ?></td></tr>
          <?php $pickup_checked = (!empty($order['rubber_impression_delivery']) && ($order['rubber_impression_delivery']===1 || $order['rubber_impression_delivery']==='1' || $order['rubber_impression_delivery']==='on')) ? '신청함' : '선택하지 않았음'; ?>
          <tr><th><span class="grid-chip">택배 픽업 신청</span></th><td><?php echo htmlspecialchars($pickup_checked); ?></td></tr>
          <?php if (!empty($order['delivery_hope_date'])) { ?><tr><th><span class="grid-chip">픽업 희망일</span></th><td><?php echo htmlspecialchars($order['delivery_hope_date']); ?></td></tr><?php } ?>
          <?php if (!empty($order['delivery_address']) || !empty($order['delivery_detail_address'])) { ?><tr><th><span class="grid-chip">픽업 주소</span></th><td><?php echo htmlspecialchars((isset($order['delivery_address'])?$order['delivery_address']:'') . ' ' . (isset($order['delivery_detail_address'])?$order['delivery_detail_address']:'')); ?></td></tr><?php } ?>
          <tr><th><span class="grid-chip">기공소 주소</span></th><td>대전 유성구 테크노8로 44 2동 쓰리포인트덴탈<br>연락처: 1855-2804</td></tr>
        </tbody></table>
      </div>
    </section>

    <section class="block">
      <h2>선택 치아</h2>
      <?php
        // 주문 페이지처럼 간단 텍스트 요약: Upper / Lower 구분
        $upper_order = array(18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28);
        $lower_order = array(48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38);
        $upper_sel = array();
        $lower_sel = array();
        if ($teeth && count($teeth)) {
            foreach($teeth as $t){
                $n = (int)$t['tooth_number'];
                if (in_array($n, $upper_order, true)) $upper_sel[] = $n;
                elseif (in_array($n, $lower_order, true)) $lower_sel[] = $n;
            }
        }
        $upper_txt = count($upper_sel) ? implode(', ', $upper_sel) : '-';
        $lower_txt = count($lower_sel) ? implode(', ', $lower_sel) : '-';
      ?>
      
      <?php
        // 선택 치아 번호 배열 생성
        $selected_teeth_nums = array();
        if ($teeth && count($teeth)) {
            foreach ($teeth as $t) {
                $selected_teeth_nums[] = isset($t['tooth_number']) ? (int)$t['tooth_number'] : 0;
            }
        }
        // 폴백: 주문 테이블에 저장된 selected_teeth JSON에서 복원
        if (empty($selected_teeth_nums) && isset($order['selected_teeth']) && !empty($order['selected_teeth'])) {
            $sel = json_decode($order['selected_teeth'], true);
            if (is_array($sel)) {
                foreach ($sel as $v) { if (is_numeric($v)) $selected_teeth_nums[] = (int)$v; }
            } elseif (is_string($order['selected_teeth'])) {
                // 콤마로 구분된 문자열 등도 처리
                $parts = preg_split('/\s*,\s*/', $order['selected_teeth']);
                foreach ($parts as $p) { if (is_numeric($p)) $selected_teeth_nums[] = (int)$p; }
            }
        }
        // 폴백: 주문 스냅샷(auto_save_data)에서 복원
        if (empty($selected_teeth_nums) && isset($order['auto_save_data']) && !empty($order['auto_save_data'])) {
            $snap = json_decode($order['auto_save_data'], true);
            if (is_array($snap)) {
                if (isset($snap['selected_teeth']) && is_array($snap['selected_teeth'])) {
                    foreach ($snap['selected_teeth'] as $v) { if (is_numeric($v)) $selected_teeth_nums[] = (int)$v; }
                }
                // tooth_options 키에서 치아 번호 유추
                if (empty($selected_teeth_nums)) {
                    foreach ($snap as $k => $v) {
                        if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[/', $k, $m)) {
                            $selected_teeth_nums[] = (int)$m[1];
                        }
                    }
                }
            }
        }
        // 중복/0 제거 및 정렬 (초기 정리)
        $__tmp_sel = array();
        if (is_array($selected_teeth_nums)) {
            foreach ($selected_teeth_nums as $__n) {
                $__n = (int)$__n;
                if ($__n > 0) { $__tmp_sel[] = $__n; }
            }
        }
        $selected_teeth_nums = array_values(array_unique($__tmp_sel));
        sort($selected_teeth_nums);
        
        // 치아 옵션 맵 구성 (partner_lab_orders.teeth_configurations JSON 기반)
        $tooth_options_map = array();
        if (isset($order['teeth_configurations']) && !empty($order['teeth_configurations'])) {
            $cfg = json_decode($order['teeth_configurations'], true);
            if (is_array($cfg)) {
                foreach ($cfg as $k => $v) {
                    if (preg_match('/^tooth_options\[(\d+)\]\[(.+)\]$/', $k, $m)) {
                        $tn = (int)$m[1];
                        $optk = $m[2];
                        if (!isset($tooth_options_map[$tn])) $tooth_options_map[$tn] = array();
                        $tooth_options_map[$tn][$optk] = $v;
                    }
                }
                // 배열형 구조도 지원: { tooth_options: { 14: {system:..., ...}, ... } }
                if (isset($cfg['tooth_options']) && is_array($cfg['tooth_options'])) {
                    foreach ($cfg['tooth_options'] as $tn => $opts) {
                        if (!isset($tooth_options_map[$tn])) $tooth_options_map[$tn] = array();
                        if (is_array($opts)) {
                            foreach ($opts as $optk => $optv) { $tooth_options_map[$tn][$optk] = $optv; }
                        }
                    }
                }
                // 숫자 키 기반 구조도 지원: { "14": {system:..., ...}, ... }
                if (empty($tooth_options_map)) {
                    $allNumericKeys = true;
                    foreach ($cfg as $k => $v) { if (!is_numeric($k)) { $allNumericKeys = false; break; } }
                    if ($allNumericKeys) {
                        foreach ($cfg as $tn => $opts) {
                            if (!isset($tooth_options_map[$tn])) $tooth_options_map[$tn] = array();
                            if (is_array($opts)) {
                                foreach ($opts as $optk => $optv) { $tooth_options_map[$tn][$optk] = $optv; }
                            }
                        }
                    }
                }
            }
        }
        // 2차 폴백: auto_save_data에서 옵션 재구성 (주문 스냅샷)
        if (empty($tooth_options_map) && isset($order['auto_save_data']) && !empty($order['auto_save_data'])) {
            $snap = json_decode($order['auto_save_data'], true);
            if (is_array($snap)) {
                foreach ($snap as $k => $v) {
                    if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[(.+)\]$/', $k, $m)) {
                        $tn = (int)$m[1];
                        $optk = $m[2];
                        if (!isset($tooth_options_map[$tn])) $tooth_options_map[$tn] = array();
                        $tooth_options_map[$tn][$optk] = $v;
                    }
                }
                // 배열 형태로 들어온 경우도 처리
                if (isset($snap['tooth_options']) && is_array($snap['tooth_options'])) {
                    foreach ($snap['tooth_options'] as $tn => $opts) {
                        if (!isset($tooth_options_map[$tn])) $tooth_options_map[$tn] = array();
                        foreach ($opts as $optk => $optv) { $tooth_options_map[$tn][$optk] = $optv; }
                    }
                }
            }
        }
        // 보강: 정규화 테이블(partner_lab_order_teeth_details)에서 옵션을 조회해 기존 맵에 병합 (라이브 PHP 5 호환)
        // 기존 맵이 비어있는 경우에는 새로 구성하고, 비어있지 않더라도 누락된 필드를 채움
        {
            try {
                if ($conn instanceof PDO) {
                    $ds = $conn->prepare("SELECT tooth_number, system_spec, margin_level, final_prosthetic, special_notes FROM partner_lab_order_teeth_details WHERE order_id = ?");
                    $ds->execute(array($order_id));
                    $drows = $ds->fetchAll(PDO::FETCH_ASSOC);
                } elseif (is_object($conn) && (get_class($conn) === 'mysqli')) {
                    $stmt = mysqli_prepare($conn, "SELECT tooth_number, system_spec, margin_level, final_prosthetic, special_notes FROM partner_lab_order_teeth_details WHERE order_id = ?");
                    $drows = array();
                    if ($stmt) {
                        $type = is_int($order_id) ? 'i' : 's';
                        mysqli_stmt_bind_param($stmt, $type, $order_id);
                        mysqli_stmt_execute($stmt);
                        $meta = mysqli_stmt_result_metadata($stmt);
                        $fields = array(); $bind = array(); $bindArgs = array($stmt);
                        if ($meta) {
                            while ($f = mysqli_fetch_field($meta)) { $fields[] = $f->name; $bind[$f->name] = null; $bindArgs[] = &$bind[$f->name]; }
                            call_user_func_array('mysqli_stmt_bind_result', $bindArgs);
                            while (mysqli_stmt_fetch($stmt)) { $row = array(); foreach ($fields as $name) { $row[$name] = $bind[$name]; } $drows[] = $row; }
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $drows = array();
                }
                foreach ($drows as $dr) {
                    $tn = isset($dr['tooth_number']) ? (int)$dr['tooth_number'] : 0;
                    if ($tn <= 0) continue;
                    if (!isset($tooth_options_map[$tn])) $tooth_options_map[$tn] = array();
                    // 시스템/마진/보철
                    if (!empty($dr['system_spec']) && (!isset($tooth_options_map[$tn]['system']) || $tooth_options_map[$tn]['system'] === '')) {
                        $tooth_options_map[$tn]['system'] = $dr['system_spec'];
                    }
                    if (!empty($dr['margin_level']) && (!isset($tooth_options_map[$tn]['margin']) || $tooth_options_map[$tn]['margin'] === '')) {
                        $tooth_options_map[$tn]['margin'] = $dr['margin_level'];
                    }
                    if (!empty($dr['final_prosthetic']) && (!isset($tooth_options_map[$tn]['prosthetic']) || $tooth_options_map[$tn]['prosthetic'] === '')) {
                        $tooth_options_map[$tn]['prosthetic'] = $dr['final_prosthetic'];
                    }
                    // 특이사항에서 shade / flags 파싱
                    $notes = isset($dr['special_notes']) ? (string)$dr['special_notes'] : '';
                    if ($notes !== '') {
                        // shade=VALUE
                        if (preg_match('/shade=([^;]+)/', $notes, $m)) {
                            if (!isset($tooth_options_map[$tn]['shade']) || $tooth_options_map[$tn]['shade'] === '') {
                                $tooth_options_map[$tn]['shade'] = trim($m[1]);
                            }
                        }
                        // flags=a,b,c
                        if (preg_match('/flags=([^;]+)/', $notes, $m2)) {
                            $flagStr = trim($m2[1]);
                            $flags = array_map('trim', explode(',', $flagStr));
                            foreach ($flags as $f) {
                                if (strcasecmp($f, 'PMAB') === 0) $tooth_options_map[$tn]['pmab'] = '1';
                                else if (strcasecmp($f, 'Screw') === 0) $tooth_options_map[$tn]['screw'] = '1';
                                else if (strcasecmp($f, 'Ano') === 0) $tooth_options_map[$tn]['anodizing'] = '1';
                                else if (strcasecmp($f, 'Non-Eng') === 0) $tooth_options_map[$tn]['non_engaging'] = '1';
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // 병합 실패 시에도 화면은 계속 표시
            }
        }
        // 최종 폴백: 옵션 맵 또는 상세 행에서 선택 치아 번호 유추 (맵/디테일 생성 이후 실행)
        if (empty($selected_teeth_nums)) {
            // 우선 옵션 맵 키에서 추출
            if (!empty($tooth_options_map)) {
                foreach (array_keys($tooth_options_map) as $tn) { if (is_numeric($tn)) $selected_teeth_nums[] = (int)$tn; }
            }
            // 옵션 맵이 비어있고 상세 행이 있다면 상세에서 추출
            if (empty($selected_teeth_nums) && isset($drows) && is_array($drows) && !empty($drows)) {
                foreach ($drows as $dr) { $tn = isset($dr['tooth_number']) ? (int)$dr['tooth_number'] : 0; if ($tn > 0) $selected_teeth_nums[] = $tn; }
            }
            // 최종 정리
            if (!empty($selected_teeth_nums)) { $selected_teeth_nums = array_values(array_unique($selected_teeth_nums)); sort($selected_teeth_nums); }
        }

        // 저장 폴백: teeth 테이블에는 데이터가 있는데 주문 JSON 필드가 비어 있으면 채워넣기
        try {
            $orderCols = function_exists('dbu_get_columns') ? dbu_get_columns($conn, 'partner_lab_orders') : array();
            $canSaveSel = isset($orderCols['selected_teeth']) && is_array($selected_teeth_nums) && count($selected_teeth_nums) > 0;
            $canSaveCfg = isset($orderCols['teeth_configurations']) && is_array($tooth_options_map) && count($tooth_options_map) > 0;
            $needsSaveSel = $canSaveSel && (!isset($order['selected_teeth']) || trim((string)$order['selected_teeth']) === '');
            $needsSaveCfg = $canSaveCfg && (!isset($order['teeth_configurations']) || trim((string)$order['teeth_configurations']) === '');
            if ($needsSaveSel || $needsSaveCfg) {
                $sets = array();
                $params = array();
                if ($needsSaveSel) {
                    $selJson = json_encode(array_values($selected_teeth_nums));
                    $sets[] = '`selected_teeth` = ?';
                    $params[] = $selJson;
                    $order['selected_teeth'] = $selJson; // 화면에서도 바로 반영
                }
                if ($needsSaveCfg) {
                    // index.php 편집 프리필 로직과 호환되도록 평탄화된 키 형태로 저장
                    // 예: { "tooth_options[14][system]": "...", "tooth_options[14][margin]": "..." }
                    $flatCfg = array();
                    // 모드/그룹은 teeth 테이블의 tooth_type을 참고해 모드만 보조 반영
                    $typeMap = array();
                    if (is_array($teeth)) {
                        foreach ($teeth as $_t) {
                            $tn = isset($_t['tooth_number']) ? (int)$_t['tooth_number'] : 0;
                            $tt = isset($_t['tooth_type']) ? strtolower(trim($_t['tooth_type'])) : '';
                            if ($tn > 0 && $tt !== '') { $typeMap[$tn] = $tt; }
                        }
                    }
                    foreach ($tooth_options_map as $tn => $opts) {
                        if (!is_numeric($tn)) continue;
                        $tn = (int)$tn;
                        foreach ($opts as $optk => $optv) {
                            $flatCfg['tooth_options['.$tn.']['.$optk.']'] = $optv;
                        }
                        // 보조 모드 반영: single -> general, bridge 유지
                        if (isset($typeMap[$tn]) && $typeMap[$tn] !== '') {
                            $mode = ($typeMap[$tn] === 'single') ? 'general' : $typeMap[$tn];
                            $flatCfg['tooth_options['.$tn.'][mode]'] = $mode;
                        }
                    }
                    $cfgJson = json_encode($flatCfg);
                    $sets[] = '`teeth_configurations` = ?';
                    $params[] = $cfgJson;
                    $order['teeth_configurations'] = $cfgJson;
                }
                if (!empty($sets)) {
                    $sql = 'UPDATE `partner_lab_orders` SET ' . implode(', ', $sets) . ' WHERE `'.$orders_pk.'` = ?';
                    // 파라미터 마지막에 주문 PK 추가
                    $params[] = $order_id;
                    if ($conn instanceof PDO) {
                        $st = $conn->prepare($sql);
                        $st->execute($params);
                    } elseif (is_object($conn) && (get_class($conn) === 'mysqli')) {
                        $types = '';
                        // 앞의 모든 값은 문자열(JSON)
                        for ($i = 0; $i < count($params) - 1; $i++) { $types .= 's'; }
                        // 마지막은 PK 타입
                        $types .= is_int($order_id) ? 'i' : 's';
                        $st = mysqli_prepare($conn, $sql);
                        if ($st) {
                            // mysqli_stmt_bind_param은 참조가 필요
                            $bindArgs = array($st, $types);
                            foreach ($params as $idx => $val) { $bindArgs[] = &$params[$idx]; }
                            call_user_func_array('mysqli_stmt_bind_param', $bindArgs);
                            mysqli_stmt_execute($st);
                            mysqli_stmt_close($st);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // 저장 실패는 무시하고 화면 표시 계속
        }
      ?>

      <div class="unified-tooth-chart">
        <div class="utc-row">
          <?php foreach ($upper_order as $n): $is_sel = in_array($n, $selected_teeth_nums, true); ?>
            <div class="utc-tooth<?php echo $is_sel ? ' utc-sel' : ''; ?>" title="<?php echo $n; ?>">
              <span class="utc-num"><?php echo $n; ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="utc-row">
          <?php foreach ($lower_order as $n): $is_sel = in_array($n, $selected_teeth_nums, true); ?>
            <div class="utc-tooth<?php echo $is_sel ? ' utc-sel' : ''; ?>" title="<?php echo $n; ?>">
              <span class="utc-num"><?php echo $n; ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="tooth-pills">
        <?php if ($teeth && count($teeth)) { ?>
          <?php foreach ($teeth as $t) { 
              $num = isset($t['tooth_number']) ? (int)$t['tooth_number'] : 0;
              $raw_src = isset($t['tooth_type']) ? $t['tooth_type'] : '';
              $raw = strtolower(trim($raw_src));
              $type_label = ($raw==='single') ? '싱글' : (($raw==='bridge') ? '브릿지' : (isset($t['tooth_type']) ? $t['tooth_type'] : ''));
              $opts = isset($tooth_options_map[$num]) ? $tooth_options_map[$num] : array();
            $sys = isset($opts['system_other']) && $opts['system_other'] ? $opts['system_other'] : (isset($opts['system']) ? $opts['system'] : ''); if ($sys==='0') { $sys=''; }
              $margin = isset($opts['margin']) ? $opts['margin'] : '';
              $pros = isset($opts['prosthetic']) ? $opts['prosthetic'] : '';
              $shade = isset($opts['shade']) ? $opts['shade'] : '';
              $flags = array();
              foreach (array('non_engaging'=>'Non-Eng', 'anodizing'=>'Ano', 'pmab'=>'PMAB', 'screw'=>'Screw') as $k=>$lbl) {
                  if (isset($opts[$k]) && ($opts[$k]==='on' || $opts[$k]==='1' || $opts[$k]===1)) { $flags[] = $lbl; }
              }
          ?>
            <div class="tooth-pill">
              <span class="num"><?php echo htmlspecialchars($num); ?></span>
              <?php if (!empty($type_label)) { ?><span class="type"><?php echo htmlspecialchars($type_label); ?></span><?php } ?>
              <?php if ($sys || $margin || $pros || $shade) { ?>
                <span class="opt"><?php echo htmlspecialchars(trim(($sys?($sys.' '):'').($margin?('/ '.$margin.' '):'').($pros?('/ '.$pros.' '):'').($shade?('/ '.$shade):''))); ?></span>
              <?php } ?>
              <?php if (!empty($flags)) { ?>
                <span class="flags"><?php echo htmlspecialchars(implode(', ', $flags)); ?></span>
              <?php } ?>
            </div>
          <?php } ?>
        <?php } elseif (!empty($selected_teeth_nums)) { ?>
          <?php foreach ($selected_teeth_nums as $num) { 
              $opts = isset($tooth_options_map[$num]) ? $tooth_options_map[$num] : array();
              $type_label = '';
              if (isset($opts['mode'])) {
                  $raw = strtolower(trim($opts['mode']));
                  $type_label = ($raw==='bridge') ? '브릿지' : (($raw==='general' || $raw==='single') ? '싱글' : $opts['mode']);
              }
              $sys = isset($opts['system_other']) && $opts['system_other'] ? $opts['system_other'] : (isset($opts['system']) ? $opts['system'] : ''); if ($sys==='0') { $sys=''; }
              $margin = isset($opts['margin']) ? $opts['margin'] : '';
              $pros = isset($opts['prosthetic']) ? $opts['prosthetic'] : '';
              $shade = isset($opts['shade']) ? $opts['shade'] : '';
              $flags = array();
              foreach (array('non_engaging'=>'Non-Eng', 'anodizing'=>'Ano', 'pmab'=>'PMAB', 'screw'=>'Screw') as $k=>$lbl) {
                  if (isset($opts[$k]) && ($opts[$k]==='on' || $opts[$k]==='1' || $opts[$k]===1)) { $flags[] = $lbl; }
              }
          ?>
            <div class="tooth-pill">
              <span class="num"><?php echo htmlspecialchars($num); ?></span>
              <?php if (!empty($type_label)) { ?><span class="type"><?php echo htmlspecialchars($type_label); ?></span><?php } ?>
              <?php if ($sys || $margin || $pros || $shade) { ?>
                <span class="opt"><?php echo htmlspecialchars(trim(($sys?($sys.' '):'').($margin?('/ '.$margin.' '):'').($pros?('/ '.$pros.' '):'').($shade?('/ '.$shade):''))); ?></span>
              <?php } ?>
              <?php if (!empty($flags)) { ?>
                <span class="flags"><?php echo htmlspecialchars(implode(', ', $flags)); ?></span>
              <?php } ?>
            </div>
          <?php } ?>
        <?php } else { ?>
          <p class="muted" style="margin-top:8px">선택된 치아 정보가 없습니다.</p>
        <?php } ?>
      </div>
      <!-- 선택 치아 옵션 상세 테이블 -->
      <div style="margin-top:14px">
        <h3 style="margin:0 0 8px 0;font-size:16px;color:#2a7f62">선택 치아 옵션 상세</h3>
        <?php if (!empty($selected_teeth_nums)) { ?>
        <table class="table grid-table" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="border:1px solid #e6edf8; padding:8px">No.</th>
              <th style="border:1px solid #e6edf8; padding:8px">임플란트 시스템</th>
              <th style="border:1px solid #e6edf8; padding:8px">마진레벨</th>
              <th style="border:1px solid #e6edf8; padding:8px">상부 보철</th>
              <th style="border:1px solid #e6edf8; padding:8px">쉐이드</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($selected_teeth_nums as $tn) { 
              $opts = isset($tooth_options_map[$tn]) ? $tooth_options_map[$tn] : array();
                $sys = (isset($opts['system_other']) && $opts['system_other']) ? $opts['system_other'] : (isset($opts['system']) ? $opts['system'] : ''); if ($sys==='0') { $sys=''; }
              $margin = isset($opts['margin']) ? $opts['margin'] : '';
              $pros = isset($opts['prosthetic']) ? $opts['prosthetic'] : '';
              $shade = isset($opts['shade']) ? $opts['shade'] : '';
              $flags = array();
              foreach (array('pmab'=>'정품 PMAB','screw'=>'정품 스크류','anodizing'=>'아노다이징','non_engaging'=>'Non-engaging') as $k=>$lbl) {
                if (isset($opts[$k]) && ($opts[$k]==='on' || $opts[$k]==='1' || $opts[$k]===1)) { $flags[] = $lbl; }
              }
            ?>
            <tr>
              <td style="border:1px solid #e6edf8; padding:8px; text-align:center; font-weight:600; color:#2a7f62"><?php echo htmlspecialchars($tn); ?></td>
              <td style="border:1px solid #e6edf8; padding:8px"><?php echo htmlspecialchars($sys); ?></td>
              <td style="border:1px solid #e6edf8; padding:8px"><?php echo htmlspecialchars($margin); ?></td>
              <td style="border:1px solid #e6edf8; padding:8px"><?php echo htmlspecialchars($pros); ?></td>
              <td style="border:1px solid #e6edf8; padding:8px"><?php echo htmlspecialchars($shade); ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
        <?php } else { ?>
          <p class="muted" style="margin-top:8px">치아 옵션 정보가 없습니다.</p>
        <?php } ?>
      </div>
    </section>

    <section class="block">
      <h2>기타 추가 옵션</h2>
      <?php
        // 주문 전역 추가 옵션 텍스트 우선 표시, 없으면 치아 옵션에서 플래그 집계
        $extra_txt = isset($order['additional_info']) ? trim($order['additional_info']) : '';
        if ($extra_txt === '') {
            $flagSet = array();
            foreach ($tooth_options_map as $tn => $opts) {
                foreach (array('pmab'=>'정품 PMAB 적용','screw'=>'정품 스크류 적용','anodizing'=>'아노다이징 적용','non_engaging'=>'Non-engaging 적용') as $k=>$lbl) {
                    if (isset($opts[$k]) && ($opts[$k]==='on' || $opts[$k]==='1' || $opts[$k]===1)) { $flagSet[$k] = $lbl; }
                }
            }
            if (!empty($flagSet)) { $extra_txt = implode(', ', array_values($flagSet)); }
        }
      ?>
      <table class="grid-table" style="width:100%"><tbody><tr><th>추가 옵션</th><td><?php echo $extra_txt !== '' ? nl2br(htmlspecialchars($extra_txt)) : '-'; ?></td></tr></tbody></table>
    </section>

    <section class="block">
      <h2>첨부 파일</h2>
      <?php if ($files && count($files)) { ?>
        <table class="grid-table" style="width:100%">
          <thead><tr><th>파일명</th><th>다운로드</th></tr></thead>
          <tbody>
            <?php foreach($files as $f){ ?>
            <tr>
              <td><?php $__nm = isset($f['original_name']) && $f['original_name'] ? $f['original_name'] : (isset($f['file_name']) ? $f['file_name'] : ''); if ((!$__nm || $__nm==='') && isset($f['file_path'])) { $__nm = basename($f['file_path']); } echo htmlspecialchars($__nm); ?></td>
              <td>
                <?php $fid = isset($f['file_id']) ? (int)$f['file_id'] : 0; $fpath = isset($f['file_path']) ? $f['file_path'] : ''; $fname = isset($f['original_name']) ? $f['original_name'] : (isset($f['file_name']) ? $f['file_name'] : ''); ?>
                <a class="pl-btn" href="../order/file_download.php?<?php echo $fid>0 ? ('file_id='.$fid) : ('path='.urlencode($fpath).'&name='.urlencode($fname)); ?>">다운로드</a>
              </td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } else { ?><p class="muted">첨부된 파일이 없습니다.</p><?php } ?>
    </section>

    <section class="block">
      <h2>특이사항</h2>
      <table class="grid-table" style="width:100%"><tbody><tr><th>특이사항</th><td><?php echo nl2br(htmlspecialchars($order['special_notes'])); ?></td></tr></tbody></table>
    </section>

    <section class="block">
      <h2>상태 변경 이력</h2>
      <?php
      // 상태 변경 이력 조회
      $history_sql = "SELECT * FROM order_status_history 
                      WHERE `order_id` = '" . $order_id_esc . "' 
                      ORDER BY changed_at DESC";
      $history_res = mysqli_query($db, $history_sql);
      $histories = array();
      if ($history_res) {
        while ($row = mysqli_fetch_assoc($history_res)) {
          $histories[] = $row;
        }
      }
      ?>
      
      <?php if (count($histories) > 0): ?>
        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #e6edf8; border-radius: 8px;">
          <table class="table grid-table" style="margin: 0;">
            <thead>
              <tr>
                <th style="background: #f8fafc; padding: 12px; border-bottom: 2px solid #e6edf8;">변경일시</th>
                <th style="background: #f8fafc; padding: 12px; border-bottom: 2px solid #e6edf8;">이전상태</th>
                <th style="background: #f8fafc; padding: 12px; border-bottom: 2px solid #e6edf8;">새상태</th>
                <th style="background: #f8fafc; padding: 12px; border-bottom: 2px solid #e6edf8;">변경자</th>
                <th style="background: #f8fafc; padding: 12px; border-bottom: 2px solid #e6edf8;">사유</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($histories as $index => $history): ?>
              <tr style="<?php echo $index % 2 === 0 ? 'background: #ffffff;' : 'background: #f8fafc;'; ?>">
                <td style="padding: 12px; border-bottom: 1px solid #eef2f7; font-size: 14px;">
                  <?php echo date('Y-m-d H:i', strtotime($history['changed_at'])); ?>
                </td>
                <td style="padding: 12px; border-bottom: 1px solid #eef2f7;">
                  <span class="status-badge" style="background-color: <?php echo get_status_color($history['previous_status']); ?>; padding: 4px 8px; font-size: 12px;">
                    <?php echo get_order_status_name($history['previous_status']); ?>
                  </span>
                </td>
                <td style="padding: 12px; border-bottom: 1px solid #eef2f7;">
                  <span class="status-badge" style="background-color: <?php echo get_status_color($history['new_status']); ?>; padding: 4px 8px; font-size: 12px;">
                    <?php echo get_order_status_name($history['new_status']); ?>
                  </span>
                </td>
                <td style="padding: 12px; border-bottom: 1px solid #eef2f7; font-size: 14px;">
                  <?php 
                  // Get user name from ID
                  $user_sql = "SELECT mb_name FROM g5_member WHERE mb_no = '" . $history['changed_by'] . "'";
                  $user_result = mysqli_query($db, $user_sql);
                  $changed_by_name = $history['changed_by']; // Default to ID
                  if ($user_result && mysqli_num_rows($user_result) > 0) {
                    $user_row = mysqli_fetch_assoc($user_result);
                    $changed_by_name = $user_row['mb_name'];
                  }
                  echo htmlspecialchars($changed_by_name); 
                  ?>
                </td>
                <td style="padding: 12px; border-bottom: 1px solid #eef2f7; font-size: 14px; max-width: 300px;">
                  <?php echo htmlspecialchars(!empty($history['note']) ? $history['note'] : '-'); ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 8px;">상태 변경 이력이 없습니다.</p>
      <?php endif; ?>
    </section>
  </div>

  <div class="letter-actions">
    <button class="pl-btn" type="button" onclick="downloadPDF()">PDF 다운로드</button>
    <a href="index.php" class="pl-btn">목록으로</a>
    <?php 
    // 관리자만 상태 변경 가능
    if ($is_admin) { ?>
    <div style="margin-left: auto;">
      <div style="display: inline-block; margin-right: 10px;">
        <select id="order_status" class="pl-input status-select" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
          <option value="pending" <?php echo $order['order_status']==='pending'?'selected':''; ?>>대기</option>
          <option value="confirmed" <?php echo $order['order_status']==='confirmed'?'selected':''; ?>>확인</option>
          <option value="processing" <?php echo $order['order_status']==='processing'?'selected':''; ?>>파트너 확인</option>
          <option value="done" <?php echo $order['order_status']==='done'?'selected':''; ?>>완료</option>
        </select>
        <input type="text" id="status_note" class="pl-input status-note" placeholder="상태 변경 사유" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; margin-left: 10px; min-width: 250px; font-size: 14px;">
        <button type="button" class="pl-btn pl-btn--current" onclick="updateOrderStatus()">상태 변경</button>
      </div>
    </div>
    <?php } ?>
  </div>
</div>

<style>
/* GNUBoard 기본 헤더/메뉴 숨김 (confirm 페이지에서 index와 동일한 상단만 노출) */
/* 기본 G5 헤더 요소는 상단 공통 헤더로 대체 */
#hd, #hd_wrap, #tnb, #snb, #gnb, .gnb, .lnb, .menu, .gnb_wrap, .hd, .top_menu, #hd_menu_all { display: none !important; }

/* index.php의 기본 타이포그래피/레이아웃 스코프 적용 */
body.partner-lab-order-page * { box-sizing: border-box; }
html, body { font-size: 17px !important; }
body.partner-lab-order-page {
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 17px;
    line-height: 1.6;
    color: #333;
    background-color: #f5f7fb;
}

.letter-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 17px;
    line-height: 1.6;
    color: #333;
}
.letter-container p,
.letter-container td,
.letter-container li { font-size: 17px; }

.letter-container{max-width:1200px;margin:30px auto 60px;padding:0 16px}
.letter-container * { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important; color:#333 !important; }
.letter-container p, .letter-container td, .letter-container li, .letter-container span { font-size:17px !important; line-height:1.6 !important; }
.letter-body h2 { font-size:18px; color:#2c3e50; margin-top:0; }
.letter-head{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e5e7eb;padding-bottom:12px;margin-bottom:18px}
.letter-title{margin:0;font-size:22px;color:#1f2937;font-weight:700}
.letter-meta{display:grid;grid-template-columns:repeat(3,auto);gap:12px}
.letter-meta strong{margin-right:6px;color:#444}
.status-badge{padding:2px 8px;border-radius:999px;border:1px solid #e5e7eb;font-size:12px}
.status-badge.ok{background:#dcfce7;border-color:#86efac;color:#14532d}
.status-badge.pending{background:#fff7ed;border-color:#fcd34d;color:#7c2d12}
.letter-body{display:flex;flex-direction:column;gap:14px}
.block{background:#fff;border:1px solid #e6edf8;border-radius:12px;padding:18px;box-shadow:0 6px 18px rgba(21,64,121,0.08)}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.table{width:100%;border-collapse:collapse;border:1px solid #e6edf8;border-radius:8px;overflow:hidden}
.table th{background:#eaf3ff;color:#0f3060;font-weight:700;padding:10px;border-bottom:1px solid #dce9fb}
.table td{border-bottom:1px solid #eef2f7;padding:10px;color:#28354a}
.file-list{list-style:none;margin:0;padding:0}
.file-list li{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #eee}
.file-list .name{font-weight:500}
.notes{white-space:pre-wrap;line-height:1.6}
.muted{color:#777}
.letter-actions{display:flex;gap:12px;align-items:center;margin-top:16px;flex-wrap:wrap}
/* 통일 버튼 스타일 사용 (pl-btn), 별도 .btn 스타일 제거 */
/* 필요 시 버튼 최소 너비를 조정하려면 아래를 사용하세요 */
.letter-actions .pl-btn { min-width: 140px; }
@media print{.letter-actions{display:none}.block{box-shadow:none;border-color:#ddd}.letter-container{margin:0}}
@media (max-width:640px){.two-col{grid-template-columns:1fr}.letter-meta{grid-template-columns:1fr;gap:6px}}

.pl-input { height: 38px; padding: 8px 12px; border: 1px solid #cfe5dc; border-radius: 8px; background:#fff; color:#0f3060; font-size:14px; outline: none; }
.pl-input:focus { border-color:#2a7f62; box-shadow: 0 0 0 3px rgba(42,127,98,.12); }
.status-select { min-width: 160px; }
.status-note { min-width: 260px; }

/* 박스/제목 구분 강화 */
.block { background:#fff; border:1px solid #cfe5dc; border-radius:12px; padding:18px; box-shadow:0 6px 18px rgba(21,64,121,0.08); }
.block h2 { margin-top:0; padding:8px 10px; border:1px solid #cfe5dc; border-radius:8px; background:#f4fbf8; color:#2a7f62; font-size:18px; }
.letter-body { gap:18px; }
.tooth-summary { display:grid; grid-template-columns: 1fr 1fr; gap:10px; padding:10px 12px; border:1px dashed #cfe5dc; border-radius:8px; background:#fbfefc; }
.tooth-summary strong { color:#2a7f62; }
/* 치아 배지(토큰) 목록 */
.tooth-pills { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
.tooth-pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border:1px solid #cfe5dc; border-radius:999px; background:#fbfefc; color:#1f2937; }
.tooth-pill .num { font-weight:700; color:#2a7f62; }
.tooth-pill .type { font-size:12px; padding:2px 6px; border-radius:999px; background:#e8f3ef; color:#2a7f62; border:1px solid #cfe5dc; }
.tooth-pill .opt { font-size:12px; color:#64748b; }
.tooth-pill .flags { font-size:11px; color:#475569; }

/* 읽기 전용 unified tooth chart (컴팩트) */
.unified-tooth-chart { display:grid; gap:8px; margin-top:10px; }
.unified-tooth-chart .utc-row { display:grid; grid-template-columns: repeat(16, 1fr); gap:4px; }
.unified-tooth-chart .utc-tooth { height:32px; border:1px solid #d6e4f6; border-radius:6px; background:#fff; display:flex; align-items:center; justify-content:center; font-size:12px; color:#64748b; }
.unified-tooth-chart .utc-tooth.utc-sel { border-color:#2a7f62; background:#f0fbf7; color:#2a7f62; font-weight:700; box-shadow: inset 0 0 0 1px rgba(42,127,98,.2); }
.unified-tooth-chart .utc-num { pointer-events:none; }

/* 표/파일/노트 가독성 향상 */
.table tr:nth-child(even) { background: #fafcff; }
.table td, .table th { padding: 12px; }
.file-list li { display:flex; justify-content:space-between; align-items:center; gap:12px; background:#f9fbff; border:1px solid #e6edf8; border-radius:8px; padding:10px 12px; margin-bottom:8px; }
.file-list .name { color:#0f3060; }
.file-list .size { color:#64748b; font-size: 14px; }
.notes { background:#f9fbff; border:1px solid #e6edf8; border-radius:8px; padding:12px; }

/* 반응형 개선 */
@media (max-width: 640px) {
  .letter-actions { flex-direction: column; }
  .letter-actions .pl-btn { width: 100%; }
}
@media (max-width: 480px) {
  .tooth-summary { grid-template-columns: 1fr; }
  .tooth-pills { gap:6px; }
  .unified-tooth-chart .utc-tooth { height:24px; font-size:11px; }
}
</style>

<?php
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
<script>
document.addEventListener('DOMContentLoaded', function(){ document.body.classList.add('partner-lab-order-page'); });
</script>
<style>
body.partner-lab-order-page { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
</style>
<!-- jQuery는 상단에서 1회 로드되므로 중복 로드 제거 -->
<script>
// jQuery 로드 확인 및 안전한 실행
(function() {
    function checkJQuery() {
        if (typeof jQuery === 'undefined') {
            setTimeout(checkJQuery, 50);
        } else {
            // jQuery가 로드되었으므로 $ 변수 설정
            window.$ = window.jQuery;
            
            // 여기에 jQuery-dependent 코드를 넣을 수 있습니다
            jQuery(document).ready(function($) {
                // jQuery 코드가 여기서 실행됩니다
            });
        }
    }
    checkJQuery();
})();
</script>
