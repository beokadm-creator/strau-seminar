<?php
include_once('../common.php');

$svuri = explode("?", $_SERVER['REQUEST_URI']);
$uri = $svuri[1];

if ($member['mb_level'] < 3) {
		if ($is_member)
				alert('글을 읽을 권한이 없습니다.', G5_URL);
		else
				goto_url(G5_BBS_URL.'/login.php?url='.urlencode($_SERVER['REQUEST_URI']));
}

/* 테마에 소스 불러오기 */
if(defined('G5_THEME_PATH')) {
    require_once(G5_THEME_PATH.'/sub/list.skin.php');
    return;
}
?>

<?php
include_once(G5_THEME_PATH.'/tail.php');
?>