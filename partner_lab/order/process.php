<?php
// 디버그: 최대 에러 리포팅 (운영에서는 0으로 조정 가능)
@error_reporting(E_ALL);
@ini_set('display_errors', '0'); // 화면 출력은 막고 JSON으로만 전달
// 실행 단계 디버그용 글로벌 스테이지 변수
$GLOBALS['PL_EXEC_STAGE'] = 'bootstrap';

// SQLite 데이터베이스 설정 포함
include_once 'db_config.php';
include_once 'db_utils.php';
// 파트너랩 주문 컬럼 보강 (필요 컬럼이 없으면 추가)
function pl_ensure_order_columns($conn) {
    try {
        $cols = dbu_get_columns($conn, 'partner_lab_orders');
        $add = array();
        if (!isset($cols['selected_teeth'])) $add[] = "ADD COLUMN `selected_teeth` TEXT NULL COMMENT '선택된 치아 번호 배열 (JSON)'";
        if (!isset($cols['teeth_configurations'])) $add[] = "ADD COLUMN `teeth_configurations` LONGTEXT NULL COMMENT '치아별 상세 설정 (JSON)'";
        if (!isset($cols['additional_info'])) $add[] = "ADD COLUMN `additional_info` TEXT NULL COMMENT '주문 전역 추가 옵션 텍스트'";
        if (!isset($cols['delivery_date'])) $add[] = "ADD COLUMN `delivery_date` DATE NULL COMMENT '납기 희망일'";
        if (!isset($cols['rubber_impression_delivery'])) $add[] = "ADD COLUMN `rubber_impression_delivery` TINYINT(1) NULL COMMENT '러버 임프레션 픽업 신청'";
        if (!isset($cols['delivery_postcode'])) $add[] = "ADD COLUMN `delivery_postcode` VARCHAR(32) NULL COMMENT '픽업 우편번호'";
        if (!isset($cols['delivery_address'])) $add[] = "ADD COLUMN `delivery_address` VARCHAR(255) NULL COMMENT '픽업 주소'";
        if (!isset($cols['delivery_detail_address'])) $add[] = "ADD COLUMN `delivery_detail_address` VARCHAR(255) NULL COMMENT '픽업 상세주소'";
        if (!isset($cols['delivery_hope_date'])) $add[] = "ADD COLUMN `delivery_hope_date` DATE NULL COMMENT '픽업 희망일'";
        if (!empty($add)) {
            $sql = "ALTER TABLE `partner_lab_orders".'` '.implode(', ', $add);
            // mysqli/PDO 공용 실행 래퍼 사용
            dbu_prepare_execute($conn, $sql, array());
        }
    } catch (Exception $e) {
        // 스키마 변경 실패는 무시하고 진행
    }
}

// partner_lab_order_teeth 컬럼 보강
function pl_ensure_teeth_columns($conn) {
    try {
        if (!dbu_table_exists($conn, 'partner_lab_order_teeth')) { return; }
        $cols = dbu_get_columns($conn, 'partner_lab_order_teeth');
        $add = array();
        if (!isset($cols['order_id'])) $add[] = "ADD COLUMN `order_id` INT NOT NULL COMMENT '주문 ID'";
        if (!isset($cols['tooth_number'])) $add[] = "ADD COLUMN `tooth_number` INT NOT NULL COMMENT '치아 번호'";
        if (!isset($cols['tooth_type'])) $add[] = "ADD COLUMN `tooth_type` VARCHAR(64) NULL COMMENT '치아 타입'";
        if (!isset($cols['created_at'])) $add[] = "ADD COLUMN `created_at` DATETIME NULL COMMENT '생성일시'";
        if (!empty($add)) {
            $sql = "ALTER TABLE `partner_lab_order_teeth` " . implode(', ', $add);
            dbu_prepare_execute($conn, $sql, array());
        }
    } catch (Exception $e) {
        // 무시하고 진행
    }
}

