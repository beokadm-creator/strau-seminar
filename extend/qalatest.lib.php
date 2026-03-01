<?php
if (!defined('_GNUBOARD_')) exit;

// 1:1 문의 최신글 추출 - /skin/latest/qa_basic_bs
// $cache_time 캐시 갱신시간
function qalatest($skin_dir='', $qa_board, $rows=10, $subject_len=40, $cache_time=1, $options='')
{
    global $config, $member, $g5, $is_admin, $is_member;

    $qaconfig = get_qa_config();
    $qa_subject = $qaconfig['qa_title'];
    // $qa_subject = "1:1 질문답변";
    $qa_board = $g5['qa_content_table'];

    if (!$skin_dir) $skin_dir = 'qa_basic_bs'; // 강제지정

    if(G5_IS_MOBILE) {
        $qa_skin_path = G5_MOBILE_PATH.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
        $qa_skin_url  = G5_MOBILE_URL.'/'.G5_SKIN_DIR.'/latest/'.$skin_dir;
    } else {
        $qa_skin_path = G5_SKIN_PATH.'/latest/'.$skin_dir;
        $qa_skin_url  = G5_SKIN_URL.'/latest/'.$skin_dir;
    }

    $cache_fwrite = false;
    if(G5_USE_CACHE) {
        $cache_file = G5_DATA_PATH."/cache/latest-qa-{$qa_board}-{$skin_dir}-{$rows}-{$subject_len}.php";

        if(!file_exists($cache_file)) {
            $cache_fwrite = true;
        } else {
            if($cache_time > 0) {
                $filetime = filemtime($cache_file);
                if($filetime && $filetime < (G5_SERVER_TIME - 3600 * $cache_time)) {
                    @unlink($cache_file);
                    $cache_fwrite = true;
                }
            }

            if(!$cache_fwrite)
                include($cache_file);
        }
    }

    if(!G5_USE_CACHE || $cache_fwrite) {

        $sql_common = " from {$qa_board} ";
        $sql_search = " where qa_type = '0' ";
        if(!$is_admin){
            $sql_search .= " and mb_id = '{$member['mb_id']}' ";
        }
       
        $sql_order = " order by qa_num ";

        $sql = " select *
                    $sql_common
                    $sql_search
                    $sql_order
                    limit 0, $rows ";
        $result = sql_query($sql);

        $list = array();
        $subject_len = G5_IS_MOBILE ? $qaconfig['qa_mobile_subject_len'] : $qaconfig['qa_subject_len'];
        for($i=0; $row=sql_fetch_array($result); $i++) {
            // $list[$i] = get_list($row, $qa_board, $qa_skin_url, $subject_len);
            $list[$i] = $row;
            $list[$i]['category'] = get_text($row['qa_category']);
            $list[$i]['subject'] = conv_subject($row['qa_subject'], $subject_len, '…');
            if ($stx) {
                $list[$i]['subject'] = search_font($stx, $list[$i]['subject']);
            }
            $list[$i]['view_href'] = G5_BBS_URL.'/qaview.php?qa_id='.$row['qa_id'].$qstr;
            $list[$i]['icon_file'] = '';
            if(trim($row['qa_file1']) || trim($row['qa_file2']))
                $list[$i]['icon_file'] = '<img src="'.$qa_skin_url.'/img/icon_file.gif">';

            $list[$i]['name'] = get_text($row['qa_name']);
            $list[$i]['date'] = substr($row['qa_datetime'], 2, 8);

            $list[$i]['num'] = $num - $i;
        }
   
        if($cache_fwrite) {
            $handle = fopen($cache_file, 'w');
            $cache_content = "<?php\nif (!defined('_GNUBOARD_')) exit;\n\$qa_subject='".$qa_subject."';\n\$list=".var_export($list, true)."?>";
            fwrite($handle, $cache_content);
            fclose($handle);
        }
    }

    /*
    // 같은 스킨은 .css 를 한번만 호출한다.
    if (!in_array($skin_dir, $css) && is_file($qa_skin_path.'/style.css')) {
        echo '<link rel="stylesheet" href="'.$qa_skin_url.'/style.css">';
        $css[] = $skin_dir;
    }
    */

    ob_start();
    include $qa_skin_path.'/latest.skin.php';
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}
?>