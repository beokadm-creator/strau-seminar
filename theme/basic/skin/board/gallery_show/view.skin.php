<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

$depth1 = 6;

include_once(G5_LIB_PATH.'/ray_util.lib.php');
$common_util = new Ray_Util();

$lec_status = $common_util->start_day_check($view['wr_3']) ? 'ing':'wait';

/* [OSJ : 2024-03-14] 관리자가 아닌 경우 오픈일 이전인 경우 접근제한 */
if (!$is_admin && $lec_status == "wait") {
    alert("해당 강의는 {$view['wr_3']}에 오픈될 예정입니다.");
}    

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
include_once(G5_THEME_PATH.'/head.php');

$user_no =  $member['mb_no'];
//mypage check
$sql_my = "select count(no) as cnt from g5_content_mypage_show where content_no = '{$view["wr_id"]}' and user_no = {$user_no} order by no ";
$row_my = sql_fetch($sql_my);

if ($row_my['cnt'] == 0) {
    // if ( $lec == "1" || $is_admin ) {
        $content_type = "";
        if ($view['wr_4'] == "") {
            $content_type = "live";
        }
        $sql = " insert into g5_content_mypage_show set content_no = '{$view["wr_id"]}', type='{$content_type}', user_no = '$user_no', name = '$user_name',  reg_datetime = '".G5_TIME_YMDHIS."' ";
        sql_query($sql, true);
    // } else {
    //     alert("수강 신청이 필요합니다.");
    // }
}

//mypage check
$sql_my2 = "select * from g5_content_mypage_show where content_no = '{$view["wr_id"]}' and user_no = {$user_no} order by no ";
$row_my2 = sql_fetch($sql_my2);


$seconds = $row_my2['seconds'];
$percent = $row_my2['percent']*1;
$complete = $row_my2['complete'];
$controls = 1;
?>

<script src="<?php echo G5_JS_URL; ?>/viewimageresize.js"></script>


