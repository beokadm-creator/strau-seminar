<?php
// 파트너랩 주문 상태 관리 페이지

include_once('../../common.php');
include_once('../../config.php');
include_once('./config.php');

// 관리자 권한 체크
if (!check_partner_admin_permission()) {
    alert('관리자 권한이 필요합니다.', G5_URL);
}

// 데이터베이스 연결
$db = get_partner_lab_db_connection();

// 페이지 설정
$g5['title'] = '주문 상태 관리';
include_once(G5_PATH.'/head.php');

// 상태 목록
$status_list = get_order_status_list();
?>

 

<link rel="stylesheet" href="./assets/admin.css">

<div class="partner-admin-wrap">
    <h2>주문 상태 관리</h2>
    
    <!-- 상태 설명 -->
    <div class="status-guide" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
        <h3>주문 상태 안내</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
            <?php foreach ($status_list as $key => $label): 
                $colors = array(
                    'pending' => '#ffc107',
                    'confirmed' => '#17a2b8',
                    'processing' => '#007bff',
                    'shipping' => '#ff7f50',
                    'completed' => '#28a745',
                    'cancelled' => '#dc3545'
                );
            ?>
            <div style="padding: 10px; border-left: 4px solid <?php echo $colors[$key]; ?>; background: white;">
                <strong><?php echo $label; ?></strong> (<?php echo $key; ?>)
                <br><small style="color: #666;">
                    <?php echo getStatusDescription($key); ?>
                </small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- 빠른 상태 변경 도구 -->
    <div class="quick-status-tool" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <h3>빠른 상태 변경</h3>
        <form method="post" action="update_order_status.php" style="margin-top: 15px;">
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <input type="text" name="order_id" placeholder="주문번호" required 
                       style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <select name="status" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">상태 선택</option>
                    <?php foreach ($status_list as $key => $label): ?>
                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <button type="submit" class="pl-btn pl-btn--current">상태 변경</button>
            </div>
        </form>
    </div>

    <!-- 대량 상태 변경 -->
    <div class="bulk-status-tool" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <h3>대량 상태 변경</h3>
        <p style="color: #666; margin-bottom: 15px;">쉼표(,)로 구분된 여러 주문번호를 한번에 변경할 수 있습니다.</p>
        <form method="post" action="update_order_status.php" style="margin-top: 15px;" onsubmit="return confirmBulkStatusChange();">
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <textarea name="order_ids" placeholder="주문번호들 (예: ORDER001,ORDER002,ORDER003)" required 
                         style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-width: 300px; min-height: 60px;"></textarea>
                <select name="status" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">상태 선택</option>
                    <?php foreach ($status_list as $key => $label): ?>
                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <button type="submit" class="pl-btn pl-btn--current">대량 변경</button>
            </div>
        </form>
    </div>
    
    <!-- 검색 및 필터링 -->
    <div class="search-section" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <h3>주문 검색</h3>
        <form method="get" action="" style="margin-top: 15px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">고객명</label>
                    <input type="text" name="customer_name" value="<?php echo isset($_GET['customer_name']) ? htmlspecialchars($_GET['customer_name']) : ''; ?>" 
                           placeholder="고객명 입력" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">환자명</label>
                    <input type="text" name="patient_name" value="<?php echo isset($_GET['patient_name']) ? htmlspecialchars($_GET['patient_name']) : ''; ?>" 
                           placeholder="환자명 입력" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">주문번호</label>
                    <input type="text" name="order_id" value="<?php echo isset($_GET['order_id']) ? htmlspecialchars($_GET['order_id']) : ''; ?>" 
                           placeholder="주문번호 입력" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">상태</label>
                    <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">전체 상태</option>
                        <?php foreach ($status_list as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo (isset($_GET['status']) && $_GET['status'] === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">시작일</label>
                    <input type="date" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">종료일</label>
                    <input type="date" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            <div style="margin-top: 15px; text-align: center;">
                <button type="submit" class="pl-btn pl-btn--current">검색</button>
                <a href="status_management.php" class="pl-btn">초기화</a>
            </div>
        </form>
    </div>

    <!-- 상태별 통계 -->
    <div class="status-stats" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
        <h3>상태별 주문 통계</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
            <?php 
            // 검색 조건에 따른 통계
            $stats_where = " WHERE 1=1 ";
            if (isset($_GET['customer_name']) && !empty($_GET['customer_name'])) {
                $stats_where .= " AND customer_name LIKE '%" . mysqli_real_escape_string($db, $_GET['customer_name']) . "%' ";
            }
            if (isset($_GET['patient_name']) && !empty($_GET['patient_name'])) {
                $stats_where .= " AND patient_name LIKE '%" . mysqli_real_escape_string($db, $_GET['patient_name']) . "%' ";
            }
            if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
                $stats_where .= " AND order_id LIKE '%" . mysqli_real_escape_string($db, $_GET['order_id']) . "%' ";
            }
            if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
                $stats_where .= " AND created_at >= '" . mysqli_real_escape_string($db, $_GET['start_date']) . " 00:00:00' ";
            }
            if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
                $stats_where .= " AND created_at <= '" . mysqli_real_escape_string($db, $_GET['end_date']) . " 23:59:59' ";
            }

            foreach ($status_list as $key => $label): 
                $count_sql = "SELECT COUNT(*) as cnt FROM partner_lab_orders " . $stats_where . " AND order_status = '{$key}'";
                $count_result = mysqli_query($db, $count_sql);
                $count = $count_result ? mysqli_fetch_assoc($count_result)['cnt'] : 0;
                $colors = array(
                    'pending' => '#ffc107',
                    'confirmed' => '#17a2b8',
                    'processing' => '#007bff',
                    'shipping' => '#ff7f50',
                    'completed' => '#28a745',
                    'cancelled' => '#dc3545'
                );
            ?>
            <div style="padding: 15px; border-left: 4px solid <?php echo $colors[$key]; ?>; background: #f8f9fa; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #333;"><?php echo number_format($count); ?></div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;"><?php echo $label; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- 주문 목록 -->
    <div class="order-list" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
        <h3>주문 목록</h3>
        <?php
        // 검색 조건 처리
        $where_conditions = array("1=1");
        
        if (isset($_GET['customer_name']) && !empty($_GET['customer_name'])) {
            $where_conditions[] = "customer_name LIKE '%" . mysqli_real_escape_string($db, $_GET['customer_name']) . "%'";
        }
        if (isset($_GET['patient_name']) && !empty($_GET['patient_name'])) {
            $where_conditions[] = "patient_name LIKE '%" . mysqli_real_escape_string($db, $_GET['patient_name']) . "%'";
        }
        if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
            $where_conditions[] = "order_id LIKE '%" . mysqli_real_escape_string($db, $_GET['order_id']) . "%'";
        }
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where_conditions[] = "order_status = '" . mysqli_real_escape_string($db, $_GET['status']) . "'";
        }
        if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
            $where_conditions[] = "created_at >= '" . mysqli_real_escape_string($db, $_GET['start_date']) . " 00:00:00'";
        }
        if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
            $where_conditions[] = "created_at <= '" . mysqli_real_escape_string($db, $_GET['end_date']) . " 23:59:59'";
        }
        
        $where_sql = "WHERE " . implode(" AND ", $where_conditions);
        
        // 페이지네이션
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $rows_per_page = 20;
        $offset = ($page - 1) * $rows_per_page;
        
        // 전체 개수 조회
        $count_sql = "SELECT COUNT(*) as total FROM partner_lab_orders $where_sql";
        $count_result = mysqli_query($db, $count_sql);
        $total_rows = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;
        $total_pages = ceil($total_rows / $rows_per_page);
        
        // 주문 목록 조회
        $order_sql = "SELECT * FROM partner_lab_orders $where_sql ORDER BY created_at DESC LIMIT $offset, $rows_per_page";
        $order_result = mysqli_query($db, $order_sql);
        ?>
        
        <div style="margin-bottom: 15px; color: #666;">
            전체 <?php echo number_format($total_rows); ?>건 | 페이지 <?php echo $page; ?>/<?php echo $total_pages; ?>
        </div>
        
        <?php if (mysqli_num_rows($order_result) > 0): ?>
        <table style="width: 100%; margin-top: 15px; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">주문번호</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">주문일시</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">고객명</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">환자명</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">상태</th>
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6;">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = mysqli_fetch_assoc($order_result)): 
                    $status_name = get_order_status_name($order['order_status']);
                    $colors = array(
                        'pending' => '#ffc107',
                        'confirmed' => '#17a2b8',
                        'processing' => '#007bff',
                        'shipping' => '#ff7f50',
                        'completed' => '#28a745',
                        'cancelled' => '#dc3545'
                    );
                ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo $order['order_id']; ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><?php echo htmlspecialchars($order['patient_name']); ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                        <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; background-color: <?php echo $colors[$order['order_status']]; ?>">
                            <?php echo $status_name; ?>
                        </span>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #dee2e6;">
                        <a href="order_detail.php?order_id=<?php echo $order['order_id']; ?>" class="pl-btn pl-btn-sm">상세</a>
                        <button type="button" class="pl-btn pl-btn-sm" onclick="quickStatusChange('<?php echo $order['order_id']; ?>', '<?php echo $order['order_status']; ?>')">변경</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- 페이지네이션 -->
        <?php if ($total_pages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php
            $query_params = $_GET;
            $base_url = 'status_management.php';
            
            // 이전 페이지
            if ($page > 1) {
                $query_params['page'] = $page - 1;
                echo '<a href="' . $base_url . '?' . http_build_query($query_params) . '" class="pl-btn" style="margin: 0 2px;">이전</a>';
            }
            
            // 페이지 번호
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                $query_params['page'] = $i;
                if ($i == $page) {
                    echo '<span class="pl-btn pl-btn--current" style="margin: 0 2px;">' . $i . '</span>';
                } else {
                    echo '<a href="' . $base_url . '?' . http_build_query($query_params) . '" class="pl-btn" style="margin: 0 2px;">' . $i . '</a>';
                }
            }
            
            // 다음 페이지
            if ($page < $total_pages) {
                $query_params['page'] = $page + 1;
                echo '<a href="' . $base_url . '?' . http_build_query($query_params) . '" class="pl-btn" style="margin: 0 2px;">다음</a>';
            }
            ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <p style="text-align: center; padding: 20px; color: #666;">등록된 주문이 없습니다.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// 빠른 상태 변경 함수
