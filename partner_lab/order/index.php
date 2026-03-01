<?php
include_once '../_common.php';

// 주문 페이지 설정/DB 함수 폴백 (상대경로/서버 환경 차이 대응, PHP 5.x 호환)
// 1) 로컬 디렉터리의 config.php가 있다면 우선 포함
if (file_exists(dirname(__FILE__) . '/config.php')) {
    include_once dirname(__FILE__) . '/config.php';
}
// 2) admin 쪽 config.php에 get_partner_lab_db_connection이 정의되어 있을 수 있음
if (!function_exists('get_partner_lab_db_connection')) {
    $admin_cfg = dirname(__FILE__) . '/../partner_admin/config.php';
    if (file_exists($admin_cfg)) {
        include_once $admin_cfg;
    }
}
// 3) 최종 폴백: order/db_config.php의 getDBConnection을 래핑
if (!function_exists('get_partner_lab_db_connection')) {
    $order_db_cfg = dirname(__FILE__) . '/db_config.php';
    if (file_exists($order_db_cfg)) {
        include_once $order_db_cfg;
    }
    if (function_exists('getDBConnection')) {
        function get_partner_lab_db_connection() {
            return getDBConnection();
        }
    }
}

// 로그인 체크: G5 공통 변수 사용
if (!$is_member) {
    // GNUBoard 상수(G5_URL)가 있다면 이를 기준으로 /bbs/login.php 사용
    $login_url = (defined('G5_URL') ? G5_URL.'/bbs/login.php' : '../bbs/login.php');
    if (function_exists('goto_url')) {
        goto_url($login_url, '로그인이 필요합니다.');
    } else {
        header('Location: '.$login_url);
        exit;
    }
}

$g5['title'] = '스트라우만 코리아 주문 시스템';
// GNUBoard 헤더가 있을 때만 포함 (로컬 단독 실행 안전)
if (defined('G5_PATH') && file_exists(G5_PATH.'/head.php')) {
    include_once G5_PATH.'/head.php';
}

// 파트너 주문 페이지에서는 기본 사이트 헤더/메뉴를 숨김 처리
echo '<style>#hd, #hd_wrap, #tnb, #snb, #gnb, .gnb, .lnb, .menu, .gnb_wrap, .hd, .top_menu, #hd_menu_all { display: none !important; }</style>';

// jQuery 안전 로더: 없을 때만 동적으로 주입 (CDN)
echo '<script>(function(){if(!window.jQuery){var s=document.createElement("script");s.src="https://code.jquery.com/jquery-3.6.0.min.js";s.integrity="sha256-/xUj+3OJ+Y7C3eZ9zq3N1Jw4E6CkA9vYXsC3x6tJ5vY=";s.crossOrigin="anonymous";document.head.appendChild(s);}})();</script>';

// 세션에서 기존 데이터 가져오기
$order_data = get_order_session_data();

// 각 단계별 데이터 초기화
$step1_data = isset($order_data['step1']) ? $order_data['step1'] : array();
$step2_data = isset($order_data['step2']) ? $order_data['step2'] : array();
$step3_data = isset($order_data['step3']) ? $order_data['step3'] : array();
$step4_data = isset($order_data['step4']) ? $order_data['step4'] : array();
$step5_data = isset($order_data['step5']) ? $order_data['step5'] : array();
$step6_data = isset($order_data['step6']) ? $order_data['step6'] : array();

// Step 1 데이터
$customer_name = isset($step1_data['customer_name']) ? $step1_data['customer_name'] : $member['mb_name'];
$customer_phone = isset($step1_data['customer_phone']) ? $step1_data['customer_phone'] : $member['mb_hp'];
$customer_email = isset($step1_data['customer_email']) ? $step1_data['customer_email'] : $member['mb_email'];
$company_name = isset($step1_data['company_name']) ? $step1_data['company_name'] : '';
$region = isset($step1_data['region']) ? $step1_data['region'] : '';
$shipping_name = isset($step1_data['shipping_name']) ? $step1_data['shipping_name'] : $customer_name;
$shipping_phone = isset($step1_data['shipping_phone']) ? $step1_data['shipping_phone'] : $customer_phone;
$shipping_postcode = isset($step1_data['shipping_postcode']) ? $step1_data['shipping_postcode'] : '';
$shipping_address = isset($step1_data['shipping_address']) ? $step1_data['shipping_address'] : '';
$shipping_detail = isset($step1_data['shipping_detail']) ? $step1_data['shipping_detail'] : '';
$agreement = isset($step1_data['agreement']) ? $step1_data['agreement'] : '';

// 회원 기본 배송정보 불러오기 (있을 경우)
include_once dirname(__FILE__) . '/db_config.php';
// DB 유틸 포함 (기본키 유연 처리 등)
if (file_exists(dirname(__FILE__) . '/db_utils.php')) {
    include_once dirname(__FILE__) . '/db_utils.php';
}
$db = getDBConnection();
if ($db && !empty($member['mb_id'])) {
    try {
        $setting_key = 'shipping_defaults_' . $member['mb_id'];
        $setting_value = '';

        // prepare 후 드라이버에 맞게 안전 처리
        $stmt = $db->prepare("SELECT setting_value FROM partner_lab_system_settings WHERE setting_key = ? LIMIT 1");
        if ($stmt) {
            // PDO 사용 시
            if (class_exists('PDOStatement') && $stmt instanceof PDOStatement) {
                $stmt->execute(array($setting_key));
                if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $setting_value = isset($row['setting_value']) ? $row['setting_value'] : '';
                }
            }
            // mysqli 사용 시
            else if (class_exists('mysqli_stmt') && $stmt instanceof mysqli_stmt) {
                $stmt->bind_param('s', $setting_key);
                $stmt->execute();
                // 단일 컬럼 스칼라 값 추출
                $stmt->bind_result($setting_value);
                $stmt->fetch();
                $stmt->close();
            }
            // 기타: 가능하면 직접 쿼리로 폴백
            else {
                if (class_exists('mysqli') && $db instanceof mysqli) {
                    $key_esc = $db->real_escape_string($setting_key);
                    $sql = "SELECT setting_value FROM partner_lab_system_settings WHERE setting_key = '".$key_esc."' LIMIT 1";
                    if ($res = $db->query($sql)) {
                        if ($row = $res->fetch_assoc()) {
                            $setting_value = isset($row['setting_value']) ? $row['setting_value'] : '';
                        }
                        $res->free();
                    }
                }
            }
        }

        if ($setting_value !== '') {
            $defaults = json_decode($setting_value, true);
            if (is_array($defaults)) {
                if (!$shipping_name) $shipping_name = isset($defaults['shipping_name']) ? $defaults['shipping_name'] : $shipping_name;
                if (!$shipping_phone) $shipping_phone = isset($defaults['shipping_phone']) ? $defaults['shipping_phone'] : $shipping_phone;
                if (!$shipping_postcode) $shipping_postcode = isset($defaults['shipping_postcode']) ? $defaults['shipping_postcode'] : $shipping_postcode;
                if (!$shipping_address) $shipping_address = isset($defaults['shipping_address']) ? $defaults['shipping_address'] : $shipping_address;
                if (!$shipping_detail) $shipping_detail = isset($defaults['shipping_detail']) ? $defaults['shipping_detail'] : $shipping_detail;
            }
        }
    } catch (Exception $e) {
        // ignore load errors
    }
}

