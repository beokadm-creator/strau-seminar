<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

$depth1 = 6;

if ($board['bo_use_category']) {
    $category_location = "$bo_table?sca=";
}

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
include_once(G5_THEME_PATH.'/head.php');
?>


<!-- 게시판 목록 시작 { -->
<div class="subpage board pbt100" id="bo_gall">
    <div class="inner_container">
	 	<div class="inWrap plr60">
			<h3 class="tit fs_42 fw_700"><?php echo $board['bo_subject'] ?></h3>
			<p class="tit_info mb65">신제품 정보를 확인하실 수 있습니다.</p>
	 		<div class="dfbox category_wrap mb60">
<!--                 <div class="board_select_box category_year">
                    <select name="syear" id="syear" onchange="location='<?php echo $category_location.$sca ?>&syear='+this.value;" class="frm_input">
                        <option value=''>연도별 분류</option>
                        <option value="2024" <?=$syear == '2024' ? 'selected':''?>>2024</option>
                        <option value="2023" <?=$syear == '2023' ? 'selected':''?>>2023</option>
                    </select>
                </div> --> <!-- 2024-04-25 jh_주석 -->
				<!-- 게시판 카테고리 시작 { -->
				<?php if ($is_category) { ?>
				<nav id="bo_cate">
					<h2><?php echo $board['bo_subject'] ?> 카테고리</h2>
					<ul id="bo_cate_ul">
						<?php echo $category2_option ?>
					</ul>
				</nav>
				<?php } ?>
				<!-- } 게시판 카테고리 끝 -->
			</div>

            <form name="fboardlist"  id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
                <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
                <input type="hidden" name="syear" value="<?php echo $syear ?>">
                <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
                <input type="hidden" name="stx" value="<?php echo $stx ?>">
                <input type="hidden" name="spt" value="<?php echo $spt ?>">
                <input type="hidden" name="sst" value="<?php echo $sst ?>">
                <input type="hidden" name="sod" value="<?php echo $sod ?>">
                <input type="hidden" name="page" value="<?php echo $page ?>">
                <input type="hidden" name="sw" value="">


                    
                <!-- 게시판 페이지 정보 및 버튼 시작 { -->
                <div id="bo_btn_top">
                    <div id="bo_list_total">
                    <!--
                        <span>Total <?php echo number_format($total_count) ?>건</span>
                        <?php echo $page ?> 페이지
                        -->
                    </div>
                    <!-- 
                    <?php if ($rss_href || $write_href) { ?>
                    <ul class="btn_bo_user">
                        <?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn" title="관리자"><i class="fa fa-cog fa-spin fa-fw"></i><span class="sound_only">관리자</span></a></li><?php } ?>
                        <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01 btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
                        <li>
                            <button type="button" class="btn_bo_sch btn_b01 btn" title="게시판 검색"><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">게시판 검색</span></button>
                        </li>
                        <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b01 btn" title="글쓰기"><i class="fa fa-pencil" aria-hidden="true"></i><span class="sound_only">글쓰기</span></a></li><?php } ?>
                        <?php if ($is_admin == 'super' || $is_auth) {  ?>
                        <li>
                            <button type="button" class="btn_more_opt is_list_btn btn_b01 btn" title="게시판 리스트 옵션"><i class="fa fa-ellipsis-v" aria-hidden="true"></i><span class="sound_only">게시판 리스트 옵션</span></button>
                            <?php if ($is_checkbox) { ?>	
                            <ul class="more_opt is_list_btn">  
                                <li><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value"><i class="fa fa-trash-o" aria-hidden="true"></i> 선택삭제</button></li>
                                <li><button type="submit" name="btn_submit" value="선택복사" onclick="document.pressed=this.value"><i class="fa fa-files-o" aria-hidden="true"></i> 선택복사</button></li>
                                <li><button type="submit" name="btn_submit" value="선택이동" onclick="document.pressed=this.value"><i class="fa fa-arrows" aria-hidden="true"></i> 선택이동</button></li>
                            </ul>
                            <?php } ?>
                        </li>
                        <?php }  ?>
                    </ul>
                    <?php } ?>
                    -->
                </div>
                <!-- } 게시판 페이지 정보 및 버튼 끝 -->

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
                        $lec_status = $common_util->start_day_check($list[$i]['wr_3']) ? 'ing':'wait';
                        $lec_use = "";
                        $sql_lec = "select count(no) as cnt from g5_content_mypage where content_no = '{$list[$i]["wr_id"]}' and user_no = '{$member["mb_no"]}' order by no ";
                        $row_lec = sql_fetch($sql_lec);
                        if ($row_lec['cnt'] == 0) {
                            $lec_use = "N";
                        }

                        $classes = array();
                        
                        $classes[] = 'gall_li';
                        $classes[] = 'col-gn-'.$bo_gallery_cols;

                        if ( $i && ($i % $bo_gallery_cols == 0) ) {
                            $classes[] = 'box_clear';
                        }

                        if ( $wr_id && $wr_id == $list[$i]['wr_id'] ) {
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
                            <!-- [OSJ : 2024-11-11] 숨김처리 흐릿하게 표시 -->
                            <div class="gall_con" <?=$list[$i]['wr_9'] == 1 ? 'style="opacity:.3"':''?>>
                                <div class="gall_img">
                                    <a href="javascript:fnLectureMove(<?php echo $list[$i]['wr_id'] ?>, '<?php echo $list[$i]['wr_3'] ?>',`<?php echo $list[$i]['href'] ?>`, '<?=$lec_status?>', '<?=$lec_use?>')">
                                    <?php
                                    if ($list[$i]['is_notice']) { // 공지사항
                                        $thumb = get_list_thumbnail($board['bo_table'], $list[$i]['wr_id'], $board['bo_gallery_width'], $board['bo_gallery_height'], false, true);

                                        if ($thumb['src']) {
                                            $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" >';
                                        } else {
                                            $img_content = '<span class="no_image">no image</span>';
                                        }

                                        echo run_replace('thumb_image_tag', $img_content, $thumb);
                                    } else {
                                        $thumb = get_list_thumbnail($board['bo_table'], $list[$i]['wr_id'], $board['bo_gallery_width'], $board['bo_gallery_height'], false, true);

                                        if ($thumb['src']) {
                                            $img_content = '<img src="'.$thumb['src'].'" alt="'.$thumb['alt'].'" >';
                                        } else {
                                            $img_content = '<span class="no_image">no image</span>';
                                        }

                                        echo run_replace('thumb_image_tag', $img_content, $thumb);
                                    }
                                    ?>
                                    </a>
                                </div>
                                <div class="gall_text_href">
                                    <a href="javascript:fnLectureMove(<?php echo $list[$i]['wr_id'] ?>, '<?php echo $list[$i]['wr_3'] ?>',`<?php echo $list[$i]['href'] ?>`, '<?=$lec_status?>', '<?=$lec_use?>')" class="bo_tit">
                                        
                                        <?php // echo $list[$i]['icon_reply']; ?>
                                        <!-- 갤러리 댓글기능 사용시 주석을 제거하세요. -->
                                    
                                        <?php echo $list[$i]['subject'] ?>                      
                                        <?php
                                        // if ($list[$i]['file']['count']) { echo '<'.$list[$i]['file']['count'].'>'; }
                                        if ($list[$i]['icon_new']) echo "<span class=\"new_icon\">N<span class=\"sound_only\">새글</span></span>";
                                        if (isset($list[$i]['icon_hot'])) echo rtrim($list[$i]['icon_hot']);
                                        //if (isset($list[$i]['icon_file'])) echo rtrim($list[$i]['icon_file']);
                                        //if (isset($list[$i]['icon_link'])) echo rtrim($list[$i]['icon_link']);
                                        if (isset($list[$i]['icon_secret'])) echo rtrim($list[$i]['icon_secret']);
                                        ?>
                                    </a>
                                                            <div class="dfbox">
                                                                <p class="title"><span class="content"> <?php echo $list[$i]['wr_1'] ?></span></p>
                                                                <p class="date"><span class="content"> <?php echo $list[$i]['wr_2'] ?></span></p>
                                                            </div>
                                    <!-- <span class="bo_cnt"><?php echo utf8_strcut(strip_tags($list[$i]['wr_content']), 72, '...'); ?></span> -->
                                </div>
                                <!--
                                <div class="gall_info">
                                    <span class="sound_only">작성자 </span><?php echo $list[$i]['name'] ?>
                                    <span class="gall_date"><span class="sound_only">작성일 </span><i class="fa fa-clock-o" aria-hidden="true"></i> <?php echo $list[$i]['datetime2'] ?></span>
                                    <span class="gall_view"><span class="sound_only">조회 </span><i class="fa fa-eye" aria-hidden="true"></i> <?php echo $list[$i]['wr_hit'] ?></span>
                                </div>
                                <div class="gall_option">
                                    <?php if ($is_good) { ?><span class="sound_only">추천</span><strong><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> <?php echo $list[$i]['wr_good'] ?></strong><?php } ?>
                                    <?php if ($is_nogood) { ?><span class="sound_only">비추천</span><strong><i class="fa fa-thumbs-o-down" aria-hidden="true"></i> <?php echo $list[$i]['wr_nogood'] ?></strong><?php } ?>           
                                </div>
                                    -->
                            </div>
                        </div>
                    </li>
                    <?php } ?>
                    <?php if (count($list) == 0) { echo "<li class=\"empty_list\">스트라우만덴탈의 최신 온라인 이벤트를 이 곳에서 가장 먼저 확인 하실 수 있습니다. <br>
다가올 이벤트에 많은 관심 부탁드리겠습니다.</li>"; } ?>
                </ul>
                
                
                <?php if ($list_href || $is_checkbox || $write_href) { ?>
                <!-- <div class="bo_fx">
                    <?php if ($list_href || $write_href) { ?>
                    <ul class="btn_bo_user">
                        <?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn" title="관리자"><i class="fa fa-cog fa-spin fa-fw"></i><span class="sound_only">관리자</span></a></li><?php } ?>
                        <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01 btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
                        <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b01 btn" title="글쓰기"><i class="fa fa-pencil" aria-hidden="true"></i><span class="sound_only">글쓰기</span></a></li><?php } ?>
                    </ul>	
                    <?php } ?>
                </div> -->
                <ul class="btn_bo_user">
                    <!-- <?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn" title="관리자"><i class="fa fa-cog fa-spin fa-fw"></i>관리자<span class="sound_only">관리자</span></a></li><?php } ?> -->
                    <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01 btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
                    <!-- <li>
                    <button type="button" class="btn_bo_sch btn_b01 btn" title="게시판 검색"><i class="fa fa-search" aria-hidden="true"></i>검색<span class="sound_only">게시판 검색</span></button>
                    </li> -->
                    <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b01 btn btn_green" title="글쓰기">글쓰기</a></li><?php } ?>
                    <?php if ($is_admin == 'super' || $is_auth) {  ?>
                    <li><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value" class="btn btn_green">선택삭제</button></li>
                    <!--
                        <li class="list_option">
                        <button type="button" class="btn_more_opt is_list_btn btn_b01 btn" title="게시판 리스트 옵션"><i class="fa fa-ellipsis-v" aria-hidden="true"></i><span class="sound_only">게시판 리스트 옵션</span></button>
                        <?php if ($is_checkbox) { ?>	
                        <ul class="more_opt is_list_btn">  
                            <li><button type="submit" name="btn_submit" value="선택삭제" onclick="document.pressed=this.value"><i class="fas fa-trash-alt" aria-hidden="true"></i> 선택삭제</button></li>
                            <li><button type="submit" name="btn_submit" value="선택복사" onclick="document.pressed=this.value"><i class="fa fa-files-o" aria-hidden="true"></i> 선택복사</button></li>
                            <li><button type="submit" name="btn_submit" value="선택이동" onclick="document.pressed=this.value"><i class="fas fa-arrows-alt" aria-hidden="true"></i> 선택이동</button></li>
                        </ul>
                        <?php } ?>
                        </li>
                        -->
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

<?php if ($is_checkbox) { ?>
<noscript>
<p>자바스크립트를 사용하지 않는 경우<br>별도의 확인 절차 없이 바로 선택삭제 처리하므로 주의하시기 바랍니다.</p>
</noscript>
<?php } ?>
<script>
    
/* [OSJ : 2024-03-14] 사전 수강신청 + 일반수강신청 분기문 */
function fnLectureMove(wr_id, wr_date, href, lec_status, lec_use) {
    <? if ($is_admin) { ?>
        location.href=href;
    <? } else { ?>
        // 현재 진행주중인 강의
        if (lec_status == "ing") {
            if (lec_use == "N") {
                fnLectureApplyBtn(wr_id, wr_date, href);
            } else{
                location.href=href;
            }
        }
        // 오픈하지 않은 강의
        if (lec_status == "wait") {
            fnPreApplyBtn(wr_id, wr_date);
        }
    <? } ?>
}

/* [OSJ : 2024-03-14] 수강신청 알럿 + 페이지 이동 */
function fnLectureApplyBtn(wr_id, wr_date, href) {
    <? if ($member['mb_level'] < 3) { ?>
		alert("승인된 회원만 이용 가능합니다");
		return false;
	<? } ?>
    
    let url = new URL(href);
    let searchParams = url.searchParams;

    // if (confirm("수강 신청하시겠습니까?")) {
        searchParams.append("lec", "1");
        location.href=url.toString();
    // }
}

/* [OSJ : 2024-03-14] 사전 수강신청 처리 */
function fnPreApplyBtn(wr_id, wr_date) {
    <? if ($member['mb_level'] < 3) { ?>
		alert("승인된 회원만 이용 가능합니다");
		return false;
	<? } ?>
    if (confirm("해당 강의는 " + wr_date + "에 오픈될 예정입니다.\r사전 예약 하시겠습니까?")) {
        $.ajax({
            url: "/bbs/ajax.pre_apply.php",
            type: "POST",
            dataType: "json",
            data: {
                type: "pre_apply",
                mypage_type: "launchingShow",
                no: wr_id
            },
            success:function(data) {
                console.log(data);
                if (data.error) {
                    alert(data.error);
                } else if (data.result == "already") {
                    alert("사전신청된 강의 입니다.");
                } else if (data.result == "success") {
                    alert("신청완료 되었습니다.");
                }
            },
            error:function() {
                console.log("error");
            },
            complete: function() {
                console.log("complete");
            }
        });
        
    }
}
</script>
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

    if (document.pressed == "선택복사") {
        select_copy("copy");
        return;
    }

    if (document.pressed == "선택이동") {
        select_copy("move");
        return;
    }

    if (document.pressed == "선택삭제") {
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
jQuery(function($) {
    $(".btn_more_opt.is_list_btn").on("click", function(e) {
        e.stopPropagation();
        $(".more_opt.is_list_btn").toggle();
    });
    $(document).on("click", function (e) {
        if (!$(e.target).closest('.is_list_btn').length) {
            $(".more_opt.is_list_btn").hide();
        }
    });
});
</script>
<?php } ?>
<!-- } 게시판 목록 끝 -->

<?php
include_once(G5_THEME_PATH.'/tail.php'); ?>
