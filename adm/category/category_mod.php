<?php
define('G5_IS_ADMIN', true);
require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/category/category_css.css">', 0);

$idx = $_REQUEST['idx'];


$sql = " SELECT * FROM category WHERE idx = '{$idx}' ";
$row =  sql_fetch($sql);
$uri = $_SERVER['REQUEST_URI'];
if($type == "u"){
	$sql_set = "
		catenm	= '{$catenm}'
		$upFile
		, isopen	= '{$isopen}'
		, cate_exposure	= '{$cate_exposure}'
		, reg_date	= NOW()
	";

	$sql = "UPDATE category SET {$sql_set} WHERE idx = '{$idx}' ";
	sql_query($sql);

	goto_url(G5_ADMIN_URL."/category/category_mod.php?idx={$idx} ");
}
?>
<link rel="stylesheet" href="<?php echo G5_ADMIN_URL; ?>/css/admin.css">
<link rel="stylesheet" href="<?php echo G5_ADMIN_URL; ?>/category/category_css.css">
<script src="<?php echo G5_ADMIN_URL;?>/category/category.js?ver=<?php echo G5_JS_VER;?>"></script>
<style>
body {background-color: #fff;}
.tbl_frm01 th {width: 100px; font-size:0.8em;}
.btn_confirm {text-align: center;}
.filebox .upload-name.style2 {width: 250px;}
</style>
<form name="fcgyform" method="post" action="./category_mod.php" enctype="MULTIPART/FORM-DATA">
	<input type="hidden" name="idx" value="<?php echo $idx; ?>">
	<input type="hidden" name="type" value="u">
	<div class="adm_list">
		<div class="tbl_frm01 tbl_wrap">
			<table>
				<colgroup>
					<col style="width:100px;">
					<col>
				</colgroup>
				
				<tbody>
					<tr>
						<th>카테고리명</th>
						<td>
							<input type="text" name="catenm" value="<?php echo $row['catenm'];?>" class="frm_input">
						</td>
					</tr>
					<tr>
						<th>노출여부</th>
						<td>
							<select name="isopen" id="isopen" class="csel">
								<option value="1" <?php echo $row['isopen'] == 1?"selected":"" ?>>노출</option>
								<option value="0" <?php echo $row['isopen'] == 0?"selected":"" ?> >비노출</option>
							</select>
						</td>
					</tr>
					<tr>
						<th>노출순서</th>
						<td>
							<input type="text" name="cate_exposure" value="<?php echo $row['cate_exposure']; ?>" class="frm_input">
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="btn_confirm" >
			<input type="submit" value="확인" class="btn_submit btn" onClick="alert('수정되었습니다.');">
			<button type="button" onClick="cancel('<?php echo $idx; ?>')" class="btn_02 btn del_btn">닫기</button>
		</div>
	</div>
</form>

<script>
let id = document.querySelector("#ex_filename");
let upid = document.querySelector(".upload-name");
id.addEventListener("change", function(){
	let v = this.value;
	upid.value = v;
});
</script>