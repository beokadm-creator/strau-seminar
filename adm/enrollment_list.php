<?php
$sub_menu = "200100";
require_once './_common.php';
print_r($_dev_mode);
auth_check_menu($auth, $sub_menu, 'r');

// $qstr에 type 추가
$qstr = $qstr.'&amp;type='.$type;

$sql_common = " from {$g5['member_table']} ";

$sql_search = " where mb_id <> 'admin' and mb_password <> '' ";

// 타입별 전체 카운트 구하기
$sql = " select 
            sum(case when mb_1 = '치과의사' then 1 else 0 end) as total_count_dent1,
            sum(case when mb_1 = '치과위생사' then 1 else 0 end) as total_count_dent2,
            sum(case when mb_1 = '기공사' then 1 else 0 end) as total_count_dent3,
            sum(case when mb_1 = '학생' then 1 else 0 end) as total_count_student,
            sum(case when mb_1 = '간호조무사' then 1 else 0 end) as total_count_nurse,
            sum(case when mb_1 = '협력사' then 1 else 0 end) as total_count_company,
            sum(case when mb_1 = '에이전시' then 1 else 0 end) as total_count_agency,
            sum(case when mb_1 = '임직원' then 1 else 0 end) as total_count_employee
        {$sql_common} {$sql_search} ";
$row = sql_fetch($sql);
$total_count_dent1 = $row['total_count_dent1'];
$total_count_dent2 = $row['total_count_dent2'];
$total_count_dent3 = $row['total_count_dent3'];
$total_count_student = $row['total_count_student'];
$total_count_nurse = $row['total_count_nurse'];
$total_count_company = $row['total_count_company'];
$total_count_agency = $row['total_count_agency'];
$total_count_employee = $row['total_count_employee'];

$sql = " select count(*) as cnt {$sql_common} {$sql_search} ";
$row = sql_fetch($sql);
$total_view_count = $row['cnt'];



if($type){
    switch ($type) {
        case 'dent1':
            $sql_search .= " and mb_1 = '치과의사' ";
            break;
        case 'dent2':
            $sql_search .= " and mb_1 = '치과위생사' ";
            break;
        case 'dent3':
            $sql_search .= " and mb_1 = '기공사' ";
            break;
        case 'student':
            $sql_search .= " and mb_1 = '학생' ";
            break;
        case 'nurse':
            $sql_search .= " and mb_1 = '간호조무사' ";
            break;
        case 'company':
            $sql_search .= " and mb_1 = '협력사' ";
            break;
        case 'agency':
            $sql_search .= " and mb_1 = '에이전시' ";
            break;
        case 'employee':
            $sql_search .= " and mb_1 = '임직원' ";
            break;
    }
}

if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case 'mb_point':
            $sql_search .= " ({$sfl} >= '{$stx}') ";
            break;
        case 'mb_level':
            $sql_search .= " ({$sfl} = '{$stx}') ";
            break;
        case 'mb_tel':
        case 'mb_hp':
            $sql_search .= " ({$sfl} like '%{$stx}') ";
            break;
        default:
            $sql_search .= " ({$sfl} like '{$stx}%') ";
            break;
    }
    $sql_search .= " ) ";
}

if ($is_admin != 'super') {
    $sql_search .= " and mb_level <= '{$member['mb_level']}' ";
}

if (!$sst) {
    $sst = "mb_datetime";
    $sod = "desc";
}

$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) {
    $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
}
$from_record = ($page - 1) * $rows; // 시작 열을 구함

// 탈퇴회원수
$sql = " select count(*) as cnt {$sql_common} {$sql_search} and mb_leave_date <> '' {$sql_order} ";
$row = sql_fetch($sql);
$leave_count = $row['cnt'];

// 차단회원수
$sql = " select count(*) as cnt {$sql_common} {$sql_search} and mb_intercept_date <> '' {$sql_order} ";
$row = sql_fetch($sql);
$intercept_count = $row['cnt'];

