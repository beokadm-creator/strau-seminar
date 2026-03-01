<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$latest_skin_url.'/style.css">', 0);
$thumb_width = 225;
$thumb_height = 152;
$list_count = (is_array($list) && $list) ? count($list) : 0;
?>


<div class="swiper row4 product">
    
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
        <div class="swiper-slide">
		    <div class="img_box">
                 <a href="<?php echo $list[$i]['href'] ?>" class="lt_img"><?php echo run_replace('thumb_image_tag', $img_content, $thumb); ?></a>
			</div>

            <?php
            if ($list[$i]['icon_secret']) echo "<i class=\"fa fa-lock\" aria-hidden=\"true\"></i><span class=\"sound_only\">비밀글</span> ";

  			echo "<div class='cont'>";
			echo "<a class='title' href=\"".$list[$i]['href']."\"> ";
            if ($list[$i]['is_notice'])
                echo $list[$i]['subject'];
            else
                echo $list[$i]['subject'];
            echo "</a>";
			echo "</div>";

            ?>

        </div>
    <?php }  ?>
    <?php if ($list_count == 0) { //게시물이 없을 때  ?>
    <div class="empty_li">게시물이 없습니다.</div>
    <?php }  ?>
    </div>
</div>

<div class="swiper-button-prev"></div>
<div class="swiper-pagination"></div>
<div class="swiper-button-next"></div>

<script>
$(document).ready(function() {
  var fourSwiper = new Swiper(".product", {
    slidesPerView: 2,
		slidesPerGroup: 2,
		spaceBetween: 10,
    pagination: {
			el: ".swiper-pagination",
			clickable: true,
		},
		navigation: {
			nextEl: ".swiper-button-next",
			prevEl: ".swiper-button-prev",
		},
		breakpoints: {
			768: {
				slidesPerView: 3,
				slidesPerGroup: 3,
				spaceBetween: 10,
			},
			1025: {
				slidesPerView: 4,
				slidesPerGroup: 4,
				spaceBetween: 20,
			},
		},
  });
});
</script>


