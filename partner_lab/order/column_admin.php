<?php
// 파트너랩 테이블 컬럼 점검 및 누락 컬럼 추가 스크립트
// PHP 5.2 호환: __DIR__ 대신 dirname(__FILE__) 사용
include_once dirname(__FILE__).'/db_config.php';
include_once dirname(__FILE__).'/db_utils.php';

header('Content-Type: text/html; charset=utf-8');

$conn = dbu_get_connection();
if (!$conn) {
    echo '<h3>DB 연결 실패</h3><p>DB 설정 또는 네트워크/확장 모듈(pdo_mysql) 문제일 수 있습니다.</p>';
    exit;
}

// 스키마 요구사항 정의: 누락된 컬럼만 안전하게 추가합니다. (인덱스/PK는 자동 추가하지 않음)
$SCHEMA_SPEC = array(
    'partner_lab_orders' => array(
        'order_number' => 'VARCHAR(50) NOT NULL',
        'customer_name' => 'VARCHAR(100) NOT NULL',
        'customer_phone' => 'VARCHAR(50) NOT NULL',
        'customer_email' => 'VARCHAR(100)',
        'shipping_name' => 'VARCHAR(100) NOT NULL',
        'shipping_phone' => 'VARCHAR(50) NOT NULL',
        'shipping_postcode' => 'VARCHAR(20)',
        'shipping_address' => 'VARCHAR(255)',
        'shipping_detail' => 'VARCHAR(255)',
        'patient_name' => 'VARCHAR(100) NOT NULL',
        'patient_birth' => 'VARCHAR(20)',
        'patient_gender' => 'VARCHAR(20)',
        'delivery_preference' => 'VARCHAR(50)',
        'lab_postcode' => 'VARCHAR(20)',
        'lab_address' => 'VARCHAR(255)',
        'lab_address_detail' => 'VARCHAR(255)',
        'dispatch_date' => 'DATETIME',
        'special_notes' => 'TEXT',
        'order_status' => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
        'created_at' => 'DATETIME',
        'updated_at' => 'DATETIME',
    ),
    'partner_lab_order_teeth' => array(
        'order_id' => 'INT NOT NULL',
        'tooth_number' => 'VARCHAR(20) NOT NULL',
        'tooth_type' => 'VARCHAR(50)',
        'created_at' => 'DATETIME',
    ),
    'partner_lab_order_files' => array(
        'order_id' => 'INT NULL',
        'file_path' => 'VARCHAR(255) NOT NULL',
        'uploaded_at' => 'DATETIME',
    ),
    'partner_lab_order_sessions' => array(
        'session_id' => 'VARCHAR(64) NOT NULL',
        'form_data' => 'TEXT',
        'data_json' => 'TEXT',
        'created_at' => 'DATETIME',
        'updated_at' => 'DATETIME',
    ),
    'partner_lab_order_logs' => array(
        'order_id' => 'INT NOT NULL',
        'action' => 'VARCHAR(50) NOT NULL',
        'description' => 'TEXT',
        'created_at' => 'DATETIME',
    ),
    'partner_lab_system_settings' => array(
        'setting_key' => 'VARCHAR(100) NOT NULL',
        'setting_value' => 'TEXT',
        'setting_group' => 'VARCHAR(50)',
        'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'updated_at' => 'DATETIME',
    ),
);

function get_missing_columns($conn, $table, $spec) {
    $cols = dbu_get_columns($conn, $table);
    $missing = array();
    foreach ($spec as $col => $def) {
        if (!isset($cols[$col])) { $missing[$col] = $def; }
    }
    return $missing;
}

$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
$messages = array();

if ($act === 'add') {
    foreach ($SCHEMA_SPEC as $table => $colsSpec) {
        if (!dbu_table_exists($conn, $table)) {
            $messages[] = "테이블 없음: ".$table." — 테이블 생성 후 다시 시도하세요.";
            continue;
        }
        $missing = get_missing_columns($conn, $table, $colsSpec);
        if (empty($missing)) {
            $messages[] = "추가할 컬럼 없음: ".$table;
            continue;
        }
        foreach ($missing as $col => $def) {
            try {
                $sql = "ALTER TABLE `".$table."` ADD COLUMN `".$col."` ".$def;
                dbu_exec($conn, $sql);
                $messages[] = "[성공] ".$table.".".$col." 추가됨";
            } catch (Exception $e) {
                $messages[] = "[실패] ".$table.".".$col." — ".htmlspecialchars($e->getMessage());
            }
        }
    }
}

echo '<!doctype html><html><head><meta charset="utf-8"><title>컬럼 점검/추가</title>';
echo '<style>body{font-family:system-ui,Segoe UI,Arial;padding:20px} h1{font-size:20px} pre{background:#f8fafc;border:1px solid #e5e7eb;padding:10px} .box{border:1px solid #e5e7eb;padding:12px;margin-bottom:16px}</style>';
echo '</head><body>';
echo '<h1>파트너랩 테이블 컬럼 점검 및 추가</h1>';
echo '<p>아래 목록은 현재 DB의 컬럼 상태와 누락 컬럼입니다. "누락 컬럼 추가" 버튼을 누르면 누락된 컬럼만 안전하게 추가합니다. (기존 데이터/컬럼/인덱스는 변경하지 않음)</p>';

if (!empty($messages)) {
    echo '<div class="box"><strong>실행 결과</strong><ul>';
    foreach ($messages as $m) { echo '<li>'.htmlspecialchars($m).'</li>'; }
    echo '</ul></div>';
}

echo '<form method="post">';
echo '<input type="hidden" name="act" value="add" />';

foreach ($SCHEMA_SPEC as $table => $colsSpec) {
    echo '<div class="box">';
    echo '<h3>'.htmlspecialchars($table).'</h3>';
    if (!dbu_table_exists($conn, $table)) {
        echo '<p style="color:#b91c1c">테이블이 존재하지 않습니다. 테이블을 먼저 생성하세요.</p>';
        echo '</div>';
        continue;
    }
    $existing = dbu_get_columns($conn, $table);
    echo '<pre>';
    foreach ($existing as $name => $meta) {
        echo $name.'  '.$meta['Type'].'  '.($meta['Null']=='NO'?'NOT NULL':'NULL')."\n";
    }
    echo '</pre>';
    $missing = get_missing_columns($conn, $table, $colsSpec);
    if (empty($missing)) {
        echo '<p>누락 컬럼 없음</p>';
    } else {
        echo '<p><strong>누락 컬럼</strong></p><pre>';
        foreach ($missing as $col => $def) {
            echo $col.'  '.$def."\n";
        }
        echo '</pre>';
    }
    echo '</div>';
}

echo '<button type="submit" style="padding:8px 12px;background:#2563eb;color:#fff;border:0;border-radius:4px">누락 컬럼 추가</button>';
echo ' <a href="db_check.php" style="margin-left:8px">테이블 점검 페이지</a>';
echo '</form>';

echo '</body></html>';
?>