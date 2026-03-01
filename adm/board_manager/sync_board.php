<?php

    include_once('./_common.php');

    $g5['title'] = '전체 게시판 동기화';
    include_once(G5_ADMIN_PATH.'/admin.head.php');

    $arrQuery = array();

    //--- 기존 뷰테이블 삭제
    $sql = 'DROP VIEW view_board';
    sql_query($sql, false);

    //--- 전체 테이블 조회
    $sql = "SHOW TABLES";
    $res = sql_query($sql, true);
    for($k=0; $tb=sql_fetch_array($res); $k++) {
        $v = reset($tb);
        if ( preg_match_all('/^g5_write_/', $v, $matches) ) {
            $tableName  = $v;
            $boardName  = str_replace('g5_write_', '', $v);
            $arrQuery[] = "
                (
                    SELECT wr_id, mb_id, ca_name, wr_parent, wr_is_comment, wr_subject, wr_content, wr_name, wr_hit, wr_datetime, '{$boardName}' AS tbl_name
                    FROM  {$tableName}
                )
            ";
        }
    }

    //--- 가져온 테이블들 중 g5_write로 시작하는 테이블만 뷰테이블 쿼리 작성
    // foreach ($res AS $list) {
    //     $v = reset($list);

    //     if ( preg_match_all('/^g5_write_/', $v) ) {
    //         $tableName  = $v;
    //         $boardName  = str_replace('g5_write_', '', $v);
    //         $arrQuery[] = "
    //             (
    //                 SELECT wr_id, mb_id, wr_parent, wr_is_comment, wr_subject, wr_content, wr_name, wr_hit, wr_datetime, '{$boardName}' AS tbl_name
    //                 FROM  {$tableName}
    //             )
    //         ";
    //     }
    // }

    //--- 쿼리문 합치기 및 뷰테이블 생성
    $sql = 'CREATE VIEW view_board AS ' . implode(' UNION ALL ', $arrQuery);
    $res = sql_query($sql, false);

    if ($res === true) {
        $msg = '전체 게시판 동기화가 완료되었습니다.';
        $btn = '돌아가기';
        $lnk = $_SERVER['HTTP_REFERER'];
    } else {
		echo $sql;
        $msg = '오류가 발생했습니다.<br /><br />' . '<strong>' . sql_error_info() . '</strong>';
        $btn = '재시도';
        $lnk = './sync_board.php';
    }
?>

    <div class="local_desc01 local_desc">
        <p>
            <?php echo $msg; ?>
        </p>
    </div>

   <center>
       <a href="<?php echo $lnk; ?>" class="btn_frmline"><?php echo $btn; ?></a>
   </center>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
