<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.G5_SKIN_URL.'/banner/style.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_SKIN_URL.'/banner/slide/css/swiper.min.css">', 0);
add_stylesheet('<script src="'.G5_SKIN_URL.'/banner/slide/js/swiper.min.js"></script>', 0);
?>



<?php

for ($i=0; $row=sql_fetch_array($result); $i++) {
    
    if ($i==0) echo '<div class="swiper-container slide_bn"><div class="swiper-wrapper">'.PHP_EOL;

    // 테두리 옵션
    $bn_border  = ($row['bn_border']) ? ' slide_bn_border' : '';
    
    // 새창 옵션
    $bn_new_win = ($row['bn_new_win']) ? ' target="_blank"' : '';

    
    $bimg = G5_DATA_PATH.'/banner_bbs/'.$row['bn_id'];
    if (file_exists($bimg))
    {
        $banner = '';
        $size = getimagesize($bimg);
        echo '<div class="swiper-slide mb-0 swiper-slide-slide_bn">'.PHP_EOL;
        if ($row['bn_url'][0] == '#')
            $banner .= '<a href="'.$row['bn_url'].'">';
        else if ($row['bn_url'] && $row['bn_url'] != 'http://') {
            $banner .= '<a href="'.G5_BBS_URL.'/bannerhit.php?bn_id='.$row['bn_id'].'"'.$bn_new_win.'>';
        }
        echo $banner.'<img src="'.G5_DATA_URL.'/banner_bbs/'.$row['bn_id'].'" alt="'.get_text($row['bn_alt']).'" width="100%" class="'.$bn_border.'">';
        if($banner)
            echo '</a>'.PHP_EOL;
        echo '</div>'.PHP_EOL;
    }
}


if ($i>0) echo '</div></div><div class="swiper-banner2-pagination"></div>'.PHP_EOL;

?>



<script>
document.addEventListener("DOMContentLoaded", function() {
    var sliderContainer = document.querySelector('.slide_bn .swiper-wrapper');
    var slides = sliderContainer ? sliderContainer.children.length : 0;

    var slide_bn = new Swiper('.slide_bn', {
        slidesPerView: 1, // 한 번에 보여질 슬라이드 개수
        loop: slides > 1, // 슬라이드가 2개 이상일 때만 loop 활성화
		speed : 100,
        autoplay: slides > 1 ? { // 슬라이드가 2개 이상일 때만 자동 재생
            delay: 5000,
            disableOnInteraction: false
        } : false,
        spaceBetween: 0, // 슬라이드 간 간격
        pagination: {
            el: ".swiper-banner2-pagination",
            clickable: true,
        },
        watchOverflow: true, // 슬라이드가 1개 이하이면 Swiper 기능 비활성화
        breakpoints: {
            1024: { slidesPerView: 1, spaceBetween: 0 },
            768: { slidesPerView: 1, spaceBetween: 0 },
            640: { slidesPerView: 1, spaceBetween: 0 },
            450: { slidesPerView: 1, spaceBetween: 0 }
        }
    });
});

</script>
