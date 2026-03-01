<?php
if (!defined('_INDEX_')) define('_INDEX_', true);
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

require_once G5_LIB_PATH.'/ray_util.lib.php';
$common_util = new Ray_Util();

if (G5_IS_MOBILE) {
    include_once(G5_THEME_MOBILE_PATH.'/index.php');
    return;
}

if(G5_COMMUNITY_USE === false) {
    include_once(G5_THEME_SHOP_PATH.'/index.php');
    return;
}

include_once(G5_THEME_PATH.'/head.php');

/* [OSJ : 2024-08-06] 슬라이드의 수강여부 확인하여 이동처리. */
// fnLectureMove(118, '2024-07-11 09:00',`https://stkr-edu.com/campus/118`, 'ing', 'N');
// $common_util->get_main_slide_lecture(게시글번호);
$onclick_txt = $common_util->get_main_slide_lecture(118);

?>


<div class="main">
	<div class="banner_sect" style="display: none;">
		<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_banner.jpg" alt="img"></div>
		<div class="imgbox mo"><img src="<?= G5_THEME_IMG_URL; ?>/main/mo_main_banner.jpg" alt="img"></div>
	</div>
	<div class="banner_sect swiper">
		<div class="swiper-wrapper">
			<!-- <div class="swiper-slide imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_banner2.jpg" alt="img" onClick="location.href='<?= G5_URL; ?>/campus/118'"></div> -->
			<!-- <div class="swiper-slide imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_banner2.jpg" alt="img" onClick="<?=$onclick_txt?>"></div> -->
			<div class="swiper-slide imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_banner.jpg" alt="img"></div>
		</div>
	</div>
	<div class="banner_mo_sect swiper">
		<div class="swiper-wrapper">
			<!-- <div class="swiper-slide imgbox mo"><img src="<?= G5_THEME_IMG_URL; ?>/main/mo_main_banner2.jpg" alt="img" onClick="location.href='<?= G5_URL; ?>/campus/118'"></div> -->
			<!-- <div class="swiper-slide imgbox mo"><img src="<?= G5_THEME_IMG_URL; ?>/main/mo_main_banner2.jpg" alt="img" onClick="<?=$onclick_txt?>"></div> -->
			<div class="swiper-slide imgbox mo"><img src="<?= G5_THEME_IMG_URL; ?>/main/mo_main_banner.jpg" alt="img"></div>
		</div>
	</div>
	<div class="swiper-banner-pagination"></div>
	<div class="swiper-mobanner-pagination"></div>
	<div class="product_sect" style="display: none;">
		<div class="inner_container pbt50">
			<div class="board_wrap">
				<div class="swiper product_slide">
					<ul class="swiper-wrapper">
						<?php 
							$sql = " SELECT * FROM products WHERE pd_main != '' ORDER BY pd_main ASC LIMIT 20 ";

							$res = sql_query($sql);
							for($i=0; $row = sql_fetch_array($res); $i++){
								echo "<li class='swiper-slide'>";
								echo "	<a href=\"/sub/list_view.php?idx=".$row['idx']."&cateno=".$cateno."&subcateno=".$subcateno."\">";
								echo "		<div class=\"product_wrap\">";
								echo "			<div class=\"img_box\">";
								echo "				<img src=\"".G5_DATA_URL."/products/".$row['pd_img1']."\" alt=\"\" />";
								echo "			</div>";
								echo "			<div class=\"txt\">";
								echo "				<h2 class=\"product_name\">".$row['pd_nm']."</h2>";
								//echo "				<p>".$row['pd_text'][1]."</p>";
								echo "			</div>";
								echo "		</div>";
								echo "	</a>";
								echo "</li>";
							}
							?>
					</ul>
				</div>
				<div class="swiper-button-prev"></div>
				<!-- <div class="swiper-pagination"></div> -->
				<div class="swiper-button-next"></div>
			</div>
		</div>
	</div>
	<div class="product_sect2">
		<div class="inner_container pbt50">
			<!--<div class="board_wrap">
				<div class="swiper product_slide">
					<ul class="swiper-wrapper">
						<li class="swiper-slide">
							<a href="<?= G5_URL; ?>/sub/list_view.php?idx=71&cateno=104101102101" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product01.jpg" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">스트라우만</small>
									<h4 class="fs_46 fw_700">BLX Implant</h4>	
									<p class="fs_20 fw_300">BLX 임플란트 Immediate placement 부터 Conventional placement 까지 술자가 원하는 프로토콜에 <br>
									맞춰 모든 적응증에 식립할 수 있는 탁월한 임플란트 솔루션입니다. 인텔리전트한 임플란트 디자인으로 <br>Dynamic Bone Management를 제공하며 모든 골질에서 최소 절개와 예측 가능한 Immediate <br>
									프로토콜을 가능하게 합니다.</p>
								</div>
							</a>
						</li>
						<li class="swiper-slide">
							<a href="<?= G5_URL; ?>/sub/list_view.php?idx=70&cateno=104101101101" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product02.jpg" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">스트라우만</small>
									<h4 class="fs_46 fw_700">TLX Implant</h4>	
									<p class="fs_20 fw_300">TLX 임플란트는 경조직 및 연조직 치유의 주요 생물학적 원칙을 고려하여 디자인 되었습니다. <br>
									즉시 식립에서 Conventional 식립 및 부하까지 술자가 원하는 치료 프로토콜에 맞춰 <br>
									모든 적응증에 적용할 수 있는 탁월한 임플란트 솔루션입니다.</p>
								</div>
							</a>
						</li>
						<li class="swiper-slide dfbox">
							<a href="<?= G5_URL; ?>/sub/list.php?cateno=104102&cate1=104&cate2=104102" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product03.jpg" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">스트라우만</small>
									<h4 class="fs_46 fw_700">앤서지 Implant</h4>	
									<p class="fs_20 fw_300">앤서지는 혁신적인 임플란트와 보철 솔루션을 제공하는 프랑스 기업으로 70년이 넘는 <br>
									고정밀 의료기기 및 치과기기 분야의 노하우를 가지고 있습니다. <br>
									보험적용이 가능한 앤서지 임플란트로 차별화된 진료를 환자에게 제공하고 만족도를 높일 수 있습니다.</p>
								</div>
							</a>
						</li>
						<li class="swiper-slide dfbox">
							<a href="<?= G5_URL; ?>/sub/list_view.php?idx=74&cateno=104101102102" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product04.png" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">스트라우만</small>
									<h4 class="fs_46 fw_700">BLT Implant</h4>	
									<p class="fs_20 fw_300">BLT 임플란트는 발치와 (Extraction Socket) 부위에 탁월한 초기 고정력을 제공합니다. 좁은 턱뼈구조, <br>
									폭이 좁은 위축된 치조능선 등과 같은 환자의 해부학적 구조의 한계점을 효과적으로 시술할 수 있고 <br>
									수술의 복잡성이 줄어들어 환자의 치료 만족감을 높일 수 있습니다.</p>
								</div>
							</a>
						</li>
						<li class="swiper-slide dfbox">
							<a href="<?= G5_URL; ?>/sub/list_view.php?idx=68&cateno=104101101102" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product05.jpg" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">스트라우만</small>
									<h4 class="fs_46 fw_700">TL Implant</h4>	
									<p class="fs_20 fw_300">TL 임플란트의 디자인은 연조직과 경조직 치료의 생물학적 원리에 따라 설계 되었습니다. <br>
									티슈 레벨 임플란트 시스템은 지난 40여년간 지속적으로 사랑받아 온 솔루션이며, <br>
									이미 세계적으로 증명되었고, 가장 많이 문서화 된 치과 임플란트 시스템 중 하나 입니다.</p>
								</div>
							</a>
						</li>
						<li class="swiper-slide dfbox">
							<a href="<?= G5_URL; ?>/sub/list_view.php?idx=94&cateno=106101101" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product06.jpg" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">디지털 솔루션</small>
									<h4 class="fs_46 fw_700">Virtuo Vivo™</h4>	
									<p class="fs_20 fw_300">스트라우만 구강스캐너 Virtuo Vivo™ 는 130g의 가벼운 무게와 펜처럼 잡을 수 있는 슬림한 디자인으로, <br>사용자의 피로도를 최소화하고 환자에게 편안한 경험을 제공합니다.</p>
								</div>
							</a>
						</li>
						<li class="swiper-slide dfbox">
							<a href="<?= G5_URL; ?>/sub/list_view.php?idx=89&cateno=106103101102" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product07.jpg" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">디지털 솔루션</small>
									<h4 class="fs_46 fw_700">P30+</h4>
									<p class="fs_20 fw_300">스트라우만의 P30+는 고품질의 결과물을 제공하는 전문가용 프린터로서 대형 디스플레이, <br>
									자동문, 수조 열선 기능으로 사용자의 편의성을 극대화하였습니다.</p>
								</div>
							</a>
						</li>
						<li class="swiper-slide dfbox">
							<a href="<?= G5_URL; ?>/sub/list_view.php?idx=79&cateno=105101" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product08.jpg" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">바이오머테리얼</small>
									<h4 class="fs_46 fw_700">Emdogain</h4>	
									<p class="fs_20 fw_300">스트라우만 엠도게인은 법랑질 매트릭스 유도체를 함유한 생물학적 솔루션입니다. <br>
									이러한 천연 단백질 혼합물은 연조직과 경조직의 치유에 관여하는 특정 세포 유형과 조직재생을 <br>
									자극하여 치주 재생과 구강 상처 치유의 가속화를 유도하고 자연치아를 살릴 수 있어 치료 후 <br>
									환자의 만족도가 높은 제품입니다.</p>
								</div>
							</a>
						</li>
						<li class="swiper-slide dfbox">
							<a href="<?= G5_URL; ?>/sub/list_view.php?idx=80&cateno=105102101" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product09.jpg" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">바이오머테리얼</small>
									<h4 class="fs_46 fw_700">Cerabone</h4>	
									<p class="fs_20 fw_300">스트라우만 세라본은 재생치과의학 분야에서 일반적으로 사용되는 이종골이식재료 중 하나이며, <br>
									영구적으로 구조적인 서포트를 지원하는 안정적인 골이식재 입니다.</p>
								</div>
							</a>
						</li>
						<li class="swiper-slide dfbox">
							<a href="<?= G5_URL; ?>/sub/list_view.php?idx=82&cateno=105103101" class="dfbox">
								<div class="imgbox"><img src="<?= G5_THEME_IMG_URL; ?>/main/main_product10.jpg" alt="img"></div>
								<div class="txtbox">
									<small class="fs_22">바이오머테리얼</small>
									<h4 class="fs_46 fw_700">Jason Membrane</h4>	
									<p class="fs_20 fw_300">제이스 멤브레인은 돼지 심막 소재로 원재료의 고유한 특성과 구조를 그대로 보존하며 <br>
									높은 인열 저항성과 장기간 차폐 기능을 보이는 흡수성 콜라겐 멤브레인 입니다.</p>
								</div>
							</a>
						</li>
					</ul>
				</div>
				<div class="swiper-button-prev"></div>
				<div class="swiper-pagination"></div>
				<div class="swiper-button-next"></div>
			</div>-->
			<div class="board_wrap">
				<?php echo latest_main('theme/latest_pic_banner', 'banner', 10, 60); ?>
				<div class="swiper-button-prev"></div>
				<div class="swiper-pagination"></div>
				<div class="swiper-button-next"></div>
			</div>
		</div>
	</div>
	<div class="campus_sect">
		<div class="inner_container pbt50">
			<div class="tit_wrap dfbox">
				<h3 class="fs_34 fw_700">캠퍼스</h3>
				<p class="fs_20 fw_300">임플란트, 바이오머테리얼, 디지털 등 다양한 토픽의 강의를 확인하실 수 있습니다.</p>
			</div>
			<div class="board_wrap"><?php echo latest_main('theme/latest_campus', 'campus', 10, 60); ?></div>
			<a class="more_btn" onClick="location.href='<?= G5_URL; ?>/campus'">more</a>
		</div>
	</div>
	<div class="video_sect">
		<div class="inner_container pbt50">
			<div class="tit_wrap dfbox">
				<h3 class="fs_34 fw_700">영상자료</h3>
				<p class="fs_20 fw_300">각 제품별 영상과 유저 인터뷰를 확인하실 수 있습니다.</p>
			</div>
			<div class="board_wrap"><?php echo latest_main('theme/latest_pic_row3', 'video', 10, 60); ?></div>
			<a class="more_btn" onClick="location.href='<?= G5_URL; ?>/video'">more</a>
		</div>
	</div>
	<div class="youtooth_sect">
		<div class="inner_container pbt50">
			<div class="tit_wrap">
				<h3 class="fs_34 fw_700">사이언스</h3>
			</div>
			<div class="board_wrap"><?php echo latest_main('theme/latest_pic_youtooth', 'science', 10, 60); ?></div>
		</div>
	</div>
