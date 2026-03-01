<?php
// 파트너랩 공통 파일
if (!defined('_GNUBOARD_')) {
    // 다양한 경로에서 common.php 찾기
    $common_paths = array(
        '../common.php',           // 기본 상대 경로
        dirname(__DIR__).'/common.php',  // 절대 경로 방식
        $_SERVER['DOCUMENT_ROOT'].'/common.php',  // 문서 루트 기준
        realpath(dirname(__FILE__).'/../common.php')  // 실제 경로 계산
    );
    
    $common_loaded = false;
    foreach ($common_paths as $path) {
        if ($path && file_exists($path)) {
            include_once $path;
            $common_loaded = true;
            break;
        }
    }
    
    // common.php를 찾지 못한 경우 기본 설정
    if (!$common_loaded) {
        // 기본 상수 정의
        if (!defined('G5_PATH')) define('G5_PATH', dirname(__DIR__));
        if (!defined('G5_URL')) define('G5_URL', '');
        if (!defined('G5_DATA_PATH')) define('G5_DATA_PATH', G5_PATH.'/data');
        if (!defined('G5_DATA_URL')) define('G5_DATA_URL', G5_URL.'/data');
        if (!defined('G5_BBS_URL')) define('G5_BBS_URL', G5_URL.'/bbs');
        if (!defined('G5_DIR_PERMISSION')) define('G5_DIR_PERMISSION', 0755);
        
        // alert 함수가 정의되지 않은 경우 대체 함수 정의
        if (!function_exists('alert')) {
            function alert($msg='', $url='', $error=true, $post=false) {
                $msg = $msg ? strip_tags($msg, '<br>') : '올바른 방법으로 이용해 주십시오.';
                
                echo "<script type='text/javascript'>\n";
                if ($msg) echo "alert('" . addslashes($msg) . "');\n";
                if ($url) {
                    if ($url == 'back') {
                        echo "history.back();\n";
                    } else {
                        echo "location.href = '" . addslashes($url) . "';\n";
                    }
                }
                echo "</script>\n";
                
                if ($url) exit;
            }
        }
        
        // 기본 변수 설정
        if (!isset($is_member)) $is_member = false;
        if (!isset($member)) $member = array();
        if (!isset($config)) $config = array();
    }
}

// 파트너랩 관련 상수 정의
define('PARTNER_LAB_PATH', G5_PATH.'/partner_lab');
define('PARTNER_LAB_URL', G5_URL.'/partner_lab');
define('PARTNER_LAB_DATA_PATH', G5_DATA_PATH.'/partner_lab');
define('PARTNER_LAB_DATA_URL', G5_DATA_URL.'/partner_lab');

// 파트너랩 데이터 디렉토리 생성
if (!is_dir(PARTNER_LAB_DATA_PATH)) {
    @mkdir(PARTNER_LAB_DATA_PATH, G5_DIR_PERMISSION, true);
}

// 파트너랩 업로드 디렉토리 생성
if (!is_dir(PARTNER_LAB_DATA_PATH.'/uploads')) {
    @mkdir(PARTNER_LAB_DATA_PATH.'/uploads', G5_DIR_PERMISSION, true);
}

// 파트너랩 PDF 디렉토리 생성
if (!is_dir(PARTNER_LAB_DATA_PATH.'/pdf')) {
    @mkdir(PARTNER_LAB_DATA_PATH.'/pdf', G5_DIR_PERMISSION, true);
}

// 파트너랩 이미지 디렉토리 생성
if (!is_dir(PARTNER_LAB_DATA_PATH.'/images')) {
    @mkdir(PARTNER_LAB_DATA_PATH.'/images', G5_DIR_PERMISSION, true);
}

// 파트너랩 공통 함수들

