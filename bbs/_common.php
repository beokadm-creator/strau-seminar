<?php
// bbs 하위 스크립트에서 사용하는 공통 로더

@include_once dirname(__FILE__) . '/../_common.php';

if (!defined('_GNUBOARD_')) {
    $root = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') : '';
    if ($root && file_exists($root . '/_common.php')) {
        include_once $root . '/_common.php';
    }
}

if (!defined('_GNUBOARD_')) {
    $parent_common = dirname(__FILE__) . '/../common.php';
    if (file_exists($parent_common)) {
        include_once $parent_common;
    }
}

if (!defined('_GNUBOARD_')) {
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
    }
    echo '<script>alert("시스템 설정 파일을 찾을 수 없습니다. 잠시 후 다시 시도해 주세요.");history.back();</script>';
    exit;
}