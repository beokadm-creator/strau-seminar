<?php
include_once('./_common.php');
require_once G5_LIB_PATH.'/ray_util.lib.php';
$common_util = new Ray_Util();


if(isset($wr_id) && $wr_id != ""){
    // $where = " where mb_id <> 'admin' ";
    $where = " where campus.wr_id = '{$wr_id}' ";
}

if (!$sst) {
    $sst = "c.content_no";
    $sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";
$sql = " select * from g5_content_mypage c JOIN g5_write_campus campus ON campus.wr_id = c.content_no LEFT JOIN g5_member m ON m.mb_no = c.user_no {$where} {$sql_order} ";
$result = sql_query( $sql );

if(!@sql_num_rows($result))
    alert_close('내역이 없습니다.');

if(! function_exists('column_char')) {
    function column_char($i) {
        return chr( 65 + $i );
    }
}

include_once(G5_LIB_PATH.'/PHPExcel.php');

$headers = array('강의제목','구분','아이디', '이름', '상태', '휴대폰', '직업', '면허번호', '치과명/학교명', '지역', '제품사용여부', '가입경로', '이메일', '가입일');

$widths  = array(35, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20);
$header_bgcolor = 'FFABCDEF';
$last_char = column_char(count($headers) - 1);
$rows = array();

for($i=1; $row=sql_fetch_array($result); $i++){
    $percent = "0";
    $complete = "수강신청";
    if($row['percent'] > 0){
        $percent = $row['percent'];
        $complete = "수강중";
    }

    if($row['percent'] == "100"){
        $complete = "수강완료";
    }

	if($row['type'] == "live"){
		$complete = "수강완료(실시간)";
		if($common_util->start_day_check($row['wr_3']) == false){
			$complete = "수강전(실시간)";
		}
	}
    $gubun = $row['pre_apply'] == "Y" ? '사전':'일반';
    

    $mb_7 = ($row['mb_7']!="")?''.$row['mb_7'] : '';
    $mb_6 = $row['mb_6'].str_replace("기타,", "", $mb_7);

    $sql = "select wr_subject from g5_write_campus where wr_id = '{$row["content_no"]}' ";
    $wr = sql_fetch($sql);
    if($wr){
        $rows[] = array(
            $wr['wr_subject'],
            $gubun,
            $row['mb_id'],
            $row['mb_name'],
            $complete,
            $row['mb_hp'],
            $row['mb_1'],
            $row['mb_2'],
            $row['mb_3'],
            $row['mb_4'],
            $row['mb_5'],
            $mb_6,
            $row['mb_id'],
            $row['mb_datetime'],
        );
    }

}

$data = array_merge(array($headers), $rows);

$excel = new PHPExcel();
$excel->setActiveSheetIndex(0)->getStyle( "A1:${last_char}1" )->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($header_bgcolor);
$excel->setActiveSheetIndex(0)->getStyle( "A:$last_char" )->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
foreach($widths as $i => $w) $excel->setActiveSheetIndex(0)->getColumnDimension( column_char($i) )->setWidth($w);

$excel->getActiveSheet()->getStyle('H2:H'.(count($data)))->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

$excel->getActiveSheet()->fromArray($data,NULL,'A1');

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"lecture_list-".date("ymdhis", time()).".xls\"");
header("Cache-Control: max-age=0");

$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
$writer->save('php://output');
?>
