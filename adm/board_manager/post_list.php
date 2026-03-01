<?php

    $sub_menu = "300980";
    include_once('./_common.php');

    $g5['title'] = '강의 관리';

    include_once(G5_ADMIN_PATH.'/admin.head.php');
//    include_once(G5_ADMIN_BBS_PATH.'/board_head.php');
    include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');


    if ($is_admin) {
        set_session('ss_delete_token', $token = uniqid(time()));
    }

    $sql_search = 'wr_is_comment = 0';

    //--- 검색 조회
    if ($stx) {
        $sql_search .= funcSearchCase($sfl, $stx);
    }

    /* [OSJ : 2024-03-14] campus 게시판 고정처리 */
    $sbn = "campus";
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
                SELECT    wr_id, mb_id, wr_subject, wr_name, wr_hit, guest_hit, wr_datetime, tbl_name
                FROM      {$vTable}
                WHERE     {$sql_search}
                ORDER BY  wr_datetime DESC
                LIMIT    {$from_record}, {$rows}
        ";
        $res = sql_query($sql, true);
    }

?>

<!-- <link rel="stylesheet" href="./default.css"> -->

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
        <li>게시물 제목을 클릭하면 새창으로 해당 게시물이 열립니다.</li>
        <li>댓글수를 클릭하면 댓글 관리 페이지로 이동하며, 해당 게시물의 댓글들을 전부 표시합니다.</li>
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
        <!-- [OSJ : 2024-03-14] campus 게시판 고정 -->
        <input type="hidden" name="sbn" value="campus" />
        <!-- <select name="sbn" id="sbn">
            <option value="" <?php echo get_selected($_GET['sbn'], ""); ?> >전체 게시판</option>
            <?php foreach($arrName as $tbl => $tblName){ ?>
                <option value="<?php echo($tbl); ?>" <?php echo get_selected($_GET['sbn'], $tbl); ?> ><?php echo($tblName); ?></option>
            <?php } ?>
        </select> -->
        <label for="sfl" class="sound_only">검색대상</label>
        <select name="sfl" id="sfl">
            <option value="wr_subject" <?php echo get_selected($_GET['sfl'], "bo_subject"); ?> >제목</option>
            <option value="wr_content" <?php echo get_selected($_GET['sfl'], "wr_content"); ?> >내용</option>
            <option value="wr_subject|wr_content" <?php echo get_selected($_GET['sfl'], "wr_subject|wr_content"); ?> >제목+내용</option>
            <option value="wr_name" <?php echo get_selected($_GET['sfl'], "wr_name"); ?> >작성자</option>
            <option value="mb_id" <?php echo get_selected($_GET['sfl'], "mb_id"); ?> >아이디</option>
        </select>
        <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
        <input type="text" name="stx" value="<?php echo $stx ?>" id="stx" class="frm_input">
        <input type="submit" value="검색" class="btn_submit">
    </div>
</form>


<form name="boardlist" id="boardlist" action="./delete_all.php" onsubmit="return boardDelete(this);" method="post">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="qstr" value="<?php echo $qstr ?>">
    <input type="hidden" name="token" value="">

    <div class="btn_fixed_top">
        <a href="<?=G5_URL ?>/campus/write" class="btn_02 btn">강의등록</a>
        <input type="submit" name="act_button" onclick="document.pressed=this.value" value="선택삭제" class="btn btn_02">
        <!-- <a href="./sync_board.php" class="btn_01 btn">전체 게시판 동기화</a> -->
        <a href="./lecture_list_excel.php" class="btn_02 btn">엑셀저장</a>
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
            <th scope="col">제목</th>
						<th scope="col" class="width45p">신청자</th>
            <th scope="col" class="width45p">댓글수</th>
            <th scope="col">글쓴이</th>
            <th scope="col" class="width45p">조회(비회원)</th>
            <th scope="col" class="width80p">작성일</th>
            <th scope="col" class="width80p">관리</th>
        </tr>
        </thead>
        <tbody>


        <?php if($tblExist == 0) { ?>
            <tr><td colspan="8" class="empty_table">전체 게시판 동기화를 먼저 진행해 주십시오.</td></tr>
        <?php } elseif($total_count == 0) { ?>
            <tr><td colspan="8" class="empty_table">게시물이 없습니다.</td></tr>
        <?php } else { ?>

            <?php
                for($k=0; $list=sql_fetch_array($res); $k++) {
                    $sql = "select COUNT(*) AS apply_cnt from g5_content_mypage where content_no = '{$list["wr_id"]}' ";
                    // print_r($sql);
                    // echo " <BR>";
                    $content_cnt = sql_fetch($sql);
                    $apply_cnt = $content_cnt['apply_cnt'];

                    $cnt      = 0;
                    $wr_id    = $list['wr_id'];
                    $tbl_name = $list['tbl_name'];

                    $sql = "
                        SELECT  COUNT(wr_id) AS cnt
                        FROM    {$vTable}
                        WHERE   wr_is_comment = 1 AND wr_parent = {$wr_id} AND tbl_name = '{$tbl_name}'
                    ";
                    $row = sql_fetch($sql);
                    $cnt = $row['cnt'];
            ?>
                <tr class="">
                    <td class="td_chk">
                        <input type="checkbox" name="chk[]" value="<?php echo($list['tbl_name'] . '|' . $list['wr_id']); ?>">
                    </td>
                    <td class="td_category"><?php echo $arrName[$list['tbl_name']]; ?></td>
                    <td class="">
                        <a href="/bbs/board.php?bo_table=<?php echo $list['tbl_name']; ?>&wr_id=<?php echo $list['wr_id']; ?>" target="_blank"><?php echo(conv_subject($list['wr_subject'], 70, '...')); ?></a>
                    </td>
                    <td class="td_applinum">
                        <a href="javascript:void(0);" class="poplink_attending_list" poplink="#attending_list" onclick="fnLectureList(<?=$list['wr_id']?>);"><?php echo $apply_cnt; ?></a>
                    </td>
                    <td class="td_num">
                        <a href="./comment_list.php?sbn=<?php echo $list['tbl_name']; ?>&sfl=wr_parent&stx=<?php echo $list['wr_id']; ?>"><?php echo $cnt; ?></a>
                    </td>
                    <td class="td_mng">
                        <strong><?php echo $list['wr_name']; ?></strong><br />(<?php echo $list['mb_id'] ? $list['mb_id'] : '비회원'; ?>)
                    </td>
                    <td class="td_num">
                        <?php echo $list['wr_hit']; ?>
                        <br><span style="color:#888; font-size:11px;">(<?php echo $list['guest_hit']; ?>)</span>
                    </td>
                    <td class="td_mng td_mng_l"><?php echo $list['wr_datetime']; ?></td>
                    <td class="td_mng td_mng_l">
                        <a href="<?php echo('./delete.php?bo_table='.$list['tbl_name'].'&amp;wr_id='.$list['wr_id'].'&amp;token='.$token.'&amp;page='.$page.'&amp;'.urldecode($qstr)); ?>" class="btn btn_02 del_btn"><span class="sound_only"></span>삭제</a>
						<a href="./lecture_list_excel.php?wr_id=<?=$list['wr_id']?>" class="btn_02 btn">엑셀저장</a>
                    </td>
                </tr>
            <?php } ?>

        <?php } ?>

        </tbody>
        </table>
    </div>
    <!-- <div class="btn_fixed_top">
        <a href="javascript:void(0);" class="btn_02 btn">엑셀저장</a>
    </div> -->