$listall = '<a href="' . $_SERVER['SCRIPT_NAME'] . '" class="ov_listall">전체목록</a>';
$listall .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?type=" class=""><span class="btn_ov01"><span class="ov_txt">총등록수 </span><span class="ov_num"> '.number_format($total_view_count).'명 </span></span></a>';
$listall .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?type=dent1" class=""><span class="btn_ov01"><span class="ov_txt">치과의사 </span><span class="ov_num"> '.number_format($total_count_dent1).'명 </span></span></a>';
$listall .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?type=dent2" class=""><span class="btn_ov01"><span class="ov_txt">치과위생사 </span><span class="ov_num"> '.number_format($total_count_dent2).'명 </span></span></a>';
$listall .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?type=dent3" class=""><span class="btn_ov01"><span class="ov_txt">기공사 </span><span class="ov_num"> '.number_format($total_count_dent3).'명 </span></span></a>';
$listall .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?type=student" class=""><span class="btn_ov01"><span class="ov_txt">학생 </span><span class="ov_num"> '.number_format($total_count_student).'명 </span></span></a>';
$listall .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?type=nurse" class=""><span class="btn_ov01"><span class="ov_txt">간호조무사 </span><span class="ov_num"> '.number_format($total_count_nurse).'명 </span></span></a>';
$listall .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?type=company" class=""><span class="btn_ov01"><span class="ov_txt">협력사 </span><span class="ov_num"> '.number_format($total_count_company).'명 </span></span></a>';
$listall .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?type=agency" class=""><span class="btn_ov01"><span class="ov_txt">에이전시 </span><span class="ov_num"> '.number_format($total_count_agency).'명 </span></span></a>';
$listall .= '<a href="' . $_SERVER['SCRIPT_NAME'] . '?type=employee" class=""><span class="btn_ov01"><span class="ov_txt">임직원 </span><span class="ov_num"> '.number_format($total_count_employee).'명 </span></span></a>';

$g5['title'] = '회원관리';
require_once './admin.head.php';

$sql = " select * {$sql_common} {$sql_search} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$colspan = 17;
?>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    <!-- <span class="btn_ov01"><span class="ov_txt">총등록수 </span><span class="ov_num"> <?php echo number_format($total_count) ?>명 </span></span> -->
</div>

<form id="fsearch" name="fsearch" class="local_sch01 local_sch" method="get">

<label for="sfl" class="sound_only">검색대상</label>


<select name="sfl" id="sfl">
    <option value="mb_name">이름</option>
    <option value="mb_2">면허번호</option>
	<option value="mb_1">직업</option>
    <!-- <option value="entry_code">입장코드</option> -->
</select>
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx"  class="frm_input">
<input type="submit" class="btn_submit" value="검색">

</form>



