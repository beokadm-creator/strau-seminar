<?php
// GNUBoard 공통 상수/경로 포함 (데이터 경로 사용)
@include_once dirname(__FILE__) . '/../_common.php';
@include_once dirname(__FILE__) . '/../config.php';
// 데이터베이스 설정 포함
include_once 'db_config.php';
include_once 'db_utils.php';
@ini_set('display_errors', 0);
@ini_set('html_errors', 0);
if (function_exists('ob_get_level') && ob_get_level() === 0) { @ob_start(); }

// JSON 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 간단한 세션 체크 (실제 환경에서는 적절한 인증 로직 사용)
session_start();

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
    exit;
}

try {
    // 업로드 디렉토리 설정 (GNUBOARD 환경 없을 경우 폴백)
    if (defined('G5_DATA_PATH') && defined('G5_DATA_URL')) {
        $upload_base_dir = G5_DATA_PATH . '/partner_lab/uploads';
        $upload_web_dir = G5_DATA_URL . '/partner_lab/uploads';
    } else {
        $upload_base_dir = __DIR__ . '/uploads';
        // 현재 스크립트 URL 기준 상대 경로 사용
        $script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $upload_web_dir = $script_dir . '/uploads';
    }
    
    // 디렉토리 생성 및 권한 보정
    if (!is_dir($upload_base_dir)) { @mkdir($upload_base_dir, 0777, true); }
    @chmod($upload_base_dir, 0777);
    
    // 오늘 날짜로 서브 디렉토리 생성
    $today = date('Y/m/d');
    $upload_dir = $upload_base_dir . '/' . $today;
    $upload_url = $upload_web_dir . '/' . $today;
    
    if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
    @chmod($upload_dir, 0777);

    // 권한이 여전히 없으면 시스템 임시 디렉토리로 폴백
    if (!is_writable($upload_dir)) {
        $tmpBase = rtrim(sys_get_temp_dir(), '/\\') . '/partner_lab_uploads';
        $upload_base_dir = $tmpBase;
        if (!is_dir($upload_base_dir)) { @mkdir($upload_base_dir, 0777, true); }
        @chmod($upload_base_dir, 0777);
        $upload_dir = $upload_base_dir . '/' . $today;
        if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
        @chmod($upload_dir, 0777);
        // 웹 경로는 임시로 스크립트 기준으로 유지 (다운로드는 file_download.php가 file_path로 직접 읽음)
        $upload_web_dir = $script_dir . '/uploads';
    }
    
    // 파일 타입 확인
    $file_type = isset($_POST['file_type']) ? $_POST['file_type'] : 'additional';
    $allowed_types = array('scan', 'design', 'additional');
    
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('허용되지 않은 파일 타입입니다.');
    }
    
    // 업로드된 파일들 처리
    $uploaded_files = array();
    $file_field_name = $file_type . '_files';
    
    if (!isset($_FILES[$file_field_name])) {
        throw new Exception('업로드할 파일이 없습니다.');
    }
    // 단일/다중 모두 지원
    if (is_array($_FILES[$file_field_name]['name'])) {
        $files = $_FILES[$file_field_name];
        $file_count = count($files['name']);
    } else {
        $files = array(
            'name' => array($_FILES[$file_field_name]['name']),
            'tmp_name' => array($_FILES[$file_field_name]['tmp_name']),
            'size' => array($_FILES[$file_field_name]['size']),
            'type' => array($_FILES[$file_field_name]['type']),
            'error' => array($_FILES[$file_field_name]['error'])
        );
        $file_count = 1;
    }
    
    // 보안상 금지할 파일 확장자(서버 실행 가능/위험 파일 차단). 그 외 확장자는 모두 허용
    $blocked_extensions = array('php','php3','php4','php5','phtml','phps','asp','aspx','jsp','cgi','pl','py','exe','com','bat','sh','cmd','dll','msi','hta','htm','html');
    $max_file_size = 50 * 1024 * 1024; // 50MB
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue; // 업로드 오류가 있는 파일은 건너뛰기
        }
        
        $original_name = $files['name'][$i];
        $tmp_name = $files['tmp_name'][$i];
        $file_size = $files['size'][$i];
        $mime_type = $files['type'][$i];
        
        // 파일 크기 체크
        if ($file_size > $max_file_size) {
            throw new Exception("파일 크기가 너무 큽니다: {$original_name} (최대 50MB)");
        }
        
        // 파일 확장자 체크 (차단 목록만 제한, 기타 확장자는 허용)
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if (in_array($file_extension, $blocked_extensions)) {
            throw new Exception("보안상 업로드가 제한된 파일 형식입니다: {$original_name}");
        }
        
        // 안전한 파일명 생성
        $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $stored_name = $safe_filename . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        $file_path = $upload_dir . '/' . $stored_name;
        $file_url = $upload_url . '/' . $stored_name;
        
        // 파일 이동 (move_uploaded_file 실패 시 rename/copy 폴백)
        $moved = false;
        $tmpExists = file_exists($tmp_name);
        if ($tmpExists && is_uploaded_file($tmp_name)) { $moved = @move_uploaded_file($tmp_name, $file_path); }
        if (!$moved && $tmpExists) { $moved = @rename($tmp_name, $file_path); }
        if (!$moved && $tmpExists) { $moved = @copy($tmp_name, $file_path); }
        if (!$moved || !file_exists($file_path)) {
            $dirWritable = is_writable($upload_dir);
            $baseWritable = is_writable($upload_base_dir);
            $errMsg = "파일 업로드 실패: {$original_name}";
            $errDbg = array(
                'tmp_exists' => $tmpExists ? 1 : 0,
                'is_uploaded_file' => (int) ( $tmpExists && is_uploaded_file($tmp_name) ),
                'dest_dir' => $upload_dir,
                'dest_dir_writable' => $dirWritable ? 1 : 0,
                'base_dir' => $upload_base_dir,
                'base_dir_writable' => $baseWritable ? 1 : 0,
                'dest_path' => $file_path
            );
            throw new Exception($errMsg . ' :: ' . json_encode($errDbg));
        }
        @chmod($file_path, 0644);
        
        // 파일 권한 설정
        @chmod($file_path, 0644);
        
        // 세션 ID 가져오기
        $session_id = session_id();
        if (empty($session_id)) {
            session_start();
            $session_id = session_id();
        }
        
        // 임시 주문 ID 생성 (세션 기반)
        $session_order_id = 'session_' . $session_id;
        
        // 데이터베이스에 파일 정보 저장
        $conn = getDBConnection();
        if (!$conn) {
            // 파일 삭제
            @unlink($file_path);
            echo json_encode(array('success' => false, 'message' => '데이터베이스 연결 실패: 쓰기 권한 또는 경로 문제'));
            exit;
        }
        // 컬럼 유효성에 따라 동적 INSERT 구성 (PDO/mysqli 모두 지원)
        $cols = array();
        if (class_exists('PDO') && $conn instanceof PDO) {
            try {
                $rs = $conn->query("SHOW COLUMNS FROM `partner_lab_order_files`");
                if ($rs) { foreach ($rs as $r) { $cols[$r['Field']] = $r; } }
            } catch (Exception $e) { $cols = array(); }
        } elseif (class_exists('mysqli') && $conn instanceof mysqli) {
            $cols = dbu_get_columns($conn, 'partner_lab_order_files');
        }
        $use_cols = array();
        $params = array();
        // order_id는 업로드 시점에 생략(주문 완료 시 매핑). NOT NULL이면 0으로 임시 대입
        if (isset($cols['order_id'])) {
            $isNotNull = (isset($cols['order_id']['Null']) && $cols['order_id']['Null'] === 'NO');
            if ($isNotNull) { $use_cols[] = 'order_id'; $params[] = '0'; }
        }
        if (isset($cols['file_type'])) { $use_cols[] = 'file_type'; $params[] = $file_type; }
        if (isset($cols['original_name'])) { $use_cols[] = 'original_name'; $params[] = $original_name; }
        // stored_name 또는 file_name 중 존재하는 컬럼 사용
        if (isset($cols['stored_name'])) { $use_cols[] = 'stored_name'; $params[] = $stored_name; }
        elseif (isset($cols['file_name'])) { $use_cols[] = 'file_name'; $params[] = $stored_name; }
        // 필수: file_path
        $use_cols[] = 'file_path'; $params[] = $file_path;
        if (isset($cols['file_size'])) { $use_cols[] = 'file_size'; $params[] = $file_size; }
        // 날짜 컬럼 결정
        $date_col = null;
        if (isset($cols['uploaded_at'])) { $date_col = 'uploaded_at'; }
        elseif (isset($cols['created_at'])) { $date_col = 'created_at'; }
        if ($date_col) { $use_cols[] = $date_col; $params[] = date('Y-m-d H:i:s'); }

        // 플레이스홀더 구성
        $placeholders = implode(', ', array_fill(0, count($use_cols), '?'));
        $sql = 'INSERT INTO partner_lab_order_files (' . implode(', ', $use_cols) . ') VALUES (' . $placeholders . ')';

        if (class_exists('PDO') && $conn instanceof PDO) {
            $stmt = $conn->prepare($sql);
            if (!$stmt->execute($params)) {
                @unlink($file_path);
                throw new Exception("파일 정보 저장 실패: {$original_name} (PDO)");
            }
            $file_id = $conn->lastInsertId();
        } elseif (class_exists('mysqli') && $conn instanceof mysqli) {
            // 단순 이스케이프 기반 INSERT (PHP 5.2 호환, 바인딩 오류 회피)
            $esc = array();
            foreach ($params as $p) { $esc[] = "'" . $conn->real_escape_string((string)$p) . "'"; }
            $sql2 = 'INSERT INTO partner_lab_order_files (' . implode(', ', $use_cols) . ') VALUES (' . implode(', ', $esc) . ')';
            $ok = $conn->query($sql2);
            if (!$ok) {
                @unlink($file_path);
                throw new Exception("파일 정보 저장 실패: {$original_name} (mysqli query 실패) :: " . $conn->error);
            }
            $file_id = $conn->insert_id;
        } else {
            @unlink($file_path);
            echo json_encode(array('success' => false, 'message' => '지원되지 않는 DB 드라이버'));
            exit;
        }
        
        $uploaded_files[] = array(
            'file_id' => $file_id,
            'original_name' => $original_name,
            'stored_name' => $stored_name,
            'file_size' => $file_size,
            'file_url' => $file_url,
            'file_type' => $file_type,
            'upload_time' => date('Y-m-d H:i:s')
        );
    }
    
    if (empty($uploaded_files)) {
        throw new Exception('업로드된 파일이 없습니다.');
    }
    
    if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
    echo json_encode(array(
        'success' => true,
        'message' => count($uploaded_files) . '개 파일이 업로드되었습니다.',
        'files' => $uploaded_files
    ));
    
} catch (Exception $e) {
    // Production: Remove error logging
            // error_log('Upload Error: ' . $e->getMessage());
    if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage()
    ));
}
?>
