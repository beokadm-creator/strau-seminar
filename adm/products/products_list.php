<?php
$sub_menu = '300910';
define('G5_IS_ADMIN', true);
require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';

$g5['title'] = "제품 리스트";

$dep = "제품 리스트";
include_once('../admin.head.php');

add_javascript('<script src="'.G5_ADMIN_URL.'/products/products.js?ver='.G5_JS_VER.'"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/products/products.css">', 0);

$sql_common = " FROM products ";

$sql_search = " WHERE (1)";
if ($stx) {
	$sql_search .= " AND ( ";
	switch ($sfl) {
		case 'pd_nm':
			$sql_search .= " {$sfl} like '%{$stx}%' ";
			break;
		case 'pd_qs':
			$sql_search .= " {$sfl} like '%{$stx}%' ";
			break;
		default:
			$sql_search .= " pd_nm like '{$stx}%' ";
			break;
	}
	$sql_search .= " ) ";
}

if($cateno){
	$sql_search .= " AND pd_ctno like '{$cateno}%' ";
}

if ($is_admin != 'super') {
    $sql_search .= " AND mb_level <= '{$member['mb_level']}' ";
}

if (!$sst) {
	$sst = "pd_main";
	$sod = "ASC";
}

// if (!$sst) {
//     $sst = "reg_date";
//     $sod = "DESC";
// }

$sql_order = " ORDER BY {$sst} {$sod} ";

$sql = " SELECT count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) {$page = 1;} // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$sql = " SELECT * {$sql_common} {$sql_search} {$sql_order} LIMIT {$from_record}, {$rows} ";
$res = sql_query($sql);
?>

<div class="admin_container">
	<h2 class="adm_title">제품 리스트</h2>
	<!--검색-->
	<div class="search_list local_sch01">
		<div class="search_box">
			<form name="fsearch" id="fsearch" class="local_sch" method="get">
				<input type="hidden" name="cateno" id="cateNo" value="">

				<label for="sfl" class="sound_only">검색대상</label>
				<select name="sfl" id="sfl">
