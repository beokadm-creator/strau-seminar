<?php
$sub_menu = '300830';
include_once('./_common.php');

auth_check($auth[$sub_menu], "r");

//배너 테이블이 있는지 검사한다.
if(!sql_query(" DESCRIBE {$g5['banner_table']} ", false)) {
       $query_cp = sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['banner_table']}` (
        `bn_id` int(11) NOT NULL AUTO_INCREMENT,
        `bn_alt` varchar(255) NOT NULL DEFAULT '',
        `bn_url` varchar(255) NOT NULL DEFAULT '',
        `bn_device` varchar(10) NOT NULL DEFAULT '',
        `bn_position` varchar(255) NOT NULL DEFAULT '',
        `bn_border` tinyint(4) NOT NULL DEFAULT '0',
        `bn_new_win` tinyint(4) NOT NULL DEFAULT '0',
        `bn_begin_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `bn_end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `bn_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `bn_hit` int(11) NOT NULL DEFAULT '0',
        `bn_order` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`bn_id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", true);
      sql_query(" ALTER TABLE `{$g5['banner_table']}` ADD PRIMARY KEY (`bn_id`) ", false);
      sql_query(" ALTER TABLE `{$g5['banner_table']}` MODIFY `bn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;COMMIT ", false);
}

$g5['title'] = '배너관리';
include_once (G5_ADMIN_PATH.'/admin.head.php');

$sql_common = " from {$g5['banner_table']} ";

// 테이블의 전체 레코드수만 얻음
$sql = " select count(*) as cnt " . $sql_common;
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt"> 등록된 배너 </span><span class="ov_num"> <?php echo $total_count; ?>개</span></span>
</div>

<div class="btn_fixed_top">
    <a href="./bannerform.php" class="btn_01 btn">배너추가</a>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
    <thead>
    <tr>
        <th scope="col" id="th_id">ID</th>
        <th scope="col" id="th_dvc">배너 Url</th>
		<th scope="col" id="th_loc">접속기기</th>
        <th scope="col" id="th_loc">출력형태</th>
        <th scope="col" id="th_st">시작일시</th>
        <th scope="col" id="th_end">종료일시</th>
        <th scope="col" id="th_odr">출력순서</th>
        <th scope="col" id="th_hit">클릭</th>
        <th scope="col" id="th_mng">관리</th>
    </tr>

    </thead>
    <tbody>
    <?php
    $sql = " select * from {$g5['banner_table']}
          order by bn_order, bn_id desc
          limit $from_record, $rows  ";
    $result = sql_query($sql);
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        // 테두리 있는지
        $bn_border  = $row['bn_border'];
        // 새창 띄우기인지
        $bn_new_win = ($row['bn_new_win']) ? 'target="_blank"' : '';

        $bimg = G5_DATA_PATH.'/banner_bbs/'.$row['bn_id'];
        if(file_exists($bimg)) {
            $size = @getimagesize($bimg);
            if($size[0] && $size[0] > 800)
                $width = 800;
            else
                $width = $size[0];

            $bn_img = "";

            $bn_img .= G5_DATA_URL."/banner_bbs/".$row['bn_id'];
        }

        switch($row['bn_device']) {
            case 'pc':
                $bn_device = 'PC';
                break;
            case 'mobile':
                $bn_device = '모바일';
                break;
            default:
                $bn_device = 'PC와 모바일';
                break;
        }

        $bn_begin_time = substr($row['bn_begin_time'], 0, 19);
        $bn_end_time   = substr($row['bn_end_time'], 0, 19);

        $bg = 'bg'.($i%2);
    ?>

    <tr class="<?php echo $bg; ?>">
        <td headers="th_id" class="td_num"><?php echo $row['bn_id']; ?></td>
        <td headers="th_dvc"><a href="<?php echo $bn_img; ?>" target="_blank"><?php echo $bn_img; ?></a></td>
		<td headers="th_loc"><?php echo $bn_device; ?></td>
        <td headers="th_loc">
		<?php if($row['bn_position'] == "") {
			echo "-";
		} else {
			echo $row['bn_position'];
		}
		?>
		</td>
        <td headers="th_st" class="td_datetime"><?php echo $bn_begin_time; ?></td>
        <td headers="th_end" class="td_datetime"><?php echo $bn_end_time; ?></td>
        <td headers="th_odr" class="td_num"><?php echo $row['bn_order']; ?></td>
        <td headers="th_hit" class="td_num"><?php echo number_format($row['bn_hit']); ?></td>
        <td headers="th_mng" class="td_mng td_mns_m">
            <a href="./bannerform.php?w=u&amp;bn_id=<?php echo $row['bn_id']; ?>" class="btn btn_03">수정</a>
            <a href="./bannerformupdate.php?w=d&amp;bn_id=<?php echo $row['bn_id']; ?>" onclick="return delete_confirm(this);" class="btn btn_02">삭제</a>
        </td>
    </tr>

    <?php
    }
    if ($i == 0) {
    echo '<tr><td colspan="9" class="empty_table">자료가 없습니다.</td></tr>';
    }
    ?>
    </tbody>
    </table>

</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>


<!-- <div class="local_desc01 local_desc">
    <p>
        선택하신 출력형태에 따른 출력코드 예시 입니다. 개별출력의 경우 배너ID 값을 지정하여 개별로 출력 합니다.<br>
		<span style="color:#888;">
           <?php echo htmlspecialchars("개별 출력 (배너ID 지정출력) : <?php echo display_banner_bbs('개별', '배너ID'); ?>"); ?><br>
           <?php echo htmlspecialchars("일반 출력 (세로정렬) : <?php echo display_banner_bbs('일반'); ?>"); ?><br>
		   <?php echo htmlspecialchars("슬라이드 출력 (좌우 슬라이드) : <?php echo display_banner_bbs('슬라이드'); ?>"); ?><br>
		   <?php echo htmlspecialchars("랜덤 출력 (새로고침시 랜덤출력) : <?php echo display_banner_bbs('랜덤'); ?>"); ?><br>
			<?php echo htmlspecialchars("미출력 : 배너를 출력하지 않습니다."); ?>
		   </span>

    </p>
</div> -->

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
