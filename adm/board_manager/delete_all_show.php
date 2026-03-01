<?php

    include_once('./_common.php');

    if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

    if (!$is_admin)
        alert('접근 권한이 없습니다.', G5_URL);

    $tmp_array = array();
    $tmp_array = $_POST['chk'];
    $qstr      = $_POST['qstr'];

    $chk_count = count($tmp_array);


    for ($i=$chk_count-1; $i>=0; $i--)
    {

        $count_write = 0;
        $count_comment = 0;

        //--- 변수 재설정
        $arrVal   = explode('|', $tmp_array[$i]);
        $bo_table = $arrVal[0];
        $wr_id    = $arrVal[1];


        //--- 게시판 기본 변수 재설정
        $board = sql_fetch(" select * from {$g5['board_table']} where bo_table = '$bo_table' ");
        if ($board['bo_table']) {
            $write_table = $g5['write_prefix'] . $bo_table; // 게시판 테이블 전체이름
            if (isset($wr_id) && $wr_id)
                $write = sql_fetch(" select * from $write_table where wr_id = '$wr_id' ");
        }

        //--- 재설정 된 변수를 이용해서 스킨명, url 다시 할당
        if (G5_IS_MOBILE) {
            $board_skin_path = get_skin_path('board', $board['bo_mobile_skin']);
            $board_skin_url  = get_skin_url('board', $board['bo_mobile_skin']);
        } else {
            $board_skin_path = get_skin_path('board', $board['bo_skin']);
            $board_skin_url  = get_skin_url('board', $board['bo_skin']);
        }



        //--- 원본 delete.php 삭제 코드 시작
        @include_once($board_skin_path.'/delete.head.skin.php');

        $len = strlen($write['wr_reply']);
        if ($len < 0) $len = 0;
        $reply = substr($write['wr_reply'], 0, $len);

        // 원글만 구한다.
        $sql = " select count(*) as cnt from $write_table
                where wr_reply like '$reply%'
                and wr_id <> '{$write['wr_id']}'
                and wr_num = '{$write['wr_num']}'
                and wr_is_comment = 0 ";
        $row = sql_fetch($sql);

        // 코멘트 달린 원글의 삭제 여부
        $sql = " select count(*) as cnt from $write_table
                where wr_parent = '$wr_id'
                and mb_id <> '{$member['mb_id']}'
                and wr_is_comment = 1 ";
        $row = sql_fetch($sql);


        // 사용자 코드 실행
        @include_once($board_skin_path.'/delete.skin.php');

        $sql = " select wr_id, mb_id, wr_is_comment, wr_content from $write_table where wr_parent = '{$write['wr_id']}' order by wr_id ";
        $result = sql_query($sql);
        while ($row = sql_fetch_array($result))
        {
            // 원글이라면
            if (!$row['wr_is_comment'])
            {
                // 원글 포인트 삭제
                if (!delete_point($row['mb_id'], $bo_table, $row['wr_id'], '쓰기'))
                    insert_point($row['mb_id'], $board['bo_write_point'] * (-1), "{$board['bo_subject']} {$row['wr_id']} 글삭제");

                // 업로드된 파일이 있다면 파일삭제
                $sql2 = " select * from {$g5['board_file_table']} where bo_table = '$bo_table' and wr_id = '{$row['wr_id']}' ";
                $result2 = sql_query($sql2);
                while ($row2 = sql_fetch_array($result2)) {
                    @unlink(G5_DATA_PATH.'/file/'.$bo_table.'/'.str_replace('../', '', $row2['bf_file']));
                    // 썸네일삭제
                    if (preg_match("/\.({$config['cf_image_extension']})$/i", $row2['bf_file'])) {
                        delete_board_thumbnail($bo_table, $row2['bf_file']);
                    }
                }

                // 에디터 썸네일 삭제
                delete_editor_thumbnail($row['wr_content']);

                // 파일테이블 행 삭제
                sql_query(" delete from {$g5['board_file_table']} where bo_table = '$bo_table' and wr_id = '{$row['wr_id']}' ");

                $count_write++;
            }
            else
            {
                // 코멘트 포인트 삭제
                if (!delete_point($row['mb_id'], $bo_table, $row['wr_id'], '댓글'))
                    insert_point($row['mb_id'], $board['bo_comment_point'] * (-1), "{$board['bo_subject']} {$write['wr_id']}-{$row['wr_id']} 댓글삭제");

                $count_comment++;
            }
        }

        // 게시글 삭제
        sql_query(" delete from $write_table where wr_parent = '{$write['wr_id']}' ");

        // 최근게시물 삭제
        sql_query(" delete from {$g5['board_new_table']} where bo_table = '$bo_table' and wr_parent = '{$write['wr_id']}' ");

        // 스크랩 삭제
        sql_query(" delete from {$g5['scrap_table']} where bo_table = '$bo_table' and wr_id = '{$write['wr_id']}' ");


        $bo_notice = board_notice($board['bo_notice'], $write['wr_id']);
        sql_query(" update {$g5['board_table']} set bo_notice = '$bo_notice' where bo_table = '$bo_table' ");

        // 글숫자 감소
        if ($count_write > 0 || $count_comment > 0)
            sql_query(" update {$g5['board_table']} set bo_count_write = bo_count_write - '$count_write', bo_count_comment = bo_count_comment - '$count_comment' where bo_table = '$bo_table' ");

        @include_once($board_skin_path.'/delete.tail.skin.php');

        delete_cache_latest($bo_table);
    }

    goto_url('./post_list2.php' . '?page='.$page.'&amp;'.$qstr);
?>