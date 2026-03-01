<?php
include_once('./_common.php');

// 로그인중인 경우 회원가입 할 수 없습니다.
if ($is_member) {
    goto_url(G5_URL);
}

// 세션을 지웁니다.
set_session("ss_mb_reg", "");

$g5['title'] = '회원가입약관';
include_once('./_head.php');

$register_action_url = G5_BBS_URL.'/register_form.php';
// Persist referral code server-side
try {
    $referral_code = '';
    if (isset($_GET['referral_code']) && $_GET['referral_code']) { $referral_code = trim($_GET['referral_code']); }
    else if (isset($_GET['ref']) && $_GET['ref']) { $referral_code = trim($_GET['ref']); }
    if ($referral_code !== '') { set_session('referral_code', $referral_code); }
} catch(Exception $e) {}
include_once($member_skin_path.'/register.skin.php');

include_once('./_tail.php');
