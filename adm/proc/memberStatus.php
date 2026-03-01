<?php
include_once('./_common.php');
$sub_menu = "200100"; // 회원 메뉴 권한
include_once(G5_LIB_PATH.'/mailer.lib.php');

$data = array();
$data['error'] = '';

$data['error'] = auth_check_menu($auth, $sub_menu, 'w', true);
if($data['error'])
    die($common_util->json_encode($data));


$type = $_POST['type'];
$arr_type = array('mod_status', 'mod_job');
if(!in_array($type, $arr_type)) {
    $data['error'] = '올바른 방법으로 이용해 주십시오.';
    die($common_util->json_encode($data));
}


if($type == 'mod_status') {
    $no = isset($_POST['no']) ? trim($_POST['no']) : '';
    
    if($no == ""){
        $data['error'] = '올바른 방법으로 이용해 주십시오.';
    }else{

        $set_data = array();
        $set_data[] = " mb_level = '{$mb_level}' ";
        $sql_set = ' set '.implode(' , ', $set_data);

        $where = array();
        $where[] = " mb_no = '{$no}' ";
        $sql_where = ' where '.implode(' and ', $where);

        $sql = " update g5_member {$sql_set} {$sql_where} "; 
        sql_query($sql,true);

        $data['result'] = 'success';

        // mail send
        $sql = " select * from g5_member where mb_no = '{$no}' ";
        $row = sql_fetch($sql);

        $mb_name = $row['mb_name'];
        $mb_email = $row['mb_email'];
        $subject = '['.$config['cf_title'].'] 회원가입승인 완료안내';


        //include_once ('../register_form_update_mail4.php');
        $html = '<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>[스트라우만 캠퍼스]</title>
</head>

<body>

<div style="margin:30px auto;width:600px;border:10px solid #f7f7f7">
    <div style="border:1px solid #dedede">
        <h1 style="padding:30px 30px 0;background:#f7f7f7;color:#555;font-size:1.4em">
            안녕하세요,<b>'.$mb_name.'</b>님.
        </h1>
        <span style="display:block;padding:10px 30px 30px;background:#f7f7f7;text-align:right">
            <a href="'.G5_URL.'" target="_blank">'.$config['cf_title'].'</a>
        </span>
        <p style="margin:20px 0 0;padding:30px 30px 50px;height:auto !important;height:150px;border-bottom:1px solid #eee">
            <b>'.$mb_name.'</b> 회원님의 회원가입이 승인되었습니다.<br>
			이제 스트라우만 캠퍼스의 모든 콘텐츠를 이용하실 수 있습니다.
			<br><br>
			로그인 후 다양한 강의와 콘텐츠를 마음껏 즐겨보세요.<br>
            궁금한 점이나 도움이 필요하시면 언제든지 고객 지원팀에 문의해 주세요.<br><br>
            감사합니다.<br>
			스트라우만 캠퍼스팀 드림
        </p>
		<p style="margin:20px 0 0;padding:0 30px 50px;height:auto !important;height:100px;border-bottom:1px solid #eee">
            <b>스트라우만 대표번호</b><br>
			02-2149-3800
        </p>
        <a href="'.G5_BBS_URL.'/login.php" target="_blank" style="display:block;padding:30px 0;background:#484848;color:#fff;text-decoration:none;text-align:center"> 로그인</a>
    </div>
</div>

</body>
</html>';

        if($mb_level == 3) {
            $html = str_replace("<?php echo G5_URL ?>", $config['cf_url'], $html);
            $html = str_replace("<?php echo $config[cf_title] ?>", $config['cf_title'], $html);
            $html = str_replace("<?php echo $mb_name ?>", $mb_name, $html);

            mailer($config['cf_admin_email_name'], $config['cf_admin_email'], $mb_email, $subject, $html, 1);
        }

        $data['result'] = 'success';


    }
    
}

if($type == 'mod_job') {
    $no = isset($_POST['no']) ? trim($_POST['no']) : '';
    $mb_1 = isset($_POST['mb_1']) ? trim($_POST['mb_1']) : '';
    
    if($no == "" || $mb_1 == ""){
        $data['error'] = '올바른 방법으로 이용해 주십시오.';
    }else{

        $set_data = array();
        $set_data[] = " mb_1 = '{$mb_1}' ";
        $sql_set = ' set '.implode(' , ', $set_data);

        $where = array();
        $where[] = " mb_no = '{$no}' ";
        $sql_where = ' where '.implode(' and ', $where);

        $sql = " update g5_member {$sql_set} {$sql_where} "; 
        sql_query($sql,true);

        $data['result'] = 'success';
    }
    
}

die($common_util->json_encode($data));