<?php
// 파트너랩 관리자 주문 목록 엑셀 다운로드
include_once('../../common.php');
include_once('../../config.php');
include_once('./config.php');

// 상태 라벨 폴백 정의 (다른 파일에서 미정의 시)
if (!function_exists('get_order_status_name')) {
    function get_order_status_name($status) {
        $status_names = array(
            'pending' => '주문',
            'confirmed' => '주문접수',
            'processing' => '파트너 확인',
            'done' => '완료'
        );
        return isset($status_names[$status]) ? $status_names[$status] : $status;
    }
}

// 관리자 접근 제어
{ 
    $main_url  = defined('G5_URL') ? G5_URL : '/';
    $login_url = defined('G5_BBS_URL') ? (G5_BBS_URL.'/login.php') : '/bbs/login.php';
    $is_logged_in = (isset($is_member) && $is_member) || (isset($_SESSION['ss_mb_id']) && $_SESSION['ss_mb_id']);
    if (!$is_logged_in) { alert('로그인 후 이용 가능합니다.', $login_url.'?url='.urlencode($_SERVER['REQUEST_URI'])); exit; }
    $admin_ok = (isset($is_admin) && $is_admin === 'super') || (isset($member['mb_level']) && (int)$member['mb_level'] >= 10) || (isset($_SESSION['ss_mb_level']) && (int)$_SESSION['ss_mb_level'] >= 10);
    if (!$admin_ok) { alert('접근 권한이 없습니다. 관리자만 이용 가능합니다.', $main_url); exit; }
}

$db = get_partner_lab_db_connection();

