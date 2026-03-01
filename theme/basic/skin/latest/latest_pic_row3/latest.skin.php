<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);
/* [OSJ : 2024-05-03] 썸네일 이미지 넓이 / 높이 설정 */
$thumb_width = 225;
$thumb_height = 225;
$list_count = (is_array($list) && $list) ? count($list) : 0;
?>


<div class="swiper row3">
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
		?>
		<div class="swiper-slide h210">
			<div class="img_box">
				<a href="<?php echo $list[$i]['href'] ?>" class="lt_img"><?php echo run_replace('thumb_image_tag', $img_content, $thumb); ?></a>
			</div>

			<?php
				if ($list[$i]['icon_secret']) echo "<i class=\"fa fa-lock\" aria-hidden=\"true\"></i><span class=\"sound_only\">비밀글</span> ";
					/*echo "<div class='cont'>";
					echo "<a class='title fs_20 fw_600' href=\"".$list[$i]['href']."\"> ";
				if ($list[$i]['is_notice'])
					echo $list[$i]['subject'];
				else
					echo $list[$i]['subject'];
					echo "</a>";*/
				if ($list[$i]['bo_table'] == 'campus')
					echo "<a href='javascript:void(0);' class='appli_btn'>신청하기</a>";
					//echo "</div>";
				?>
		</div>
		<?php }  ?>
		<?php if ($list_count == 0) { //게시물이 없을 때  ?>
		<div class="empty_li">게시물이 없습니다.</div>
		<?php }  ?>
	</div>
</div>
<div class="swiper-video-pagination"></div>
<div class="swiper-video-button-prev"></div>
<div class="swiper-video-button-next"></div>



<script>
$(document).ready(function() {
  var threeSwiper = new Swiper(".row3", {
    slidesPerView: 1,
		slidesPerGroup: 1,
		spaceBetween: 10,
		loop: true,
		autoplay: {
			delay: 4000,
			disableOnInteraction: false,
		},
		pagination: {
			el: ".swiper-video-pagination",
			clickable: true,
		},
		navigation: {
			nextEl: ".swiper-video-button-next",
			prevEl: ".swiper-video-button-prev",
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


