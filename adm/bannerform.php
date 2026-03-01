<?php
$sub_menu = '300830';
include_once('./_common.php');

auth_check($auth[$sub_menu], "w");

$bn_id = preg_replace('/[^0-9]/', '', $bn_id);

$html_title = '배너';
$g5['title'] = $html_title.'관리';

if ($w=="u")
{
    $html_title .= ' 수정';
    $sql = " select * from {$g5['banner_table']} where bn_id = '$bn_id' ";
    $bn = sql_fetch($sql);
}
else
{
    $html_title .= ' 입력';
    $bn['bn_url']        = "http://";
    $bn['bn_begin_time'] = date("Y-m-d 00:00:00", time());
    $bn['bn_end_time']   = date("Y-m-d 00:00:00", time()+(60*60*24*31));
}

// 접속기기 필드 추가
if(!sql_query(" select bn_device from {$g5['banner_table']} limit 0, 1 ")) {
    sql_query(" ALTER TABLE `{$g5['banner_table']}`
                    ADD `bn_device` varchar(10) not null default '' AFTER `bn_url` ", true);
    sql_query(" update {$g5['banner_table']} set bn_device = 'pc' ", true);
}

include_once (G5_ADMIN_PATH.'/admin.head.php');
?>

<form name="fbanner" action="./bannerformupdate.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="bn_id" value="<?php echo $bn_id; ?>">

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row">이미지<br><span style="display: block; padding-top:10px; line-height: 1.3; color: #ff4747;"><b>PC</b> 1900*500<br><b>MO</b> 470*350</span></th>
        <td>
            <input type="file" name="bn_bimg">
            <?php
            $bimg_str = "";
            $bimg = G5_DATA_PATH."/banner_bbs/{$bn['bn_id']}";
            if (file_exists($bimg) && $bn['bn_id']) {
                $size = @getimagesize($bimg);
                if($size[0] && $size[0] > 750)
                    $width = 750;
                else
                    $width = $size[0];

                echo '<input type="checkbox" name="bn_bimg_del" value="1" id="bn_bimg_del"> <label for="bn_bimg_del">삭제</label>';
                $bimg_str = '<img src="'.G5_DATA_URL.'/banner_bbs/'.$bn['bn_id'].'" width="'.$width.'">';
            }
            if ($bimg_str) {
                echo '<div class="banner_or_img">';
                echo $bimg_str;
                echo '</div>';
            }
            ?>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_alt">이미지 설명</label></th>
        <td>
            <?php echo help("이미지 태그의 alt, title 에 해당되는 내용입니다."); ?>
            <input type="text" name="bn_alt" value="<?php echo get_text($bn['bn_alt']); ?>" id="bn_alt" class="frm_input" size="80">
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_url">링크</label></th>
        <td>
            <?php echo help("배너이미지 클릭시 이동하는 Url입니다."); ?>
            <input type="text" name="bn_url" size="80" value="<?php echo $bn['bn_url']; ?>" id="bn_url" class="frm_input">
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_device">접속기기</label></th>
        <td>
            <?php echo help('배너이미지를 출력할 기기를 선택할 수 있습니다..'); ?>
            <select name="bn_device" id="bn_device">
                <option value="both"<?php echo get_selected($bn['bn_device'], 'both', true); ?>>PC와 모바일</option>
                <option value="pc"<?php echo get_selected($bn['bn_device'], 'pc'); ?>>PC</option>
                <option value="mobile"<?php echo get_selected($bn['bn_device'], 'mobile'); ?>>모바일</option>
        </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_position">출력형태</label></th>
        <td>
            <select name="bn_position" id="bn_position">
<!-- 				<option value="개별" <?php echo get_selected($bn['bn_position'], '개별'); ?>>개별 출력</option>
                <option value="일반" <?php echo get_selected($bn['bn_position'], '일반'); ?>>일반 출력</option> -->
                <option value="슬라이드" <?php echo get_selected($bn['bn_position'], '슬라이드'); ?>>슬라이드 출력</option>
<!-- 				<option value="랜덤" <?php echo get_selected($bn['bn_position'], '랜덤'); ?>>랜덤 출력</option>
				<option value="" <?php echo get_selected($bn['bn_position'], ''); ?>>미출력</option> -->
			</select>
<!-- 			<br><br>
			<?php echo help("선택하신 출력형태에 따른 출력코드 예시 입니다. 개별출력의 경우 배너ID 값을 지정하여 개별로 출력 합니다."); ?>
			<?php echo htmlspecialchars("개별 출력 (배너ID 지정출력) : <?php echo display_banner_bbs('개별', '배너ID'); ?>"); ?><br>
			           <?php echo htmlspecialchars("일반 출력 (세로정렬) : <?php echo display_banner_bbs('일반'); ?>"); ?><br>
					   <?php echo htmlspecialchars("슬라이드 출력 (좌우 슬라이드) : <?php echo display_banner_bbs('슬라이드'); ?>"); ?><br>
					   <?php echo htmlspecialchars("랜덤 출력 (새로고침시 랜덤출력) : <?php echo display_banner_bbs('랜덤'); ?>"); ?><br>
			<?php echo htmlspecialchars("미출력 : 배너를 출력하지 않습니다."); ?> -->
		   
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_border">테두리</label></th>
        <td>
             <?php echo help("배너이미지에 테두리를 넣을지를 설정합니다.", 50); ?>
            <select name="bn_border" id="bn_border">
                <option value="0" <?php echo get_selected($bn['bn_border'], 0); ?>>사용안함</option>
                <option value="1" <?php echo get_selected($bn['bn_border'], 1); ?>>사용</option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_new_win">새창</label></th>
        <td>
            <?php echo help("배너이미지 클릭시 새창연결 여부를 선택할 수 있습니다.", 50); ?>
            <select name="bn_new_win" id="bn_new_win">
                <option value="0" <?php echo get_selected($bn['bn_new_win'], 0); ?>>사용안함</option>
                <option value="1" <?php echo get_selected($bn['bn_new_win'], 1); ?>>사용</option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_begin_time">게시 시작일시</label></th>
        <td>
            <?php echo help("배너 게시 시작일시를 설정합니다."); ?>
            <input type="text" name="bn_begin_time" value="<?php echo $bn['bn_begin_time']; ?>" id="bn_begin_time" class="frm_input"  size="21" maxlength="19">
            <input type="checkbox" name="bn_begin_chk" value="<?php echo date("Y-m-d 00:00:00", time()); ?>" id="bn_begin_chk" onclick="if (this.checked == true) this.form.bn_begin_time.value=this.form.bn_begin_chk.value; else this.form.bn_begin_time.value = this.form.bn_begin_time.defaultValue;">
            <label for="bn_begin_chk">오늘</label>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_end_time">게시 종료일시</label></th>
        <td>
            <?php echo help("배너 게시 종료일시를 설정합니다."); ?>
            <input type="text" name="bn_end_time" value="<?php echo $bn['bn_end_time']; ?>" id="bn_end_time" class="frm_input" size=21 maxlength=19>
            <input type="checkbox" name="bn_end_chk" value="<?php echo date("Y-m-d 23:59:59", time()+60*60*24*31); ?>" id="bn_end_chk" onclick="if (this.checked == true) this.form.bn_end_time.value=this.form.bn_end_chk.value; else this.form.bn_end_time.value = this.form.bn_end_time.defaultValue;">
            <label for="bn_end_chk">오늘+31일</label>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="bn_order">출력 순서</label></th>
        <td>
           <?php echo help("배너를 출력할 때 순서를 정합니다. 숫자가 작을수록 먼저 출력됩니다."); ?>
           <?php echo order_select("bn_order", $bn['bn_order']); ?>
        </td>
    </tr>

    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./bannerlist.php" class="btn_02 btn">목록</a>
    <input type="submit" value="확인" class="btn_submit btn" accesskey="s">
</div>

</form>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>
