<?php
$sub_menu = "700100";
require_once './_common.php';

auth_check_menu($auth, $sub_menu, 'r');
 

$sql_common = " from {$g5['member_table']} m ";
$sql_search = " where (1) ";

if ($stx) {
    switch ($sfl) {
        case 'mb_name':
            $sql_search .= " and m.mb_name like '{$stx}%' ";
            break;
        case 'mb_id':
            $sql_search .= " and m.mb_id like '{$stx}%' ";
            break;
        default:
            $sql_search .= " and (m.mb_id like '{$stx}%' or m.mb_name like '{$stx}%') ";
            break;
    }
}

$sql = " select count(*) as cnt {$sql_common} {$sql_search} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

if (!$sst) {
    $sst = "mb_datetime";
    $sod = "desc";
}

$sql_order = " order by {$sst} {$sod} ";

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $export_sql = " select m.*, (select count(*) from {$g5['member_table']} x where x.mb_referred_by = m.mb_referral_code) as referral_cnt {$sql_common} {$sql_search} {$sql_order} ";
    $export_result = sql_query($export_sql);
    $filename = 'referral_list_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $headers = array('이름','아이디','추천코드','추천수','이메일','휴대폰');
    echo implode(',', $headers) . "\r\n";
    while ($r = sql_fetch_array($export_result)) {
        $row = array(
            $r['mb_name'],
            $r['mb_id'],
            $r['mb_referral_code'],
            isset($r['referral_cnt']) ? $r['referral_cnt'] : 0,
            $r['mb_email'],
            $r['mb_hp']
        );
        $escaped = array();
        for ($j = 0; $j < count($row); $j++) {
            $v = (string)$row[$j];
            $v = str_replace("\r", ' ', $v);
            $v = str_replace("\n", ' ', $v);
            if (preg_match('/[",]/', $v)) {
                $v = '"' . str_replace('"', '""', $v) . '"';
            }
            $escaped[] = $v;
        }
        echo implode(',', $escaped) . "\r\n";
    }
    exit;
}

$rows = $config['cf_page_rows'];
$total_page = ceil($total_count / $rows);
if ($page < 1) {
    $page = 1;
}
$from_record = ($page - 1) * $rows;

$sql = " select m.*, (select count(*) from {$g5['member_table']} x where x.mb_referred_by = m.mb_referral_code) as referral_cnt {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$g5['title'] = '추천인 관리';
require_once './admin.head.php';

$colspan = 7;
?>

 

 

 

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">총 회원수 </span><span class="ov_num"> <?php echo number_format($total_count) ?>명 </span></span>
    <span class="btn_ov01"><span class="ov_txt">추천코드 보유 </span><span class="ov_num"> 
        <?php
        $code_cnt_row = sql_fetch(" select count(*) as cnt from {$g5['member_table']} where mb_referral_code <> '' ");
        echo number_format($code_cnt_row['cnt']);
        ?>명
    </span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="mb_name" <?php echo get_selected($sfl, 'mb_name'); ?>>이름</option>
    <option value="mb_id" <?php echo get_selected($sfl, 'mb_id'); ?>>아이디</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input">
<input type="submit" class="btn_submit" value="검색">
<a href="./referral_list.php?<?php echo $qstr ? $qstr.'&' : '' ?>sst=referral_cnt&sod=desc" class="btn btn_03" style="margin-left:8px;">추천 많은 순</a>
<a href="./referral_list.php?<?php echo $qstr ? $qstr.'&' : '' ?>export=excel" class="btn btn_03" style="margin-left:8px;">엑셀 다운로드</a>
</form>

 

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>추천인 목록</caption>
    <colgroup>
        <col class="grid_4">
        <col class="grid_3">
        <col class="grid_3">
        <col class="grid_3">
        <col class="grid_3">
        <col class="grid_3">
        <col class="grid_4">
    </colgroup>
    <thead>
    <tr>
        <th scope="col">이름</th>
        <th scope="col">아이디</th>
        <th scope="col">추천코드</th>
        <th scope="col">추천수</th>
        <th scope="col">이메일</th>
        <th scope="col">휴대폰</th>
        <th scope="col">상세</th>
    </tr>
    </thead>
    <tbody>
    <?php
    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $bg = 'bg' . ($i % 2);
        ?>
        <tr class="<?php echo $bg; ?>">
            <td class="td_name"><?php echo $row['mb_name'] ?></td>
            <td class="td_name sv_use"><?php echo $row['mb_id'] ?></td>
            <td class="td_name"><?php echo $row['mb_referral_code'] ?></td>
            <td class="td_numbig"><?php echo number_format($row['referral_cnt']) ?></td>
            <td class="td_name"><?php echo $row['mb_email'] ?></td>
            <td class="td_name"><?php echo $row['mb_hp'] ?></td>
            <td class="td_mngsmall"><a href="./referral_detail.php?mb_id=<?php echo $row['mb_id'] ?>" class="btn btn_03">보기</a></td>
        </tr>
        <?php
    }
    if ($i == 0) {
        echo "<tr><td colspan=\"".$colspan."\" class=\"empty_table\">자료가 없습니다.</td></tr>";
    }
    ?>
    </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>

<?php
include_once ('./admin.tail.php');
?>
