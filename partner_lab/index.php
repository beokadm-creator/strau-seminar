<?php
include_once './_common.php';

$g5['title'] = '파트너 랩 주문';
include_once G5_PATH.'/head.php';

// 파트너랩 콘텐츠 조회
$content_sql = "SELECT * FROM {$g5['partner_lab_content_table']} WHERE content_type = 'intro' AND is_active = 1 ORDER BY created_at DESC LIMIT 1";
$content_result = sql_query($content_sql);
$content = sql_fetch_array($content_result);

// 파트너랩 배너 조회
$banner_sql = "SELECT * FROM {$g5['partner_lab_banner_table']} WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 1";
$banner_result = sql_query($banner_sql);
$banner = sql_fetch_array($banner_result);
?>

<div class="partner-lab-container">
    <!-- 소개 섹션 -->
    <div class="intro-section">
        <?php if ($content && $content['content']) { ?>
            <div class="intro-content">
                <?php echo $content['content']; ?>
            </div>
        <?php } else { ?>
            <div class="intro-content">
                <h2>스트라우만 코리아 주문 시스템</h2>
                <p>스트라우만 코리아의 파트너 랩을 통해 고품질의 치과 보철물을 주문하실 수 있습니다.</p>
                <p>전문적인 기술력과 최신 장비를 통해 정확하고 신뢰할 수 있는 제품을 제공합니다.</p>
            </div>
        <?php } ?>
    </div>

    <!-- 배너 섹션 -->
    <?php if ($banner && $banner['image_path']) { ?>
    <div class="banner-section">
        <?php if ($banner['link_url']) { ?>
            <a href="<?php echo $banner['link_url']; ?>" 
               <?php echo $banner['is_new_window'] ? 'target="_blank"' : ''; ?>>
                <img src="<?php echo $banner['image_path']; ?>" 
                     alt="<?php echo htmlspecialchars($banner['alt_text']); ?>" 
                     class="banner-image">
            </a>
        <?php } else { ?>
            <img src="<?php echo $banner['image_path']; ?>" 
                 alt="<?php echo htmlspecialchars($banner['alt_text']); ?>" 
                 class="banner-image">
        <?php } ?>
    </div>
    <?php } ?>

    <!-- 주문 버튼 섹션 -->
    <div class="order-section">
        <div class="order-button-container">
            <a href="./order/index.php" class="order-button">
                <span class="button-text">주문하기</span>
                <span class="button-icon">→</span>
            </a>
        </div>
        
        <div class="order-info">
            <h3>주문 프로세스</h3>
            <div class="process-steps">
                <div class="step">
                    <span class="step-number">1</span>
                    <span class="step-title">주문정보</span>
                </div>
                <div class="step">
                    <span class="step-number">2</span>
                    <span class="step-title">환자정보</span>
                </div>
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-title">작업모형</span>
                </div>
                <div class="step">
                    <span class="step-number">4</span>
                    <span class="step-title">주문상품</span>
                </div>
                <div class="step">
                    <span class="step-number">5</span>
                    <span class="step-title">치식 및 임플란트</span>
                </div>
                <div class="step">
                    <span class="step-number">6</span>
                    <span class="step-title">기타사항</span>
                </div>
                <div class="step">
                    <span class="step-number">7</span>
                    <span class="step-title">의뢰서 확인</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.partner-lab-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.intro-section {
    background: #f8f9fa;
    padding: 40px;
    border-radius: 10px;
    margin-bottom: 30px;
    text-align: center;
}

.intro-content h2 {
    color: #2c3e50;
    font-size: 2.5em;
    margin-bottom: 20px;
    font-weight: 700;
}

.intro-content p {
    color: #555;
    font-size: 1.1em;
    line-height: 1.6;
    margin-bottom: 15px;
}

.banner-section {
    margin-bottom: 40px;
    text-align: center;
}

.banner-image {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.order-section {
    text-align: center;
}

.order-button-container {
    margin-bottom: 50px;
}

.order-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 40px;
    border-radius: 50px;
    text-decoration: none;
    font-size: 1.3em;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.order-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

.button-text {
    margin-right: 10px;
}

.button-icon {
    font-size: 1.2em;
    transition: transform 0.3s ease;
}

.order-button:hover .button-icon {
    transform: translateX(5px);
}

.order-info h3 {
    color: #2c3e50;
    font-size: 1.8em;
    margin-bottom: 30px;
    font-weight: 600;
}

.process-steps {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 20px;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    min-width: 120px;
    transition: transform 0.3s ease;
}

.step:hover {
    transform: translateY(-5px);
}

.step-number {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 10px;
}

.step-title {
    color: #2c3e50;
    font-weight: 500;
    text-align: center;
}

@media (max-width: 768px) {
    .partner-lab-container {
        padding: 10px;
    }
    
    .intro-section {
        padding: 20px;
    }
    
    .intro-content h2 {
        font-size: 2em;
    }
    
    .process-steps {
        flex-direction: column;
        align-items: center;
    }
    
    .step {
        width: 100%;
        max-width: 300px;
    }
}
</style>

<?php
include_once G5_PATH.'/tail.php';
?>
