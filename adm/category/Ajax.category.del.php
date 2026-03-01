<?php
require_once '../../common.php';

$v = $_REQUEST['v'];



$res = sql_query(" SELECT cate_img FROM category WHERE cateno LIKE '{$v}%' ");

//echo "SELECT cate_img FROM category WHERE cateno LIKE '{$v}%'";
while($row = sql_fetch_array($res)){
	if($row['cate_img'] != ""){
		echo "<br>".$row['cate_img'];
		@unlink(G5_DATA_PATH.'/category/'.$row['cate_img']);
	}
}

$sql = " DELETE FROM category WHERE cateno LIKE '{$v}%' ";
sql_query($sql);
?>