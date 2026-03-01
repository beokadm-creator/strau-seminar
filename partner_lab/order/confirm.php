<?php
// 컴펌레터 형식 주문 확인서
// PHP 5.2 호환을 위해 __DIR__ 대신 dirname(__FILE__) 사용
include_once dirname(__FILE__).'/../_common.php';
include_once dirname(__FILE__).'/db_config.php';
include_once dirname(__FILE__).'/db_utils.php';
include_once G5_LIB_PATH.'/mailer.lib.php';

// jQuery가 반드시 필요하므로 최상단에서 CDN을 로드 (중복 로드 방지)
echo '<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>';
echo '<script>window.jQuery || document.write(\'<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"><\\/script>\')</script>';

if (isset($g5) && is_array($g5)) {
    $g5['title'] = '파트너 랩 주문 확인서';
    if (defined('G5_PATH') && file_exists(G5_PATH.'/head.php')) {
        include_once G5_PATH.'/head.php';
    }
}

// 로그인 체크: 비로그인 사용자는 접근 불가
if (isset($is_guest) ? $is_guest : (isset($is_member) ? !$is_member : true)) {
    $login_base = defined('G5_BBS_URL') ? G5_BBS_URL.'/login.php' : '/bbs/login.php';
    $return_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : './confirm.php';
    alert('로그인 후 이용 가능합니다.', $login_base.'?url='.urlencode($return_url));
    exit;
}

$conn = getDBConnection();
if (!$conn) {
    echo '<div style="padding:20px;color:#b00020">데이터베이스 연결 오류: 디렉터리 권한/경로를 확인하세요.</div>';
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0 && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
}
// 세션 또는 최근 주문 ID 폴백
if ($order_id <= 0 && isset($_SESSION['last_order_id'])) {
    $order_id = intval($_SESSION['last_order_id']);
}
if ($order_id <= 0) {
    echo '<div style="padding:20px;color:#b00020">주문 ID가 필요합니다.</div>';
    exit;
}

