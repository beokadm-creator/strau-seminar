<?php
require_once './_common.php';

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$id = (int)$_GET['id'];

if (!$id) {
    alert('잘못된 접근입니다.');
}

// 기존 데이터 조회
$sql = "SELECT * FROM {$g5['partner_lab_banner_table']} WHERE id = '$id'";
$banner = sql_fetch($sql);

if (!$banner) {
    alert('존재하지 않는 배너입니다.');
}

// 이미지 파일 삭제
if ($banner['image_path']) {
    $file_path = str_replace(G5_DATA_URL, G5_DATA_PATH, $banner['image_path']);
    if (file_exists($file_path)) {
        @unlink($file_path);
    }
}

// 데이터베이스에서 삭제
$sql = "DELETE FROM {$g5['partner_lab_banner_table']} WHERE id = '$id'";
sql_query($sql);

alert('배너가 삭제되었습니다.', './partner_lab_banner.php');
?>