<?php
$sub_menu = '100310';
require_once './_common.php';

$nw_id = isset($_REQUEST['nw_id']) ? (string)preg_replace('/[^0-9]/', '', $_REQUEST['nw_id']) : 0;

if ($w == "u" || $w == "d") {
    check_demo();
}

if ($w == 'd') {
    auth_check_menu($auth, $sub_menu, "d");
} else {
    auth_check_menu($auth, $sub_menu, "w");
}

check_admin_token();

include $_SERVER['DOCUMENT_ROOT']."/data/util.php";
$filename = getRequest("filename1","file");
if($filename){
	$board_id ="popup";
	if(count($_FILES) > 0){
		//해당 게시판 폴더 존재하는지
		$y_upload_board = $_SERVER['DOCUMENT_ROOT'].'/data/'. $board_id;
		if(is_dir($y_upload_board)){
			//echo "폴더 존재 O"; 
		}else{
			//echo "폴더 존재 X";
			@mkdir($y_upload_board, 0757);
		}


		//해당 년도 폴더 존재하는지
		$upload_y = date("Y");
		$y_upload_board = $_SERVER['DOCUMENT_ROOT'].'/data/'. $board_id .'/'.$upload_y;
		if(is_dir($y_upload_board)){
			//echo "폴더 존재 O"; 
		}else{
			//echo "폴더 존재 X";
			@mkdir($y_upload_board, 0757);
		}
		

		//해당 월 폴더 존재하는지
		$upload_m = date("m");
		$upload_board = $_SERVER['DOCUMENT_ROOT'].'/data/'. $board_id .'/'.$upload_y."/".$upload_m."/";
		if(is_dir($upload_board)){
			//echo "폴더 존재 O"; 
		}else{
			//echo "폴더 존재 X";
			@mkdir($upload_board, 0757);
		}

		$db_upload_board = '/data/'. $board_id .'/'.$upload_y."/".$upload_m."/";
	}

	//새로 업로드
	$fileserver = GetUniqFileName(getbasename($filename), $upload_board , $_FILES['filename'.$i]['tmp_name'],$_FILES['filename'.$i]['size'],100);  //끝에 1이면 1메가 이상 못올림
	$filesize = formatSize(filesize($upload_board.$fileserver));
	
}

$nw_subject = isset($_POST['nw_subject']) ? strip_tags(clean_xss_attributes($_POST['nw_subject'])) : '';
$posts = array();

$check_keys = array(
	'nw_target' => 'str',
	'fileToUpload' => 'str',
	'nw_link' => 'str',
    'nw_device' => 'str',
    'nw_division' => 'str',
    'nw_begin_time' => 'str',
    'nw_end_time' => 'str',
    'nw_disable_hours' => 'int',
    'nw_left' => 'int',
    'nw_top' => 'int',
    'nw_height' => 'int',
    'nw_width' => 'int',
    'nw_content' => 'text',
    'nw_content_html' => 'text',
);

foreach ($check_keys as $key => $val) {
    if ($val === 'int') {
        $posts[$key] = isset($_POST[$key]) ? (int) $_POST[$key] : 0;
    } elseif ($val === 'str') {
        $posts[$key] = isset($_POST[$key]) ? clean_xss_tags($_POST[$key], 1, 1) : 0;
    } else {
        $posts[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : 0;
    }
}

if($filename){
	$sql_common = " 
		nw_target = '{$posts['nw_target']}',
		fileFath = '{$db_upload_board}',
		fileName = '{$fileserver}',
		nw_link = '{$posts['nw_link']}',
		nw_device = '{$posts['nw_device']}',
		nw_division = '{$posts['nw_division']}',
		nw_begin_time = '{$posts['nw_begin_time']}',
		nw_end_time = '{$posts['nw_end_time']}',
		nw_disable_hours = '{$posts['nw_disable_hours']}',
		nw_left = '{$posts['nw_left']}',
		nw_top = '{$posts['nw_top']}',
		nw_height = '{$posts['nw_height']}',
		nw_width = '{$posts['nw_width']}',
		nw_subject = '{$nw_subject}',
		nw_content = '{$posts['nw_content']}',
		nw_content_html = '{$posts['nw_content_html']}' 
	";
}else{
	$sql_common = " 
		nw_target = '{$posts['nw_target']}',
		nw_link = '{$posts['nw_link']}',
		nw_device = '{$posts['nw_device']}',
		nw_division = '{$posts['nw_division']}',
		nw_begin_time = '{$posts['nw_begin_time']}',
		nw_end_time = '{$posts['nw_end_time']}',
		nw_disable_hours = '{$posts['nw_disable_hours']}',
		nw_left = '{$posts['nw_left']}',
		nw_top = '{$posts['nw_top']}',
		nw_height = '{$posts['nw_height']}',
		nw_width = '{$posts['nw_width']}',
		nw_subject = '{$nw_subject}',
		nw_content = '{$posts['nw_content']}',
		nw_content_html = '{$posts['nw_content_html']}' 
	";
}

if ($w == "") {
    $sql = " insert {$g5['new_win_table']} set $sql_common ";
    sql_query($sql);
    $nw_id = sql_insert_id();
    run_event('admin_newwin_created', $nw_id);
} elseif ($w == "u") {
    $sql = " update {$g5['new_win_table']} set $sql_common where nw_id = '$nw_id' ";
    sql_query($sql);
    run_event('admin_newwin_updated', $nw_id);
} elseif ($w == "d") {
    $sql = " delete from {$g5['new_win_table']} where nw_id = '$nw_id' ";
    sql_query($sql);
    run_event('admin_newwin_deleted', $nw_id);
}

if ($w == "d") {
    goto_url('./newwinlist.php');
} else {
    goto_url("./newwinform.php?w=u&amp;nw_id=$nw_id");
}