$orderPk = dbu_get_order_pk($conn);
if (!$orderPk) {
    echo '<div style="padding:20px;color:#b00020">주문 테이블의 기본키 컬럼을 확인할 수 없습니다.</div>';
    exit;
}
// 주문 조회 (PDO 또는 mysqli 모두 지원)
$order = null;
if ($conn instanceof PDO) {
    $stmt = $conn->prepare("SELECT * FROM partner_lab_orders WHERE `{$orderPk}` = ?");
    $stmt->execute(array($order_id));
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif (is_object($conn) && (get_class($conn) === 'mysqli')) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM partner_lab_orders WHERE `{$orderPk}` = ?");
    if ($stmt) {
        $type = is_int($order_id) ? 'i' : 's';
        mysqli_stmt_bind_param($stmt, $type, $order_id);
        mysqli_stmt_execute($stmt);
        $meta = mysqli_stmt_result_metadata($stmt);
        $fields = array();
        $bind = array();
        $bindArgs = array($stmt);
        if ($meta) {
            while ($f = mysqli_fetch_field($meta)) {
                $fields[] = $f->name;
                $bind[$f->name] = null;
                $bindArgs[] = &$bind[$f->name];
            }
            call_user_func_array('mysqli_stmt_bind_result', $bindArgs);
            if (mysqli_stmt_fetch($stmt)) {
                $row = array();
                foreach ($fields as $name) { $row[$name] = $bind[$name]; }
                $order = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
if (!$order) {
    echo '<div style="padding:20px;color:#b00020">해당 주문을 찾을 수 없습니다.</div>';
    exit;
}

// 정규화 상세 테이블이 없으면 생성 (라이브 서버 폴백)
try {
    if (function_exists('dbu_table_exists') && dbu_table_exists($conn, 'partner_lab_order_teeth_details') === false) {
        $createSql = "CREATE TABLE IF NOT EXISTS `partner_lab_order_teeth_details` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `order_id` INT NOT NULL,
            `tooth_number` INT NOT NULL,
            `tooth_position` VARCHAR(64) NULL,
            `is_selected` TINYINT(1) NULL,
            `system_spec` VARCHAR(255) NULL,
            `margin_level` VARCHAR(255) NULL,
            `final_prosthetic` VARCHAR(255) NULL,
            `special_notes` TEXT NULL,
            `created_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_order` (`order_id`),
            INDEX `idx_tooth` (`tooth_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        if ($conn instanceof PDO) {
            $conn->exec($createSql);
        } elseif (is_object($conn) && (get_class($conn) === 'mysqli')) {
            mysqli_query($conn, $createSql);
        }
    }
} catch (Exception $e) {
    // 테이블 생성 실패 시에도 페이지는 계속 표시
}

$fileDateCol = dbu_get_files_date_col($conn);
$orderColExists = dbu_get_columns($conn, 'partner_lab_order_files');
$filesSql = "SELECT * FROM partner_lab_order_files WHERE order_id = ?";
if ($fileDateCol) { $filesSql .= " ORDER BY `{$fileDateCol}` DESC"; }
// 파일 조회
$files = array();
if ($conn instanceof PDO) {
    $files_stmt = $conn->prepare($filesSql);
    $files_stmt->execute(array($order_id));
    $files = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (is_object($conn) && (get_class($conn) === 'mysqli')) {
    $stmt = mysqli_prepare($conn, $filesSql);
    if ($stmt) {
        $type = is_int($order_id) ? 'i' : 's';
        mysqli_stmt_bind_param($stmt, $type, $order_id);
        mysqli_stmt_execute($stmt);
        $meta = mysqli_stmt_result_metadata($stmt);
        $fields = array();
        $bind = array();
        $bindArgs = array($stmt);
        if ($meta) {
            while ($f = mysqli_fetch_field($meta)) {
                $fields[] = $f->name;
                $bind[$f->name] = null;
                $bindArgs[] = &$bind[$f->name];
            }
            call_user_func_array('mysqli_stmt_bind_result', $bindArgs);
            while (mysqli_stmt_fetch($stmt)) {
                $row = array();
                foreach ($fields as $name) { $row[$name] = $bind[$name]; }
                $files[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// 치아 조회
$teeth = array();
if ($conn instanceof PDO) {
    $teeth_stmt = $conn->prepare("SELECT * FROM partner_lab_order_teeth WHERE order_id = ? ORDER BY tooth_number ASC");
    $teeth_stmt->execute(array($order_id));
    $teeth = $teeth_stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (is_object($conn) && (get_class($conn) === 'mysqli')) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM partner_lab_order_teeth WHERE order_id = ? ORDER BY tooth_number ASC");
    if ($stmt) {
        $type = is_int($order_id) ? 'i' : 's';
        mysqli_stmt_bind_param($stmt, $type, $order_id);
        mysqli_stmt_execute($stmt);
        $meta = mysqli_stmt_result_metadata($stmt);
        $fields = array();
        $bind = array();
        $bindArgs = array($stmt);
        if ($meta) {
            while ($f = mysqli_fetch_field($meta)) {
                $fields[] = $f->name;
                $bind[$f->name] = null;
                $bindArgs[] = &$bind[$f->name];
            }
            call_user_func_array('mysqli_stmt_bind_result', $bindArgs);
            while (mysqli_stmt_fetch($stmt)) {
                $row = array();
                foreach ($fields as $name) { $row[$name] = $bind[$name]; }
                $teeth[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// 발주서 저장(확정)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'finalize') {
    try {
        dbu_begin($conn);
        // 상태 업데이트
        $ok1 = dbu_prepare_execute($conn, "UPDATE partner_lab_orders SET order_status = ?, updated_at = NOW() WHERE `{$orderPk}` = ?", array('confirmed', $order_id));
        // 로그 기록 (실패해도 진행)
        @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'order_confirmed', '컴펌레터 확인 및 발주서 저장', NOW())", array($order_id));
        dbu_commit($conn);
        $notifyEmail = '';
        if (defined('ORDER_NOTIFY_EMAIL')) { $notifyEmail = ORDER_NOTIFY_EMAIL; }
        else if (defined('PL_ORDER_NOTIFY_EMAIL')) { $notifyEmail = PL_ORDER_NOTIFY_EMAIL; }
        $base_url = defined('G5_URL') ? G5_URL : '';
        $pdf_path = PARTNER_LAB_DATA_PATH.'/pdf/order_'.(int)$order_id.'.pdf';
        $pdf_name = 'order_'.(int)$order_id.'.pdf';
        if (!is_dir(PARTNER_LAB_DATA_PATH.'/pdf')) { @mkdir(PARTNER_LAB_DATA_PATH.'/pdf', 0777, true); }
        if (!file_exists($pdf_path) || filesize($pdf_path) < 200) {
            $pdf_lines = array();
            $pdf_lines[] = '주문 확인서';
            $pdf_lines[] = '주문번호: '.(isset($order['order_number'])?$order['order_number']:$order_id);
            $pdf_lines[] = '작성일: '.(isset($order['created_at'])?substr($order['created_at'],0,16):'');
            $pdf_lines[] = '고객: '.(isset($order['customer_name'])?$order['customer_name']:'').' / '.(isset($order['customer_phone'])?$order['customer_phone']:'');
            $pdf_lines[] = '선택 치아 Upper: '.$upper_txt.' / Lower: '.$lower_txt;
            $objects = array();
            $catalog = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
            $pages = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
            $page = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n";
            $font = "4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
            $text = 'BT /F1 12 Tf 50 800 Td';
            foreach($pdf_lines as $line){
                $safe = str_replace(array('\\','(',')'), array('\\\\','\\(','\\)'), $line);
                $text .= ' 0 -14 Td (' . $safe . ') Tj';
            }
            $text .= ' ET';
            $stream_body = $text;
            $stream = "5 0 obj << /Length ".strlen($stream_body)." >> stream\n".$stream_body."\nendstream endobj\n";
            $objects[] = $catalog;
            $objects[] = $pages;
            $objects[] = $page;
            $objects[] = $font;
            $objects[] = $stream;
            $pdf = "%PDF-1.4\n";
            $offsets = array(0);
            for($i=0;$i<count($objects);$i++){
                $offsets[] = strlen($pdf);
                $pdf .= $objects[$i];
            }
            $xref = "xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
            for($i=1;$i<=count($objects);$i++){
                $xref .= sprintf("%010d 00000 n \n", $offsets[$i]);
            }
            $trailer = "trailer << /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n".strlen($pdf)."\n%%EOF";
            $pdf .= $xref.$trailer;
            @file_put_contents($pdf_path, $pdf);
        }
        $pdf_dl = $base_url . '/partner_lab/order/file_download.php?path=' . urlencode($pdf_path) . '&name=' . urlencode($pdf_name);
        // 파트너 확인 이메일 확인 토큰 생성 및 로그 저장
        $email_confirm_token = '';
        if (function_exists('openssl_random_pseudo_bytes')) {
            $email_confirm_token = bin2hex(openssl_random_pseudo_bytes(16));
        }
        if ($email_confirm_token === '') { $email_confirm_token = sha1(uniqid('pl_confirm_', true) . '|' . (string)$order_id); }
        @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'email_confirm_token', ?, NOW())", array($order_id, $email_confirm_token));
        $confirm_open_pixel = (defined('G5_URL') ? G5_URL : '') . '/partner_lab/partner_admin/email_confirm.php?order_id=' . (int)$order_id . '&token=' . urlencode($email_confirm_token) . '&mode=open';
        $zip_download_token = '';
        if (function_exists('openssl_random_pseudo_bytes')) { $zip_download_token = bin2hex(openssl_random_pseudo_bytes(16)); }
        if ($zip_download_token === '') { $zip_download_token = sha1(uniqid('pl_zip_', true) . '|' . (string)$order_id); }
        @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'zip_token', ?, NOW())", array($order_id, $zip_download_token));
        ob_start();
        echo '<div style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#333;line-height:1.6">';
        echo '<img src="' . htmlspecialchars($confirm_open_pixel) . '" alt="" style="display:none;width:1px;height:1px">';
        echo '<h2 style="margin:0 0 10px 0;color:#1f2937">주문 확인서</h2>';
        echo '<p><strong>주문번호:</strong> ' . htmlspecialchars(isset($order['order_number']) ? $order['order_number'] : $order_id) . '</p>';
        echo '<p><strong>작성일:</strong> ' . htmlspecialchars(substr(isset($order['created_at']) ? $order['created_at'] : '', 0, 16)) . '</p>';
        echo '<table style="width:100%;border-collapse:collapse;border:1px solid #e6edf8;margin:10px 0"><tbody>';
        echo '<tr><th style="text-align:left;border:1px solid #e6edf8;padding:8px;background:#f6f8fa">고객 이름</th><td style="border:1px solid #e6edf8;padding:8px">' . htmlspecialchars(isset($order['customer_name']) ? $order['customer_name'] : '') . '</td></tr>';
        echo '<tr><th style="text-align:left;border:1px solid #e6edf8;padding:8px;background:#f6f8fa">고객 연락처</th><td style="border:1px solid #e6edf8;padding:8px">' . htmlspecialchars(isset($order['customer_phone']) ? $order['customer_phone'] : '') . '</td></tr>';
        echo '<tr><th style="text-align:left;border:1px solid #e6edf8;padding:8px;background:#f6f8fa">배송지</th><td style="border:1px solid #e6edf8;padding:8px">(' . htmlspecialchars(isset($order['shipping_postcode']) ? $order['shipping_postcode'] : '') . ') ' . htmlspecialchars(isset($order['shipping_address']) ? $order['shipping_address'] : '') . ' ' . htmlspecialchars(isset($order['shipping_detail']) ? $order['shipping_detail'] : '') . '</td></tr>';
        echo '</tbody></table>';
        $upper_order = array(18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28);
        $lower_order = array(48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38);
        $selected_teeth_nums = array();
        if ($teeth && count($teeth)) {
            foreach ($teeth as $t) { $selected_teeth_nums[] = isset($t['tooth_number']) ? (int)$t['tooth_number'] : 0; }
        }
        if (empty($selected_teeth_nums) && isset($order['selected_teeth']) && !empty($order['selected_teeth'])) {
            $sel = json_decode($order['selected_teeth'], true);
            if (is_array($sel)) { foreach ($sel as $v) { if (is_numeric($v)) $selected_teeth_nums[] = (int)$v; } }
            else if (is_string($order['selected_teeth'])) { $parts = preg_split('/\s*,\s*/', $order['selected_teeth']); foreach ($parts as $p) { if (is_numeric($p)) $selected_teeth_nums[] = (int)$p; } }
        }
        if (empty($selected_teeth_nums) && isset($order['auto_save_data']) && !empty($order['auto_save_data'])) {
            $snap = json_decode($order['auto_save_data'], true);
            if (is_array($snap)) {
                if (isset($snap['selected_teeth']) && is_array($snap['selected_teeth'])) { foreach ($snap['selected_teeth'] as $v) { if (is_numeric($v)) $selected_teeth_nums[] = (int)$v; } }
                if (empty($selected_teeth_nums)) { foreach ($snap as $k => $v) { if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[/', $k, $m)) { $selected_teeth_nums[] = (int)$m[1]; } } }
            }
        }
        $tmp_sel = array();
        foreach ($selected_teeth_nums as $__n) { $__n = (int)$__n; if ($__n > 0) { $tmp_sel[] = $__n; } }
        $selected_teeth_nums = array_values(array_unique($tmp_sel)); sort($selected_teeth_nums);
        $upper_sel = array();
        $lower_sel = array();
        foreach ($selected_teeth_nums as $n) { if (in_array($n, $upper_order, true)) $upper_sel[] = $n; elseif (in_array($n, $lower_order, true)) $lower_sel[] = $n; }
        $upper_txt = count($upper_sel) ? implode(', ', $upper_sel) : '-';
        $lower_txt = count($lower_sel) ? implode(', ', $lower_sel) : '-';
        echo '<div style="border:1px dashed #cfe5dc;border-radius:8px;padding:8px;background:#fbfefc">';
        echo '<strong style="color:#2a7f62">Upper:</strong> ' . htmlspecialchars($upper_txt) . ' &nbsp; ';
        echo '<strong style="color:#2a7f62">Lower:</strong> ' . htmlspecialchars($lower_txt);
        echo '</div>';
        $patient_name = isset($order['patient_name']) ? $order['patient_name'] : '';
        $delivery_date = isset($order['delivery_date']) ? $order['delivery_date'] : (isset($order['dispatch_date']) ? $order['dispatch_date'] : '');
        $delivery_hope = isset($order['delivery_hope_date']) ? $order['delivery_hope_date'] : '';
        $method_label = '';
        if (isset($order['delivery_method'])) { $dm = strtolower(trim($order['delivery_method'])); $method_label = ($dm==='pickup' ? '택배 픽업 신청' : ($dm==='delivery' ? '배송 진행' : '')); }
        if ($method_label==='') { if (!empty($order['rubber_impression_delivery']) || !empty($order['delivery_hope_date']) || !empty($order['delivery_address']) || !empty($order['delivery_detail_address'])) { $method_label = '택배 픽업 신청'; } else { $method_label = '배송 진행'; } }
        $has_teeth = ($upper_txt !== '-' || $lower_txt !== '-');
        echo '<div style="margin-top:10px;border:1px solid #e6edf8;border-radius:8px;padding:10px;background:#f9fbff">';
        echo '<div><strong>환자명:</strong> ' . htmlspecialchars($patient_name) . '</div>';
        echo '<div><strong>작업/발송 정보</strong></div>';
        echo '<table style="width:100%;border-collapse:collapse;border:1px solid #e6edf8;margin:8px 0"><tbody>';
        echo '<tr><th style="text-align:left;border:1px solid #e6edf8;padding:8px;background:#f6f8fa;width:30%">수령/배송</th><td style="border:1px solid #e6edf8;padding:8px">' . htmlspecialchars($method_label) . '</td></tr>';
        $pickup_checked = (!empty($order['rubber_impression_delivery']) && ($order['rubber_impression_delivery']===1 || $order['rubber_impression_delivery']==='1' || $order['rubber_impression_delivery']==='on')) ? '신청함' : '선택하지 않았음';
        echo '<tr><th style="text-align:left;border:1px solid #e6edf8;padding:8px;background:#f6f8fa">택배 픽업 신청</th><td style="border:1px solid #e6edf8;padding:8px">' . htmlspecialchars($pickup_checked) . '</td></tr>';
        if ($delivery_hope) { echo '<tr><th style="text-align:left;border:1px solid #e6edf8;padding:8px;background:#f6f8fa">픽업 희망일</th><td style="border:1px solid #e6edf8;padding:8px">' . htmlspecialchars($delivery_hope) . '</td></tr>'; }
        echo '</tbody></table>';
        if (!$has_teeth) { echo '<div style="color:#6b7280">선택 치아가 없습니다</div>'; }
        echo '</div>';
        if (!empty($order['special_notes'])) {
            echo '<div style="margin-top:10px;border:1px solid #e6edf8;border-radius:8px;padding:10px;background:#f9fbff">' . nl2br(htmlspecialchars($order['special_notes'])) . '</div>';
        }
        if ($teeth && count($teeth)) {
            echo '<h3 style="margin:12px 0 6px 0;font-size:16px;color:#2a7f62">선택 치아 상세</h3>';
            echo '<table style="width:100%;border-collapse:collapse;border:1px solid #e6edf8"><thead><tr><th style="border:1px solid #e6edf8;padding:8px">No.</th><th style="border:1px solid #e6edf8;padding:8px">유형</th></tr></thead><tbody>';
            foreach($teeth as $t){
                $num = isset($t['tooth_number']) ? (int)$t['tooth_number'] : 0;
                $raw = isset($t['tooth_type']) ? strtolower(trim($t['tooth_type'])) : '';
                $type_label = ($raw==='single') ? '싱글' : (($raw==='bridge') ? '브릿지' : (isset($t['tooth_type']) ? $t['tooth_type'] : ''));
                echo '<tr><td style="border:1px solid #e6edf8;padding:8px;text-align:center">' . htmlspecialchars($num) . '</td><td style="border:1px solid #e6edf8;padding:8px">' . htmlspecialchars($type_label) . '</td></tr>';
            }
            echo '</tbody></table>';
        }
        echo '<p style="margin-top:12px">PDF 다운로드: <a href="' . htmlspecialchars($pdf_dl) . '" style="color:#2a7f62">주문 확인서 PDF 다운로드</a></p>';
        if ($files && count($files) > 1) {
            $zip_link = $base_url . '/partner_lab/order/download_zip.php?order_id=' . (int)$order_id . '&token=' . urlencode($zip_download_token);
            echo '<p style="margin-top:8px">전체 첨부 ZIP 다운로드: <a href="' . htmlspecialchars($zip_link) . '" style="color:#2a7f62">ZIP 다운로드</a></p>';
        }
        if ($files && count($files)) {
            echo '<h3 style="margin:12px 0 6px 0;font-size:16px;color:#2a7f62">첨부 파일</h3>';
            echo '<ul style="margin:0;padding-left:18px">';
            foreach($files as $f){
                $fid = isset($f['file_id']) ? (int)$f['file_id'] : 0;
                $fpath = isset($f['file_path']) ? $f['file_path'] : '';
                $fname = isset($f['original_name']) && $f['original_name'] ? $f['original_name'] : (isset($f['file_name']) ? $f['file_name'] : '');
                if ((!$fname || $fname==='') && $fpath) { $fname = basename($fpath); }
                $dl = $fid>0 ? ($base_url . '/partner_lab/order/file_download.php?file_id=' . $fid) : ($base_url . '/partner_lab/order/file_download.php?path=' . urlencode($fpath) . '&name=' . urlencode($fname));
                echo '<li><a href="' . htmlspecialchars($dl) . '" style="color:#0f3060">' . htmlspecialchars($fname) . '</a></li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        $content = ob_get_clean();
        $attachments = array();
        if ($files && count($files)) {
            foreach($files as $f){
                $fpath = isset($f['file_path']) ? $f['file_path'] : '';
                $fname = isset($f['original_name']) && $f['original_name'] ? $f['original_name'] : (isset($f['file_name']) ? $f['file_name'] : '');
                if ((!$fname || $fname==='') && $fpath) { $fname = basename($fpath); }
                if ($fpath && file_exists($fpath)) {
                    $attachments[] = array('path' => $fpath, 'name' => $fname);
                }
            }
        }
        $attachments_to_send = $attachments;
        $att_count = count($attachments);
        if ($att_count > 10) {
            $attachments_to_send = array_slice($attachments, 0, 10);
            @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'attachments_limited', ?, NOW())", array($order_id, '첨부 제한: total='.$att_count.' omitted='.( $att_count - 10 )));
        }
        $att_total_size = 0;
        foreach ($attachments_to_send as $__a) { if (isset($__a['path']) && file_exists($__a['path'])) { $att_total_size += (int)filesize($__a['path']); } }
        if ($att_total_size > 15728640) {
            $attachments_to_send = array();
            @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'attachments_omitted', ?, NOW())", array($order_id, '첨부 생략: size='.( $att_total_size )));
        }
        if (!function_exists('pl_build_zip')) {
            function pl_build_zip($zip_path, $files) {
                $fp = @fopen($zip_path, 'wb');
                if (!$fp) return false;
                $offset = 0;
                $central = '';
                $count = 0;
                foreach ($files as $f) {
                    $p = isset($f['path']) ? $f['path'] : '';
                    $n = isset($f['name']) ? $f['name'] : ($p ? basename($p) : '');
                    if (!$p || !file_exists($p)) continue;
                    $data = @file_get_contents($p);
                    if ($data === false) continue;
                    $count++;
                    $mtime = @filemtime($p);
                    if (!$mtime) $mtime = time();
                    $d = getdate($mtime);
                    $dosTime = ($d['hours'] << 11) | ($d['minutes'] << 5) | (int)floor($d['seconds'] / 2);
                    $dosDate = (($d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday'];
                    $crc = crc32($data);
                    $crc = $crc & 0xFFFFFFFF;
                    $size = strlen($data);
                    $localOffset = $offset;
                    $lh = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, strlen($n), 0);
                    fwrite($fp, $lh);
                    fwrite($fp, $n);
                    fwrite($fp, $data);
                    $offset += strlen($lh) + strlen($n) + $size;
                    $ce = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, strlen($n), 0, 0, 0, 0, 0, $localOffset);
                    $central .= $ce . $n;
                }
                $centralSize = strlen($central);
                $centralOffset = $offset;
                fwrite($fp, $central);
                $eocd = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralSize, $centralOffset, 0);
                fwrite($fp, $eocd);
                fclose($fp);
                return $count > 0 && file_exists($zip_path);
            }
        }
        $zip_created = false;
        $zip_path = '';
        $zip_name = '';
        if (!empty($attachments_to_send) && count($attachments_to_send) > 1) {
            $tmpDir = defined('G5_DATA_PATH') ? (G5_DATA_PATH . '/tmp') : (dirname(__FILE__) . '/tmp');
            if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0777, true); }
            $zip_name = 'order_' . (isset($order['order_number']) ? $order['order_number'] : $order_id) . '_attachments.zip';
            $zip_path = $tmpDir . '/' . $zip_name;
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                $opened = $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                if ($opened === true) {
                    foreach ($attachments_to_send as $f) {
                        $fpath = isset($f['path']) ? $f['path'] : '';
                        $fname = isset($f['name']) ? $f['name'] : ($fpath ? basename($fpath) : '');
                        if ($fpath && file_exists($fpath)) {
                            $zip->addFile($fpath, $fname);
                        }
                    }
                    $zip->close();
                    $zip_size = (int)@filesize($zip_path);
                    if ($zip_size > 15728640) {
                        @unlink($zip_path);
                        $zip_path = '';
                        $zip_name = '';
                        $attachments_to_send = array();
                        @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'attachments_omitted', ?, NOW())", array($order_id, '첨부 생략(ZIP 용량 초과): size=' . $zip_size));
                    } else {
                        $attachments_to_send = array(array('path' => $zip_path, 'name' => $zip_name));
                        $zip_created = true;
                        @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'attachments_zipped', ?, NOW())", array($order_id, 'ZIP 패키징: files=' . count($attachments)));
                    }
                } else {
                    $ok = pl_build_zip($zip_path, $attachments_to_send);
                    if ($ok) {
                        $zip_size = (int)@filesize($zip_path);
                        if ($zip_size > 15728640) {
                            @unlink($zip_path);
                            $zip_path = '';
                            $zip_name = '';
                            $attachments_to_send = array();
                            @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'attachments_omitted', ?, NOW())", array($order_id, '첨부 생략(ZIP 용량 초과): size=' . $zip_size));
                        } else {
                            $attachments_to_send = array(array('path' => $zip_path, 'name' => $zip_name));
                            $zip_created = true;
                            @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'attachments_zipped', ?, NOW())", array($order_id, 'ZIP 패키징: files=' . count($attachments)));
                        }
                    } else {
                        @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'zip_unavailable', 'ZipArchive 오픈 실패 및 수동 ZIP 생성 실패', NOW())", array($order_id));
                    }
                }
            } else {
                $ok = pl_build_zip($zip_path, $attachments_to_send);
                if ($ok) {
                    $zip_size = (int)@filesize($zip_path);
                    if ($zip_size > 15728640) {
                        @unlink($zip_path);
                        $zip_path = '';
                        $zip_name = '';
                        $attachments_to_send = array();
                        @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'attachments_omitted', ?, NOW())", array($order_id, '첨부 생략(ZIP 용량 초과): size=' . $zip_size));
                    } else {
                        $attachments_to_send = array(array('path' => $zip_path, 'name' => $zip_name));
                        $zip_created = true;
                        @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'attachments_zipped', ?, NOW())", array($order_id, 'ZIP 패키징: files=' . count($attachments)));
                    }
                } else {
                    @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'zip_unavailable', 'ZipArchive 클래스 미제공 및 수동 ZIP 생성 실패', NOW())", array($order_id));
                }
            }
        }
        $subject = '[파트너랩] 주문 확인서 - 주문번호 ' . (isset($order['order_number']) ? $order['order_number'] : $order_id);
        $fname = isset($config['cf_admin_email_name']) ? $config['cf_admin_email_name'] : 'Straumann';
        $fmail = defined('COMMON_SEND_EMAIL') ? COMMON_SEND_EMAIL : (isset($config['cf_admin_email']) ? $config['cf_admin_email'] : '');
        if (!$fmail || $fmail === '') {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (defined('G5_URL') ? parse_url(G5_URL, PHP_URL_HOST) : 'localhost');
            $fmail = 'noreply@' . $host;
        }
        $to_list = array('jeremy.jeon@straumann.com', 'order@3pointdental.com');
        if ($notifyEmail) { $to_list[] = $notifyEmail; }
        $to_email = implode(',', $to_list);
        $email_enabled = (isset($config['cf_email_use']) && $config['cf_email_use']) ? 1 : 0;
        if (!$email_enabled) {
            @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'email_disabled', '환경설정에서 메일발송 사용이 꺼져 있음', NOW())", array($order_id));
        }
        $mail_ok = mailer($fname, $fmail, $to_email, $subject, $content, 1, $attachments_to_send);
        if (!$mail_ok) {
            $pm = new PHPMailer();
            $pm->CharSet = 'UTF-8';
            $pm->Encoding = 'base64';
            $pm->From = $fmail;
            $pm->FromName = $fname;
            $pm->Subject = $subject;
            $pm->isHTML(true);
            $pm->msgHTML($content);
            foreach ($to_list as $__em) { $pm->addAddress($__em); }
            if ($attachments_to_send && count($attachments_to_send)) {
                foreach ($attachments_to_send as $f) {
                    $ext = strtolower(pathinfo(isset($f['name'])?$f['name']:basename($f['path']), PATHINFO_EXTENSION));
                    if ($ext === 'pdf') { $pm->addAttachment($f['path'], $f['name'], 'base64', 'application/pdf'); }
                    elseif ($ext === 'zip') { $pm->addAttachment($f['path'], $f['name'], 'base64', 'application/zip'); }
                    else { $pm->addAttachment($f['path'], $f['name']); }
                }
            }
            $fallback_ok = $pm->send();
            if ($fallback_ok) {
                @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'mail_sent', '메일 전송 성공(폴백)', NOW())", array($order_id));
            } else {
                $err = method_exists($pm, 'ErrorInfo') ? $pm->ErrorInfo : '';
                @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'mail_failed', ?, NOW())", array($order_id, '메일 전송 실패(폴백): '.$err));
            }
        } else {
            @dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, 'mail_sent', '메일 전송 성공', NOW())", array($order_id));
        }
        if ($zip_created && $zip_path && file_exists($zip_path)) { @unlink($zip_path); }
        // 중복 발송 방지: 개별 aaron 전송 제거 (to_list에 포함)
        // 엑셀(CSV) 로그 적재
        $csvPath = dirname(__FILE__) . '/order_exports.csv';
        $fp = @fopen($csvPath, 'a');
        if ($fp) {
            $row = array(date('Y-m-d H:i:s'), $order_id, isset($order['order_number'])?$order['order_number']:'', isset($order['patient_name'])?$order['patient_name']:'', isset($order['delivery_preference'])?$order['delivery_preference']:'', isset($order['delivery_date'])?$order['delivery_date']:'', isset($order['delivery_hope_date'])?$order['delivery_hope_date']:'', (isset($order['delivery_address'])?$order['delivery_address']:'').' '.(isset($order['delivery_detail_address'])?$order['delivery_detail_address']:'') );
            @fputcsv($fp, $row);
            @fclose($fp);
        }
        echo '<script>alert("주문이 전송되었습니다."); location.href = "./history.php";</script>';
        exit;
    } catch (Exception $e) {
        dbu_rollback($conn);
        echo '<div style="padding:20px;color:#b00020">저장 중 오류: '.htmlspecialchars($e->getMessage()).'</div>';
    }
}

?>

<script>
// index와 동일하게 body에 페이지 전용 클래스를 추가하여 폰트/타이포그래피 적용
document.addEventListener('DOMContentLoaded', function(){
  document.body.classList.add('partner-lab-order-page');
  document.body.classList.add('hydrated');
});
</script>


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
.pl-overlay { position:fixed; left:0; top:0; right:0; bottom:0; background:rgba(0,0,0,0.45); display:none; align-items:center; justify-content:center; z-index:2000; }
/* 기본 테마 푸터(dfbox fWrap)는 파트너 페이지에서 숨김 */
.dfbox.fWrap, .fWrap.dfbox { display: none !important; }
.pl-overlay__box { width:90%; max-width:520px; background:#ffffff; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.25); padding:24px; text-align:center; font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif; }
.pl-spinner { margin:0 auto 16px auto; width:56px; height:56px; border-radius:50%; border:6px solid #e6f4ef; border-top-color:#2a7f62; animation:pl-spin 0.9s linear infinite; }
@keyframes pl-spin { from { transform:rotate(0deg);} to { transform:rotate(360deg);} }
.pl-overlay__title { font-size:18px; font-weight:600; color:#1f2937; margin:0 0 4px 0; }
.pl-overlay__desc { font-size:14px; color:#374151; line-height:1.6; margin:0; }
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

<div id="pl-overlay" class="pl-overlay" role="dialog" aria-live="polite" aria-busy="true">
  <div class="pl-overlay__box">
    <div class="pl-spinner"></div>
    <p class="pl-overlay__title">주문서를 안전하게 전송 중입니다.</p>
    <p class="pl-overlay__desc">전송이 완료될 때까지 잠시만 기다려주시고,</p>
    <p class="pl-overlay__desc">창을 닫으실 경우 전송이 실패할 수 있으니</p>
    <p class="pl-overlay__desc">창을 닫지 말아주세요.</p>
  </div>
  </div>
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
<style>
.grid-title{font-size:16px;font-weight:700;margin:0 0 8px 0}
.grid-section{margin-top:16px}
.grid-table{width:100%;border-collapse:collapse;border:1px solid #d0d7de;border-radius:6px;overflow:hidden}
.grid-table th{background:#f6f8fa;color:#0f3060;font-weight:600;padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
.grid-table td{padding:10px;border-bottom:1px solid #e5e7eb}
.grid-table tr:nth-child(even){background:#fbfdff}
</style>

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
                    $sql = 'UPDATE `partner_lab_orders` SET ' . implode(', ', $sets) . ' WHERE `'.$orderPk.'` = ?';
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
      <?php if ($teeth && count($teeth)) { ?>
      <div class="tooth-pills">
        <?php foreach ($teeth as $t) { 
            $num = isset($t['tooth_number']) ? (int)$t['tooth_number'] : 0;
            $raw_src = isset($t['tooth_type']) ? $t['tooth_type'] : '';
            $raw = strtolower(trim($raw_src));
            $type_label = ($raw==='single') ? '싱글' : (($raw==='bridge') ? '브릿지' : (isset($t['tooth_type']) ? $t['tooth_type'] : ''));
            $opts = isset($tooth_options_map[$num]) ? $tooth_options_map[$num] : array();
            $sys = isset($opts['system_other']) && $opts['system_other'] ? $opts['system_other'] : (isset($opts['system']) ? $opts['system'] : '');
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
      <?php } else { ?>
        <div class="tooth-pills">
          <?php if (!empty($selected_teeth_nums)) { ?>
            <?php foreach ($selected_teeth_nums as $num) { 
                $opts = isset($tooth_options_map[$num]) ? $tooth_options_map[$num] : array();
                $type_label = '';
                if (isset($opts['mode'])) {
                    $raw = strtolower(trim($opts['mode']));
                    $type_label = ($raw==='bridge') ? '브릿지' : (($raw==='general' || $raw==='single') ? '싱글' : $opts['mode']);
                }
                $sys = isset($opts['system_other']) && $opts['system_other'] ? $opts['system_other'] : (isset($opts['system']) ? $opts['system'] : '');
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
                  <span class="opt"><?php echo htmlspecialchars(trim(($sys?($sys.' '):'').($margin?('/ '.$margin+' '):'').($pros?('/ '.$pros.' '):'').($shade?('/ '.$shade):''))); ?></span>
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
                $sys = (isset($opts['system_other']) && $opts['system_other']) ? $opts['system_other'] : (isset($opts['system']) ? $opts['system'] : '');
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
      <?php } ?>
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
      <div class="notes"><?php echo $extra_txt !== '' ? nl2br(htmlspecialchars($extra_txt)) : '-'; ?></div>
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
                <a class="pl-btn" href="file_download.php?<?php echo $fid>0 ? ('file_id='.$fid) : ('path='.urlencode($fpath).'&name='.urlencode($fname)); ?>">다운로드</a>
                <button type="button" class="pl-btn file-del-btn" data-file-id="<?php echo $fid; ?>" data-file-path="<?php echo htmlspecialchars($fpath); ?>">삭제</button>
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
  </div>

  <div class="letter-actions">
    <button class="pl-btn" type="button" onclick="downloadPDF()">PDF 다운로드</button>
    <?php if ($files && count($files)) { ?>
    <a class="pl-btn" href="./download_zip.php?order_id=<?php echo (int)$order_id; ?>">전체 첨부 ZIP 다운로드</a>
    <?php } ?>
    <button class="pl-btn" type="button" onclick="location.href='./history.php'">주문 내역</button>
    <?php 
    // 발주서 저장 및 수정하기 버튼은 특정 상태에서만 표시
    $hide_buttons_statuses = array('confirmed', 'processing', 'shipping', 'completed');
    $current_status = isset($order['order_status']) ? $order['order_status'] : '';
    $should_hide_buttons = in_array($current_status, $hide_buttons_statuses);
    ?>
    <?php if (!$should_hide_buttons): ?>
    <form method="post" action="./confirm.php">
      <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
      <input type="hidden" name="action" value="finalize">
      <button type="submit" class="pl-btn pl-btn--current">주문 전송</button>
    </form>
    <a class="pl-btn" href="./index.php?order_id=<?php echo (int)$order_id; ?>&ref=confirm">수정하기</a>
    <?php endif; ?>
  </div>
</div>

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
<script>
document.addEventListener('DOMContentLoaded', function(){
  var btns = document.querySelectorAll('.file-del-btn');
  for (var i=0;i<btns.length;i++) {
    btns[i].addEventListener('click', function(e){
      var btn = this;
      var fid = parseInt(btn.getAttribute('data-file-id'), 10) || 0;
      var path = btn.getAttribute('data-file-path') || '';
      if (!confirm('첨부파일을 삭제하시겠습니까?')) return;
      var body = 'file_id=' + encodeURIComponent(fid);
      if (path) body += '&path=' + encodeURIComponent(path);
      fetch('delete_file.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
        .then(function(res){ return res.json(); })
        .then(function(data){
          if (data && data.success) {
            var el = btn;
            while (el && el.tagName !== 'TR') { el = el.parentNode; }
            if (el && el.parentNode) { el.parentNode.removeChild(el); }
          } else {
            alert('삭제 실패: ' + (data && data.message ? data.message : ''));
          }
        })
        .catch(function(err){ alert('삭제 중 오류: ' + err.message); });
    });
  }
});
</script>

<?php /* 치아 이미지 스냅샷 제거: 주문 페이지처럼 텍스트 요약으로 단순화 */ ?>

<?php
// 기본 테마 푸터는 사용하지 않음 (파트너 주문 페이지 전용 푸터 사용)
?>
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
    doc.save('order-confirmation.pdf');
  });
}
</script>
<script>
function savePdfToServer(pdfData, isBlob){
  return new Promise(function(resolve, reject){
    if (isBlob) {
      var fd = new FormData();
      fd.append('order_id', <?php echo (int)$order_id; ?>);
      fd.append('pdf_file', pdfData, 'order_'+<?php echo (int)$order_id; ?>+'.pdf');
      if (window.jQuery && jQuery.ajax) {
        jQuery.ajax({ url: 'save_pdf.php', type: 'POST', dataType: 'json', data: fd, processData: false, contentType: false })
          .done(function(resp){ resolve(resp); })
          .fail(function(){ reject(new Error('upload_failed')); });
      } else {
        fetch('save_pdf.php', { method: 'POST', body: fd })
          .then(function(res){ return res.json(); })
          .then(function(resp){ resolve(resp); })
          .catch(function(err){ reject(err || new Error('upload_failed')); });
      }
    } else {
      if (window.jQuery && jQuery.ajax) {
        jQuery.ajax({ url: 'save_pdf.php', type: 'POST', dataType: 'json', data: { order_id: <?php echo (int)$order_id; ?>, pdf: pdfData } })
          .done(function(resp){ resolve(resp); })
          .fail(function(){ reject(new Error('upload_failed')); });
      } else {
        fetch('save_pdf.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'order_id='+encodeURIComponent(<?php echo (int)$order_id; ?>)+'&pdf='+encodeURIComponent(pdfData) })
          .then(function(res){ return res.json(); })
          .then(function(resp){ resolve(resp); })
          .catch(function(err){ reject(err || new Error('upload_failed')); });
      }
    }
  });
}
document.addEventListener('DOMContentLoaded', function(){
  var finalizeForm = document.querySelector('form[action="./confirm.php"][method="post"]');
  if (!finalizeForm) return;
  finalizeForm.addEventListener('submit', function(e){
    var ok = confirm('발주서를 저장하시겠습니까?');
    if (!ok) { e.preventDefault(); return; }
    e.preventDefault();
    var overlay = document.getElementById('pl-overlay');
    if (overlay) { overlay.style.display = 'flex'; }
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
      var blob = doc.output('blob');
      savePdfToServer(blob, true).then(function(resp){
        try {
          if (resp && resp.success && resp.path) {
            var p = finalizeForm.querySelector('input[name="pdf_path"]');
            var n = finalizeForm.querySelector('input[name="pdf_name"]');
            if (p) p.value = resp.path;
            if (n) n.value = 'order_'+<?php echo (int)$order_id; ?>+'.pdf';
          }
        } catch(_e) {}
        finalizeForm.submit();
      }).catch(function(){
        finalizeForm.submit();
      });
    });
  });
});
</script>
