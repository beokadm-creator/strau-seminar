<?php
include_once dirname(__FILE__).'/../_common.php';
include_once dirname(__FILE__).'/db_config.php';
include_once dirname(__FILE__).'/db_utils.php';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$allow_guest = false;
$conn = getDBConnection();
if (!$conn) { exit; }
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($order_id <= 0) { exit; }
$orderPk = dbu_get_order_pk($conn);
if ($token !== '') {
    if ($conn instanceof PDO) {
        $st = $conn->prepare("SELECT 1 FROM partner_lab_order_logs WHERE order_id = ? AND action = 'zip_token' AND description = ? LIMIT 1");
        $st->execute(array($order_id, $token));
        $allow_guest = $st->fetch(PDO::FETCH_NUM) ? true : false;
    } elseif (is_object($conn) && (get_class($conn) === 'mysqli')) {
        $sql = "SELECT 1 FROM partner_lab_order_logs WHERE order_id = ? AND action = 'zip_token' AND description = ? LIMIT 1";
        $st = mysqli_prepare($conn, $sql);
        if ($st) { $t1 = is_int($order_id)?'i':'s'; mysqli_stmt_bind_param($st, $t1.'s', $order_id, $token); mysqli_stmt_execute($st); mysqli_stmt_store_result($st); $allow_guest = (mysqli_stmt_num_rows($st) > 0); mysqli_stmt_close($st); }
    }
}
if (!$allow_guest) {
    if (isset($is_guest) ? $is_guest : (isset($is_member) ? !$is_member : true)) { exit; }
}
$order = null;
if ($conn instanceof PDO) {
    $stmt = $conn->prepare("SELECT * FROM partner_lab_orders WHERE `{$orderPk}` = ?");
    $stmt->execute(array($order_id));
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif (is_object($conn) && (get_class($conn) === 'mysqli')) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM partner_lab_orders WHERE `{$orderPk}` = ?");
    if ($stmt) {
        $type = is_int($order_id) ? 'i' : 's';
        mysqli_stmt_bind_param($stmt, $type, $order_id);
        mysqli_stmt_execute($stmt);
        $meta = mysqli_stmt_result_metadata($stmt);
        $fields = array();
        $bind = array();
        $bindArgs = array($stmt);
        if ($meta) {
            while ($f = mysqli_fetch_field($meta)) { $fields[] = $f->name; $bind[$f->name] = null; $bindArgs[] = &$bind[$f->name]; }
            call_user_func_array('mysqli_stmt_bind_result', $bindArgs);
            if (mysqli_stmt_fetch($stmt)) {
                $row = array();
                foreach ($fields as $name) { $row[$name] = $bind[$name]; }
                $order = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
if (!$order) { exit; }
$fileDateCol = dbu_get_files_date_col($conn);
$filesSql = "SELECT * FROM partner_lab_order_files WHERE order_id = ?";
if ($fileDateCol) { $filesSql .= " ORDER BY `{$fileDateCol}` DESC"; }
$files = array();
if ($conn instanceof PDO) {
    $files_stmt = $conn->prepare($filesSql);
    $files_stmt->execute(array($order_id));
    $files = $files_stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (is_object($conn) && (get_class($conn) === 'mysqli')) {
    $stmt = mysqli_prepare($conn, $filesSql);
    if ($stmt) {
        $type = is_int($order_id) ? 'i' : 's';
        mysqli_stmt_bind_param($stmt, $type, $order_id);
        mysqli_stmt_execute($stmt);
        $meta = mysqli_stmt_result_metadata($stmt);
        $fields = array();
        $bind = array();
        $bindArgs = array($stmt);
        if ($meta) {
            while ($f = mysqli_fetch_field($meta)) { $fields[] = $f->name; $bind[$f->name] = null; $bindArgs[] = &$bind[$f->name]; }
            call_user_func_array('mysqli_stmt_bind_result', $bindArgs);
            while (mysqli_stmt_fetch($stmt)) { $row = array(); foreach ($fields as $name) { $row[$name] = $bind[$name]; } $files[] = $row; }
        }
        mysqli_stmt_close($stmt);
    }
}
$attachments = array();
foreach ($files as $f) {
    $fpath = isset($f['file_path']) ? $f['file_path'] : '';
    $fname = isset($f['original_name']) && $f['original_name'] ? $f['original_name'] : (isset($f['file_name']) ? $f['file_name'] : '');
    if ((!$fname || $fname==='') && $fpath) { $fname = basename($fpath); }
    if ($fpath && file_exists($fpath)) { $attachments[] = array('path' => $fpath, 'name' => $fname); }
}
if (empty($attachments)) { exit; }
$tmpDir = defined('G5_DATA_PATH') ? (G5_DATA_PATH . '/tmp') : (dirname(__FILE__) . '/tmp');
if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0777, true); }
$zip_name = 'order_' . (isset($order['order_number']) ? $order['order_number'] : $order_id) . '_attachments.zip';
$zip_path = $tmpDir . '/' . $zip_name;
if (!function_exists('pl_build_zip')) {
    function pl_build_zip($zip_path, $files) {
        $fp = @fopen($zip_path, 'wb');
        if (!$fp) return false;
        $offset = 0;
        $central = '';
        $count = 0;
        foreach ($files as $f) {
            $p = isset($f['path']) ? $f['path'] : '';
            $n = isset($f['name']) ? $f['name'] : ($p ? basename($p) : '');
            if (!$p || !file_exists($p)) continue;
            $data = @file_get_contents($p);
            if ($data === false) continue;
            $count++;
            $mtime = @filemtime($p);
            if (!$mtime) $mtime = time();
            $d = getdate($mtime);
            $dosTime = ($d['hours'] << 11) | ($d['minutes'] << 5) | (int)floor($d['seconds'] / 2);
            $dosDate = (($d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday'];
            $crc = crc32($data);
            $crc = $crc & 0xFFFFFFFF;
            $size = strlen($data);
            $localOffset = $offset;
            $lh = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, strlen($n), 0);
            fwrite($fp, $lh);
            fwrite($fp, $n);
            fwrite($fp, $data);
            $offset += strlen($lh) + strlen($n) + $size;
            $ce = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, strlen($n), 0, 0, 0, 0, 0, $localOffset);
            $central .= $ce . $n;
        }
        $centralSize = strlen($central);
        $centralOffset = $offset;
        fwrite($fp, $central);
        $eocd = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralSize, $centralOffset, 0);
        fwrite($fp, $eocd);
        fclose($fp);
        return $count > 0 && file_exists($zip_path);
    }
}
$ok = false;
if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $opened = $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($opened === true) {
        foreach ($attachments as $f) {
            $fpath = isset($f['path']) ? $f['path'] : '';
            $fname = isset($f['name']) ? $f['name'] : ($fpath ? basename($fpath) : '');
            if ($fpath && file_exists($fpath)) { $zip->addFile($fpath, $fname); }
        }
        $zip->close();
        $ok = file_exists($zip_path);
    }
}
if (!$ok) { $ok = pl_build_zip($zip_path, $attachments); }
if (!$ok) { exit; }
$zip_size = (int)@filesize($zip_path);
if ($zip_size <= 0) { @unlink($zip_path); exit; }
header('Content-Type: application/zip');
header('Content-Length: ' . $zip_size);
header('Content-Disposition: attachment; filename="' . $zip_name . '"');
header('Cache-Control: private');
readfile($zip_path);
@unlink($zip_path);
exit;