<!-- 게시물 읽기 시작 { -->

<article class="subpage board pbt100" id="bo_v">
	<div class="inner_container">
		<h3 class="tit fs_42 fw_700 mb50"><?php echo $board['bo_subject'] ?></h3>
    <header>
        <h2 id="bo_v_title">
            <span class="bo_v_tit">
            <?php
            echo cut_str(get_text($view['wr_subject']), 70); // 글제목 출력
            ?></span>
        </h2>
    </header>

    <section id="bo_v_info">
        <h2>페이지 정보</h2>
        <div class="profile_info">
        	<!-- <div class="pf_img"><?php echo get_member_profile_img($view['mb_id']) ?></div> -->
        	<div class="profile_info_ct clear_after">
						<p class="title"><span class="content"> <?php echo $view['wr_12'] ?></span></p>
						<!-- <p class="title"><span class="title">강의자 : </span><span class="content"> <?php echo $view['wr_1'] ?></span></p>
						<p class="date"><span class="title">치과명 : </span><span class="content"> <?php echo $view['wr_2'] ?></span></p>
        		<p class="name"><span class="title">작성자 : </span> <span class="content"><?php echo $view['name'] ?><?php if ($is_ip_view) { echo "&nbsp;($ip)"; } ?> </span></p>
       		 	<span class="sound_only">댓글</span><strong><a href="#bo_vc"> <i class="fa fa-comment" aria-hidden="true" style="color:#888;"></i> <?php echo number_format($view['wr_comment']) ?>건</a></strong>
						<p class="date"><span class="title">등록일 : </span><span class="content if_date"><?php echo date("Y.m.d", strtotime($view['wr_datetime'])) ?></span></p>
        		<p class="hit"><span class="title">조회수 : </span><span class="content"> <?php echo number_format($view['wr_hit']) ?></span></p> -->
                
                 
                   <?php
                            $cnt = 0;
                            if ($view['file']['count']) {
                                   for ($i=0; $i<count($view['file']); $i++) {
                                       if (isset($view['file'][$i]['source']) && $view['file'][$i]['source'])
                                           $cnt++;
                                   }
                            }
	                ?>

                       <?php if ($cnt) { ?>
                       <!-- 첨부파일 시작 { -->
 
                           <?php
                           // 가변 파일
                           for ($i=0; $i<count($view['file']); $i++) {
                               if (isset($view['file'][$i]['source']) && $view['file'][$i]['source']) {
                            ?>
															
                               <p class="content_file" style="display: none;">
							       <span class="title attach">첨부파일 : </span>
                                   <a href="<?php echo $view['file'][$i]['href'];  ?>" class="view_file_download" download>
                                       <?php echo $view['file'][$i]['source'] ?> <?php echo $view['file'][$i]['content'] ?>
                                   </a>
								   <span class="down_text"><a href="<?php echo $view['file'][$i]['href'];  ?>" class="view_file_download" download>내려받기</a></span>
                               </p>
															 
                           <?php
                               }
                           }
                            ?>
                           
                        <!-- } 첨부파일 끝 -->
                     <?php } ?>
										 

    		</div>
    	</div>

    	<!-- 게시물 상단 버튼 시작 { -->
	    <div id="bo_v_top">
          <!--
	        <?php ob_start(); ?>
	        <ul class="btn_bo_user bo_v_com">
				<li><a href="<?php echo $list_href ?>" class="btn_b01 btn" title="목록"><i class="fa fa-list" aria-hidden="true"></i><span class="sound_only">목록</span></a></li>
	            <?php if ($reply_href) { ?><li><a href="<?php echo $reply_href ?>" class="btn_b01 btn" title="답변"><i class="fa fa-reply" aria-hidden="true"></i><span class="sound_only">답변</span></a></li><?php } ?>
	            <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b01 btn" title="글쓰기"><i class="fas fa-pencil-alt" aria-hidden="true"></i><span class="sound_only">글쓰기</span></a></li><?php } ?>
	        	<?php if ($update_href || $delete_href || $copy_href || $move_href || $search_href) { ?>
	        	<li>
	        		<button type="button" class="btn_more_opt is_view_btn btn_b01 btn"><i class="fa fa-ellipsis-v" aria-hidden="true"></i><span class="sound_only">게시판 리스트 옵션</span></button>
		        	<ul class="more_opt is_view_btn"> 
			            <?php if ($update_href) { ?><li><a href="<?php echo $update_href ?>">수정<i class="fa fa-pencil-square-o" aria-hidden="true"></i></a></li><?php } ?>
			            <?php if ($delete_href) { ?><li><a href="<?php echo $delete_href ?>" onclick="del(this.href); return false;">삭제<i class="fas fa-trash-alt" aria-hidden="true"></i></a></li><?php } ?>
			            <?php if ($copy_href) { ?><li><a href="<?php echo $copy_href ?>" onclick="board_move(this.href); return false;">복사<i class="fa fa-files-o" aria-hidden="true"></i></a></li><?php } ?>
			            <?php if ($move_href) { ?><li><a href="<?php echo $move_href ?>" onclick="board_move(this.href); return false;">이동<i class="fas fa-arrows-alt" aria-hidden="true"></i></a></li><?php } ?>
			            <?php if ($search_href) { ?><li><a href="<?php echo $search_href ?>">검색<i class="fa fa-search" aria-hidden="true"></i></a></li><?php } ?>
			        </ul> 
	        	</li>
	        	<?php } ?>
	        </ul>
 	        <?php
	        $link_buttons = ob_get_contents();
	        ob_end_flush();
			?>
           -->
	    </div>
	    <!-- } 게시물 상단 버튼 끝 -->
    </section>

    <section id="bo_v_atc" style="display: none;">
        <h2 id="bo_v_atc_title">본문</h2>
        <div id="bo_v_share">
        	<?php include_once(G5_SNS_PATH."/view.sns.skin.php"); ?>
	        <?php if ($scrap_href) { ?><a href="<?php echo $scrap_href;  ?>" target="_blank" class="btn btn_b03" onclick="win_scrap(this.href); return false;"><i class="fa fa-bookmark" aria-hidden="true"></i> 스크랩</a><?php } ?>
	    </div>
        <!--
        <?php
        // 파일 출력
        $v_img_count = count($view['file']);
        if ($v_img_count) {
            echo "<div id=\"bo_v_img\">\n";

            for ($i=0; $i<=count($view['file']); $i++) {
                echo get_file_thumbnail($view['file'][$i]);
            }

            echo "</div>\n";
        }
         ?>
         -->
        

        <?php if ($is_signature) { ?><p><?php echo $signature ?></p><?php } ?>


        <!--  추천 비추천 시작 { -->
        <?php if ( $good_href || $nogood_href) { ?>
        <div id="bo_v_act">
            <?php if ($good_href) { ?>
            <span class="bo_v_act_gng">
                <a href="<?php echo $good_href.'&amp;'.$qstr ?>" id="good_button" class="bo_v_good"><i class="fa fa-thumbs-o-up" aria-hidden="true"></i><span class="sound_only">추천</span><strong><?php echo number_format($view['wr_good']) ?></strong></a>
                <b id="bo_v_act_good"></b>
            </span>
            <?php } ?>
            <?php if ($nogood_href) { ?>
            <span class="bo_v_act_gng">
                <a href="<?php echo $nogood_href.'&amp;'.$qstr ?>" id="nogood_button" class="bo_v_nogood"><i class="fa fa-thumbs-o-down" aria-hidden="true"></i><span class="sound_only">비추천</span><strong><?php echo number_format($view['wr_nogood']) ?></strong></a>
                <b id="bo_v_act_nogood"></b>
            </span>
            <?php } ?>
        </div>
        <?php } else {
            if ($board['bo_use_good'] || $board['bo_use_nogood']) {
        ?>
        <div id="bo_v_act">
            <?php if ($board['bo_use_good']) { ?><span class="bo_v_good"><i class="fa fa-thumbs-o-up" aria-hidden="true"></i><span class="sound_only">추천</span><strong><?php echo number_format($view['wr_good']) ?></strong></span><?php } ?>
            <?php if ($board['bo_use_nogood']) { ?><span class="bo_v_nogood"><i class="fa fa-thumbs-o-down" aria-hidden="true"></i><span class="sound_only">비추천</span><strong><?php echo number_format($view['wr_nogood']) ?></strong></span><?php } ?>
        </div>
        <?php
            }
        }
        ?>
        <!-- }  추천 비추천 끝 -->
    </section>

 

    <?php if (isset($view['link'][1]) && $view['link'][1]) { ?>
    <!-- 관련링크 시작 { -->
    <section id="bo_v_link">
        <h2>관련링크</h2>
        <ul style="display: none;">
        <?php
        // 링크
        $cnt = 0;
        for ($i=1; $i<=count($view['link']); $i++) {
            if ($view['link'][$i]) {
                $cnt++;
                $link = cut_str($view['link'][$i], 70);
            ?>
            <li>
                <i class="fa fa-link" aria-hidden="true"></i>
                <a href="<?php echo $view['link_href'][$i] ?>" target="_blank">
                    <strong><?php echo $link ?></strong>
                </a>
                <br>
                <span class="bo_v_link_cnt"><?php echo $view['link_hit'][$i] ?>회 연결</span>
            </li>
            <?php
            }
        }
        
        $vimeo_url = $view['link'][1];
        $video_type = 'vimeo';
        ?>
        </ul>
        <div class="video_container">
            <!-- play info -->
            <input type="hidden" id="seconds" />
            <input type="hidden" id="percent" value="<?=$percent?>"/>
            <input type="hidden" id="duration" />
            <input type="hidden" id="content_no" value="<?=$no?>"/>
            <script type="text/javascript" src="https://player.vimeo.com/api/player.js"></script>
            <iframe src="<?php echo $vimeo_url ?>"  frameborder="0" allowfullscreen  allow="autoplay;"></iframe>
        </div>
    </section>
    <!-- } 관련링크 끝 -->
    <?php } ?>

    <!-- 본문 내용 시작 { -->
    <div id="bo_v_con"><?php echo $view['wr_content']; ?></div>
    <!-- } 본문 내용 끝 -->

    
    <div class="btn">
        <?php if($view['wr_10']){ ?>
        <a href="<?php echo $view['wr_10']; ?>">제품정보 바로가기</a>
        <?php } ?>
        <?php if($view['wr_11']){ ?>
        <a href="<?php echo $view['wr_11']; ?>" class="demo_btn">데모 신청하기 <span class="arrow"></span></a>
        <?php } ?>
    </div>
    
    <?php if ($prev_href || $next_href) { ?>
    <ul class="bo_v_nb">
        <?php if ($prev_href) { ?><li class="btn_prv"><span class="nb_tit"><i class="fa fa-chevron-up" aria-hidden="true"></i> 이전글</span><a href="<?php echo $prev_href ?>"><?php echo $prev_wr_subject;?></a> <span class="nb_date"><?php echo str_replace('-', '.', substr($prev_wr_date, '2', '8')); ?></span></li><?php } ?>
        <?php if ($next_href) { ?><li class="btn_next"><span class="nb_tit"><i class="fa fa-chevron-down" aria-hidden="true"></i> 다음글</span><a href="<?php echo $next_href ?>"><?php echo $next_wr_subject;?></a>  <span class="nb_date"><?php echo str_replace('-', '.', substr($next_wr_date, '2', '8')); ?></span></li><?php } ?>
    </ul>
    <?php } ?>

    <?php
    // 코멘트 입출력
    include_once(G5_BBS_PATH.'/view_comment.php');
	?>

    <!-- 게시판 버튼-->

      <div id="bo_V_bottom">
	      <?php ob_start(); ?>
          <ul class="btn_bo_user bo_v_com">
				<li><a href="<?php echo $list_href ?>?sca=<?php echo $sca ?>" class="btn_b01 btn btn_green" title="목록">목록으로</a></li>
	            <!-- <?php if ($reply_href) { ?><li><a href="<?php echo $reply_href ?>" class="btn_b01 btn btn_green" title="답변">답변</a></li><?php } ?> -->
				<?php if ($update_href || $delete_href || $copy_href || $move_href || $search_href) { ?>
			            <?php if ($update_href) { ?><li><a href="<?php echo $update_href ?>" class="btn_b01 btn btn_green">수정</a></li><?php } ?>
			            <?php if ($delete_href) { ?><li><a href="<?php echo $delete_href ?>" onclick="del(this.href); return false;" class="btn_b01 btn btn_green">삭제</a></li><?php } ?>
			            <!-- <?php if ($copy_href) { ?><li><a href="<?php echo $copy_href ?>" onclick="board_move(this.href); return false;" class="btn_b01 btn">복사</a></li><?php } ?> -->
 			            <!-- <?php if ($move_href) { ?><li><a href="<?php echo $move_href ?>" onclick="board_move(this.href); return false;" class="btn_b01 btn">이동</a></li><?php } ?> -->
	        	<?php } ?>
		  </ul>
		  <?php
	        $link_buttons = ob_get_contents();
	        ob_end_flush();
		  ?>

    </div>
    <!-- 게시판 버튼 끝-->
</div>
</article>
<!-- } 게시판 읽기 끝 -->


