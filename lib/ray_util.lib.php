<?php

/**
 * 공통함수 클래스
 */
class Ray_Util {


    // ##### 개발 관련 파라미터 ##### //
    private $_dev_ip = "58.229.223.164";
    public $_dev_mode = false;
    // ##### 개발 관련 파라미터 ##### //

    function __construct($_dev_mode = false){
        $this->_dev_mode = $_dev_mode;
    }

    /*
    * ##########################################
    * 기본 함수
    * ##########################################
    */

    /**
     * 접근 URL 체크하여 메인으로 이동처리
     * require_once G5_LIB_PATH.'/ray_util.lib.php';'
     * $common_util = new Ray_Util();
     * $common_util->chkUrl();
     */
    function chkUrl(){
        if($_SERVER['REMOTE_ADDR'] == $this->_dev_ip ) return true;
            
        $chk_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $chk_domain	= $_SERVER['SERVER_NAME'];
        if(!stristr( $chk_url , $chk_domain)){
            header('Location: /');
            exit;
        }

        return true;
    }
    /**
     * 랜덤 문자열 생성
     */
    function random_string($len = 6) {
        $temp_str = "";
        $rand_set = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'A', 'B', 'C', 'd', 'E', 'F', 'G', 'H', 'x', 'J', 'K', 'b', 'M', 'N', 'y', 'P', 'r', 'R', 'S', 'T', 'u', 'V', 'W', 'X', 'Y', 'Z');
    
        mt_srand( (double) microtime() * 1000000 );
        for($i=1; $i<=$len; $i++){
            $temp_str .= $rand_set[mt_rand(1, count($rand_set))];
        }

