<?php

    $sub_menu = "300990";
    include_once('./_common.php');

    $g5['title'] = 'Q&A 관리';

    include_once(G5_ADMIN_PATH.'/admin.head.php');
    // include_once(G5_ADMIN_BBS_PATH.'/board_head.php');
    include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');


    if ($is_admin) {
        set_session('ss_delete_comment_token', $token = uniqid(time()));
    }

    $sql_search = 'wr_is_comment = 1';

    /* [OSJ : 2024-11-11] 카테고리 검색 추가. */
    if($sbn == 'campus'){
        if($sca) {
            $sql_search .= " AND ca_name = '{$sca}' ";
        }
    }else{
        $sca = ""; 
    }

    //--- 검색 조회
    if ($stx) {
        $sql_search .= funcSearchCase($sfl, $stx);
    }

    //--- 게시판 선택
    if($sbn != ''){
        $sql_search .= " AND tbl_name = '{$sbn}' ";
    }

    //--- 기간별 검색
    $sql_search .= funcSearchDate($fr_date, $to_date);

    //--- 뷰테이블 유무 확인
    $tblExist = funcTableExist($vTable);

    if($tblExist > 0) {

        $sql = "
            SELECT  count(*) AS cnt
            FROM    {$vTable}
            WHERE   {$sql_search}
        ";
        $row = sql_fetch($sql);
        $total_count = $row['cnt'];

        //--- 페이징 처리
        $rows = $config['cf_page_rows'];
        $total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
        if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
        $from_record = ($page - 1) * $rows; // 시작 열을 구함


        //--- 게시판 명칭 조회
        $sql = "
                    SELECT  bo_table, bo_subject
                    FROM    g5_board
            ";
        $res = sql_query($sql, true);
        while( $bRow = sql_fetch_array($res) ) {
            $arrName[$bRow['bo_table']] = $bRow['bo_subject'];
        }

        //--- 게시물 가져오기
        $sql = "
                    SELECT    wr_id, ca_name, mb_id, wr_parent, wr_content, wr_name, wr_datetime, tbl_name
                    FROM      {$vTable}
                    WHERE     {$sql_search}
                    ORDER BY  wr_datetime DESC
                    LIMIT    {$from_record}, {$rows}
            ";
        $res = sql_query($sql, true);
    }

    /* [OSJ : 2024-11-13] $qstr 값 추가 .. 카테고리 공백 사라지는 이슈 있음. */
    $qstr = $qstr."&amp;sca=".str_replace(" ", "+", $sca)."&amp;sbn={$sbn}";