// 임시저장 데이터 자동 로드 (세션 기반)
try {
    // 확인 페이지에서 돌아온 편집 모드라면 자동저장 적용을 비활성화하여 DB 최신값을 우선합니다
    $ref = isset($_GET['ref']) ? strtolower($_GET['ref']) : '';
    $skip_autosave = ($ref === 'confirm');
    if ($db) {
        // 현재 세션 ID로 저장된 임시 데이터 조회
        $session_id = session_id();
        $autosave_row = null;
        // 공용 SQL
        $sql_autosave = "SELECT * FROM partner_lab_order_sessions WHERE session_id = ? LIMIT 1";
        // PDO
        if (class_exists('PDO') && $db instanceof PDO) {
            $stmt = $db->prepare($sql_autosave);
            $stmt->execute(array($session_id));
            $autosave_row = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        // MySQLi: get_result를 사용하지 않고 안전한 쿼리로 대체
        else if (class_exists('mysqli') && $db instanceof mysqli) {
            $sid_esc = $db->real_escape_string($session_id);
            $sql = "SELECT * FROM partner_lab_order_sessions WHERE session_id = '".$sid_esc."' LIMIT 1";
            if ($res = $db->query($sql)) {
                $autosave_row = $res->fetch_assoc();
                $res->free();
            }
        }

        // JSON 컬럼 자동 선택: data_json, form_data, session_data, session_json, data 중 첫 번째 값 사용
        if (!$skip_autosave && $autosave_row && is_array($autosave_row)) {
            $autosave_json = '';
            foreach (array('data_json','form_data','session_data','session_json','data') as $col) {
                if (isset($autosave_row[$col]) && $autosave_row[$col]) { $autosave_json = $autosave_row[$col]; break; }
            }
            if ($autosave_json) {
                $autosave = json_decode($autosave_json, true);
                if (is_array($autosave)) {
                    // Step 1: 고객/배송 정보
                    if (isset($autosave['customer_name'])) $customer_name = $autosave['customer_name'];
                    if (isset($autosave['customer_phone'])) $customer_phone = $autosave['customer_phone'];
                    if (isset($autosave['customer_email'])) $customer_email = $autosave['customer_email'];
                    if (isset($autosave['shipping_name'])) $shipping_name = $autosave['shipping_name'];
                    if (isset($autosave['shipping_phone'])) $shipping_phone = $autosave['shipping_phone'];
                    if (isset($autosave['shipping_postcode'])) $shipping_postcode = $autosave['shipping_postcode'];
                    if (isset($autosave['shipping_address'])) $shipping_address = $autosave['shipping_address'];
                    if (isset($autosave['shipping_detail'])) $shipping_detail = $autosave['shipping_detail'];
                    if (isset($autosave['agreement'])) $agreement = $autosave['agreement'];

                    // Step 2: 환자 정보
                    if (isset($autosave['patient_name'])) $patient_name = $autosave['patient_name'];
                    if (isset($autosave['patient_birth'])) $patient_birth = $autosave['patient_birth'];
                    if (isset($autosave['patient_gender'])) $patient_gender = $autosave['patient_gender'];

                    // Step 3: 작업/러버 인상체 배송
                    if (isset($autosave['delivery_preference'])) $delivery_preference = $autosave['delivery_preference'];
                    if (isset($autosave['dispatch_date'])) $dispatch_date = $autosave['dispatch_date'];
                    if (isset($autosave['lab_postcode'])) $lab_postcode = $autosave['lab_postcode'];
                    if (isset($autosave['lab_address'])) $lab_address = $autosave['lab_address'];
                    if (isset($autosave['lab_address_detail'])) $lab_address_detail = $autosave['lab_address_detail'];
                    if (isset($autosave['rubber_impression_delivery'])) $rubber_impression_delivery = $autosave['rubber_impression_delivery'];
                    if (isset($autosave['delivery_postcode'])) $delivery_postcode = $autosave['delivery_postcode'];
                    if (isset($autosave['delivery_address'])) $delivery_address = $autosave['delivery_address'];
                    if (isset($autosave['delivery_detail_address'])) $delivery_detail_address = $autosave['delivery_detail_address'];
                    if (isset($autosave['delivery_hope_date'])) $delivery_hope_date = $autosave['delivery_hope_date'];

                    // Step 4: 제품/음영
                    if (isset($autosave['zirconia_shade'])) $zirconia_shade = $autosave['zirconia_shade'];
                    if (isset($autosave['products']) && is_array($autosave['products'])) $products = $autosave['products'];

                    // Step 5: 선택 치아
                    if (isset($autosave['selected_teeth']) && is_array($autosave['selected_teeth'])) $selected_teeth = $autosave['selected_teeth'];

                    // Step 6: 기타/동의
                    if (isset($autosave['special_notes'])) $special_notes = $autosave['special_notes'];
                    if (isset($autosave['surgery_date'])) $surgery_date = $autosave['surgery_date'];
                    if (isset($autosave['final_agreement'])) $final_agreement = $autosave['final_agreement'];
                }
            }
        }
    }
} catch (Exception $e) {
    // 임시저장 로드 실패는 무시하고 기본값 유지
}

// 파일 폴백 로드는 더 이상 사용하지 않음

// 회원 주소 정보 (러버 인상체 배송지 자동 입력용)
$member_zip = trim((isset($member['mb_zip1']) ? $member['mb_zip1'] : '') . (isset($member['mb_zip2']) ? $member['mb_zip2'] : ''));
$member_addr1 = isset($member['mb_addr1']) ? $member['mb_addr1'] : '';
$member_addr2 = isset($member['mb_addr2']) ? $member['mb_addr2'] : '';

// Step 2 데이터
$patient_name = isset($step2_data['patient_name']) ? $step2_data['patient_name'] : '';
$patient_gender = isset($step2_data['patient_gender']) ? $step2_data['patient_gender'] : '';
$patient_birth = isset($step2_data['patient_birth']) ? $step2_data['patient_birth'] : '';

// Step 3 데이터
$delivery_preference = isset($step3_data['delivery_preference']) ? $step3_data['delivery_preference'] : '';
$dispatch_date = isset($step3_data['dispatch_date']) ? $step3_data['dispatch_date'] : '';
$lab_postcode = isset($step3_data['lab_postcode']) ? $step3_data['lab_postcode'] : '';
$lab_address = isset($step3_data['lab_address']) ? $step3_data['lab_address'] : '';
$lab_address_detail = isset($step3_data['lab_address_detail']) ? $step3_data['lab_address_detail'] : '';

// 러버 인상체 전달 데이터
$rubber_impression_delivery = isset($step3_data['rubber_impression_delivery']) ? $step3_data['rubber_impression_delivery'] : '';
$delivery_postcode = isset($step3_data['delivery_postcode']) ? $step3_data['delivery_postcode'] : '';
$delivery_address = isset($step3_data['delivery_address']) ? $step3_data['delivery_address'] : '';
$delivery_detail_address = isset($step3_data['delivery_detail_address']) ? $step3_data['delivery_detail_address'] : '';
$delivery_hope_date = isset($step3_data['delivery_hope_date']) ? $step3_data['delivery_hope_date'] : '';

// 납기 희망일 표시값 계산 (임시저장 데이터 기반 폴백)
// delivery_date가 별도로 저장되지 않았을 수 있으므로 dispatch_date 또는 delivery_hope_date로 폴백
$delivery_date = isset($delivery_date) ? $delivery_date : '';
if (empty($delivery_date)) {
    if (!empty($dispatch_date)) { $delivery_date = $dispatch_date; }
    elseif (!empty($delivery_hope_date)) { $delivery_date = $delivery_hope_date; }
}

// Step 4 데이터
$zirconia_shade = isset($step4_data['zirconia_shade']) ? $step4_data['zirconia_shade'] : '';
$products = isset($step4_data['products']) ? $step4_data['products'] : array();

// Step 5 데이터
$selected_teeth = isset($step5_data['selected_teeth']) ? $step5_data['selected_teeth'] : array();

// Step 6 데이터
$special_notes = isset($step6_data['special_notes']) ? $step6_data['special_notes'] : '';
$surgery_date = isset($step6_data['surgery_date']) ? $step6_data['surgery_date'] : '';
$final_agreement = isset($step6_data['final_agreement']) ? $step6_data['final_agreement'] : '';

// 기존 주문 수정 모드: GET order_id가 있으면 DB에서 기존 주문 데이터를 로드하여 폼에 적용
try {
    $editing_order_id = 0;
    if (isset($_GET['order_id'])) {
        $editing_order_id = intval($_GET['order_id']);
    }
    if ($db && $editing_order_id > 0) {
        // 기본키 컬럼 확인 (id 또는 order_id)
        $orderPk = 'order_id';
        if (function_exists('dbu_get_order_pk')) {
            // PHP 5.2 호환: elvis(?:) 연산자 사용 금지
            $tmpPk = dbu_get_order_pk($db);
            if (!$tmpPk) { $tmpPk = 'order_id'; }
            $orderPk = $tmpPk;
        }

        // 주문 메인 정보 로드
        $order_row = null;
        if (class_exists('PDO') && $db instanceof PDO) {
            $stmt = $db->prepare("SELECT * FROM partner_lab_orders WHERE `{$orderPk}` = ? LIMIT 1");
            $stmt->execute(array($editing_order_id));
            $order_row = $stmt->fetch(PDO::FETCH_ASSOC);
        } else if (class_exists('mysqli') && $db instanceof mysqli) {
            $sql = "SELECT * FROM partner_lab_orders WHERE `{$orderPk}` = ".intval($editing_order_id)." LIMIT 1";
            if ($res = $db->query($sql)) { $order_row = $res->fetch_assoc(); $res->free(); }
        }

        if (is_array($order_row)) {
            // Step 1
            $customer_name    = isset($order_row['customer_name']) ? $order_row['customer_name'] : $customer_name;
            $customer_phone   = isset($order_row['customer_phone']) ? $order_row['customer_phone'] : $customer_phone;
            $customer_email   = isset($order_row['customer_email']) ? $order_row['customer_email'] : $customer_email;
            $company_name     = isset($order_row['company_name']) ? $order_row['company_name'] : $company_name;
            $region           = isset($order_row['region']) ? $order_row['region'] : $region;
            $shipping_name    = isset($order_row['shipping_name']) ? $order_row['shipping_name'] : $shipping_name;
            $shipping_phone   = isset($order_row['shipping_phone']) ? $order_row['shipping_phone'] : $shipping_phone;
            $shipping_postcode= isset($order_row['shipping_postcode']) ? $order_row['shipping_postcode'] : $shipping_postcode;
            $shipping_address = isset($order_row['shipping_address']) ? $order_row['shipping_address'] : $shipping_address;
            $shipping_detail  = isset($order_row['shipping_detail']) ? $order_row['shipping_detail'] : $shipping_detail;
            $agreement        = isset($order_row['agreement']) ? $order_row['agreement'] : $agreement;

            // Step 2
            $patient_name   = isset($order_row['patient_name']) ? $order_row['patient_name'] : $patient_name;
            $patient_gender = isset($order_row['patient_gender']) ? $order_row['patient_gender'] : $patient_gender;
            $patient_birth  = isset($order_row['patient_birth']) ? $order_row['patient_birth'] : $patient_birth;
            // 편집 모드 UI: patient_birth(date) -> 나이 숫자로 변환하여 표시
            if (!empty($patient_birth) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $patient_birth)) {
                $year = intval(substr($patient_birth, 0, 4));
                $currentYear = intval(date('Y'));
                $age = $currentYear - $year;
                if ($age < 0) { $age = 0; }
                if ($age > 120) { $age = 120; }
                $patient_birth = (string)$age;
            }

            // Step 3
            $delivery_preference = isset($order_row['delivery_preference']) ? $order_row['delivery_preference'] : $delivery_preference;
            $dispatch_date       = isset($order_row['dispatch_date']) ? $order_row['dispatch_date'] : $dispatch_date;
            $lab_postcode        = isset($order_row['lab_postcode']) ? $order_row['lab_postcode'] : $lab_postcode;
            $lab_address         = isset($order_row['lab_address']) ? $order_row['lab_address'] : $lab_address;
            $lab_address_detail  = isset($order_row['lab_address_detail']) ? $order_row['lab_address_detail'] : $lab_address_detail;
            $rubber_impression_delivery = isset($order_row['rubber_impression_delivery']) ? $order_row['rubber_impression_delivery'] : (isset($rubber_impression_delivery)?$rubber_impression_delivery:'');
            $delivery_postcode   = isset($order_row['delivery_postcode']) ? $order_row['delivery_postcode'] : (isset($delivery_postcode)?$delivery_postcode:'');
            $delivery_address    = isset($order_row['delivery_address']) ? $order_row['delivery_address'] : (isset($delivery_address)?$delivery_address:'');
            $delivery_detail_address = isset($order_row['delivery_detail_address']) ? $order_row['delivery_detail_address'] : (isset($delivery_detail_address)?$delivery_detail_address:'');
            $delivery_hope_date  = isset($order_row['delivery_hope_date']) ? $order_row['delivery_hope_date'] : (isset($delivery_hope_date)?$delivery_hope_date:'');
            // 납기 희망일 복원: delivery_date 우선, 없으면 dispatch_date 폴백
            $delivery_date = (isset($order_row['delivery_date']) && $order_row['delivery_date'])
                ? $order_row['delivery_date']
                : (isset($order_row['dispatch_date']) ? $order_row['dispatch_date'] : (isset($delivery_date) ? $delivery_date : ''));

            // Step 4
            $zirconia_shade   = isset($order_row['zirconia_shade']) ? $order_row['zirconia_shade'] : $zirconia_shade;
            $inlay_onlay_type = isset($order_row['inlay_onlay_type']) ? $order_row['inlay_onlay_type'] : (isset($inlay_onlay_type)?$inlay_onlay_type:'');
            if (isset($order_row['products']) && $order_row['products'] !== '') {
                $tmp_products = json_decode($order_row['products'], true);
                if (is_array($tmp_products)) { $products = $tmp_products; }
            }

            // Step 5: 선택 치아 (JSON 컬럼 또는 별도 테이블)
            // 유효 치아 번호 판정: FDI 11-18, 21-28, 31-38, 41-48
            if (!function_exists('pl_is_valid_tooth')) {
                function pl_is_valid_tooth($n) {
                    if (!is_numeric($n)) return false;
                    $n = intval($n, 10);
                    $ten = intval($n / 10);
                    $one = $n % 10;
                    return ($ten >= 1 && $ten <= 4 && $one >= 1 && $one <= 8);
                }
            }
            // PHP 5.2 호환: array_filter/array_map에 사용할 명명 함수 정의
            if (!function_exists('pl_filter_valid_tooth')) {
                function pl_filter_valid_tooth($v) { return pl_is_valid_tooth($v); }
            }
            if (!function_exists('pl_intval10')) {
                function pl_intval10($v) { return intval($v, 10); }
            }
            $loaded_teeth = array();
            if (isset($order_row['selected_teeth']) && $order_row['selected_teeth'] !== '') {
                $raw = $order_row['selected_teeth'];
                $teeth_json = json_decode($raw, true);
                if (is_array($teeth_json)) { $loaded_teeth = $teeth_json; }
                else if (is_string($raw)) {
                    // CSV 형태 혹은 공백 포함 문자열일 경우 파싱
                    $parts = preg_split('/\s*,\s*/', trim($raw));
                    foreach ($parts as $p) { if (is_numeric($p)) $loaded_teeth[] = intval($p, 10); }
                }
                // 숫자 배열 + 유효 치아 번호만 유지 (PHP 5.2 호환 명명 함수 사용)
                $loaded_teeth = array_values(array_filter($loaded_teeth, 'pl_filter_valid_tooth'));
                $loaded_teeth = array_values(array_unique(array_map('pl_intval10', $loaded_teeth)));
                sort($loaded_teeth);
            }
            // partner_lab_order_teeth 테이블에서 보완 로드
            if (empty($loaded_teeth)) {
                if (class_exists('PDO') && $db instanceof PDO) {
                    $tstmt = $db->prepare("SELECT tooth_number FROM partner_lab_order_teeth WHERE order_id = ? ORDER BY tooth_number ASC");
                    $tstmt->execute(array($editing_order_id));
                    $rows = $tstmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as $r) { $loaded_teeth[] = intval(isset($r['tooth_number'])?$r['tooth_number']:0); }
                } else if (class_exists('mysqli') && $db instanceof mysqli) {
                    $tsql = "SELECT tooth_number FROM partner_lab_order_teeth WHERE order_id = ".intval($editing_order_id)." ORDER BY tooth_number ASC";
                    if ($tres = $db->query($tsql)) {
                        while ($tr = $tres->fetch_assoc()) { $loaded_teeth[] = intval(isset($tr['tooth_number'])?$tr['tooth_number']:0); }
                        $tres->free();
                    }
                }
            }
            // details 테이블 최종 폴백 (확인 페이지에서만 생성된 경우를 대비)
            if (empty($loaded_teeth)) {
                if (class_exists('PDO') && $db instanceof PDO) {
                    $tstmt = $db->prepare("SELECT tooth_number FROM partner_lab_order_teeth_details WHERE order_id = ? ORDER BY tooth_number ASC");
                    try { $tstmt->execute(array($editing_order_id)); } catch (Exception $ignored) {}
                    if (isset($tstmt)) {
                        $rows = $tstmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($rows as $r) { $loaded_teeth[] = intval(isset($r['tooth_number'])?$r['tooth_number']:0); }
                    }
                } else if (class_exists('mysqli') && $db instanceof mysqli) {
                    $tsql = "SELECT tooth_number FROM partner_lab_order_teeth_details WHERE order_id = ".intval($editing_order_id)." ORDER BY tooth_number ASC";
                    if ($tres = @$db->query($tsql)) {
                        while ($tr = $tres->fetch_assoc()) { $loaded_teeth[] = intval(isset($tr['tooth_number'])?$tr['tooth_number']:0); }
                        $tres->free();
                    }
                }
            }
            if (!empty($loaded_teeth)) { $selected_teeth = $loaded_teeth; }

            // Step 6
            $special_notes    = isset($order_row['special_notes']) ? $order_row['special_notes'] : $special_notes;
            $surgery_date     = isset($order_row['surgery_date']) ? $order_row['surgery_date'] : $surgery_date;
            $final_agreement  = isset($order_row['final_agreement']) ? $order_row['final_agreement'] : $final_agreement;

            // 치아 상세 옵션 및 모드/브릿지 그룹 초기화 (편집 시 복원)
            $existing_cfg = array();
            if (isset($order_row['teeth_configurations']) && $order_row['teeth_configurations'] !== '') {
                $tmp_cfg = json_decode($order_row['teeth_configurations'], true);
                if (is_array($tmp_cfg)) {
                    // 평탄화 처리: { tooth_options: { 14: {system:...} } } 형태를
                    // { 'tooth_options[14][system]': '...' } 형태로 변환하여 프리필과 호환
                    if (isset($tmp_cfg['tooth_options']) && is_array($tmp_cfg['tooth_options'])) {
                        $flat = array();
                        foreach ($tmp_cfg['tooth_options'] as $tn => $opts) {
                            if (!is_array($opts)) continue;
                            foreach ($opts as $k => $v) { $flat['tooth_options['.$tn.']['.$k.']'] = $v; }
                        }
                        $existing_cfg = $flat;
                    } else {
                        // 대안: 루트에 숫자 키(치아 번호)가 직접 있는 구조를 평탄화
                        $flat = array();
                        foreach ($tmp_cfg as $maybeTooth => $opts) {
                            if ((is_int($maybeTooth) || (is_string($maybeTooth) && preg_match('/^\d+$/', $maybeTooth))) && is_array($opts)) {
                                $tn = intval($maybeTooth, 10);
                                foreach ($opts as $k => $v) { $flat['tooth_options['.$tn.']['.$k.']'] = $v; }
                            }
                        }
                        $existing_cfg = !empty($flat) ? $flat : $tmp_cfg;
                    }
                }
            }
            // 보강: 상세 테이블(partner_lab_order_teeth_details)에서 옵션을 조회해 기존 구성(existing_cfg)에 병합
            // 주문 JSON이 존재하더라도 누락된 필드를 상세에서 채워 초기화 문제를 방지
            try {
                $detail_rows = array();
                if (class_exists('PDO') && $db instanceof PDO) {
                    $ds = $db->prepare("SELECT tooth_number, system_spec, margin_level, final_prosthetic, special_notes FROM partner_lab_order_teeth_details WHERE order_id = ?");
                    $ds->execute(array($editing_order_id));
                    $detail_rows = $ds->fetchAll(PDO::FETCH_ASSOC);
                } else if (class_exists('mysqli') && $db instanceof mysqli) {
                    $stmt = mysqli_prepare($db, "SELECT tooth_number, system_spec, margin_level, final_prosthetic, special_notes FROM partner_lab_order_teeth_details WHERE order_id = ?");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'i', $editing_order_id);
                        mysqli_stmt_execute($stmt);
                        $meta = mysqli_stmt_result_metadata($stmt);
                        $fields = array(); $bind = array(); $bindArgs = array($stmt);
                        if ($meta) {
                            while ($f = mysqli_fetch_field($meta)) { $fields[] = $f->name; $bind[$f->name] = null; $bindArgs[] = &$bind[$f->name]; }
                            call_user_func_array('mysqli_stmt_bind_result', $bindArgs);
                            while (mysqli_stmt_fetch($stmt)) { $row = array(); foreach ($fields as $name) { $row[$name] = $bind[$name]; } $detail_rows[] = $row; }
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
                if (!empty($detail_rows)) {
                    // 임플란트 시스템 고정 목록 (드롭다운 옵션과 동일)
                    $KNOWN_SYSTEMS = array(
                        'Straumann BLX RB','Straumann BLX WB','Straumann TLX RT','Straumann TLX WT',
                        'Straumann Bone level NC','Straumann Bone level RC',
                        'Straumann Tissue level RN','Straumann Tissue level WN',
                    );
                    foreach ($detail_rows as $dr) {
                        $tn = isset($dr['tooth_number']) ? intval($dr['tooth_number'], 10) : 0;
                        if ($tn <= 0) continue;
                        $kSystem = 'tooth_options['.$tn.'][system]';
                        $kMargin = 'tooth_options['.$tn.'][margin]';
                        $kPros   = 'tooth_options['.$tn.'][prosthetic]';
                        $kShade  = 'tooth_options['.$tn.'][shade]';
                        // 시스템/마진/보철 병합: 기존 값이 비어있을 때만 채움
                        if (!empty($dr['system_spec']) && (!isset($existing_cfg[$kSystem]) || $existing_cfg[$kSystem] === '')) {
                            $sysSpec = trim((string)$dr['system_spec']);
                            if ($sysSpec !== '' && !in_array($sysSpec, $KNOWN_SYSTEMS, true)) {
                                // 드롭다운 목록에 없는 값이면 Others로 설정하고 텍스트 입력에 보강
                                $existing_cfg[$kSystem] = 'Others';
                                $existing_cfg['tooth_options['.$tn.'][system_other]'] = $sysSpec;
                            } else {
                                $existing_cfg[$kSystem] = $sysSpec;
                            }
                        }
                        if (!empty($dr['margin_level']) && (!isset($existing_cfg[$kMargin]) || $existing_cfg[$kMargin] === '')) {
                            $existing_cfg[$kMargin] = $dr['margin_level'];
                        }
                        if (!empty($dr['final_prosthetic']) && (!isset($existing_cfg[$kPros]) || $existing_cfg[$kPros] === '')) {
                            $existing_cfg[$kPros] = $dr['final_prosthetic'];
                        }
                        // 특이사항에서 shade/flags 파싱하여 병합
                        $notes = isset($dr['special_notes']) ? (string)$dr['special_notes'] : '';
                        if ($notes !== '') {
                            if (preg_match('/shade=([^;]+)/', $notes, $m)) {
                                if (!isset($existing_cfg[$kShade]) || $existing_cfg[$kShade] === '') {
                                    $existing_cfg[$kShade] = trim($m[1]);
                                }
                            }
                            if (preg_match('/flags=([^;]+)/', $notes, $m2)) {
                                $flagStr = trim($m2[1]);
                                $flags = array_map('trim', explode(',', $flagStr));
                                foreach ($flags as $f) {
                                    if (strcasecmp($f, 'PMAB') === 0) { $existing_cfg['tooth_options['.$tn.'][pmab]'] = '1'; }
                                    else if (strcasecmp($f, 'Screw') === 0) { $existing_cfg['tooth_options['.$tn.'][screw]'] = '1'; }
                                    else if (strcasecmp($f, 'Ano') === 0) { $existing_cfg['tooth_options['.$tn.'][anodizing]'] = '1'; }
                                    else if (strcasecmp($f, 'Non-Eng') === 0) { $existing_cfg['tooth_options['.$tn.'][non_engaging]'] = '1'; }
                                }
                            }
                        }
                    }
                }
            } catch (Exception $ignored) {
                // 병합 실패 시에도 화면은 계속 표시
            }
            // 폴백: 주문 JSON이 비어 있는 경우 DB 테이블/자동저장에서 옵션을 재구성하여 프리필에 사용
            if (empty($existing_cfg)) {
                $tooth_options_map = array();
                // 1차: auto_save_data에서 브래킷 키 또는 배열형 옵션 복원
                if (isset($order_row['auto_save_data']) && $order_row['auto_save_data'] !== '') {
                    $snap = json_decode($order_row['auto_save_data'], true);
                    if (is_array($snap)) {
                        foreach ($snap as $k => $v) {
                            if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[([^\]]+)\]$/', $k, $m)) {
                                $tn = intval($m[1], 10);
                                $optk = $m[2];
                                if (!isset($tooth_options_map[$tn])) $tooth_options_map[$tn] = array();
                                $tooth_options_map[$tn][$optk] = $v;
                            }
                        }
                        if (isset($snap['tooth_options']) && is_array($snap['tooth_options'])) {
                            foreach ($snap['tooth_options'] as $tn => $opts) {
                                if (!isset($tooth_options_map[$tn])) $tooth_options_map[$tn] = array();
                                if (is_array($opts)) {
                                    foreach ($opts as $optk => $optv) { $tooth_options_map[$tn][$optk] = $optv; }
                                }
                            }
                        }
                    }
                }
                // 2차: 정규화 테이블(partner_lab_order_teeth_details)에서 시스템/마진/보철/음영/플래그 복원
                if (empty($tooth_options_map)) {
                    try {
                        if (class_exists('PDO') && $db instanceof PDO) {
                            $ds = $db->prepare("SELECT tooth_number, system_spec, margin_level, final_prosthetic, special_notes FROM partner_lab_order_teeth_details WHERE order_id = ?");
                            $ds->execute(array($editing_order_id));
                            $drows = $ds->fetchAll(PDO::FETCH_ASSOC);
                        } else if (class_exists('mysqli') && $db instanceof mysqli) {
                            $drows = array();
                            $stmt = mysqli_prepare($db, "SELECT tooth_number, system_spec, margin_level, final_prosthetic, special_notes FROM partner_lab_order_teeth_details WHERE order_id = ?");
                            if ($stmt) {
                                mysqli_stmt_bind_param($stmt, 'i', $editing_order_id);
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
                        // 임플란트 시스템 고정 목록 (드롭다운 옵션과 동일)
                        $KNOWN_SYSTEMS = array(
                            'Straumann BLX RB','Straumann BLX WB','Straumann TLX RT','Straumann TLX WT',
                            'Straumann Bone level NC','Straumann Bone level RC',
                            'Straumann Tissue level RN','Straumann Tissue level WN',
                        );
                        foreach ($drows as $dr) {
                            $tn = isset($dr['tooth_number']) ? intval($dr['tooth_number'], 10) : 0;
                            if ($tn <= 0) continue;
                            if (!isset($tooth_options_map[$tn])) $tooth_options_map[$tn] = array();
                            if (!empty($dr['system_spec'])) {
                                $sysSpec = trim((string)$dr['system_spec']);
                                if ($sysSpec !== '' && !in_array($sysSpec, $KNOWN_SYSTEMS, true)) {
                                    $tooth_options_map[$tn]['system'] = 'Others';
                                    $tooth_options_map[$tn]['system_other'] = $sysSpec;
                                } else {
                                    $tooth_options_map[$tn]['system'] = $sysSpec;
                                }
                            }
                            if (!empty($dr['margin_level'])) { $tooth_options_map[$tn]['margin'] = $dr['margin_level']; }
                            if (!empty($dr['final_prosthetic'])) { $tooth_options_map[$tn]['prosthetic'] = $dr['final_prosthetic']; }
                            $notes = isset($dr['special_notes']) ? (string)$dr['special_notes'] : '';
                            if ($notes !== '') {
                                if (preg_match('/shade=([^;]+)/', $notes, $m)) { $tooth_options_map[$tn]['shade'] = trim($m[1]); }
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
                        // 폴백 실패 시 무시
                    }
                }
                // 3차: partner_lab_order_teeth의 tooth_type을 이용해 모드만 보조 복원 (싱글/브릿지)
                $type_map_for_mode = array();
                if (!empty($selected_teeth)) {
                    if (class_exists('PDO') && $db instanceof PDO) {
                        $tstmt2 = $db->prepare("SELECT tooth_number, tooth_type FROM partner_lab_order_teeth WHERE order_id = ?");
                        $tstmt2->execute(array($editing_order_id));
                        $trows2 = $tstmt2->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($trows2 as $tr2) { $type_map_for_mode[intval($tr2['tooth_number'])] = isset($tr2['tooth_type']) ? strtolower(trim($tr2['tooth_type'])) : ''; }
                    } else if (class_exists('mysqli') && $db instanceof mysqli) {
                        $tsql2 = "SELECT tooth_number, tooth_type FROM partner_lab_order_teeth WHERE order_id = ".intval($editing_order_id);
                        if ($tres2 = $db->query($tsql2)) {
                            while ($tr2 = $tres2->fetch_assoc()) { $type_map_for_mode[intval($tr2['tooth_number'])] = isset($tr2['tooth_type']) ? strtolower(trim($tr2['tooth_type'])) : ''; }
                            $tres2->free();
                        }
                    }
                }
                // 평탄화하여 existing_cfg로 제공 (UI 프리필 호환)
                if (!empty($tooth_options_map)) {
                    $flatCfg = array();
                    foreach ($tooth_options_map as $tn => $opts) {
                        if (!is_numeric($tn)) continue;
                        $tn = intval($tn, 10);
                        foreach ($opts as $optk => $optv) {
                            $flatCfg['tooth_options['.$tn.']['.$optk.']'] = $optv;
                        }
                        if (isset($type_map_for_mode[$tn]) && $type_map_for_mode[$tn] !== '') {
                            $mode = ($type_map_for_mode[$tn] === 'single') ? 'general' : $type_map_for_mode[$tn];
                            $flatCfg['tooth_options['.$tn.'][mode]'] = $mode;
                        }
                    }
                    $existing_cfg = $flatCfg;
                }
            }
            // 선택 치아가 비어 있고, 옵션 키가 존재한다면 키에서 치아 번호를 유추하여 복원
            if ((empty($selected_teeth) || !is_array($selected_teeth)) && !empty($existing_cfg) && is_array($existing_cfg)) {
                $derived = array();
                // 1) 평탄화된 키 형태: tooth_options[14][...]
                foreach ($existing_cfg as $k => $_) {
                    if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[/', $k, $m)) {
                        $derived[] = intval($m[1], 10);
                    }
                }
                // 2) 루트가 숫자 키인 형태: { "14": { ... }, "23": { ... } }
                if (empty($derived)) {
                    foreach ($existing_cfg as $k => $v) {
                        if ((is_int($k) || (is_string($k) && preg_match('/^\d+$/', $k))) && is_array($v)) {
                            $derived[] = intval($k, 10);
                        }
                    }
                }
                if (!empty($derived)) {
                    $derived = array_values(array_filter($derived, 'pl_filter_valid_tooth'));
                    $selected_teeth = array_values(array_unique($derived));
                    sort($selected_teeth);
                }
            }
            $existing_modes = array();
            $existing_groups = array();
            if (!empty($selected_teeth)) {
                // DB 테이블에서 tooth_type(싱글/브릿지 모드 저장)을 보조 로드
                $type_map = array();
                if (class_exists('PDO') && $db instanceof PDO) {
                    $tstmt2 = $db->prepare("SELECT tooth_number, tooth_type FROM partner_lab_order_teeth WHERE order_id = ?");
                    $tstmt2->execute(array($editing_order_id));
                    $trows2 = $tstmt2->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($trows2 as $tr2) { $type_map[intval($tr2['tooth_number'])] = isset($tr2['tooth_type']) ? $tr2['tooth_type'] : ''; }
                } else if (class_exists('mysqli') && $db instanceof mysqli) {
                    $tsql2 = "SELECT tooth_number, tooth_type FROM partner_lab_order_teeth WHERE order_id = ".intval($editing_order_id);
                    if ($tres2 = $db->query($tsql2)) {
                        while ($tr2 = $tres2->fetch_assoc()) { $type_map[intval($tr2['tooth_number'])] = isset($tr2['tooth_type']) ? $tr2['tooth_type'] : ''; }
                        $tres2->free();
                    }
                }
                foreach ($selected_teeth as $tn) {
                    $modeKey = 'tooth_options['.$tn.'][mode]';
                    $groupKey = 'tooth_options['.$tn.'][bridge_group]';
                    // JSON 구성에서 모드/그룹 추출
                    foreach ($existing_cfg as $k => $v) {
                        if ($k === $modeKey) { $existing_modes[$tn] = $v; }
                        else if ($k === $groupKey) { $existing_groups[$tn] = $v; }
                    }
                    // 보조 맵: DB 저장된 tooth_type(일반/브릿지)을 사용, general -> single 로 변환은 서버측에서 처리됨
                    if (!isset($existing_modes[$tn]) && isset($type_map[$tn]) && $type_map[$tn] !== '') {
                        // UI에서는 '싱글'을 'general'로 사용하므로 변환
                        $existing_modes[$tn] = ($type_map[$tn] === 'single') ? 'general' : $type_map[$tn];
                    }
                }
            }
            
            // 글로벌 체크박스 옵션 상태 결정: 기존에 로드된 치아 옵션에서 전역 상태 유추
            $global_pmab_checked = false;
            $global_screw_checked = false;
            $global_anodizing_checked = false;
            $global_non_engaging_checked = false;
            
            if (!empty($existing_cfg) && is_array($existing_cfg)) {
                $pmab_count = 0;
                $screw_count = 0;
                $anodizing_count = 0;
                $non_engaging_count = 0;
                $total_teeth = 0;
                
                // 각 치아의 옵션 상태 확인
                foreach ($selected_teeth as $tn) {
                    $total_teeth++;
                    if (isset($existing_cfg['tooth_options['.$tn.'][pmab]']) && $existing_cfg['tooth_options['.$tn.'][pmab]'] === '1') {
                        $pmab_count++;
                    }
                    if (isset($existing_cfg['tooth_options['.$tn.'][screw]']) && $existing_cfg['tooth_options['.$tn.'][screw]'] === '1') {
                        $screw_count++;
                    }
                    if (isset($existing_cfg['tooth_options['.$tn.'][anodizing]']) && $existing_cfg['tooth_options['.$tn.'][anodizing]'] === '1') {
                        $anodizing_count++;
                    }
                    if (isset($existing_cfg['tooth_options['.$tn.'][non_engaging]']) && $existing_cfg['tooth_options['.$tn.'][non_engaging]'] === '1') {
                        $non_engaging_count++;
                    }
                }
                
                // 대부분의 치아에 옵션이 적용되어 있으면 글로벌 체크박스를 체크 (50% 이상)
                if ($total_teeth > 0) {
                    $global_pmab_checked = ($pmab_count >= ceil($total_teeth / 2));
                    $global_screw_checked = ($screw_count >= ceil($total_teeth / 2));
                    $global_anodizing_checked = ($anodizing_count >= ceil($total_teeth / 2));
                    $global_non_engaging_checked = ($non_engaging_count >= ceil($total_teeth / 2));
                }
            }
            
            echo '<script>window.existingToothConfig='.json_encode($existing_cfg).';window.toothModes='.json_encode($existing_modes).';window.toothGroupId='.json_encode($existing_groups).';window.isEditingExistingOrder=true;window.editingOrderId='.intval($editing_order_id).';</script>';
            echo '<script>window.globalPmabChecked='.($global_pmab_checked ? 'true' : 'false').';window.globalScrewChecked='.($global_screw_checked ? 'true' : 'false').';window.globalAnodizingChecked='.($global_anodizing_checked ? 'true' : 'false').';window.globalNonEngagingChecked='.($global_non_engaging_checked ? 'true' : 'false').';</script>';

            // 저장 폴백: teeth_configurations JSON이 비어 있고, 옵션 맵을 재구성했다면 orders 테이블에 반영하여 페이지 간 일관성 유지
            if (empty($order_row['teeth_configurations']) && !empty($existing_cfg)) {
                try {
                    $cfgJson = json_encode($existing_cfg);
                    if (class_exists('PDO') && $db instanceof PDO) {
                        $ust = $db->prepare('UPDATE `partner_lab_orders` SET `teeth_configurations` = ?, `updated_at` = NOW() WHERE `'.$orderPk.'` = ?');
                        $ust->execute(array($cfgJson, $editing_order_id));
                    } else if (class_exists('mysqli') && $db instanceof mysqli) {
                        $cfgEsc = $db->real_escape_string($cfgJson);
                        $usql = 'UPDATE `partner_lab_orders` SET `teeth_configurations` = \''.$cfgEsc.'\', `updated_at` = NOW() WHERE `'.$orderPk.'` = '.intval($editing_order_id);
                        @$db->query($usql);
                    }
                } catch (Exception $e) {
                    // 저장 실패는 무시하고 화면 표시 계속
                }
            }

            // 첨부 파일 목록 로드 → 초기 렌더링용 전역 변수로 제공 (날짜 컬럼 감지)
            $existing_files = array();
            $dateCol = 'created_at';
            try {
                if (class_exists('PDO') && $db instanceof PDO) {
                    $cols = $db->query("SHOW COLUMNS FROM `partner_lab_order_files`");
                    if ($cols) {
                        foreach ($cols as $c) { if ($c['Field'] === 'uploaded_at') { $dateCol = 'uploaded_at'; break; } }
                    }
                    $fstmt = $db->prepare("SELECT file_id, original_name, stored_name AS file_name, file_path, file_size FROM partner_lab_order_files WHERE order_id = ? ORDER BY `".$dateCol."` ASC");
                    $fstmt->execute(array($editing_order_id));
                    $frows = $fstmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($frows as $fr) { $existing_files[] = $fr; }
                } else if (class_exists('mysqli') && $db instanceof mysqli) {
                    $colsRes = $db->query("SHOW COLUMNS FROM `partner_lab_order_files`");
                    if ($colsRes) { while ($c = $colsRes->fetch_assoc()) { if ($c['Field'] === 'uploaded_at') { $dateCol = 'uploaded_at'; break; } } $colsRes->free(); }
                    $fsql = "SELECT file_id, original_name, stored_name AS file_name, file_path, file_size FROM partner_lab_order_files WHERE order_id = ".intval($editing_order_id)." ORDER BY `".$dateCol."` ASC";
                    if ($fres = $db->query($fsql)) {
                        while ($fr = $fres->fetch_assoc()) { $existing_files[] = $fr; }
                        $fres->free();
                    }
                }
            } catch (Exception $e) { /* ignore */ }

            if (!empty($existing_files)) {
                echo '<script>window.existingFiles=' . json_encode($existing_files) . ';</script>';
            } else {
                echo '<script>window.existingFiles=[];</script>';
            }
        }
    }
} catch (Exception $e) {
    // 기존 주문 로드 실패는 무시하고 새 주문 플로우 유지
}
?>

<!-- Straumann Header -->
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

<div class="onepage-container">
    <!-- 진행 표시기 -->
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>
        <div class="progress-steps">
            <div class="progress-step" data-step="1" onclick="scrollToSection(1)">
                <div class="progress-step-circle">1</div>
                <span class="progress-step-title">주문정보</span>
                <div class="progress-step-line"></div>
            </div>
            <div class="progress-step" data-step="2" onclick="scrollToSection(2)">
                <div class="progress-step-circle">2/3</div>
                <span class="progress-step-title">환자정보/치식설정</span>
                <div class="progress-step-line"></div>
            </div>
            <div class="progress-step" data-step="3" onclick="scrollToSection(4)">
                <div class="progress-step-circle">4</div>
                <span class="progress-step-title">디지털 임프레션/러버 임프레션</span>
                <div class="progress-step-line"></div>
            </div>
            <div class="progress-step" data-step="4" onclick="scrollToSection(5)">
                <div class="progress-step-circle">5</div>
                <span class="progress-step-title">기타사항</span>
            </div>
        </div>
    </div>

    <!-- 안내 박스 -->
    <section class="info-box" style="max-width:1200px;margin:8px auto 20px auto;padding:12px;border:1px solid #d0d7de;background:#fff">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;font-family:inherit">
            <div style="min-width:160px;text-align:center">
                <img src="/img/p_logo.png" alt="쓰리포인트덴탈 로고" style="height:48px;width:auto">
            </div>
            <div style="text-align:left;flex:1 1 auto">
                <h3 style="margin:0 0 10px 0;color:#0f3060;font-weight:700;text-align:center">(주)쓰리포인트덴탈 안내</h3>
                <p style="margin:0 10px 10px 10px;line-height:1.7;color:#333;text-align:center;font-size:14px">(주)쓰리포인트덴탈은 15년 이상 디지털 덴티스트리의 경험을 기반으로 정밀하고 일관된 품질의 보철물을 제작하는 프리미엄 디지털 밀링 센터입니다.</p>
                <div style="background:#f9fbff;border:1px solid #e6edf8;padding:12px 14px;font-size:15px;line-height:1.8;color:#0f3060;display:flex;align-items:center;justify-content:space-between">
                    <div>
                        <div style="margin-bottom:6px"><strong>기공소명</strong> : ㈜쓰리포인트덴탈</div>
                        <div style="margin-bottom:6px"><strong>주소</strong> : 대전 유성구 테크노8로 44 2동 쓰리포인트덴탈</div>
                        <div><strong>제품 문의</strong> : 1855-2804, E-MAIL : order@3pointdental.com</div>
                    </div>
                    <div style="min-width:200px;text-align:right">
                        <a href="file_download.php?path=<?php echo urlencode('./'.'쓰리포인트덴탈 보철수가표 2025 (스트라우만).pdf'); ?>&name=<?php echo urlencode('쓰리포인트덴탈 보철수가표 2025 (스트라우만).pdf'); ?>" class="btn btn-secondary price-download-btn" style="min-width:200px;min-height:60px;display:inline-block;text-align:center;border:2px solid #2a7f62;color:#2a7f62;background:#fff;padding:6px 22px 32px 22px;font-size:15px;line-height:1.35">표준 가격표<br>다운로드</a>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

    <!-- 메인 폼 -->
    <form id="onePageOrderForm" method="post" action="./process.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="submit_order">
<input type="hidden" name="mb_id" value="<?php echo htmlspecialchars(isset($member['mb_id']) ? $member['mb_id'] : ''); ?>">
        <?php if (isset($editing_order_id) && $editing_order_id > 0) { ?>
        <input type="hidden" name="order_id" value="<?php echo (int)$editing_order_id; ?>">
        <?php } ?>
        
        <!-- Step 1: 주문정보 -->
        <section class="order-section" id="section-1" data-section="1">
            <div class="section-header">
                <h3><span class="section-number">1</span> 주문정보</h3>
            </div>
            
            <div class="section-content">
                <div class="form-subsection">
                    <h4>고객 정보</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_name">고객명 <span class="required">*</span></label>
                            <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="company_name">소속 (병원명)</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="customer_phone">연락처 <span class="required">*</span></label>
                            <input type="tel" id="customer_phone" name="customer_phone" value="<?php echo htmlspecialchars($customer_phone); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="customer_email">이메일</label>
                            <input type="email" id="customer_email" name="customer_email" value="<?php echo htmlspecialchars($customer_email); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="region">지역</label>
                            <select id="region" name="region">
                                <option value="">지역 선택</option>
                                <option value="서울" <?php echo $region == '서울' ? 'selected' : ''; ?>>서울</option>
                                <option value="경기" <?php echo $region == '경기' ? 'selected' : ''; ?>>경기</option>
                                <option value="인천" <?php echo $region == '인천' ? 'selected' : ''; ?>>인천</option>
                                <option value="부산" <?php echo $region == '부산' ? 'selected' : ''; ?>>부산</option>
                                <option value="대구" <?php echo $region == '대구' ? 'selected' : ''; ?>>대구</option>
                                <option value="광주" <?php echo $region == '광주' ? 'selected' : ''; ?>>광주</option>
                                <option value="대전" <?php echo $region == '대전' ? 'selected' : ''; ?>>대전</option>
                                <option value="울산" <?php echo $region == '울산' ? 'selected' : ''; ?>>울산</option>
                                <option value="강원" <?php echo $region == '강원' ? 'selected' : ''; ?>>강원</option>
                                <option value="충북" <?php echo $region == '충북' ? 'selected' : ''; ?>>충북</option>
                                <option value="충남" <?php echo $region == '충남' ? 'selected' : ''; ?>>충남</option>
                                <option value="전북" <?php echo $region == '전북' ? 'selected' : ''; ?>>전북</option>
                                <option value="전남" <?php echo $region == '전남' ? 'selected' : ''; ?>>전남</option>
                                <option value="경북" <?php echo $region == '경북' ? 'selected' : ''; ?>>경북</option>
                                <option value="경남" <?php echo $region == '경남' ? 'selected' : ''; ?>>경남</option>
                                <option value="제주" <?php echo $region == '제주' ? 'selected' : ''; ?>>제주</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-subsection">
                    <div class="section-title-with-checkbox">
                        <h4>배송 정보</h4>
                        <label class="checkbox-inline-large">
                            <input type="checkbox" id="same_as_customer" <?php echo $shipping_name == $customer_name ? 'checked' : ''; ?>>
                            고객 정보와 동일
                        </label>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_name">받는 분 <span class="required">*</span></label>
                            <input type="text" id="shipping_name" name="shipping_name" value="<?php echo htmlspecialchars($shipping_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_phone">연락처 <span class="required">*</span></label>
                            <input type="tel" id="shipping_phone" name="shipping_phone" value="<?php echo htmlspecialchars($shipping_phone); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="shipping_postcode">우편번호 <span class="required">*</span></label>
                            <div class="postcode-group">
                                <input type="text" id="shipping_postcode" name="shipping_postcode" value="<?php echo htmlspecialchars($shipping_postcode); ?>" readonly required>
                                <button type="button" onclick="execDaumPostcode()" class="btn-postcode">우편번호 찾기</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="shipping_address">주소 <span class="required">*</span></label>
                            <input type="text" id="shipping_address" name="shipping_address" value="<?php echo htmlspecialchars($shipping_address); ?>" readonly required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="shipping_detail">상세주소 <span class="required">*</span></label>
                            <input type="text" id="shipping_detail" name="shipping_detail" value="<?php echo htmlspecialchars($shipping_detail); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-subsection">
                    <div class="section-title-with-checkbox">
                        <h4>필수 확인사항</h4>
                        <!-- 금액/가격표 관련 UI 제거 -->
                    </div>
                    <div class="agreement-box">
                        <p>본 주문 페이지는 기공물 의뢰 및 배송을 돕기 위한 페이지입니다.</p>
                        <p>더 정확한 기공 의뢰 및 배송을 위해 아래의 사항에 대해 동의 여부를 체크해주시기 바랍니다.</p>
                        <p>파트너 랩 주문 시 다음 사항에 동의해주시기 바랍니다:</p>
                        <ul>
                            <li>주문 내용의 정확성에 대한 책임은 주문자에게 있습니다.</li>
                            <li>제작 완료 후 변경 및 취소는 불가능합니다.</li>
                            <li>배송비는 별도로 청구될 수 있습니다.</li>
                            <li>제작 기간은 영업일 기준 7-10일입니다.</li>
                        </ul>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="agreement" name="agreement" value="1" <?php echo $agreement ? 'checked' : ''; ?> required>
                                위 내용에 동의합니다. <span class="required">*</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="paired-sections-row">
        <!-- Step 2: 환자정보 -->
        <section class="order-section" id="section-2" data-section="2">
            <div class="section-header">
                <h3><span class="section-number">2</span> 환자정보</h3>
            </div>
            
            <div class="section-content">
                <div class="form-subsection">
                    <h4>환자정보</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="patient_name">환자명 <span class="required">*</span></label>
                            <input type="text" id="patient_name" name="patient_name" value="<?php echo htmlspecialchars($patient_name); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="patient_gender">성별 <span class="required">*</span></label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="patient_gender" value="male" <?php echo $patient_gender == 'male' ? 'checked' : ''; ?> required>
                                    남성
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="patient_gender" value="female" <?php echo $patient_gender == 'female' ? 'checked' : ''; ?> required>
                                    여성
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="patient_age">나이 <span class="required">*</span></label>
                            <input type="number" id="patient_age" name="patient_birth" value="<?php echo htmlspecialchars($patient_birth); ?>" min="0" max="120" placeholder="숫자만 입력" required>
                        </div>
                    </div>
                </div>

                <div class="form-subsection">
                    <h4>납기 일정</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="delivery_date">납기 희망일 <span class="required">*</span></label>
                            <input type="date" id="delivery_date" name="delivery_date" value="<?php echo htmlspecialchars(isset($delivery_date) ? $delivery_date : ''); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </section>





        <!-- Step 3: 치식설정 -->
        <section class="order-section" id="section-3" data-section="3">
            <div class="section-header">
                <h3><span class="section-number">3</span> 치식설정</h3>
            </div>
            
            <div class="section-content">
                <div class="form-subsection">
                    <h4>치아 선택</h4>
                    <div class="unified-tooth-chart">
                        <div class="chart-container">
                            <!-- 상악 섹션 -->
                            <div class="maxilla-section">
                                <div class="teeth-grid maxilla-grid">
                                    <div class="teeth-row upper-row">
                                        <!-- 18 → 11 (우측에서 좌측으로) -->
                                        <?php for ($i = 18; $i >= 11; $i--): ?>
                                        <div class="tooth-button" data-tooth="<?php echo $i; ?>">
                                            <input type="checkbox" id="tooth-<?php echo $i; ?>" name="selected_teeth[]" value="<?php echo $i; ?>" <?php echo in_array($i, $selected_teeth) ? 'checked' : ''; ?>>
                                            <label for="tooth-<?php echo $i; ?>" class="tooth-visual">
                                                <img class="tooth-img default" src="/partner_lab/img/teeth/<?php echo $i; ?>.png" alt="<?php echo $i; ?>">
                                                <img class="tooth-img selected" src="/partner_lab/img/teeth/choice/<?php echo $i; ?>.png" alt="<?php echo $i; ?>">
                                                <span class="tooth-number"><?php echo $i; ?></span>
                                            </label>
                                            <button type="button" class="bridge-point" data-tooth="<?php echo $i; ?>" title="Bridge point"></button>
                                            <input type="hidden" name="tooth_type_<?php echo $i; ?>" value="upper_right">
                                        </div>
                                        <?php endfor; ?>
                                        
                                        <!-- 21 → 28 (좌측에서 우측으로) -->
                                        <?php for ($i = 21; $i <= 28; $i++): ?>
                                        <div class="tooth-button" data-tooth="<?php echo $i; ?>">
                                            <input type="checkbox" id="tooth-<?php echo $i; ?>" name="selected_teeth[]" value="<?php echo $i; ?>" <?php echo in_array($i, $selected_teeth) ? 'checked' : ''; ?>>
                                            <label for="tooth-<?php echo $i; ?>" class="tooth-visual">
                                                <img class="tooth-img default" src="/partner_lab/img/teeth/<?php echo $i; ?>.png" alt="<?php echo $i; ?>">
                                                <img class="tooth-img selected" src="/partner_lab/img/teeth/choice/<?php echo $i; ?>.png" alt="<?php echo $i; ?>">
                                                <span class="tooth-number"><?php echo $i; ?></span>
                                            </label>
                                            <button type="button" class="bridge-point" data-tooth="<?php echo $i; ?>" title="Bridge point"></button>
                                            <input type="hidden" name="tooth_type_<?php echo $i; ?>" value="upper_left">
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 십자가 구분선 -->
                            <div class="chart-divider">
                                <div class="horizontal-line"></div>
                                <div class="vertical-line"></div>
                            </div>
                            
                            <!-- 하악 섹션 -->
                            <div class="mandible-section">
                                <div class="teeth-grid mandible-grid">
                                    <div class="teeth-row lower-row">
                                        <!-- 48 → 41 (우측에서 좌측으로) -->
                                        <?php for ($i = 48; $i >= 41; $i--): ?>
                                        <div class="tooth-button" data-tooth="<?php echo $i; ?>">
                                            <input type="checkbox" id="tooth-<?php echo $i; ?>" name="selected_teeth[]" value="<?php echo $i; ?>" <?php echo in_array($i, $selected_teeth) ? 'checked' : ''; ?>>
                                            <label for="tooth-<?php echo $i; ?>" class="tooth-visual">
                                                <img class="tooth-img default" src="/partner_lab/img/teeth/<?php echo $i; ?>.png" alt="<?php echo $i; ?>">
                                                <img class="tooth-img selected" src="/partner_lab/img/teeth/choice/<?php echo $i; ?>.png" alt="<?php echo $i; ?>">
                                                <span class="tooth-number"><?php echo $i; ?></span>
                                            </label>
                                            <button type="button" class="bridge-point" data-tooth="<?php echo $i; ?>" title="Bridge point"></button>
                                            <input type="hidden" name="tooth_type_<?php echo $i; ?>" value="lower_right">
                                        </div>
                                        <?php endfor; ?>
                                        
                                        <!-- 31 → 38 (좌측에서 우측으로) -->
                                        <?php for ($i = 31; $i <= 38; $i++): ?>
                                        <div class="tooth-button" data-tooth="<?php echo $i; ?>">
                                            <input type="checkbox" id="tooth-<?php echo $i; ?>" name="selected_teeth[]" value="<?php echo $i; ?>" <?php echo in_array($i, $selected_teeth) ? 'checked' : ''; ?>>
                                            <label for="tooth-<?php echo $i; ?>" class="tooth-visual">
                                                <img class="tooth-img default" src="/partner_lab/img/teeth/<?php echo $i; ?>.png" alt="<?php echo $i; ?>">
                                                <img class="tooth-img selected" src="/partner_lab/img/teeth/choice/<?php echo $i; ?>.png" alt="<?php echo $i; ?>">
                                                <span class="tooth-number"><?php echo $i; ?></span>
                                            </label>
                                            <button type="button" class="bridge-point" data-tooth="<?php echo $i; ?>" title="Bridge point"></button>
                                            <input type="hidden" name="tooth_type_<?php echo $i; ?>" value="lower_left">
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- 브릿지 연결선 오버레이 -->
                        <svg class="bridge-overlay" id="bridge-overlay" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <g id="bridge-lines"></g>
                        </svg>
                        <!-- 치아 선택 초기화 버튼 -->
                        <div style="margin-top:8px;">
                            <button type="button" id="resetAllBtnChart" class="btn btn-danger" style="padding:6px 10px; font-size:13px;">초기화</button>
                        </div>
                        <!-- 안내 문구: 작게 표시 -->
                        <div class="confirm-hint">치아를 선택하고 확인 버튼을 누른 뒤 싱글/브릿지 옵션을 선택해주세요.</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 선택 치아 옵션: 두 섹션 하단에 전체 너비로 표시 -->
        <div class="form-subsection tooth-options-subsection">
            <h4>선택 치아 옵션</h4>
            <div id="selected-teeth-options" class="tooth-options">
                <div class="options-header">
                    <div class="col no">No.</div>
                    <div class="col system">임플란트 시스템 <span class="required">*</span></div>
                    <div class="col margin">마진레벨 <span class="required">*</span></div>
                    <div class="col prosthetic">상부 보철</div>
                    <div class="col shade">쉐이드</div>
                </div>
                <div class="options-body" id="tooth-options-body">
<?php
if (!empty($selected_teeth) && is_array($selected_teeth)) {
  foreach ($selected_teeth as $toothNo) {
    $toothNo = (int)$toothNo;
    $sys_val = isset($existing_cfg["tooth_options[{$toothNo}][system]"]) ? $existing_cfg["tooth_options[{$toothNo}][system]"] : '';
    $sys_other = isset($existing_cfg["tooth_options[{$toothNo}][system_other]"]) ? $existing_cfg["tooth_options[{$toothNo}][system_other]"] : '';
    $margin_val = isset($existing_cfg["tooth_options[{$toothNo}][margin]"]) ? $existing_cfg["tooth_options[{$toothNo}][margin]"] : '';
    $prosthetic_val = isset($existing_cfg["tooth_options[{$toothNo}][prosthetic]"]) ? $existing_cfg["tooth_options[{$toothNo}][prosthetic]"] : '의뢰 안함';
    $shade_val = isset($existing_cfg["tooth_options[{$toothNo}][shade]"]) ? $existing_cfg["tooth_options[{$toothNo}][shade]"] : '';
    $mode_val = isset($existing_modes[$toothNo]) ? $existing_modes[$toothNo] : (isset($existing_cfg["tooth_options[{$toothNo}][mode]"]) ? $existing_cfg["tooth_options[{$toothNo}][mode]"] : '');
    $group_val = isset($existing_groups[$toothNo]) ? $existing_groups[$toothNo] : (isset($existing_cfg["tooth_options[{$toothNo}][bridge_group]"]) ? $existing_cfg["tooth_options[{$toothNo}][bridge_group]"] : '');
    $sys_list = array('Straumann BLX RB','Straumann BLX WB','Straumann TLX RT','Straumann TLX WT','Straumann Bone level NC','Straumann Bone level RC','Straumann Tissue level RN','Straumann Tissue level WN','Others');
    $margin_list = array('Equal','Sub','Supra');
    $prosthetic_list = array('의뢰 안함','Full zirconia Crown','Temporary crown');
    $shade_list = array('','A1','A2','A3','A3.5','A4','B1','B2','B3','B4','C1','C2','C3','C4','D2','D3','D4');
    echo '<div class="options-row" data-tooth="'.$toothNo.'">';
    echo '<div class="option-main-row">';
    $mode_tag = $mode_val ? '<span class="tooth-mode-tag '.($mode_val==='bridge'?'bridge':'general').'">'.($mode_val==='bridge'?'브릿지':'싱글').'</span>' : '';
    $group_badge = ($mode_val==='bridge' && $group_val) ? '<span class="group-badge color-'.(((int)$group_val-1)%4+1).'">G'.$group_val.'</span>' : '';
    echo '<div class="no-label"><span class="tooth-no">'.$toothNo.'</span>'.$mode_tag.$group_badge.'</div>';
    echo '<div><select name="tooth_options['.$toothNo.'][system]" required><option value="">선택</option>';
    foreach ($sys_list as $opt) { $sel = ($sys_val===$opt)?' selected':''; echo '<option'.$sel.'>'.$opt.'</option>'; }
    echo '</select><input type="text" name="tooth_options['.$toothNo.'][system_other]" value="'.htmlspecialchars($sys_other).'" placeholder="기타 시스템 입력" class="system-other-input" style="'.(($sys_val==='Others')?'display:block;':'display:none;').' margin-top:6px;"></div>';
    echo '<div><select name="tooth_options['.$toothNo.'][margin]" required><option value="">선택</option>';
    foreach ($margin_list as $opt) { $sel = ($margin_val===$opt)?' selected':''; echo '<option'.$sel.'>'.$opt.'</option>'; }
    echo '</select></div>';
    echo '<div><select name="tooth_options['.$toothNo.'][prosthetic]"><option value="의뢰 안함"'.($prosthetic_val==='의뢰 안함'?' selected':'').'>의뢰 안함</option><option value="Full zirconia Crown"'.($prosthetic_val==='Full zirconia Crown'?' selected':'').'>Full zirconia Crown</option><option value="Temporary crown"'.($prosthetic_val==='Temporary crown'?' selected':'').'>Temporary crown</option></select></div>';
    echo '<div><select name="tooth_options['.$toothNo.'][shade]">';
    foreach ($shade_list as $opt) { $sel = ($shade_val===$opt)?' selected':''; $label = $opt!==''?$opt:'선택(옵션)'; $valAttr = $opt!==''?' value="'.$opt.'"':''; echo '<option'.$valAttr.$sel.'>'.$label.'</option>'; }
    echo '</select></div>';
    echo '</div>';
    echo '<input type="hidden" name="tooth_options['.$toothNo.'][mode]" value="'.htmlspecialchars($mode_val).'">';
    echo '<input type="hidden" name="tooth_options['.$toothNo.'][bridge_group]" value="'.htmlspecialchars($group_val).'">';
    echo '<input type="hidden" name="tooth_options['.$toothNo.'][non_engaging]" value="">';
    echo '<input type="hidden" name="tooth_options['.$toothNo.'][anodizing]" value="">';
    echo '<input type="hidden" name="tooth_options['.$toothNo.'][pmab]" value="">';
    echo '<input type="hidden" name="tooth_options['.$toothNo.'][screw]" value="">';
    echo '</div>';
  }
}
?>
                </div>
                <div class="options-extra-box">
                    <h5 class="options-extra-title">기타 추가 옵션</h5>
                    <div class="options-extra-items">
                        <label><input type="checkbox" id="global_pmab"> 정품 PMAB 적용</label>
                        <label><input type="checkbox" id="global_screw"> 정품 스크류 적용</label>
                        <label><input type="checkbox" id="global_anodizing"> 아노다이징 적용</label>
                        <label><input type="checkbox" id="global_non_engaging"> Non-engaging 적용</label>
                    </div>
                </div>
            </div>
        </div>
        </div>
        <!-- Step 4: 디지털 임프레션/ 러버 임프레션 -->
        <section class="order-section" id="section-4" data-section="4">
            <div class="section-header">
                <h3><span class="section-number">4</span> 디지털 임프레션/ 러버 임프레션</h3>
            </div>
            
            <div class="section-content">
                



                <div class="form-subsection">
                    <h4>디지털 임프레션 (또는 어버트먼트 stl파일, 필요시, 구강사진이나 기타 파일도 올릴수있습니다.)</h4>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="scan_files">파일 업로드</label>
                            <div class="file-upload-area" id="scan_upload_area">
                                <div class="upload-content">
                                    <i class="upload-icon">📁</i>
                                    <p>파일을 끌어오거나 클릭하여 업로드 해주세요</p>
                                    <p class="upload-info">모든 파일 형식 지원 (최대 50MB)</p>
                                </div>
                                <input type="file" id="scan_files" name="scan_files[]" multiple style="display: none;">
                            </div>
                            <div class="uploaded-files" id="scan_uploaded_files">
<?php
if (!empty($existing_files)) {
  foreach ($existing_files as $f) {
    $fname = '';
    if (isset($f['original_name']) && $f['original_name']) $fname = $f['original_name'];
    elseif (isset($f['file_name']) && $f['file_name']) $fname = $f['file_name'];
    elseif (isset($f['file_path']) && $f['file_path']) { $parts = explode('/', $f['file_path']); $fname = end($parts); }
    $fid = isset($f['file_id']) ? (int)$f['file_id'] : 0;
    $fpath = isset($f['file_path']) ? $f['file_path'] : '';
    $dl = $fid>0 ? ('./file_download.php?file_id='.$fid) : ('./file_download.php?path='.urlencode($fpath).'&name='.urlencode($fname));
    echo '<div class="uploaded-file"><div class="file-info"><span class="file-name">'.htmlspecialchars($fname).'</span></div><div style="display:flex;gap:8px;align-items:center"><a class="btn btn-secondary" href="'.$dl.'">다운로드</a><button type="button" class="file-remove" onclick="deleteExistingFile('.$fid.', this, \''.$fpath.'\')">삭제</button></div></div>';
  }
}
?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-subsection">
                    <h4>러버 임프레션 (하단 체크박스에 체크를 하면 파트너랩에서 택배 픽업을 신청합니다.)</h4>
                    <div class="form-row">
                        <div class="form-group full-width pickup-callout">
                            <label class="checkbox-label">
                                <input type="checkbox" id="rubber_impression_delivery" name="rubber_impression_delivery" value="1" <?php echo $rubber_impression_delivery ? 'checked' : ''; ?>>
                                택배 픽업 신청
                            </label>
                        </div>
                    </div>
                    
                    <div id="delivery_address_section" style="display: none;">
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="delivery_hope_date">픽업 희망일 <span class="required">*</span></label>
                                <div class="date-inline">
                                    <input type="date" id="delivery_hope_date" name="delivery_hope_date" value="<?php echo htmlspecialchars($delivery_hope_date); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    <button type="button" id="delivery_hope_date_btn" class="date-icon" aria-label="날짜 선택"></button>
                                </div>
                                <p class="form-help">
                                    위의 고객정보 입력하신 주소로 발송지가 입력 됩니다. 변경을 원하시는 분은 기타 사항에 기재 부탁드립니다. 픽업 희망일을 입력하세요.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                

                <div class="form-subsection">
                    <h4>제작 문의</h4>
                    <div class="contact-info-box">
                        <div class="contact-item">
                            <strong>주소:</strong> 대전 유성구 테크노8로 44 2동 쓰리포인트덴탈
                        </div>
                        <div class="contact-item">
                            <strong>연락처:</strong> 1855-2804
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Step 5: 기타사항 -->
        <section class="order-section" id="section-5" data-section="5">
            <div class="section-header">
                <h3><span class="section-number">5</span> 기타사항</h3>
            </div>
            <div class="section-content">
                <div class="form-subsection">
                    <h4>특이사항</h4>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="special_notes">특이사항</label>
                            <textarea id="special_notes" name="special_notes" rows="4" placeholder="예 : 스캔에 사용한 스캔바디는 OO사의 제품입니다. 
        전치부 어버트먼트 마진을 최소 1.5mm 서브로 내려주세요."><?php echo htmlspecialchars($special_notes); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-subsection">
                    <h4>최종 동의</h4>
                    <div class="agreement-box">
                        <p>본인은 위 내용을 확인하였으며, 파트너 랩 주문에 관한 모든 조건에 동의합니다.</p>
                        <ul>
                            <li>주문 후 변경 및 취소는 제작 시작 전까지만 가능합니다.</li>
                            <li>제작 완료 후 배송비는 고객 부담입니다.</li>
                            <li>제품 하자가 아닌 단순 변심으로 인한 반품은 불가능합니다.</li>
                            <li>제작 기간은 영업일 기준으로 계산됩니다.</li>
                        </ul>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="final_agreement" name="final_agreement" value="1" <?php echo $final_agreement ? 'checked' : ''; ?> required>
                                위 내용을 모두 확인하였으며 주문에 동의합니다. <span class="required">*</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 하단 액션 버튼 -->
        <div class="form-actions">
            <button type="button" id="resetAllBtn" class="btn btn-danger">초기화</button>
            <button type="submit" id="submitOrderBtn" class="btn btn-primary">다음 단계</button>
            <button type="button" class="btn btn-secondary" id="historyBtn" style="margin-left:8px" onclick="window.location.href='./history.php'">주문 내역</button>
        </div>
</form>
</div>

<!-- 다음 우편번호 API -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
// 주문 페이지 전용 스타일 스코프를 위해 body 클래스 추가
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('partner-lab-order-page');
});
</script>

