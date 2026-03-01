<?php
/**
 * Validation Helpers
 * 
 * 입력 데이터 검증 관련 헬퍼 함수
 */

/**
 * 필수 파라미터 체크
 * 
 * @param array $data 검증할 데이터
 * @param array $required 필수 필드 목록
 * @return void 누락된 필드가 있으면 400 응답
 */
function validate_required($data, $required) {
    $missing = [];
    
    foreach ($required as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        json_response(false, ['missing_fields' => $missing], 'validation_error', '필수 필드가 누락되었습니다: ' . implode(', ', $missing), 400);
    }
}

/**
 * 이메일 형식 검증
 * 
 * @param string $email 검증할 이메일
 * @return bool 유효성 여부
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 날짜 형식 검증 (Y-m-d H:i:s)
 * 
 * @param string $date 검증할 날짜 문자열
 * @return bool 유효성 여부
 */
function validate_datetime($date) {
    $format = 'Y-m-d H:i:s';
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * 정수 검증
 * 
 * @param mixed $value 검증할 값
 * @param int $min 최소값 (optional)
 * @param int $max 최대값 (optional)
 * @return bool 유효성 여부
 */
function validate_integer($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    
    $intValue = (int) $value;
    
    if ($min !== null && $intValue < $min) {
        return false;
    }
    
    if ($max !== null && $intValue > $max) {
        return false;
    }
    
    return true;
}

/**
 * ENUM 값 검증
 * 
 * @param string $value 검증할 값
 * @param array $allowed 허용된 값 목록
 * @return bool 유효성 여부
 */
function validate_enum($value, $allowed) {
    return in_array($value, $allowed, true);
}

/**
 * XSS 방지를 위한 입력 sanitize
 * 
 * @param string $input sanitize할 문자열
 * @return string sanitize된 문자열
 */
function sanitize_input($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * 배열의 모든 값을 sanitize
 * 
 * @param array $data sanitize할 배열
 * @return array sanitize된 배열
 */
function sanitize_data($data) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitize_data($value);
        } else if (is_string($value)) {
            $sanitized[$key] = sanitize_input($value);
        } else {
            $sanitized[$key] = $value;
        }
    }
    
    return $sanitized;
}

/**
 * SQL Injection 방지를 위한 escape
 * 
 * @param string $value escape할 값
 * @return string escape된 값
 */
function escape_sql($value) {
    global $g5;
    
    if (function_exists('sql_real_escape_string')) {
        return sql_real_escape_string($value);
    }
    
    return mysqli_real_escape_string($g5['connect_db'], $value);
}
?>