// JSON 응답 안전 출력 (출력 버퍼 정리 후 JSON만 반환)
function pl_utf8ize($mixed) {
    if (is_array($mixed)) { foreach ($mixed as $k=>$v) { $mixed[$k] = pl_utf8ize($v); } return $mixed; }
    if (is_string($mixed)) {
        if (function_exists('mb_detect_encoding') && !mb_detect_encoding($mixed, 'UTF-8', true)) {
            if (function_exists('mb_convert_encoding')) { return mb_convert_encoding($mixed, 'UTF-8', 'CP949, EUC-KR, ISO-8859-1, UTF-8'); }
        }
        return $mixed;
    }
    return $mixed;
}
function pl_json_encode($data) {
    if (function_exists('json_encode')) {
        $json = @json_encode($data);
        if ($json === false || $json === null) { $data = pl_utf8ize($data); $json = @json_encode($data); }
        return $json !== false ? $json : '{}';
    }
    return '{}';
}
function pl_json_echo($payload) {
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    if (!headers_sent()) { header('Content-Type: application/json; charset=UTF-8'); }
    echo pl_json_encode($payload);
    exit;
}
// 치아 구성 JSON 생성 (구버전 PHP 호환)
function pl_build_teeth_configurations() {
    $config = array();
    // 우선 표준 구조 배열을 평탄화하여 저장
    if (isset($_POST['tooth_options']) && is_array($_POST['tooth_options'])) {
        foreach ($_POST['tooth_options'] as $toothNo => $opts) {
            if (!is_array($opts)) continue;
            foreach ($opts as $optk => $optv) {
                $key = 'tooth_options[' . $toothNo . '][' . $optk . ']';
                $config[$key] = $optv;
            }
        }
    }
    // 보조 경로: JSON으로 전달된 경우 처리
    if (empty($config) && isset($_POST['tooth_options_json']) && is_string($_POST['tooth_options_json'])) {
        $json = @json_decode($_POST['tooth_options_json'], true);
        if (is_array($json)) {
            foreach ($json as $toothNo => $opts) {
                if (!is_array($opts)) continue;
                foreach ($opts as $optk => $optv) {
                    $key = 'tooth_options[' . $toothNo . '][' . $optk . ']';
                    $config[$key] = $optv;
                }
            }
        }
    }
    // 레거시 포맷(브래킷 포함 키가 직접 POST로 들어온 경우)도 함께 수집
    foreach ($_POST as $k => $v) {
        if (is_string($k) && strpos($k, 'tooth_options[') === 0) { $config[$k] = $v; }
    }
    if (!empty($config)) return pl_json_encode($config);
    return '';
}

// tooth_options 정규화: 브래킷 키 또는 JSON을 표준 배열로 변환
function pl_normalize_tooth_options_from_post() {
    $map = array();
    // 브래킷 포함 키를 파싱하여 중첩 배열 구성
    foreach ($_POST as $k => $v) {
        if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[([^\]]+)\]$/', $k, $m)) {
            $toothNo = intval($m[1]);
            $optKey = $m[2];
            if (!isset($map[$toothNo])) { $map[$toothNo] = array(); }
            $map[$toothNo][$optKey] = $v;
        }
    }
    // 비어있고 JSON 대체가 있을 경우 처리
    if (empty($map) && isset($_POST['tooth_options_json']) && is_string($_POST['tooth_options_json'])) {
        $json = @json_decode($_POST['tooth_options_json'], true);
        if (is_array($json)) { $map = $json; }
    }
    // 이미 배열로 전달되었으면 그대로 사용
    if (empty($map) && isset($_POST['tooth_options']) && is_array($_POST['tooth_options'])) {
        return $_POST['tooth_options'];
    }
    return $map;
}

