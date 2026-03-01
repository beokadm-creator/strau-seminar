<?php 
define('G5_IS_ADMIN', true);
require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';
@mkdir(G5_DATA_PATH."/products", G5_DIR_PERMISSION);

if($depth4){
	$ctno = $depth4;
} else if($depth3){
	$ctno = $depth3;
} else if($depth2){
	$ctno = $depth2;
} else if($depth1){
	$ctno = $depth1;
}


/* [OSJ : 2024-04-09] 5개 삭제 파일을 위한 기초 데이터 */
$org_data = sql_fetch(" SELECT pd_img1, pd_img2, pd_img3, pd_img4, pd_img5 FROM products WHERE idx=".$idx);

$usefile = "";
for($i=0; $i<6; $i++){
	$no = $i+1;

	/* [OSJ : 2024-04-09] 삭제에 체크가 되어있다면 파일을 삭제 */
	if (isset($_POST['pd_img_del'][$no]) && $_POST['pd_img_del'][$no]) {
		@unlink(G5_DATA_PATH.'/products/'.$org_data['pd_img'.$no]);

		// 데이터 삭제 처리.
		$sql = " UPDATE products SET pd_img".$no." = '' WHERE idx = '{$idx}' ";
		sql_query($sql);
	}

	if ($_FILES['pd_img']['name'][$i]){

		$row = sql_fetch(" SELECT pd_img".$no." FROM products WHERE idx=".$idx);
		if($row['pd_img'.$no] != ""){
			@unlink(G5_DATA_PATH.'/products/'.$row['pd_img'.$no]);
		}

		$bn_img = $_FILES['pd_img']['name'][$i];
		
		$name_tmp = explode('/',$_FILES['pd_img']['tmp_name'][$i]);
		$ext = array_pop(explode('.', $bn_img));
		$filename = sha1($name_tmp[2]).".".$ext;
		$dest_path = G5_DATA_PATH."/products/".$filename;
		@move_uploaded_file($_FILES['pd_img']['tmp_name'][$i], $dest_path);
		@chmod($dest_path, G5_FILE_PERMISSION);

		if($bn_img){
			$usefile .= ", pd_img".$no." = '{$filename}' ";
		} else {
			$usefile = "";
		}
	}
}

/* [OSJ : 2024-05-08] 4개 삭제 파일을 위한 기초 데이터 */
$org_data = sql_fetch(" SELECT pd_file1, pd_file2, pd_file3, pd_file4 FROM products WHERE idx=".$idx);

$use_pdfile = "";
for($i=0; $i<5; $i++){
	$no = $i+1;

	if (isset($_POST['pd_file_del'][$no]) && $_POST['pd_file_del'][$no]) {
		@unlink(G5_DATA_PATH.'/products/'.$org_data['pd_file'.$no]);

		// 데이터 삭제 처리.
		$sql = " UPDATE products SET pd_file".$no." = '', pd_file".$no."_name = '' WHERE idx = '{$idx}' ";
		sql_query($sql);
	}

	if ($_FILES['pd_file']['name'][$i]){

		$row = sql_fetch(" SELECT pd_file".$no." FROM products WHERE idx=".$idx);
		if($row['pd_file'.$no] != ""){
			@unlink(G5_DATA_PATH.'/products/'.$row['pd_file'.$no]);
		}

		$pd_file_name = $_FILES['pd_file']['name'][$i];
		
		$name_tmp = explode('/',$_FILES['pd_file']['tmp_name'][$i]);
		$ext = array_pop(explode('.', $pd_file_name));
		$filename = sha1($name_tmp[2]).".".$ext;
		$dest_path = G5_DATA_PATH."/products/".$filename;
		@move_uploaded_file($_FILES['pd_file']['tmp_name'][$i], $dest_path);
		@chmod($dest_path, G5_FILE_PERMISSION);

		if($pd_file_name){
			$use_pdfile .= ", pd_file".$no." = '{$filename}' ";
			$use_pdfile .= ", pd_file".$no."_name = '{$pd_file_name}' ";
		} else {
			$use_pdfile = "";
		}
	}
}

// 파일등록
// $pd_file = "";
// if($delchk1){
// 	$row = sql_fetch(" SELECT pd_file FROM products WHERE idx=".$idx);
// 	if($row['pd_file'] != ""){
// 		@unlink(G5_DATA_PATH.'/products/'.$row['pd_file']);
// 	}
// 	$pd_file = ", pd_file = '{$filename}' ";
// }

