<?php
// 파트너랩 주문관리 - 최상위 메뉴 (게시판관리와 동일 레벨)

// 관리자만 노출
if (isset($member) && isset($member['mb_level']) && (int)$member['mb_level'] == 10) {
    $menu['menu350'] = array(
        // 최상위 버튼 및 그룹 제목
        array('350000', '파트너랩 주문관리', '/partner_lab/partner_admin/index.php', 'partner_lab_order_admin'),
        // 하위(2단) 메뉴 항목들
        array('350100', '파트너랩 주문관리', '/partner_lab/partner_admin/index.php', 'partner_lab_order_admin'),
    );
}
?>