<?php 
define('G5_IS_ADMIN', true);
require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';
@mkdir(G5_DATA_PATH."/category", G5_DIR_PERMISSION);

// 생성 쿼리
// create table if not exists file_type
// (
//     idx        int auto_increment
//         primary key,
//     relatedjob varchar(255)                           null comment '해시태그',
//     mod_date   datetime default '0000-00-00 00:00:00' not null comment '수정일',
//     reg_date   datetime default '0000-00-00 00:00:00' not null comment '등록일'
// );


// create table if not exists category
// (
//     idx           int auto_increment
//         primary key,
//     upcate        bigint      default 0                     not null comment '케인카테고리',
//     cateno        bigint      default 0                     not null comment '서브카테고리',
//     catenm        varchar(50) default ''                    not null comment '카테고리명',
//     cate_img      varchar(50) default ''                    not null comment '카테고리 이미지',
//     cate_bg       varchar(50) default ''                    not null comment '배경색',
//     cate_exposure int                                       null comment '노출순서',
//     reg_date      datetime    default '0000-00-00 00:00:00' not null comment '등록일',
//     isopen        int         default 1                     null comment '노출여부'
// );


// create table if not exists products
// (
//     idx         int auto_increment
//         primary key,
//     isopen      int          default 1                     null comment '상태(1:사용, 2:미사용)',
//     pd_ctno     bigint                                     null comment '카테고리번호',
//     pd_nm       varchar(255) default ''                    null comment '제품명',
//     pd_snm      varchar(255) default ''                    null comment '소제목',
//     pd_main_txt varchar(255) default ''                    null comment '메인 소개 텍스트',
//     pd_img1     varchar(255) default ''                    null comment '이미지1',
//     pd_img2     varchar(255) default ''                    null comment '이미지2',
//     pd_img3     varchar(255) default ''                    null comment '이미지3',
//     pd_img4     varchar(255) default ''                    null comment '이미지4',
//     pd_img5     varchar(255) default ''                    null comment '이미지5',
//     pd_tag      varchar(255) default ''                    null comment '해시태그',
//     pd_qs       int          default 0                     null comment '문의기능(1:사용, 0:미사용)',
//     pd_file     varchar(255) default ''                    null comment '첨부파일1',
//     pd_file1    varchar(255) default ''                    null comment '첨부파일2',
//     pd_text     longtext                                   null comment '상세설명',
//     pd_ipttext  longtext                                   null comment '주요정보',
//     pd_subtxt   longtext                                   null comment '추가설명',
//     pd_ctist    longtext                                   null comment '특성',
//     pd_grp_no   int          default 0                     null,
//     pd_exposure int          default 0                     null comment '노출순서',
//     pd_opt      varchar(50)  default ''                    null comment '모델 시리즈',
//     pd_own      int          default 1                     null comment '보유장비(1:보유, 2:미보유)',
//     pd_main     int          default 0                     null comment '메인노출 순서',
//     pd_new      int          default 2                     null comment 'new 버튼 노출 (1노출 / 2비노출',
//     pd_mddate   datetime     default '0000-00-00 00:00:00' null comment '수정일',
//     reg_date    datetime     default '0000-00-00 00:00:00' null comment '등록일'
// );


if($w == ""){
	if($depth1) $upcate = $depth1;
	if($depth2) $upcate = $depth2;
	if($depth3) $upcate = $depth3;
	if($depth4) $upcate = $depth4;

	// 카테고리 코드 새로 생성
	$sql_fld = " MAX(cateno) as max_caid ";

	$ca = sql_fetch("select {$sql_fld} from category where upcate = '$upcate' ");
	$max_caid = $ca['max_caid'] + 1;

	if(strlen($max_caid)%3 == 1) {
		$new_code = '10'.$max_caid;
	} else if(strlen($max_caid)%3 == 2) {
		$new_code = '1'.$max_caid;
	} else {
		$new_code = $max_caid;
	}

	$new_code = substr($new_code,-3);
	$new_code = $upcate.$new_code;
	
	$overlap = sql_fetch(" SELECT COUNT(*) AS cnt FROM category WHERE catenm = '{$catenm}' AND upcate = '{$upcate}' ");
	$ovcnt = $overlap['cnt'];

	if($ovcnt > 0){
		echo $uri;
		echo "<script>alert('[".$catenm."] 이미 사용중인 카테고리명 입니다.'); window.location.href='".$uri."'; </script>";
		exit;
	} else {
		$sql_set = "
			upcate		= '{$upcate}'
			, cateno	= '{$new_code}'
			, catenm	= '{$catenm}'
			, isopen	= '{$isopen}'
			, reg_date	= NOW()
		";

		$sql = "INSERT INTO category SET {$sql_set} ";
		sql_query($sql);

		echo "<script>alert('".$catenm." 등록 되었습니다.');  window.location.href='".$uri."';</script>";
	}
}

?>