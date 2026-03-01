<?php
include_once('./_common.php');
include_once(G5_LIB_PATH.'/mailer.lib.php');

ob_start();
include_once ('./email_lecture_alarm.php');
$content = ob_get_contents();
ob_end_clean();

/* [OSJ : 2024-04-05] 매시간 진행건에 대해서 발송 처리. */
// $sql = " select w.wr_subject, w.wr_3, m.user_no from g5_content_mypage m, g5_write_campus w where m.content_no = w.wr_id and m.pre_apply = 'Y' and date_format(date_add(wr_3, interval -1 day), '%Y-%m-%d') = date_format(now(), '%Y-%m-%d') ";
/* [YSH : 2025.03.12] 런칭쇼 추가로 인한 메일 발송 수정 */
$tables = array(
    'g5_write_campus' => 'g5_content_mypage',
    'g5_write_launchingShow' => 'g5_content_mypage_show'
);

$chk = 0;
foreach ($tables as $tWrite => $tMypage) {
    $sql = " 
        select w.wr_subject, w.wr_3, m.user_no 
        from ".$tMypage." m, ".$tWrite." w 
        where m.content_no = w.wr_id 
            and m.pre_apply = 'Y' 
            and date_format(wr_3, '%Y-%m-%d %H:00') = date_format(now(), '%Y-%m-%d %H:00') 
    ";
    $result = sql_query($sql, true);

    for ($i=0; $row=sql_fetch_array($result); $i++) {
        $subject = "[{$row['wr_subject']}] 강의가 오픈되었습니다.";
        $mail_content = $content;
    
        $sql = " select * from g5_member where mb_no = '{$row['user_no']}' ";
        $mb = sql_fetch($sql);
        $to_mail = $mb['mb_id']; // 메일 받는 사람
    
        $mail_content = str_replace("{강의제목}", $row['wr_subject'], $mail_content);
        $mail_content = str_replace("{강의날짜}", $row['wr_3'], $mail_content);
        
        mailer("스트라우만", COMMON_SEND_EMAIL, $to_mail, $subject, $mail_content, 1);
        $chk++;
    }
}

echo "총 {$chk}건의 메일이 발송되었습니다.";
?>