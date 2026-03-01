<?php

    include_once('./_common.php');

    check_demo();

    if(!$is_admin)
        alert('접근 권한이 없습니다.', G5_URL);

    auth_check($auth[$sub_menu], 'd');

    $tmp_array = array();
    $tmp_array = $_POST['chk'];
    $qstr      = $_POST['qstr'];

    $chk_count = count($tmp_array);

    for ($i=$chk_count-1; $i>=0; $i--) {

        //--- 변수 재설정
        $arrVal     = explode('|', $tmp_array[$i]);
        $bo_table   = $arrVal[0];
        $comment_id = (int) $arrVal[1];

        //--- 게시판 기본 변수 재설정
        $board = sql_fetch(" select * from {$g5['board_table']} where bo_table = '$bo_table' ");
        if ($board['bo_table']) {
            $write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름
            if (isset($comment_id) && $comment_id) {
                //$write = sql_fetch(" select * from $write_table where wr_id = '$wr_id' ");
                $write = sql_fetch(" select * from {$write_table} where wr_id = '{$comment_id}' ");
            }
        }

        //--- 재설정 된 변수를 이용해서 스킨명, url 다시 할당
        if (G5_IS_MOBILE) {
            $board_skin_path = get_skin_path('board', $board['bo_mobile_skin']);
            $board_skin_url  = get_skin_url('board', $board['bo_mobile_skin']);
        } else {
            $board_skin_path = get_skin_path('board', $board['bo_skin']);
            $board_skin_url  = get_skin_url('board', $board['bo_skin']);
        }



        @include_once($board_skin_path . '/delete_comment.head.skin.php');


        if (!$write['wr_id'] || !$write['wr_is_comment'])
            alert('등록된 코멘트가 없거나 코멘트 글이 아닙니다.');

        $len = strlen($write['wr_comment_reply']);
        if ($len < 0) $len = 0;
        $comment_reply = substr($write['wr_comment_reply'], 0, $len);

        $sql = " select count(*) as cnt from {$write_table}
                            where wr_comment_reply like '{$comment_reply}%'
                            and wr_id <> '{$comment_id}'
                            and wr_parent = '{$write[wr_parent]}'
                            and wr_comment = '{$write[wr_comment]}'
                            and wr_is_comment = 1 ";
        $row = sql_fetch($sql);

        // 코멘트 포인트 삭제
        if (!delete_point($write['mb_id'], $bo_table, $comment_id, '댓글'))
            insert_point($write['mb_id'], $board['bo_comment_point'] * (-1), "{$board['bo_subject']} {$write['wr_parent']}-{$comment_id} 댓글삭제");

        // 코멘트 삭제
        sql_query(" delete from {$write_table} where wr_id = '{$comment_id}' ");

        // 코멘트가 삭제되므로 해당 게시물에 대한 최근 시간을 다시 얻는다.
        $sql = " select max(wr_datetime) as wr_last from {$write_table} where wr_parent = '{$write['wr_parent']}' ";
        $row = sql_fetch($sql);

        // 원글의 코멘트 숫자를 감소
        sql_query(" update {$write_table} set wr_comment = wr_comment - 1, wr_last = '{$row['wr_last']}' where wr_id = '{$write['wr_parent']}' ");

        // 코멘트 숫자 감소
        sql_query(" update {$g5['board_table']} set bo_count_comment = bo_count_comment - 1 where bo_table = '{$bo_table}' ");

        // 새글 삭제
        sql_query(" delete from {$g5['board_new_table']} where bo_table = '{$bo_table}' and wr_id = '{$comment_id}' ");

        // 사용자 코드 실행
        @include_once($board_skin_path . '/delete_comment.skin.php');
        @include_once($board_skin_path . '/delete_comment.tail.skin.php');

        delete_cache_latest($bo_table);
    }

    goto_url('./comment_list.php' . '?page='.$page.'&amp;'.$qstr);
