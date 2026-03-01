<?php

$g5['title'] = "제품정보";
$g5['title2'] = "pro";
$depth1 = 2;

include_once(G5_THEME_PATH.'/head.php');
//add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/pro.css">', 0);

add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/magpop/magnific-popup.css">', 10);
add_javascript('<script src="'.G5_JS_URL.'/magpop/jquery.magnific-popup.js"></script>', 10);

?>

<!--컨텐츠시작-->

<?php echo $row['pd_nm'];
$sql = " SELECT * FROM products WHERE (1) AND idx = '{$idx}' ";
$row = sql_fetch($sql);


$cate0 = sql_fetch(" SELECT * FROM category WHERE pd_ctno = '{$_GET['pd_ctno']}' ");

if(strlen($row['pd_ctno']) == 3){
	$cateno1 = sql_fetch(" SELECT * FROM category WHERE cateno = '{$row['pd_ctno']}' ");
	$cateno2 = '';
	$cateno3 = '';
	$cateno4 = '';
} else if(strlen($row['pd_ctno']) == 6){
	$cateno1 = sql_fetch(" SELECT * FROM category WHERE cateno = ".substr($row['pd_ctno'],0,3)." AND cateno LIKE '{$cate0['pd_ctno']}%' ORDER BY idx ");
	$cateno2 = sql_fetch(" SELECT * FROM category WHERE cateno = '{$row['pd_ctno']}' ");
	$cateno3 = '';
	$cateno4 = '';
} else if(strlen($row['pd_ctno']) == 9){
	$cateno1 = sql_fetch(" SELECT * FROM category WHERE cateno = ".substr($row['pd_ctno'],0,3)." AND cateno LIKE '{$cate0['pd_ctno']}%' ORDER BY idx ");
	$cateno2 = sql_fetch(" SELECT * FROM category WHERE cateno = ".substr($row['pd_ctno'],0,6)." AND cateno LIKE '{$cate0['pd_ctno']}%' ORDER BY idx ");
	$cateno3 = sql_fetch(" SELECT * FROM category WHERE cateno = '{$row['pd_ctno']}' ");
	$cateno4 = '';
} else{
	$cateno1 = sql_fetch(" SELECT * FROM category WHERE cateno = ".substr($row['pd_ctno'],0,3)." AND cateno LIKE '{$cate0['pd_ctno']}%' ORDER BY idx ");
	$cateno2 = sql_fetch(" SELECT * FROM category WHERE cateno = ".substr($row['pd_ctno'],0,6)." AND cateno LIKE '{$cate0['pd_ctno']}%' ORDER BY idx ");
	$cateno3 = sql_fetch(" SELECT * FROM category WHERE cateno = ".substr($row['pd_ctno'],0,9)." AND cateno LIKE '{$cate0['pd_ctno']}%' ORDER BY idx");
	$cateno4 = sql_fetch(" SELECT * FROM category WHERE cateno = '{$row['pd_ctno']}' ");
}


