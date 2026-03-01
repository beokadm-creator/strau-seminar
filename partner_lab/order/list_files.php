<?php
@include_once '../_common.php';
@include_once '../config.php';
include_once 'db_config.php';
header('Content-Type: application/json; charset=utf-8');
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) { echo json_encode(array('success'=>false,'files'=>array())); exit; }
$db = getDBConnection();
if (!$db) { echo json_encode(array('success'=>false,'files'=>array())); exit; }
$files = array();
// 날짜 컬럼 감지
$dateCol = 'created_at';
if (class_exists('PDO') && $db instanceof PDO) {
  try {
    $cols = $db->query("SHOW COLUMNS FROM `partner_lab_order_files`");
    if ($cols) { foreach ($cols as $c) { if ($c['Field'] === 'uploaded_at') { $dateCol = 'uploaded_at'; break; } } }
    $st = $db->prepare('SELECT file_id, original_name, stored_name AS file_name, file_path FROM partner_lab_order_files WHERE order_id = ? ORDER BY `'+$dateCol+'` ASC');
    // 위 문자열 결합은 PHP에서 안되므로 재작성
  } catch (Exception $e) {}
  try {
    $sql = 'SELECT file_id, original_name, stored_name AS file_name, file_path FROM partner_lab_order_files WHERE order_id = ? ORDER BY `'.$dateCol.'` ASC';
    $st = $db->prepare($sql);
    $st->execute(array($order_id));
    $files = $st->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e2) { $files = array(); }
} elseif (class_exists('mysqli') && $db instanceof mysqli) {
  $colsRes = $db->query("SHOW COLUMNS FROM `partner_lab_order_files`");
  if ($colsRes) { while ($c = $colsRes->fetch_assoc()) { if ($c['Field'] === 'uploaded_at') { $dateCol = 'uploaded_at'; break; } } $colsRes->free(); }
  $sql = 'SELECT file_id, original_name, stored_name AS file_name, file_path FROM partner_lab_order_files WHERE order_id = '.intval($order_id).' ORDER BY `'.$dateCol.'` ASC';
  if ($res = $db->query($sql)) { while ($r = $res->fetch_assoc()) { $files[] = $r; } $res->free(); }
}
echo json_encode(array('success'=>true,'files'=>$files));
?>