?>

    <script>
        $(function(){
            $("#fr_date, #to_date").datepicker({ changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", showButtonPanel: true, yearRange: "c-99:c+99", maxDate: "+0d" });
        });
    </script>

    <div class="local_ov01 local_ov">
        <a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>" class="ov_listall">전체목록</a>
        <span class="btn_ov01"><span class="ov_txt"> 전체 게시물 </span><span class="ov_num"> <?php echo $total_count; ?>건</span></span>
    </div>

    <!-- <div class="local_desc01 local_desc">
        <ol>
            <li>신규 게시판 생성시 우측 상단의 <strong>[전체 게시판 동기화]</strong> 버튼을 클릭해서 작업을 진행해야 합니다.</li>
            <li>게시물 번호를 클릭하면 새창으로 해당 게시물이 열립니다.</li>
            <li>댓글 내용을 클릭하면 새창으로 해당 댓글이 작성된 게시물이 열립니다.</li>
        </ol>
    </div> -->


    <form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">

        <div class="sch_last">
            <strong>기간별검색</strong>
            <input type="text" name="fr_date" value="<?php echo $fr_date ?>" id="fr_date" class="frm_input" size="11" maxlength="10">
            <label for="fr_date" class="sound_only">시작일</label>
            ~
            <input type="text" name="to_date" value="<?php echo $to_date ?>" id="to_date" class="frm_input" size="11" maxlength="10">
            <label for="to_date" class="sound_only">종료일</label>
        </div>

        <div>
            <select name="sbn" id="sbn">
                <option value="" <?php echo get_selected($_GET['sbn'], ""); ?> >전체 게시판</option>
                <?php foreach($arrName as $tbl => $tblName){ ?>
                    <option value="<?php echo($tbl); ?>" <?php echo get_selected($_GET['sbn'], $tbl); ?> ><?php echo($tblName); ?></option>
                <?php } ?>
            </select>
            <!-- [OSJ : 2024-11-11] 카테고리 검색 추가 -->
            <select name="sca" id="sca" class="frm_input" <?=$sbn == 'campus' ? '':' style="display:none;" '?>>
                <option value=''>전체</option>
                <?
                // campus 테이블의 카테고리 정보 가져오기
                $sql = " select * from g5_board where bo_table = 'campus' ";
                $opt = sql_fetch($sql);
                $arr = explode("|", $opt['bo_category_list']);
                for ($i=0; $i<count($arr); $i++) {
                    if(trim($arr[$i]) == "") continue;
                    $arr[$i] = get_text($arr[$i]);
                ?>
                    <option value='<?=$arr[$i]?>' <? if($sca == $arr[$i]) echo "selected"; ?>><?=$arr[$i]?></option>
                <? } ?>
            </select>
            <!-- [OSJ : 2024-11-11] 카테고리 검색 추가 -->
            <label for="sfl" class="sound_only">검색대상</label>
            <select name="sfl" id="sfl">
                <option value="wr_content" <?php echo get_selected($_GET['sfl'], "wr_content"); ?> >내용</option>
                <option value="wr_name" <?php echo get_selected($_GET['sfl'], "wr_name"); ?> >작성자</option>
                <option value="mb_id" <?php echo get_selected($_GET['sfl'], "mb_id"); ?> >아이디</option>
                <option value="wr_parent" <?php echo get_selected($_GET['sfl'], "wr_parent"); ?> >게시물 번호</option>
            </select>
            <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
            <input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input">
            <input type="submit" value="검색" class="btn_submit">
        </div>
    </form>


    <form name="boardlist" id="boardlist" action="./delete_comment_all.php" onsubmit="return boardDelete(this);" method="post">
        <input type="hidden" name="page" value="<?php echo $page ?>">
        <input type="hidden" name="qstr" value="<?php echo $qstr ?>">
        <input type="hidden" name="token" value="">

        <div class="btn_fixed_top">
            <input type="submit" name="act_button" onclick="document.pressed=this.value" value="선택삭제" class="btn btn_02">
            <a href="./sync_board.php" class="btn_01 btn">전체 게시판 동기화</a>
        </div>

        <div class="tbl_head01 tbl_wrap">
            <table>
                <caption><?php echo $g5['title']; ?> 목록</caption>
                <thead>
                <tr>
                    <th scope="col">
                        <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
                    </th>
                    <th scope="col" class="width50p">게시판명</th>
                    <th scope="col">카테고리</th>
                    <th scope="col">게시물 번호</th>
                    <th scope="col">내용</th>
                    <th scope="col">글쓴이</th>
                    <th scope="col" class="width80p">작성일</th>
                    <th scope="col" class="width80p">관리</th>
                </tr>
                </thead>
                <tbody>


                <?php if($tblExist == 0) { ?>
                    <tr><td colspan="8" class="empty_table">전체 게시판 동기화를 먼저 진행해 주십시오.</td></tr>
                <?php } elseif($total_count == 0) { ?>
                    <tr><td colspan="8" class="empty_table">댓글이 없습니다.</td></tr>
                <?php } else { ?>

                    <?php 
                    for($k=0; $list=sql_fetch_array($res); $k++) {
                    ?>
                        <tr class="">
                            <td class="td_chk">
                                <input type="checkbox" name="chk[]" value="<?php echo($list['tbl_name'] . '|' . $list['wr_id']); ?>">
                            </td>
                            <td class="td_category"><?php echo $arrName[$list['tbl_name']]; ?></td>
                            <td class="td_category"><?php echo $list['ca_name']; ?></td>
                            <td class="td_num">
                                <a href="/bbs/board.php?bo_table=<?php echo $list['tbl_name']; ?>&wr_id=<?php echo $list['wr_parent']; ?>" target="_blank"><?php echo $list['wr_parent']; ?></a>
                            </td>
                            <td>
                                <a href="/bbs/board.php?bo_table=<?php echo $list['tbl_name']; ?>&wr_id=<?php echo $list['wr_id']; ?>" target="_blank"><?php echo(conv_subject($list['wr_content'], 70, '...')); ?></a>
                            </td>
                            <td class="td_mng">
                                <strong><?php echo $list['wr_name']; ?></strong><br />
                                (<?php echo $list['mb_id'] ? $list['mb_id'] : '비회원'; ?>)
                            </td>
                            <td class="td_mng td_mng_l"><?php echo $list['wr_datetime']; ?></td>
                            <td class="td_mng td_mng_l">
                                <a href="<?php echo('./delete_comment.php?bo_table='.$list['tbl_name'].'&amp;comment_id='.$list['wr_id'].'&amp;token='.$token.'&amp;page='.$page.'&amp;'.urldecode($qstr)); ?>" class="btn btn_02 del_btn"><span class="sound_only"></span>삭제</a>
                            </td>
                        </tr>
                    <?php } ?>

                <?php } ?>

                </tbody>
            </table>
        </div>
    </form>


<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>


    <script>
        function boardDelete(f){

            if (!is_checked("chk[]")) {
                alert(document.pressed+" 하실 게시물을 하나 이상 선택하세요.");
                return false;
            }

            if(document.pressed == "선택삭제") {
                if(!confirm("선택한 댓글을 정말 삭제하시겠습니까?")) {
                    return false;
                }
            }

            return true;
        }

        $(".del_btn").click(function(){
            if(!confirm("선택한 댓글을 정말 삭제하시겠습니까?")) {
                return false;
            }
        });

        $("#sbn").change(function(){
            $("#fsearch").submit();
        });
    </script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');