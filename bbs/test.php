<?php
include_once('./_common.php');


exit;

$blocking = "0";
if($_SERVER['REMOTE_ADDR'] == '58.229.223.164' ){
    $blocking = "1";    
}else{
  exit;
}

$g5['title'] = "녹화강의";
$wr_id = $no;

include_once(G5_THEME_PATH.'/head.php');
//include_once(G5_LIB_PATH.'/latest.lib.php');

//echo $no;
if(!$member['mb_id']){
  alert("로그인 후 이용하세요","/");
  exit;
}

$user_no =  $member['mb_no'];
//mypage check
$sql_my = "select count(no) as cnt from g5_content_mypage where content_no = {$no} and user_no = {$user_no} order by no ";
$row_my = sql_fetch($sql_my);

$sql_cnt = "select count(no) as cnt from g5_content_mypage where user_no = {$user_no} and percent > 80 ";
$row_cnt = sql_fetch($sql_cnt);


if($row_my['cnt'] == 0){
  $sql = " insert into g5_content_mypage set content_no = '{$no}', user_no = '$user_no', name = '$user_name',  reg_datetime = '".G5_TIME_YMDHIS."' ";

  //echo $sql;
  $result = sql_query($sql, true);  
}

//mypage check
$sql_my2 = "select * from g5_content_mypage where content_no = {$no} and user_no = {$user_no} order by no ";
$row_my2 = sql_fetch($sql_my2);


$seconds = $row_my2['seconds'];
$percent = $row_my2['percent']*1;
$complete = $row_my2['complete'];

// 2023-04-18 추가 수정사항으로 항상 컨트롤이 표기되도록 변경
// if($percent == "100"){
$controls = 1;
// }else{
  // $controls = 0;
// }

$sql = "select * from g5_content_lec2 where no = {$no} order by no ";
$row = sql_fetch($sql);
$vimeo_url = $row['subject_info'];
$vimeo_url = "https://player.vimeo.com/video/921886032?h=7163375e40";
$video_type = 'vimeo';

// $comment_array = array();
// $comment_sql = "SELECT * FROM g5_content_lec2_comment A INNER JOIN g5_member B ON A.user_no = B.mb_no WHERE A.content_no = {$no}";
// $comment_result = sql_query($comment_sql);
// while($comment_row = sql_fetch_array($comment_result)) {
//   array_push($comment_array, $comment_row);
// }
?>

<!--본문내용 -->
<div class="lecture_view_area">
  <div class="lecture_view_area_inner clear">

    <!--비디오 영역-->
    <div class="video_holder">
      <div id="video_container" class="video_container" style="position: relative;">
        <div id="vimeo-controls" style="display: none; position: absolute; width: 100%; height: 100%; z-index: 9999; background: rgba(0, 0, 0, 0.3);">
          <button id="control-button" style="position: absolute; left: 0; right: 0; top: 0; bottom: 0;">
            <img src="/img/main/lecture_btn.png">
          </button>
        </div>
        <div class="video_inner">
            <!-- <div id="player"></div> -->
            <iframe src="<?=$vimeo_url?>" width="640" height="564" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
        </div>
      </div>
    </div>

    <!--텍스트 영역-->
    <div class="txt_holder">
      <p class="video_title"><?=$row['title']?></p>
      <div class="video_info">
        <?=$row['subject']?>
      </div>

      <ul class="progress_info clear">
        <li><span id="rate_txt">수강율 <?=$percent?>%</span></li>
        <li><span>누적 학습량 <?=$row_cnt['cnt']?>개</span></li>
      </ul>
    </div>
  </div>
</div>
<!-- 본문내용 끝 -->
<!-- play info -->
<input type="hidden" id="seconds" />
<input type="hidden" id="percent" value="<?=$percent?>"/>
<input type="hidden" id="duration" />
<input type="hidden" id="content_no" value="<?=$no?>"/>
<script type="text/javascript" src="https://player.vimeo.com/api/player.js"></script>

<!-- 1. The <iframe> (and video player) will replace this <div> tag. -->
  <script>
    var blocking = "<?=$blocking?>";
    var mb_no = "<?=$member['mb_no']?>";
    var mb_point = "<?=number_format($member['mb_point'])?>";
    var mb_point_int = "<?=$member['mb_point']?>";
    var save_point_yn = 'N';
    if("<?=$controls?>"*1 == 1) {
      save_point_yn = 'Y';
    }

    function getPlayTime() {
      console.log("##########");
      var second = $("#seconds").val()*1;
      var duration = $("#duration").val()*1;
      var percent = (100 * second) / duration;
      console.log(second, duration, percent);

      $("#percent").val(Math.round(percent.toFixed(2)));
    }

      // 3. This function creates an <iframe> (and Vimeo player)
      //    after the API code downloads.
      var options = {
        id: "<?=$vimeo_url?>",
        height: '360',
        width: '640',
        autoplay: false,
        controls: (<?= $controls?> == 1),
        loop: false,
        byline: false,
        title: false,
        subtitle: false,
      }

