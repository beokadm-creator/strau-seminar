<?php 
define('G5_IS_ADMIN', true);
require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';

$no		= $_REQUEST["no"];
$v		= $_REQUEST["v"];

//echo "v : ".$v."<br>";

$row = sql_fetch(" SELECT * FROM product_tag WHERE cateno = '{$v}' ");

$list="";
$list[] = array(
	"idx"		=>$row['idx']
	,"cateno"	=>$row['cateno']
	,"tag"		=>$row['tag']
); 

echo json_encode($list);
?>