$cate1 = $cateno1['catenm'];
$cate2 = $cateno2['catenm'];
$cate3 = $cateno3['catenm'];
$cate4 = $cateno4['catenm'];
?>
<div class="<? echo $g5['title2']?>_wrap pbt100">
	<div class="product view content">
		<div class="inner_container plr60">
			<div class="common_wrap mb65">
				<h2 class="tit fs_42 fw_700"><? echo $g5['title'];?></h2>
				<ul class="dfbox">
					<li><?php echo $cate1; ?></li>
					<?php if($cate2) { ?>
						<li>&nbsp;&nbsp;>&nbsp;&nbsp;</li>
						<li><?php echo $cate2; ?></li>
					<?php } ?>
					<?php if($cate3) { ?>
						<li>&nbsp;&nbsp;>&nbsp;&nbsp;</li>
						<li><?php echo $cate3; ?></li>
					<?php } ?>
					<?php if($cate4) { ?>
						<li>&nbsp;&nbsp;>&nbsp;&nbsp;</li>
						<li><?php echo $cate4; ?></li>
					<?php } ?>
				</ul>
			</div>
		</div>
	</div>
	<div class="product_detail_info pbt60">
		<div class="inner_container">
			<h3 class="title fs_36 fw_500"><?php echo $row['pd_nm'];?></h3>
			<?php if($row['pd_ctist']) { ?>
			<div class="video_container mb50"><iframe src="<?php echo strip_tags($row['pd_ctist']); ?>"  frameborder="0" allowfullscreen  allow="autoplay;"></iframe></div>
			<?php } ?>
			<div class="dfbox">
				<div class="product_gallery">
					<!-- 제품 이미지 슬라이더 -->
					<div  class="swiper productSlider">
						<div class="swiper-wrapper gall_img">
							<?php 
								$res = sql_query($sql);
								$bn = sql_fetch_array($res);
								for($i=1; $i<6; $i++ ){
									if($bn['pd_img'.$i]){
										echo "<div class=\"swiper-slide\"><a href=\"".G5_DATA_URL."/products/".$bn['pd_img'.$i]."\"><img src=\"".G5_DATA_URL."/products/".$bn['pd_img'.$i]."\" /></a></div>";
									}
								}
							?>
						</div>
					</div>
					<!-- 제품 이미지 슬라이더 썸네일-->
					<div class="swiper productSliderThumb">
						<div class="swiper-wrapper">
							<?php 
								$res = sql_query($sql);
								$sbn = sql_fetch_array($res);
								for($i=1; $i<6; $i++ ){
									if($sbn['pd_img'.$i]){
										echo "<div class=\"swiper-slide\"><img src=\"".G5_DATA_URL."/products/".$sbn['pd_img'.$i]."\" /></div>";
									}
								}
							?>
						</div>
					</div>
				</div>

				<div class="product_info">
					<? if($row['pd_nm'] != "") { ?>
					<div>
						<!-- 제품명 -->
						<span class ="info_small_title">제품명 : <span><?=$row['pd_nm']?>
					</div>
					<br>
					<? } ?>
					<? if($row['pd_brand_text'] != "") { ?>
						<div>
							<!-- 브랜드 -->
							<span class ="info_small_title">브랜드 : <span><?=nl2br($row['pd_brand_text'])?>
						</div>
						<br>
					<? } ?>
					<? if($row['pd_text'] != "") { ?>
					<div class="product_txt">
						<!-- 제품정보 -->
						<span class ="info_small_title">제품정보 : <span>
						<?=nl2br($row['pd_text'])?>
					</div>
					<? } ?>

					<!-- [OSJ : 2024-05-08] 첨부파일 표시 -->
					<? 
					for($i=1; $i<=4; $i++){ 
						if($row['pd_file'.$i]){ 
					?>
						<div class="product_down">
							<div class="filebox bs3-primary">
								<a class="btn_frmline" href="<?php echo G5_DATA_URL."/products/".$row['pd_file'.$i]?>" download="<?=$row['pd_file'.$i.'_name']?>"><?=$row['pd_file'.$i.'_name']?></a>
							</div>
						</div>
					<? 
						}
					} 
					?>
					<? if($row['pd_demo']) { ?>
					<div class="product_txt">
						<div class="product_down">
							<div class="filebox bs3-primary">
								<a class="btn_frmline" href="<?=$row['pd_demo']?>">무료데모신청</a>
							</div>
						</div>
					</div>
					<? } ?>
				</div>
			</div>
		</div>
	</div>
</div>


<script>
$(document).ready(function(){
	var swiperThumb = new Swiper(".productSliderThumb", {
		loop: false,
		spaceBetween: 10,
		slidesPerView: 5,
		freeMode: true,
		watchSlidesProgress: true,
		slideToClickedSlide: true,
	});

	var swiperSlide = new Swiper(".productSlider", {
		loop: false,
		slidesPerView: 1,
		spaceBetween: 10,
		navigation: {
			nextEl: ".swiper-button-next",
			prevEl: ".swiper-button-prev",
		},
		thumbs: {
			swiper: swiperThumb,	
		},
	});
	swiperSlide.controller.control = swiperThumb;
	//swiperThumb.controller.control = swiperSlide;

	$('.gall_img').magnificPopup({
		delegate: 'a',
		type: 'image',
		tLoading: 'Loading image #%curr%...',
		mainClass: 'mfp-img-mobile',
		gallery: {
			enabled: true,
			navigateByImgClick: true,
			preload: [0,1] 
		},
		image: {
			tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
		}
	});
});
</script>


<?php
include_once(G5_THEME_PATH.'/tail.php');
?>