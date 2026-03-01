<?php 
define('G5_IS_ADMIN', true);
require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';

for($j=0;$j<sizeof($chk);$j++) { 
	$req_up .= $chk[$j]."/"; 
}

$req_up_Division = explode("/",$req_up);
$req_up_Total = count($req_up_Division);
$req_up_Total = $req_up_Total-1 ;
 
if($act_button == '선택삭제'){
	if(!$req_up_Total){
		alert("삭제할 항목을 선택해 주세요.");
	} else {
		for($i=0; $i<$req_up_Total; $i++){
			$row = sql_fetch(" select * from products where idx='$req_up_Division[$i]'" );
			$sql = "DELETE FROM products WHERE idx='{$req_up_Division[$i]}'";
			sql_query($sql);
		}

		goto_url(G5_ADMIN_URL."/products/products_list.php");
	}
}