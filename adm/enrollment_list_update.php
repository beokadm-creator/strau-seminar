<?php
$sub_menu = "200100";
include_once('./_common.php');

check_demo();

if (! (isset($_POST['chk']) && is_array($_POST['chk']))) {
    alert($_POST['act_button']." 하실 항목을 하나 이상 체크하세요.");
}

auth_check_menu($auth, $sub_menu, 'w');

check_admin_token();

$mb_datas = array();
$msg = '';

if ($_POST['act_button'] == "선택수정") {

    for ($i=0; $i<count($_POST['chk']); $i++)
    {
        // 실제 번호를 넘김
        $k = isset($_POST['chk'][$i]) ? (int) $_POST['chk'][$i] : 0;
        
       $post_mb_open = isset($_POST['mb_open'][$k]) ? (int) $_POST['mb_open'][$k] : 0;

       $sql = " update {$g5['member_table']}
                   set mb_level = '".$post_mb_level."',
                       mb_intercept_date = '".sql_real_escape_string($post_mb_intercept_date)."',
                       mb_mailling = '".$post_mb_mailling."',
                       mb_sms = '".$post_mb_sms."',
                       mb_open = '".$post_mb_open."',
                       mb_certify = '".sql_real_escape_string($post_mb_certify)."',
                       mb_adult = '{$mb_adult}'
                   where mb_id = '".sql_real_escape_string($mb['mb_id'])."' ";

       sql_query($sql);
       
    }

} else if ($_POST['act_button'] == "선택삭제") {

    for ($i=0; $i<count($_POST['chk']); $i++)
    {
        // 실제 번호를 넘김
        $k = isset($_POST['chk'][$i]) ? (int) $_POST['chk'][$i] : 0;

        echo $_POST['mb_id'][$k];
    }
}

if ($msg)
    //echo '<script> alert("'.$msg.'"); </script>';
    alert($msg);

//run_event('admin_member_list_update', $_POST['act_button'], $mb_datas);

goto_url('./enrollment_list.php?'.$qstr);