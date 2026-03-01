<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
add_stylesheet('<link rel="stylesheet" href="'.G5_SKIN_URL.'/banner/style.css">', 0);
?>

<script>
$('.random_item').hide();
</script>

<?php

for ($i=0; $row=sql_fetch_array($result); $i++) {
    
    if ($i==0) echo '<div class="random_bn">'.PHP_EOL;

    // 테두리 옵션
    $bn_border  = ($row['bn_border']) ? ' random_bn_border' : '';
    
    // 새창 옵션
    $bn_new_win = ($row['bn_new_win']) ? ' target="_blank"' : '';

    
    $bimg = G5_DATA_PATH.'/banner_bbs/'.$row['bn_id'];
    if (file_exists($bimg))
    {
        $banner = '';
        $size = getimagesize($bimg);
        echo '<ul class="random_item">'.PHP_EOL;
        if ($row['bn_url'][0] == '#')
            $banner .= '<a href="'.$row['bn_url'].'">';
        else if ($row['bn_url'] && $row['bn_url'] != 'http://') {
            $banner .= '<a href="'.G5_BBS_URL.'/bannerhit.php?bn_id='.$row['bn_id'].'"'.$bn_new_win.'>';
        }
        echo $banner.'<img src="'.G5_DATA_URL.'/banner_bbs/'.$row['bn_id'].'" alt="'.get_text($row['bn_alt']).'" width="100%" class="'.$bn_border.'">';
        if($banner)
            echo '</a>'.PHP_EOL;
        echo '</ul>'.PHP_EOL;
    }
}

if ($i>0) echo '</div>'.PHP_EOL;

?>

<script>
var random = Math.floor(Math.random() * $('.random_item').length);
$('.random_item').hide().eq(random).show();
</script>
