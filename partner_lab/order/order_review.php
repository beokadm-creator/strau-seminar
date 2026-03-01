<?php
// 파트너랩 주문 최종 확인 페이지
// partner_lab/order/index.php에서 주문완료 버튼 클릭 후 최종 확인용

// G5 공통 파일 포함
// GNUBoard 환경에서만 공용 파일 포함
if (defined('G5_PATH') && file_exists(G5_PATH.'/common.php')) {
    include_once(G5_PATH.'/common.php');
}
if (defined('G5_PATH') && file_exists(G5_PATH.'/config.php')) {
    include_once(G5_PATH.'/config.php');
}
if (file_exists(__DIR__.'/config.php')) {
    include_once(__DIR__.'/config.php');
}

// 로그인 체크
if (empty($is_member)) {
    $login_base = defined('G5_BBS_URL') ? G5_BBS_URL.'/login.php' : '/bbs/login.php';
    if (function_exists('alert')) {
        alert('로그인이 필요합니다.', $login_base);
    } else {
        header('Location: '.$login_base);
        exit;
    }
}

// 주문 정보가 전달되지 않은 경우
if (!isset($_POST['order_data']) && !isset($_SESSION['partner_order_temp'])) {
    $order_index = '/partner_lab/order/index.php';
    if (function_exists('alert')) {
        alert('주문 정보가 없습니다.', $order_index);
    } else {
        header('Location: '.$order_index);
        exit;
    }
}

// 주문 데이터 처리
$order_data = [];
if (isset($_POST['order_data'])) {
    $order_data = json_decode(stripslashes($_POST['order_data']), true);
    $_SESSION['partner_order_temp'] = $order_data;
} else {
    $order_data = $_SESSION['partner_order_temp'];
}

// 기본 주문 데이터 구조 설정
$default_order_data = [
    'customer_name' => '',
    'customer_phone' => '',
    'customer_email' => '',
    'patient_name' => '',
    'patient_age' => '',
    'patient_gender' => '',
    'selected_teeth' => [],
    'work_type' => '',
    'material' => '',
    'shade' => '',
    'additional_info' => '',
    'delivery_method' => '',
    'delivery_address' => '',
    'delivery_date' => '',
    'files' => [],
    'agreement1' => false,
    'agreement2' => false,
    // 금액/결제 제거: 총 금액 사용 안 함
];

// 기본값과 실제 데이터 병합
$order_data = array_merge($default_order_data, $order_data);

// CSRF 토큰 생성
$csrf_token = generate_csrf_token();

// 페이지 제목
$g5['title'] = '주문 최종 확인 - 파트너랩';

if (defined('G5_PATH') && file_exists(G5_PATH.'/head.php')) {
    include_once(G5_PATH.'/head.php');
}
?>

<style>
.order-review-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.review-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 20px;
}

.review-section h3 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: bold;
    color: #555;
    min-width: 120px;
}

.info-value {
    color: #333;
    text-align: right;
}

.teeth-selection {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
    gap: 5px;
    margin: 15px 0;
}

.tooth-number {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 4px;
    padding: 8px;
    text-align: center;
    font-weight: bold;
    color: #1976d2;
}

.file-list {
    margin: 15px 0;
}

