<?php
$sub_menu = "200100";
require_once './_common.php';

auth_check_menu($auth, $sub_menu, 'w');

$mb = array(
    'mb_certify' => null,
    'mb_adult' => null,
    'mb_sms' => null,
    'mb_intercept_date' => null,
    'mb_id' => null,
    'mb_name' => null,
    'mb_nick' => null,
    'mb_point' => null,
    'mb_email' => null,
    'mb_homepage' => null,
    'mb_hp' => null,
    'mb_tel' => null,
    'mb_zip1' => null,
    'mb_zip2' => null,
    'mb_addr1' => null,
    'mb_addr2' => null,
    'mb_addr3' => null,
    'mb_addr_jibeon' => null,
    'mb_signature' => null,
    'mb_profile' => null,
    'mb_memo' => null,
    'mb_leave_date' => null,
    'mb_1' => null,
    'mb_2' => null,
    'mb_3' => null,
    'mb_4' => null,
    'mb_5' => null,
    'mb_6' => null,
    'mb_7' => null,
    'mb_8' => null,
    'mb_9' => null,
    'mb_10' => null,
    'mb_board_comment' => null,
    'mb_board_comment_cate' => null,
    'mb_qa_board_cate' => null,
);

$sound_only = '';
$required_mb_id = '';
$required_mb_id_class = '';
$required_mb_password = '';
$html_title = '';

if ($w == '') {
    $required_mb_id = 'required';
    $required_mb_id_class = 'required alnum_';
    $required_mb_password = 'required';
    $sound_only = '<strong class="sound_only">필수</strong>';

    $mb['mb_mailling'] = 1;
    $mb['mb_open'] = 1;
    $mb['mb_level'] = $config['cf_register_level'];
    $html_title = '추가';
} elseif ($w == 'u') {
    $mb = get_member($mb_id);
    if (!$mb['mb_id']) {
        alert('존재하지 않는 회원자료입니다.');
    }

    if ($is_admin != 'super' && $mb['mb_level'] > $member['mb_level']) {
        alert('자신보다 권한이 높거나 같은 회원은 수정할 수 없습니다.');
    }

    $required_mb_id = 'readonly';
    $html_title = '수정';

    $mb['mb_name'] = get_text($mb['mb_name']);
    $mb['mb_nick'] = get_text($mb['mb_nick']);
    $mb['mb_email'] = get_text($mb['mb_email']);
    $mb['mb_homepage'] = get_text($mb['mb_homepage']);
    $mb['mb_birth'] = get_text($mb['mb_birth']);
    $mb['mb_tel'] = get_text($mb['mb_tel']);
    $mb['mb_hp'] = get_text($mb['mb_hp']);
    $mb['mb_addr1'] = get_text($mb['mb_addr1']);
    $mb['mb_addr2'] = get_text($mb['mb_addr2']);
    $mb['mb_addr3'] = get_text($mb['mb_addr3']);
    $mb['mb_signature'] = get_text($mb['mb_signature']);
    $mb['mb_recommend'] = get_text($mb['mb_recommend']);
    $mb['mb_profile'] = get_text($mb['mb_profile']);
    $mb['mb_1'] = get_text($mb['mb_1']);
    $mb['mb_2'] = get_text($mb['mb_2']);
    $mb['mb_3'] = get_text($mb['mb_3']);
    $mb['mb_4'] = get_text($mb['mb_4']);
    $mb['mb_5'] = get_text($mb['mb_5']);
    $mb['mb_6'] = get_text($mb['mb_6']);
    $mb['mb_7'] = get_text($mb['mb_7']);
    $mb['mb_8'] = get_text($mb['mb_8']);
    $mb['mb_9'] = get_text($mb['mb_9']);
    $mb['mb_10'] = get_text($mb['mb_10']);
    
    $mb['mb_board_comment'] = get_text($mb['mb_board_comment']);
    $mb['mb_board_comment_cate'] = get_text($mb['mb_board_comment_cate']);
    $mb['mb_qa_board_cate'] = get_text($mb['mb_qa_board_cate']);
} else {
    alert('제대로 된 값이 넘어오지 않았습니다.');
}

