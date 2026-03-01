<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

include_once(G5_LIB_PATH.'/ray_util.lib.php');
$common_util = new Ray_Util();

/* [YSH : 2025.03.12] swiper 가 두개 이상 들어가는 경우 class 충돌로 인한 css 깨짐 방지 */
$swiperCls = isset($multi_swiper) && $multi_swiper == 'Y' ? $bo_tables : 'latest-swiper';

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);
$list_count = (is_array($list) && $list) ? count($list) : 0;
?>

<div class="swiper-container campusSwiper <?php echo $swiperCls; ?>">
    <div class="swiper-wrapper">
    <?php 
	for ($i=0; $i<$list_count; $i++) { 
		if ($common_util->start_day_check($list[$i]['wr_3']) == false) {
			$list[$i]['href'] = "javascript:fnPreApplyBtn(`{$list[$i]['wr_3']}`);";
		}
	?>
		<div class="swiper-slide">
			<?php
			echo "<a href=\"{$list[$i]['href']}\">";
				echo "<div class=\"tit_box\">";
				if ($list[$i]['is_notice']) {
					echo "<h2><strong>{$list[$i]['subject']}</strong></h2>";
				} else {
					echo "<h2>{$list[$i]['subject']}</h2>";
				}
					echo "<div class=\"dfbox\">"; 
						echo "<p>{$list[$i]['wr_1']}</p>"; 
						echo "<p>{$list[$i]['wr_2']}</p>"; 
					echo "</div>";
				echo "</div>";
				echo "<div class=\"progress_box\">";
					echo "<p>현재 수강 진행률<span>{$list[$i]['percent']}%</span></p>";
				echo "</div>";
			echo "</a>";
         ?>
        </div>
	<?php }  ?>
    <?php if ($list_count == 0) { //게시물이 없을 때  ?>
    	<p class="empty_li">게시물이 없습니다.</p>
    <?php }  ?>
    </div>
</div>
<div class="paging_box dfbox">
	<div class="swiper-button-prev <?php echo $swiperCls; ?>"></div>
	<div class="swiper-pagination <?php echo $swiperCls; ?>"></div>
	<div class="swiper-button-next <?php echo $swiperCls; ?>"></div>
</div>


<script>
$(document).ready(function() {
	var campusSwiper = new Swiper(".campusSwiper.<?php echo $swiperCls; ?>", {
		slidesPerView: 1,
		slidesPerGroup: 3,
		grid: {
			rows: 2,
			fill: "row",
		},
		spaceBetween: 10,
		pagination: {
			el: ".swiper-pagination.<?php echo $swiperCls; ?>",
			clickable: true,
			renderBullet: function (index, className) {
				return '<span class="' + className + '">' + (index + 1) + "</span>";
			},
		},
		navigation: {
			nextEl: ".swiper-button-next.<?php echo $swiperCls; ?>",
			prevEl: ".swiper-button-prev.<?php echo $swiperCls; ?>",
		},
		breakpoints: {
			480: {
				spaceBetween: 15,
				slidesPerView: 2,
				grid: {
					rows: 2,
					fill: "row",
				}
			},
			768: {
				spaceBetween: 20,
				slidesPerView: 3,
				grid: {
					rows: 2,
					fill: "row",
				}
			}
		}
	});
});

/* [OSJ : 2024-03-14] 사전 수강신청 처리 */
function fnPreApplyBtn(wr_date){
	alert("해당 강의는 " + wr_date + "에 오픈될 예정입니다.");
}
</script>
