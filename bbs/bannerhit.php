<?php
include_once("./_common.php");

$bn_id = (int) $bn_id;

$sql = " select bn_id, bn_url from {$g5['banner_table']} where bn_id = '$bn_id' ";
$row = sql_fetch($sql);

if( ! $row['bn_id'] ){
    alert('등록된 배너가 없습니다.', G5_URL);
}

if ($_COOKIE['ck_bn_id'] != $bn_id)
{
    $sql = " update {$g5['banner_table']} set bn_hit = bn_hit + 1 where bn_id = '$bn_id' ";
    sql_query($sql);
    // 하루 동안
    set_cookie("ck_bn_id", $bn_id, 60*60*24);
}

$url = clean_xss_tags($row['bn_url']);

goto_url($url);
?>