// 본인확인방법
switch ($mb['mb_certify']) {
    case 'simple':
        $mb_certify_case = '간편인증';
        $mb_certify_val = 'simple';
        break;
    case 'hp':
        $mb_certify_case = '휴대폰';
        $mb_certify_val = 'hp';
        break;
    case 'ipin':
        $mb_certify_case = '아이핀';
        $mb_certify_val = 'ipin';
        break;
    case 'admin':
        $mb_certify_case = '관리자 수정';
        $mb_certify_val = 'admin';
        break;
    default:
        $mb_certify_case = '';
        $mb_certify_val = 'admin';
        break;
}

// 본인확인
$mb_certify_yes  =  $mb['mb_certify'] ? 'checked="checked"' : '';
$mb_certify_no   = !$mb['mb_certify'] ? 'checked="checked"' : '';

// 성인인증
$mb_adult_yes       =  $mb['mb_adult']      ? 'checked="checked"' : '';
$mb_adult_no        = !$mb['mb_adult']      ? 'checked="checked"' : '';

//메일수신
$mb_mailling_yes    =  $mb['mb_mailling']   ? 'checked="checked"' : '';
$mb_mailling_no     = !$mb['mb_mailling']   ? 'checked="checked"' : '';

// SMS 수신
$mb_sms_yes         =  $mb['mb_sms']        ? 'checked="checked"' : '';
$mb_sms_no          = !$mb['mb_sms']        ? 'checked="checked"' : '';

// 정보 공개
$mb_open_yes        =  $mb['mb_open']       ? 'checked="checked"' : '';
$mb_open_no         = !$mb['mb_open']       ? 'checked="checked"' : '';

if (isset($mb['mb_certify'])) {
    // 날짜시간형이라면 drop 시킴
    if (preg_match("/-/", $mb['mb_certify'])) {
        sql_query(" ALTER TABLE `{$g5['member_table']}` DROP `mb_certify` ", false);
    }
} else {
    sql_query(" ALTER TABLE `{$g5['member_table']}` ADD `mb_certify` TINYINT(4) NOT NULL DEFAULT '0' AFTER `mb_hp` ", false);
}

if (isset($mb['mb_adult'])) {
    sql_query(" ALTER TABLE `{$g5['member_table']}` CHANGE `mb_adult` `mb_adult` TINYINT(4) NOT NULL DEFAULT '0' ", false);
} else {
    sql_query(" ALTER TABLE `{$g5['member_table']}` ADD `mb_adult` TINYINT NOT NULL DEFAULT '0' AFTER `mb_certify` ", false);
}

// 지번주소 필드추가
if (!isset($mb['mb_addr_jibeon'])) {
    sql_query(" ALTER TABLE {$g5['member_table']} ADD `mb_addr_jibeon` varchar(255) NOT NULL DEFAULT '' AFTER `mb_addr2` ", false);
}

// 건물명필드추가
if (!isset($mb['mb_addr3'])) {
    sql_query(" ALTER TABLE {$g5['member_table']} ADD `mb_addr3` varchar(255) NOT NULL DEFAULT '' AFTER `mb_addr2` ", false);
}

// 중복가입 확인필드 추가
if (!isset($mb['mb_dupinfo'])) {
    sql_query(" ALTER TABLE {$g5['member_table']} ADD `mb_dupinfo` varchar(255) NOT NULL DEFAULT '' AFTER `mb_adult` ", false);
}

// 이메일인증 체크 필드추가
if (!isset($mb['mb_email_certify2'])) {
    sql_query(" ALTER TABLE {$g5['member_table']} ADD `mb_email_certify2` varchar(255) NOT NULL DEFAULT '' AFTER `mb_email_certify` ", false);
}

