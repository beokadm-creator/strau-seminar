<?
$qa_latest_skin_url = G5_THEME_URL."/skin/latest/pic_list/"; //조건 테마를 사용며 최신글 베이직 스킨을 사용할때
add_stylesheet('<link rel="stylesheet" href="'.$qa_latest_skin_url.'/style.css">', 0); //스타일시트를 적용하고
$rows = "5"; // 출력시킬 최신글 갯수
$sql = " select * from {$g5['qa_content_table']} where qa_type = 0 order by qa_id desc limit 0, {$rows} ";
$result = sql_query($sql);
?>
<div class="lt">
    <strong class="lt_title"><a href="<?php echo G5_BBS_URL ?>/qalist.php">1:1문의</a></strong>
    <ul>
		<?php for ($i=0; $row = sql_fetch_array($result); $i++) { 
		// 오늘을 불러옵니다. 
		$intime = date("Y-m-d H:i:s", time() - (int)(60 * 60 * 24)); ?>
		<li>
			<a href="<?php echo G5_BBS_URL ?>/qaview.php?qa_id=<?php echo $row[qa_id];?>">
			<?php echo "<strong>[{$row[qa_category]}]</strong>".$row[qa_subject];
			if($row[qa_datetime] >= $intime) echo "&nbsp;<img src=".$qa_latest_skin_url."img/icon_new.gif";
			//24시간 이내에 등록된 게시물은 new 아이콘 출력 시키기
			?>
			</a>
		</li>
		<?php } ?>
		<?php if (count($result) == 0) { //게시물이 없을 때  ?>
		<li>게시물이 없습니다.</li>
		<?php }  ?>
	</ul>
    <div class="lt_more"><a href="<?php echo G5_BBS_URL ?>/qalist.php"><span class="sound_only">1:1문의</span>더보기</a></div>
</div>