</div>


<script>
$(document).ready(function() {
	var fourSwiper = new Swiper(".product", {
    slidesPerView: 1,
		slidesPerGroup: 1,
		spaceBetween: 10,
		loop: true,
		autoplay: {
			delay: 4000,
			disableOnInteraction: false,
		},
    pagination: {
			el: ".swiper-pagination",
			clickable: true,
		},
		navigation: {
			nextEl: ".swiper-button-next",
			prevEl: ".swiper-button-prev",
		},
  });

	var bannerSwiper = new Swiper(".banner_sect", {
    slidesPerView: 1,
		slidesPerGroup: 1,
		spaceBetween: 10,
		loop: true,
		autoplay: {
			delay: 4000,
			disableOnInteraction: false,
		},
		// autoplay랑 touchRatio는 2개이상일때 제거하기
		autoplay: false,
		touchRatio: 0,
    pagination: {
			el: ".swiper-banner-pagination",
			clickable: true,
		},
  });

	var bannerMoSwiper = new Swiper(".banner_mo_sect", {
    slidesPerView: 1,
		slidesPerGroup: 1,
		spaceBetween: 10,
		loop: true,
		autoplay: {
			delay: 4000,
			disableOnInteraction: false,
		},
		// autoplay랑 touchRatio는 2개이상일때 제거하기
		autoplay: false,
		touchRatio: 0,
    pagination: {
			el: ".swiper-mobanner-pagination",
			clickable: true,
		},
  });
});
</script>

<?php
include_once(G5_THEME_PATH.'/tail.php');