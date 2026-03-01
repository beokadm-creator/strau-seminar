<?php
require_once '../../common.php';

$no		= $_REQUEST["no"];
$v		= $_REQUEST["v"];
if($no == 1){
	$leng = 6;
} else if($no == 2) {
	$leng = 9;
} else if($no == 3) {
	$leng = 12;
}
$sql = sql_query(" SELECT * FROM category WHERE LENGTH(cateno) = '{$leng}' AND upcate = '{$v}' AND isopen = '1' ");
//echo "SELECT * FROM category WHERE LENGTH(cateno) = '{$leng}' AND isopen = '1'";

$list="";
while($row = sql_fetch_array($sql)){
	$list[] = array(
		"upcate"	=>$row['upcate']
		,"cateno"	=>$row['cateno']
		,"catenm"	=>$row['catenm']
		,"isopen"	=>$row['isopen']
	); 
}
echo json_encode($list);
?>