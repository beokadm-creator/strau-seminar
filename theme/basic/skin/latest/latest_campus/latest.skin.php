<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');
include_once(G5_LIB_PATH.'/ray_util.lib.php');
$common_util = new Ray_Util();
global $member,$is_member;


// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);
$thumb_width = 225;
$thumb_height = 225;
$list_count = (is_array($list) && $list) ? count($list) : 0;

?>


<div class="swiper row_campus">
	<div class="swiper-wrapper">
		<?php
		for ($i=0; $i<$list_count; $i++) {
		$thumb = get_list_thumbnail($list[$i]['bo_table'], $list[$i]['wr_id'], $thumb_width, $thumb_height, false, true);

		if($thumb['src']) {
			$img = $thumb['src'];
		} else {
			$img = G5_IMG_URL.'/no_img.png';
			$thumb['alt'] = '이미지가 없습니다.';
		}
		$img_content = '<img src="'.$img.'" alt="'.$thumb['alt'].'" >';
		$lec_status = $common_util->start_day_check($list[$i]['wr_3']) ? 'ing':'wait';
		$lec_use = "";
		$sql_lec = "select count(no) as cnt from g5_content_mypage where content_no = '{$list[$i]["wr_id"]}' and user_no = '{$member["mb_no"]}' order by no ";
		$row_lec = sql_fetch($sql_lec);
		if($row_lec['cnt'] == 0){
			$lec_use = "N";
		}

		?>
		<div class="swiper-slide h210">
			<div class="img_box">
				<a href="javascript:fnLectureMove(<?php echo $list[$i]['wr_id'] ?>, '<?php echo $list[$i]['wr_3'] ?>',`<?php echo $list[$i]['href'] ?>`, '<?=$lec_status?>', '<?=$lec_use?>');" class="lt_img"><?php echo run_replace('thumb_image_tag', $img_content, $thumb); ?></a>
			</div>

			<?php
				if ($list[$i]['icon_secret']) echo "<i class=\"fa fa-lock\" aria-hidden=\"true\"></i><span class=\"sound_only\">비밀글</span> ";
					echo "<div class='cont' style='display: none;'>";
					?>
					<!-- <a class='title fs_20 fw_600' href="javascript:fnLectureMove(<?php echo $list[$i]['wr_id'] ?>, '<?php echo $list[$i]['wr_3'] ?>',`<?php echo $list[$i]['href'] ?>`, '<?=$lec_status?>', '<?=$lec_use?>');" > -->
					<?
					// echo "<a class='title' href=\"".$list[$i]['href']."\"> ";
						/*if ($list[$i]['is_notice']){
							echo $list[$i]['subject'];
						} else {
							echo $list[$i]['subject'];
						}*/
					//echo "</a>";
					/* [OSJ : 2024-03-14] 강의 대기중이라면 신청하기 버튼 표시 */
					if($lec_status == 'wait'){
						?>
						<a href="javascript:fnLectureMove(<?php echo $list[$i]['wr_id'] ?>, '<?php echo $list[$i]['wr_3'] ?>',`<?php echo $list[$i]['href'] ?>`, '<?=$lec_status?>', '<?=$lec_use?>');" class='appli_btn'>신청하기</a>
						<?
					}
					echo "</div>";
				?>
		</div>
		<?php }  ?>
		<?php if ($list_count == 0) { //게시물이 없을 때  ?>
		<div class="empty_li">게시물이 없습니다.</div>
		<?php }  ?>
	</div>
</div>
<div class="swiper-campus-pagination"></div>
<div class="swiper-campus-button-prev"></div>
<div class="swiper-campus-button-next"></div>



<script>
	
/* [OSJ : 2024-03-14] 사전 수강신청 + 일반수강신청 분기문 */
function fnLectureMove(wr_id, wr_date, href, lec_status, lec_use){

	<? if($is_member == false){ ?>
		location.href="/bbs/login";
		return false;
	<? } ?>
	<? if($member['mb_level'] < 3){ ?>
		alert("승인된 회원만 이용 가능합니다");
		return false;
	<? } ?>
    // 현재 진행주중인 강의
    if(lec_status == "ing"){
        if(lec_use == "N"){
            fnLectureApplyBtn(wr_id, wr_date, href);
        }else{
            location.href=href;
        }
    }
    // 오픈하지 않은 강의
    if(lec_status == "wait"){
        fnPreApplyBtn(wr_id, wr_date);
    }
}

/* [OSJ : 2024-03-14] 수강신청 알럿 + 페이지 이동 */
function fnLectureApplyBtn(wr_id, wr_date, href){
	<? if($is_member == false){ ?>
		location.href="/bbs/login";
		return false;
	<? } ?>
	<? if($member['mb_level'] < 3){ ?>
		alert("승인된 회원만 이용 가능합니다");
		return false;
	<? } ?>

    let url = new URL(href);
    let searchParams = url.searchParams;

    if(confirm("수강 신청하시겠습니까?")){
        searchParams.append("lec", "1");
        location.href=url.toString();
    }
}

/* [OSJ : 2024-03-14] 사전 수강신청 처리 */
function fnPreApplyBtn(wr_id, wr_date){
	<? if($is_member == false){ ?>
		location.href="/bbs/login";
		return false;
	<? } ?>
	<? if($member['mb_level'] < 3){ ?>
		alert("승인된 회원만 이용 가능합니다");
		return false;
	<? } ?>
	
    if(confirm("해당 강의는 " + wr_date + "에 오픈될 예정입니다.\r사전 예약 하시겠습니까?")){
        $.ajax({
            url: "/bbs/ajax.pre_apply.php",
            type: "POST",
            dataType: "json",
            data: {
                type: "pre_apply",
                no: wr_id,
            },
            success:function(data){
                console.log(data);
                if(data.error){
                    alert(data.error);
                }else if(data.result == "already"){
                    alert("사전신청된 강의 입니다.");
                }else if(data.result == "success"){
                    alert("신청완료 되었습니다.");
                }
            },
            error:function(){
                console.log("error");
            },
            complete: function(){
                console.log("complete");
            }
        });
        
    }
}

$(document).ready(function() {
  var threeSwiper = new Swiper(".row_campus", {
    slidesPerView: 1,
		slidesPerGroup: 1,
		spaceBetween: 10,
		loop: true,
		autoplay: {
			delay: 4000,
			disableOnInteraction: false,
		},
		pagination: {
			el: ".swiper-campus-pagination",
			clickable: true,
		},
		navigation: {
			nextEl: ".swiper-campus-button-next",
			prevEl: ".swiper-campus-button-prev",
		},
		breakpoints: {
			480: {
				slidesPerView: 2,
				slidesPerGroup: 1,
				spaceBetween: 10,
			},
			768: {
				slidesPerView: 3,
				slidesPerGroup: 1,
				spaceBetween: 10,
			},
			1025: {
				slidesPerView: 5,
				slidesPerGroup: 1,
				spaceBetween: 20,
			},
		},
  });
});
</script>


