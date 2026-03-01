<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

$depth1 = 4;

if ($board['bo_use_category']) {
    $category_location = "$bo_table?sca=";
}

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
include_once(G5_THEME_PATH.'/head.php');
?>
<style>
	.inner_container {width: 95%; max-width: 1000px; margin: 0 auto; position: relative;}
	.subpage.board#bo_gall ul#gall_ul {display: flex; gap: 60px;}
</style>
<!-- 게시판 목록 시작 { -->
<div class="subpage board pbt100" id="bo_gall">
   <div class="inner_container">
	 	<div class="inWrap">
			<h3 class="tit fs_42 fw_700">스트라우만® <?php echo $board['bo_subject'] ?></h3>
      <p class="info fs_18 mb65">스트라우만은 전 세계 최고의 병원, 연구 기관, 대학 등과 협력하여<br>치과용 임플란트 및 구강 조직 재생 분야의 연구에 전념하고 있습니다.<br>과학과 품질에 대한 스트라우만의 약속은 다양한 임상 데이터를 통해 확인 하실 수 있습니다.</p>  

    <form name="fboardlist"  id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sw" value="">



    <?php if ($is_checkbox) { ?>
    <div id="gall_allchk" class="all_chk chk_box">
        <input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);" class="selec_chk">
    	<label for="chkall">
        	<span></span>
        	<b class="sound_only">현재 페이지 게시물 </b> 전체선택
        </label>
    </div>
    <?php } ?>

    <ul id="gall_ul" class="gall_row">
        <?php for ($i=0; $i<count($list); $i++) {

            $classes = array();
            
            $classes[] = 'gall_li';
            $classes[] = 'col-gn-'.$bo_gallery_cols;

            if( $i && ($i % $bo_gallery_cols == 0) ){
                //$classes[] = 'box_clear';
            }

            if( $wr_id && $wr_id == $list[$i]['wr_id'] ){
                $classes[] = 'gall_now';
            }
         ?>
        <li class="<?php echo implode(' ', $classes); ?>">
            <div class="gall_box">
                <div class="gall_chk chk_box">
	                <?php if ($is_checkbox) { ?>
					<input type="checkbox" name="chk_wr_id[]" value="<?php echo $list[$i]['wr_id'] ?>" id="chk_wr_id_<?php echo $i ?>" class="selec_chk">
	                <label for="chk_wr_id_<?php echo $i ?>">
	                	<span></span>
	                	<b class="sound_only"><?php echo $list[$i]['subject'] ?></b>
	                </label>
	                
	                <?php } ?>
	                <span class="sound_only">
	                    <?php
	                    if ($wr_id == $list[$i]['wr_id'])
	                        echo "<span class=\"bo_current\">열람중</span>";
	                    else
	                        echo $list[$i]['num'];
	                     ?>
	                </span>
                </div>
                <div class="gall_con">
                    <div class="gall_img">
                        <?php
                        if ($list[$i]['is_notice']) { // 공지사항
                            $thumb = get_list_thumbnail($board['bo_table'], $board['bo_gallery_width'], $board['bo_gallery_height'], false, true);

                            if($thumb['src']) {
                                $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" >';
                            } else {
                                $img_content = '<div class="no_image">'.$list[$i]['subject'].'</div>';
                            }

                            echo run_replace('thumb_image_tag', $img_content, $thumb);
                        } else {
                            $thumb = get_list_thumbnail($board['bo_table'], $list[$i]['wr_id'], $board['bo_gallery_width'], $board['bo_gallery_height'], false, true);

                            if($thumb['src']) {
                                $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" >';
                            } else {
                                $img_content = '<div class="no_image">'.$list[$i]['subject'].'</div>';
                            }

                            echo run_replace('thumb_image_tag', $img_content, $thumb);
                        }
                         ?>
                        <?php if ($is_admin){ ?><a href="<?php echo G5_BBS_URL ?>/write.php?w=u&bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $list[$i]['wr_id']; ?>" class="wu_btn">[수정하기]</a><?php } ?>
                    </div>
										
                    <div class="gall_text_href">
                        <?php echo $list[$i]['wr_content'] ?>                      
                    </div>
                </div>
								<div class="btn_box"><a href="<?php echo $list[$i]['wr_link1'] ?>" target="_blank"><?= $list[$i]['subject'] ?> 바로가기</a></div>
            </div>
        </li>
        <?php } ?>
        <?php if (count($list) == 0) { echo "<li class=\"empty_list\">게시물이 없습니다.</li>"; } ?>
    </ul>
	
	
     <?php if ($list_href || $is_checkbox || $write_href) { ?>
	 <ul class="btn_bo_user">
        <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01 btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
         <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b01 btn btn_green" title="글쓰기">글쓰기</a></li><?php } ?>
       	<?php if ($is_admin == 'super' || $is_auth) {  ?>
		<li><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value" class="btn btn_green">선택삭제</button></li>
       	<?php }  ?>
         </ul>
    <?php } ?>  
    </form>

	<!-- 페이지 -->
	<?php echo $write_pages; ?>
	<!-- 페이지 -->
	</div>
	</div>
</div>

<?php if($is_checkbox) { ?>
<noscript>
<p>자바스크립트를 사용하지 않는 경우<br>별도의 확인 절차 없이 바로 선택삭제 처리하므로 주의하시기 바랍니다.</p>
</noscript>
<?php } ?>

<?php if ($is_checkbox) { ?>
<script>
function all_checked(sw) {
    var f = document.fboardlist;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]")
            f.elements[i].checked = sw;
    }
}

function fboardlist_submit(f) {
    var chk_count = 0;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked)
            chk_count++;
    }

    if (!chk_count) {
        alert(document.pressed + "할 게시물을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택복사") {
        select_copy("copy");
        return;
    }

    if(document.pressed == "선택이동") {
        select_copy("move");
        return;
    }

    if(document.pressed == "선택삭제") {
        if (!confirm("선택한 게시물을 정말 삭제하시겠습니까?\n\n한번 삭제한 자료는 복구할 수 없습니다\n\n답변글이 있는 게시글을 선택하신 경우\n답변글도 선택하셔야 게시글이 삭제됩니다."))
            return false;

        f.removeAttribute("target");
        f.action = g5_bbs_url+"/board_list_update.php";
    }

    return true;
}

// 선택한 게시물 복사 및 이동
function select_copy(sw) {
    var f = document.fboardlist;

    if (sw == 'copy')
        str = "복사";
    else
        str = "이동";

    var sub_win = window.open("", "move", "left=50, top=50, width=500, height=550, scrollbars=1");

    f.sw.value = sw;
    f.target = "move";
    f.action = g5_bbs_url+"/move.php";
    f.submit();
}

// 게시판 리스트 관리자 옵션
jQuery(function($){
    $(".btn_more_opt.is_list_btn").on("click", function(e) {
        e.stopPropagation();
        $(".more_opt.is_list_btn").toggle();
    });
    $(document).on("click", function (e) {
        if(!$(e.target).closest('.is_list_btn').length) {
            $(".more_opt.is_list_btn").hide();
        }
    });
});
</script>
<?php } ?>
<!-- } 게시판 목록 끝 -->

<?php
include_once(G5_THEME_PATH.'/tail.php'); ?>
