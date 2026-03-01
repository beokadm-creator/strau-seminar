<?php
// 안전한 파일 다운로드 처리
include_once './db_config.php';

function respond_bad($msg){
  header('Content-Type: text/plain; charset=utf-8');
  header('HTTP/1.1 400 Bad Request');
  echo $msg;
  exit;
}

$file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;
$path_param = isset($_GET['path']) ? $_GET['path'] : '';
$name_param = isset($_GET['name']) ? $_GET['name'] : '';

$db = getDBConnection();
if (!$db){ respond_bad('데이터베이스 연결 실패'); }

$row = null;
if ($file_id > 0) {
  if (class_exists('PDO') && $db instanceof PDO){
    $st = $db->prepare('SELECT file_id, original_name, stored_name, file_path, file_size FROM partner_lab_order_files WHERE file_id = ? LIMIT 1');
    $st->execute(array($file_id));
    $row = $st->fetch(PDO::FETCH_ASSOC);
  } elseif (class_exists('mysqli') && $db instanceof mysqli){
    $st = $db->prepare('SELECT file_id, original_name, stored_name, file_path, file_size FROM partner_lab_order_files WHERE file_id = ? LIMIT 1');
    if ($st){ $st->bind_param('i', $file_id); $st->execute(); $res = $st->get_result(); $row = $res ? $res->fetch_assoc() : null; $st->close(); }
  }
}

if (!$row && $path_param) {
  $row = array('file_path' => $path_param, 'original_name' => $name_param, 'stored_name' => basename($path_param), 'file_size' => @filesize($path_param));
}

if (!$row || empty($row['file_path'])) { respond_bad('잘못된 요청입니다.'); }
// URL 인코딩된 경로 안전 디코딩
$path = $row['file_path'];
if ($path) { $path = urldecode($path); }
if (!file_exists($path)) { respond_bad('파일이 존재하지 않습니다.'); }


$name = isset($row['original_name']) && $row['original_name'] ? $row['original_name'] : (isset($row['stored_name']) ? $row['stored_name'] : basename($path));
clearstatcache();
$size = filesize($path);

// 출력 버퍼/압축 종료
if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { @ob_end_clean(); } }
if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
if (function_exists('ini_set')) { @ini_set('zlib.output_compression', '0'); }

// MIME 추정
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';
if ($ext === 'pdf') $mime = 'application/pdf';
elseif ($ext === 'png') $mime = 'image/png';
elseif ($ext === 'jpg' || $ext === 'jpeg') $mime = 'image/jpeg';
elseif ($ext === 'zip') $mime = 'application/zip';

// 헤더 설정
header('Content-Description: File Transfer');
header('Content-Type: '.$mime);
header('Content-Disposition: attachment; filename="'.basename($name).'"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.$size);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$fp = fopen($path, 'rb');
if ($fp) {
  $chunk = 1024 * 64;
  while (!feof($fp)) {
    echo fread($fp, $chunk);
  }
  fclose($fp);
}
exit;
?>
