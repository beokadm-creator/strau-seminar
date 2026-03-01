<?php
$sub_menu = "700100";
require_once './_common.php';

auth_check_menu($auth, $sub_menu, 'r');

$mb_id = isset($_GET['mb_id']) ? clean_xss_tags($_GET['mb_id'], 1, 1) : '';

if (!$mb_id) {
    alert('회원아이디가 넘어오지 않았습니다.');
}

// 추천인 정보 조회
$referrer_sql = " select * from {$g5['member_table']} where mb_id = '{$mb_id}' ";
$referrer = sql_fetch($referrer_sql);

if (!$referrer) {
    alert('존재하지 않는 회원입니다.');
}

// 추천받은 사람들 목록 조회
$sql_common = " from {$g5['member_table']} ";
$sql_search = " where mb_referred_by = '{$referrer['mb_referral_code']}' ";

if ($stx) {
    switch ($sfl) {
        case 'mb_name':
            $sql_search .= " and mb_name like '{$stx}%' ";
            break;
        case 'mb_id':
            $sql_search .= " and mb_id like '{$stx}%' ";
            break;
        default:
            $sql_search .= " and (mb_id like '{$stx}%' or mb_name like '{$stx}%') ";
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
    $export_sql = " select * {$sql_common} {$sql_search} {$sql_order} ";
    $export_result = sql_query($export_sql);
    $filename = 'referral_detail_' . $mb_id . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $headers = array('이름','아이디','직업','치과명/학교명','이메일','휴대폰','상태','가입일');
    echo implode(',', $headers) . "\r\n";
    while ($r = sql_fetch_array($export_result)) {
        $status = ($r['mb_level'] == '3') ? '승인' : '비승인';
        $row = array(
            $r['mb_name'],
            $r['mb_id'],
            $r['mb_1'],
            $r['mb_3'],
            $r['mb_email'],
            $r['mb_hp'],
            $status,
            substr($r['mb_datetime'], 0, 10)
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

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$g5['title'] = '추천내역 상세보기';
require_once './admin.head.php';

$colspan = 8;
?>

<div class="local_ov01 local_ov">
    <h3>추천인 정보</h3>
    <table class="frm_table">
        <tr>
            <th width="15%">이름</th>
            <td width="35%"><?php echo $referrer['mb_name'] ?></td>
            <th width="15%">아이디</th>
            <td width="35%"><?php echo $referrer['mb_id'] ?></td>
        </tr>
        <tr>
            <th>추천인코드</th>
            <td><?php echo $referrer['mb_referral_code'] ?></td>
            <th>추천수</th>
            <td><?php echo number_format($total_count) ?>명</td>
        </tr>
        <tr>
            <th>이메일</th>
            <td><?php echo $referrer['mb_email'] ?></td>
            <th>휴대폰</th>
            <td><?php echo $referrer['mb_hp'] ?></td>
        </tr>
        <tr>
            <th>직업</th>
            <td><?php echo $referrer['mb_1'] ?></td>
            <th>치과명/학교명</th>
            <td><?php echo $referrer['mb_3'] ?></td>
        </tr>
        <tr>
            <th>가입일</th>
            <td><?php echo $referrer['mb_datetime'] ?></td>
            <th>상태</th>
            <td><?php echo $referrer['mb_level'] == '3' ? '승인' : '비승인' ?></td>
        </tr>
    </table>
</div>

<div class="local_ov01 local_ov" style="margin-top:20px;">
    <span class="btn_ov01"><span class="ov_txt">총 추천수 </span><span class="ov_num"> <?php echo number_format($total_count) ?>명 </span></span>
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">
<input type="hidden" name="mb_id" value="<?php echo $mb_id ?>">
<label for="sfl" class="sound_only">검색대상</label>
<select name="sfl" id="sfl">
    <option value="mb_name" <?php echo get_selected($sfl, 'mb_name'); ?>>이름</option>
    <option value="mb_id" <?php echo get_selected($sfl, 'mb_id'); ?>>아이디</option>
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input">
<input type="submit" class="btn_submit" value="검색">
<a href="./referral_list.php" class="btn btn_03">목록</a>
<a href="<?php echo './referral_detail.php?mb_id='.$mb_id.'&'.$qstr.'&export=excel'; ?>" class="btn btn_03">엑셀 다운로드</a>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption>추천받은 회원 목록</caption>
    <colgroup>
        <col class="grid_4">
        <col class="grid_3">
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
        <th scope="col">직업</th>
        <th scope="col">치과명/학교명</th>
        <th scope="col">이메일</th>
        <th scope="col">휴대폰</th>
        <th scope="col">상태</th>
        <th scope="col">가입일</th>
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
            <td class="td_name"><?php echo $row['mb_1'] ?></td>
            <td class="td_name"><?php echo $row['mb_3'] ?></td>
            <td class="td_name"><?php echo $row['mb_email'] ?></td>
            <td class="td_name"><?php echo $row['mb_hp'] ?></td>
            <td class="td_name">
                <?php if ($row['mb_level'] == '3') { ?>
                    <span style="color:blue;">승인</span>
                <?php } else { ?>
                    <span style="color:red;">비승인</span>
                <?php } ?>
            </td>
            <td class="td_date"><?php echo substr($row['mb_datetime'], 0, 10) ?></td>
        </tr>
        <?php
    }
    if ($i == 0) {
        echo "<tr><td colspan=\"".$colspan."\" class=\"empty_table\">추천받은 회원이 없습니다.</td></tr>";
    }
    ?>
    </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?mb_id='.$mb_id.'&amp;'.$qstr.'&amp;page='); ?>

<?php
include_once ('./admin.tail.php');
?>