<!-- 					<option value="">통합검색</option> -->
					<option value="pd_nm" <?php echo $sfl == "pd_nm" ? "selected" : ""; ?>>상품명</option>
				</select>
				<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
				<input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" required="" class="required frm_input">
				<input type="submit" value="검색" class="btn_submit">

				<div class="cateList">
					<ul>
						<?php
							$sql3 = " SELECT * FROM category WHERE length(cateno) = '3' ORDER BY cate_exposure ASC ";
							$res3 = sql_query($sql3);
							while($row3 = sql_fetch_array($res3)){
								$actbtn = $row3['cateno'] == $cateno ? "btn_03" : "btn_02";
								echo "<li><input type=\"button\" value=\"".$row3['catenm']."\" class=\"".$actbtn." btn cateNo\" rel=\"".$row3['cateno']."\" ></li>";
							}
						?>
					</ul>
				</div>

			</form>
		</div>
	</div>
	<!--검색-->

	<!--리스트-->
	<form name="frm" method="post" action="./products_delete.php" enctype="MULTIPART/FORM-DATA">
	<div class="adm_list">
		<div class="adm_list_header clear">
			<div class="btn_left">
				<input type="button" class="btn_01 btn" value="등록하기" onclick="location.href='products_form.php'">
				<!--<input type="button" class="btn btn_green" value="전체다운로드">-->
			</div>
			<div class="btn_right">
				<input type="submit" class="btn_02 btn" name="act_button" onclick="document.pressed=this.value" value="선택삭제">
			</div>
		</div>
		
		<div class="tbl_head01 tbl_wrap">
			<table>
				<colgroup>
					<col style="width:50px;">
					<col style="width:50px;">
					<col style="width:200px;">
					<col style="width:400px;">
					<col style="width:150px;">
					<col style="width:150px;">
					<col style="width:150px;">
					<col style="width:150px;">
				</colgroup>
				<thead>
					<tr>
						<th scope="col">
							<input type="checkbox" name="chkAll" id="chkAll" class="selec_chk">
							<label for="chkAll" class="sound_only">전체선택</label>
						</th>
						<th scope="col">No.</th>
						<th scope="col">대표이미지</th>
						<th scope="col">카테고리</th>
						<th scope="col">상품명</th>
						<th scope="col">메인진열 순서</th>
						<th scope="col">등록일</th>
						<th scope="col">상태</th>
					</tr>
				</thead>
				<tbody>
					<!--한 페이지당 10개 리스트-->
					<?php 
					$no = 1;
					while($row = sql_fetch_array($res)){
						$pd_qs = $row['pd_qs'] == 1 ? "사용":"미사용";
						
						$href = G5_ADMIN_URL."/products/products_form.php?w=u&idx=".$row['idx'];
						$hrefd = G5_ADMIN_URL."/products/products_update.php?w=d&idx=".$row['idx'];
						$thumb = $row['pd_img1'] ? "<a class=\"sImg\" href=\"".$href."\" ><img src=\"".G5_DATA_URL."/products/".$row['pd_img1']."\" /></a>":"";

						$d1 = substr($row['pd_ctno'],0,3);
						$d2 = substr($row['pd_ctno'],0,6);
						$d3 = substr($row['pd_ctno'],0,9);
						$d4 = substr($row['pd_ctno'],0,12);
						
						$ct1 = "a";
						$ct2 = "b";
						$ct3 = "c";
						$ct4 = "d";

						$catelist = "";
						if(strlen($row['pd_ctno']) == 12){
							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($row['pd_ctno'],0,3)."' ");
							$catelist .= $ct['catenm'];

							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($row['pd_ctno'],0,6)."' ");
							$catelist .= " -> " . $ct['catenm'];

							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($row['pd_ctno'],0,9)."' ");
							$catelist .= " -> " . $ct['catenm'];

							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '{$row['pd_ctno']}' ");
							$catelist .= " -> " . $ct['catenm'];
							
						}else if(strlen($row['pd_ctno']) == 9){
							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($row['pd_ctno'],0,3)."' ");
							$catelist .= $ct['catenm'];

							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($row['pd_ctno'],0,6)."' ");
							$catelist .= " -> " . $ct['catenm'];

							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '{$row['pd_ctno']}' ");
							$catelist .= " -> " . $ct['catenm'];

						}else if(strlen($row['pd_ctno']) == 6){

							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '".substr($row['pd_ctno'],0,3)."' ");
							$catelist .= $ct['catenm'];

							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '{$row['pd_ctno']}' ");
							$catelist .= " -> " . $ct['catenm'];

						}else if(strlen($row['pd_ctno']) == 3){
							$ct = sql_fetch(" SELECT * FROM category WHERE cateno = '{$row['pd_ctno']}' ");
							$catelist = $ct['catenm'];
						}
						
						$pd_main = $row['pd_main'] == 0 ? "" : $row['pd_main'];
						echo "<tr>";
						echo "	<td>";
						//echo "		<div class=\"chk_box\">";
						echo "			<input type=\"checkbox\" name=\"chk[]\" id=\"chk".$no."\" class=\"chk\" value=\"".$row['idx']."\">";
						echo "			<label for=\"chk".$no."\" class=\"sound_only\"></label>";
						//echo "		</div>";
						echo "	</td>";
						echo "	<td>".$no."</td>";
						echo "	<td>".$thumb."</td>";
						echo "	<td>".$catelist."</td>";
						echo "	<td>".$row['pd_nm']."</td>";

						echo "	<td>".$pd_main."</td>";
						echo "	<td>".$row['reg_date']."</td>";
						echo "	<td class=\"td_mng td_mng_m\">";
						echo "		<a href=\"".$href."\" class=\"btn btn_03\">자세히보기</a>";
						echo "		<a href=\"".$hrefd."\" class=\"board_copy btn btn_02 del_btn\">삭제</a>";
						echo "	</td>";
						echo "</tr>";
					?>
					
					<? $no++; } ?>
				</tbody>
			</table>
		</div>
		<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?' . $qstr . '&amp;page=&cateno=' . $cateno); ?>
	</div>
	<!--//리스트-->
	</form>
</div>

<script>
$("#chkAll").click(function() {
	if($("#chkAll").is(":checked")) $(".chk").prop("checked", true);
	else $(".chk").prop("checked", false);
});

$(".chk").click(function() {
	var total = $(".chk").length;
	var checked = $(".chk:checked").length;

	if(total != checked) $("#chkAll").prop("checked", false);
	else $("#chkAll").prop("checked", true); 
});


$(".cateNo").on("click", function(){
	let v =  $(this).attr("rel");
	let cn = $("#cateNo");
	cn.val(v);
	fsearch.submit();
});
</script>

<?php
include_once ('../admin.tail.php');
?>