// 구버전 PHP 호환: 종료 핸들러를 별도 함수로 정의
function pl_shutdown_handler() {
    $last = function_exists('error_get_last') ? error_get_last() : null;
    if ($last && isset($last['type']) && in_array($last['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR))) {
        $payload = array(
            'success' => false,
            'message' => '서버 오류가 발생했습니다. 관리자에게 문의하세요.',
            'error_debug' => array(
                'type' => 'fatal',
                'error_type' => isset($last['type']) ? $last['type'] : null,
                'message' => isset($last['message']) ? $last['message'] : null,
                'file' => isset($last['file']) ? $last['file'] : null,
                'line' => isset($last['line']) ? $last['line'] : null,
                'stage' => isset($GLOBALS['PL_EXEC_STAGE']) ? $GLOBALS['PL_EXEC_STAGE'] : null
            )
        );
        pl_json_echo($payload);
    }
}

// PHP 경고/공지 출력 비활성화하여 JSON 파괴 방지
// 출력 버퍼 시작
if (!headers_sent()) { @ob_start(); }

// 치명적 오류 발생 시 JSON으로 마무리 (구버전 PHP 호환)
register_shutdown_function('pl_shutdown_handler');

// JSON 응답 헤더 설정
// 헤더는 pl_json_echo에서 보장

// PHP 5.2 호환 세션 시작: 쿠키 기반 판단 후 시작
if (!isset($_COOKIE[ini_get('session.name')])) {
    @session_start();
} else {
    if (!isset($GLOBALS['_SESSION']) || !is_array($_SESSION)) { @session_start(); }
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pl_json_echo(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

try {
    $GLOBALS['PL_EXEC_STAGE'] = 'validate_post';
    // POST 데이터 검증 및 정규화
    if (!isset($_POST['tooth_options']) || !is_array($_POST['tooth_options'])) {
        $normalized = pl_normalize_tooth_options_from_post();
        if (!empty($normalized)) { $_POST['tooth_options'] = $normalized; }
    }

    // tooth_options 정규화 (서버에서 보정)
    if (!isset($_POST['tooth_options']) || !is_array($_POST['tooth_options'])) {
        $normalized = pl_normalize_tooth_options_from_post();
        if (!empty($normalized)) { $_POST['tooth_options'] = $normalized; }
    }
    // 데이터베이스 연결 (공용 래퍼)
    $GLOBALS['PL_EXEC_STAGE'] = 'db_connect';
    $conn = dbu_get_connection();
    if (!$conn) {
        pl_json_echo(array('success' => false, 'message' => '데이터베이스 연결 실패: 쓰기 권한 또는 경로 문제'));
        exit;
    }
    // 폴백 병합: 세션 자동저장 스냅샷에서 누락된 selected_teeth / tooth_options 보강 (DB 연결 후 수행)
    if ((!isset($_POST['selected_teeth']) || !is_array($_POST['selected_teeth'])) || (!isset($_POST['tooth_options']) || !is_array($_POST['tooth_options']))) {
        $sid = session_id();
        if ($sid && dbu_table_exists($conn, 'partner_lab_order_sessions')) {
            try {
                // 사용 가능한 데이터 컬럼 확인
                $sessCols = dbu_get_columns($conn, 'partner_lab_order_sessions');
                $dataCol = null;
                foreach (array('data_json','form_data','session_data') as $cand) { if (isset($sessCols[$cand])) { $dataCol = $cand; break; } }
                if ($dataCol) {
                    // 최신 스냅샷 조회 (updated_at/created_at 존재 시 정렬)
                    $orderBy = '';
                    if (isset($sessCols['updated_at'])) { $orderBy = ' ORDER BY updated_at DESC'; }
                    elseif (isset($sessCols['created_at'])) { $orderBy = ' ORDER BY created_at DESC'; }
                    $sqlSnap = "SELECT `{$dataCol}` FROM partner_lab_order_sessions WHERE session_id = ?" . $orderBy . " LIMIT 1";
                    $row = dbu_query_one_row($conn, $sqlSnap, array($sid));
                    if ($row && isset($row[$dataCol])) {
                        $snap = @json_decode($row[$dataCol], true);
                        if (is_array($snap)) {
                            if (!isset($_POST['selected_teeth']) || !is_array($_POST['selected_teeth'])) {
                                if (isset($snap['selected_teeth']) && is_array($snap['selected_teeth'])) { $_POST['selected_teeth'] = $snap['selected_teeth']; }
                            }
                            if (!isset($_POST['tooth_options']) || !is_array($_POST['tooth_options'])) {
                                // 브래킷 키 형태를 배열로 재구성하거나 직접 배열 사용
                                $map = array();
                                foreach ($snap as $k => $v) {
                                    if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[([^\]]+)\]$/', $k, $m)) {
                                        $tn = intval($m[1]); $optk = $m[2];
                                        if (!isset($map[$tn])) $map[$tn] = array();
                                        $map[$tn][$optk] = $v;
                                    }
                                }
                                if (empty($map) && isset($snap['tooth_options']) && is_array($snap['tooth_options'])) { $map = $snap['tooth_options']; }
                                if (!empty($map)) { $_POST['tooth_options'] = $map; }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // 세션 스냅샷 병합 실패 - 무시하고 계속 진행
            }
        }
    }

    // 서버 보강: selected_teeth가 비었지만 tooth_options가 있는 경우 키에서 유추하여 복원
    if (!isset($_POST['selected_teeth']) || !is_array($_POST['selected_teeth']) || empty($_POST['selected_teeth'])) {
        $derived = array();
        if (isset($_POST['tooth_options']) && is_array($_POST['tooth_options'])) {
            foreach ($_POST['tooth_options'] as $tn => $opts) {
                if (is_numeric($tn)) { $n = intval($tn); if ($n > 0) { $derived[] = $n; } }
            }
        }
        // 레거시 브래킷 키에서도 유추
        if (empty($derived)) {
            foreach ($_POST as $k => $v) {
                if (is_string($k) && preg_match('/^tooth_options\[(\d+)\]\[/', $k, $m)) {
                    $n = intval($m[1]); if ($n > 0) { $derived[] = $n; }
                }
            }
        }
        if (!empty($derived)) {
            // 중복 제거 및 정렬 (PHP 5 호환 수동 처리)
            $tmp = array();
            foreach ($derived as $_n) { $_n = intval($_n); if ($_n > 0) { $tmp[] = $_n; } }
            $tmp = array_values(array_unique($tmp));
            sort($tmp);
            $_POST['selected_teeth'] = $tmp;
        }
    }
    
    // 필수 필드 검증
    $required_fields = array(
        'customer_name' => '고객명',
        'customer_phone' => '연락처',
        'shipping_name' => '받는 분',
        'shipping_phone' => '배송 연락처',
        'shipping_postcode' => '우편번호',
        'shipping_address' => '주소',
        'shipping_detail' => '상세주소',
        'patient_name' => '환자명',
        'patient_birth' => '환자 나이',
        'patient_gender' => '환자 성별'
    );
    
    $GLOBALS['PL_EXEC_STAGE'] = 'validate_required';
    foreach ($required_fields as $field => $name) {
        if (empty($_POST[$field])) {
            pl_json_echo(array('success' => false, 'message' => $name . '을(를) 입력해주세요.'));
            exit;
        }
    }
    
    // 체크박스 검증
    if (empty($_POST['agreement'])) {
        pl_json_echo(array('success' => false, 'message' => '주문 약관에 동의해주세요.'));
        exit;
    }
    
    if (empty($_POST['final_agreement'])) {
        pl_json_echo(array('success' => false, 'message' => '최종 동의사항을 확인해주세요.'));
        exit;
    }
    
    // 트랜잭션 시작
    $GLOBALS['PL_EXEC_STAGE'] = 'transaction_begin';
    dbu_begin($conn);
    // 주문 저장에 필요한 컬럼 보강 시도
    pl_ensure_order_columns($conn);

    // 수정 모드 여부 확인
    $editing_order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    // PHP 5.2 호환: 단축 삼항(?:) 연산자 대신 일반 처리
    $orderPk = dbu_get_order_pk($conn);
    if (!$orderPk) { $orderPk = 'order_id'; }

    // 신규 주문의 경우 주문번호 생성
    $order_number = '';
    if ($editing_order_id <= 0) {
        $order_number = 'PL' . date('Ymd') . sprintf('%04d', rand(1, 9999));
        // 중복 체크
        $exists = dbu_row_exists($conn, "SELECT 1 FROM partner_lab_orders WHERE order_number = ? LIMIT 1", array($order_number));
        if ($exists) {
            $order_number = 'PL' . date('YmdHis') . rand(100, 999);
        }
    }
    
    // 주문 정보 저장 (스키마 유연 처리)

    $current_time = date('Y-m-d H:i:s');
    // 배송 선호도와 발송일 처리 (UI 변경 반영)
    $delivery_preference = isset($_POST['delivery_preference']) ? $_POST['delivery_preference'] : 'standard';
    // 납기 희망일 직접 저장(우선순위: delivery_date > delivery_hope_date > surgery_date)
    $delivery_date = isset($_POST['delivery_date']) ? $_POST['delivery_date'] : (isset($_POST['delivery_hope_date']) ? $_POST['delivery_hope_date'] : (isset($_POST['surgery_date']) ? $_POST['surgery_date'] : ''));
    // 기존 스키마(dispatch_date)와의 호환을 위해 디스패치 날짜에 동일 값 반영
    $dispatch_date = $delivery_date ? $delivery_date : date('Y-m-d');
    
    // 스키마 확인하여 order_status 호환 처리
    $order_status = 'pending';
    try {
        $orderCols = dbu_get_columns($conn, 'partner_lab_orders');
        if (isset($orderCols['order_status'])) {
            $type = strtolower($orderCols['order_status']['Type']);
            if (strpos($type, 'enum(') === 0) {
                // enum 값 파싱
                $m = array();
                if (preg_match('/enum\((.+)\)/i', $type, $m)) {
                    $raw = $m[1];
                    $vals = array();
                    foreach (explode(',', $raw) as $v) {
                        $v = trim($v, " '" );
                        if ($v !== '') $vals[] = $v;
                    }
                    if (!in_array('pending', $vals)) {
                        if (in_array('submitted', $vals)) $order_status = 'submitted';
                        elseif (in_array('draft', $vals)) $order_status = 'draft';
                        else $order_status = count($vals) ? $vals[0] : 'draft';
                    }
                }
            }
        }
    } catch (Exception $e) {
        // 스키마 조회 실패는 무시하고 기본값 유지
    }

    // patient_birth 타입 호환 (date 컬럼일 경우 형식 보정)
    $patient_birth_val = isset($_POST['patient_birth']) ? $_POST['patient_birth'] : '';
    try {
        if (isset($orderCols['patient_birth'])) {
            $pbType = strtolower($orderCols['patient_birth']['Type']);
            if (strpos($pbType, 'date') !== false) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $patient_birth_val)) {
                    // 잘못된 형식이면 안전한 기본값으로 설정
                    $patient_birth_val = '0000-00-00';
                }
            }
        }
    } catch (Exception $e) {
        // 타입 조회 실패 시 원본 값 사용
    }
    // 스키마 유연 처리로 동적 INSERT 실행
    $teethConfigJson = pl_build_teeth_configurations();
    // 주문 전역 추가옵션 집계 (PMAB/스크류/아노/Non-engaging)
    // PHP 환경에 따라 브래킷 키를 배열로 자동 파싱하지 못하는 경우가 있어
    // 표준화 함수(pl_normalize_tooth_options_from_post)를 사용해 항상 동일하게 처리
    $globalFlags = array('pmab'=>false,'screw'=>false,'anodizing'=>false,'non_engaging'=>false);
    $optsMap = pl_normalize_tooth_options_from_post();
    if (is_array($optsMap) && !empty($optsMap)) {
        foreach ($optsMap as $tn => $opts) {
            if (!is_array($opts)) continue;
            foreach ($globalFlags as $k => $cur) {
                if (isset($opts[$k]) && ($opts[$k]==='1' || $opts[$k]===1 || $opts[$k]==='on')) { $globalFlags[$k] = true; }
            }
        }
    }
    $additional_info_text = '';
    $labels = array('pmab'=>'정품 PMAB 적용','screw'=>'정품 스크류 적용','anodizing'=>'아노다이징 적용','non_engaging'=>'Non-engaging 적용');
    $onLabels = array(); foreach ($globalFlags as $k=>$v) { if ($v) $onLabels[] = $labels[$k]; }
    if (!empty($onLabels)) { $additional_info_text = implode(', ', $onLabels); }
    // teeth_configurations 구성
    $orderData = array(
        'mb_id' => isset($_POST['mb_id']) ? $_POST['mb_id'] : '',
        // 신규 주문에서만 order_number 설정
        'order_number' => $order_number,
        'customer_name' => $_POST['customer_name'],
        'customer_phone' => $_POST['customer_phone'],
        'customer_email' => isset($_POST['customer_email']) ? $_POST['customer_email'] : '',
        'shipping_name' => $_POST['shipping_name'],
        'shipping_phone' => $_POST['shipping_phone'],
        'shipping_postcode' => $_POST['shipping_postcode'],
        'shipping_address' => $_POST['shipping_address'],
        'shipping_detail' => $_POST['shipping_detail'],
        'patient_name' => $_POST['patient_name'],
        'patient_birth' => $patient_birth_val,
        'patient_gender' => $_POST['patient_gender'],
        'delivery_preference' => $delivery_preference,
        'lab_postcode' => isset($_POST['lab_postcode']) ? $_POST['lab_postcode'] : '',
        'lab_address' => isset($_POST['lab_address']) ? $_POST['lab_address'] : '',
        'lab_address_detail' => isset($_POST['lab_address_detail']) ? $_POST['lab_address_detail'] : '',
        // 러버 인상체 및 픽업 정보 저장 (스키마 존재 시에만 동적 포함)
        'rubber_impression_delivery' => isset($_POST['rubber_impression_delivery']) ? $_POST['rubber_impression_delivery'] : '',
        'delivery_postcode' => isset($_POST['delivery_postcode']) ? $_POST['delivery_postcode'] : '',
        'delivery_address' => isset($_POST['delivery_address']) ? $_POST['delivery_address'] : '',
        'delivery_detail_address' => isset($_POST['delivery_detail_address']) ? $_POST['delivery_detail_address'] : '',
        'delivery_hope_date' => isset($_POST['delivery_hope_date']) ? $_POST['delivery_hope_date'] : '',
        // 스키마 호환: dispatch_date에 납기 희망일 저장
        'dispatch_date' => $dispatch_date,
        // delivery_date 컬럼이 있을 경우 동적 INSERT에서 자동 포함됨
        'delivery_date' => $delivery_date,
        'special_notes' => isset($_POST['special_notes']) ? $_POST['special_notes'] : '',
        // 주문서 전역 추가 옵션 (스키마에 컬럼 존재 시 저장)
        'additional_info' => $additional_info_text,
        // 스키마가 엄격한 경우 대비: NOT NULL 텍스트 컬럼들 기본값 제공
        'products' => (isset($_POST['products']) ? (is_array($_POST['products']) ? pl_json_encode($_POST['products']) : (string)$_POST['products']) : ''),
        'selected_teeth' => (isset($_POST['selected_teeth']) ? pl_json_encode($_POST['selected_teeth']) : ''),
        // 치아 상세 옵션(존재 시) JSON 구성 (구버전 PHP 호환)
        'teeth_configurations' => $teethConfigJson,
        // 전체 폼 데이터 자동저장 스냅샷(존재 시)
        'auto_save_data' => pl_json_encode($_POST),
        'order_status' => $order_status,
        // 신규/수정 공통 처리: created_at은 신규에서만 사용, updated_at은 항상 업데이트
        'created_at' => $current_time,
        'updated_at' => $current_time
    );
    if ($editing_order_id > 0) {
        $GLOBALS['PL_EXEC_STAGE'] = 'update_order';
        // 수정 모드: 스키마에 존재하는 컬럼만 업데이트
        $cols = dbu_get_columns($conn, 'partner_lab_orders');
        $setCols = array();
        $params = array();
        foreach ($orderData as $k => $v) {
            // 수정 시 order_number/created_at은 건너뜀
            if ($k === 'order_number' || $k === 'created_at') continue;
            // 옵션이 비어있을 경우 기존 값을 보존하기 위해 선택 치아/치아구성 JSON은 빈 값이면 업데이트하지 않음
            if (($k === 'selected_teeth' || $k === 'teeth_configurations') && ('' === (string)$v)) { continue; }
            if (isset($cols[$k])) { $setCols[] = "`$k` = ?"; $params[] = $v; }
        }
        // 최소한 updated_at은 항상 포함되도록 보장
        if (!in_array('`updated_at` = ?', $setCols)) {
            if (isset($cols['updated_at'])) { $setCols[] = '`updated_at` = ?'; $params[] = $current_time; }
        }
        if (empty($setCols)) {
            throw new Exception('업데이트할 필드가 없습니다.');
        }
        $sql = "UPDATE partner_lab_orders SET " . implode(', ', $setCols) . " WHERE `{$orderPk}` = ?";
        $params[] = $editing_order_id;
        $ok = dbu_prepare_execute($conn, $sql, $params);
        if (!$ok) { throw new Exception('주문 정보 업데이트 실패'); }
        $order_id = $editing_order_id;
        // 수정 모드에서도 응답에 주문번호 포함을 보장 (DB에서 조회)
        if (!$order_number) {
            $order_number = dbu_query_one_scalar($conn, "SELECT order_number FROM partner_lab_orders WHERE `{$orderPk}` = ? LIMIT 1", array($order_id));
            if (!$order_number) { $order_number = ''; }
        }
    } else {
        $GLOBALS['PL_EXEC_STAGE'] = 'insert_order';
        // 신규 모드: INSERT
        list($sqlInsertOrder, $sqlValues) = dbu_build_insert_sql($conn, 'partner_lab_orders', $orderData);
        // 실행 시 상세 오류 확보
        $result = false;
        try {
            $result = dbu_prepare_execute($conn, $sqlInsertOrder, $sqlValues);
        } catch (Exception $e) {
            throw $e;
        }
        if (!$result) {
            $detail = '';
            if (is_object($conn) && (get_class($conn) === 'mysqli')) { $detail = mysqli_error($conn); }
            throw new Exception('주문 정보 저장 실패' . ($detail ? (' - ' . $detail) : ''));
        }

        $order_id = dbu_last_insert_id($conn);
        if (!$order_id || intval($order_id) <= 0) {
            // 기본키가 auto_increment가 아닐 수 있는 환경 대비: order_number로 기본키 조회
            $orderPk2 = dbu_get_order_pk($conn);
            if ($orderPk2) {
                $order_id = dbu_query_one_scalar($conn, "SELECT `{$orderPk2}` FROM partner_lab_orders WHERE order_number = ? LIMIT 1", array($order_number));
            }
        }
        if (!$order_id || intval($order_id) <= 0) {
            throw new Exception('주문 ID 확인 실패: 기본키 조회 불가');
        }
        // 신규 주문 응답에서도 주문번호 포함을 보장 (DB에서 조회)
        if (!$order_number) {
            $order_number = dbu_query_one_scalar($conn, "SELECT order_number FROM partner_lab_orders WHERE `{$orderPk}` = ? LIMIT 1", array($order_id));
            if (!$order_number) { $order_number = ''; }
        }

        // 안전장치: INSERT 후에도 핵심 JSON 컬럼을 강제 업데이트하여 저장 누락 방지
        try {
            $orderCols2 = dbu_get_columns($conn, 'partner_lab_orders');
            $needsUpdateCols = array();
            $updateParams = array();
            if (isset($orderCols2['teeth_configurations']) && $teethConfigJson !== '') {
                $needsUpdateCols[] = '`teeth_configurations` = ?';
                $updateParams[] = $teethConfigJson;
            }
            if (isset($orderCols2['selected_teeth']) && isset($_POST['selected_teeth']) && is_array($_POST['selected_teeth'])) {
                $needsUpdateCols[] = '`selected_teeth` = ?';
                $updateParams[] = pl_json_encode($_POST['selected_teeth']);
            }
            if (!empty($needsUpdateCols)) {
                $sqlFix = 'UPDATE partner_lab_orders SET ' . implode(', ', $needsUpdateCols) . ' WHERE `'.$orderPk.'` = ?';
                $updateParams[] = $order_id;
                dbu_prepare_execute($conn, $sqlFix, $updateParams);
            }
        } catch (Exception $e) {
            // ORDER POST-INSERT FIX 실패 - 무시하고 계속 진행
        }
    }

    // 회원 기본 배송정보 저장 (있을 경우)
    if (!empty($_POST['mb_id'])) {
        $GLOBALS['PL_EXEC_STAGE'] = 'save_member_shipping_defaults';
        $key = 'shipping_defaults_' . $_POST['mb_id'];
        $value = pl_json_encode(array(
            'shipping_name' => isset($_POST['shipping_name']) ? $_POST['shipping_name'] : '',
            'shipping_phone' => isset($_POST['shipping_phone']) ? $_POST['shipping_phone'] : '',
            'shipping_postcode' => isset($_POST['shipping_postcode']) ? $_POST['shipping_postcode'] : '',
            'shipping_address' => isset($_POST['shipping_address']) ? $_POST['shipping_address'] : '',
            'shipping_detail' => isset($_POST['shipping_detail']) ? $_POST['shipping_detail'] : ''
        ));
        // MySQL 업서트 처리 (setting_key가 UNIQUE 인덱스여야 함) - 테이블 미존재 시 주문 실패 방지
        try {
            dbu_prepare_execute($conn, "INSERT INTO partner_lab_system_settings (setting_key, setting_value, setting_group, is_active, updated_at) VALUES (?, ?, 'member', 1, NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), setting_group='member', is_active=1, updated_at=NOW() ", array($key, $value));
        } catch (Exception $e) {
            // 시스템 설정 업서트 실패 - 무시하고 계속 진행
        }
    }
    
    // 선택된 치아 정보 저장 (테이블 존재 시에만)
    if (isset($_POST['selected_teeth']) && is_array($_POST['selected_teeth'])) {
        if (dbu_table_exists($conn, 'partner_lab_order_teeth')) {
            // 치아 테이블 컬럼 보강
            pl_ensure_teeth_columns($conn);
            // 수정 모드일 경우: 새 선택치아가 전달된 경우에만 기존 치아 정보를 삭제
            if ($editing_order_id > 0) {
                $hasNewSelection = (isset($_POST['selected_teeth']) && is_array($_POST['selected_teeth']) && !empty($_POST['selected_teeth']));
                if ($hasNewSelection) {
                    try { dbu_prepare_execute($conn, "DELETE FROM partner_lab_order_teeth WHERE order_id = ?", array($order_id)); } catch (Exception $e) {}
                }
            }
            $GLOBALS['PL_EXEC_STAGE'] = 'save_selected_teeth';
            foreach ($_POST['selected_teeth'] as $tooth_number) {
                // 싱글/브릿지 모드 우선 저장. UI에서 'general'을 'single'로 변환
                $mode = '';
                if (isset($_POST['tooth_options']) && isset($_POST['tooth_options'][$tooth_number]) && isset($_POST['tooth_options'][$tooth_number]['mode'])) {
                    $mode = $_POST['tooth_options'][$tooth_number]['mode'];
                }
                if ($mode === 'general') { $mode = 'single'; }
                // 모드 정보가 없으면 기존 사분면 정보로 폴백
                $tooth_type = $mode ? $mode : (isset($_POST['tooth_type_' . $tooth_number]) ? $_POST['tooth_type_' . $tooth_number] : '');
                $teethData = array(
                    'order_id' => $order_id,
                    'tooth_number' => $tooth_number,
                    'tooth_type' => $tooth_type,
                    'created_at' => $current_time
                );
                list($sqlTeeth, $valsTeeth) = dbu_build_insert_sql($conn, 'partner_lab_order_teeth', $teethData, array('order_id','tooth_number'));
                try {
                    dbu_prepare_execute($conn, $sqlTeeth, $valsTeeth);
                } catch (Exception $e) {
                    throw $e; // 치아 저장 실패는 트랜잭션 롤백
                }
            }
        } else {
            // 테이블 누락: partner_lab_order_teeth 미존재로 치아 정보 저장 건너뜀
        }

        // 치아 상세 옵션 저장 (정규화 테이블 존재 시 사용, PHP 5 호환)
        if (dbu_table_exists($conn, 'partner_lab_order_teeth_details')) {
            $GLOBALS['PL_EXEC_STAGE'] = 'save_teeth_details';
            // 입력 페이로드 유무 확인
            $hasSelPayload = (isset($_POST['selected_teeth']) && is_array($_POST['selected_teeth']) && !empty($_POST['selected_teeth']));
            $hasOptPayload = (isset($_POST['tooth_options']) && is_array($_POST['tooth_options']) && !empty($_POST['tooth_options']));
            // 수정 모드일 경우: 실제로 재삽입할 페이로드가 있을 때만 기존 상세 정보를 삭제
            // 페이로드가 없으면 기존 데이터를 보존하여 공란 문제를 방지
            if ($editing_order_id > 0) {
                if ($hasSelPayload || $hasOptPayload) {
                    try { dbu_prepare_execute($conn, "DELETE FROM partner_lab_order_teeth_details WHERE order_id = ?", array($order_id)); } catch (Exception $e) {}
                } else {
                    // SKIP DETAILS RESET (no payload)
                }
            }
            // 선택 치아 목록 구성: selected_teeth -> tooth_options 키 -> (없으면) 삽입 생략
            $selectedTeethList = array();
            if ($hasSelPayload) {
                foreach ($_POST['selected_teeth'] as $_tn) { if (is_numeric($_tn)) { $selectedTeethList[] = (int)$_tn; } }
            } elseif ($hasOptPayload) {
                foreach (array_keys($_POST['tooth_options']) as $_key) { if (is_numeric($_key)) { $selectedTeethList[] = (int)$_key; } }
            }
            // 중복 제거 정렬
            if (!empty($selectedTeethList)) { $selectedTeethList = array_values(array_unique($selectedTeethList)); sort($selectedTeethList); }
            // 페이로드가 없으면 삽입을 생략 (기존 데이터 유지)
            foreach ($selectedTeethList as $tooth_number) {
                $tn = intval($tooth_number);
                // 위치 계산
                $pos = '';
                if ($tn >= 11 && $tn <= 18) $pos = 'upper_right';
                else if ($tn >= 21 && $tn <= 28) $pos = 'upper_left';
                else if ($tn >= 31 && $tn <= 38) $pos = 'lower_left';
                else if ($tn >= 41 && $tn <= 48) $pos = 'lower_right';
                // 옵션 매핑
                $opts = (isset($_POST['tooth_options']) && isset($_POST['tooth_options'][$tn]) && is_array($_POST['tooth_options'][$tn])) ? $_POST['tooth_options'][$tn] : array();
                $system_spec = isset($opts['system_other']) && $opts['system_other'] ? $opts['system_other'] : (isset($opts['system']) ? $opts['system'] : '');
                $margin_level = isset($opts['margin']) ? $opts['margin'] : '';
                $final_prosthetic = isset($opts['prosthetic']) ? $opts['prosthetic'] : '';
                $shade = isset($opts['shade']) ? $opts['shade'] : '';
                $flags = array();
                foreach (array('non_engaging'=>'Non-Eng','anodizing'=>'Ano','pmab'=>'PMAB','screw'=>'Screw') as $k=>$lbl) {
                    if (isset($opts[$k]) && ($opts[$k]==='on' || $opts[$k]==='1' || $opts[$k]===1)) { $flags[] = $lbl; }
                }
                $notes = '';
                if ($shade) { $notes .= 'shade=' . $shade; }
                if (!empty($flags)) { $notes .= ($notes?'; ':'') . 'flags=' . implode(',', $flags); }
                $detailData = array(
                    'order_id' => $order_id,
                    'tooth_number' => (string)$tn,
                    'tooth_position' => $pos,
                    'is_selected' => 1,
                    'system_spec' => $system_spec,
                    'connection_type' => '',
                    'margin_level' => $margin_level,
                    'final_prosthetic' => $final_prosthetic,
                    'emergence_profile' => '',
                    'gingiva_contact' => '',
                    'taper_angle' => '',
                    'margin_type' => '',
                    'special_notes' => $notes,
                    'created_at' => $current_time,
                    'updated_at' => $current_time
                );
                list($sqlDet, $valsDet) = dbu_build_insert_sql($conn, 'partner_lab_order_teeth_details', $detailData, array('order_id','tooth_number'));
                try {
                    dbu_prepare_execute($conn, $sqlDet, $valsDet);
                } catch (Exception $e) {
                    // 치아 상세 옵션 INSERT 실패 - 무시하고 계속 진행
                }
            }
        }
    }
    
    // 임시 업로드된 파일들을 주문에 연결
    $session_id = session_id();
    try { dbu_prepare_execute($conn, "UPDATE partner_lab_order_files SET order_id = ? WHERE order_id IS NULL", array($order_id)); } catch (Exception $e) {}
    try { dbu_prepare_execute($conn, "UPDATE partner_lab_order_files SET order_id = ? WHERE order_id = ''", array($order_id)); } catch (Exception $e) {}
    try { dbu_prepare_execute($conn, "UPDATE partner_lab_order_files SET order_id = ? WHERE order_id = 0", array($order_id)); } catch (Exception $e) {}

    // 폴백 보존: 세션 스냅샷 테이블에 전체 POST 저장 (확인/관리자 화면에서 옵션 복원용)
    if (dbu_table_exists($conn, 'partner_lab_order_sessions')) {
        try {
            $sessCols = dbu_get_columns($conn, 'partner_lab_order_sessions');
            $dataCol = null;
            foreach (array('data_json','form_data','session_data') as $cand) { if (isset($sessCols[$cand])) { $dataCol = $cand; break; } }
            $createdCol = isset($sessCols['created_at']) ? 'created_at' : null;
            $updatedCol = isset($sessCols['updated_at']) ? 'updated_at' : null;
            $payload = pl_json_encode($_POST);
            if ($dataCol) {
                // 기존 세션 레코드 제거 후 재삽입 (세션ID 기준)
                try { dbu_prepare_execute($conn, "DELETE FROM partner_lab_order_sessions WHERE session_id = ?", array($session_id)); } catch (Exception $e) {}
                $sessData = array(
                    'session_id' => (string)$session_id,
                    'order_id' => $order_id,
                    $dataCol => $payload
                );
                if ($createdCol) $sessData[$createdCol] = $current_time;
                if ($updatedCol) $sessData[$updatedCol] = $current_time;
                list($sqlSess, $valsSess) = dbu_build_insert_sql($conn, 'partner_lab_order_sessions', $sessData, array('session_id'));
                dbu_prepare_execute($conn, $sqlSess, $valsSess);
            }
        } catch (Exception $e) {
            // 세션 스냅샷 저장 실패 - 무시하고 계속 진행
        }
    }

    // 주문 로그 저장
    if ($editing_order_id > 0) {
        dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, ?, ?, NOW())", array($order_id, 'order_updated', '주문이 수정되었습니다.'));
    } else {
        dbu_prepare_execute($conn, "INSERT INTO partner_lab_order_logs (order_id, action, description, created_at) VALUES (?, ?, ?, NOW())", array($order_id, 'order_created', '주문이 생성되었습니다.'));
    }
    
    // 주문 처리 완료
    
    // 트랜잭션 커밋
    $GLOBALS['PL_EXEC_STAGE'] = 'commit';
    dbu_commit($conn);
    
    // 성공 응답
    $GLOBALS['PL_EXEC_STAGE'] = 'response_success';
    pl_json_echo(array(
        'success' => true,
        'message' => ($editing_order_id > 0) ? '주문이 성공적으로 수정되었습니다.' : '주문이 성공적으로 접수되었습니다.',
        'order_id' => $order_id,
        'order_number' => $order_number
    ));
    exit;
    
} catch (Exception $e) {
    // 트랜잭션 롤백
    if (isset($conn) && $conn) { dbu_rollback($conn); }
    // 디버그 정보 포함
    pl_json_echo(array(
        'success' => false,
        'message' => '주문 처리 중 오류가 발생했습니다: ' . $e->getMessage(),
        'error_debug' => array(
            'type' => 'exception',
            'file' => method_exists($e, 'getFile') ? $e->getFile() : null,
            'line' => method_exists($e, 'getLine') ? $e->getLine() : null,
            'stage' => isset($GLOBALS['PL_EXEC_STAGE']) ? $GLOBALS['PL_EXEC_STAGE'] : null
        )
    ));
    exit;
}
