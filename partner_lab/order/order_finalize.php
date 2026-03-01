<?php
// 파트너랩 주문 최종 완료 처리
// order_review.php에서 최종 확인 후 주문 완료 처리

// G5 공통 파일 포함
include_once('../../common.php');
include_once('../../config.php');
include_once('./config.php');

// 로그인 체크
if (!$is_member) {
    die(json_encode(['success' => false, 'message' => '로그인이 필요합니다.']));
}

// CSRF 토큰 검증
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die(json_encode(['success' => false, 'message' => '잘못된 요청입니다.']));
}

// 주문 데이터 확인
if (!isset($_SESSION['partner_order_temp'])) {
    die(json_encode(['success' => false, 'message' => '주문 정보가 없습니다.']));
}

$order_data = $_SESSION['partner_order_temp'];

try {
    // 데이터베이스 연결
    $pdo = get_partner_lab_db_connection();
    
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 주문 번호 생성 (G5 규칙에 맞게)
    $order_id = date('YmdHis') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // 주문 기본 정보 저장
    $sql = "INSERT INTO partner_lab_orders (
                order_id, mb_id, customer_name, customer_phone, customer_email,
                patient_name, patient_age, patient_gender, work_type, material,
                shade, additional_info, delivery_method, delivery_address,
                delivery_date, order_status, created_at, updated_at
            ) VALUES (
                :order_id, :mb_id, :customer_name, :customer_phone, :customer_email,
                :patient_name, :patient_age, :patient_gender, :work_type, :material,
                :shade, :additional_info, :delivery_method, :delivery_address,
                :delivery_date, 'pending', NOW(), NOW()
            )";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':order_id' => $order_id,
        ':mb_id' => $member['mb_id'],
        ':customer_name' => $order_data['customer_name'],
        ':customer_phone' => $order_data['customer_phone'],
        ':customer_email' => $order_data['customer_email'],
        ':patient_name' => $order_data['patient_name'],
        ':patient_age' => $order_data['patient_age'],
        ':patient_gender' => $order_data['patient_gender'],
        ':work_type' => $order_data['work_type'],
        ':material' => $order_data['material'],
        ':shade' => $order_data['shade'],
        ':additional_info' => $order_data['additional_info'],
        ':delivery_method' => $order_data['delivery_method'],
        ':delivery_address' => $order_data['delivery_address'],
        ':delivery_date' => $order_data['delivery_date']
    ]);
    
    // 선택된 치아 정보 저장
    if (!empty($order_data['selected_teeth'])) {
        // 싱글/브릿지 등 상세 옵션은 orders.teeth_configurations(JSON)에 저장되어 있을 수 있음
        $teeth_sql = "INSERT INTO partner_lab_order_teeth (order_id, tooth_number, created_at) VALUES (:order_id, :tooth_number, NOW())";
        $teeth_stmt = $pdo->prepare($teeth_sql);
        foreach ($order_data['selected_teeth'] as $tooth_number) {
            $teeth_stmt->execute([
                ':order_id' => $order_id,
                ':tooth_number' => $tooth_number
            ]);
        }
        // teeth_details 테이블이 있을 경우 위치/모드 정보까지 저장 시도
        try {
            $detail_sql = "INSERT INTO partner_lab_order_teeth_details (order_id, tooth_number, tooth_position, is_selected, created_at) VALUES (:order_id, :tooth_number, :tooth_position, 1, NOW())";
            $detail_stmt = $pdo->prepare($detail_sql);
            foreach ($order_data['selected_teeth'] as $tooth_number) {
                $pos = '';
                if ($tooth_number >= 11 && $tooth_number <= 18) $pos = 'upper_right';
                else if ($tooth_number >= 21 && $tooth_number <= 28) $pos = 'upper_left';
                else if ($tooth_number >= 31 && $tooth_number <= 38) $pos = 'lower_left';
                else if ($tooth_number >= 41 && $tooth_number <= 48) $pos = 'lower_right';
                $detail_stmt->execute([
                    ':order_id' => $order_id,
                    ':tooth_number' => $tooth_number,
                    ':tooth_position' => $pos
                ]);
            }
        } catch (Exception $e) {
            // 테이블이 없거나 제약 조건에 걸릴 수 있으므로 무시
        }
    }
    
    // 업로드된 파일 정보 저장
    if (!empty($order_data['files'])) {
        $file_sql = "INSERT INTO partner_lab_order_files (order_id, file_name, file_path, file_size, file_type, created_at) 
                     VALUES (:order_id, :file_name, :file_path, :file_size, :file_type, NOW())";
        $file_stmt = $pdo->prepare($file_sql);
        
        foreach ($order_data['files'] as $file) {
            $file_stmt->execute([
                ':order_id' => $order_id,
                ':file_name' => $file['name'],
                ':file_path' => $file['path'],
                ':file_size' => $file['size'],
                ':file_type' => $file['type']
            ]);
        }
    }
    
    // 주문 로그 기록
    $log_sql = "INSERT INTO partner_lab_order_logs (order_id, log_type, log_message, created_by, created_at) 
                VALUES (:order_id, 'order_created', '주문이 생성되었습니다.', :created_by, NOW())";
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([
        ':order_id' => $order_id,
        ':created_by' => $member['mb_id']
    ]);
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    // 세션에서 임시 주문 데이터 제거
    unset($_SESSION['partner_order_temp']);
    
    // 성공 응답
    $response = [
        'success' => true,
        'order_id' => $order_id,
        'message' => '주문이 성공적으로 완료되었습니다.',
        'redirect' => 'order_complete.php?order_id=' . $order_id
    ];
    
    // JSON 응답
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
    
} catch (Exception $e) {
    // 트랜잭션 롤백
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // 에러 처리 - 로그 기록 제거
    
    // 에러 응답
    $response = [
        'success' => false,
        'message' => '주문 처리 중 오류가 발생했습니다. ' . $e->getMessage()
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response);
}

?>