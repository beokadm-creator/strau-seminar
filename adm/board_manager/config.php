<?php

    /*
     * 검색용 함수
     * $sfl   : 검색 필드
     * $stx   : 검색할 내용
     * return : 조합된 WHERE 구문
     */
    function funcSearchCase($sfl, $stx)
    {
        switch ($sfl) {
            case "wr_name" :
                $where .= " ({$sfl} = '{$stx}') ";
                break;
            case "mb_id" :
                $where .= " ({$sfl} = '{$stx}') ";
                break;
            case "wr_subject" :
                $where .= " ({$sfl} LIKE '%{$stx}%') ";
                break;
            case "wr_content" :
                $where .= " ({$sfl} LIKE '%{$stx}%') ";
                break;
            case "wr_subject|wr_content" :
                $where .= " (wr_subject LIKE '%{$stx}%' OR wr_content LIKE '%{$stx}%') ";
                break;
            default :
                $where .= " ({$sfl} = '{$stx}') ";
                break;
        }

        return " AND ( " .  $where . " ) ";
    }

    /*
     * 테이블 유무 확인
     * $tbl   : 테이블명
     * return : 테이블 row 값
     */
    function funcTableExist($tbl = 'view_board'){

        $sql   = "SHOW TABLES LIKE '{$tbl}'";
        $res   = @sql_query($sql, true);
        $exist = mysqli_num_rows($res);

        return $exist;
    }


    /*
     * 기간별 검색
     * $fr_date : 시작일
     * $to_date : 종료일
     * return   : 조합된 WHERE 구문
     */
    function funcSearchDate($fr_date, $to_date){

        $where = "";

        if($fr_date && $to_date) {
            $where = " AND DATE(wr_datetime) BETWEEN '{$fr_date}' AND '{$to_date}' ";
        }

        return $where;
    }


    $list       = array();
    $arrName    = array();
    $vTable     = 'view_board'; //--- 뷰테이블 명칭
    $qstr       = "sfl={$sfl}&amp;stx={$stx}&amp;fr_date={$fr_date}&amp;to_date={$to_date}";
