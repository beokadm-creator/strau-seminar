<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
goto_url(short_url_clean(G5_HTTP_BBS_URL.'/board.php?bo_table='.$bo_table));
?>