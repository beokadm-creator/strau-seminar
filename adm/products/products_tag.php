<?php
define('G5_IS_ADMIN', true);
require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';

$g5['title'] = "분류별 기본 태그";

$dep = "분류별 기본 태그";
include_once('../admin.head.php');

add_javascript('<script src="'.G5_ADMIN_URL.'/products/products.js?ver='.G5_JS_VER.'"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/products/products.css">', 0);

$sql_where = " WHERE (1) ";

if($depth1){
	$sql_search = " AND length(cateno) = '3' AND cateno = '{$depth1}' ";
	$sql_search1 = " AND length(cateno) = '6' AND isopen = 1 AND cateno LIKE '{$depth1}%' ";
	$cateno = $depth1;
}
if($depth2){
	$sql_search = " AND length(cateno) = '6' AND cateno = '{$depth2}' ";
	$sql_search1 = " AND length(cateno) = '9' AND isopen = 1 AND cateno LIKE '{$depth2}%' ";
	$cateno = $depth2;
}
if($depth3){
	$sql_search = " AND length(cateno) = '9' AND cateno = '{$depth3}' ";
	$sql_search1 = " AND length(cateno) = '12' AND isopen = 1 AND cateno LIKE '{$depth3}%' ";
	$cateno = $depth3;
}
if($depth4){
	$sql_search = " AND length(cateno) = '12' AND cateno = '{$depth4}' ";
	$sql_search1 = " AND length(cateno) = '15' AND isopen = 1 AND cateno LIKE '{$depth4}%' ";
	$cateno = $depth4;
}
if($depth1){
	$sql = " SELECT * FROM product_tag {$sql_where} {$sql_search} AND isopen = 1 ORDER BY reg_date DESC ";
	$row = sql_fetch($sql);

	$sqlcnt = " SELECT COUNT(*) AS cnt FROM category {$sql_where} {$sql_search1} ORDER BY reg_date ASC ";
	$rowcnt = sql_fetch($sqlcnt);
}
$catecnt = $rowcnt['cnt'];

//echo "depth1 : ".$depth1."<br>";
//echo "depth2 : ".$depth2."<br>";
//echo "depth3 : ".$depth3."<br>";
//echo "depth4 : ".$depth4."<br>";

//echo "catecnt : ".$catecnt."<br>";

if(!$catecnt){
//	echo "asdasd";
	$redonly = "";
} else {
	$redonly = " readonly";
}
//echo $sql."<br><br>";
//echo $sqlcnt."<br><br>";
////echo $sql_search."<br><br>";
////print_r2($row);
////if($row['tag']){
////	$w = "u";
////	$idx = $row['idx'];
////}
////
////echo $w;
?>
<form name="frmsch" method="get" action="">
<div class="admin_container">
	<h2 class="adm_title">분류별 기본 태그</h2>

	<div class="member_reg_form">
		<div class="member_reg_form_inner">
			<table class="tb_iframe">
				<tbody>
					<tr>
						<th>상품위치</th>
						<td>
							<select name="depth1" id="depth1" class="csel" rel="1">
								<option value="">DEPTH 1</option>
								<?php
									$sql1 = " SELECT * FROM category WHERE length(cateno) = '3' AND isopen = 1 ORDER BY reg_date ASC ";
									$res1 = sql_query($sql1);
									while($cate = sql_fetch_array($res1)){
										$category = substr($cate['cateno'], 0,3);
										$select = $category==$depth1?"selected":"";
										echo "<option value=\"".$cate['cateno']."\" ".$select.">".$cate['catenm']."</option>";
									}
								?>
							</select>

							<select name="depth2" id="depth2" class="csel" rel="2">
								<option value="">DEPTH 2</option>
								<?php 
									if($depth1){
										$sql1 = " SELECT * FROM category WHERE length(cateno) = '6' AND isopen = 1 AND cateno LIKE '{$depth1}%' ORDER BY reg_date ASC ";
										$res1 = sql_query($sql1);
										while($cate = sql_fetch_array($res1)){
											$category = substr($cate['cateno'], 0,6);
											$select = $category==$depth2?"selected":"";
											echo "<option value=\"".$cate['cateno']."\" ".$select.">".$cate['catenm']."</option>";
										}
									}
								?>
							</select>

							<select name="depth3" id="depth3" class="csel" rel="3">
								<option value="">DEPTH 3</option>
								<?php 
									if($depth2){
										$sql1 = " SELECT * FROM category WHERE length(cateno) = '9' AND isopen = 1 AND cateno LIKE '{$depth2}%' ORDER BY reg_date ASC ";
										$res1 = sql_query($sql1);
										while($cate = sql_fetch_array($res1)){
											$category = substr($cate['cateno'], 0,9);
											$select = $category==$depth3?"selected":"";
											echo "<option value=\"".$cate['cateno']."\" ".$select.">".$cate['catenm']."</option>";
										}
									}
								?>
							</select>
							<select name="depth4" id="depth4" class="csel" rel="4">
								<option value="">DEPTH 4</option>
								<?php 
									if($depth3){
										$sql1 = " SELECT * FROM category WHERE length(cateno) = '12' AND isopen = 1 AND cateno LIKE '{$depth3}%' ORDER BY reg_date ASC ";
										$res1 = sql_query($sql1);
										while($cate = sql_fetch_array($res1)){
											$category = substr($cate['cateno'], 0,12);
											$select = $category==$depth4?"selected":"";
											echo "<option value=\"".$cate['cateno']."\" ".$select.">".$cate['catenm']."</option>";
										}
									}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th>태그 명</th>
						<td colspan="2" class="list_select">
							<?php 
								$sql = "";
							?>
							<input type="text" name="addtag" value="<?php echo $row['tag']; ?>" id="addtag" class="ipt <?php echo $redonly;?>" <?php echo $redonly;?>>
							<?php echo help('단어와 단어 사이는 콤마 ( , ) 로 구분하여 여러개를 입력할 수 있습니다. 예시) 빨강, 노랑, 파랑'); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

</form>
<form name="frm" method="get" action="./products_tag_update.php">
<input type="hidden" name="w" id="w" value="<?php echo $row['tag']?"u":""; ?>" >
<input type="hidden" name="idx" id="idx" value="<?php echo $row['idx']; ?>" >
<input type="hidden" name="cateno" id="cateno" value="<?php echo $cateno;?>" >
<input type="hidden" name="tag" value="<?php echo $row['tag'];?>" id="tag" class="ipt">

	<div class="confirm_btn col2">
		<input type="submit" class="btn btn_blue complete_btn" value="등록하기" id="frmsubmit">
		<input type="button" class="btn btn_red" value="초기화" onclick="location.href='<?php echo G5_ADMIN_URL;?>/products/products_tag.php'">
	</div>
</form>
</div>


<script>
$(".csel").on("change", function(){
	let id = $(this).nextAll();
	id.each(function(){
		let v = $(this).attr("rel");
		$("#depth"+v).val("").prop("selected", false);
	});
	frmsch.submit();
});

$("#addtag").on("change", function(){
	$("#tag").val($(this).val());
});
</script>
<?php
include_once ('../admin.tail.php');
?>