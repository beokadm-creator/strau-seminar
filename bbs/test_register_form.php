<?php
include_once('./_common.php');

// 회원가입 옵션 설정
$g5['title'] = '회원가입 (테스트)';

// 테스트 모드 플래그
$test_mode = true;

// 기존 register.php 파일의 내용을 복사하여 테스트용으로 수정
include_once('./_head.php');
include_once(G5_CAPTCHA_PATH.'/captcha.lib.php');

$action_url = './test_register_form_update.php';

// 회원가입 설정
$config['cf_cert_use'] = 0; // 테스트용으로 본인인증 비활성화
$config['cf_cert_req'] = 0;
$config['cf_cert_limit'] = 0;

// 추천인 기능 활성화
$use_referral = true;

include_once($member_skin_path.'/test_register_form.skin.php');

include_once('./_tail.php');
?>