//   var options = {
//     // url: "https://player.vimeo.com/video/76979871?h=8272103f6e",
//     url: "https://player.vimeo.com/video/921886032?h=7163375e40",
//     width: 800
//   };

    //   var player =  new Vimeo.Player('player', options);

    const iframe = document.querySelector('iframe');
    const player = new Vimeo.Player(iframe);


      if(<?= $controls?> == 1) {
        $("#vimeo-controls").remove()
      }

      if("<?php echo $percent?>" != "100") {
        player.setCurrentTime("<?=$seconds?>").then(function(seconds) {
          $("#seconds").val(seconds)
          player.getDuration().then(function(duration) {
            $("#duration").val(duration)
            getPlayTime()
          });
        });
      }

      var durationTimer = setInterval(function() {
        player.getDuration().then(function(duration) {
            console.log(duration);
          $("#duration").val(duration);
        });
      }, 2000);

      var getCurrentTimer = setInterval(function() {
        player.getCurrentTime().then(function(seconds) {
          $("#seconds").val(seconds)
        });
      }, 2000);

      var getPlayTimer = setInterval(function() {
        getPlayTime()
      }, 2000);

      var saveInfoTimer;

      $("#video_container").hover(function() {
        $(this).children('#vimeo-controls').css({display: ''})
      }, function() {
        $(this).children('#vimeo-controls').css({display: 'none'})
      })

      var video_status = 'stop'
      $("#video_container").click(function() {
        consolle.log("AAA");
        if(video_status == 'stop') {
          player.play().then(function() {
            video_status = 'start';
            $('#control-button').html('<img src="/img/main/lecture_pause_btn.png">');
          });
        } else {
          player.pause().then(function() {
            video_status = 'stop';
            clearInterval(saveInfoTimer);
            $('#control-button').html('<img src="/img/main/lecture_btn.png">');
          })
        }
      })

      player.on('play', function() {
        saveInfoTimer = setInterval(function() {
          if('<?= $percent?>' != '100'){
            saveInfo();
          }
        }, 5000);
      });

    function saveInfo(){
      if($("#seconds").val()==""){
        return false;
      }
      if($("#percent").val()==""){
        return false;
      }
      if($("#duration").val()==""){
        return false;
      }
      $.ajax({
        url:"/bbs/ajax.play.php",
        type:"POST",
        data: {
          "user_no": "<?=$user_no?>", 
          "user_name": "<?=$user_no?>", 
          "content_no" : $("#content_no").val() , 
          "seconds" : $("#seconds").val(), 
          "percent" : $("#percent").val(), 
          "duration" : $("#duration").val() 

        },
        success:function(data){
          //console.log(data);
          if(save_point_yn == "N" && $("#percent").val()*1 > 80 ){
            // savePoint();
          }

          if($("#percent").val()=="100"){
            //popQuestion();
            alert("수강이 완료되었습니다.");
            location.reload();
          }

          $("#rate_txt").text('수강율 '+$("#percent").val()+'%');
        },
        error:function(request,status,error){
          //alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
        }
      });
    }

    // function savePoint(){
    //   $.ajax({
    //     url: '/bbs/ajax.save.point.lecture.php',
    //     type:'post',
    //     data: {
    //       gubun:'study_room',
    //       mb_no: "<?=$user_no?>",
    //       point_use: 100,
    //       item_no: 2,
    //       item_name: "[<?=$row['title']?>] 강의 수강율에 따른 100포인트 지급",
    //       content_no : "<?=$no?>"
    //     },
    //     success:function(data){
    //       if(data=="success"){
    //       //alert("100 포인트가 지급되었습니다");
    //         save_point_yn = "Y";
    //       }else{
    //       //alert("100 포인트가 지급되었습니다");
    //       }
    //     },
    //     error:function(){
    //       console.log("error");
    //     },
    //     //success 혹은 error 콜백 실행 후 항상 실행
    //     complete: function(){
    //       console.log("complete");
    //       //location.reload();
    //     }
    //   });
    // }
  </script>


  <?php
  include_once(G5_THEME_PATH.'/tail.php');
