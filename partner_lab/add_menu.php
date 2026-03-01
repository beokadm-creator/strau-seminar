<?php
// 파트너랩 메뉴 추가 스크립트
require_once '../common.php';

// 관리자 권한 확인
if ($is_admin != 'super') {
    die('관리자 권한이 필요합니다.');
}

// 메뉴 테이블에 파트너랩 메뉴 추가
$sql = "INSERT INTO {$g5['menu_table']} (me_code, me_name, me_link, me_target, me_order, me_use, me_mobile_use) 
        VALUES ('99', '파트너 랩 주문', '/partner_lab/', 'self', 99, 1, 1)
        ON DUPLICATE KEY UPDATE 
        me_name = VALUES(me_name),
        me_link = VALUES(me_link),
        me_target = VALUES(me_target),
        me_order = VALUES(me_order),
        me_use = VALUES(me_use),
        me_mobile_use = VALUES(me_mobile_use)";

$result = sql_query($sql);

if ($result) {
    echo "파트너랩 메뉴가 성공적으로 추가되었습니다.";
} else {
    echo "메뉴 추가 중 오류가 발생했습니다: " . sql_error();
}
?>