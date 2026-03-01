<?php
require_once './_common.php';

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$w = $_POST['w'];
$id = (int)$_POST['id'];

$title = trim($_POST['title']);
$alt_text = trim($_POST['alt_text']);
$link_url = trim($_POST['link_url']);
$is_new_window = (int)$_POST['is_new_window'];
$sort_order = (int)$_POST['sort_order'];
$is_active = (int)$_POST['is_active'];

// 필수 입력 체크
if (!$title) {
    alert('배너 제목을 입력해주세요.');
}

// 이미지 업로드 처리
$image_path = '';
$upload_error = '';

if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK) {
    $upload_dir = G5_DATA_PATH . '/partner_lab/images';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, G5_DIR_PERMISSION, true);
    }
    
    $file = $_FILES['banner_image'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = array('jpg', 'jpeg', 'png', 'gif');
    
    if (!in_array($file_ext, $allowed_exts)) {
        alert('JPG, PNG, GIF 형식의 이미지만 업로드 가능합니다.');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        alert('이미지 파일 크기는 5MB 이하여야 합니다.');
    }
    
    $new_filename = date('YmdHis') . '_' . uniqid() . '.' . $file_ext;
    $upload_path = $upload_dir . '/' . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $image_path = G5_DATA_URL . '/partner_lab/images/' . $new_filename;
    } else {
        alert('이미지 업로드에 실패했습니다.');
    }
}

if ($w == 'u') {
    // 수정
    if (!$id) {
        alert('잘못된 접근입니다.');
    }
    
    // 기존 데이터 조회
    $sql = "SELECT * FROM {$g5['partner_lab_banner_table']} WHERE id = '$id'";
    $banner = sql_fetch($sql);
    if (!$banner) {
        alert('존재하지 않는 배너입니다.');
    }
    
    // 이미지 삭제 처리
    if (isset($_POST['del_image']) && $_POST['del_image'] == '1') {
        if ($banner['image_path']) {
            $old_file = str_replace(G5_DATA_URL, G5_DATA_PATH, $banner['image_path']);
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }
        $image_path = '';
    } else if (!$image_path) {
        $image_path = $banner['image_path'];
    } else {
        // 새 이미지가 업로드된 경우 기존 이미지 삭제
        if ($banner['image_path']) {
            $old_file = str_replace(G5_DATA_URL, G5_DATA_PATH, $banner['image_path']);
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }
    }
    
    $sql = "UPDATE {$g5['partner_lab_banner_table']} SET 
                title = '" . sql_real_escape_string($title) . "',
                image_path = '" . sql_real_escape_string($image_path) . "',
                alt_text = '" . sql_real_escape_string($alt_text) . "',
                link_url = '" . sql_real_escape_string($link_url) . "',
                is_new_window = '$is_new_window',
                sort_order = '$sort_order',
                is_active = '$is_active',
                updated_at = NOW()
            WHERE id = '$id'";
    
    sql_query($sql);
    
    alert('배너가 수정되었습니다.', './partner_lab_banner.php');
    
} else {
    // 등록
    if (!$image_path) {
        alert('배너 이미지를 선택해주세요.');
    }
    
    $sql = "INSERT INTO {$g5['partner_lab_banner_table']} SET 
                title = '" . sql_real_escape_string($title) . "',
                image_path = '" . sql_real_escape_string($image_path) . "',
                alt_text = '" . sql_real_escape_string($alt_text) . "',
                link_url = '" . sql_real_escape_string($link_url) . "',
                is_new_window = '$is_new_window',
                sort_order = '$sort_order',
                is_active = '$is_active',
                created_at = NOW(),
                updated_at = NOW()";
    
    sql_query($sql);
    
    alert('배너가 등록되었습니다.', './partner_lab_banner.php');
}
?>