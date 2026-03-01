<?php
/**
 * Certificates API Routes
 * 
 * 수료증 관련 API 엔드포인트
 * - GET /api/certificates/:id - 수료증 PDF 다운로드
 */

global $request_method, $resource_id;

// 모든 엔드포인트는 인증 필요
require_auth();

// 수료증 PDF 다운로드
if ($request_method === 'GET' && $resource_id !== null) {
    $registration_id = (int) $resource_id;
    
    // 본인의 신청인지 확인
    $sql = "SELECT r.id, r.seminar_id, r.mb_id, r.attendance_status,
                   s.title, s.event_date, s.certificate_template_url
            FROM seminar_registration r
            INNER JOIN seminar_info s ON r.seminar_id = s.id
            WHERE r.id = {$registration_id}
            AND r.mb_id = '{$_SESSION['ss_mb_id']}'
            LIMIT 1";
    
    $registration = sql_fetch($sql);
    
    if (!$registration) {
        json_response(false, null, 'not_found', '신청 내역을 찾을 수 없습니다.', 404);
    }
    
    // 출석 완료만 다운로드 가능
    if ($registration['attendance_status'] !== 'attended') {
        json_response(false, null, 'not_attended', '출석이 완료되지 않았습니다.', 400);
    }
    
    // 수료증 생성 (TODO: 실제 PDF 생성 라이브러리 연동)
    // 현재는 간단한 HTML 렌더링 (프로덕션에서는 TCPDF 또는 FPDF 라이브러리 권장)
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="certificate_' . $registration_id . '.pdf"');
    
    // 간단한 HTML로 수료증 표시 (실제로는 PDF 변환 필요)
    // 이 부분은 추후 라이브러리 연동 시 구현
    echo json_encode([
        'success' => false,
        'error' => 'not_implemented',
        'message' => 'PDF 생성 기능은 아직 구현 중입니다. 추후 FPDF 또는 TCPDF 라이브러리로 구현 예정.'
    ]);
    
    // TODO: 실제 PDF 생성
    // 예시 코드 (TCPDF 사용 시):
    // require_once('tcpdf/tcpdf.php');
    // $pdf = new TCPDF();
    // $pdf->AddPage();
    // $pdf->WriteHTML($html);
    // $pdf->Output('certificate.pdf', 'D');
    
    exit;
}

// 지원하지 않는 메서드
json_response(false, null, 'method_not_allowed', '허용되지 않는 메서드입니다.', 405);
?>
