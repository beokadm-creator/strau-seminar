<?php

$g5['title'] = "제품정보";
$g5['title2'] = "pro";
$depth1 = 2;


$sql_common = " FROM products ";

$sql_where = " WHERE (1) ";

$sql_search = " {$sql_where} ";

if($slf){
	$sql_search .= " AND (pd_nm LIKE '%{$slf}%' OR pd_tag LIKE '%{$slf}%') ";
}

if($cateno){
	$sql_search .= " AND pd_ctno LIKE '{$cateno}%' ";
}

if($subcateno){
	$sql_search .= " AND pd_ctno = '{$subcateno}' ";
}

if($sort == 1){
	$sst = "reg_date";
	$sod = "DESC";
} else if($sort == 2){
	$sst = "hit";
	$sod = "DESC";
} else if($sort == 3){
	$sst = "funds";
	$sod = "ASC";
} else if($sort == 4){
	$sst = "profit";
	$sod = "DESC";
} else {
	$sst = "pd_exposure";
	$sod = "ASC";
}


$sql_order = " ORDER BY {$sst} {$sod} ";

$sql = " SELECT count(*) AS cnt {$sql_common} {$sql_search} {$sql_order}  ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = 12;
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) {
    $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
}
$from_record = ($page - 1) * $rows; // 시작 열을 구함

if($cateno){
	$sql_search .= " AND pd_ctno like '{$cateno}%' ";
}

include_once(G5_THEME_PATH.'/head.php');
//add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_CSS_URL.'/pro.css">', 0);

?>


<!--컨텐츠시작-->
<div class="<? echo $g5['title2']?>_wrap pbt100">
	<div class="inner_container plr60">
	<div class="common_wrap mb65">
		<h2 class="tit fs_42 fw_700"><? echo $g5['title'];?></h2>
		<ul class="sub_menu">
		<?php 
		
			$catelist = "";

			if(strlen($cateno) == 12){
				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($cateno,0,3)."' ");
				$catelist .= $ct['catenm'];

				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($cateno,0,6)."' ");
				$catelist .= "&nbsp;&nbsp;>&nbsp;&nbsp;" . $ct['catenm'];

				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($cateno,0,9)."' ");
				$catelist .= "&nbsp;&nbsp;>&nbsp;&nbsp;" . $ct['catenm'];

				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '{$cateno}' ");
				$catelist .= "&nbsp;&nbsp;>&nbsp;&nbsp;" . $ct['catenm'];

			} else if(strlen($cateno) == 9){

				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($cateno,0,3)."' ");
				$catelist .= $ct['catenm'];

				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($cateno,0,6)."' ");
				$catelist .= "&nbsp;&nbsp;>&nbsp;&nbsp;" . $ct['catenm'];

				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '{$cateno}' ");
				$catelist .= "&nbsp;&nbsp;>&nbsp;&nbsp;" . $ct['catenm'];

			} else if(strlen($cateno) == 6){
				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($cateno,0,3)."' ");
				$catelist .= $ct['catenm'];

				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '{$cateno}' ");
				$catelist .= "&nbsp;&nbsp;>&nbsp;&nbsp;" . $ct['catenm'];

			} else if(strlen($cateno) == 3){
				$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '{$cateno}' ");
				$catelist = $ct['catenm'];
			}else{
				$catelist = "카테고리 정보 없음";
			}

			echo "<li>{$catelist}</li>";			
		?>
		</ul>
	</div>
	<div class="product list" id="conTab">
		
			<?php 
//			$ct = sql_fetch("SELECT * FROM category WHERE cateno = '{$cateno}'");
//			$subtit = $ct['catenm'];
//			if(!$slf){
//				echo "<h2 class=\"product_category_name category1\">".$subtit."</h2>";
//				echo "<div class=\"mobile_button_list\">";
//				echo "	<div class=\"mobile_button_prev\"></div>";
//				echo "	<div class=\"mobile_button_next\"></div>";
//				$scnt = sql_fetch("SELECT COUNT(*) AS cnt FROM category WHERE upcate = '{$cateno}'");
//				if($scnt['cnt']){
//					echo "	<div class=\"product_category category2\">";
//					echo "		<ul>";
//					$sql1 = " SELECT * FROM category WHERE upcate = '{$cateno}' ORDER BY cate_exposure ASC, reg_date ASC ";
//					$res1 = sql_query($sql1);
//					while($row1 = sql_fetch_array($res1)){
//						$on = $row1['cateno'] == $subcateno ?"on":"";
//						echo "			<li class=\"".$on."\"><a href=\"/sub/list.php?cateno=".$cateno."&subcateno=".$row1['cateno']."#conTab\">".$row1['catenm']."</a></li>";
//					}
//					echo "		</ul>";
//					echo "	</div>";
//				}
//				echo "</div>";
//			}
//			?>

			<ul class="product_list_ul dfbox">
				<?php 
				$sql = " SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows} ";

				$res = sql_query($sql);
				$count = sql_num_rows($res);
				while($row = sql_fetch_array($res)){
					echo "<li>";
					echo "	<a href=\"/sub/list_view.php?idx=".$row['idx']."&cateno=".$cateno."&subcateno=".$subcateno."\">";
					echo "		<div class=\"product_wrap\">";
					echo "			<div class=\"img\">";
					echo "				<img src=\"".G5_DATA_URL."/products/".$row['pd_img1']."\" alt=\"\" />";
					echo "			</div>";
					echo "			<div class=\"txt\">";
					echo "				<p class=\"product_name\">".$row['pd_nm']."</p>";
					echo "			</div>";
					echo "		</div>";
					echo "	</a>";
					echo "</li>";
				}
				?>
				<? if($count == 0) {
					echo "<li class='empty'>제품이 등록되지 않았습니다.</li>";
				} ?>
			</ul>

		<!-- 페이징 -->
		<?php echo get_paging_list(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?' . $uri . '&amp;page='); ?>
		</div>
	</div>
<!-- 	</form> -->
</div>

<script>
	$(function(){
		/* 서브메뉴 크기 지정하기 */
		var x = $("ul.col3 li").length;
		var wid = (100/x);
		$("ul.col3 li").css({"width":wid+"%"});
	});

	// 767px일시 동작
	if (matchMedia("screen and (max-width: 767px)").matches) {
		var count = $('.product_category.category2 ul li').length;
		var count_wid = (45*count);
		$(".product_category.category2 ul").css({"width":count_wid+"vw"});
	}
</script>

<script>
//   AOS.init({once: true});
</script>
<?php
include_once(G5_THEME_PATH.'/tail.php');
?>