<script>
<?php if ($board['bo_download_point'] < 0) { ?>
$(function() {
    $("a.view_file_download").click(function() {
        if (!g5_is_member) {
            alert("다운로드 권한이 없습니다.\n회원이시라면 로그인 후 이용해 보십시오.");
            return false;
        }

        var msg = "파일을 다운로드 하시면 포인트가 차감(<?php echo number_format($board['bo_download_point']) ?>점)됩니다.\n\n포인트는 게시물당 한번만 차감되며 다음에 다시 다운로드 하셔도 중복하여 차감하지 않습니다.\n\n그래도 다운로드 하시겠습니까?";

        if (confirm(msg)) {
            var href = $(this).attr("href")+"&js=on";
            $(this).attr("href", href);

            return true;
        } else {
            return false;
        }
    });
});
<?php } ?>

function board_move(href)
{
    window.open(href, "boardmove", "left=50, top=50, width=500, height=550, scrollbars=1");
}
</script>

<script>
$(function() {
    $("a.view_image").click(function() {
        window.open(this.href, "large_image", "location=yes,links=no,toolbar=no,top=10,left=10,width=10,height=10,resizable=yes,scrollbars=no,status=no");
        return false;
    });

    // 추천, 비추천
    $("#good_button, #nogood_button").click(function() {
        var $tx;
        if (this.id == "good_button")
            $tx = $("#bo_v_act_good");
        else
            $tx = $("#bo_v_act_nogood");

        excute_good(this.href, $(this), $tx);
        return false;
    });

    // 이미지 리사이즈
    $("#bo_v_atc").viewimageresize();
});