<form name="fmemberlist" id="fmemberlist" action="./member_list_update.php" onsubmit="return fmemberlist_submit(this);" method="post">
<input type="hidden" name="type" value="<?php echo $type ?>">
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<input type="hidden" name="token" value="">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
		<colgroup>
			<col>
			<col class="grid_4">
			<col class="grid_4">
			<col class="grid_4">
			<col class="grid_1">
			<col class="grid_4">
			<col class="grid_4">
			<col class="grid_4">
			<col class="grid_4">
			<col class="grid_4">
			<col class="grid_1">
			<col class="grid_1">
			<col class="grid_4">
			<col>
		</colgroup>
    <thead>
    <tr>
			<th scope="col" id="mb_list_chk">
				<label for="chkall" class="sound_only">회원 전체</label>
				<input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
			</th>
			<th scope="col" id="mb_list_id">아이디</a></th>
			<th scope="col" id="mb_list_state">상태</a></th>
			<th scope="col" id="mb_list_hp">휴대폰</a></th>
			<th scope="col" id="mb_list_name">이름</a></th>
			<th scope="col" id="mb_list_job">직업</a></th>
			<th scope="col" id="mb_list_dentist">치과명/학교명</a></th>
			<th scope="col" id="mb_list_use">제품사용여부</a></th>
            <th scope="col" id="mb_list_use">가입경로</a></th>
			<th scope="col" id="mb_list_email">이메일</a></th>
			<th scope="col" id="mb_list_email">QA</a></th>
			<th scope="col" id="mb_list_email">1:1</a></th>
      <th scope="col" id="mb_list_id">수강내역</a></th>
			<th scope="col" id="mb_list_date">가입일</a></th>
      <th scope="col" id="mb_list_referral">추천인</a></th>
      <th scope="col" id="mb_list_mailr">관리</a></th>
    </tr>
    
    </thead>
    <tbody>
    <?php
                for ($i = 0; $row = sql_fetch_array($result); $i++) {
                    // 접근가능한 그룹수
                    $sql2 = " select count(*) as cnt from {$g5['group_member_table']} where mb_id = '{$row['mb_id']}' ";
                    $row2 = sql_fetch($sql2);
                    $group = '';
                    if ($row2['cnt']) {
                        $group = '<a href="./boardgroupmember_form.php?mb_id=' . $row['mb_id'] . '">' . $row2['cnt'] . '</a>';
                    }

                    if ($is_admin == 'group') {
                        $s_mod = '';
                    } else {
                        $s_mod = '<a href="./enrollment_form.php?' . $qstr . '&amp;w=u&amp;mb_id=' . $row['mb_id'] . '" class="btn btn_03">수정</a>';
                    }
                    $s_grp = '<a href="./boardgroupmember_form.php?mb_id=' . $row['mb_id'] . '" class="btn btn_02">그룹</a>';

                    $leave_date = $row['mb_leave_date'] ? $row['mb_leave_date'] : date('Ymd', G5_SERVER_TIME);
                    $intercept_date = $row['mb_intercept_date'] ? $row['mb_intercept_date'] : date('Ymd', G5_SERVER_TIME);

                    $mb_nick = get_sideview($row['mb_id'], get_text($row['mb_nick']), $row['mb_email'], $row['mb_homepage']);

                    $mb_id = $row['mb_id'];
                    $leave_msg = '';
                    $intercept_msg = '';
                    $intercept_title = '';
                    if ($row['mb_leave_date']) {
                        $mb_id = $mb_id;
                        $leave_msg = '<span class="mb_leave_msg">탈퇴함</span>';
                    } elseif ($row['mb_intercept_date']) {
                        $mb_id = $mb_id;
                        $intercept_msg = '<span class="mb_intercept_msg">차단됨</span>';
                        $intercept_title = '차단해제';
                    }
                    if ($intercept_title == '') {
                        $intercept_title = '차단하기';
                    }

                    $address = $row['mb_zip1'] ? print_address($row['mb_addr1'], $row['mb_addr2'], $row['mb_addr3'], $row['mb_addr_jibeon']) : '';

                    $bg = 'bg' . ($i % 2);

                    switch ($row['mb_certify']) {
                        case 'hp':
                            $mb_certify_case = '휴대폰';
                            $mb_certify_val = 'hp';
                            break;
                        case 'ipin':
                            $mb_certify_case = '아이핀';
                            $mb_certify_val = '';
                            break;
                        case 'simple':
                            $mb_certify_case = '간편인증';
                            $mb_certify_val = '';
                            break;
                        case 'admin':
                            $mb_certify_case = '관리자';
                            $mb_certify_val = 'admin';
                            break;
                        default:
                            $mb_certify_case = '&nbsp;';
                            $mb_certify_val = 'admin';
                            break;
                    }

                    $mb_7 = ($row['mb_7']!="")?''.$row['mb_7'] : '';
                    $mb_6 = $row['mb_6'].str_replace("기타,", "", $mb_7);


                ?>

			<tr class="<?php echo $bg; ?>">
        <td headers="mb_list_chk" class="td_chk" >
            <input type="hidden" name="mb_id[<?php echo $i ?>]" value="<?php echo $row['mb_id'] ?>" id="mb_id_<?php echo $i ?>">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($row['mb_name']); ?> <?php echo get_text($row['mb_nick']); ?>님</label>
            <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">

            <!-- <input type="hidden" name="mb_id[<?php echo $i ?>]" value="<?php echo $row['no'] ?>" id="mb_id_<?php echo $i ?>">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo get_text($row['mb_name']); ?> <?php echo get_text($row['mb_nick']); ?>님</label>
            <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>"> -->
        </td>
        <td headers="mb_list_id"  class="td_name sv_use"><?php echo $row['mb_id'] ?></td>
				<td headers="mb_list_state"  class="td_state sv_use">
					<select id="mb_level_<?=$row['mb_no']?>"  name="mb_level_<?=$row['mb_no']?>" onchange="fnMemberStatus(this, <?=$row['mb_no']?>);">
						<option value="2" <?=$row['mb_level'] == "2" ? "selected" : ""?>>비승인</option>
						<option value="3" <?=$row['mb_level'] == "3" ? "selected" : ""?>>승인</option>
					</select>
				</td>
				<td headers="mb_list_hp"  class="td_name sv_use"><?php echo $row['mb_hp'] ?></td>
				<td headers="mb_list_name"  class="td_name sv_use"><?php echo $row['mb_name'] ?></td>
				<td headers="mb_list_job"  class="td_name sv_use">
                    <select name="select_mb_1" onchange="fnMemberJob(this, <?=$row['mb_no']?>);">
                        <option value="선택">선택</option>
                        <option value="치과의사" <?php echo $row['mb_1'] == "치과의사" ? "selected" : ""?>>치과의사</option>
                        <option value="치과위생사" <?php echo $row['mb_1'] == "치과위생사" ? "selected" : ""?>>치과위생사</option>
                        <option value="기공사" <?php echo $row['mb_1'] == "기공사" ? "selected" : ""?>>기공사</option>
                        <option value="학생" <?php echo $row['mb_1'] == "학생" ? "selected" : ""?>>학생</option>
                        <option value="간호조무사" <?php echo $row['mb_1'] == "간호조무사" ? "selected" : ""?>>간호조무사</option>
                        <option value="협력사" <?php echo $row['mb_1'] == "협력사" ? "selected" : ""?>>협력사</option>
                        <option value="에이전시" <?php echo $row['mb_1'] == "에이전시" ? "selected" : ""?>>에이전시</option>
                        <option value="임직원" <?php echo $row['mb_1'] == "임직원" ? "selected" : ""?>>임직원</option>
                    </select>
                </td>
				<td headers="mb_list_dentist"  class="td_name sv_use"><?php echo $row['mb_3'] ?></td>
				<td headers="mb_list_use"  class="td_name sv_use"><?php echo $row['mb_5'] ?></td>
                <td headers="mb_list_use"  class="td_name sv_use"><?php echo $mb_6; ?></td>
				<td headers="mb_list_email"  class="td_name sv_use"><?php echo $row['mb_email'] ?></td>
				<td headers="mb_qa_board_cate"  class="td_name sv_use"><?php echo $row['mb_qa_board_cate'] != "" ? 'Y':'' ?></td>
				<td headers="mb_board_comment"  class="td_name sv_use"><?php echo $row['mb_board_comment'] != "" ? 'Y':'' ?></td>
				<td headers="mb_list_id"><a class="poplink_attending_list btn btn_03" poplink="#attending_list" onclick="javascript:mypageList(<?=$row['mb_no']?>);">수강내역</a></td>
				<td headers="mb_list_date"  class="td_name sv_use"><?php echo $row['mb_datetime'] ?></td>
			<td headers="mb_list_referral" class="td_name sv_use">
				<?php
				if ($row['mb_referred_by']) {
					$referrer_sql = " select mb_name from {$g5['member_table']} where mb_referral_code = '{$row['mb_referred_by']}' limit 1 ";
					$referrer_row = sql_fetch($referrer_sql);
					if ($referrer_row) {
						echo $referrer_row['mb_name'];
					} else {
						echo '-';
					}
				} else {
					echo '-';
				}
				?>
			</td>
      <td headers="mb_list_mng" class="td_mng td_mng_s">
				<?php echo $s_mod ?>
				<?php
				// 추천수 조회
				$referral_count = 0;
				if ($row['mb_referral_code']) {
					$count_sql = " select count(*) as cnt from {$g5['member_table']} where mb_referred_by = '{$row['mb_referral_code']}' ";
					$count_row = sql_fetch($count_sql);
					$referral_count = $count_row['cnt'];
				}
				if ($referral_count > 0) {
				?>
				<a href="./referral_detail.php?mb_id=<?php echo $row['mb_id'] ?>" class="btn btn_03" style="margin-top:2px;">추천내역</a>
				<?php } ?>
			</td>
    </tr>
    

    <?php
    }
    if ($i == 0)
        echo "<tr><td colspan=\"".$colspan."\" class=\"empty_table\">자료가 없습니다.</td></tr>";
    ?>
    </tbody>
    </table>