// 본인인증 내역 테이블 정보가 dbconfig에 없으면 소셜 테이블 정의
if (!isset($g5['member_cert_history'])) {
    $g5['member_cert_history_table'] = G5_TABLE_PREFIX . 'member_cert_history';
}
// 멤버 본인인증 정보 변경 내역 테이블 없을 경우 생성
if (isset($g5['member_cert_history_table']) && !sql_query(" DESC {$g5['member_cert_history_table']} ", false)) {
    sql_query(
        " CREATE TABLE IF NOT EXISTS `{$g5['member_cert_history_table']}` (
                    `ch_id` int(11) NOT NULL auto_increment,
                    `mb_id` varchar(20) NOT NULL DEFAULT '',
                    `ch_name` varchar(255) NOT NULL DEFAULT '',
                    `ch_hp` varchar(255) NOT NULL DEFAULT '',
                    `ch_birth` varchar(255) NOT NULL DEFAULT '',
                    `ch_type` varchar(20) NOT NULL DEFAULT '',
                    `ch_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
                    PRIMARY KEY (`ch_id`),
                    KEY `mb_id` (`mb_id`)
                ) ",
        true
    );
}

$mb_cert_history = '';
if (isset($mb_id) && $mb_id) {
    $sql = "select * from {$g5['member_cert_history_table']} where mb_id = '{$mb_id}' order by ch_id asc";
    $mb_cert_history = sql_query($sql);
}

if ($mb['mb_intercept_date']) {
    $g5['title'] = "차단된 ";
} else {
    $g5['title'] .= "";
}
$g5['title'] .= '회원 ' . $html_title;
require_once './admin.head.php';

// add_javascript('js 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_javascript(G5_POSTCODE_JS, 0);    //다음 주소 js
?>

<form name="fmember" id="fmember" action="./enrollment_form_update.php" onsubmit="return fmember_submit(this);" method="post" enctype="multipart/form-data">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="mb_level" value="<?php echo $mb['mb_level'] ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="token" value="">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?></caption>
            <colgroup>
                <col class="grid_4">
                <col>
                <col class="grid_4">
                <col>
            </colgroup>
            <tbody>
                <tr>
                    <th scope="row"><label for="mb_id">아이디<?php echo $sound_only ?></label></th>
                    <td>
                        <input type="text" name="mb_id" value="<?php echo $mb['mb_id'] ?>" id="mb_id" <?php echo $required_mb_id ?> class="frm_input <?php echo $required_mb_id_class ?>" size="15" maxlength="20">
                        <!-- <?php if ($w == 'u') { ?><a href="./boardgroupmember_form.php?mb_id=<?php echo $mb['mb_id'] ?>" class="btn_frmline">접근가능그룹보기</a><?php } ?> -->
                    </td>
                    <th scope="row"><label for="mb_password">비밀번호<?php echo $sound_only ?></label></th>
                    <td>
                        <div>
                        <input type="password" name="mb_password" id="mb_password" <?php echo $required_mb_password ?> class="frm_input <?php echo $required_mb_password ?>" size="15" maxlength="20">
                        </div>
                        <div id="mb_password_captcha_wrap" style="display:none">
                            <?php
                            require_once G5_CAPTCHA_PATH . '/captcha.lib.php';
                            $captcha_html = captcha_html();
                            $captcha_js   = chk_captcha_js();
                            echo $captcha_html;
                            ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mb_name">이름(실명)<strong class="sound_only">필수</strong></label></th>
                    <td><input type="text" name="mb_name" value="<?php echo $mb['mb_name'] ?>" id="mb_name" required class="required frm_input" size="15" maxlength="20"></td>
                    <th scope="row"><label for="mb_board_comment">QA메일 수신</label></th>
                    <td>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <select name="mb_board_comment" id="mb_board_comment" onchange="get_board_cate(this)">
                                <option value="">선택하세요</option>
                                <?
                                $sql = " SELECT * FROM {$g5['board_table']} order by bo_table ASC ";
                                $result = sql_query($sql);
                                while ($row = sql_fetch_array($result)) {
                                    $selected = $mb['mb_board_comment'] == $row['bo_table'] ? 'selected' : '';
                                ?>
                                    <option data-use_category="<?=$row['bo_use_category']?>" value="<?=$row['bo_table']?>" <?=$mb['mb_board_comment']?> <?=$selected?>><?=$row['bo_subject']?>(<?=$row['bo_table']?>)</option>
                                <? } ?>
                            </select>
                            <div id="mb_board_comment_cate_list" style="display: flex">
                                <!-- <select name="mb_board_comment_cate" id="mb_board_comment_cate">
                                    <option value="">선택하세요</option>
                                </select> -->
                                <div id="mb_board_comment_cate" style="padding: 5px 0;">
                                    <!-- checkbox 목록화 -->
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mb_1">직업<strong class="sound_only">필수</strong></label></th>
                    <td>
                        <!-- <input type="text" name="mb_1" value="<?php echo $mb['mb_1'] ?>" id="mb_1" required class="required frm_input" size="15" maxlength="20"> -->
                        <select name="mb_1" id="mb_1" >
                            <option value="">선택</option>
                            <option value="치과의사" <?php echo $mb['mb_1'] == "치과의사" ? "selected" : ""?>>치과의사</option>
                            <option value="치과위생사" <?php echo $mb['mb_1'] == "치과위생사" ? "selected" : ""?>>치과위생사</option>
                            <option value="기공사" <?php echo $mb['mb_1'] == "기공사" ? "selected" : ""?>>기공사</option>
                            <option value="학생" <?php echo $mb['mb_1'] == "학생" ? "selected" : ""?>>학생</option>
                            <option value="간호조무사" <?php echo $mb['mb_1'] == "간호조무사" ? "selected" : ""?>>간호조무사</option>
                            <option value="협력사" <?php echo $mb['mb_1'] == "협력사" ? "selected" : ""?>>협력사</option>
                            <option value="에이전시" <?php echo $mb['mb_1'] == "에이전시" ? "selected" : ""?>>에이전시</option>
                            <option value="임직원" <?php echo $mb['mb_1'] == "임직원" ? "selected" : ""?>>임직원</option>
                        </select>
                    </td>
                    <th scope="row"><label>1:1문의 수신</label></th>
                    <td>
                        <div>
                                <?
                                $sql = " SELECT * FROM {$g5['qa_config_table']} ";
                                $qa_config = sql_fetch($sql);
                                $qa_category = explode('|', $qa_config['qa_category']);
                                for($i=0; $i<count($qa_category); $i++) {
                                    $checked = "";
                                    // $mb['mb_qa_board_cate'] 에 $qa_category[$i] 포함되면 checked
                                    $mb_qa_board_cate = explode('|', $mb['mb_qa_board_cate']);
                                    if (in_array($qa_category[$i], $mb_qa_board_cate)) {
                                        $checked = 'checked';
                                    }

                                ?>
                                    <!-- checkbox 변경 label 감싸기 -->
                                    <input type="checkbox" name="mb_qa_board_cate[]" id="mb_qa_board_cate_<?=$i?>" value="<?=$qa_category[$i]?>" <?=$checked?>>
                                    <label for="mb_qa_board_cate_<?=$i?>"><?=$qa_category[$i]?></label>
                                <? } ?>

                        </div>
                    </td>
                </tr>
								<tr>
                    <th scope="row"><label for="mb_hp">휴대폰번호</label></th>
                    <td colspan="3"><input type="text" name="mb_hp" value="<?php echo $mb['mb_hp'] ?>" id="mb_hp" class="frm_input" size="15" maxlength="20"></td>
                </tr>
								<tr>
                    <th scope="row"><label for="mb_2">면허번호<strong class="sound_only">필수</strong></label></th>
                    <td colspan="3"><input type="text" name="mb_2" value="<?php echo $mb['mb_2'] ?>" id="mb_2" required class="required frm_input" size="15" maxlength="20"></td>
                </tr>
								<tr>
                    <th scope="row"><label for="mb_3">치과명/학교명<strong class="sound_only">필수</strong></label></th>
                    <td colspan="3"><input type="text" name="mb_3" value="<?php echo $mb['mb_3'] ?>" id="mb_3" required class="required frm_input" size="15" maxlength="20"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="mb_email">E-mail<strong class="sound_only">필수</strong></label></th>
                    <td colspan="3"><input type="text" name="mb_email" value="<?php echo $mb['mb_email'] ?>" id="mb_email" maxlength="100" required class="required frm_input email" size="30"></td>
                </tr>
                <?php
                $rec_info = null;
                if (!empty($mb['mb_recommend'])) {
                    $rec_info = sql_fetch("SELECT mb_name, mb_3, mb_email FROM {$g5['member_table']} WHERE mb_id = '{$mb['mb_recommend']}'");
                } elseif (isset($mb['mb_referred_by']) && $mb['mb_referred_by'] !== '') {
                    $rec_info = sql_fetch("SELECT mb_name, mb_3, mb_email FROM {$g5['member_table']} WHERE mb_referral_code = '{$mb['mb_referred_by']}'");
                }
                if ($rec_info && (isset($rec_info['mb_name']) || isset($rec_info['mb_3']) || isset($rec_info['mb_email']))) {
                ?>
                <tr>
                    <th scope="row">추천인</th>
                    <td colspan="3">
                        <span>이름: <?php echo isset($rec_info['mb_name']) ? htmlspecialchars($rec_info['mb_name']) : '-'; ?></span>
                        <span style="margin-left:12px;">소속: <?php echo isset($rec_info['mb_3']) ? htmlspecialchars($rec_info['mb_3']) : '-'; ?></span>
                        <span style="margin-left:12px;">이메일: <?php echo isset($rec_info['mb_email']) ? htmlspecialchars($rec_info['mb_email']) : '-'; ?></span>
                    </td>
                </tr>
                <?php } ?>
                <tr>
                    <th scope="row"><label for="mb_4">지역<strong class="sound_only">필수</strong></label></th>
                    <td colspan="3"><input type="text" name="mb_4" value="<?php echo $mb['mb_4'] ?>" id="mb_4" required class="required frm_input" size="15" maxlength="20"></td>
                </tr>
								<tr>
                    <th scope="row"><label for="mb_5">제품사용여부<strong class="sound_only">필수</strong></label></th>
                    <td colspan="3"><input type="text" name="mb_5" value="<?php echo $mb['mb_5'] ?>" id="mb_5" required class="required frm_input" size="15" maxlength="20"></td>
                </tr>
                <tr>
                    <th scope="row">배송정보</th>
                    <td colspan="3">
                        <label for="mb_shipping_postcode">우편번호</label><br>
                        <input type="text" name="mb_shipping_postcode" value="<?php echo isset($mb['mb_shipping_postcode']) ? $mb['mb_shipping_postcode'] : '' ?>" id="mb_shipping_postcode" class="frm_input" size="8" maxlength="6">
                        <button type="button" class="btn_frmline" onclick="win_zip('fmember', 'mb_shipping_postcode', 'mb_shipping_address', 'mb_shipping_detail', '', 'mb_shipping_jibeon');">주소 검색</button><br>
                        <label for="mb_shipping_address">기본주소</label><br>
                        <input type="text" name="mb_shipping_address" value="<?php echo isset($mb['mb_shipping_address']) ? $mb['mb_shipping_address'] : '' ?>" id="mb_shipping_address" class="frm_input" size="60"><br>
                        <label for="mb_shipping_detail">상세주소</label><br>
                        <input type="text" name="mb_shipping_detail" value="<?php echo isset($mb['mb_shipping_detail']) ? $mb['mb_shipping_detail'] : '' ?>" id="mb_shipping_detail" class="frm_input" size="60">
                        <input type="hidden" name="mb_shipping_jibeon" value="<?php echo isset($mb['mb_shipping_jibeon']) ? $mb['mb_shipping_jibeon'] : '' ?>">
                    </td>
                </tr>

                <?php
                //소셜계정이 있다면
                if (function_exists('social_login_link_account') && $mb['mb_id']) {
                    if ($my_social_accounts = social_login_link_account($mb['mb_id'], false, 'get_data')) { ?>
                        <tr>
                            <th>소셜계정목록</th>
                            <td colspan="3">
                                <ul class="social_link_box">
                                    <li class="social_login_container">
                                        <h4>연결된 소셜 계정 목록</h4>
                                        <?php foreach ($my_social_accounts as $account) {     //반복문
                                            if (empty($account)) {
                                                continue;
                                            }

                                            $provider = strtolower($account['provider']);
                                            $provider_name = social_get_provider_service_name($provider);
                                        ?>
                                            <div class="account_provider" data-mpno="social_<?php echo $account['mp_no']; ?>">
                                                <div class="sns-wrap-32 sns-wrap-over">
                                                    <span class="sns-icon sns-<?php echo $provider; ?>" title="<?php echo $provider_name; ?>">
                                                        <span class="ico"></span>
                                                        <span class="txt"><?php echo $provider_name; ?></span>
                                                    </span>

                                                    <span class="provider_name"><?php echo $provider_name;   //서비스이름 ?> ( <?php echo $account['displayname']; ?> )</span>
                                                    <span class="account_hidden" style="display:none"><?php echo $account['mb_id']; ?></span>
                                                </div>
                                                <div class="btn_info"><a href="<?php echo G5_SOCIAL_LOGIN_URL . '/unlink.php?mp_no=' . $account['mp_no'] ?>" class="social_unlink" data-provider="<?php echo $account['mp_no']; ?>">연동해제</a> <span class="sound_only"><?php echo substr($account['mp_register_day'], 2, 14); ?></span></div>
                                            </div>
                                        <?php } //end foreach ?>
                                    </li>
                                </ul>
                                <script>
                                    jQuery(function($) {
                                        $(".account_provider").on("click", ".social_unlink", function(e) {
                                            e.preventDefault();

                                            if (!confirm('정말 이 계정 연결을 삭제하시겠습니까?')) {
                                                return false;
                                            }

                                            var ajax_url = "<?php echo G5_SOCIAL_LOGIN_URL . '/unlink.php' ?>";
                                            var mb_id = '',
                                                mp_no = $(this).attr("data-provider"),
                                                $mp_el = $(this).parents(".account_provider");

                                            mb_id = $mp_el.find(".account_hidden").text();

                                            if (!mp_no) {
                                                alert('잘못된 요청! mp_no 값이 없습니다.');
                                                return;
                                            }

                                            $.ajax({
                                                url: ajax_url,
                                                type: 'POST',
                                                data: {
                                                    'mp_no': mp_no,
                                                    'mb_id': mb_id
                                                },
                                                dataType: 'json',
                                                async: false,
                                                success: function(data, textStatus) {
                                                    if (data.error) {
                                                        alert(data.error);
                                                        return false;
                                                    } else {
                                                        alert("연결이 해제 되었습니다.");
                                                        $mp_el.fadeOut("normal", function() {
                                                            $(this).remove();
                                                        });
                                                    }
                                                }
                                            });

                                            return;
                                        });
                                    });
                                </script>

                            </td>
                        </tr>

                <?php
                    }   //end if
                }   //end if

                run_event('admin_member_form_add', $mb, $w, 'table');
                ?>

            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top">
        <a href="<?php echo G5_ADMIN_URL ?>/enrollment_list.php" class="btn btn_02">목록</a>
        <input type="submit" value="확인" class="btn_submit btn" accesskey='s'>
    </div>
</form>

<script>
    function fmember_submit(f) {
        // if (!f.mb_icon.value.match(/\.(gif|jpe?g|png)$/i) && f.mb_icon.value) {
        //     alert('아이콘은 이미지 파일만 가능합니다.');
        //     return false;
        // }

        // if (!f.mb_img.value.match(/\.(gif|jpe?g|png)$/i) && f.mb_img.value) {
        //     alert('회원이미지는 이미지 파일만 가능합니다.');
        //     return false;
        // }

        if( jQuery("#mb_password").val() ){
            <?php echo $captcha_js; // 캡챠 사용시 자바스크립트에서 입력된 캡챠를 검사함 ?>
        }

        return true;
    }

    jQuery(function($){
        $("#captcha_key").prop('required', false).removeAttr("required").removeClass("required");

        $("#mb_password").on("keyup", function(e) {
            var $warp = $("#mb_password_captcha_wrap"),
                tooptipid = "mp_captcha_tooltip",
                $span_text = $("<span>", {id:tooptipid, style:"font-size:0.95em;letter-spacing:-0.1em"}).html("비밀번호를 수정할 경우 캡챠를 입력해야 합니다."),
                $parent = $(this).parent(),
                is_invisible_recaptcha = $("#captcha").hasClass("invisible_recaptcha");

            if($(this).val()){
                $warp.show();
                if(! is_invisible_recaptcha) {
                    $warp.css("margin-top","1em");
                    if(! $("#"+tooptipid).length){ $parent.append($span_text) }
                }
            } else {
                $warp.hide();
                if($("#"+tooptipid).length && ! is_invisible_recaptcha){ $parent.find("#"+tooptipid).remove(); }
            }
        });
        fn_cate_call('<?=$mb['mb_board_comment']?>', '<?=$mb['mb_board_comment_cate']?>');
    });

    function get_board_cate(t){
        var use_category = $(t).find("option:selected").data("use_category");
        // $("#mb_board_comment_cate option").remove();
        $("#mb_board_comment_cate").html("");
        
        if(use_category == "1"){
            fn_cate_call($(t).val(), "");
        }else{
            // 맨 앞에 추가하자
            // $("#mb_board_comment_cate").prepend("<option value=''>선택하세요</option>");
        }
    }

    function fn_cate_call(bo_table, mb_board_comment_cate){
        // $("#mb_board_comment_cate option").remove();
        $("#mb_board_comment_cate").html("");

        $.ajax({
            url: "./ajax.common.php",
            type: "POST",
            data: {
                "bo_table": bo_table,
                "mb_board_comment_cate": mb_board_comment_cate,
                "type": "get_category"
            },
            dataType: "json",
            success: function(data){
                if(data.result == "success"){
                    // $("#mb_board_comment_cate").append("<option value=''>선택하세요</option>");
                    $.each(data.result_data, function(key, val){
                        // console.log(val);
                        // $("#mb_board_comment_cate").append("<option value='"+val.name+"' "+val.selected+">"+val.name+"</option>");
                        // checkbox로 변경, label로 감싸자
                        $("#mb_board_comment_cate").append("<label><input type='checkbox' name='mb_board_comment_cate[]' value='"+val.name+"' "+val.checked+"> "+val.name+" </label>");
                    });
                }
            },
            error: function(){
                alert("카테고리를 불러오는데 실패하였습니다.");
            },
            complete: function(){
                // 맨 앞에 추가하자
                // if($("#mb_board_comment_cate option").length == 0){
                //     console.log("추가");
                //     $("#mb_board_comment_cate").prepend("<option value=''>선택하세요</option>");
                // }
            }
        });
    }
</script>
<?php
run_event('admin_member_form_after', $mb, $w);

require_once './admin.tail.php';