function excute_good(href, $el, $tx)
{
    $.post(
        href,
        { js: "on" },
        function(data) {
            if (data.error) {
                alert(data.error);
                return false;
            }

            if (data.count) {
                $el.find("strong").text(number_format(String(data.count)));
                if ($tx.attr("id").search("nogood") > -1) {
                    $tx.text("이 글을 비추천하셨습니다.");
                    $tx.fadeIn(200).delay(2500).fadeOut(200);
                } else {
                    $tx.text("이 글을 추천하셨습니다.");
                    $tx.fadeIn(200).delay(2500).fadeOut(200);
                }
            }
        }, "json"
    );
}
</script>

<!-- } 게시글 읽기 끝 -->

<script>
    
    // $("#video_container").hover(function() {
    //     $(this).children('#vimeo-controls').css({display: ''});
    // }, function() {
    //     $(this).children('#vimeo-controls').css({display: 'none'});
    // });

    // player.pause().then(function () {}); // 멈춤 처리
    // player.play().then(function () {}); // 시작 처리
    
    const iframe = document.querySelector('iframe');
    const player = new Vimeo.Player(iframe);

    <? if ($view['wr_4'] != "") { ?>
    
    function getPlayTime() {
        console.log("##########");
        var second = $("#seconds").val() * 1;
        var duration = $("#duration").val() * 1;
        var percent = (100 * second) / duration;
        $("#percent").val(Math.round(percent.toFixed(2)));
    }

    if ("<?php echo $percent?>" != "100") {
        player.setCurrentTime("<?=$seconds?>").then(function (seconds) {
            $("#seconds").val(seconds);
            player.getDuration().then(function (duration) {
                $("#duration").val(duration);
                getPlayTime();
            });
        });
    }

    var durationTimer = setInterval(function() {
        player.getDuration().then(function (duration) {
            console.log(duration);
            $("#duration").val(duration);
        });
    }, 2000);

    var getCurrentTimer = setInterval(function() {
        player.getCurrentTime().then(function (seconds) {
            $("#seconds").val(seconds);
        });
    }, 2000);

    var getPlayTimer = setInterval(function() {
        getPlayTime();
    }, 2000); 

    player.on('pause', function() {
        clearInterval(saveInfoTimer);
    });
    player.on('ended', function() {
        // clearInterval(saveInfoTimer);
        saveInfo();
        alert("수강이 완료되었습니다.");
    });

    var saveInfoTimer; 
    player.on('play', function() {
        saveInfoTimer = setInterval(function () {
            if ('<?=$percent?>' != '100') {
                saveInfo();
            }
        }, 5000);
    }); 
    function saveInfo() {
        if ( $("#seconds").val() == "" || $("#percent").val() == "" || $("#duration").val() == "" ) {
            return false;
        }
    
        $.ajax({
            url: "/bbs/ajax.play.php",
            type: "POST",
            data: {
                "mypage_type": "launchingShow",
                "user_no": "<?=$user_no?>",
                "content_no": "<?=$view["wr_id"]?>",
                "seconds": $("#seconds").val(),
                "percent": $("#percent").val(),
                "duration": $("#duration").val()
            },
            success: function (data) {
                //console.log(data);
                if ($("#percent").val() == "100") {
                    location.reload();
                }
                // $("#rate_txt").text('수강율 ' + $("#percent").val() + '%');
            },
            error: function (request, status, error) {
                // alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"err
                // or:"+error);
            }
        });
    }
    <? } else { ?>
        console.log("##### LIVE");
    <? } ?>

</script>

<?php
include_once(G5_THEME_PATH.'/tail.php'); ?>