// 파일 업로드 함수
function partner_lab_file_upload($file, $upload_dir = 'uploads') {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $upload_path = PARTNER_LAB_DATA_PATH.'/'.$upload_dir;
    if (!is_dir($upload_path)) {
        @mkdir($upload_path, G5_DIR_PERMISSION, true);
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'zip', 'rar', 'stl', 'ply');
    
    if (!in_array($file_ext, $allowed_exts)) {
        return false;
    }
    
    $new_filename = date('YmdHis').'_'.uniqid().'.'.$file_ext;
    $upload_file = $upload_path.'/'.$new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_file)) {
        return array(
            'filename' => $new_filename,
            'original_name' => $file['name'],
            'file_path' => $upload_file,
            'file_url' => PARTNER_LAB_DATA_URL.'/'.$upload_dir.'/'.$new_filename,
            'file_size' => $file['size']
        );
    }
    
    return false;
}

// 주문 단계 검증 함수
function validate_order_step($step, $data) {
    switch ($step) {
        case 1: // 주문정보
            return !empty($data['customer_name']) && !empty($data['customer_phone']);
        case 2: // 환자정보
            return !empty($data['patient_name']) && !empty($data['patient_birth']);
        case 3: // 작업모형
            return isset($data['delivery_preference']);
        case 4: // 주문상품
            return !empty($data['products']);
        case 5: // 치식 및 임플란트
            return !empty($data['selected_teeth']);
        case 6: // 기타사항
            return isset($data['agreement']);
        case 7: // 의뢰서 확인
            return true;
        default:
            return false;
    }
}

// 세션에서 주문 데이터 가져오기
function get_order_session_data() {
    if (!isset($_SESSION['partner_lab_order'])) {
        $_SESSION['partner_lab_order'] = array();
    }
    return $_SESSION['partner_lab_order'];
}

// 세션에 주문 데이터 저장
function set_order_session_data($step, $data) {
    if (!isset($_SESSION['partner_lab_order'])) {
        $_SESSION['partner_lab_order'] = array();
    }
    $_SESSION['partner_lab_order']['step'.$step] = $data;
    $_SESSION['partner_lab_order']['current_step'] = $step;
}

// 주문 데이터를 세션에 저장 (전체 데이터)
function save_order_session_data($order_data) {
    $_SESSION['partner_lab_order'] = $order_data;
}

// 주문 데이터 초기화
function clear_order_session() {
    unset($_SESSION['partner_lab_order']);
}

// 로그인 체크 및 리다이렉트
function check_partner_lab_login() {
    global $is_member, $member;
    
    if (!$is_member) {
        alert('로그인이 필요합니다.', G5_BBS_URL.'/login.php?url='.urlencode($_SERVER['REQUEST_URI']));
    }
    
    return $member;
}

// 주문 번호 생성
function generate_order_number() {
    return 'PL'.date('Ymd').sprintf('%04d', mt_rand(1, 9999));
}

// 이메일 발송 함수
function send_partner_lab_email($to_email, $subject, $message, $attachment_path = '') {
    global $config;
    
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: '.$config['cf_admin_email_name'].' <'.$config['cf_admin_email'].'>';
    
    if ($attachment_path && file_exists($attachment_path)) {
        // 첨부파일이 있는 경우 multipart 메일 처리
        $boundary = md5(time());
        $headers = array();
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/mixed; boundary="'.$boundary.'"';
        $headers[] = 'From: '.$config['cf_admin_email_name'].' <'.$config['cf_admin_email'].'>';
        
        $body = "--".$boundary."\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $message."\r\n";
        
        $body .= "--".$boundary."\r\n";
        $body .= "Content-Type: application/octet-stream; name=\"".basename($attachment_path)."\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"".basename($attachment_path)."\"\r\n\r\n";
        $body .= chunk_split(base64_encode(file_get_contents($attachment_path)))."\r\n";
        $body .= "--".$boundary."--";
        
        return mail($to_email, $subject, $body, implode("\r\n", $headers));
    } else {
        return mail($to_email, $subject, $message, implode("\r\n", $headers));
    }
}
?>