// 필터 수집 (index.php와 동일 파라미터)
$search_type   = isset($_GET['search_type']) ? $_GET['search_type'] : '';
$search_keyword= isset($_GET['search_keyword']) ? $_GET['search_keyword'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from     = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to       = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// 컬럼/FK 탐색 (index.php 로직 준용)
$orders_pk_col = 'order_id';
$orders_created_col = 'created_at';
$customer_name_col = 'customer_name';
$patient_name_col  = 'patient_name';
$orders_cols = array();
$desc_orders_res = @mysqli_query($db, "DESCRIBE partner_lab_orders");
if ($desc_orders_res) { while ($r = mysqli_fetch_assoc($desc_orders_res)) { $orders_cols[] = $r['Field']; if ($r['Key']==='PRI') $orders_pk_col = $r['Field']; } }
if (!in_array($orders_created_col, $orders_cols)) { foreach (array('regdate','reg_date','wr_datetime','write_time','created') as $c) { if (in_array($c, $orders_cols)) { $orders_created_col=$c; break; } } }
if (!in_array($customer_name_col, $orders_cols)) $customer_name_col = null;
if (!in_array($patient_name_col, $orders_cols)) $patient_name_col  = null;

$teeth_fk_col='order_id'; $files_fk_col='order_id';
$teeth_cols=array(); $files_cols=array();
$desc_teeth_res = @mysqli_query($db, "DESCRIBE partner_lab_order_teeth");
if ($desc_teeth_res) { while ($r=mysqli_fetch_assoc($desc_teeth_res)) { $teeth_cols[]=$r['Field']; } if (!in_array($teeth_fk_col,$teeth_cols)) { if (in_array($orders_pk_col,$teeth_cols)) $teeth_fk_col=$orders_pk_col; else foreach ($teeth_cols as $c) { if (strpos($c,'order')!==false) { $teeth_fk_col=$c; break; } } } }
$teeth_has_tooth_number = in_array('tooth_number',$teeth_cols);
$teeth_details_fk_col='order_id'; $teeth_details_cols=array();
$desc_teeth_details_res = @mysqli_query($db, "DESCRIBE partner_lab_order_teeth_details");
if ($desc_teeth_details_res) { while ($r=mysqli_fetch_assoc($desc_teeth_details_res)) { $teeth_details_cols[]=$r['Field']; } if (!in_array($teeth_details_fk_col,$teeth_details_cols)) { if (in_array($orders_pk_col,$teeth_details_cols)) $teeth_details_fk_col=$orders_pk_col; else foreach ($teeth_details_cols as $c) { if (strpos($c,'order')!==false) { $teeth_details_fk_col=$c; break; } } } }
$teeth_details_has_tooth_number = in_array('tooth_number',$teeth_details_cols);
$desc_files_res = @mysqli_query($db, "DESCRIBE partner_lab_order_files");
if ($desc_files_res) { while ($r=mysqli_fetch_assoc($desc_files_res)) { $files_cols[]=$r['Field']; } if (!in_array($files_fk_col,$files_cols)) { if (in_array($orders_pk_col,$files_cols)) $files_fk_col=$orders_pk_col; else foreach ($files_cols as $c) { if (strpos($c,'order')!==false) { $files_fk_col=$c; break; } } } }

// WHERE 구성
$where_conditions = array();
if ($search_type && $search_keyword) {
    $kw = mysqli_real_escape_string($db, $search_keyword);
    if ($search_type==='order_id') $where_conditions[] = "o.`$orders_pk_col` LIKE '%$kw%'";
    else if ($search_type==='customer_name' && $customer_name_col) $where_conditions[] = "o.`$customer_name_col` LIKE '%$kw%'";
    else if ($search_type==='patient_name' && $patient_name_col) $where_conditions[] = "o.`$patient_name_col` LIKE '%$kw%'";
}
if ($status_filter) { $status = mysqli_real_escape_string($db,$status_filter); $where_conditions[] = "o.order_status = '$status'"; }
if ($date_from) { $df=mysqli_real_escape_string($db,$date_from); $where_conditions[] = "DATE(o.$orders_created_col) >= '$df'"; }
if ($date_to)   { $dt=mysqli_real_escape_string($db,$date_to);   $where_conditions[] = "DATE(o.$orders_created_col) <= '$dt'"; }
$where_sql = !empty($where_conditions) ? ('WHERE '.implode(' AND ',$where_conditions)) : '';

// 서브셀렉트 (치아수/파일수)
$teeth_count_main_sel   = ($teeth_fk_col ? "(SELECT ".($teeth_has_tooth_number?"COUNT(DISTINCT ot.`tooth_number`)":"COUNT(*)")." FROM partner_lab_order_teeth ot WHERE ot.`$teeth_fk_col` = o.`$orders_pk_col`)" : '0');
$teeth_count_details_sel= ($desc_teeth_details_res && $teeth_details_fk_col ? "(SELECT ".($teeth_details_has_tooth_number?"COUNT(DISTINCT td.`tooth_number`)":"COUNT(*)")." FROM partner_lab_order_teeth_details td WHERE td.`$teeth_details_fk_col` = o.`$orders_pk_col`)" : '0');
$teeth_count_sel        = "GREATEST($teeth_count_main_sel, $teeth_count_details_sel)";
$file_count_sel         = ($files_fk_col ? "(SELECT COUNT(*) FROM partner_lab_order_files of WHERE of.`$files_fk_col` = o.`$orders_pk_col`)" : '0');

$order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
if ($order_id !== '') {
    $oid = mysqli_real_escape_string($db, $order_id);
    $where_sql = "WHERE o.`$orders_pk_col` = '$oid'";
}
$order_by_col = $orders_created_col ? $orders_created_col : $orders_pk_col;
$list_sql = "SELECT o.*, $teeth_count_sel AS teeth_count, $file_count_sel AS file_count FROM partner_lab_orders o $where_sql ORDER BY o.`$order_by_col` DESC";
$res = mysqli_query($db, $list_sql);
if (!$res) { alert('데이터를 조회할 수 없습니다.'); exit; }

$orders = array();
while ($row = mysqli_fetch_assoc($res)) { $orders[] = $row; }
if (empty($orders)) { alert('조건에 해당하는 주문이 없습니다.'); exit; }

// 헤더 정의 (템플릿 라벨 파싱에서 사용)
$headers = array('주문번호','주문일시','고객명','환자명','치아수','파일수','상태');

// PHPExcel 사용: 템플릿 exam.xlsx 로드 후 헤더 기반 매핑
include_once(G5_LIB_PATH.'/PHPExcel.php');
if (!class_exists('PHPExcel')) { alert('엑셀 라이브러리가 없습니다.'); exit; }

$template_path = dirname(__FILE__).'/exam.xlsx';
$objReader = PHPExcel_IOFactory::createReader('Excel2007');
$excel = is_file($template_path) ? $objReader->load($template_path) : new PHPExcel();
$sheet = $excel->setActiveSheetIndex(0);

// 템플릿 1행 섹션/2행 필드 라벨 수집
$headers = array();
$sub_labels = array();
for ($col = 0; $col < 100; $col++) {
    $h1 = trim((string)$sheet->getCellByColumnAndRow($col, 1)->getValue());
    $h2 = trim((string)$sheet->getCellByColumnAndRow($col, 2)->getValue());
    if ($h1 === '' && $h2 === '') { if ($col > 5) break; else continue; }
    $headers[$col] = $h1;
    $sub_labels[$col] = $h2;
}

// 라벨이 비어있으면 기본 라벨로 대체 (2행)
$default_labels = array('주문번호','작성일','이름','연락처','이메일','주소','납기 희망일','환자명','환자나이','성별','수령/배송','택배픽업신청','No.','싱글/브릿지','임플란트 시스템','마진레벨','상부보철','쉐이드','정품 PMAB 적용','정품 스크류 적용','아노다이징 적용','Non-engaging 적용','택배 픽업 신청','픽업 희망일','내용');
if (count(array_filter($sub_labels)) === 0) {
    foreach ($default_labels as $i => $t) { $sub_labels[$i] = $t; $sheet->setCellValueByColumnAndRow($i, 2, $t); }
}

// 1행 섹션 라벨을 2행 라벨에 맞춰 자동 채움
function pl_section_for_label($lab) {
    $k = str_replace(' ', '', trim((string)$lab));
    if ($k==='주문번호' || $k==='작성일') return '주문 정보';
    if ($k==='이름' || $k==='연락처' || $k==='이메일') return '고객정보';
    if ($k==='주소' || $k==='납기희망일') return '배송정보';
    if ($k==='환자명' || $k==='환자나이' || $k==='성별') return '환자정보';
    if ($k==='수령/배송' || $k==='수령/배송' || $k==='택배픽업신청' || $k==='택배픽업신청') return '작업/발송 정보';
    if ($k==='No.' || $k==='싱글/브릿지' || $k==='임플란트시스템' || $k==='임플란트 시스템' || $k==='마진레벨' || $k==='상부보철' || $k==='상부 보철' || $k==='쉐이드') return '선택치아';
    if ($k==='정품PMAB적용' || $k==='정품스크류적용' || $k==='아노다이징적용' || $k==='Non-engaging적용' || $k==='Non-engaging 적용') return '기타추가옵션';
    if ($k==='택배픽업신청' || $k==='택배픽업신청' || $k==='픽업희망일' || $k==='택배픽업신청' || $k==='택배픽업신청' || $k==='택배픽업신청' || $k==='택배픽업신청' || $k==='택배픽업신청' || $k==='택배픽업신청') return '디지털 임프레션/ 러버 임프레션';
    if ($k==='내용') return '기타 사항';
    return '';
}

foreach ($sub_labels as $i => $lab) {
    $sec = pl_section_for_label($lab);
    if ($sec !== '') { $headers[$i] = $sec; $sheet->setCellValueByColumnAndRow($i, 1, $sec); }
}

if (!function_exists('column_char')) { function column_char($i){ return chr(65+$i); } }
$maxCol = count($sub_labels) ? max(array_keys($sub_labels)) : 0;
$rangeAll = 'A1:' . column_char($maxCol) . '2';
$sheet->getStyle($rangeAll)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

$groups = array();
$cur = null; $start = null;
for ($c = 0; $c <= $maxCol; $c++) {
    $label = isset($headers[$c]) ? $headers[$c] : '';
    if ($cur === null) { $cur = $label; $start = $c; continue; }
    if ($label !== $cur) {
        if ($cur !== '' && $start !== null && ($c - 1) > $start) {
            $sheet->mergeCellsByColumnAndRow($start, 1, $c - 1, 1);
        }
        $cur = $label; $start = $c;
    }
}
if ($cur !== '' && $start !== null && $maxCol > $start) { $sheet->mergeCellsByColumnAndRow($start, 1, $maxCol, 1); }

// 파일명/치아 목록 캐시용 조회 함수
function pl_fetch_file_names($db, $files_fk_col, $orders_pk_col, $order_id) {
    $names = array();
    $sql = "SELECT original_name, file_name FROM partner_lab_order_files WHERE `".$files_fk_col."`='".mysqli_real_escape_string($db,$order_id)."' ORDER BY id ASC";
    $res = mysqli_query($db,$sql);
    if ($res) { while ($r=mysqli_fetch_assoc($res)) { $names[] = isset($r['original_name'])&&$r['original_name']?$r['original_name']:(isset($r['file_name'])?$r['file_name']:''); } }
    return implode(', ', array_filter($names));
}

function pl_compute_selected_teeth($row) {
    $nums = array();
    if (isset($row['selected_teeth']) && trim((string)$row['selected_teeth'])!=='') {
        $sel = @json_decode($row['selected_teeth'], true);
        if (is_array($sel)) { foreach ($sel as $v) { if (is_numeric($v)) $nums[] = (int)$v; } }
        else { foreach (preg_split('/\s*,\s*/', $row['selected_teeth']) as $p) { if (is_numeric($p)) $nums[] = (int)$p; } }
    }
    if (empty($nums) && isset($row['auto_save_data']) && trim((string)$row['auto_save_data'])!=='') {
        $snap = @json_decode($row['auto_save_data'], true);
        if (is_array($snap)) {
            if (isset($snap['selected_teeth']) && is_array($snap['selected_teeth'])) { foreach ($snap['selected_teeth'] as $v) { if (is_numeric($v)) $nums[]=(int)$v; } }
            if (empty($nums)) { foreach ($snap as $k=>$v) { if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[/', $k, $m)) { $nums[]=(int)$m[1]; } } }
        }
    }
    if (!empty($nums)) { $nums = array_values(array_unique(array_filter(array_map('intval',$nums)))); sort($nums); }
    return implode(', ', $nums);
}

// PHP 5.2 호환: 헤더명에 따른 값 반환 함수
if (!function_exists('get_order_status_name')) {
    function get_order_status_name($status) {
        $status_names = array(
            'pending' => '주문',
            'confirmed' => '주문접수',
            'processing' => '파트너 확인'
        );
        return isset($status_names[$status]) ? $status_names[$status] : $status;
    }
}

function pl_value_by_header($title, $row) {
    if ($title === '주문번호') return isset($row['__pk']) ? $row['__pk'] : '';
    if ($title === '주문일시') return (isset($row['__created']) && $row['__created']) ? date('Y-m-d H:i', strtotime($row['__created'])) : '';
    if ($title === '고객명') return isset($row['customer_name']) ? $row['customer_name'] : '';
    if ($title === '환자명') return isset($row['patient_name']) ? $row['patient_name'] : '';
    if ($title === '환자 나이') return isset($row['patient_birth']) ? $row['patient_birth'] : '';
    if ($title === '성별') return isset($row['patient_gender']) ? $row['patient_gender'] : '';
    if ($title === '납기 희망일') return isset($row['delivery_date']) ? substr($row['delivery_date'],0,10) : '';
    if ($title === '치아수') return isset($row['teeth_count']) ? (int)$row['teeth_count'] : 0;
    if ($title === '선택치아') return pl_compute_selected_teeth($row);
    if ($title === '파일수') return isset($row['file_count']) ? (int)$row['file_count'] : 0;
    if ($title === '파일 목록') return isset($row['__files']) ? $row['__files'] : '';
    if ($title === '작업종류') return isset($row['work_type']) ? $row['work_type'] : '';
    if ($title === 'Implant System') return isset($row['implant_system']) ? $row['implant_system'] : (isset($row['impl_system'])?$row['impl_system']:'');
    if ($title === '상태') return get_order_status_name(isset($row['order_status']) ? $row['order_status'] : '');
    if ($title === '비고') return isset($row['note']) ? $row['note'] : '';
    if ($title === '고객 이메일') return isset($row['customer_email']) ? $row['customer_email'] : '';
    if ($title === '고객 연락처') return isset($row['customer_phone']) ? $row['customer_phone'] : '';
    return '';
}

// 데이터 행을 템플릿에 채우기 (2행부터)
$rowIndex = 3;
/* 데이터 행 채우기 시작 */

// 새로 rows를 재구성: DB에서 얻은 $orders 기반 (이미 위에서 $orders 생성)
// 파일명 리스트 미리 캐시
$files_cache = array();
foreach ($orders as $i => $row) {
    $pk = isset($row[$orders_pk_col]) ? $row[$orders_pk_col] : '';
    $orders[$i]['__pk'] = $pk;
    $orders[$i]['__created'] = isset($row[$orders_created_col]) ? $row[$orders_created_col] : '';
    if (!isset($files_cache[$pk])) { $files_cache[$pk] = pl_fetch_file_names($db, $files_fk_col, $orders_pk_col, $pk); }
    $orders[$i]['__files'] = $files_cache[$pk];
}

// 치아 옵션 맵 및 발송 라벨 구성
function pl_build_tooth_options_map_from_row($row) {
    $map = array();
    if (isset($row['teeth_configurations']) && trim((string)$row['teeth_configurations']) !== '') {
        $flat = @json_decode($row['teeth_configurations'], true);
        if (is_array($flat)) {
            foreach ($flat as $k => $v) {
                if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[([^\]]+)\]$/', $k, $m)) {
                    $tn = intval($m[1]); $opt = $m[2];
                    if (!isset($map[$tn])) $map[$tn] = array();
                    $map[$tn][$opt] = $v;
                }
            }
        }
    }
    if (empty($map) && isset($row['auto_save_data']) && trim((string)$row['auto_save_data']) !== '') {
        $snap = @json_decode($row['auto_save_data'], true);
        if (is_array($snap)) {
            foreach ($snap as $k => $v) {
                if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[([^\]]+)\]$/', $k, $m)) {
                    $tn = intval($m[1]); $opt = $m[2];
                    if (!isset($map[$tn])) $map[$tn] = array();
                    $map[$tn][$opt] = $v;
                }
            }
        }
    }
    return $map;
}