</div>

<div id="attending_list"  class="pop" >
    <div class="bg_layer" style=" background: rgba(5,10,20, .0); width: 100%; height: 100%; position: fixed; top: 0; left: 0; " onClick="$('#attending_list').removeClass('show');"></div>
    <div class="pop-inner register-pop">
        <button type="button" class="btn-close" onClick="$('#attending_list').removeClass('show');" title="창닫기">✕</button>
        <div class="registration-con event-con">
            <table>
                <colgroup>
                    <col width=15%>
                    <col width=50%>
                    <col width=15%>
                    <col width=20%>
                </colgroup>
				<thead>
					<tr>
						<th>번호</th>
						<th>제목</th>
						<th>진행률</th>
						<th>상태</th>
					</tr>
				</thead>
				<tbody id="tdata">
					<tr>
						<td colspan="4">수강내역이 없습니다</td>
					</tr>
				</tbody>
            </table>
        </div>
    </div>
<!--//수강내역 팝업-->

</div>


<div class="btn_fixed_top">
	<a target="hidden_frame" href="/adm/proc/enrollment_list_excel.php?<?php echo $qstr?>" name="act_button2" class="btn btn_01 btn_sm">엑셀저장</a>
	<input type="submit" name="act_button" value="선택삭제" onclick="document.pressed=this.value" class="btn btn_02">
	<!-- <a href="./shop_admin/enrollexcel.php" onclick="return excelform(this.href);" target="_blank" class="btn btn_02">엑셀업로드</a> -->