.file-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.agreement-section {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.agreement-item {
    margin-bottom: 15px;
    padding: 10px;
    background: #fff;
    border-radius: 4px;
}

.action-buttons {
    text-align: center;
    margin-top: 30px;
}

.btn-final-order {
    background: #28a745;
    border: none;
    color: white;
    padding: 15px 40px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    margin: 0 10px;
}

.btn-final-order:hover {
    background: #218838;
}

.btn-modify {
    background: #6c757d;
    border: none;
    color: white;
    padding: 15px 30px;
    font-size: 16px;
    border-radius: 6px;
    cursor: pointer;
    margin: 0 10px;
}

.btn-modify:hover {
    background: #5a6268;
}



.warning-message {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
    color: #856404;
}

@media (max-width: 768px) {
    .order-review-container {
        padding: 10px;
    }
    
    .review-section {
        padding: 15px;
    }
    
    .info-row {
        flex-direction: column;
    }
    
    .info-value {
        text-align: left;
        margin-top: 5px;
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .btn-final-order,
    .btn-modify {
        width: 100%;
        margin: 0;
    }
}
</style>

<div class="order-review-container">
    <h2 style="text-align: center; margin-bottom: 30px; color: #333;">주문 최종 확인</h2>
    
    <div class="warning-message">
        <strong>⚠️ 주의사항</strong><br>
        아래 주문 내용을 최종 확인해 주세요. 주문 완료 후 수정이 불가능합니다.
    </div>

    <form id="finalOrderForm" method="post" action="order_finalize.php">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <!-- 고객 정보 -->
        <div class="review-section">
            <h3>고객 정보</h3>
            <div class="info-row">
                <span class="info-label">이름:</span>
                <span class="info-value"><?php echo htmlspecialchars($order_data['customer_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">연락처:</span>
                <span class="info-value"><?php echo htmlspecialchars($order_data['customer_phone']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">이메일:</span>
                <span class="info-value"><?php echo htmlspecialchars($order_data['customer_email']); ?></span>
            </div>
        </div>

        <!-- 환자 정보 -->
        <div class="review-section">
            <h3>환자 정보</h3>
            <div class="info-row">
                <span class="info-label">환자명:</span>
                <span class="info-value"><?php echo htmlspecialchars($order_data['patient_name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">나이:</span>
                <span class="info-value"><?php echo htmlspecialchars($order_data['patient_age']); ?>세</span>
            </div>
            <div class="info-row">
                <span class="info-label">성별:</span>
                <span class="info-value"><?php echo $order_data['patient_gender'] === 'male' ? '남성' : ($order_data['patient_gender'] === 'female' ? '여성' : '미지정'); ?></span>
            </div>
        </div>

        <!-- 작업 정보 -->
        <div class="review-section">
            <h3>작업 정보</h3>
            <div class="info-row">
                <span class="info-label">작업 종류:</span>
                <span class="info-value"><?php echo htmlspecialchars($order_data['work_type']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">재료:</span>
                <span class="info-value"><?php echo htmlspecialchars($order_data['material']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">색상:</span>
                <span class="info-value"><?php echo htmlspecialchars($order_data['shade']); ?></span>
            </div>
            <?php if ($order_data['additional_info']): ?>
            <div class="info-row">
                <span class="info-label">추가 요청사항:</span>
                <span class="info-value"><?php echo nl2br(htmlspecialchars($order_data['additional_info'])); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- 치아 선택 -->
        <?php if (!empty($order_data['selected_teeth'])): ?>
        <div class="review-section">
            <h3>선택된 치아</h3>
            <div class="teeth-selection">
                <?php foreach ($order_data['selected_teeth'] as $tooth): ?>
                <div class="tooth-number"><?php echo $tooth; ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 업로드 파일 -->
        <?php if (!empty($order_data['files'])): ?>
        <div class="review-section">
            <h3>업로드 파일</h3>
            <div class="file-list">
                <?php foreach ($order_data['files'] as $file): ?>
                <div class="file-item">
                    <span><?php echo htmlspecialchars($file['name']); ?></span>
                    <span class="text-muted">(<?php echo number_format($file['size'] / 1024, 1); ?> KB)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 배송 정보 -->
        <div class="review-section">
            <h3>배송 정보</h3>
            <div class="info-row">
                <span class="info-label">배송 방법:</span>
                <span class="info-value"><?php echo $order_data['delivery_method'] === 'pickup' ? '직접수령' : '택배배송'; ?></span>
            </div>
            <?php if ($order_data['delivery_method'] === 'delivery'): ?>
            <div class="info-row">
                <span class="info-label">배송 주소:</span>
                <span class="info-value"><?php echo nl2br(htmlspecialchars($order_data['delivery_address'])); ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">희망 납기일:</span>
                <span class="info-value"><?php echo htmlspecialchars($order_data['delivery_date']); ?></span>
            </div>
        </div>

        <!-- 금액/결제 표시 제거 -->

        <!-- 동의사항 -->
        <div class="agreement-section">
            <h4>동의사항</h4>
            <div class="agreement-item">
                <strong>✓ 개인정보 수집 및 이용 동의</strong><br>
                <small>주문 처리를 위한 개인정보 수집 및 이용에 동의합니다.</small>
            </div>
            <div class="agreement-item">
                <strong>✓ 주문 내용 확인 및 취소/환불 규정 동의</strong><br>
                <small>주문 내용을 확인하였으며, 취소 및 환불 규정에 동의합니다.</small>
            </div>
        </div>

        <!-- 액션 버튼 -->
        <div class="action-buttons">
            <button type="button" class="btn-modify" onclick="modifyOrder()">수정하기</button>
            <button type="submit" class="btn-final-order" onclick="return confirmFinalOrder()">주문완료</button>
        </div>
    </form>
</div>

<script>
function modifyOrder() {
    if (confirm('주문 내용을 수정하시겠습니까?')) {
        window.location.href = '/partner_lab/order/index.php?modify=1';
    }
}

function confirmFinalOrder() {
    return confirm('주문을 완료하시겠습니까?\n\n주문 완료 후에는 수정이 불가능합니다.');
}

// 폼 제출 전 마지막 확인
document.getElementById('finalOrderForm').addEventListener('submit', function(e) {
    if (!confirmFinalOrder()) {
        e.preventDefault();
        return false;
    }
    
    // 주문 완료 버튼 비활성화 (중복 제출 방지)
    const submitBtn = e.target.querySelector('.btn-final-order');
    submitBtn.disabled = true;
    submitBtn.textContent = '주문 처리중...';
});
</script>

<?php include_once('../../tail.php'); ?>