function pl_summarize_single_bridge($db, $orders_pk_col, $order_pk_val, $opts_map) {
    $single = 0; $bridge = 0; $pairs = array();
    $sql = "SELECT tooth_number, tooth_type FROM partner_lab_order_teeth WHERE `".$orders_pk_col."`='".mysqli_real_escape_string($db,$order_pk_val)."'";
    $res = mysqli_query($db,$sql);
    if ($res) { while ($r=mysqli_fetch_assoc($res)) { $n=isset($r['tooth_number'])?(int)$r['tooth_number']:0; $tt = isset($r['tooth_type'])?strtolower(trim($r['tooth_type'])):''; if ($n>0) { $label = ($tt==='bridge')?'브릿지':'싱글'; $pairs[] = $n.':'.$label; if ($label==='브릿지') $bridge++; else $single++; } } }
    foreach ($opts_map as $tn=>$opts) { if (isset($opts['mode'])) { $raw=strtolower(trim($opts['mode'])); $label = ($raw==='bridge')?'브릿지':(($raw==='general'||$raw==='single')?'싱글':$opts['mode']); $pairs[] = $tn.':'.$label; if ($label==='브릿지') $bridge++; else $single++; } }
    if (!empty($pairs)) return implode(', ', array_unique($pairs));
    if ($single || $bridge) return '싱글 '.$single.'개, 브릿지 '.$bridge.'개';
    return '';
}

