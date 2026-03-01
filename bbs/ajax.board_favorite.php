<?php
include_once("./_common.php");

if (!$is_member) return false;

// 좋아요 수를 천단위부터 소숫점으로 표기하는 함수
function format_like_count($count) {
    if ($count >= 1000000) {
        return round($count / 1000000, 1) . 'M';
    } elseif ($count >= 1000) {
        return round($count / 1000, 1) . '천';
    } else {
        return number_format($count);
    }
}

$sql = "
    SELECT * FROM board_favorite WHERE board_id = '{$board_id}' AND wr_id = '{$wr_id}' AND mb_id = '{$member['mb_id']}' AND type = '{$type}'
";
$check = sql_fetch($sql);

if ($check) {
    $sql = "
        DELETE FROM board_favorite WHERE board_id = '{$board_id}' AND wr_id = '{$wr_id}' AND mb_id = '{$member['mb_id']}' AND type = '{$type}'
    ";
    sql_query($sql);
    $toggle = 'off';
} else {
    $sql = "
        INSERT INTO board_favorite (board_id, wr_id, mb_id, type, reg_date) VALUES ('{$board_id}', '{$wr_id}', '{$member['mb_id']}', '{$type}', NOW())
    ";
    sql_query($sql);
    $toggle = 'on';
}

// 현재 좋아요 수 조회
$sql = "
    SELECT COUNT(*) as like_cnt
    FROM board_favorite
    WHERE board_id = '{$board_id}'
      AND wr_id = '{$wr_id}'
      AND type = '{$type}'
";
$like_data = sql_fetch($sql);
$like_count_display = format_like_count($like_data['like_cnt']);

echo json_encode(array(
    'toggle' => $toggle,
    'like_count' => $like_count_display
));
?>