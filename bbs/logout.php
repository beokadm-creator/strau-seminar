<?php
define('G5_CERT_IN_PROG', true);
include_once('./_common.php');

if(function_exists('social_provider_logout')){
    social_provider_logout();
}

// 세션 안전 종료 (PHP 5.2 호환)
if (!function_exists('session_status')) {
    if (session_id() === '') {
        @session_start();
    }
    if (session_id() !== '') {
        $_SESSION = array();
        @session_unset();
        @session_destroy();
        // 세션 쿠키 제거
        if (ini_get('session.use_cookies')) {
            @setcookie(session_name(), '', 0, '/');
        }
    }
} else {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        @session_unset();
        @session_destroy();
        if (ini_get('session.use_cookies')) {
            @setcookie(session_name(), '', 0, '/');
        }
    }
}

// 자동로그인 해제 --------------------------------
if (function_exists('set_cookie')) {
    set_cookie('ck_mb_id', '', 0);
    set_cookie('ck_auto', '', 0);
} else {
    // 폴백 처리
    @setcookie('ck_mb_id', '', 0, '/');
    @setcookie('ck_auto', '', 0, '/');
}
// 자동로그인 해제 end --------------------------------

if ($url) {
    if ( substr($url, 0, 2) == '//' )
        $url = 'http:' . $url;

    if (preg_match('#\\\0#', $url) || preg_match('/^\/{1,}\\\/', $url)) {
        alert('url 에 올바르지 않은 값이 포함되어 있습니다.', G5_URL);
    }

    $p = @parse_url(urldecode(str_replace('\\', '', $url)));
    /*
        // OpenRediect 취약점관련, PHP 5.3 이하버전에서는 parse_url 버그가 있음 ( Safflower 님 제보 ) 아래 url 예제
        // http://localhost/bbs/logout.php?url=http://sir.kr%23@/
    */
    if (preg_match('/^https?:\/\//i', $url) || $p['scheme'] || $p['host']) {
        alert('url에 도메인을 지정할 수 없습니다.', G5_URL);
    }

    if($url == 'shop')
        $link = G5_SHOP_URL;
    else
        $link = $url;
} else if ($bo_table) {
    $link = get_pretty_url($bo_table);
} else {
    $link = G5_URL;
}

// 이벤트 훅 실행 (존재 시)
if (function_exists('run_event')) {
    run_event('member_logout', $link);
}

// 이동 처리 (존재 시 goto_url, 없으면 안전한 폴백)
if (function_exists('goto_url')) {
    goto_url($link);
} else {
    $dest = isset($link) && $link ? $link : '/';
    if (!headers_sent()) {
        header('Location: ' . $dest);
    } else {
        echo '<script>location.replace(' . json_encode($dest) . ');</script>';
    }
    exit;
}