function quickStatusChange(orderId, currentStatus) {
    const newStatus = prompt('현재 상태: ' + currentStatus + '\n\n새로운 상태를 입력하세요:\n\n' +
        'pending: 주문\n' +
        'confirmed: 주문접수\n' +
        'processing: 제작중\n' +
        'shipping: 발송중\n' +
        'completed: 발송완료\n' +
        'cancelled: 취소됨');
    
    if (!newStatus) return;
    
    if (newStatus === currentStatus) {
        alert('현재 상태와 동일합니다.');
        return;
    }
    
    if (!confirm('주문번호 ' + orderId + '의 상태를 ' + newStatus + '로 변경하시겠습니까?')) {
        return;
    }
    
    // AJAX로 상태 변경
    fetch('update_order_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            status: newStatus,
            csrf_token: '<?php echo generate_csrf_token(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('상태가 변경되었습니다.');
            location.reload();
        } else {
            alert('오류: ' + data.message);
        }
    })
    .catch(error => {
        alert('처리 중 오류가 발생했습니다.');
    });
}

// 대량 상태 변경 확인 함수
function confirmBulkStatusChange() {
    const orderIdsTextarea = document.querySelector('textarea[name="order_ids"]');
    const statusSelect = document.querySelector('select[name="status"]');
    
    if (!orderIdsTextarea.value.trim() || !statusSelect.value) {
        alert('주문번호와 상태를 모두 입력해주세요.');
        return false;
    }
    
    const orderIds = orderIdsTextarea.value.split(',').map(id => id.trim()).filter(id => id);
    const statusName = statusSelect.options[statusSelect.selectedIndex].text;
    
    if (!confirm('총 ' + orderIds.length + '개의 주문을 ' + statusName + ' 상태로 변경하시겠습니까?')) {
        return false;
    }
    
    return true;
}
</script>

<?php
// 상태 설명 함수
function getStatusDescription($status) {
    $descriptions = array(
        'pending' => '고객이 주문을 제출한 상태',
        'confirmed' => '관리자가 주문을 확인한 상태',
        'processing' => '제작이 시작된 상태',
        'shipping' => '발송 준비 중인 상태',
        'completed' => '발송이 완료된 상태',
        'cancelled' => '주문이 취소된 상태'
    );
    return isset($descriptions[$status]) ? $descriptions[$status] : '';
}

include_once('../../tail.php');
?>
