<?php
//$sub_menu = '300600';
$member['mb_id'] = "user";
include_once('./_common.php');


$error_msg = '';

$event = isset($_POST['event']) ? preg_replace('/[^a-z0-9_]/i', '', $_POST['event']) : '';
$user_name = isset($_POST['name']) ? $_POST['name'] : '';
$check2_1 = isset($_POST['check2_1']);
$check2_2 = isset($_POST['check2_2']);
$check3_1 = isset($_POST['check3_1']);
$check3_2 = isset($_POST['check3_2']);
$ctlR_MK_YN = isset($_POST['ctlR_MK_YN']);
$ctlR_PI_YN = isset($_POST['ctlR_PI_YN']);
$ctlR_MK_YN2 = isset($_POST['ctlR_MK_YN2']);
$ctlR_PI_YN2 = isset($_POST['ctlR_PI_YN2']);
//alert($event);
$now= date("Y-m-d\TH:i:s");

$file_name = isset($_POST['file_name']) ? preg_replace(array("#[\\\]+$#", "#(<\?php|<\?)#i"), "", substr($_POST['file_name'], 0, 255)) : '';
//alert($file_name);

	@mkdir(G5_DATA_PATH."/content", G5_DIR_PERMISSION);
	@chmod(G5_DATA_PATH."/content", G5_DIR_PERMISSION);

	if( $file_name ){
		$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
		if( ! $file_ext || ! in_array($file_ext, array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'tif')) || ! preg_match('/^.*\.(jpg|jpeg|gif|hpng|bmp|tif)$/i', $file_name) ) {
			alert('상단 파일 경로의 확장자는 jpg, jpeg, gif, hpng, bmp, tif 만 허용합니다.');
		}
	}

	//if( $file_name && ! is_include_path_check($file_name, 1) ){
	//	$file_name = '';
	//	$error_msg = '/data/file/ 또는 /data/editor/ 포함된 문자를 상단 파일 경로에 포함시킬수 없습니다.';
	//}

	if( function_exists('filter_input_include_path') ){
		$file_name = filter_input_include_path($file_name);
	}

	//파일 고유값 만들기
	$file_name = strtotime("Now")."_".$file_name;

	if ($_FILES['co_himg']['name'])
    {
        $dest_path = G5_DATA_PATH."/content/".$file_name;
        @move_uploaded_file($_FILES['co_himg']['tmp_name'], $dest_path);
        @chmod($dest_path, G5_FILE_PERMISSION);
    }

$sql_colum = "(event, name, tel, tel2, tel3, zip, addr1, addr2, content1, content2, content3, file_name, policy_use, policy_consign, ctlR_MK_YN, ctlR_PI_YN, reg_date)";
if($event == 1) {
	//사전 등록 여부 체크
	$where1 = " where event=1 and name ='{$_POST['name']}' and tel = {$_POST['tel1']} and tel2 = {$_POST['tel2']} and tel3 = {$_POST['tel3']}";
	$sql1 = "select count(*) as cnt from g5_event  {$where1} ";
	$row1 = sql_fetch($sql1);
	$sql_check1 = $row1['cnt'];
	//alert($sql_check1);
	if($sql_check1 != 0) {
		$text = $user_name."님은 이미 사전접수에 신청하셨습니다.";
		alert($text, 'https://festa.or.kr/');
	} else {
		$sql_common = "($event,
						'{$_POST['name']}',
						'{$_POST['tel1']}',
						'{$_POST['tel2']}',
						'{$_POST['tel3']}',
						'{$_POST['zip']}',
						'{$_POST['addr1']}',
						'{$_POST['addr2']}',
						'{$_POST['kit']}',
						'',
						'{$_POST['dogText']}',
						'',
						'$check2_1',
						'$check2_2',
						'$ctlR_MK_YN',
						'$ctlR_PI_YN','$now') ";
	}

}else if($event == 2) {
	
	$sql_common = "($event,
					'{$_POST['name']}',
					'{$_POST['tel1']}',
					'{$_POST['tel2']}',
					'{$_POST['tel3']}',
					'','','',
					'{$_POST['picPlace']}',
					'{$_POST['title']}',
					'{$_POST['content']}',
					'$file_name',
					'$check3_1',
					'$check3_2',
					'$ctlR_MK_YN2',
					'$ctlR_PI_YN2',
					'$now') ";

}else if($event == 3) {
	$where = "where event=1 and name = '{$_POST['name']}' and tel3 = {$_POST['tel3']}"; 
	$sql = "select count(*) as cnt from g5_event  {$where} ";
	$row = sql_fetch($sql);
	$sql_check = $row['cnt'];
	//alert($sql_check);
	if($sql_check !=0 ) {
		$sql_common = "($event,'{$_POST['name']}','','','{$_POST['tel3']}','','','','','','','','','','','','$now')";
	} else{
		alert("입력하신 정보가 올바르지 않습니다. 다시 확인 후 입력해주시기 바랍니다. 아직 사전등록을 안하신분이라면 화면 왼쪽에 위치한 '사전등록' 후 이용해주시기 바랍니다.");
        goto_url("http://festa.openhaja.com/");
	}
}

    $sql = " insert into g5_event {$sql_colum} values {$sql_common} ";
    sql_query($sql);
	//alert($sql);

    if( $error_msg ){
        alert($error_msg, 'http://festa.openhaja.com/');
    } else {
		if($event == 3) {
			
			alert('행사장으로 입장합니다', '/event.php');
		}else{
			alert('등록되었습니다.');
			goto_url('http://festa.openhaja.com/');
		}
    }

