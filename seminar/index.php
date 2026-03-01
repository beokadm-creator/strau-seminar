<?php
if (!defined('_GNUBOARD_')) exit;

// 세미나 React 앱 진입점
// 이 페이지는 React SPA(Single Page Application)를 로드합니다.

// 세미나 관련 리소스 경로
$seminar_dist_url = G5_URL . '/seminar';
?>

<!-- 세미나 React 앱 컨테이너 -->
<div id="root"></div>

<!-- React 앱 로드 -->
<?php if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/seminar/index.html')): ?>
    <?php
    // 개발 모드: Vite dev server에서 직접 로드
    // 배포 후에는 빌드된 파일을 사용
    ?>
    <script type="module" crossorigin src="<?php echo $seminar_dist_url; ?>/assets/index.js"></script>
    <link rel="stylesheet" href="<?php echo $seminar_dist_url; ?>/assets/index.css">
<?php else: ?>
    <!-- 개발 서버가 실행 중이지 않습니다 -->
    <div class="text-center py-20">
        <p class="text-xl text-gray-600 mb-4">세미나 시스템을 로드할 수 없습니다.</p>
        <p class="text-sm text-gray-500">
            개발 모드: <code>cd theme/react-seminar && npm run dev</code> 실행 필요<br>
            또는 <code>cd theme/react-seminar && npm run build</code>로 빌드하세요.
        </p>
    </div>
<?php endif; ?>

<style>
/* 세미나 앱 전용 �타일 */
#root {
    min-height: 100vh;
}
</style>