<style>
/* Straumann Header 스타일 */
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
.header-user .logout-btn,
.header-user .login-btn,
.header-user .new-order-btn,
.header-user .campus-btn {
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
.header-user .campus-btn:hover {
    background: #1f5a4a;
}

.header-nav .nav-menu {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 30px;
}

.header-nav .nav-menu li a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
    font-size: 16px;
    transition: color 0.3s ease;
}

.header-nav .nav-menu li a:hover {
    color: #2d7663;
}

.header-user {
    display: flex;
    align-items: center;
    gap: 15px;
}

.header-user .user-name {
    color: #333;
    font-weight: 500;
}

.header-user .logout-btn,
.header-user .login-btn {
    background: #2d7663;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.header-user .logout-btn:hover,
.header-user .login-btn:hover {
    background: #1f5a4a;
}

/* 금액/가격표 관련 스타일 제거 */

.birth-selects {
    display: flex;
    gap: 10px;
    align-items: center;
}

.birth-selects .birth-select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

/* 반응형 헤더 */
@media (max-width: 768px) {
    .header-container {
        flex-direction: column;
        gap: 15px;
        padding: 15px;
    }
    
    .header-nav .nav-menu {
        gap: 20px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .header-nav .nav-menu li a {
        font-size: 14px;
    }
}

/* 기본 스타일 (주문 페이지 전용 스코프) */
body.partner-lab-order-page * {
    box-sizing: border-box;
}

body.partner-lab-order-page {
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 17px;
    line-height: 1.6;
    color: #333;
    background-color: #f8f9fa;
}

/* 초기 렌더링 시 잔상 방지: JS 초기화 전에는 관련 요소 숨김 */
body.partner-lab-order-page:not(.hydrated) .tooth-mode-tag,
body.partner-lab-order-page:not(.hydrated) .tooth-visual .tooth-mode-badge,
body.partner-lab-order-page:not(.hydrated) #tooth-confirm-btn,
body.partner-lab-order-page:not(.hydrated) #tooth-mode-modal {
    display: none !important;
}

.onepage-container {
    max-width: 1200px;
    margin: 0 auto;
    padding-top: 160px;
}

/* 진행 표시기 스타일 */
.progress-container {
    position: fixed;
    top: 68px;
    left: 0;
    right: 0;
    background: #fff;
    border-bottom: 1px solid #ddd;
    z-index: 1100;
    padding: 12px 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.progress-bar {
    height: 4px;
    background: #f0f0f0;
    position: relative;
    margin-bottom: 15px;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    border-radius: 2px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #007bff, #0056b3);
    width: 0%;
    transition: width 0.5s ease;
    border-radius: 2px;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #999;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    flex: 1;
    text-align: center;
    padding: 5px;
}

.progress-step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    font-weight: bold;
    font-size: 16px;
    transition: all 0.3s ease;
    position: relative;
    z-index: 2;
}

.progress-step-title {
    font-size: 16px;
    font-weight: 500;
    white-space: nowrap;
}

.progress-step.active .progress-step-circle {
    background: #007bff;
    border-color: #007bff;
    color: white;
    transform: scale(1.1);
    box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.2);
}

