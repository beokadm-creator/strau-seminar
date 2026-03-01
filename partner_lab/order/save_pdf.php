<?php
include_once dirname(__FILE__).'/../_common.php';
include_once dirname(__FILE__).'/db_config.php';
include_once dirname(__FILE__).'/db_utils.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(array('success'=>false,'message'=>'invalid')); exit; }
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
if ($order_id <= 0) { echo json_encode(array('success'=>false,'message'=>'missing_order')); exit; }
$dirCandidates = array();
if (defined('PARTNER_LAB_DATA_PATH')) { $dirCandidates[] = PARTNER_LAB_DATA_PATH.'/pdf'; }
$dirCandidates[] = dirname(__FILE__).'/pdf';
$dir = '';
foreach ($dirCandidates as $d) {
    if (!is_dir($d)) { @mkdir($d, 0777, true); }
    if (is_dir($d) && is_writable($d)) { $dir = $d; break; }
}
if ($dir === '') { echo json_encode(array('success'=>false,'message'=>'dir_not_writable')); exit; }
$path = $dir.'/order_'.(int)$order_id.'.pdf';

$saved = false;
if (isset($_FILES['pdf_file']) && is_array($_FILES['pdf_file']) && isset($_FILES['pdf_file']['tmp_name']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['pdf_file']['tmp_name'];
    $saved = @move_uploaded_file($tmp, $path);
}
if (!$saved) {
    $pdf = isset($_POST['pdf']) ? $_POST['pdf'] : '';
    if ($pdf !== '') {
        if (strpos($pdf, 'data:') === 0) { $pos = strpos($pdf, ','); if ($pos !== false) { $pdf = substr($pdf, $pos+1); } }
        $pdf = preg_replace('/\s+/', '', $pdf);
        $pdf = str_replace(' ', '+', $pdf);
        $bin = base64_decode($pdf, true);
        if ($bin === false) { $bin = base64_decode($pdf); }
        if ($bin !== false && strlen($bin) > 1024) {
            $saved = (@file_put_contents($path, $bin) !== false);
        }
    }
}

if ($saved) { echo json_encode(array('success'=>true,'path'=>$path)); } else { echo json_encode(array('success'=>false,'message'=>'save_failed')); }