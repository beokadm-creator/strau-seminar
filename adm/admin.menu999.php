<?
/**
 * admin 계정 전용 메뉴 목록
 */
if($member['mb_id'] == 'admin' && $member['mb_level'] == '10'){
    $menu['menu100'] = array(
        array('100000', '팝업레이어관리', G5_ADMIN_URL . '/newwinlist.php', 'poplayer'),
        array('100310', '팝업레이어관리', G5_ADMIN_URL . '/newwinlist.php', 'scf_poplayer'),
    );
    $menu['menu200'] = array(
        array('200000', '회원관리', G5_ADMIN_URL . '/enrollment_list.php', 'member'),
        array('200100', '회원관리', G5_ADMIN_URL . '/enrollment_list.php', 'mb_list'),
        array('200800', '접속자집계', G5_ADMIN_URL . '/visit_list.php', 'mb_visit', 1),
        array('200810', '접속자검색', G5_ADMIN_URL . '/visit_search.php', 'mb_search', 1),
        array('200820', '접속자로그삭제', G5_ADMIN_URL . '/visit_delete.php', 'mb_delete', 1),
    );
    $menu['menu300'] = array(
        array('300000', '게시판관리', '' . G5_ADMIN_URL . '/boardgroup_list.php', 'board'),
       // array('300200', '게시판그룹관리', '' . G5_ADMIN_URL . '/boardgroup_list.php', 'bbs_group'),//
		array('300900', '카테고리 관리', G5_ADMIN_URL.'/category/category_list.php', 'cate'),
		array('300910', '제품 관리', G5_ADMIN_URL.'/products/products_list.php', 'product'),
        array('300980', '강의 관리', G5_ADMIN_URL.'/board_manager/post_list.php', 'board_manager_post'),
        array('300970', '런칭쇼 관리', G5_ADMIN_URL.'/board_manager/post_list2.php', 'board_manager_post'),
        array('300990', 'Q&A 관리', G5_ADMIN_URL.'/board_manager/comment_list.php', 'board_manager_cmt'),
		array('300991', '문의현황', G5_ADMIN_URL.'/qa_list.php', 'qa'),
		array('300830', '배너관리', G5_ADMIN_URL.'/bannerlist.php', 'banner_list'),
		array('99999', '제품배너관리','/banner', 'banner'),
    );
    
    $menu['menu400'] = array();
    $menu['menu500'] = array();
    $menu['menu900'] = array();
}