</form>



<div id="attending_list"  class="pop">
    <div class="bg_layer" style=" background: rgba(5,10,20, .0); width: 100%; height: 100%; position: fixed; top: 0; left: 0; " onClick="$('#attending_list').removeClass('show');"></div>
    <div class="pop-inner register-pop">
        <button type="button" class="btn-close" onClick="$('#attending_list').removeClass('show');" title="창닫기">✕</button>
        <div class="registration-con event-con">
            <table>
				<thead>
					<tr>
						<th>번호</th>
						<th>구분</th>
						<th>ID</th>
						<th>이름</th>
						<th>상태</th>
					</tr>
				</thead>
				<tbody id="tdata">
					<tr>
						<td>1</td>
						<td>사전</td>
						<td>master</td>
						<td>마스터</td>
						<td>수강완료</td>
					</tr>
					<tr>
						<td>2</td>
						<td>일반</td>
						<td>master</td>
						<td>마스터</td>
						<td>수강완료</td>
					</tr>
				</tbody>
            </table>
        </div>
    </div>
<!--//수강내역 팝업-->

</div>
<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>


<script>
    function boardDelete(f){

        if (!is_checked("chk[]")) {
            alert(document.pressed+" 하실 게시물을 하나 이상 선택하세요.");
            return false;
        }

        if(document.pressed == "선택삭제") {
            if(!confirm("선택한 게시물을 정말 삭제하시겠습니까?")) {
                return false;
            }
        }

        return true;
    }

    $(".del_btn").click(function(){
        if(!confirm("선택한 게시물을 정말 삭제하시겠습니까?")) {
            return false;
        }
    });

    $("#sbn").change(function(){
       $("#fsearch").submit();
    });
		 $('.poplink_attending_list').on('click', function(e) {
        e.preventDefault();
        $($(this).attr('poplink')).addClass('show');
        //$("html").css("overflow", "hidden");
    });


    /* [OSJ : 2024-03-14] 신청자 목록 정보 */
    function fnLectureList(wr_id){
        //내역조회
        $("#tdata").empty();
        var t_html = "";
        $.ajax({
            url: "/adm/proc/lectueList.php",
            type: "POST",
            dataType: "json",
            data: {
                "type": "lecture_list",
                "mypage_type": "campus",
                "wr_id": wr_id
            },
            success: function (data) {
                if(data.result == "success"){
                    let result_json = data.resultList;
                    $.each(result_json, function (key, mypage) {
                        t_html += `
                            <tr>
                                <td>${mypage.no}</td>
                                <td>${mypage.gubun}</td>
                                <td>${mypage.mb_id}</td>
                                <td>${mypage.mb_name}</td>
                                <td>${mypage.complete2}</td>
                            </tr>
                        `;
                    });
                }
                
                if (t_html != "") {
                    $("#tdata").append(t_html);
                } else {
                    t_html += `
                        <tr>
                            <td colspan="5">신청자가 없습니다</td>
                        </tr>
                    `;
                    $("#tdata").append(t_html);
                }
            },
            error: function (request, status, error) {
                // alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
            }
        });

    }
</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');