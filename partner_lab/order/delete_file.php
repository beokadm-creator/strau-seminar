<?php
@include_once '../_common.php';
@include_once '../config.php';
include_once 'db_config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(array('success'=>false,'message'=>'잘못된 요청입니다.')); exit; }

$file_id = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;
$path_param = isset($_POST['path']) ? $_POST['path'] : '';
if ($file_id <= 0 && !$path_param) { echo json_encode(array('success'=>false,'message'=>'파일 ID 또는 경로가 필요합니다.')); exit; }

$db = getDBConnection();
if (!$db) { echo json_encode(array('success'=>false,'message'=>'DB 연결 실패')); exit; }

$row = null;
if ($file_id > 0) {
  if (class_exists('PDO') && $db instanceof PDO) {
    $st = $db->prepare('SELECT file_id, file_path FROM partner_lab_order_files WHERE file_id = ? LIMIT 1');
    $st->execute(array($file_id));
    $row = $st->fetch(PDO::FETCH_ASSOC);
  } elseif (class_exists('mysqli') && $db instanceof mysqli) {
    $st = $db->prepare('SELECT file_id, file_path FROM partner_lab_order_files WHERE file_id = ? LIMIT 1');
    if ($st) { $st->bind_param('i', $file_id); $st->execute(); $res = $st->get_result(); $row = $res ? $res->fetch_assoc() : null; $st->close(); }
  }
}
if (!$row && $path_param) { $row = array('file_id'=>0, 'file_path'=>$path_param); }

if (!$row) { echo json_encode(array('success'=>false,'message'=>'파일을 찾을 수 없습니다.')); exit; }
$path = $row['file_path'];
if ($path && file_exists($path)) { @unlink($path); }

// 삭제
if (class_exists('PDO') && $db instanceof PDO) {
  if ($file_id > 0) {
    $st = $db->prepare('DELETE FROM partner_lab_order_files WHERE file_id = ?');
    $st->execute(array($file_id));
  } elseif ($path_param) {
    $st = $db->prepare('DELETE FROM partner_lab_order_files WHERE file_path = ?');
    $st->execute(array($path_param));
  }
} elseif (class_exists('mysqli') && $db instanceof mysqli) {
  if ($file_id > 0) {
    $st = $db->prepare('DELETE FROM partner_lab_order_files WHERE file_id = ?');
    if ($st) { $st->bind_param('i', $file_id); $st->execute(); $st->close(); }
  } elseif ($path_param) {
    $st = $db->prepare('DELETE FROM partner_lab_order_files WHERE file_path = ?');
    if ($st) { $st->bind_param('s', $path_param); $st->execute(); $st->close(); }
  }
}

echo json_encode(array('success'=>true));
?>
