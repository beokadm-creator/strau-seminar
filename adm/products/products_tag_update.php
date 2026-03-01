<?php 
define('G5_IS_ADMIN', true);
require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';

echo "w : ".$w."<br>";
echo "idx : ".$idx."<br>";
echo "cateno : ".$cateno."<br>";
echo "tag : ".$tag;

$sql_set = "
	tag			= '{$tag}'
	, isopen	= '1'
";

if($w == "u"){
	$sql = " UPDATE product_tag SET {$sql_set} WHERE idx= '{$idx}' ";
} else {
	$sql = " INSERT INTO product_tag SET {$sql_set} , cateno='{$cateno}', reg_date=NOW() ";
}
sql_query($sql);
goto_url(G5_ADMIN_URL."/products/products_tag.php");
?>