foreach ($orders as $i => $row) {
    $orders[$i]['__opts_map'] = pl_build_tooth_options_map_from_row($row);
    $method_label = '';
    if (isset($row['delivery_method'])) {
        $dm = strtolower(trim($row['delivery_method']));
        $method_label = ($dm==='pickup' ? '택배 픽업 신청' : ($dm==='delivery' ? '배송 진행' : ''));
    }
    if ($method_label==='') {
        if (!empty($row['rubber_impression_delivery']) || !empty($row['delivery_hope_date']) || !empty($row['delivery_address']) || !empty($row['delivery_detail_address'])) { $method_label = '택배 픽업 신청'; }
        else { $method_label = '배송 진행'; }
    }
    $orders[$i]['__method_label'] = $method_label;
    $orders[$i]['__pickup_label'] = (!empty($row['rubber_impression_delivery']) && ($row['rubber_impression_delivery']===1 || $row['rubber_impression_delivery']==='1' || $row['rubber_impression_delivery']==='on')) ? '신청함' : '선택하지 않았음';
    $orders[$i]['__single_bridge'] = pl_summarize_single_bridge($db, $orders_pk_col, $orders[$i]['__pk'], $orders[$i]['__opts_map']);
}

foreach ($orders as $row) {
    // 파일 캐시 및 보조 라벨 구성은 위에서 처리됨
    foreach ($sub_labels as $col => $label) {
        $lab = trim((string)$label);
        $val = '';
        if ($lab === '주문번호') { $val = isset($row['order_number']) && $row['order_number'] ? $row['order_number'] : (isset($row['__pk'])?$row['__pk']:''); }
        else if ($lab === '작성일') { $val = isset($row['__created']) && $row['__created'] ? date('Y-m-d H:i', strtotime($row['__created'])) : ''; }
        else if ($lab === '이름') { $val = isset($row['customer_name']) ? $row['customer_name'] : ''; }
        else if ($lab === '연락처') { $val = isset($row['customer_phone']) ? $row['customer_phone'] : ''; }
        else if ($lab === '이메일') { $val = isset($row['customer_email']) ? $row['customer_email'] : ''; }
        else if ($lab === '주소') { $val = (isset($row['shipping_address'])?$row['shipping_address']:'') . ' ' . (isset($row['shipping_detail'])?$row['shipping_detail']:''); }
        else if ($lab === '납기 희망일') { $val = isset($row['delivery_date']) ? substr($row['delivery_date'],0,10) : ''; }
        else if ($lab === '환자명') { $val = isset($row['patient_name']) ? $row['patient_name'] : ''; }
        else if ($lab === '환자나이') { $val = isset($row['patient_birth']) ? $row['patient_birth'] : ''; }
        else if ($lab === '성별') { $val = isset($row['patient_gender']) ? $row['patient_gender'] : ''; }
        else if ($lab === '수령/배송') { $val = isset($row['__method_label']) ? $row['__method_label'] : ''; }
        else if ($lab === '택배픽업신청' || $lab === '택배 픽업 신청') { $val = isset($row['__pickup_label']) ? $row['__pickup_label'] : ''; }
        else if ($lab === 'No.') { $val = pl_compute_selected_teeth($row); }
        else if ($lab === '싱글/브릿지') { $val = isset($row['__single_bridge']) ? $row['__single_bridge'] : ''; }
        else if ($lab === '임플란트 시스템') { $agg = array(); $map = isset($row['__opts_map']) ? $row['__opts_map'] : array(); foreach ($map as $tn=>$opts) { $sys = (isset($opts['system_other'])&&$opts['system_other'])?$opts['system_other']:(isset($opts['system'])?$opts['system']:''); if ($sys==='0') $sys=''; if ($sys!=='') $agg[] = $tn.':'.$sys; } $val = !empty($agg)?implode(', ', $agg):''; }
        else if ($lab === '마진레벨') { $agg = array(); $map = isset($row['__opts_map']) ? $row['__opts_map'] : array(); foreach ($map as $tn=>$opts) { if (!empty($opts['margin'])) $agg[] = $tn.':'.$opts['margin']; } $val = !empty($agg)?implode(', ', $agg):''; }
        else if ($lab === '상부보철') { $agg = array(); $map = isset($row['__opts_map']) ? $row['__opts_map'] : array(); foreach ($map as $tn=>$opts) { if (!empty($opts['prosthetic'])) $agg[] = $tn.':'.$opts['prosthetic']; } $val = !empty($agg)?implode(', ', $agg):''; }
        else if ($lab === '쉐이드') { $agg = array(); $map = isset($row['__opts_map']) ? $row['__opts_map'] : array(); foreach ($map as $tn=>$opts) { if (!empty($opts['shade'])) $agg[] = $tn.':'.$opts['shade']; } $val = !empty($agg)?implode(', ', $agg):''; }
        else if ($lab === '정품 PMAB 적용') { $val = (strpos(isset($row['additional_info'])?$row['additional_info']:'', 'PMAB')!==false)?'예':''; }
        else if ($lab === '정품 스크류 적용') { $val = (strpos(isset($row['additional_info'])?$row['additional_info']:'', '스크류')!==false || strpos(isset($row['additional_info'])?$row['additional_info']:'', 'Screw')!==false)?'예':''; }
        else if ($lab === '아노다이징 적용') { $val = (strpos(isset($row['additional_info'])?$row['additional_info']:'', '아노다이징')!==false || strpos(isset($row['additional_info'])?$row['additional_info']:'', 'Ano')!==false)?'예':''; }
        else if ($lab === 'Non-engaging 적용') { $val = (strpos(isset($row['additional_info'])?$row['additional_info']:'', 'Non-engaging')!==false || strpos(isset($row['additional_info'])?$row['additional_info']:'', 'Non-Eng')!==false)?'예':''; }
        else if ($lab === '픽업 희망일') { $val = isset($row['delivery_hope_date']) ? $row['delivery_hope_date'] : ''; }
        else if ($lab === '내용') { $val = isset($row['additional_info']) ? $row['additional_info'] : ''; }
        else { $val = (string)pl_value_by_header($lab, $row); if ($val === '' && isset($row[$lab])) { $val = (string)$row[$lab]; } }
        $sheet->setCellValueByColumnAndRow($col, $rowIndex, $val);
    }
    $rowIndex++;
}

// 응답 헤더 및 출력
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="exam.xlsx"');
header('Cache-Control: max-age=0');
$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
$writer->save('php://output');
exit;
