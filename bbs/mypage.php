<?php
include_once('./_common.php');

$g5['title'] = '마이페이지';
include_once('./_head.php');

if (!$is_member)
    goto_url(G5_BBS_URL.'/login.php?url='.urlencode(G5_BBS_URL.'/mypage.php'));

if(!empty($page)) $page_chk = true;

if($member["mb_level"] == 10 ) $is_admin = "super";

//$register_action_url = G5_BBS_URL.'/register_form.php';
include_once($member_skin_path.'/mypage.skin.php');

include_once('./_tail.php');
?>