</div>


</form>
<iframe  name="hidden_frame"  style="display:none;"></iframe>
<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&amp;page='); ?>
<script>

function excelform(url)
{
    var opt = "width=600,height=450,left=10,top=10";
    window.open(url, "win_excel", opt);
    return false;
}

function fmemberlist_submit(f)
{
    if (!is_checked("chk[]")) {
        alert(document.pressed+" 하실 항목을 하나 이상 선택하세요.");
        return false;
    }

    if(document.pressed == "선택삭제") {
        if(!confirm("선택한 자료를 정말 삭제하시겠습니까?")) {
            return false;
        }
    }

    return true;
}

//팝업 코드와이드 클릭
$('.poplink_attending_list').on('click', function(e) {
    e.preventDefault();
    $($(this).attr('poplink')).addClass('show');
    //$("html").css("overflow", "hidden");
});

/* [YSH 2025.03.12] 기존의 프로세스에 launchingShow 데이터도 함께 불러오도록 처리 */
function mypageList(no) {
    //내역조회
    $("#tdata").empty();
    $("#launching").remove();

    var t_html = "";
    var lt_html = "";
    $.ajax({
        url: "/bbs/ajax.mypageList.php",
        type: "POST",
        data: {
            "user_no": no
        },
        success: function (data) {
            var result_json = JSON.parse(data);
            $.each(result_json, function (key, mypage) {
                let mystatus = "수강전";
                if (mypage.percent == "100") {
                    mystatus = "수강완료";
                } else if (mypage.percent > 0) {
                    let mystatus = "수강중";
                }

                if (mypage.type == "C") {
                    t_html += `
                        <tr>
                            <td>${mypage.no}</td>
                            <td>${mypage.subject}</td>
                            <td>${mypage.percent}%</td>
                            <td>${mypage.complete}</td>
                        </tr>
                    `;
                } else if (mypage.type == "L") {
                    lt_html += `
                        <tr>
                            <td>${mypage.no}</td>
                            <td>${mypage.subject}</td>
                            <td>${mypage.percent}%</td>
                            <td>${mypage.complete}</td>
                        </tr>
                    `;
                }
            });

            if (t_html != "" && lt_html != "") {
                $("#tdata").append(t_html);

                var table_html = "";
                table_html += `
                    <table id="launching" style="margin-top:20px;">
                        <colgroup>
                            <col width=15%>
                            <col width=50%>
                            <col width=15%>
                            <col width=20%>
                        </colgroup>
                        <thead>
				        	<tr>
				        		<th>번호</th>
				        		<th>제목</th>
				        		<th>진행률</th>
				        		<th>상태</th>
				        	</tr>
				        </thead>
				        <tbody>
                            ` + lt_html + `
                        </tbody>
                    </table>
                `;

                $("#tdata").parent().after(table_html);

            } else if (t_html != "") {
                $("#tdata").append(t_html);
            } else if (lt_html != "") {
                $("#tdata").append(lt_html);
            } else {
                t_html += `
                    <tr>
                        <td colspan="4">수강내역이 없습니다</td>
                    </tr>
                `;
                $("#tdata").append(t_html);
            }
        },
        error: function (request, status, error) {
            // alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
    });

}

// function putEnroll(no){
//     if(confirm("수정하시겠습니까?")){

//         $.ajax({
//                 url:"/bbs/ajax.putEnroll.php",
//                 type:"POST",
//                 data: {
//                 "user_no": no, 
//                 "license_no" : $("#license_no_"+no).val(),
//                 "entry_code" : $("#entry_code_"+no).val(),
//                     "user_level" : $("#user_level_"+no).val(),
//                     "content3" : $("#content3_"+no).val(),
//                     "check_auth1" : $("#check_auth1_"+no).is(":checked"),
//                     "check_auth2" : $("#check_auth2_"+no).is(":checked"),
//                     "check_auth3" : $("#check_auth3_"+no).is(":checked"),
//                     "check_auth4" : $("#check_auth4_"+no).is(":checked"),
//                     "check_auth5" : $("#check_auth5_"+no).is(":checked")

//                 },
//                 success:function(data){
//                 console.log(data);
//                 alert("수정했습니다.");
//                 },
//                 error:function(request,status,error){
//                     //alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
//                 }
//         });


//     }
// }


// 회원 승인/비승인 상태 변경.
function fnMemberStatus(t, id){
    let prevVal = "3";
    if(t.value == "3") prevVal = "";

    if(confirm("상태를 변경하시겠습니까?")){
        $.ajax({
            url: "/adm/proc/memberStatus.php",
            type: "POST",
            dataType: "json",
            data: {
                type: "mod_status",
                no: id,
                mb_level:$ ("#mb_level_"+id).val()
            },
            success:function(data){
                if(data.error){
                    alert(data.error);
                }
            },
            error:function(){
                console.log("error");
            },
            complete: function(){
                console.log("complete");
            }
        });
    }else{
        $(t).val(prevVal);
    }
}

function fnMemberJob(t, no){
    if(confirm("직업을 변경하시겠습니까?")){
        var mb_1 = $(t).val();

        $.ajax({
            url: "/adm/proc/memberStatus.php",
            type:"POST",
            data: {
                type: "mod_job",
                no: no, 
                mb_1 : mb_1
            },
            success:function(data){
                // console.log(data);
                if(data.error){
                    alert(data.error);
                }
            },
            error:function(request,status,error){
                //alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
            }
        });
    }
}
</script>
<style>
.btn {
	cursor: pointer;
}
</style>
<?php
include_once ('./admin.tail.php');