.progress-step.completed .progress-step-circle {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.progress-step.completed .progress-step-circle::after {
    content: '✓';
    position: absolute;
    font-size: 16px;
    font-weight: bold;
}

.progress-step.active {
    color: #007bff;
    font-weight: 600;
}

.progress-step.completed {
    color: #28a745;
    font-weight: 600;
}

.progress-step:hover:not(.active) {
    color: #007bff;
}

.progress-step:hover:not(.active) .progress-step-circle {
    border-color: #007bff;
    background: #e3f2fd;
}

.progress-step-line {
    position: absolute;
    top: 16px;
    left: calc(50% + 16px);
    right: calc(-50% + 16px);
    height: 2px;
    background: #dee2e6;
    z-index: 1;
}

.progress-step:last-child .progress-step-line {
    display: none;
}

.progress-step.completed .progress-step-line {
    background: #28a745;
}

.progress-step.active .progress-step-line {
    background: linear-gradient(90deg, #28a745 0%, #dee2e6 100%);
}

/* 섹션 스타일 */
.order-section {
    background: white;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.order-section.in-view {
    transform: translateY(0);
    opacity: 1;
}

.section-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 30px;
}

.section-header h3 {
    margin: 0;
    font-size: 1.4em;
    display: flex;
    align-items: center;
    gap: 15px;
}

.section-number {
    background: rgba(255,255,255,0.2);
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.section-content {
    padding: 30px;
}

/* Step 2 + Step 3 side-by-side layout */
.paired-sections-row {
    display: grid;
    grid-template-columns: 1fr 2fr; /* 환자정보 1/3, 치식설정 2/3 */
    gap: 20px;
    align-items: stretch; /* 같은 행에서 카드 높이를 동일하게 */
    margin-bottom: 30px;
}
.paired-sections-row .order-section {
    margin-bottom: 0; /* 행 내부에서는 하단 여백 제거 */
    height: 100%; /* 그리드 셀 높이에 맞춰 카드 확장 */
}
/* 선택 치아 옵션을 두 컬럼 합친 전체 너비로 표시 */
.paired-sections-row .tooth-options-subsection {
    grid-column: 1 / -1;
    margin-top: 10px;
}
@media (max-width: 992px) {
    .paired-sections-row {
        grid-template-columns: 1fr; /* 모바일에서는 세로 배치 */
        gap: 20px;
    }
}

.form-subsection {
    margin-bottom: 30px;
}

.form-subsection:last-child {
    margin-bottom: 0;
}

.form-subsection h4 {
    color: #2c3e50;
    font-size: 1.2em;
    margin-bottom: 20px;
    padding-bottom: 8px;
    border-bottom: 2px solid #f1f3f4;
}

/* 제목과 체크박스 정렬 */
.section-title-with-checkbox {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 16px;
    margin-bottom: 20px; /* h4 하단 여백을 컨테이너로 이전 */
}
/* 제목 옆 다운로드 버튼 배치 시 상단 여백 제거 */
.section-title-with-checkbox .price-download {
    margin-top: 0;
}
/* 제목과 옆 컨트롤을 같은 라인에 정확히 맞추기 위해 h4 기본 여백/밑줄 제거 */
.section-title-with-checkbox h4 {
    margin: 0;
    padding: 0;
    border-bottom: none;
}

.checkbox-inline-large {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 17px;
}
.checkbox-inline-large input[type="checkbox"] {
    transform: scale(1.2);
    margin-right: 4px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.form-group {
    flex: 1;
    min-width: 250px;
}

.form-group.full-width {
    flex: 1 1 100%;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: 500;
    font-size: 17px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 17px;
    transition: all 0.3s ease;
    background: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.required {
    color: #dc3545;
}

/* 라디오 버튼 그룹 */
.radio-group {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.radio-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: normal;
    font-size: 17px;
}

.radio-label input[type="radio"] {
    width: auto;
    margin-right: 8px;
}

/* 체크박스 라벨 */
.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: normal;
    font-size: 17px; /* 일반 텍스트 기준 상향 */
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    margin-right: 10px;
}

/* 우편번호 그룹 */
.postcode-group {
    display: flex;
    gap: 10px;
}

.postcode-group input {
    flex: 1;
}

.btn-postcode {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color 0.3s ease;
    font-size: 0.9em;
}

.btn-postcode:hover {
    background: #5a6268;
}

/* 동의서 박스 */
.agreement-box {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    max-height: 200px;
    overflow-y: auto;
}

.agreement-box ul {
    margin: 10px 0;
    padding-left: 20px;
}

.agreement-box li {
    margin-bottom: 5px;
}

/* 제작 문의 박스 */
.contact-info-box {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
}

.contact-item {
    margin-bottom: 10px;
    font-size: 14px;
    line-height: 1.5;
}

.contact-item:last-child {
    margin-bottom: 0;
}

.contact-item strong {
    color: #2d7663;
    margin-right: 8px;
}

/* 파일 업로드 영역 */
.file-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafbfc;
}

.file-upload-area:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.file-upload-area.dragover {
    border-color: #667eea;
    background: #e3f2fd;
}

.upload-content {
    pointer-events: none;
}

.upload-icon {
    font-size: 2em;
    margin-bottom: 10px;
    display: block;
}

.upload-info {
    font-size: 17px;
    color: #6c757d;
    margin-top: 5px;
}

.uploaded-files {
    margin-top: 15px;
}

.uploaded-file {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 8px;
}

.file-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* 러버 임프레션 픽업 신청 강조 영역 */
.pickup-callout {
    border: 2px solid #2d7663;
    background: #eaf8f3;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 12px;
}
.pickup-callout .checkbox-label {
    font-weight: 600;
    font-size: 16px;
}

/* 기타 추가 옵션 박스 스타일 */
.options-extra-box {
    margin-top: 12px;
    padding: 14px 16px;
    border: 1px solid #e3e7ee;
    border-radius: 10px;
    background: #f9fbff;
}
.options-extra-title {
    margin: 0 0 8px 0;
    font-size: 17px;
    color: #2c3e50;
}
.options-extra-items {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
}
.options-extra-items label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 17px;
    color: #333;
}

.file-name {
    font-weight: 500;
}

.file-size {
    font-size: 0.85em;
    color: #6c757d;
}

.file-remove {
    background: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8em;
}

.file-remove:hover {
    background: #c82333;
}

/* 제품 선택 */
.product-selection {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.product-label {
    cursor: pointer;
    font-weight: normal;
}

.product-label input[type="checkbox"] {
    display: none;
}

.product-card {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    background: white;
}

.product-label input[type="checkbox"]:checked + .product-card {
    border-color: #667eea;
    background: #f8f9ff;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.product-card h5 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 1.1em;
}

.product-card p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9em;
}

/* 치아 다이어그램 */
.dental-chart {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 30px;
    margin: 20px 0;
}

.jaw-section {
    margin-bottom: 30px;
}

.jaw-section:last-child {
    margin-bottom: 0;
}

.jaw-section h5 {
    text-align: center;
    margin-bottom: 15px;
    color: #2c3e50;
    font-size: 1.1em;
}

.teeth-row {
    display: flex;
    justify-content: center;
    gap: 2px;
    column-gap: 2px;
    row-gap: 2px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.tooth-button {
    position: relative;
    width: 40px;
    height: 40px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.tooth-button input[type="checkbox"] {
    display: none;
}

.tooth-button span {
    font-size: 0.8em;
    font-weight: bold;
    color: #495057;
}

.tooth-button:hover {
    border-color: #667eea;
    background: #f8f9ff;
}

.tooth-button input[type="checkbox"]:checked + span,
.tooth-button:has(input[type="checkbox"]:checked) {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.tooth-button:has(input[type="checkbox"]:checked) span {
    color: white;
}

/* 통합 치아 차트 레이아웃 */
.unified-tooth-chart {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 40px;
    margin: 20px 0;
    position: relative;
}

.chart-container {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.chart-divider {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
}

.horizontal-line {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 3px;
    background: #000;
    transform: translateY(-50%);
}

.vertical-line {
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #000;
    transform: translateX(-50%);
}

.jaw-label {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 15px;
    text-align: center;
}

.maxilla-section, .mandible-section {
    position: relative;
    z-index: 2;
    background: transparent;
    border-radius: 0;
    padding: 20px;
    box-shadow: none;
}

.chart-container { position: relative; }

.teeth-grid {
    display: flex;
    justify-content: center;
}

.teeth-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2px;
    flex-wrap: nowrap;
}

/* 치아 이미지 라벨 */
.tooth-visual {
    display: inline-block;
    position: relative;
    cursor: pointer;
    width: auto;
    height: auto;
    margin: 0 !important;
    padding: 0 !important;
    line-height: 0;
    transform: none;
}

.tooth-img {
    position: static;
    width: 32px;
    height: auto;
    display: block;
    margin: 0 !important;
    padding: 0 !important;
}

.tooth-img.selected {
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.tooth-button input[type="checkbox"]:checked + .tooth-visual .selected {
    opacity: 1;
}

.tooth-button input[type="checkbox"]:checked + .tooth-visual .default {
    opacity: 0;
}

.tooth-number {
    position: absolute;
    bottom: 0;
    right: 2px;
    font-size: 0.9em;
    font-weight: 700;
    color: #222;
    background: rgba(255,255,255,0.9);
    padding: 0 3px;
    border-radius: 3px;
    z-index: 3;
    transform: scale(1.35);
    transform-origin: bottom right;
    line-height: 1.1;
    text-shadow: 0 1px 1px rgba(0,0,0,0.1);
}

/* 선택 시에도 숫자는 선명하게 유지 */
.tooth-button:has(input[type="checkbox"]:checked) .tooth-number {
    color: #111 !important;
    background: rgba(255,255,255,0.95) !important;
}

/* 선택 상태에서도 배경/테두리 없음 */
.tooth-button:has(input[type="checkbox"]:checked) {
    background: transparent !important;
    border: none !important;
}

/* 네모 버튼 제거 및 이미지 자체를 버튼으로 */
.tooth-button {
    position: relative;
    display: inline-block;
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    border-radius: 0;
    background: transparent !important;
    box-shadow: none !important;
    line-height: 0;
    vertical-align: top;
}

.tooth-button:hover {
    border: none;
    background: transparent;
}

.teeth-row {
    align-items: center;
    justify-content: center !important;
    gap: 2px !important;
    column-gap: 2px !important;
    row-gap: 2px !important;
    margin-bottom: 6px !important;
}

/* 상악과 하악 치아 배열 정렬 */
.upper-row { align-items: flex-end !important; }
.lower-row { align-items: flex-start !important; }

/* 하악 치아 숫자 위치 조정 */
.mandible-section .tooth-visual .tooth-number {
    bottom: -10px;
    right: 2px;
    transform: scale(1.25);
}

/* 하악 섹션 하단 여유 공간 */
.mandible-section { padding-bottom: 25px; }

/* 브릿지 점/선 오버레이 */
.bridge-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none; /* 선은 클릭 차단 */
    z-index: 5;
}
.bridge-line {
    stroke: #ff9900; /* 기본: 오렌지 라인(연결됨) */
    stroke-width: 4;
    stroke-linecap: round;
}
.bridge-line.inactive { stroke: rgba(0,0,0,0.25); stroke-dasharray: 6 6; }
.bridge-point {
    position: absolute;
    left: 50%;
    bottom: -10px; /* 치아 하단 중앙으로 이동 */
    transform: translateX(-50%);
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #fff; /* 기본: 흰색 점 */
    border: 2px solid #ff9900; /* 오렌지 테두리로 가시성 유지 */
    opacity: 0; /* 기본은 숨김 */
    cursor: pointer;
    z-index: 6; /* 치아 위, 선 위 */
    transition: transform 0.15s ease, opacity 0.15s ease;
}
.tooth-button input[type="checkbox"]:checked ~ .bridge-point {
    opacity: 1; /* 선택된 치아만 점 표시 */
}
.bridge-point.active {
    transform: translateX(-50%) scale(1.2);
}

/* 브릿지 스킵 표시: 건너뛴 치아 블럭 처리 */
.tooth-button.bridge-skipped .tooth-visual::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    background: none;
    border: 2px dashed rgba(255,153,0,0.9);
    border-radius: 50%;
    pointer-events: none;
    z-index: 2;
}

/* 안내 문구 스타일 */
.confirm-hint {
    margin-top: 6px;
    font-size: 12px;
    color: #6c757d;
}

/* 액션 버튼 */
.form-actions {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 40px 0;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.btn {
    padding: 10px 16px;
    border: 2px solid #2a7f62;
    border-radius: 0;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
    min-width: 140px;
    background: #fff;
    color: #2a7f62;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
}

.btn-primary {
    background: #2a7f62;
    border-color: #2a7f62;
    color: #fff;
}

.btn-primary:hover { background: #256e57; border-color: #256e57; }

.btn-danger, .btn-link, .btn-secondary {
    background: #fff;
    color: #2a7f62;
    border-color: #2a7f62;
}

.btn-danger:hover, .btn-link:hover, .btn-secondary:hover { background: #f4fbf8; }

/* 자동저장 상태 */
.autosave-status {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    font-size: 0.9em;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.autosave-status.show {
    opacity: 1;
    transform: translateY(0);
}

.autosave-status.saving {
    background: #ffc107;
    color: #212529;
}

.autosave-status.error {
    background: #dc3545;
}

/* 반응형 디자인 */
@media (max-width: 768px) {
    .onepage-container {
        padding-top: 140px;
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .progress-container { padding: 0 10px; top: 60px; }
    
    .progress-steps {
        gap: 8px;
    }
    
    .progress-step {
        min-width: 70px;
        padding: 8px;
    }
    
    .progress-step-circle {
        width: 25px;
        height: 25px;
        font-size: 0.8em;
    }
    
    .progress-step-title {
        font-size: 0.7em;
    }
    
    .section-content {
        padding: 20px;
    }
    
    .form-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .form-group {
        min-width: auto;
    }
    
    .postcode-group {
        flex-direction: column;
    }
    
    .product-selection {
        grid-template-columns: 1fr;
    }
    
    .teeth-row {
        gap: 0;
    }
    
    .tooth-button {
        width: auto;
        height: auto;
    }
    
    .form-actions {
        flex-direction: column;
        padding: 20px;
    }
    
    .btn {
        min-width: auto;
    }
}

@media (max-width: 480px) {
    .progress-step {
        min-width: 60px;
    }
    
    .progress-step-title {
        display: none;
    }
    
    .teeth-row {
        gap: 0;
    }
    
    .tooth-button {
        width: auto;
        height: auto;
    }
    
    .tooth-button span {
        font-size: 0.7em;
    }
}

/* 선택 치아 옵션 그리드 */
.tooth-options-subsection h4 { margin-top: 10px; }
.tooth-options { 
    border: 1px solid #e9ecef; 
    border-radius: 10px; 
    padding: 15px; 
    background: #fff; 
    margin-top: 30px;
    overflow-x: auto; /* 가로 스크롤 허용으로 기존 선택 영역 크기 유지 */
}

.options-header { 
    display: grid; 
    grid-template-columns: 60px 1fr 1fr 1fr 1fr; 
    gap: 15px; 
    align-items: center; 
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0;
    padding: 15px;
    font-weight: 600; 
    color: #2c3e50; 
    font-size: 17px;
    min-height: 50px;
}
.options-header, .option-main-row { min-width: 720px; }

/* 글로벌 옵션 바 */
.options-global {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: center;
    margin: 8px 0 12px;
}
.options-global label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

/* 모드 태그 (옵션 행) */
.tooth-mode-tag {
    display: inline-block;
    margin-left: 6px;
    padding: 2px 6px;
    font-size: 12px;
    border-radius: 8px;
    border: 1px solid #ced4da;
    vertical-align: middle;
}
.tooth-mode-tag.general { background: #f1f3f5; color: #495057; }
.tooth-mode-tag.bridge { background: #fff3e6; color: #d9480f; border-color: #ffc078; }

/* 치아 차트 상 모드 배지 */
.tooth-visual .tooth-mode-badge {
    position: absolute;
    top: 2px;
    left: 2px;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    border-radius: 8px;
    border: 1px solid #adb5bd;
    background: rgba(255,255,255,0.96);
    color: #212529 !important;
    pointer-events: none;
    box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    z-index: 7;
    display: none; /* 초기 로드 시 숨김, JS에서 선택 상태에 따라 표시 */
}
.tooth-visual .tooth-mode-badge.bridge {
    background: #ffe8cc;
    color: #a05a00 !important;
    border-color: #ffc078;
}

/* 글로벌 옵션 하단 배치 및 크기 확대 */
.options-global-bottom {
    margin-top: 16px;
    padding-top: 10px;
    border-top: 1px solid #e9ecef;
    gap: 20px;
}
.options-global-bottom label {
    font-size: 19px;
}
.options-global-bottom input[type="checkbox"] {
    transform: scale(1.4);
}

.options-row { 
    border: 1px solid #dee2e6;
    border-top: none;
    padding: 12px 15px;
    background: white;
}

.options-row:first-child { border-top: none; }

.option-main-row {
    display: grid; 
    grid-template-columns: 60px 1fr 1fr 1fr 1fr; 
    gap: 15px; 
    align-items: center; 
    min-height: 45px;
}

.options-row .no-label { 
    font-weight: 600; 
    color: #495057; 
    text-align: center;
    font-size: 17px;
}

.options-row select { 
    width: 100%; 
    min-height: 40px;
    font-size: 17px;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background: white;
}
/* Others 선택 시 표시되는 텍스트 입력을 옵션 셀렉트와 동일 크기로 */
.options-row .system-other-input {
    width: 100%;
    min-height: 40px;
    font-size: 17px;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background: white;
    display: none; /* 기본은 숨김, JS에서 block으로 표시 */
    margin-top: 6px;
}

.options-row .extras-group { 
    display: flex; 
    flex-direction: row; 
    gap: 20px; 
    margin-top: 10px;
    padding: 10px 0;
    border-top: 1px solid #e9ecef;
}

.options-row .extras-group label { 
    display: flex; 
    align-items: center; 
    gap: 6px; 
    font-size: 14px; 
    color: #333; 
    cursor: pointer;
    white-space: nowrap;
}

.options-row .extras-group input[type="checkbox"] {
    width: 16px;
    height: 16px;
}

/* 브릿지 그룹 옵션 행 강조: 기본틀 + 색상 클래스로 구분 */
.options-row[data-bridge-group]:not([data-bridge-group=""]) {
    border-left-width: 4px;
    border-left-style: solid;
    border-left-color: transparent;
}
.options-row.bridge-color-1 { background: rgba(255,153,0,0.06); border-left-color: #ff9900; }
.options-row.bridge-color-2 { background: rgba(43,124,255,0.06); border-left-color: #2b7cff; }
.options-row.bridge-color-3 { background: rgba(47,158,68,0.06); border-left-color: #2f9e44; }
.options-row.bridge-color-4 { background: rgba(174,62,201,0.06); border-left-color: #ae3ec9; }

/* 필수 항목 표시 */
.required {
    color: #dc3545;
    font-weight: bold;
}

@media (max-width: 1100px) {
  .options-header, .option-main-row { grid-template-columns: 60px 1fr 160px 160px 120px; gap: 8px; }
}
@media (max-width: 800px) {
  .options-header, .option-main-row { grid-template-columns: 55px 1fr 140px 140px 100px; gap: 6px; }
  .options-row { padding: 4px 0; }
}
</style>

<script>
// 전역 변수
let currentSection = 1;
let isFormChanged = false;

// 폼 변경 표시 함수
function markFormChanged() {
    isFormChanged = true;
}
// 브릿지 연결 상태
let bridgeConnections = new Set(); // e.g. "26-27-upper_left"
let bridgeActiveStart = null; // 마지막 선택한 포인트

// DOM 로드 완료 후 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeOnePage();
    setupEventListeners();
    setupFileUploads();
    setupTeethOptions();
    setupBridgeUI();
    updateProgress();
});

// 원페이지 초기화
function initializeOnePage() {
    // 스크롤 이벤트 리스너
    window.addEventListener('scroll', handleScroll);
    
    // 진행 표시기 클릭 이벤트
    document.querySelectorAll('.progress-step').forEach(step => {
        step.addEventListener('click', function() {
            const stepNum = parseInt(this.dataset.step, 10);
            // 스텝→섹션 매핑: 2와 3(섹션)은 2 스텝으로 통합됨
            let sectionTarget = 1;
            if (stepNum === 1) sectionTarget = 1;
            else if (stepNum === 2) sectionTarget = 2; // 환자정보/치식설정
            else if (stepNum === 3) sectionTarget = 4; // 디지털 임프레션/러버 임프레션
            else if (stepNum === 4) sectionTarget = 5; // 기타사항
            scrollToSection(sectionTarget);
        });
    });
    
    // 초기 섹션 활성화
    updateProgress();
}

// 이벤트 리스너 설정
function setupEventListeners() {
    // 고객 정보와 배송 정보 동일 체크박스
    document.getElementById('same_as_customer').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('shipping_name').value = document.getElementById('customer_name').value;
            document.getElementById('shipping_phone').value = document.getElementById('customer_phone').value;
        }
    });

    // 고객 정보 변경 시 배송 정보도 자동 업데이트
    document.getElementById('customer_name').addEventListener('input', function() {
        if (document.getElementById('same_as_customer').checked) {
            document.getElementById('shipping_name').value = this.value;
        }
        markFormChanged();
    });

    document.getElementById('customer_phone').addEventListener('input', function() {
        if (document.getElementById('same_as_customer').checked) {
            document.getElementById('shipping_phone').value = this.value;
        }
        markFormChanged();
    });

    // 모든 입력 필드에 변경 감지 이벤트 추가
    document.querySelectorAll('input, select, textarea').forEach(element => {
        element.addEventListener('change', markFormChanged);
        element.addEventListener('input', markFormChanged);
    });

    // 폼 제출 이벤트
    document.getElementById('onePageOrderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (validateForm()) {
            saveFormData(true);
        }
    });
}

// 기존 첨부 렌더링 함수 (setupFileUploads에서 사용)
function renderExistingFileItem(f, container){
  var fname = f.original_name ? f.original_name : (f.file_name ? f.file_name : '');
  if ((!fname || fname==='') && f.file_path) { try { fname = String(f.file_path).split('/').pop(); } catch(e){ fname = '첨부파일'; } }
  var fid = (f.file_id?parseInt(f.file_id,10):0);
  var fpath = f.file_path ? String(f.file_path) : '';
  var dlink = fid>0 ? ('./file_download.php?file_id='+fid) : ('./file_download.php?path='+encodeURIComponent(fpath)+'&name='+encodeURIComponent(fname));
  var fileDiv = document.createElement('div');
  fileDiv.className = 'uploaded-file';
  var info = document.createElement('div');
  info.className = 'file-info';
  var nameSpan = document.createElement('span');
  nameSpan.className = 'file-name';
  nameSpan.textContent = fname || '첨부파일';
  info.appendChild(nameSpan);
  var actions = document.createElement('div');
  actions.style.display = 'flex'; actions.style.gap = '8px'; actions.style.alignItems = 'center';
  var a = document.createElement('a');
  a.className = 'btn btn-secondary'; a.href = dlink; a.textContent = '다운로드';
  var del = document.createElement('button');
  del.type = 'button'; del.className = 'file-remove'; del.textContent = '삭제';
  del.addEventListener('click', function(){ deleteExistingFile(fid, del, fpath); });
  actions.appendChild(a); actions.appendChild(del);
  fileDiv.appendChild(info); fileDiv.appendChild(actions);
  container.appendChild(fileDiv);
}

// 파일 업로드 설정
function setupFileUploads() {
    const area = document.getElementById('scan_upload_area');
    if (!area) return;
    const input = document.getElementById('scan_files');
    const filesContainer = document.getElementById('scan_uploaded_files');

    // 클릭으로 파일 선택
    area.addEventListener('click', () => input.click());

    // 드래그 앤 드롭
    area.addEventListener('dragover', (e) => {
        e.preventDefault();
        area.classList.add('dragover');
    });

    area.addEventListener('dragleave', () => {
        area.classList.remove('dragover');
    });

    area.addEventListener('drop', (e) => {
        e.preventDefault();
        area.classList.remove('dragover');
        handleFiles(e.dataTransfer.files, input, filesContainer);
    });

    // 파일 선택 변경
    input.addEventListener('change', (e) => {
        handleFiles(e.target.files, input, filesContainer);
    });

    // 편집 모드: 기존 파일 표시
    if (window.existingFiles && Array.isArray(window.existingFiles) && window.existingFiles.length) {
        window.existingFiles.forEach(function(f){ renderExistingFileItem(f, filesContainer); });
    } else if (window.editingOrderId && window.editingOrderId>0) {
        fetch('./list_files.php?order_id='+encodeURIComponent(window.editingOrderId))
          .then(function(res){ return res.json(); })
          .then(function(data){ if (data && data.success && Array.isArray(data.files)) {
              data.files.forEach(function(f){ renderExistingFileItem(f, filesContainer); });
          }});
    }
}

// 파일 처리
function handleFiles(files, input, container) {
    Array.from(files).forEach(file => {
        if (file.size > 50 * 1024 * 1024) { // 50MB 제한
            alert('파일 크기는 50MB를 초과할 수 없습니다: ' + file.name);
            return;
        }
        
        const fileDiv = document.createElement('div');
        fileDiv.className = 'uploaded-file';
        fileDiv.innerHTML = `
            <div class="file-info">
                <span class="file-name">${file.name}</span>
                <span class="file-size">(${formatFileSize(file.size)})</span>
            </div>
            <button type="button" class="file-remove" onclick="removeFile(this)">삭제</button>
        `;
        
        container.appendChild(fileDiv);
    });
    try { uploadFilesToServer(files, 'scan'); } catch(e) {}
    
    markFormChanged();
}

// 파일 크기 포맷
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 파일 삭제
function removeFile(button) {
    button.parentElement.remove();
    markFormChanged();
}

// 기존 파일 삭제 (서버)
function deleteExistingFile(fileId, btn, filePath){
    if (!fileId || fileId<=0) { alert('파일 정보를 찾을 수 없습니다.'); return; }
    fetch('./delete_file.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'file_id=' + encodeURIComponent(fileId) + (filePath?('&path='+encodeURIComponent(filePath)):'')
    }).then(function(res){ return res.json(); })
      .then(function(data){ if (data && data.success) { btn.parentElement.remove(); } else { alert('삭제 실패: ' + (data && data.message ? data.message : '')); } })
      .catch(function(err){ alert('삭제 중 오류: ' + err.message); });
}



// 스크롤 처리
function handleScroll() {
    updateProgress();
}

// 진행률 업데이트 함수
 function updateProgress() {
     const sections = document.querySelectorAll('.order-section');
     const progressSteps = document.querySelectorAll('.progress-step');
     const progressFill = document.getElementById('progress-fill');
     if (!sections || sections.length===0 || !progressSteps || progressSteps.length===0 || !progressFill) {
         return;
     }
     
     let activeSection = 1;
     const headerEl = document.querySelector('.straumann-header');
     const progEl = document.querySelector('.progress-container');
     const offsetPad = ((headerEl ? headerEl.offsetHeight : 0) + (progEl ? progEl.offsetHeight : 0) + 20);
     const scrollTop = window.pageYOffset + offsetPad;
     
     // 현재 보이는 섹션 찾기 (고정 헤더/네비게이션을 고려해 바운스 방지)
     sections.forEach((section, index) => {
         const sectionTop = section.offsetTop;
         if (scrollTop >= sectionTop - 10) {
             activeSection = index + 1;
         }
     });
     
    currentSection = activeSection;

    // 섹션-스텝 매핑: 2와 3을 하나의 스텝으로 통합
    let mappedStep = 1;
    if (currentSection === 1) {
        mappedStep = 1;
    } else if (currentSection === 2 || currentSection === 3) {
        mappedStep = 2;
    } else if (currentSection === 4) {
        mappedStep = 3;
    } else {
        mappedStep = 4;
    }

    // 진행 표시기 업데이트
    progressSteps.forEach((step, index) => {
        const stepNumber = index + 1;
        step.classList.remove('active', 'completed');

        if (stepNumber === mappedStep) {
            step.classList.add('active');
        } else if (stepNumber < mappedStep) {
            step.classList.add('completed');
        }
    });

    // 진행바 업데이트 (동적 스텝 수 반영)
    const totalSteps = progressSteps.length;
    const progress = ((mappedStep - 1) / (totalSteps - 1)) * 100;
    progressFill.style.width = progress + '%';
 }
 
 // 섹션으로 스크롤
 function scrollToSection(sectionNumber) {
     const section = document.getElementById('section-' + sectionNumber);
     if (section) {
         const header = document.querySelector('.progress-container');
         const headerHeight = header ? header.offsetHeight : 0;
         const targetPosition = section.offsetTop - headerHeight - 20;
         
         window.scrollTo({
             top: targetPosition,
             behavior: 'smooth'
         });
     }
 }
 
    // 폼 데이터 저장
    let isSubmitting = false;
    function saveFormData(isSubmit = false) {
    // 제출 직전에 글로벌 옵션을 모든 옵션 행 히든필드에 반영
    try {
        const gNon = document.getElementById('global_non_engaging');
        const gAno = document.getElementById('global_anodizing');
        const gPmab = document.getElementById('global_pmab');
        const gScrew = document.getElementById('global_screw');
        const anyChecked = (gNon && gNon.checked) || (gAno && gAno.checked) || (gPmab && gPmab.checked) || (gScrew && gScrew.checked);
        if (anyChecked && typeof applyGlobalOptionsToAllRows === 'function') applyGlobalOptionsToAllRows();
    } catch(e) { /* 글로벌 옵션 적용 중 경고 무시 */ }
    const formEl = document.getElementById('onePageOrderForm');
    const formData = new FormData(formEl);
    // 선택 치아를 명시적으로 수집하여 누락/중복 방지
    try {
        const checked = Array.from(document.querySelectorAll('.tooth-button input[type="checkbox"]:checked')).map(cb => parseInt(cb.value, 10));
        // 기존 값 제거 후 재설정 (중복 방지)
        try { formData.delete('selected_teeth[]'); } catch (eDel) {}
        checked.forEach(n => formData.append('selected_teeth[]', String(n)));
        // 선택된 치아 확인
        if (isSubmit && checked.length === 0) {
            alert('선택된 치아가 없습니다. 치아를 선택해주세요.');
            return;
        }
    } catch (e) { /* selected_teeth 수집 중 경고 무시 */ }
    // 강제: tooth_options가 폼에 존재하는 경우 키/값을 FormData에 재설정하여 누락 방지
    try {
        const rows = document.querySelectorAll('#tooth-options-body .options-row');
        rows.forEach(row => {
            const toothNo = parseInt(row.dataset.tooth, 10);
            // 기본 옵션들
            const sys = row.querySelector(`select[name="tooth_options[${toothNo}][system]"]`);
            const margin = row.querySelector(`select[name="tooth_options[${toothNo}][margin]"]`);
            const prosth = row.querySelector(`select[name="tooth_options[${toothNo}][prosthetic]"]`);
            const shade = row.querySelector(`select[name="tooth_options[${toothNo}][shade]"]`);
            const other = row.querySelector(`input[name="tooth_options[${toothNo}][system_other]"]`);
            // 히든 옵션들
            const non = row.querySelector(`input[name="tooth_options[${toothNo}][non_engaging]"]`);
            const ano = row.querySelector(`input[name="tooth_options[${toothNo}][anodizing]"]`);
            const pmab = row.querySelector(`input[name="tooth_options[${toothNo}][pmab]"]`);
            const screw = row.querySelector(`input[name="tooth_options[${toothNo}][screw]"]`);
            const mode = row.querySelector(`input[name="tooth_options[${toothNo}][mode]"]`);
            const group = row.querySelector(`input[name="tooth_options[${toothNo}][bridge_group]"]`);
            const setKV = (k, v) => { if (typeof v === 'undefined' || v === null) v = ''; formData.set(`tooth_options[${toothNo}][${k}]`, v); };
            if (sys) setKV('system', sys.value);
            if (margin) setKV('margin', margin.value);
            if (prosth) setKV('prosthetic', prosth.value);
            if (shade && !shade.disabled) setKV('shade', shade.value); else setKV('shade', '');
            if (other && sys && sys.value === 'Others') setKV('system_other', other.value); else setKV('system_other', '');
            if (non) setKV('non_engaging', non.value);
            if (ano) setKV('anodizing', ano.value);
            if (pmab) setKV('pmab', pmab.value);
            if (screw) setKV('screw', screw.value);
            if (mode) setKV('mode', mode.value);
            if (group) setKV('bridge_group', group.value);
        });
        // tooth_options 데이터 수집 및 FormData 설정
    } catch (e) {
        /* tooth_options 강제 설정 중 경고 무시 */
    }
    formData.set('action', 'submit_order');
     
     if (isSubmit) {
         // 제출 중에는 이탈 경고를 끔
         isSubmitting = true;
     }
     
     const endpoint = './process.php';
     
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(async (response) => {
        // Response body는 한 번만 읽어야 함. 텍스트로 읽은 뒤 JSON 파싱을 시도한다.
        const ct = (response.headers.get('content-type') || '').toLowerCase();
        const raw = await response.text();
        try {
            const data = JSON.parse(raw);
            return data;
        } catch (e) {
            throw new Error('서버 응답 처리 중 오류가 발생했습니다.');
        }
    })
    .then(data => {
        if (data.success) {
           // 주문 완료 후 확인서(컴펌레터) 페이지로 이동
           // 제출 완료 시 변경사항 플래그를 해제하여 이탈 경고가 뜨지 않도록 함
           isFormChanged = false;
           isSubmitting = true;
           window.location.href = './confirm.php?order_id=' + data.order_id;
        } else {
            alert(data.message || '저장 중 오류가 발생했습니다.');
            isSubmitting = false;
        }
    })
    .catch(error => {
        alert(error && error.message ? error.message : '저장 중 오류가 발생했습니다.');
        isSubmitting = false;
    });
}

// 폼 유효성 검사
function validateForm() {
    const requiredFields = [
        { id: 'customer_name', name: '고객명' },
        { id: 'customer_phone', name: '연락처' },
        { id: 'shipping_name', name: '받는 분' },
        { id: 'shipping_phone', name: '배송 연락처' },
        { id: 'shipping_postcode', name: '우편번호' },
        { id: 'shipping_address', name: '주소' },
        { id: 'shipping_detail', name: '상세주소' },
        { id: 'patient_name', name: '환자명' },
        { id: 'patient_age', name: '환자 나이' }
    ];

    for (let field of requiredFields) {
        const element = document.getElementById(field.id);
        if (!element || !element.value.trim()) {
            alert(field.name + '을(를) 입력해주세요.');
            if (element) {
                element.focus();
                // 해당 섹션으로 스크롤
                const section = element.closest('.order-section');
                if (section) {
                    const sectionNumber = parseInt(section.dataset.section);
                    scrollToSection(sectionNumber);
                }
            }
            return false;
        }
    }

    // 선택 치아 옵션 필수값 검증 (임플란트 시스템, 마진레벨, Others 입력)
    const checkedTeeth = document.querySelectorAll('.tooth-button input[type="checkbox"]:checked');
    if (checkedTeeth.length > 0) {
        for (const cb of checkedTeeth) {
            const toothNo = parseInt(cb.value, 10);
            const sysEl = document.querySelector(`select[name="tooth_options[${toothNo}][system]"]`);
            const marginEl = document.querySelector(`select[name="tooth_options[${toothNo}][margin]"]`);
            const otherEl = document.querySelector(`input[name="tooth_options[${toothNo}][system_other]"]`);
            if (!sysEl || !sysEl.value) {
                alert(`치아 ${toothNo}의 임플란트 시스템을 선택해주세요.`);
                scrollToSection(3);
                return false;
            }
            if (sysEl.value === 'Others') {
                if (!otherEl || !otherEl.value.trim()) {
                    alert(`치아 ${toothNo}의 기타 임플란트 시스템명을 입력해주세요.`);
                    scrollToSection(3);
                    return false;
                }
            }
            if (!marginEl || !marginEl.value) {
                alert(`치아 ${toothNo}의 마진 레벨을 선택해주세요.`);
                scrollToSection(3);
                return false;
            }
        }
    }

    // 필수 체크박스 확인
    if (!document.getElementById('agreement').checked) {
        alert('주문 약관에 동의해주세요.');
        scrollToSection(1);
        return false;
    }

    if (!document.getElementById('final_agreement').checked) {
        alert('최종 동의사항을 확인해주세요.');
        scrollToSection(5);
        return false;
    }

    // 성별 선택 확인
    if (!document.querySelector('input[name="patient_gender"]:checked')) {
        alert('환자 성별을 선택해주세요.');
        scrollToSection(2);
        return false;
    }



    return true;
}

// 생년월일 선택 초기화
function initPatientBirthSelectors() {
    const yearSelect = document.getElementById('patient_birth_year');
    const monthSelect = document.getElementById('patient_birth_month');
    const daySelect = document.getElementById('patient_birth_day');
    const hiddenInput = document.getElementById('patient_birth');

    if (!yearSelect || !monthSelect || !daySelect || !hiddenInput) return;

    // 기존 값으로 초기화
    const existing = hiddenInput.value;
    if (existing && /^\d{4}-\d{2}-\d{2}$/.test(existing)) {
        const [y, m, d] = existing.split('-');
        if (y) yearSelect.value = y;
        if (m) monthSelect.value = m;
        populateBirthDays();
        if (d) daySelect.value = d;
    } else {
        populateBirthDays();
    }

    // 변경 이벤트 연결
    yearSelect.addEventListener('change', () => { populateBirthDays(); updatePatientBirthValue(); markFormChanged(); });
    monthSelect.addEventListener('change', () => { populateBirthDays(); updatePatientBirthValue(); markFormChanged(); });
    daySelect.addEventListener('change', () => { updatePatientBirthValue(); markFormChanged(); });

    // 최초 값 설정
    updatePatientBirthValue();
}

// 월/년도에 따라 일 수 갱신
function populateBirthDays() {
    const yearSelect = document.getElementById('patient_birth_year');
    const monthSelect = document.getElementById('patient_birth_month');
    const daySelect = document.getElementById('patient_birth_day');
    if (!yearSelect || !monthSelect || !daySelect) return;

    const y = parseInt(yearSelect.value, 10);
    const m = parseInt(monthSelect.value, 10);
    let daysInMonth = 31;
    if (!isNaN(y) && !isNaN(m) && m >= 1 && m <= 12) {
        daysInMonth = new Date(y, m, 0).getDate();
    }

    const currentDay = daySelect.value;
    daySelect.innerHTML = '<option value="">일</option>';
    for (let d = 1; d <= daysInMonth; d++) {
        const dStr = String(d).padStart(2, '0');
        const opt = document.createElement('option');
        opt.value = dStr;
        opt.textContent = dStr;
        if (currentDay === dStr) opt.selected = true;
        daySelect.appendChild(opt);
    }
}

// 숨김 입력값 업데이트
function updatePatientBirthValue() {
    const yearSelect = document.getElementById('patient_birth_year');
    const monthSelect = document.getElementById('patient_birth_month');
    const daySelect = document.getElementById('patient_birth_day');
    const hiddenInput = document.getElementById('patient_birth');
    if (!yearSelect || !monthSelect || !daySelect || !hiddenInput) return;

    const y = yearSelect.value;
    const m = monthSelect.value;
    const d = daySelect.value;
    if (y && m && d) {
        hiddenInput.value = `${y}-${m}-${d}`;
    } else {
        hiddenInput.value = '';
    }
}
 
 // 치아 옵션 UI 동기화 및 로직 설정
function setupTeethOptions() {
     const body = document.getElementById('tooth-options-body');
     if (!body) return;

     // 편집 모드: 저장된 옵션/모드/브릿지 그룹을 옵션 행 렌더에 반영
     const existingCfg = (window.existingToothConfig && typeof window.existingToothConfig === 'object') ? window.existingToothConfig : {};
     const preModes = (window.toothModes && typeof window.toothModes === 'object') ? window.toothModes : {};
     const preGroups = (window.toothGroupId && typeof window.toothGroupId === 'object') ? window.toothGroupId : {};

     // 치아 체크박스 변경 시 옵션 행 추가/삭제
    document.querySelectorAll('.tooth-button input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', () => {
            const toothNo = parseInt(cb.value, 10);
            syncToothOptionRow(toothNo, cb.checked);
            markFormChanged();
             // 글로벌 옵션 적용
             applyGlobalOptionsToAllRows();
             // 선택 상태 변화 즉시 브릿지 선 재렌더링
             renderBridgeLines();
             // 브릿지 연결 동기화
             handleBridgeSelectionChange(toothNo, cb.checked);
             // 모드 배지/태그 동기화 (선택 해제 시 배지 숨김)
            if (typeof applyModesUI === 'function') applyModesUI();
            // 옵션 행 히든 필드에 현재 모드/그룹 반영
            const row = document.querySelector(`.options-row[data-tooth="${toothNo}"]`);
            if (row) {
                const m = (window.toothModes && window.toothModes[toothNo]) || '';
                const g = (window.toothGroupId && window.toothGroupId[toothNo]) || '';
                const hm = row.querySelector(`input[name="tooth_options[${toothNo}][mode]"]`);
                const hg = row.querySelector(`input[name="tooth_options[${toothNo}][bridge_group]"]`);
                if (hm) hm.value = m || '';
                if (hg) hg.value = g || '';
            }
        });
    });

    // 초기 선택 상태 반영
        document.querySelectorAll('.tooth-button input[type="checkbox"]:checked').forEach(cb => {
            const toothNo = parseInt(cb.value, 10);
            syncToothOptionRow(toothNo, true);
            // 옵션 프리필: 기존 JSON에서 해당 치아의 필드를 찾아 반영
            const sysSel = document.querySelector(`select[name="tooth_options[${toothNo}][system]"]`);
            const marginSel = document.querySelector(`select[name="tooth_options[${toothNo}][margin]"]`);
            const prosthSel = document.querySelector(`select[name="tooth_options[${toothNo}][prosthetic]"]`);
            const shadeSel = document.querySelector(`select[name="tooth_options[${toothNo}][shade]"]`);
            const otherInp = document.querySelector(`input[name="tooth_options[${toothNo}][system_other]"]`);
            const modeInp = document.querySelector(`input[name="tooth_options[${toothNo}][mode]"]`);
            const groupInp = document.querySelector(`input[name="tooth_options[${toothNo}][bridge_group]"]`);
            const nonInp = document.querySelector(`input[name="tooth_options[${toothNo}][non_engaging]"]`);
            const anoInp = document.querySelector(`input[name="tooth_options[${toothNo}][anodizing]"]`);
            const pmabInp = document.querySelector(`input[name="tooth_options[${toothNo}][pmab]"]`);
            const screwInp = document.querySelector(`input[name="tooth_options[${toothNo}][screw]"]`);
            const key = (k) => `tooth_options[${toothNo}][${k}]`;
            if (sysSel && typeof existingCfg[key('system')] !== 'undefined') sysSel.value = existingCfg[key('system')];
            if (marginSel && typeof existingCfg[key('margin')] !== 'undefined') marginSel.value = existingCfg[key('margin')];
            if (prosthSel && typeof existingCfg[key('prosthetic')] !== 'undefined') {
                prosthSel.value = existingCfg[key('prosthetic')];
                // 쉐이드 활성화 상태 동기화: 비활성화하더라도 기존 값은 유지
                const allowShade = (prosthSel.value === 'Full zirconia Crown');
                if (shadeSel) {
                    shadeSel.disabled = !allowShade;
                    // 편집 모드에서는 기존 값 유지
                    if (typeof window !== 'undefined' && window.isEditingExistingOrder) {
                        if (typeof existingCfg[key('shade')] !== 'undefined') {
                            shadeSel.value = existingCfg[key('shade')];
                        }
                    }
                }
            }
            if (shadeSel && typeof existingCfg[key('shade')] !== 'undefined') shadeSel.value = existingCfg[key('shade')];
            if (otherInp && typeof existingCfg[key('system_other')] !== 'undefined') { otherInp.value = existingCfg[key('system_other')] || ''; otherInp.style.display = (sysSel && sysSel.value === 'Others') ? 'block' : 'none'; }
            // 추가 옵션(플래그) 히든 필드 프리필: 기존 데이터가 있으면 행별로 반영
            if (nonInp && typeof existingCfg[key('non_engaging')] !== 'undefined') nonInp.value = existingCfg[key('non_engaging')] ? '1' : '';
            if (anoInp && typeof existingCfg[key('anodizing')] !== 'undefined') anoInp.value = existingCfg[key('anodizing')] ? '1' : '';
            if (pmabInp && typeof existingCfg[key('pmab')] !== 'undefined') pmabInp.value = existingCfg[key('pmab')] ? '1' : '';
            if (screwInp && typeof existingCfg[key('screw')] !== 'undefined') screwInp.value = existingCfg[key('screw')] ? '1' : '';
            // 모드/그룹: 우선 기존 모드 맵을 적용, 없으면 JSON에서 보조 적용
            const pm = preModes[toothNo];
            const pg = preGroups[toothNo];
            if (modeInp) modeInp.value = (typeof pm !== 'undefined' ? pm : (existingCfg[key('mode')] || ''));
            if (groupInp) groupInp.value = (typeof pg !== 'undefined' ? pg : (existingCfg[key('bridge_group')] || ''));
        // 편집 모드: 기존 모드/그룹 뱃지 반영 유지
        if (typeof window !== 'undefined' && window.isEditingExistingOrder) {
            if (modeInp && typeof existingCfg[key('mode')] !== 'undefined' && !pm) { modeInp.value = existingCfg[key('mode')]; }
            if (groupInp && typeof existingCfg[key('bridge_group')] !== 'undefined' && !pg) { groupInp.value = existingCfg[key('bridge_group')]; }
        }
    });

     // 글로벌 옵션 이벤트 등록
     const globalNon = document.getElementById('global_non_engaging');
     const globalAno = document.getElementById('global_anodizing');
     const globalPmab = document.getElementById('global_pmab');
     const globalScrew = document.getElementById('global_screw');
     if (globalNon) globalNon.addEventListener('change', applyGlobalOptionsToAllRows);
     if (globalAno) globalAno.addEventListener('change', applyGlobalOptionsToAllRows);
     if (globalPmab) globalPmab.addEventListener('change', applyGlobalOptionsToAllRows);
     if (globalScrew) globalScrew.addEventListener('change', applyGlobalOptionsToAllRows);
     
     // 기존 주문 편집 모드에서 글로벌 체크박스 상태 복원
     if (typeof window !== 'undefined' && window.isEditingExistingOrder) {
         if (globalNon && typeof window.globalNonEngagingChecked !== 'undefined') {
             globalNon.checked = window.globalNonEngagingChecked;
         }
         if (globalAno && typeof window.globalAnodizingChecked !== 'undefined') {
             globalAno.checked = window.globalAnodizingChecked;
         }
         if (globalPmab && typeof window.globalPmabChecked !== 'undefined') {
             globalPmab.checked = window.globalPmabChecked;
         }
         if (globalScrew && typeof window.globalScrewChecked !== 'undefined') {
             globalScrew.checked = window.globalScrewChecked;
         }
         // 글로벌 체크박스 상태 복원 후 개별 치아 옵션에 적용
         applyGlobalOptionsToAllRows();
     }
     
     // 초기 로드 시: 기존 주문 편집 모드에서는 히든 필드 프리필을 유지하고 덮어쓰지 않음
     if (!(typeof window !== 'undefined' && window.isEditingExistingOrder)) {
         // 신규 주문일 때만 현재 글로벌 체크박스 상태를 옵션 히든필드에 반영
         applyGlobalOptionsToAllRows();
     }
        // 초기 상태에서 선 렌더링
        renderBridgeLines();

    // 편집 모드 폴백: 체크된 치아가 없고 기존 설정이 있으면 해당 번호를 체크 후 행 생성
    if (typeof window !== 'undefined' && window.isEditingExistingOrder) {
        const anyRow = document.querySelector('#tooth-options-body .options-row');
        if (!anyRow && window.existingToothConfig) {
            const nums = [];
            for (var k in window.existingToothConfig) {
                if (!window.existingToothConfig.hasOwnProperty(k)) continue;
                var m = k.match(/^tooth_options\[(\d+)\]\[/);
                if (m) { var n = parseInt(m[1],10); if (nums.indexOf(n)===-1) nums.push(n); }
            }
            nums.sort(function(a,b){return a-b});
            nums.forEach(function(n){
                var cb = document.querySelector('.tooth-button input[type="checkbox"][value="'+n+'"]');
                if (cb) { cb.checked = true; syncToothOptionRow(n, true); }
            });
            applyGlobalOptionsToAllRows();
            renderBridgeLines();
        }
    }
}

 // 치아 선택 해제 시 브릿지 연결 제거
 function handleBridgeSelectionChange(toothNo, isChecked) {
     const point = document.querySelector(`.bridge-point[data-tooth="${toothNo}"]`);
     if (!isChecked) {
         // 활성 포인트 초기화
         if (bridgeActiveStart === toothNo) {
             bridgeActiveStart = null;
             if (point) point.classList.remove('active');
         }
         // 해당 치아와 관련된 연결 제거
         const toDelete = [];
         bridgeConnections.forEach(pair => {
             const [from, to] = pair.split('-').map(v => parseInt(v, 10));
             if (from === toothNo || to === toothNo) toDelete.push(pair);
         });
         toDelete.forEach(p => bridgeConnections.delete(p));
         renderBridgeLines();
     }
 }

 function syncToothOptionRow(toothNo, isChecked) {
     const body = document.getElementById('tooth-options-body');
     if (!body) return;

     const existing = body.querySelector(`.options-row[data-tooth="${toothNo}"]`);
     if (isChecked) {
         if (!existing) {
             const row = createOptionRow(toothNo);
             // 숫자 기준 정렬 삽입
             const rows = Array.from(body.querySelectorAll('.options-row'));
             const insertBefore = rows.find(r => parseInt(r.dataset.tooth, 10) > toothNo);
             if (insertBefore) body.insertBefore(row, insertBefore); else body.appendChild(row);
         }
     } else {
         if (existing) existing.remove();
     }
 }

function createOptionRow(toothNo) {
    const row = document.createElement('div');
    row.className = 'options-row';
    row.dataset.tooth = String(toothNo);
    // 브릿지 그룹 데이터 표시(시각적 그룹화용)
    const initGid = (window.toothGroupId && window.toothGroupId[toothNo]) || '';
    row.dataset.bridgeGroup = initGid ? String(initGid) : '';
    // 그룹 색상 클래스 적용
    if (row.dataset.bridgeGroup) {
        const colorIdx = (parseInt(row.dataset.bridgeGroup,10)-1)%4 + 1;
        row.classList.add('bridge-color-'+colorIdx);
    }
    const mode = (window.toothModes && window.toothModes[toothNo]) || '';
    const modeTagHTML = mode ? `<span class="tooth-mode-tag ${mode==='bridge'?'bridge':'general'}">${mode==='bridge'?'브릿지':'싱글'}</span>` : '';
    const groupBadgeHTML = (mode==='bridge' && row.dataset.bridgeGroup)
        ? `<span class="group-badge color-${(parseInt(row.dataset.bridgeGroup,10)-1)%4 + 1}">G${row.dataset.bridgeGroup}</span>`
        : '';
    row.innerHTML = `
        <div class="option-main-row">
            <div class="no-label"><span class="tooth-no">${toothNo}</span>${modeTagHTML}${groupBadgeHTML}</div>
            <div>
                <select name="tooth_options[${toothNo}][system]" required>
                    <option value="">선택</option>
                    <option>Straumann BLX RB</option>
                    <option>Straumann BLX WB</option>
                     <option>Straumann TLX RT</option>
                     <option>Straumann TLX WT</option>
                     <option>Straumann Bone level NC</option>
                     <option>Straumann Bone level RC</option>
                     <option>Straumann Tissue level RN</option>
                     <option>Straumann Tissue level WN</option>
                     <option>Others</option>
                 </select>
                 <input type="text" name="tooth_options[${toothNo}][system_other]" placeholder="기타 시스템 입력" class="system-other-input" style="display:none; margin-top:6px;">
             </div>
             <div>
                 <select name="tooth_options[${toothNo}][margin]" required>
                     <option value="">선택</option>
                     <option>Equal</option>
                     <option>Sub</option>
                     <option>Supra</option>
                 </select>
             </div>
             <div>
                 <select name="tooth_options[${toothNo}][prosthetic]">
                     <option value="의뢰 안함" selected>의뢰 안함</option>
                     <option value="Full zirconia Crown">Full zirconia Crown</option>
                     <option value="Temporary crown">Temporary crown</option>
                 </select>
             </div>
             <div>
                 <select name="tooth_options[${toothNo}][shade]">
                     <option value="">선택(옵션)</option>
                     <option value="A1">A1</option>
                     <option value="A2">A2</option>
                     <option value="A3">A3</option>
                     <option value="A3.5">A3.5</option>
                     <option value="A4">A4</option>
                     <option value="B1">B1</option>
                     <option value="B2">B2</option>
                     <option value="B3">B3</option>
                     <option value="B4">B4</option>
                     <option value="C1">C1</option>
                     <option value="C2">C2</option>
                     <option value="C3">C3</option>
                     <option value="C4">C4</option>
                     <option value="D2">D2</option>
                     <option value="D3">D3</option>
                     <option value="D4">D4</option>
                 </select>
             </div>
         </div>
         <input type="hidden" name="tooth_options[${toothNo}][non_engaging]" value="">
         <input type="hidden" name="tooth_options[${toothNo}][anodizing]" value="">
         <input type="hidden" name="tooth_options[${toothNo}][pmab]" value="">
         <input type="hidden" name="tooth_options[${toothNo}][screw]" value="">
     `;
     const systemSelect = row.querySelector(`select[name="tooth_options[${toothNo}][system]"]`);
     const systemOther = row.querySelector(`input[name="tooth_options[${toothNo}][system_other]"]`);
     const prostheticSelect = row.querySelector(`select[name="tooth_options[${toothNo}][prosthetic]"]`);
     const shadeSelect = row.querySelector(`select[name="tooth_options[${toothNo}][shade]"]`);
     // 모드/브릿지 그룹 저장용 히든필드
     const hiddenMode = document.createElement('input');
     hiddenMode.type = 'hidden';
     hiddenMode.name = `tooth_options[${toothNo}][mode]`;
    hiddenMode.value = mode || '';
     const hiddenGroup = document.createElement('input');
     hiddenGroup.type = 'hidden';
     hiddenGroup.name = `tooth_options[${toothNo}][bridge_group]`;
    hiddenGroup.value = (window.toothGroupId && window.toothGroupId[toothNo]) ? String(window.toothGroupId[toothNo]) : '';
     row.appendChild(hiddenMode);
     row.appendChild(hiddenGroup);

     // Others 선택 시 기타 시스템 입력 표시
     function toggleSystemOther() {
         if (systemSelect && systemOther) {
             if (systemSelect.value === 'Others') {
                 systemOther.style.display = 'block';
             } else {
                 systemOther.style.display = 'none';
                 systemOther.value = '';
             }
         }
     }
     if (systemSelect) {
         systemSelect.addEventListener('change', () => { toggleSystemOther(); markFormChanged(); });
         toggleSystemOther();
     }

     // 쉐이드 선택 가능 조건: Full zirconia Crown 일 때만 가능
     function updateShadeAvailability() {
         if (!shadeSelect || !prostheticSelect) return;
         const p = prostheticSelect.value;
         const allowShade = (p === 'Full zirconia Crown');
         shadeSelect.disabled = !allowShade;
         if (!allowShade) {
             // 기존 주문 편집 모드에서는 저장된 쉐이드 값을 보존
             const nm = shadeSelect.name || '';
             const hasExisting = (typeof window !== 'undefined' && window.isEditingExistingOrder && window.existingToothConfig && nm && (typeof window.existingToothConfig[nm] !== 'undefined'));
             if (hasExisting) {
                 shadeSelect.value = String(window.existingToothConfig[nm] || '');
             } else {
                 shadeSelect.value = '';
             }
         }
     }
     if (prostheticSelect) {
         prostheticSelect.addEventListener('change', () => { updateShadeAvailability(); markFormChanged(); });
         // 초기 상태 반영
         updateShadeAvailability();
     }

     // 변경 감지 연결
     row.querySelectorAll('select, input').forEach(el => {
         el.addEventListener('change', markFormChanged);
     });
     return row;
 }

 // 글로벌 옵션 적용 함수
 function applyGlobalOptionsToAllRows() {
     const globalNon = document.getElementById('global_non_engaging');
     const globalAno = document.getElementById('global_anodizing');
     const globalPmab = document.getElementById('global_pmab');
     const globalScrew = document.getElementById('global_screw');
     const rows = document.querySelectorAll('#tooth-options-body .options-row');
     rows.forEach(row => {
         const non = row.querySelector('input[type="hidden"][name$="[non_engaging]"]');
         const ano = row.querySelector('input[type="hidden"][name$="[anodizing]"]');
         const pmab = row.querySelector('input[type="hidden"][name$="[pmab]"]');
         const screw = row.querySelector('input[type="hidden"][name$="[screw]"]');
         if (globalNon && non) non.value = globalNon.checked ? '1' : '';
         if (globalAno && ano) ano.value = globalAno.checked ? '1' : '';
         if (globalPmab && pmab) pmab.value = globalPmab.checked ? '1' : '';
         // PMAB 체크 시 정품 스크류 자동 적용 및 전역 스크류도 체크
         if (globalPmab && globalScrew) {
             if (globalPmab.checked) {
                 globalScrew.checked = true;
                 globalScrew.disabled = true;
             } else {
                 globalScrew.disabled = false;
             }
         }
         // 저장 및 확인 페이지 표시를 위해 스크류도 '1'로 저장
         if (globalScrew && screw) screw.value = globalScrew.checked ? '1' : '';
     });
 }

 // ===== 브릿지(연결) UI & 로직 =====
 function setupBridgeUI() {
     // 포인트 클릭 이벤트
     document.querySelectorAll('.bridge-point').forEach(btn => {
         btn.addEventListener('click', onBridgePointClick);
     });
     window.addEventListener('resize', renderBridgeLines);
     window.addEventListener('scroll', renderBridgeLines);
     renderBridgeLines();
 }

 function onBridgePointClick(e) {
     const btn = e.currentTarget;
     const toothNo = parseInt(btn.dataset.tooth, 10);
     const cb = document.getElementById('tooth-' + toothNo);
     if (!cb || !cb.checked) return; // 미선택 치아는 무시
     if (window.toothModes && window.toothModes[toothNo]) return; // 확정된 치아(일반/브릿지)는 새 연결에 포함하지 않음

     // 시작 포인트가 없으면 설정
     if (bridgeActiveStart == null) {
         bridgeActiveStart = toothNo;
         btn.classList.add('active');
         return;
     }

     // 같은 포인트 클릭 시 선택 해제
     if (bridgeActiveStart === toothNo) {
         btn.classList.remove('active');
         bridgeActiveStart = null;
         return;
     }

     // 연속 치아만 연결 허용
     if (!isConsecutiveTeeth(bridgeActiveStart, toothNo)) {
         // 비연속이면 활성 포인트를 현재로 교체(연속 클릭 유도)
         const prev = document.querySelector(`.bridge-point[data-tooth="${bridgeActiveStart}"]`);
         if (prev) prev.classList.remove('active');
         bridgeActiveStart = toothNo;
         btn.classList.add('active');
         return;
     }

     const pairKey = formatPairKey(bridgeActiveStart, toothNo);
     if (bridgeConnections.has(pairKey)) {
         bridgeConnections.delete(pairKey); // 이미 있으면 토글로 삭제
     } else {
         bridgeConnections.add(pairKey);
     }

     // 체인 연결을 위해 현재 포인트를 활성 포인트로 유지
     const prev = document.querySelector(`.bridge-point[data-tooth="${bridgeActiveStart}"]`);
     if (prev) prev.classList.remove('active');
     bridgeActiveStart = toothNo;
     btn.classList.add('active');

     renderBridgeLines();
 }

 function isConsecutiveTeeth(a, b) {
     const ga = toothGroup(a);
     const gb = toothGroup(b);
     return ga === gb && Math.abs(a - b) === 1;
 }

 function toothGroup(n) {
     if (n >= 11 && n <= 18) return 'upper_right';
     if (n >= 21 && n <= 28) return 'upper_left';
     if (n >= 31 && n <= 38) return 'lower_left';
     if (n >= 41 && n <= 48) return 'lower_right';
     return 'unknown';
 }

 // 상/하악 단일 행 구분 (상단/하단 브릿지 전용)
 function toothRow(n) {
     if ((n >= 11 && n <= 18) || (n >= 21 && n <= 28)) return 'upper';
     if ((n >= 31 && n <= 38) || (n >= 41 && n <= 48)) return 'lower';
     return 'unknown';
 }

 function formatPairKey(a, b) {
     const from = Math.min(a, b);
     const to = Math.max(a, b);
     return `${from}-${to}`;
 }

 function renderBridgeLines() {
     const overlay = document.getElementById('bridge-overlay');
     const linesGroup = overlay ? overlay.querySelector('#bridge-lines') : null;
     if (!overlay || !linesGroup) return;

     // 기존 선 삭제
     while (linesGroup.firstChild) linesGroup.removeChild(linesGroup.firstChild);

    // SVG 좌표계를 오버레이의 실제 픽셀 크기에 맞춤
    const overlayRect = overlay.getBoundingClientRect();
    overlay.setAttribute('width', String(overlayRect.width));
    overlay.setAttribute('height', String(overlayRect.height));
    overlay.setAttribute('viewBox', `0 0 ${overlayRect.width} ${overlayRect.height}`);

     // 이전 스킵 표시 제거
     document.querySelectorAll('.tooth-button.bridge-skipped').forEach(el => el.classList.remove('bridge-skipped'));

    let hasSkippedGap = false;
    let hasLines = false;
    const drawnPairs = new Set();
    const canConnect = (a,b) => {
        const ma = window.toothModes ? window.toothModes[a] : null;
        const mb = window.toothModes ? window.toothModes[b] : null;
        const ga = window.toothGroupId ? window.toothGroupId[a] : null;
        const gb = window.toothGroupId ? window.toothGroupId[b] : null;
        // 줄은 브릿지 옵션을 확정했을 때만 표시, 같은 그룹끼리만
        return ma === 'bridge' && mb === 'bridge' && ga && gb && ga === gb;
    };
    const drawPair = (a,b) => {
        const elFrom = document.querySelector(`.bridge-point[data-tooth="${a}"]`);
        const elTo = document.querySelector(`.bridge-point[data-tooth="${b}"]`);
        if (!elFrom || !elTo) return;
        // 모드/그룹 게이트
        if (!canConnect(a,b)) return;
        const r1 = elFrom.getBoundingClientRect();
        const r2 = elTo.getBoundingClientRect();
        const ovRect = overlay.getBoundingClientRect();
        const x1 = r1.left + r1.width / 2 - ovRect.left;
        const y1 = r1.top + r1.height / 2 - ovRect.top;
        const x2 = r2.left + r2.width / 2 - ovRect.left;
        const y2 = r2.top + r2.height / 2 - ovRect.top;
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', x1);
        line.setAttribute('y1', y1);
        line.setAttribute('x2', x2);
        line.setAttribute('y2', y2);
        line.setAttribute('class', 'bridge-line');
        linesGroup.appendChild(line);
        hasLines = true;
    };

    // 선택된 치아의 하단 주황 포인트들을 연속쌍으로 연결
    const selected = Array.from(document.querySelectorAll('.tooth-button input[type="checkbox"]:checked'))
        .map(cb => parseInt(cb.value, 10))
        .sort((a,b) => a - b);

    // 상단/하단 단일 그룹으로 묶어 브릿지 연결 (상단끼리, 하단끼리)
    const groups = { upper: [], lower: [] };
    selected.forEach(n => { const r = toothRow(n); if (groups[r]) groups[r].push(n); });

    // 상/하악 아치의 실제 순서를 기준으로 연결 및 중간 블록 처리
    const getRowOrder = (row) => {
        if (row === 'upper') return [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28];
        if (row === 'lower') return [48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38];
        return [];
    };

    Object.keys(groups).forEach(row => {
        const arr = groups[row];
        const order = getRowOrder(row);
        if (!arr || arr.length < 2 || order.length === 0) return;

        // 치아 번호 -> 아치 인덱스 매핑
        const idxMap = {};
        for (let i=0;i<order.length;i++) idxMap[order[i]] = i;

        // 아치 순서 기준으로 선택 치아 정렬
        arr.sort((a,b) => (idxMap[a] ?? 999) - (idxMap[b] ?? 999));

        for (let i=0;i<arr.length-1;i++) {
            const a = arr[i];
            const b = arr[i+1];
            const ia = idxMap[a];
            const ib = idxMap[b];
            if (ia == null || ib == null) continue;

            const dist = Math.abs(ib - ia);
            if (dist >= 1) {
                // 연결 허용되는 경우에만 선/스킵 표시 처리
                if (canConnect(a,b)) {
                    const key = formatPairKey(a,b);
                    if (!drawnPairs.has(key)) {
                        drawPair(a, b);
                        drawnPairs.add(key);
                    }
                    if (dist >= 2) {
                        // 중간 치아들을 모두 스킵 표시 (비선택인 경우만)
                        const startIdx = Math.min(ia, ib) + 1;
                        const endIdx = Math.max(ia, ib) - 1;
                        for (let midIdx = startIdx; midIdx <= endIdx; midIdx++) {
                            const midTooth = order[midIdx];
                            const midBtn = document.querySelector(`.tooth-button[data-tooth="${midTooth}"]`);
                            const midCb = document.getElementById('tooth-' + midTooth);
                            if (midBtn && (!midCb || !midCb.checked)) {
                                midBtn.classList.add('bridge-skipped');
                                hasSkippedGap = true;
                            }
                        }
                    }
                }
            }
        }
    });

    // 클릭으로 생성된 브릿지 연결도 그리기 (중복 방지)
    if (typeof bridgeConnections !== 'undefined' && bridgeConnections && bridgeConnections.size > 0) {
        bridgeConnections.forEach(key => {
            const parts = key.split('-');
            if (parts.length !== 2) return;
            const a = parseInt(parts[0], 10);
            const b = parseInt(parts[1], 10);
            // 같은 상/하악 행이며, 두 치아 모두 선택된 경우만 표시
            const ra = toothRow(a);
            const rb = toothRow(b);
            const cbA = document.getElementById('tooth-' + a);
            const cbB = document.getElementById('tooth-' + b);
            if (ra !== rb || !cbA || !cbA.checked || !cbB || !cbB.checked) return;
            if (!drawnPairs.has(key)) {
                drawPair(a,b);
                drawnPairs.add(key);
            }
        });
    }

    // 안내 문구는 항상 표시되며, 라인/갭 상태와 무관합니다
}
 
 // 다음 우편번호 API - 배송지
 function execDaumPostcode() {
     new daum.Postcode({
         oncomplete: function(data) {
             document.getElementById('shipping_postcode').value = data.zonecode;
             document.getElementById('shipping_address').value = data.address;
             document.getElementById('shipping_detail').focus();
             markFormChanged();
         }
     }).open();
 }
 
 // 다음 우편번호 API - 랩 주소
function execLabPostcode() {
    new daum.Postcode({
        oncomplete: function(data) {
            document.getElementById('lab_postcode').value = data.zonecode;
            document.getElementById('lab_address').value = data.address;
            document.getElementById('lab_address_detail').focus();
            markFormChanged();
        }
    }).open();
}

// 러버 인상체 전달 옵션 토글
document.addEventListener('DOMContentLoaded', function() {
    const rubberDeliveryCheckbox = document.getElementById('rubber_impression_delivery');
    const deliveryAddressSection = document.getElementById('delivery_address_section');
    
    if (rubberDeliveryCheckbox && deliveryAddressSection) {
        // 페이지 로드 시 체크박스 상태에 따라 섹션 표시
        if (rubberDeliveryCheckbox.checked) {
            deliveryAddressSection.style.display = 'block';
            document.getElementById('delivery_hope_date').required = true;
        } else {
            deliveryAddressSection.style.display = 'none';
            document.getElementById('delivery_hope_date').required = false;
        }
        
        rubberDeliveryCheckbox.addEventListener('change', function() {
            if (this.checked) {
                deliveryAddressSection.style.display = 'block';
                // 필수 필드 설정
                document.getElementById('delivery_hope_date').required = true;
            } else {
                deliveryAddressSection.style.display = 'none';
                // 필수 필드 해제 및 값 초기화
                document.getElementById('delivery_hope_date').required = false;
                document.getElementById('delivery_hope_date').value = '';
            }
            markFormChanged();
        });
        const dateBtn = document.getElementById('delivery_hope_date_btn');
        const dateInput = document.getElementById('delivery_hope_date');
        if (dateBtn && dateInput) {
            dateBtn.addEventListener('click', function(){ dateInput.showPicker ? dateInput.showPicker() : dateInput.focus(); });
        }
    }
});
 
 // 페이지 떠나기 전 확인 (제출 중에는 알럿 비활성화)
 const beforeUnloadHandler = function(e) {
     if (isFormChanged && !isSubmitting) {
         e.preventDefault();
         e.returnValue = '저장되지 않은 변경사항이 있습니다. 페이지를 떠나시겠습니까?';
     }
 };
 window.addEventListener('beforeunload', beforeUnloadHandler);
 </script>

 <!-- 최소한의 확인 UI 스타일 -->
 <style>
   .tooth-confirm-btn { position:absolute; top:8px; right:8px; z-index:20; padding:8px 12px; border-radius:6px; border:1px solid #ced4da; background:#2b7cff; color:#fff; font-size:14px; font-weight:600; box-shadow:0 2px 6px rgba(0,0,0,0.15); display:none; }
   .tooth-confirm-btn:hover { background:#1f6dff; }
   .tooth-mode-modal { position:fixed; inset:0; background:rgba(0,0,0,0.35); display:none; align-items:center; justify-content:center; z-index:1000; }
   .tooth-mode-panel { background:#fff; border-radius:12px; padding:20px; width:320px; max-width:90vw; box-shadow:0 12px 28px rgba(0,0,0,0.22); text-align:center; }
   .tooth-mode-panel h4 { margin:0 0 6px; font-size:16px; }
   .tooth-mode-desc { margin:0 0 12px; font-size:12px; color:#6c757d; }
   .tooth-mode-actions { display:flex; gap:10px; justify-content:center; }
   .tooth-mode-actions button { flex:1; padding:10px 12px; font-size:14px; border-radius:8px; border:1px solid #ced4da; cursor:pointer; }
   .btn-normal { background:#f8f9fa; }
   .btn-normal:hover { background:#eef1f4; }
   .btn-bridge { background:#2b7cff; color:#fff; border-color:#2b7cff; }
   .btn-bridge:hover { background:#1f6dff; border-color:#1f6dff; }
   .bridge-point.normal-mode { background:#fff !important; border:2px solid #ff9900; }
   /* 그룹 배지 스타일 */
   .group-badge { display:inline-block; margin-left:6px; padding:2px 6px; font-size:11px; font-weight:700; line-height:1; border-radius:8px; border:1px solid transparent; background:#fff; color:#333; }
   .group-badge.color-1 { background:#fff3e6; border-color:#ffc078; color:#a05a00; }
   .group-badge.color-2 { background:#e7f0ff; border-color:#a5c7ff; color:#0b5ed7; }
   .group-badge.color-3 { background:#e9f7ef; border-color:#a8e1b8; color:#2b8a3e; }
   .group-badge.color-4 { background:#f4e9fb; border-color:#dab6f2; color:#862e9c; }
 .date-inline { display:flex; align-items:center; gap:4px; }
.date-inline input[type="date"] { width:150px; min-width:140px; height:28px; line-height:28px; padding:0 8px; font-size:14px; flex:0 0 auto; }
.date-icon { width:28px; height:28px; border:1px solid #ced4da; border-radius:6px; background:#fff url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"%23666\" class=\"bi bi-calendar\" viewBox=\"0 0 16 16\"><path d=\"M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v1H0V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5z\"/><path d=\"M16 14V5H0v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2z\"/></svg>') no-repeat center/14px; cursor:pointer; }
.date-icon:hover { background-color:#f8f9fa; }
</style>

<script>
 (function(){
   // 치아별 모드 저장: 'general' | 'bridge'
  window.toothModes = window.toothModes || {};
  // 브릿지 그룹 ID 저장: 같은 그룹끼리만 확정 브릿지 라인 허용
  window.toothGroupId = window.toothGroupId || {};
  window.bridgeGroupSeq = window.bridgeGroupSeq || 1;

   function getCheckedTeeth(){
     return Array.from(document.querySelectorAll('.tooth-button input[type="checkbox"]:checked'))
       .map(cb => parseInt(cb.value,10)).filter(n => !isNaN(n));
   }
   function applyModesUI(){
     document.querySelectorAll('.bridge-point').forEach(pt => {
       const n = parseInt(pt.dataset.tooth,10);
       const mode = window.toothModes[n];
       const toothBtn = pt.closest('.tooth-button');
       const isChecked = !!(toothBtn && toothBtn.querySelector('input[type="checkbox"]').checked);
       if (mode === 'general') {
         pt.classList.add('normal-mode');
         pt.classList.remove('active');
       } else {
         pt.classList.remove('normal-mode');
       }
       // 차트 배지 업데이트
       if (toothBtn) {
         const vis = toothBtn.querySelector('.tooth-visual');
         if (vis) {
           let badge = vis.querySelector('.tooth-mode-badge');
           if (!badge) { badge = document.createElement('span'); badge.className = 'tooth-mode-badge'; vis.appendChild(badge); }
           // 선택된 치아만 배지 표시, 해제 시 숨김
           if (!isChecked) {
             badge.textContent = '';
             badge.classList.remove('bridge');
             badge.style.display = 'none';
           } else if (mode === 'bridge') {
             const gid = window.toothGroupId ? window.toothGroupId[n] : '';
             badge.textContent = gid ? `브릿지 G${gid}` : '브릿지';
             badge.classList.add('bridge');
             badge.style.display = 'inline-block';
           } else if (mode === 'general') {
             badge.textContent = '싱글';
             badge.classList.remove('bridge');
             badge.style.display = 'inline-block';
           } else {
             badge.textContent = '';
             badge.style.display = 'none';
           }
         }
       }
       // 옵션 행 모드 태그 및 브릿지 그룹 표시 업데이트
       const row = document.querySelector(`.options-row[data-tooth="${n}"]`);
       if (row) {
         // bridge group dataset(브릿지 확정 + 체크된 경우만)
         const gid = (window.toothGroupId && window.toothGroupId[n]) || '';
         row.dataset.bridgeGroup = (isChecked && mode === 'bridge' && gid) ? String(gid) : '';
         // 색상 클래스 초기화 후 그룹에 따른 색상 적용
         row.classList.remove('bridge-color-1','bridge-color-2','bridge-color-3','bridge-color-4');
         if (row.dataset.bridgeGroup) {
           const colorIdx = (parseInt(row.dataset.bridgeGroup,10)-1)%4 + 1; // 1..4 순환
           row.classList.add('bridge-color-'+colorIdx);
         }
         // 모드 태그와 그룹 배지
         const tag = row.querySelector('.tooth-mode-tag');
         const noLabel = row.querySelector('.no-label');
         let badge = noLabel ? noLabel.querySelector('.group-badge') : null;
         // 히든 필드에 모드/그룹 기록
     const hiddenMode = row.querySelector(`input[name=\"tooth_options[${n}][mode]\"]`);
     const hiddenGroup = row.querySelector(`input[name=\"tooth_options[${n}][bridge_group]\"]`);
         if (hiddenMode) hiddenMode.value = mode || '';
         if (hiddenGroup) hiddenGroup.value = gid || '';
         if (!isChecked) {
           if (tag) { tag.textContent=''; tag.style.display='none'; tag.classList.remove('bridge'); tag.classList.remove('general'); }
           if (badge) { badge.remove(); }
           if (hiddenMode) hiddenMode.value = '';
           if (hiddenGroup) hiddenGroup.value = '';
         } else if (mode === 'bridge') {
           if (tag) { tag.textContent = '브릿지'; tag.classList.add('bridge'); tag.classList.remove('general'); tag.style.display='inline-block'; }
           else if (noLabel) {
             const span = document.createElement('span'); span.className='tooth-mode-tag bridge'; span.textContent='브릿지'; noLabel.appendChild(span);
           }
           // 그룹 배지 업데이트
           if (noLabel && row.dataset.bridgeGroup) {
             const colorIdx = (parseInt(row.dataset.bridgeGroup,10)-1)%4 + 1;
             if (!badge) { badge = document.createElement('span'); badge.className='group-badge'; noLabel.appendChild(badge); }
             badge.textContent = 'G'+row.dataset.bridgeGroup;
             badge.className = 'group-badge color-'+colorIdx;
           } else if (badge) {
             badge.remove();
           }
         } else if (mode === 'general') {
           if (tag) { tag.textContent = '싱글'; tag.classList.add('general'); tag.classList.remove('bridge'); tag.style.display='inline-block'; }
           else if (noLabel) {
             const span = document.createElement('span'); span.className='tooth-mode-tag general'; span.textContent='싱글'; noLabel.appendChild(span);
           }
           if (badge) { badge.remove(); }
         } else {
           if (tag) { tag.textContent=''; tag.style.display='none'; }
           if (badge) { badge.remove(); }
         }
       }
     });
   }
   function removeConnectionsFor(teethArr){
     if (!window.bridgeConnections) return;
     const del = [];
     window.bridgeConnections.forEach(key => {
       const [a,b] = key.split('-').map(v=>parseInt(v,10));
       if (teethArr.includes(a) || teethArr.includes(b)) del.push(key);
     });
     del.forEach(k => window.bridgeConnections.delete(k));
   }

   function setupConfirmUI(){
     const chart = document.querySelector('.unified-tooth-chart');
     const btn = document.createElement('button');
     btn.type='button'; btn.id='tooth-confirm-btn'; btn.className='tooth-confirm-btn'; btn.textContent='확인';
     if (chart){
       const cs = getComputedStyle(chart);
       if (cs.position==='static') chart.style.position='relative';
       chart.appendChild(btn);
     } else {
       btn.style.position='fixed'; btn.style.top='16px'; btn.style.right='16px'; document.body.appendChild(btn);
     }

     const modal = document.createElement('div');
     modal.id='tooth-mode-modal'; modal.className='tooth-mode-modal';
     modal.innerHTML = '<div class="tooth-mode-panel">\n  <h4>싱글/브릿지 선택</h4>\n  <p class="tooth-mode-desc">선택한 치아에 적용할 옵션을 선택하세요.</p>\n  <div class="tooth-mode-actions">\n    <button type="button" class="btn-normal" id="btn-mode-normal">싱글</button>\n    <button type="button" class="btn-bridge" id="btn-mode-bridge">브릿지</button>\n  </div>\n</div>';
     document.body.appendChild(modal);

     // 현재 체크된 치아 중 아직 모드가 지정되지 않은 치아만 확인 대상으로 인식
     function getUnassignedCheckedTeeth(){
       return getCheckedTeeth().filter(n => !window.toothModes[n]);
     }
     function updateBtnVisibility(){ btn.style.display = getUnassignedCheckedTeeth().length>0 ? 'block' : 'none'; }
     document.querySelectorAll('.tooth-button input[type="checkbox"]').forEach(cb => cb.addEventListener('change', updateBtnVisibility));
     updateBtnVisibility();

     btn.addEventListener('click', ()=>{ modal.style.display='flex'; });
     modal.addEventListener('click', (e)=>{ if(e.target===modal) modal.style.display='none'; });

    modal.querySelector('#btn-mode-normal').addEventListener('click', ()=>{
      const arr=getUnassignedCheckedTeeth();
      arr.forEach(n=>{ window.toothModes[n]='general'; window.toothGroupId[n]=null; });
      removeConnectionsFor(arr);
      applyModesUI(); if (typeof renderBridgeLines==='function') renderBridgeLines();
      // 모드 확정 후 팝업만 닫고, 기존 체크 상태는 유지
      modal.style.display='none'; updateBtnVisibility();
    });
    modal.querySelector('#btn-mode-bridge').addEventListener('click', ()=>{
      const arr=getUnassignedCheckedTeeth();
      const gid = window.bridgeGroupSeq++;
      arr.forEach(n=>{ window.toothModes[n]='bridge'; window.toothGroupId[n]=gid; });
      applyModesUI(); if (typeof renderBridgeLines==='function') renderBridgeLines();
      // 모드 확정 후 팝업만 닫고, 기존 체크 상태는 유지
      modal.style.display='none'; updateBtnVisibility();
    });

     window.applyModesUI = applyModesUI; // 초기 렌더 시 동기화용
   }

   document.addEventListener('DOMContentLoaded', function(){
     setupConfirmUI();
     applyModesUI();
     if (typeof renderBridgeLines==='function') renderBridgeLines();
     // 초기화 버튼 연결 (하단 폼/차트 내부 둘 다 지원)
     const resetBtn = document.getElementById('resetAllBtn');
     const resetChartBtn = document.getElementById('resetAllBtnChart');
     [resetBtn, resetChartBtn].forEach(btn => {
       if (btn) btn.addEventListener('click', function(){ if (typeof resetAllTeeth === 'function') resetAllTeeth(); });
     });
     // 초기화 완료 후에만 관련 요소 노출
     document.body.classList.add('hydrated');
   });

   // 전체 초기화 함수
   window.resetAllTeeth = function(){
     try {
       // 모드/그룹 초기화
       window.toothModes = {};
       window.toothGroupId = {};
       window.bridgeGroupSeq = 1;
       // 연결 초기화
       if (window.bridgeConnections) { window.bridgeConnections.clear(); }
       window.bridgeActiveStart = null;
       // 체크박스 해제
       document.querySelectorAll('.tooth-button input[type="checkbox"]').forEach(cb => { cb.checked = false; });
       // 옵션 행 삭제
       const body = document.getElementById('tooth-options-body');
       if (body) { body.innerHTML = ''; }
       // 배지 제거
       document.querySelectorAll('.tooth-visual .tooth-mode-badge').forEach(el => { el.remove(); });
       // 브릿지 포인트 상태 리셋
       document.querySelectorAll('.bridge-point').forEach(pt => { pt.classList.remove('active','normal-mode'); });
       // 선 삭제
       const overlay = document.getElementById('bridge-overlay');
       const linesGroup = overlay ? overlay.querySelector('#bridge-lines') : null;
       if (linesGroup) { while (linesGroup.firstChild) linesGroup.removeChild(linesGroup.firstChild); }
       // 스킵 표시 제거
       document.querySelectorAll('.tooth-button.bridge-skipped').forEach(el => el.classList.remove('bridge-skipped'));
       // UI 재적용
       if (typeof applyModesUI === 'function') applyModesUI();
       if (typeof renderBridgeLines === 'function') renderBridgeLines();
     } catch (e) {
       // 초기화 중 오류 무시
     }
   };
  })();

 // 서버 업로드 (upload.php 사용)
 function uploadFilesToServer(files, fileType){
   const fd = new FormData();
   fd.append('file_type', fileType || 'additional');
   Array.from(files).forEach(file => { fd.append((fileType||'additional') + '_files[]', file); });
  fetch('./upload.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
      if (!data || !data.success) { throw new Error(data && data.message ? data.message : '업로드 실패'); }
      console.log('Uploaded files:', data.files);
    })
    .catch(err => { alert('파일 업로드 중 오류: ' + err.message); });
  }

</script>
 
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