// if($_FILES['pd_file']['name']){
// 	$row = sql_fetch(" SELECT pd_file FROM products WHERE idx=".$idx);
// 	if($row['pd_file'] != ""){
// 		@unlink(G5_DATA_PATH.'/products/'.$row['pd_file']);
// 	}

// 	$pd_img = $_FILES['pd_file']['name'];
		
// 	$name_tmp = explode('/',$_FILES['pd_file']['tmp_name']);
// 	$ext = array_pop(explode('.', $pd_img));
// 	$filename = sha1($name_tmp[2]).".".$ext;
// 	$dest_path = G5_DATA_PATH."/products/".$filename;
// 	@move_uploaded_file($_FILES['pd_file']['tmp_name'], $dest_path);
// 	@chmod($dest_path, G5_FILE_PERMISSION);

// 	if($pd_img){
// 		$pd_file .= ", pd_file = '{$filename}' ";
// 		$pd_file .= ", pd_file_name = '{$pd_img}' ";
		
// 	} else {
// 		$pd_file = ", pd_file = '' ";
// 		$pd_file = ", pd_file_name = '' ";
// 	}
// }

// $pd_file1 = "";
// if($delchk2){
// 	$row = sql_fetch(" SELECT pd_file1 FROM products WHERE idx=".$idx);
// 	if($row['pd_file1'] != ""){
// 		@unlink(G5_DATA_PATH.'/products/'.$row['pd_file1']);
// 	}
// 	$pd_file1 = ", pd_file1 = '{$filename1}' ";
// }


// if($_FILES['pd_file1']['name']){
// 	$row = sql_fetch(" SELECT pd_file1 FROM products WHERE idx=".$idx);
// 	if($row['pd_file1'] != ""){
// 		@unlink(G5_DATA_PATH.'/products/'.$row['pd_file1']);
// 	}

// 	$pd_img1 = $_FILES['pd_file1']['name'];
		
// 	$name_tmp1 = explode('/',$_FILES['pd_file1']['tmp_name']);
// 	$ext1 = array_pop(explode('.', $pd_img1));
// 	$filename1 = sha1($name_tmp1[2]).".".$ext1;
// 	$dest_path1 = G5_DATA_PATH."/products/".$filename1;
// 	@move_uploaded_file($_FILES['pd_file1']['tmp_name'], $dest_path1);
// 	@chmod($dest_path1, G5_FILE_PERMISSION);

// 	if($pd_img1){
// 		$pd_file1 .= ", pd_file1 = '{$filename1}' ";
// 		$pd_file1 .= ", pd_file1_name = '{$pd_img1}' ";
// 	} else {
// 		$pd_file1 = ", pd_file1 = '' ";
// 		$pd_file1 = ", pd_file1_name = '' ";
// 	}
// }

$sql_set = "
	  isopen			= '{$isopen}'
	, pd_main			= '{$pd_main}'
	, pd_ctno			= '{$ctno}'
	, pd_nm				= '{$pd_nm}'
	, pd_main_txt		= '{$pd_main_txt}'
	$usefile
	, pd_tag			= '{$pd_tag}'
	, pd_qs				= '{$pd_qs}'
	{$use_pdfile}
	, pd_demo			= '{$pd_demo}'
	, pd_text			= '{$pd_text}'
	, pd_brand_text		= '{$pd_brand_text}'
	, pd_ctist			= '{$pd_ctist}'
	, pd_ipttext		= '{$pd_ipttext}'
	, pd_subtxt			= '{$pd_subtxt}'
	, pd_grp_no			= '{$pd_grp_no}'
	, pd_opt			= '{$pd_opt}'
	, pd_exposure		= '{$pd_exposure}'
";


// products 테이블에 pd_brand_text 컬럼 없다면 추가
if(!sql_query(" SELECT pd_brand_text FROM products LIMIT 1 ", false)){
	sql_query(" ALTER TABLE products ADD pd_brand_text TEXT NULL AFTER pd_text ", false);
}


if($w == ''){
	$sql = " INSERT INTO products SET {$sql_set} , pd_mddate = NOW() ,reg_date = NOW() ";
} else if($w == "u"){
	$sql = " UPDATE products SET {$sql_set} , pd_mddate = '{$pd_mddate}' WHERE idx = '{$idx}' ";
} else if($w == "d"){
	$sql = " DELETE FROM products WHERE idx = '{$idx}' ";
}
sql_query($sql);
goto_url(G5_ADMIN_URL."/products/products_list.php");
?>