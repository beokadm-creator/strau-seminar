<?php
$sub_menu = '300900';
define('G5_IS_ADMIN', true);

require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';

if (isset($token)) {
    $token = @htmlspecialchars(strip_tags($token), ENT_QUOTES);
} else {
	$token = get_admin_token();
}
run_event('admin_common');


$uri = $_SERVER['REQUEST_URI'];

$qstr = "";
if(isset($depth1))		$qstr .= '?depth1=' . $depth1;
if(isset($depth2))		$qstr .= '&depth2=' . $depth2;
if(isset($depth3))		$qstr .= '&depth3=' . $depth3;
if(isset($depth4))		$qstr .= '&depth4=' . $depth4;

$g5['title'] = "카테고리 관리";

$dep = "카테고리 관리";
include_once('../admin.head.php');

//echo "token : ".$token."<br>";

add_javascript('<script src="'.G5_ADMIN_URL.'/category/category.js?ver='.G5_JS_VER.'"></script>', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/category/category_css.css">', 0);
?>

<div class="admin_container">
	<h2 class="adm_title">카테고리 관리</h2>
	<div class="adm_list">
		<form name="fcf" method="post" action="./category.update.php" enctype="MULTIPART/FORM-DATA" onsubmit="return fcfsubmit(this)">
		<input type="hidden" name="w" value="">
		<input type="hidden" name="uri" value="<?php echo $uri;?>">
		<input type="hidden" name="token" value="<?php echo get_admin_token();?>">
			<div class="tbl_frm01 tbl_wrap">
			<table>
				<colgroup>
					<col style="width:150px;">
					<col>
				</colgroup>
				<tbody>
					<tr>
						<th scope="row">카테고리 선택</th>
						<td>
							<select name="depth1" id="depth1" class="csel">
								<option value="">DEPTH 1</option>
								<?php
									$sql = "SELECT * FROM category WHERE length(cateno) = '3' ";
									$res = sql_query($sql);
									while($row = sql_fetch_array($res)){
										$select = $category==$row['cateno']?"selected":"";
										echo "<option value=\"".$row['cateno']."\" ".$select.">".$row['catenm']."</option>";
									}
								?>
							</select>

							<select name="depth2" id="depth2" class="csel">
								<option value="">DEPTH 2</option>
 								<!-- Ajax  -->
							</select>

							<select name="depth3" id="depth3" class="csel">
								<option value="">DEPTH 3</option>
								<!--Ajax--> 
							</select>

							<!-- <select name="depth4" id="depth4" class="csel">
								<option value="">DEPTH 4</option>
							</select> -->
						</td>
					</tr>
					<tr>
						<th scope="row">카테고리 명</th>
						<td>
							<input type="text" name="catenm" id="catenm" class="frm_input" >
						</td>
					</tr>
					<tr>
						<th scope="row">노출여부</th>
						<td>
							<select name="isopen" id="isopen" class="csel">
								<option value="1">노출</option>
								<option value="0">비노출</option>
							</select>
						</td>
					</tr>
					
				</tbody>
			</table>
			</div>
			<div class="btn_right">
				<button type="submit" class="btn_submit btn">등록하기</button>

				<input type="button" class="btn btn_02" value="초기화" onclick="location.href='<?php echo G5_ADMIN_URL;?>/category/category_list.php'">
			</div>
		</form>
	</div>

	<div class="adm_list">
		<h2 class="adm_title">카테고리 목록 및 수정</h2>
		<?php 
		$count = sql_fetch(" SELECT COUNT(*) AS cnt FROM category WHERE LENGTH(cateno) = '3' ORDER BY idx ASC ");
		$cnt = $count['cnt'];
		if($cnt){
		?>
		
		<div class="adm_list_inner tbl_head01 tbl_wrap lstw">
			<h4 class="ttlbgc">DEPTH 1</h4>
			<table>
				<colgroup>
					<col>
					<col style="width:50px;">
					<col style="width:80px;">
					<col style="width:80px;">
				</colgroup>
				
				<thead>
					<tr>
						<th class="list_select">카테고리명</th>
						<th>노출</th>
						<th>수정</th>
						<th>삭제</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$sql = " SELECT * FROM category WHERE LENGTH(cateno) = '3' ORDER BY idx ASC ";
						$res = sql_query($sql);
						while($row = sql_fetch_array($res)){
							$c = sql_fetch(" SELECT COUNT(*) AS cnt FROM category WHERE LENGTH(cateno) = '6' AND cateno LIKE '{$row['cateno']}%' ORDER BY idx ASC ");
							$ctcnt = $c['cnt']?" (".$c['cnt'].")":"";
							$isopen = $row['isopen'] == 1?"●":"";
							echo "<tr>";
							echo "	<td>";
							echo "		<a href=\"?depth1=".$row['cateno']."\">".$row['catenm'].$ctcnt." </a>";
							echo "		<div id=\"co".$row['idx']."\" class=\"coList\" style=\"display:none; position:absolute; z-index:2; background-color:#fff; width:630px; padding:10px; border:1px solid #ccc;\" >";
							echo "			<iframe id=\"cos".$row['idx']."\" frameborder=\"0\" width=\"100%\" height=\"350\"></iframe>";
							echo "		</div>";
							echo "	</td>";
							echo "	<td>".$isopen."</td>";
							echo "	<td><a href=\"#blank\" class=\"btn_03 btn\" onclick=\"javascript:modok('".$row['idx']."');\">수정</a></td>";
							echo "	<td><a href=\"#blank\" class=\"btn_02 btn del_btn\" rel=\"".$row['cateno']."\">삭제</a></td>";
							echo "</tr>";
						}
					?>
				</tbody>
			</table>
		</div>
		<?php  } ?>

		<?php 
		$count = sql_fetch(" SELECT COUNT(*) AS cnt FROM category WHERE LENGTH(cateno) = '6' AND upcate = '{$depth1}' ORDER BY idx ASC ");
		$cnt = $count['cnt'];
		if($depth1 && $cnt){ ?>
		<div class="adm_list_inner tbl_head01 tbl_wrap lstw">
			<h4 class="ttlbgc">DEPTH 2</h4>
			<table>
					<col>
					<col style="width:50px;">
					<col style="width:80px;">
					<col style="width:80px;">
				</colgroup>
				
				<thead>
					<tr>
						<th class="list_select">카테고리명</th>
						<th>노출</th>
						<th>수정</th>
						<th>삭제</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$sql = " SELECT * FROM category WHERE LENGTH(cateno) = '6' AND upcate = '{$depth1}' ORDER BY idx ASC ";
						$res = sql_query($sql);
						while($row = sql_fetch_array($res)){
							$c = sql_fetch(" SELECT COUNT(*) AS cnt FROM category WHERE LENGTH(cateno) = '9' AND cateno LIKE '{$row['cateno']}%' ORDER BY idx ASC ");
							$ctcnt = $c['cnt']?" (".$c['cnt'].")":"";
							$isopen = $row['isopen'] == 1?"●":"";
							echo "<tr>";
							echo "	<td>";
							echo "		<a href=\"?depth1=".$row['upcate']."&depth2=".$row['cateno']."\">".$row['catenm'].$ctcnt."</a>";
							echo "		<div id=\"co".$row['idx']."\" class=\"coList\" style=\"display:none; position:absolute; z-index:2; background-color:#fff; width:630px; padding:10px; border:1px solid #ccc;\" >";
							echo "			<iframe id=\"cos".$row['idx']."\" frameborder=\"0\" width=\"100%\" height=\"350\"></iframe>";
							echo "		</div>";
							echo "	</td>";
							echo "	<td>".$isopen."</td>";
							echo "	<td><a href=\"#blank\" class=\"btn_03 btn\" onclick=\"javascript:modok('".$row['idx']."');\">수정</a></td>";
							echo "	<td><a href=\"#blank\" class=\"btn_02 btn del_btn\" rel=\"".$row['cateno']."\">삭제</a></td>";
							echo "</tr>";
						}
					?>
				</tbody>
			</table>
		</div>
		<? } ?>

		<?php 
		$count = sql_fetch(" SELECT COUNT(*) AS cnt FROM category WHERE LENGTH(cateno) = '9' AND upcate = '{$depth2}' ORDER BY idx ASC ");
		$cnt = $count['cnt'];
		if($depth1 && $depth2 && $cnt){ ?>
		<div class="adm_list_inner tbl_head01 tbl_wrap lstw">
			<h4 class="ttlbgc">DEPTH 3</h4>
			<table>
				<colgroup>
					<col>
					<col style="width:50px;">
					<col style="width:80px;">
					<col style="width:80px;">
				</colgroup>
				
				<thead>
					<tr>
						<th class="list_select">카테고리명</th>
						<th>노출</th>
						<th>수정</th>
						<th>삭제</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$sql = " SELECT * FROM category WHERE LENGTH(cateno) = '9' AND upcate = '{$depth2}' ORDER BY idx ASC ";
						$res = sql_query($sql);
						while($row = sql_fetch_array($res)){
							$c = sql_fetch(" SELECT COUNT(*) AS cnt FROM category WHERE LENGTH(cateno) = '12' AND cateno LIKE '{$row['cateno']}%' ORDER BY idx ASC ");
							$ctcnt = $c['cnt']?" (".$c['cnt'].")":"";
							$isopen = $row['isopen'] == 1?"●":"";
							echo "<tr>";
							echo "	<td>";
							echo "		<a href=\"?depth1=".$depth1."&depth2=".$depth2."&depth3=".$row['cateno']."\">".$row['catenm'].$ctcnt."</a>";
							echo "		<div id=\"co".$row['idx']."\" class=\"coList\" style=\"display:none; position:absolute; z-index:2; background-color:#fff; width:630px; padding:10px; border:1px solid #ccc;\" >";
							echo "			<iframe id=\"cos".$row['idx']."\" frameborder=\"0\" width=\"100%\" height=\"350\"></iframe>";
							echo "		</div>";
							echo "	</td>";
							echo "	<td>".$isopen."</td>";
							echo "	<td><a href=\"#blank\" class=\"btn_03 btn\" onclick=\"javascript:modok('".$row['idx']."');\">수정</a></td>";
							echo "	<td><a href=\"#blank\" class=\"btn_02 btn del_btn\" rel=\"".$row['cateno']."\">삭제</a></td>";
							echo "</tr>";
						}
					?>
				</tbody>
			</table>
		</div>
		<? } ?>
		
		<?php 
		$count = sql_fetch(" SELECT COUNT(*) AS cnt FROM category WHERE LENGTH(cateno) = '12' AND upcate = '{$depth3}' ORDER BY idx ASC ");
		$cnt = $count['cnt'];
		if($depth1 && $depth2 && $depth3 && $cnt){ ?>
		<div class="adm_list_inner tbl_head01 tbl_wrap lstw">
			<h4 class="ttlbgc">DEPTH 4</h4>
			<table>
				<colgroup>
					<col>
					<col style="width:50px;">
					<col style="width:80px;">
					<col style="width:80px;">
				</colgroup>
				
				<thead>
					<tr>
						<th class="list_select">카테고리명</th>
						<th>노출</th>
						<th>수정</th>
						<th>삭제</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$sql = " SELECT * FROM category WHERE LENGTH(cateno) = '12' AND upcate = '{$depth3}' ORDER BY idx ASC ";
						$res = sql_query($sql);
						while($row = sql_fetch_array($res)){
							$isopen = $row['isopen'] == 1?"●":"";
							echo "<tr>";
							echo "	<td>";
							echo "		<a href=\"?depth1=".$depth1."&depth2=".$depth2."&depth3=".$depth3."\">".$row['catenm']."</a>";
							echo "		<div id=\"co".$row['idx']."\" class=\"coList\" style=\"display:none; position:absolute; z-index:2; background-color:#fff; width:630px; padding:10px; border:1px solid #ccc;\" >";
							echo "			<iframe id=\"cos".$row['idx']."\" frameborder=\"0\" width=\"100%\" height=\"350\"></iframe>";
							echo "		</div>";
							echo "	</td>";
							echo "	<td>".$isopen."</td>";
							echo "	<td><a href=\"#blank\" class=\"btn_03 btn\" onclick=\"javascript:modok('".$row['idx']."');\">수정</a></td>";
							echo "	<td><a href=\"#blank\" class=\"btn_02 btn del_btn\" rel=\"".$row['cateno']."\">삭제</a></td>";
							echo "</tr>";
						}
					?>
				</tbody>
			</table>
		</div>
		<? } ?>
	</div>
</div>
<script>
$(".csel").on("change", function(){
	let url		= "<?php echo G5_ADMIN_URL;?>";
	let fnm		= "/category/Ajax.category.php";
	let v		= $(this).val();
	let no		= $(this).attr("id").split("depth")[1];
	let nxtno	= parseInt(no)+1;

	ajaxSelectChange(url,fnm,no,v, nxtno);

});

$(".del_btn").on("click", function(){
	let url		= "<?php echo G5_ADMIN_URL;?>";
	let fnm		= "/category/Ajax.category.del.php";
	let v		= $(this).attr("rel");
	let cfm		= confirm("카테고리를 삭제하시겠습니까?");
	let uri		= "<?php echo $uri;?>";
	ajaxDel(cfm,url,fnm,v,uri);
});

$("#ex_filename").on("change", function(){
	let v = $(this).val();
	$(".upload-name").val(v);
});

function fcfsubmit(f){
	if(!f.catenm.value){
		alert("카테고리명을 입력해주세요.");
		f.catenm.focus();
		return false;
	}
}
</script>
<?php
include_once ('../admin.tail.php');
?>