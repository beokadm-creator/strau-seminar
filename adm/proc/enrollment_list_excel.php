<?php
include_once('./_common.php');

$where = " where mb_id <> 'admin' ";

if (!$sst) {
    $sst = "mb_datetime";
    $sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select * from {$g5['member_table']} {$where} {$sql_order} ";
$result = sql_query( $sql );


if(!@sql_num_rows($result))
    alert_close('내역이 없습니다.');

if(! function_exists('column_char')) {
    function column_char($i) {
        return chr( 65 + $i );
    }
}

include_once(G5_LIB_PATH.'/PHPExcel.php');

$headers = array('아이디', '상태', '휴대폰', '이름', '직업', '면허번호', '치과명/학교명', '지역', '제품사용여부','가입경로', '이메일', '가입일');

$widths  = array(35, 20, 20, 20, 20, 20, 20, 20, 35, 35, 25);
$header_bgcolor = 'FFABCDEF';
$last_char = column_char(count($headers) - 1);
$rows = array();


for($i=1; $row=sql_fetch_array($result); $i++){
    $mb_type = "";
	switch($row['mb_level']){
		case "2": 
            $mb_type = '비승인'; 
            break;
		case "3": 
            $mb_type = '승인'; 
            break;
		default: 
            $mb_type = "비승인"; 
            break;
	}
	$mb_7 = ($row['mb_7'] != "") ? ',' . $row['mb_7'] : '';


	$mb_7 = ($row['mb_7']!="")?''.$row['mb_7'] : '';
	$mb_6 = $row['mb_6'].str_replace("기타,", "", $mb_7);


	$rows[] = array(
		$row['mb_id'],
		$mb_type,
		$row['mb_hp'],
		$row['mb_name'],
		$row['mb_1'],
		$row['mb_2'],
		$row['mb_3'],
		$row['mb_4'],
		$row['mb_5'],
		$mb_6,
		$row['mb_email'],
		substr($row['mb_datetime'], 0, 10)
	);
}

$data = array_merge(array($headers), $rows);

$excel = new PHPExcel();
$excel->setActiveSheetIndex(0)->getStyle( "A1:${last_char}1" )->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($header_bgcolor);
$excel->setActiveSheetIndex(0)->getStyle( "A:$last_char" )->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setWrapText(true);
foreach($widths as $i => $w) $excel->setActiveSheetIndex(0)->getColumnDimension( column_char($i) )->setWidth($w);
$excel->getActiveSheet()->fromArray($data,NULL,'A1');

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"member_list-".date("ymd", time()).".xls\"");
header("Cache-Control: max-age=0");

$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
$writer->save('php://output');
?>
