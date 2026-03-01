<?php
include_once('./_common.php');

$data = array( "error"=> '', "result"=> 'fail', "msg"=> '');

// 관리자만 접근 가능
if($member['mb_level'] != 10) {
    $data['error'] = '관리자만 접근 가능합니다.';
    die(json_encode($data));
}

$type = $_POST['type'];
$arr_type = array(
    'get_category', // 게시판 카테고리 목록
);

if(!in_array($type, $arr_type)) {
    $data['error'] = '올바른 방법으로 이용해 주십시오.';
    die(json_encode($data));
}

// 게시판 카테고리 목록
if($type == "get_category"){

    $bo_table = $_POST['bo_table'];
    $mb_board_comment_cate = $_POST['mb_board_comment_cate'];

    $sql = "SELECT * FROM {$g5['board_table']} WHERE bo_table = '{$bo_table}'";
    $board = sql_fetch($sql);
    $cate_list = explode('|', $board['bo_category_list']);
    $mb_board_comment_cate_arr = array();
    if($mb_board_comment_cate){
        $mb_board_comment_cate_arr = explode('|', $mb_board_comment_cate);
    }

    $cate_arr = array();
    for($i=0; $i<count($cate_list); $i++){
        if($cate_list[$i] == '') continue;

        if(in_array($cate_list[$i], $mb_board_comment_cate_arr)){
            $selected = ' selected ';
            $checked = ' checked ';
        }else{
            $selected = '';
            $checked = '';
        }

        $cate = $cate_list[$i];
        $cate_arr[] = array(
            'name' => $cate,
            'selected' => $selected,
            'checked' => $checked
        );
    }
    $data['result'] = 'success';
    $data['result_data'] = $cate_arr;
    die(json_encode($data));
}