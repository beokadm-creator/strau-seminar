<?php
$sub_menu = '300910';
// define('G5_IS_ADMIN', true);
// require_once '../../common.php';
// require_once G5_ADMIN_PATH . '/admin.lib.php';
include_once('./_common.php');

include_once(G5_EDITOR_LIB);
$g5['title'] = "제품 리스트";

$dep = "제품 리스트";
include_once('../admin.head.php');

add_javascript('<script src="'.G5_ADMIN_URL.'/products/products.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_ADMIN_URL.'/products/jquery-ui.js?ver='.G5_JS_VER.'"></script>', 0);
add_javascript('<script src="'.G5_ADMIN_URL.'/products/timepicker.js?ver='.G5_JS_VER.'"></script>', 0);

add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/products/products.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/products/jquery-ui.css">', 0);
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/products/timepicker.css">', 0);

if($idx){
	$sql = "SELECT * FROM products WHERE idx = '{$idx}' ORDER BY reg_date DESC ";
	$row = sql_fetch($sql);
}
if($w != "u"){
	$readonly = " readonly";
}
?>


<form name="frm" method="post" action="./products_update.php" enctype="MULTIPART/FORM-DATA">
<input type="hidden" name="w" value="<?php echo $w; ?>" >
<input type="hidden" name="idx" value="<?php echo $idx; ?>" >
<div class="admin_container">
	<h2 class="adm_title">제품 등록</h2>

	<div class="member_reg_form">
		<div class="tbl_frm01 tbl_wrap">
			<table>
				<tbody>
					<tr>
						<th>등록일</th>
						<td colspan="2" class="list_select">
							<input type="text" name="pd_mddate" value="<?php echo $row['pd_mddate']?$row['pd_mddate']:date("Y-m-d h:m:s");?>" id="uptime" class="ipt frm_input" style="width:204px">
						</td>
					</tr>
					<tr>
						<th>메인노출</th>
						<td colspan="2" class="list_select">
							<select name="pd_main" id="pd_main" class="csel">
								<option value="" >메인진열 순서</option>
								<option value="1" <?php echo $row['pd_main'] == 1 ? "selected" : "";?>>1</option>
								<option value="2" <?php echo $row['pd_main'] == 2 ? "selected" : "";?>>2</option>
								<option value="3" <?php echo $row['pd_main'] == 3 ? "selected" : "";?>>3</option>
								<option value="4" <?php echo $row['pd_main'] == 4 ? "selected" : "";?>>4</option>
								<option value="5" <?php echo $row['pd_main'] == 5 ? "selected" : "";?>>5</option>
								<option value="6" <?php echo $row['pd_main'] == 6 ? "selected" : "";?>>6</option>
								<option value="7" <?php echo $row['pd_main'] == 7 ? "selected" : "";?>>7</option>
								<option value="8" <?php echo $row['pd_main'] == 8 ? "selected" : "";?>>8</option>
								<option value="9" <?php echo $row['pd_main'] == 9 ? "selected" : "";?>>9</option>
								<option value="10" <?php echo $row['pd_main'] == 10 ? "selected" : "";?>>10</option>
								<option value="11" <?php echo $row['pd_main'] == 11 ? "selected" : "";?>>11</option>
								<option value="12" <?php echo $row['pd_main'] == 12 ? "selected" : "";?>>12</option>
								<option value="13" <?php echo $row['pd_main'] == 13 ? "selected" : "";?>>13</option>
								<option value="14" <?php echo $row['pd_main'] == 14 ? "selected" : "";?>>14</option>
								<option value="15" <?php echo $row['pd_main'] == 15 ? "selected" : "";?>>15</option>
								<option value="16" <?php echo $row['pd_main'] == 16 ? "selected" : "";?>>16</option>
								<option value="17" <?php echo $row['pd_main'] == 17 ? "selected" : "";?>>17</option>
								<option value="18" <?php echo $row['pd_main'] == 18 ? "selected" : "";?>>18</option>
								<option value="19" <?php echo $row['pd_main'] == 19 ? "selected" : "";?>>19</option>
								<option value="20" <?php echo $row['pd_main'] == 20 ? "selected" : "";?>>20</option>
							</select>
						</td>
					</tr>
					<tr>
						<th>제품위치</th>
						<td>
							<select name="depth1" id="depth1" class="csel">
								<option value="">DEPTH 1</option>
								<?php
									$sql1 = " SELECT * FROM category WHERE length(cateno) = '3' AND isopen = 1 ORDER BY reg_date ASC ";
									$res1 = sql_query($sql1);
									$category = substr($row['pd_ctno'], 0,3);
									while($cate = sql_fetch_array($res1)){
										$select = $category==$cate['cateno']?"selected":"";
										echo "<option value=\"".$cate['cateno']."\" ".$select.">".$cate['catenm']."</option>";
									}
								?>
							</select>

							<select name="depth2" id="depth2" class="csel" >
								<option value="">DEPTH 2</option>
								<?php 
									if($w){
										$sql1 = " SELECT * FROM category WHERE length(cateno) = '6' AND isopen = 1 ORDER BY reg_date ASC ";
										$res1 = sql_query($sql1);
										$category = substr($row['pd_ctno'], 0,6);
										while($cate = sql_fetch_array($res1)){
											$select = $category==$cate['cateno']?"selected":"";
											echo "<option value=\"".$cate['cateno']."\" ".$select.">".$cate['catenm']."</option>";
										}
									}
								?>
								<!--Ajax-->
							</select>
							<select name="depth3" id="depth3" class="csel" >
								<option value="">DEPTH 3</option>
								<?php 
									if($w){
										$sql1 = " SELECT * FROM category WHERE length(cateno) = '9' AND isopen = 1 ORDER BY reg_date ASC ";
										$res1 = sql_query($sql1);
										$category = substr($row['pd_ctno'], 0,9);
										while($cate = sql_fetch_array($res1)){
											$select = $category==$cate['cateno']?"selected":"";
											echo "<option value=\"".$cate['cateno']."\" ".$select.">".$cate['catenm']."</option>";
										}
									}
								?>
								<!--Ajax-->
							</select>
							<select name="depth4" id="depth4" class="csel" >
								<option value="">DEPTH 4</option>
								<?php 
									if($w){
										$sql1 = " SELECT * FROM category WHERE length(cateno) = '12' AND isopen = 1 ORDER BY reg_date ASC ";
										$res1 = sql_query($sql1);
										$category = substr($row['pd_ctno'], 0,12);
										while($cate = sql_fetch_array($res1)){
											$select = $category==$cate['cateno']?"selected":"";
											echo "<option value=\"".$cate['cateno']."\" ".$select.">".$cate['catenm']."</option>";
										}
									}
								?>
								<!--Ajax-->
							</select>
						</td>
					</tr>
					<tr>
						<th>제품명</th>
						<td><input type="text" name="pd_nm" value="<?php echo $row['pd_nm'];?>" class="ipt frm_input"></td>
					</tr>
					<tr class="upFileList">
						<th data="1">제품 이미지<br><span class="thumb">※250*250</span></th>
						<td colspan="5" class="list_select">
							<? 
							for($i=1; $i<=5; $i++){
							?>
							<div class="filebox bs3-primary">
								<input class="upload-name style2 pd_img<?=$i?>" disabled="disabled" value="<?=$row['pd_img'.$i]?>">
								<label for="pd_img<?=$i?>" class="btn_frmline">파일첨부</label>
								<input type="file" id="pd_img<?=$i?>" name="pd_img[]" class="upload-hidden pd_img" value="<?=$row['pd_img'.$i]?>">
								<? if($row['pd_img'.$i]){ ?>
									<div class="thumbImg"><img src="<?=G5_DATA_URL."/products/".$row['pd_img'.$i]?>" alt=""></div>
									<span class="file_del">
										<input type="checkbox" id="pd_img_del<?php echo $i ?>" name="pd_img_del[<?php echo $i;  ?>]" value="1"> <label for="pd_img_del<?php echo $i ?>"> 파일 삭제</label>
									</span>
								<? } ?>
							</div>
							<? } ?>
						</td>
					</tr>
					<tr>
						<th>노출 순서</th>
						<td>
							<input type="number" name="pd_exposure" value="<?php echo $row['pd_exposure'];?>" id="pd_exposure" class="ipt frm_input" style="width:240px">
						</td>
					</tr>

					<!-- [OSJ : 2024-05-08] 관련자료 멀티 업로드로 확장 -->
					<tr>
						<th>관련자료</th>
						<td>
							<? for($i=1; $i<=4; $i++){ ?>
							<div class="filebox bs3-primary">
								<input class="upload-name style2 pd_file<?=$i?>" disabled="disabled" value="<?php echo $row['pd_file'.$i.'_name'];?>">
								<label for="pd_file<?=$i?>" class="btn_frmline">파일첨부</label> 
								<input type="file" id="pd_file<?=$i?>" name="pd_file[]" class="upload-hidden pd_file" value="<?php echo $row['pd_file'.$i];?>">
								<?php if($row['pd_file'.$i]){ ?>
									&nbsp;&nbsp;<input type="checkbox" id="pd_file_del<?php echo $i ?>" name="pd_file_del[<?php echo $i;  ?>]" value="1"> <label for="pd_file_del<?php echo $i ?>"> 파일 삭제</label>
								<a class="btn_frmline" style="margin-left:10px;" href="<?php echo G5_DATA_URL."/products/".$row['pd_file'.$i]?>" download="<?=$row['pd_file'.$i.'_name']?>">첨부파일 다운로드 <?=$i?></a>
								<?php } ?>
							</div>
							<? } ?>
						</td>
					</tr>
					<!-- [OSJ : 2024-05-08] 무료데모신청 추가 -->
					<tr>
						<th>무료데모신청</th>
						<td><input type="text" name="pd_demo" value="<?php echo $row['pd_demo'];?>" class="ipt frm_input"></td>
					</tr>
					<tr>
						<th>제품영상 URL</th>
						<td>
						<div clpd_ctistss="wr_content smarteditor2">
							<!-- 최소/최대 글자 수 사용 시 -->
							<?php echo editor_html('pd_ctist', get_text($row['pd_ctist'], 0)); ?>
						</div>
						</td>
					</tr>
					<tr>
						<th>브랜드</th>
						<td>
						<div clpd_ctistss="wr_content smarteditor2">
							<!-- 최소/최대 글자 수 사용 시 -->
							<textarea name="pd_brand_text" id="pd_brand_text"><?=$row['pd_brand_text']?></textarea>

						</div>
						</td>
					</tr>
					<tr>
						<th>제품정보</th>
						<td>
						<div clpd_ctistss="wr_content smarteditor2">
							<!-- 최소/최대 글자 수 사용 시 -->
							<!-- // echo editor_html('pd_text', get_text($row['pd_text'], 0));  -->
							<textarea name="pd_text" id="pd_text"><?=clean_xss_tags($row['pd_text'], 0, 1, 0, 0)?></textarea>
						</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="confirm_btn col2" style="text-align:center;">
		<input type="button" class="btn_submit btn" value="등록하기" id="frmsubmit">
		<input type="button" class="btn btn_02" value="취소하기" onclick="javascript:history.back();">
	</div>
</div>
</form>


<!-- 스마트에디터안에 기본정보 넣기 start -->

<script>
$(document).ready(function() {
	// let defaultContent = '<ul>' +
    // '<li><b>제품명</b> </li>' +
    // '<li><b>브랜드</b> </li>' +
    // '<li><b>제품정보</b> </li>' +
    // '</ul>';
	// let pdTextContent = <?= !empty($row['pd_text']) ? json_encode($row['pd_text']) : 'defaultContent' ?>;
	// $('#pd_text').val(pdTextContent);

	$('#pd_text').on('input', function() {
			// 변경된 내용을 가져와서 저장
			var newContent = $(this).val();
			// 변경된 내용을 localStorage에 저장
			sessionStorage.setItem('pd_text_content', newContent);
	});
})
// 스마트에디터안에 기본정보 넣기 end

// $(".plusFile").on("click", function(){
// 	let cnt = parseInt($(".filebox").length)-1;
// 	if(cnt < 5){
// 		let str = "";
// 			str += "	<ul>";
// 			str += "		<input class=\"upload-name style2 pd_img"+cnt+"\" disabled=\"disabled\" value=\"\">";
// 			str += "		<label for=\"pd_img"+cnt+"\" class=\"btn_frmline\">파일첨부</label>";
// 			str += "		<input type=\"file\" id=\"pd_img"+cnt+"\" name=\"pd_img[]\" class=\"upload-hidden pd_img\" value=\"\">";
// 			str += "		<div class=\"minusFile\">";
// 			str += "			<i class=\"fa fa-minus-square-o\" ></i>";
// 			str += "		</div>";
// 			str += "	</div>";
// 		$(".upFileList>td").append(str);
// 	} else {
// 		alert("파일은 5개까지 추가 할 수 있습니다.");
// 		return false;
// 	}
// 	minusFile(".minusFile");
// });

// minusFile(".minusFile");

datepickerHMS("#uptime");


$("#frmsubmit").on("click", function(){
	<?php echo get_editor_js('pd_ctist'); ?>

	frm.submit();
});

$(".csel").on("change", function(){
	let url		= "<?php echo G5_ADMIN_URL;?>";
	let fnm		= "/category/Ajax.category.php";
	let v		= $(this).val();
	let no		= $(this).attr("id").split("depth")[1];
	let nxtno	= parseInt(no)+1;

	ajaxSelectChangeDiv(url,fnm,no,v, nxtno);

});

$(".del_btn").on("click", function(){
	let url		= "<?php echo G5_ADMIN_URL;?>";
	let fnm		= "/category/Ajax.category.del.php";
	let v		= $(this).attr("rel");
	let cfm		= confirm("카테고리를 삭제하시겠습니까?");
	let uri		= "<?php echo $uri;?>";
	ajaxDel(cfm,url,fnm,v,uri);
});

$(".pd_file").on("change", function(){
	// let v = $(this).val();
	// $("#upload_file1").val(v);
	
	let id = $(this).attr("id");
	var fileName = $(this).val().split('/').pop().split('\\').pop();
	$("."+id).val(fileName);
});

$(".pd_img").on("change", function(){
	// let v = $(this).val();
	let id = $(this).attr("id");
	// $("."+id).val(v);
	var fileName = $(this).val().split('/').pop().split('\\').pop();
	$("."+id).val(fileName);
});
</script>
<?php


include_once ('../admin.tail.php');
?>