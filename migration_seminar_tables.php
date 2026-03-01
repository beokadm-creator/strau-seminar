<?php
// Mock server variables for CLI execution
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SCRIPT_NAME'] = 'migration_seminar_tables.php';
$_SERVER['HTTP_HOST'] = 'localhost';

define('G5_DISPLAY_SQL_ERROR', true);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

include_once('./_common.php');

echo "=== 스트라우만 세미나 시스템 DB 마이그레이션 ===\n\n";

// 1. Create seminar_info table
$table_name = "seminar_info";
if (!sql_fetch(" SHOW TABLES LIKE '{$table_name}' ")) {
    $sql = "CREATE TABLE `{$table_name}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL COMMENT '세미나 제목',
        `description` TEXT COMMENT '세미나 상세 설명',
        `event_date` DATETIME NOT NULL COMMENT '세미나 일시',
        `location` VARCHAR(255) NOT NULL COMMENT '장소',
        `capacity` INT(11) NOT NULL DEFAULT 0 COMMENT '정원',
        `price` INT(11) NOT NULL DEFAULT 0 COMMENT '가격(원)',
        `thumbnail_url` VARCHAR(512) COMMENT '썸네일 이미지 URL',
        `poster_url` VARCHAR(512) COMMENT '포스터 이미지 URL',
        `certificate_template_url` VARCHAR(512) COMMENT '수료증 템플릿 이미지 URL',
        `registration_start` DATETIME NOT NULL COMMENT '신청 시작일시',
        `registration_end` DATETIME NOT NULL COMMENT '신청 종료일시',
        `status` ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft' COMMENT '상태',
        `created_at` DATETIME NOT NULL COMMENT '생성일시',
        `updated_at` DATETIME NOT NULL COMMENT '수정일시',
        PRIMARY KEY (`id`),
        KEY `status` (`status`),
        KEY `event_date` (`event_date`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='세미나 정보'";
    sql_query($sql);
    echo "[OK] Created table: {$table_name}\n";
} else {
    echo "[SKIP] Table already exists: {$table_name}\n";
}

// 2. Create seminar_registration table
$table_name = "seminar_registration";
if (!sql_fetch(" SHOW TABLES LIKE '{$table_name}' ")) {
    $sql = "CREATE TABLE `{$table_name}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `seminar_id` INT(11) NOT NULL COMMENT '세미나 ID',
        `mb_id` VARCHAR(20) NOT NULL COMMENT '회원 ID',
        `payment_status` ENUM('pending', 'paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending' COMMENT '결제상태',
        `attendance_status` ENUM('pending', 'attended') NOT NULL DEFAULT 'pending' COMMENT '출석상태',
        `payment_method` VARCHAR(50) COMMENT '결제수단',
        `payment_amount` INT(11) NOT NULL DEFAULT 0 COMMENT '결제금액',
        `paid_at` DATETIME COMMENT '결제일시',
        `cancelled_at` DATETIME COMMENT '취소일시',
        `refunded_at` DATETIME COMMENT '환불일시',
        `qr_code_token` VARCHAR(255) COMMENT 'QR코드 토큰',
        `created_at` DATETIME NOT NULL COMMENT '신청일시',
        PRIMARY KEY (`id`),
        KEY `seminar_id` (`seminar_id`),
        KEY `mb_id` (`mb_id`),
        KEY `qr_code_token` (`qr_code_token`),
        KEY `payment_status` (`payment_status`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='세미나 신청 내역'";
    sql_query($sql);
    echo "[OK] Created table: {$table_name}\n";
} else {
    echo "[SKIP] Table already exists: {$table_name}\n";
}

// 3. Create seminar_survey table
$table_name = "seminar_survey";
if (!sql_fetch(" SHOW TABLES LIKE '{$table_name}' ")) {
    $sql = "CREATE TABLE `{$table_name}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `registration_id` INT(11) NOT NULL COMMENT '신청 ID',
        `content_satisfaction` INT(11) NOT NULL COMMENT '내용 만족도 (1-5)',
        `instructor_satisfaction` INT(11) NOT NULL COMMENT '강사 만족도 (1-5)',
        `facility_satisfaction` INT(11) NOT NULL COMMENT '시설 만족도 (1-5)',
        `overall_satisfaction` INT(11) NOT NULL COMMENT '전체 만족도 (1-5)',
        `suggestions` TEXT COMMENT '건의사항',
        `created_at` DATETIME NOT NULL COMMENT '응답일시',
        PRIMARY KEY (`id`),
        KEY `registration_id` (`registration_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='세미나 설문조사 응답'";
    sql_query($sql);
    echo "[OK] Created table: {$table_name}\n";
} else {
    echo "[SKIP] Table already exists: {$table_name}\n";
}

// 4. Check if seminar_staff auth needs to be added to g5_auth
// First, let's check if there's an auth entry for seminar management
echo "\n[CHECK] Checking seminar staff permissions...\n";

// For now, we'll skip auth table modification as it requires manual review
// The staff permission check will be handled in the API layer

echo "\n=== Migration completed ===\n";
echo "Next step: Create API endpoints\n";
?>
