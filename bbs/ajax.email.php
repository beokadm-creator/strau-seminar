<?php
include_once('./_common.php');
include_once(G5_LIB_PATH.'/json.lib.php');
require_once G5_LIB_PATH.'/ray_util.lib.php';

$common_util = new Ray_Util();
$common_util->chkUrl();

$data = array();
$data['error'] = "";

if($data['error'])
    die($common_util->json_encode($data));


$type = $_POST['type'];
$arr_type = array('mem_email_auth', 'mem_email_auth_check');
if(!in_array($type, $arr_type)) {
    $data['error'] = '올바른 방법으로 이용해 주십시오.';
    die($common_util->json_encode($data));
}

if($type == 'mem_email_auth') {
    set_session('mem_auth_str', "");
    $wr_email      = isset($_POST['wr_email']) ? $_POST['wr_email'] : "";
    if($wr_email == ""){
        $data['error'] = '올바른 방법으로 이용해 주십시오.';
    }else{
        $rand_str = $common_util->random_string(); // 랜덤 문자열 인증번호
        set_session('mem_auth_str', $rand_str);

        $to_mail = $wr_email; // 메일 받는 사람

        $subject = '[스트라우만] 인증번호를 안내드립니다.';
        include_once(G5_LIB_PATH.'/mailer.lib.php');

        ob_start();
        include_once ('./email_auth.php');
        $content = ob_get_contents();
        ob_end_clean();

        $content = str_replace("{인증번호}", $rand_str, $content);
        mailer("스트라우만", COMMON_SEND_EMAIL, $to_mail, $subject, $content, 1);
        
        // $common_util->send_mail("스트라우만", COMMON_SEND_EMAIL,$to_mail,$to_mail,"","",$subject,$content);

        $data['result'] = 'success'; 
    }
}

if($type == 'mem_email_auth_check') {
    $ss_auth_str = get_session('mem_auth_str');

    if($ss_auth_str == ""){
        $data['error'] = '올바른 방법으로 이용해 주십시오.';
    }else{
        $auth_str = isset($_POST['auth_str']) ? $_POST['auth_str'] : "";
        if($auth_str == ""){
            $data['error'] = '올바른 방법으로 이용해 주십시오.';
        }else{
            if($auth_str == $ss_auth_str){
                $data['result'] = 'success';
            }else{
                $data['result'] = 'diff';
            }
        }
    }
}

die($common_util->json_encode($data));

?>