        return $temp_str;
    }
    /**
     * 파라미터 반환
     *
     * ->getParam("it_id");
     * ->getParam("it_id", "int", "get");
     * ->getParam("it_id", "", "get");
     * ->getParam("it_id", "", "post");
     *
     */
    function getParam($name, $type="", $method_type=""){
        if($method_type == ""){
            return isset($_REQUEST[$name]) ? $this->paramReplace($_REQUEST[$name], $type) : '';
        }else if($method_type == "get"){
            return isset($_GET[$name]) ? $this->paramReplace($_GET[$name], $type) : '';
        }else if($method_type == "post"){
            return isset($_POST[$name]) ? $this->paramReplace($_POST[$name], $type) : '';
        }
    }

    /**
     * 문자열 검증
     * ->paramReplace($_REQUEST['test'], "int");
     */
    function paramReplace($str, $str_case=''){
        if($str_case === 'time'){
            return preg_replace('/[^0-9 _\-:]/i', '', $str);
        }else if($str_case === 'int'){
            return preg_replace('/[^0-9]/i', '', $str);
        }else{
            return preg_replace('/[^0-9a-z_\-]/i', '', $str);
        }
    }

    /**
     * 체크박스 checked="checked" 반환
     * 값이 같은 경우 반환 $type = ""
     * 값을 포함하는 경우 반환 $type = "inc"
     *
     * $compare_text = "비교할 문자열" : 데이터베이스 값
     * $str_text = "체크박스의 value"
     */
    function checked($compare_text = "", $str_text = "", $type = ""){
        $checked = "";

        if(empty($compare_text)){
            return $checked;
        }

        if($type == "inc"){
            if(strpos($compare_text, $str_text) !== false){
                $checked = ' checked="checked" ';
            }
        }else{
            if($compare_text == $str_text){
                $checked = ' checked="checked" ';
            }
        }

        return $checked;
    }

    /**
     * 체크박스 checked="checked" 반환
     * 값이 같은 경우 반환 $type = ""
     * 값을 포함하는 경우 반환 $type = "inc"
     *
     * $compare_text = "비교할 문자열" : 데이터베이스 값
     * $str_text = "체크박스의 value"
     */
    function selected($compare_text = "", $str_text = "", $type = ""){
        $selected = "";

        if(empty($compare_text)){
            return $selected;
        }

        if($type == "inc"){
            if(strpos($compare_text, $str_text) !== false){
                $selected = ' selected="selected" ';
            }
        }else{
            if($compare_text == $str_text){
                $selected = ' selected="selected" ';
            }
        }

        return $selected;
    }

    /**
     * 날짜형식 Y-m-d
     * format : 미정
     */
    function display_date($str_date = "", $format = ""){
        if(trim($str_date) == ""){
            return "";
        }
        if(strlen($str_date) <10){
            return substr($str_date, 0, strlen($str_date));
        }else{
            return substr($str_date, 0, 10);
        }
    }

    /**
     * JSON encode
     */
    function json_encode($a=false)
    {
        // Some basic debugging to ensure we have something returned
        if (is_null($a)) return 'null';
        if ($a === false) return 'false';
        if ($a === true) return 'true';
        if (is_scalar($a))
        {
            if (is_float($a))
            {
                // Always use '.' for floats.
                return floatval(str_replace(',', '.', strval($a)));
            }
            if (is_string($a))
            {
                static $jsonReplaces = array(array('\\', '/', "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            }
            else
                return $a;
        }
        $isList = true;
        for ($i = 0, reset($a); true; $i++) {
            if (key($a) !== $i)
            {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList)
        {
            foreach ($a as $v) $result[] = json_encode($v);
            return '[' . join(',', $result) . ']';
        }
        else
        {
            foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
            return '{' . join(',', $result) . '}';
        }
    }

    /**
     * JSON 디코드
     */
    function json_decode($json)
    {
        if($json == ""){
            return array();
        }
        $comment = false;
        $out = '$x=';
        for ($i=0; $i<strlen($json); $i++)
        {
            if (!$comment)
            {
                if (($json[$i] == '{') || ($json[$i] == '['))
                    $out .= ' array(';
                else if (($json[$i] == '}') || ($json[$i] == ']'))
                    $out .= ')';
                else if ($json[$i] == ':')
                    $out .= '=>';
                else
                    $out .= $json[$i];
            }
            else
                $out .= $json[$i];
            if ($json[$i] == '"' && $json[($i-1)]!="\\")
                $comment = !$comment;
        }

        eval($out . ';');

        return $x;
    }

    /**
     * $destfile로 파일 업로드 처리.
     */
    function upload_file($srcfile, $destfile, $dir)
    {
        if ($destfile == "") return false;
        // 업로드 한후 , 퍼미션을 변경함
        @move_uploaded_file($srcfile, $dir.'/'.$destfile);
        @chmod($dir.'/'.$destfile, 0644);
        return true;
    }

    /**
     * 특정 폴더의 /년/월/ 형식의 폴더를 생성한다.
     * $custom_util->createDirYm("", date("Y"), date("m"), $prefix = "custom_");
     */
    function createDirYm($folder_nm, $folder_year, $folder_month, $prefix = ""){

        if(empty($folder_nm) || empty($folder_year) || empty($folder_month)){
            return "";
        }

        // 폴더 생성
        $upload_folder = $_SERVER["DOCUMENT_ROOT"]."/data/".$prefix.$folder_nm; // G5_DATA_PATH : ) /home/ableup1/html/data
        if(!is_dir($upload_folder)){
            @mkdir($upload_folder, 0755); // 755
        }
        // 폴더 생성 연도
        $upload_folder_y = $_SERVER["DOCUMENT_ROOT"]."/data/{$prefix}{$folder_nm}/{$folder_year}"; // G5_DATA_PATH : ) /home/ableup1/html/data/2023
        if(!is_dir($upload_folder_y)){
            @mkdir($upload_folder_y, 0755); // 755
        }
        // 폴더 생성 월
        $upload_folder_y_m = $_SERVER["DOCUMENT_ROOT"]."/data/{$prefix}{$folder_nm}/{$folder_year}/{$folder_month}"; // G5_DATA_PATH : ) /home/ableup1/html/data/2023/10
        if(!is_dir($upload_folder_y_m)){
            @mkdir($upload_folder_y_m, 0755); // 755
        }
        $upload_folder_y_m = str_replace($_SERVER["DOCUMENT_ROOT"], "", $upload_folder_y_m);
        return $upload_folder_y_m;
    }

    /**
     * 카테고리를 포함하는 메뉴 HTML 생성 2차까지만.
     * ->getMenuHtml("pc");
     * ->getMenuHtml("mobile");
     */
    function getMenuHtml($device = "pc"){

        $ca_id = $this->getParam('ca_id');

        //2차 메뉴 유지를 위해 2차보다 코드명이 길다면 2차로 변환해준다.
        if(strlen($ca_id) > 4) $ca_id = substr($ca_id, 0, 4);

        $i = 1;
        $menu_html = "";
        $menu_cate_list = get_shop_category_array(true);

        if($device == "pc"){
            foreach($menu_cate_list as $cate1) {
                if( empty($cate1) ) continue;

                $row = $cate1['text'];
                $menu_html .= "<li><a href='{$row['url']}10'>{$row['ca_name']}</a>"; // 1차 메뉴
                $menu_html .= "<ul class=\"sub_menu smenus\" id=\"sub_menu{$i}\">"; // 2차 메뉴 그룹 시작
                // 2단계 분류
                foreach($cate1 as $key=>$cate2) {
                    if( empty($cate2) || $key === 'text' ) continue;

                    $row2 = $cate2['text'];
                    $class_name = "";

                    if($ca_id == $row2['ca_id']) $class_name = "active";

                    $row2 = $cate2['text'];
                    $menu_html .= "<li><a href=\"{$row2['url']}\" class=\"{$class_name}\">{$row2['ca_name']}</a></li>"; // 2차 메뉴
                }
                $menu_html .= '</ul>';
                $menu_html .= '</li>';
                $i++;
            }
        }else{
            foreach($menu_cate_list as $cate1) {
                if( empty($cate1) ) continue;

                $row = $cate1['text'];
                $menu_html .= "<li class=\"menu-item-has-children\"><a href=\"javascript:void(0);\">{$row['ca_name']}</a>"; // 1차 메뉴
                $menu_html .= "<ul class=\"sub_menu\">"; // 2차 메뉴 그룹 시작
                // 2단계 분류
                foreach($cate1 as $key=>$cate2) {
                    if( empty($cate2) || $key === 'text' ) continue;

                    $row2 = $cate2['text'];
                    $class_name = "";

                    if($ca_id == $row2['ca_id']) $class_name = "active";

                    $row2 = $cate2['text'];
                    $menu_html .= "<li><a href=\"{$row2['url']}\" class=\"{$class_name}\">{$row2['ca_name']}</a></li>"; // 2차 메뉴
                }
                $menu_html .= '</ul>';
                $menu_html .= '</li>';
                $i++;
            }
        }

        return $menu_html;
    }

    /**
     * 해당 뎁스의 카테고리 목록
     * ->getCateList(); 1차 카테고리
     * ->getCateList("10", 2); 2차 카테고리
     * ->getCateList("1010", 3); 3차 카테고리
     */
    function getCateList($p_ca_id = "", $depth = 1){
        global $g5;

        $cate_list = array();
        $depth_len = $depth * 2;

        $where = array();
        if($depth == 1 || $p_ca_id == ""){

        }else{
            if($depth == 2){
                $p_ca_id = substr($p_ca_id, 0, 2);
            }else if($depth == 3){
                $p_ca_id = substr($p_ca_id, 0, 4);
            }
            $where[] = " ca_id like '{$p_ca_id}%' ";
        }
        $where[] = " length(ca_id) = {$depth_len} ";
        $where[] = " ca_use = 1 ";

        $sql_search = ' where '.implode(' and ', $where);

        $sql = " select * from {$g5['g5_shop_category_table']} {$sql_search} ";
        $result = sql_query($sql);

        for($i=0; $row=sql_fetch_array($result); $i++) {
            $cate_list[] = $row;
        }

        return $cate_list;
    }

    /**
     * 슬라이드 형태의 달력을 반환
     * $calendar_arr = array();
     * $calendar_arr[] = array("date"=>"2023-07-12", "marker"=>"possible");
     * $calendar_arr[] = array("date"=>"2023-08-16", "marker"=>"confirm");
     * $calendar_arr[] = array("date"=>"2023-09-12", "marker"=>"end");
     *
     *  ->getCalendarTableBySlideHtml($calendar_arr);
     */
    function getCalendarTableBySlideHtml($calendar_arr = array()){
        $result = "";

        $result .= "<div class=\"swiper calendar_Swiper\">\n";
        $result .= "<div class=\"swiper-wrapper\">\n";

        // 캘린더
        if(sizeof($calendar_arr) > 0){
            foreach ($calendar_arr as $key => $value) {
                $result .= $this->getCalendarTable($value['date'], $value['marker']);
            }
        }

        $result .= "</div>\n";
        $result .= "
            <div class='swiper-button-next'></div>
            <div class='swiper-button-prev'></div>\n
        ";
        $result .= "</div>\n";

        $result .= "
            <script>
            var swiper = new Swiper(\".calendar_Swiper\", {
                spaceBetween: 13,    // 슬라이드 사이 여백
                slidesPerView : '3', // 한 슬라이드에 보여줄 갯수
                //centeredSlides: true,    //센터모드
                navigation: {
                    nextEl: \".swiper-button-next\",
                    prevEl: \".swiper-button-prev\",
                },
                breakpoints: {
                    300: {
                        slidesPerView: 1,  //브라우저가 768보다 클 때
                        spaceBetween: 13,
                    },
                    600: {
                        slidesPerView: 2,  //브라우저가 768보다 클 때
                        spaceBetween: 10,
                        //centeredSlides: false,    //센터모드
                    },
                    980: {
                        slidesPerView: 3,  //브라우저가 1024보다 클 때
                        spaceBetween: 13,
                        //centeredSlides: true,    //센터모드
                    },
                },
            });
            </script>
        ";
        return $result;
    }
    /**
     * 입력받은 날짜의 캘린더를 HTML로 반환
     * ->getCalendarTableHtml("2023-09-10");
     */
    function getCalendarTableHtml($str_date = "", $marker = ""){
        $result = "";

        $date = empty($str_date) ? date('Y-m-d') : $str_date;

        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $firstDate = date('Y-m-01',strtotime($date));
        $firstDateCalendarNo = date('w',strtotime($firstDate));
        $lastDateDay = date('t', strtotime($date));
        $lastDateCalendarNo = date('w',strtotime( date('Y-m-'.$lastDateDay,strtotime($date)) ));

        $prevDate = date('Y-m-d',strtotime("-1 month",strtotime($firstDate))); // 이전달 정보
        $nextDate = date('Y-m-d',strtotime("+1 month",strtotime($firstDate))); // 다음달 정보
        $prevLastDateDay = date('t', strtotime($prevDate));

        $week = array();
        $day = 1;
        while(1){
            $subWeek = array();
            for($i=0;$i<7;$i++){
                if( $day > $lastDateDay){

                    // 마지막 날의 날짜코드가 6이 아니라면, 다음달을 미리 넣어준다.
                    if( $lastDateCalendarNo != 6){
                        for($si=0;$si < 6-$lastDateCalendarNo; $si++){
                            $thisDate = date('Y-m-'.($si+1),strtotime("+1 month",strtotime($firstDate)));
                            $subWeek[] = array('type'=>'next', 'day'=>$si+1,'date'=>$thisDate,'calendar_no'=>date('w',strtotime($thisDate)));
                        }
                    }
                    break;
                }
                if( $day == 1 && $firstDateCalendarNo != 0){
                    for($si=($prevLastDateDay-$firstDateCalendarNo);$si < $prevLastDateDay; $si++){
                        $thisDate = date('Y-m-'.($si+1),strtotime("-1 month",strtotime($firstDate)));
                        $subWeek[] = array('type'=>'prev', 'day'=>$si+1,'date'=>$thisDate,'calendar_no'=>date('w',strtotime($thisDate)));
                    }
                    $i += $firstDateCalendarNo;
                }

                $thisDate = date('Y-m-'.sprintf('%02d', $day),strtotime($firstDate));
                $subWeek[] = array('type'=>'now', 'day'=>$day,'date'=>$thisDate,'calendar_no'=>date('w',strtotime($thisDate)));
                $day++;
            }
            $week[] = $subWeek;
            if( $day > $lastDateDay){ break; }
        }
        $result = '
        <div class="calendar_wrap swiper-slide">
            <div class="calendar_ym">
                <p>'.$year.'년 '.($month+0).'월</p>
            </div>
            <table id="calendar">
                <tr>
                    <th>일</th>
                    <th>월</th>
                    <th>화</th>
                    <th>수</th>
                    <th>목</th>
                    <th>금</th>
                    <th>토</th>
                </tr>
        ';
        foreach($week as $k=>$v){
            $result .= '<tr>';
            foreach($v as $day_key=>$day_v){
                $class_name = "calendar_no_{$day_v['calendar_no']}";

                if($day_v['type'] != "now"){
                    $class_name .= " not_this"; // 해당월의 날짜가 아니라면 not_this 클래스 추가
                }
                $onclick = "fnDayClick(`{$day_v['date']}`)";

                if($date == $day_v['date']){
                    if($marker == "possible"){
                        $class_name .= " possible cal_mark"; // 선택된 날짜에 active 클래스 추가
                    }else if($marker == "confirm"){
                        $class_name .= " confirm cal_mark"; // 선택된 날짜에 active 클래스 추가
                    }else if($marker == "end"){
                        $class_name .= " end cal_mark"; // 선택된 날짜에 active 클래스 추가
                    }else{
                        $onclick = ""; // 포함되지 않는다면 클릭 이벤트 삭제
                    }
                }
                $result .= "<td class='{$class_name}' title='{$day_v['date']}' onclick='{$onclick}'>{$day_v['day']}</td>";
            }
            $result .= '</tr>';
        }
        $result .= "
            </table>
        </div>
        ";

        return $result;
    }

    /**
     * 입력받은 날짜의 캘린더를 HTML로 반환
     * ->getCalendarTable("2023-09-10");
     */
    function getCalendarTable($str_date = "", $marker = ""){
        $result = array();

        $date = empty($str_date) ? date('Y-m-d') : $str_date;

        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $firstDate = date('Y-m-01',strtotime($date));
        $firstDateCalendarNo = date('w',strtotime($firstDate));
        $lastDateDay = date('t', strtotime($date));
        $lastDateCalendarNo = date('w',strtotime( date('Y-m-'.$lastDateDay,strtotime($date)) ));

        $prevDate = date('Y-m-d',strtotime("-1 month",strtotime($firstDate))); // 이전달 정보
        $nextDate = date('Y-m-d',strtotime("+1 month",strtotime($firstDate))); // 다음달 정보
        $prevLastDateDay = date('t', strtotime($prevDate));

        $week = array();
        $day = 1;
        while(1){
            $subWeek = array();
            for($i=0;$i<7;$i++){
                if( $day > $lastDateDay){

                    // 마지막 날의 날짜코드가 6이 아니라면, 다음달을 미리 넣어준다.
                    if( $lastDateCalendarNo != 6){
                        for($si=0;$si < 6-$lastDateCalendarNo; $si++){
                            $thisDate = date('Y-m-'.($si+1),strtotime("+1 month",strtotime($firstDate)));
                            $subWeek[] = array('type'=>'next', 'day'=>$si+1,'date'=>$thisDate,'calendar_no'=>date('w',strtotime($thisDate)));
                        }
                    }
                    break;
                }
                if( $day == 1 && $firstDateCalendarNo != 0){
                    for($si=($prevLastDateDay-$firstDateCalendarNo);$si < $prevLastDateDay; $si++){
                        $thisDate = date('Y-m-'.($si+1),strtotime("-1 month",strtotime($firstDate)));
                        $subWeek[] = array('type'=>'prev', 'day'=>$si+1,'date'=>$thisDate,'calendar_no'=>date('w',strtotime($thisDate)));
                    }
                    $i += $firstDateCalendarNo;
                }

                $thisDate = date('Y-m-'.sprintf('%02d', $day),strtotime($firstDate));
                $subWeek[] = array('type'=>'now', 'day'=>$day,'date'=>$thisDate,'calendar_no'=>date('w',strtotime($thisDate)));
                $day++;
            }
            $week[] = $subWeek;
            if( $day > $lastDateDay){ break; }
        }

        $result = array("week"=>$week, "date"=>$date,  "year"=>$year, "month"=>$month, "prevDate"=>$prevDate, "nextDate"=>$nextDate);

        return $result;
    }

    /**
     * 요일 정보를 반환
     */
    function dayName($str_date = ""){
        if($str_date == ""){
            return "";
        }

        $yoil = array("일","월","화","수","목","금","토");
        return ($yoil[date('w', strtotime($str_date))]);
    }
    /**
     * 날짜 포맷
     */
    function dateFormat($str_date = "", $format = "Y-m-d"){
        if($str_date == ""){
            return "";
        }

        $str = strtotime($str_date);
        $date = date($format, $str);
        return $date;
    }

    /**
     * 시작 년월 ~ 마지막 년월 배열로 반환한다.
     */
    function dateArrayYm($date_str_arr = array()){
        if(sizeof($date_str_arr) == 0){
            return array();
        }

        $tmp_arr = array();

        if(sizeof($date_str_arr) > 1){
            $s_month = $date_str_arr[0];
            $e_month = $date_str_arr[sizeof($date_str_arr)-1];

            $tmp_arr[] = $s_month;


            while(1){
                $thisMonth = date('Y-m', strtotime("+1 month", strtotime($s_month)));
                $s_month = $thisMonth;
                $tmp_arr[] = $s_month;

                if( $thisMonth == $e_month){ break; }
            }
            $date_str_arr = $tmp_arr;
        }

        return $tmp_arr;
    }

    /**
     * 중복 배열 제거
     */
    function duplicateRemoveArray($tmp_arr = array()){
        return array_unique($tmp_arr, SORT_REGULAR);
    }

    /**
     * PHP startsWith
     */
    function startsWith($full_word, $find_word) {
        return $find_word === "" || strrpos($full_word, $find_word, -strlen($full_word)) !== false;
    }

    /**
     * PHP endsWith
     */
    function endsWith($full_word, $find_word) {
        return $find_word === "" || (($temp = strlen($full_word) - strlen($find_word)) >= 0 && strpos($full_word, $find_word, $temp) !== false);
    }


    /**
     * 전체 레코드수 반환
     * table
     * search
     */
    function getTotalCount($table = "", $sql_search = ""){

        $sql_common = " from {$table} ";
        $sql_common .= $sql_search;

        // 테이블의 전체 레코드수만 얻음
        $sql = " select count(*) as cnt " . $sql_common;
        $row = sql_fetch($sql);
        $total_count = $row['cnt'];

        return $total_count;
    }

    /**
     * 랜덤 파일명
     */
    function replace_filename($name){
        @session_start();
        $ss_id = session_id();
        $usec = get_microtime();
        $file_path = pathinfo($name);
        $ext = $file_path['extension'];
        $return_filename = sha1($ss_id.$_SERVER['REMOTE_ADDR'].$usec);

        if( $ext )
            $return_filename .= '.'.$ext;

        return $return_filename;
    }

    /**
     * XSS 관련 태그 제거
     * $변수 = clean_xss_tags($_POST['변수']);
     * $타이틀 = isset($_POST['타이틀']) ? clean_xss_tags($_POST['타이틀'], 1, 1) : '';
     * $메모 = clean_xss_tags($_POST['메모'], 0, 1, 0, 0);
     */
    function clean_xss_tags($str, $check_entities=0, $is_remove_tags=0, $cur_str_len=0, $is_trim_both=1)
    {
        if( $is_trim_both ) {
            // tab('\t'), formfeed('\f'), vertical tab('\v'), newline('\n'), carriage return('\r') 를 제거한다.
            $str = preg_replace("#[\t\f\v\n\r]#", '', $str);
        }

        if( $is_remove_tags ){
            $str = strip_tags($str);
        }

        if( $cur_str_len ){
            $str = utf8_strcut($str, $cur_str_len, '');
        }

        $str_len = strlen($str);

        $i = 0;
        while($i <= $str_len){
            $result = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $str);

            if( $check_entities ){
                $result = str_replace(array('&colon;', '&lpar;', '&rpar;', '&NewLine;', '&Tab;'), '', $result);
            }

            $result = preg_replace('#([^\p{L}]|^)(?:javascript|jar|applescript|vbscript|vbs|wscript|jscript|behavior|mocha|livescript|view-source)\s*:(?:.*?([/\\\;()\'">]|$))#ius',
                '$1$2', $result);

            if((string)$result === (string)$str) break;

            $str = $result;
            $i++;
        }

        return $str;
    }

    /**
     * XSS 어트리뷰트 태그 제거
     * $변수명 = isset($_POST['변수명']) ? strip_tags(clean_xss_attributes($_POST['변수명'])) : '';
     */
    function clean_xss_attributes($str)
    {
        $xss_attributes_string = 'onAbort|onActivate|onAttribute|onAfterPrint|onAfterScriptExecute|onAfterUpdate|onAnimationCancel|onAnimationEnd|onAnimationIteration|onAnimationStart|onAriaRequest|onAutoComplete|onAutoCompleteError|onAuxClick|onBeforeActivate|onBeforeCopy|onBeforeCut|onBeforeDeactivate|onBeforeEditFocus|onBeforePaste|onBeforePrint|onBeforeScriptExecute|onBeforeUnload|onBeforeUpdate|onBegin|onBlur|onBounce|onCancel|onCanPlay|onCanPlayThrough|onCellChange|onChange|onClick|onClose|onCommand|onCompassNeedsCalibration|onContextMenu|onControlSelect|onCopy|onCueChange|onCut|onDataAvailable|onDataSetChanged|onDataSetComplete|onDblClick|onDeactivate|onDeviceLight|onDeviceMotion|onDeviceOrientation|onDeviceProximity|onDrag|onDragDrop|onDragEnd|onDragEnter|onDragLeave|onDragOver|onDragStart|onDrop|onDurationChange|onEmptied|onEnd|onEnded|onError|onErrorUpdate|onExit|onFilterChange|onFinish|onFocus|onFocusIn|onFocusOut|onFormChange|onFormInput|onFullScreenChange|onFullScreenError|onGotPointerCapture|onHashChange|onHelp|onInput|onInvalid|onKeyDown|onKeyPress|onKeyUp|onLanguageChange|onLayoutComplete|onLoad|onLoadedData|onLoadedMetaData|onLoadStart|onLoseCapture|onLostPointerCapture|onMediaComplete|onMediaError|onMessage|onMouseDown|onMouseEnter|onMouseLeave|onMouseMove|onMouseOut|onMouseOver|onMouseUp|onMouseWheel|onMove|onMoveEnd|onMoveStart|onMozFullScreenChange|onMozFullScreenError|onMozPointerLockChange|onMozPointerLockError|onMsContentZoom|onMsFullScreenChange|onMsFullScreenError|onMsGestureChange|onMsGestureDoubleTap|onMsGestureEnd|onMsGestureHold|onMsGestureStart|onMsGestureTap|onMsGotPointerCapture|onMsInertiaStart|onMsLostPointerCapture|onMsManipulationStateChanged|onMsPointerCancel|onMsPointerDown|onMsPointerEnter|onMsPointerLeave|onMsPointerMove|onMsPointerOut|onMsPointerOver|onMsPointerUp|onMsSiteModeJumpListItemRemoved|onMsThumbnailClick|onOffline|onOnline|onOutOfSync|onPage|onPageHide|onPageShow|onPaste|onPause|onPlay|onPlaying|onPointerCancel|onPointerDown|onPointerEnter|onPointerLeave|onPointerLockChange|onPointerLockError|onPointerMove|onPointerOut|onPointerOver|onPointerUp|onPopState|onProgress|onPropertyChange|onqt_error|onRateChange|onReadyStateChange|onReceived|onRepeat|onReset|onResize|onResizeEnd|onResizeStart|onResume|onReverse|onRowDelete|onRowEnter|onRowExit|onRowInserted|onRowsDelete|onRowsEnter|onRowsExit|onRowsInserted|onScroll|onSearch|onSeek|onSeeked|onSeeking|onSelect|onSelectionChange|onSelectStart|onStalled|onStorage|onStorageCommit|onStart|onStop|onShow|onSyncRestored|onSubmit|onSuspend|onSynchRestored|onTimeError|onTimeUpdate|onTimer|onTrackChange|onTransitionEnd|onToggle|onTouchCancel|onTouchEnd|onTouchLeave|onTouchMove|onTouchStart|onTransitionCancel|onTransitionEnd|onUnload|onURLFlip|onUserProximity|onVolumeChange|onWaiting|onWebKitAnimationEnd|onWebKitAnimationIteration|onWebKitAnimationStart|onWebKitFullScreenChange|onWebKitFullScreenError|onWebKitTransitionEnd|onWheel';

        do {
            $count = $temp_count = 0;

            $str = preg_replace(
                '/(.*)(?:' . $xss_attributes_string . ')(?:\s*=\s*)(?:\'(?:.*?)\'|"(?:.*?)")(.*)/ius',
                '$1-$2-$3-$4',
                $str,
                -1,
                $temp_count
            );
            $count += $temp_count;

            $str = preg_replace(
                '/(.*)(?:' . $xss_attributes_string . ')\s*=\s*(?:[^\s>]*)(.*)/ius',
                '$1$2',
                $str,
                -1,
                $temp_count
            );
            $count += $temp_count;

        } while ($count);

        return $str;
    }


    /**
     * 파일업로드 파일 삭제 및 데이터 삭제
     * $custom_util->delUploadFile($upload_table, $folder_nm, $wr_id, $i);
     */
    function delUploadFile($upload_table = "", $folder_nm = "", $wr_id = 0, $f_no = 0){

        if($upload_table == "" || $folder_nm == "" || $wr_id == 0 || $f_no == 0){
            return false;
        }

        $sql = "SELECT * FROM {$upload_table} WHERE f_gubun = '{$folder_nm}' AND wr_id = '{$wr_id}' AND f_no = '{$f_no}' ";
        $file_info = sql_fetch($sql);

        if($file_info['wr_id']){
            @unlink("{$_SERVER["DOCUMENT_ROOT"]}/{$file_info['f_path']}/{$file_info['f_filename']}");
            // 파일삭제
            $sql = "DELETE FROM {$upload_table} WHERE f_gubun = '{$folder_nm}' AND wr_id = '{$wr_id}' AND f_no = '{$f_no}' ";
            sql_query($sql);
        }

        return true;
    }

    /**
     * 파일업로드 특정 타겟의 전체 목록 가져오기
     */
    function getUploadFileList($f_gubun = "", $wr_id = 0){
        $result_list = array();

        if($f_gubun == "" || $wr_id == 0){
            return $result_list;
        }

        $sql = " select * from tb_upload_file where f_gubun = '{$f_gubun}' and wr_id = '{$wr_id}' ";
        $result = sql_query($sql);

        for($i=0; $row=sql_fetch_array($result); $i++) {
            $row['bn_img'] = "/data/banner_{$type}/{$row['bn_id']}";
            $result_list[] = $row;
        }

        return $result_list;
    }

    /**
     * 파일업로드 1개 가져오기
     */
    function getUploadFile($f_gubun = "", $wr_id = 0, $f_no = 1){

        if($f_gubun == "" || $wr_id == 0){
            return false;
        }

        $sql = " select * from tb_upload_file where f_gubun = '{$f_gubun}' and wr_id = '{$wr_id}' and f_no = '{$f_no}' limit 1 ";
        $result = sql_fetch($sql);

        return $result;
    }

    /**
     * 유투브 썸네일 가져오기
     */
    function getYoutubeThumb($url = "", $size = "max"){

        if($url == ""){
            return "";
        }

        $regExp = '/^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*/';
        preg_match($regExp, $url, $matches);
        $youtube_id = $matches[7];

        if($size == "max"){
            $youtube_thumb_img="<img src=\"https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg\" alt=\"\">";
        }else if($size == "mq"){
            $youtube_thumb_img="<img src=\"https://img.youtube.com/vi/{$youtube_id}/mqdefault.jpg\" alt=\"\">";
        }else{
            $youtube_thumb_img="<img src=\"https://img.youtube.com/vi/{$youtube_id}/default.jpg\" alt=\"\">";
        }

        return $youtube_thumb_img;
    }

    /**
     * 엑셀 및 등록 관련 필드정보
     */
    function getTableField($type = ''){

        // if($type == ''){
        // }else{
        // }

        $table_field = array(
            array( 'field'=>'wr_id',                'title'=>'고유키',               'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_company',           'title'=>'기업명',               'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_representative',    'title'=>'대표자명',              'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_company_type',      'title'=>'기업형태',              'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_regular_worker',    'title'=>'상시근로자',             'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_business',          'title'=>'주요사업',              'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_1',                 'title'=>'홈페이지',              'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_incharge',          'title'=>'담당자명',              'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_position',          'title'=>'부서명/직위',            'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_tel',               'title'=>'연락처',               'class'=>'td_left', 'type'=>'phone' ),
            array( 'field'=>'wr_email',             'title'=>'이메일',                'class'=>'td_left', 'type'=>'email' ),
            array( 'field'=>'wr_pship_type',        'title'=>'제휴유형',              'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_request',           'title'=>'요청내용',              'class'=>'td_mng_m', 'type'=>'text' ),
            array( 'field'=>'wr_datetime',          'title'=>'접수일',               'class'=>'td_mng_l', 'type'=>'date' ),
        );

        return $table_field;
    }

    /**
     * 엑셀 다운로드에 사용됨, 타입에 따라 값 재가공
     * text, phone, email, date, text_split
     * $val = $custom_util->getExcelField($table_field[$j]['type'], $val, 'excel');
     * getTableField 와 같이 사용됨
     */
    function getExcelField($type = 'text', $word, $file_export = ''){
        $return_word = $word;

        if($type == 'text')
        {
            $return_word = strip_tags(trim(clean_xss_attributes($word)));
        }
        else if($type == 'text_split')
        {
            if($file_export == 'excel'){
                $lfcr = chr(10);
                $return_word = str_replace('|', $lfcr, trim($word));
            }else{
                $return_word = str_replace('|', '<br>', trim($word));
            }
        }
        else if($type == 'phone')
        {
            $return_word = $word;
        }
        else if($type == 'email')
        {
            $return_word = $word;
        }
        else if($type == 'date')
        {
            if(trim($word) != ''){
                $return_word = date("Y-m-d H:i", strtotime($word));
            }
        }

        return $return_word;
    }

    /**
     * POST > JSON 인코딩 샘플
     */
    function getSampleByPost($posts, $post_key=""){

        $post_arr = array();

        if($post_key == "")
            return $post_arr;

        $post_data = $posts[$post_key];
        if(!is_array($post_data))
            return $post_arr;

        $val2_key = str_replace("_title", "_link", $post_key);
        for ($i=0; $i < sizeof($post_data); $i++) {
            $val1 = $post_data[$i];
            $val2 = $posts[$val2_key][$i];

            if($val1 && $val2){
                $temp = array();
                $temp["title"]       = $val1;
                $temp["link"]        = $val2;

                $post_arr[] = $temp;
            }
        }

        return json_encode($post_arr);
    }

    /**
     * ROW 샘플
     */
    function getSample($idx = 0, $tb_name = ""){
        if($idx == 0 || $tb_name == ""){
            return false;
        }

        $where = array();
        $where[] = " wr_id = {$idx} ";
        $sql_search = ' where '.implode(' and ', $where);

        $sql = " select * from g5_write_{$tb_name} {$sql_search} ";
        $result = sql_fetch($sql);

        // JSON DECODE
        // $result['wr_5'] = $this->json_decode($result['wr_5']);

        return $result;
    }

    /**
     * LIST 샘플
     */
    function getSampleList(){

        // global $g5;
        $result_list = array();

        $tb_name = "테이블명";

        $where = array();
        $where[] = " wr_use=1 ";

        $sql_search = ' where '.implode(' and ', $where);
        $order_by = " order by wr_order desc, wr_id desc";

        $sql = " select * from {$tb_name} {$sql_search} {$order_by} ";
        $result = sql_query($sql);

        for($i=0; $row=sql_fetch_array($result); $i++) {
            $row['wr_img'] = "/data/write_person/{$row['wr_id']}";
            $result_list[] = $row;
        }

        return $result_list;
    }

    /**
     * 개발에 필요한 테이블 생성
     * $custom_util->createTb();
     */
    function createTb(){

        // 파일업로드 생성
        if (!sql_query(" DESCRIBE tb_upload_file ", false)) {
            $create_query = sql_query(
                " CREATE TABLE IF NOT EXISTS `tb_upload_file` (
                    f_gubun     varchar(20)  default ''                    not null,
                    wr_id       int          default 0                     not null,
                    f_no        int          default 0                     not null,
                    f_source    varchar(255) default ''                    not null,
                    f_filename  varchar(255) default ''                    not null,
                    f_download int                                        not null,
                    f_fileurl  varchar(255) default ''                    not null,
                    f_thumburl varchar(255) default ''                    not null,
                    f_filesize int          default 0                     not null,
                    bf_type     tinyint      default 0                     not null,
                    f_datetime datetime     default '0000-00-00 00:00:00' not null comment '등록일',
                    primary key (f_gubun, wr_id, f_no)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", true );
        }

        if (!sql_query(" DESCRIBE tb_write_name ", false)) {
            $create_query = sql_query(
                " CREATE TABLE IF NOT EXISTS `tb_write_name` (
                    wr_id             int auto_increment primary key comment '고유키',
                    wr_title          varchar(255)  null DEFAULT '' comment '제목',
                    wr_order          int  null DEFAULT 0 comment '정렬순서 오름차순',
                    wr_use            int  null DEFAULT 1 comment '적용여부 0: 사용안함, 1: 사용함',
                    wr_content1        mediumtext  not null DEFAULT '' comment '내용1',
                    wr_content2        mediumtext  not null DEFAULT '' comment '내용2',
                    wr_content3        mediumtext  not null DEFAULT '' comment '내용3',
                    wr_img            varchar(255)  null DEFAULT '' comment '이미지',
                    wr_1              varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_2              varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_3              varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_4              varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_5              varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_6              varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_7              varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_8              varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_9              varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_10             varchar(100)  null DEFAULT '' comment '추가필드',
                    wr_datetime      datetime   default '0000-00-00 00:00:00' not null comment '등록일',
                    wr_update_time   datetime   default '0000-00-00 00:00:00' not null comment '수정일'
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", true );
        }

    }
    /**
     * 시작일 체크 2024-01-11 11:00 형식
     * start_day_check("2024-01-11 11:00")
     * true : 시작됨, false : 시작 되지 않음
     */
    function start_day_check($custom_date) {
        if($custom_date == "") return false;
    
        $custom_date = $custom_date.":00";
    
        $now_dt = new DateTime();
        $custom_date = new DateTime($custom_date);
        if($custom_date >= $now_dt) return false;
    
        return true;
    }
    
    /**
     * OPC 이석환 차장님 메일발송 소스 적용
     */
    function send_mail($nameFrom,$mailFrom,$nameTo,$mailTo,$cc,$bcc,$subject,$content){
    
		$charset = "UTF-8";

		$nameFrom   = "=?$charset?B?".base64_encode($nameFrom)."?=";
		$nameTo   = "=?$charset?B?".base64_encode($nameTo)."?=";
		$subject = "=?$charset?B?".base64_encode($subject)."?=";

		$header  = "Content-Type: text/html; charset=utf-8\r\n";
		$header .= "MIME-Version: 1.0\r\n";

		$header .= "Return-Path: <". $mailFrom .">\r\n";
		$header .= "From: ". $nameFrom ." <". $mailFrom .">\r\n";
		$header .= "Reply-To: <". $mailFrom .">\r\n";
		if ($cc)  $header .= "Cc: ". $cc ."\r\n";
		if ($bcc) $header .= "Bcc: ". $bcc ."\r\n";

		mail($mailTo, $subject, $content, $header, '-f'.$mailFrom);

//			if(mail($mailTo, $subject, $content, $header, $mailFrom)){
//				echo "메일 성공";
//			}else{
//				echo "메일 실패";
//			}

	}

    /*
    * ##########################################
    * 추가 함수
    * ##########################################
    */

    /**
     * 메인 슬라이드 onclick 함수 생성
     * fnLectureMove(118, '2024-07-11 09:00',`https://stkr-edu.com/campus/118`, 'ing', 'N');
     */
    function get_main_slide_lecture($wr_id = ""){
        global $member;

        if($wr_id == ""){
            return "javascript:alert('관리자에게 문의하세요');";
        }

        $slide_sql = "select * from g5_write_campus where wr_id = '{$wr_id}' ";
        $slide_item = sql_fetch($slide_sql);
        $slide_lec_use = "";
        $sql_lec = "select count(no) as cnt from g5_content_mypage where content_no = '{$wr_id}' and user_no = '{$member["mb_no"]}' order by no ";
        $row_lec = sql_fetch($sql_lec);
        if($row_lec['cnt'] == 0){
            $slide_lec_use = "N";
        }
        $slide_lec_status = $this->start_day_check($slide_item['wr_3']) ? 'ing':'wait';
        $onclick_txt = "fnLectureMove({$wr_id}, '{$slide_item['wr_3']}',`https://stkr-edu.com/campus/{$wr_id}`, '{$slide_lec_status}', '{$slide_lec_use}');";

        return $onclick_txt;
    }
}

?>