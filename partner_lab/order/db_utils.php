<?php
// 파트너랩 주문 시스템 데이터베이스 유틸리티

// 데이터베이스 연결 함수 (이미 db_config.php에 정의되어 있음)
// getDBConnection() 함수를 사용

// 데이터베이스 연결 래퍼 함수 (process.php에서 사용)
function dbu_get_connection() {
    return getDBConnection();
}

// 테이블 존재 확인 함수
function dbu_table_exists($conn, $table_name) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        $table_name = $conn->real_escape_string($table_name);
        $result = $conn->query("SHOW TABLES LIKE '{$table_name}'");
        return $result && $result->num_rows > 0;
    }
    return false;
}

// 테이블 컬럼 정보 가져오기
function dbu_get_columns($conn, $table_name) {
    $columns = array();
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        $table_name = $conn->real_escape_string($table_name);
        $result = $conn->query("SHOW COLUMNS FROM `{$table_name}`");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $columns[$row['Field']] = $row;
            }
        }
    }
    return $columns;
}

// SQL 실행 함수 (prepare/execute 래퍼)
function dbu_prepare_execute($conn, $sql, $params = array()) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        if (!empty($params)) {
            // 간단한 prepared statement 구현
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                // 매개변수 바인딩 (간단한 버전)
                $types = '';
                $values = array();
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                    $values[] = $param;
                }
                
                if (!empty($values)) {
                    array_unshift($values, $types);
                    call_user_func_array(array($stmt, 'bind_param'), $values);
                }
                
                return $stmt->execute();
            }
        } else {
            return $conn->query($sql);
        }
    }
    return false;
}

// 단일 행 조회 함수
function dbu_query_one_row($conn, $sql, $params = array()) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $types = '';
                $values = array();
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                    $values[] = $param;
                }
                
                if (!empty($values)) {
                    array_unshift($values, $types);
                    call_user_func_array(array($stmt, 'bind_param'), $values);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result) {
                    return $result->fetch_assoc();
                }
            }
        } else {
            $result = $conn->query($sql);
            if ($result) {
                return $result->fetch_assoc();
            }
        }
    }
    return null;
}

// 존재 여부 확인 함수 (SELECT 결과가 1개 이상인지 확인)
function dbu_row_exists($conn, $sql, $params = array()) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $types = '';
                $values = array();
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                    $values[] = $param;
                }
                if (!empty($values)) {
                    array_unshift($values, $types);
                    call_user_func_array(array($stmt, 'bind_param'), $values);
                }
                $ok = $stmt->execute();
                if ($ok) {
                    $exists = false;
                    if (method_exists($stmt, 'get_result')) {
                        $result = @$stmt->get_result();
                        if ($result) { $exists = ($result->num_rows > 0); }
                    } else {
                        if (method_exists($stmt, 'store_result')) { @$stmt->store_result(); $exists = ($stmt->num_rows > 0); }
                    }
                    $stmt->close();
                    return $exists;
                }
                $stmt->close();
            }
        } else {
            $result = $conn->query($sql);
            if ($result && ($result instanceof mysqli_result)) {
                return $result->num_rows > 0;
            }
        }
    }
    return false;
}

// 단일 스칼라 값 조회 함수 (첫 행 첫 컬럼 반환)
function dbu_query_one_scalar($conn, $sql, $params = array()) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $types = '';
                $values = array();
                foreach ($params as $param) {
                    if (is_int($param)) { $types .= 'i'; }
                    elseif (is_float($param)) { $types .= 'd'; }
                    else { $types .= 's'; }
                    $values[] = $param;
                }
                if (!empty($values)) {
                    array_unshift($values, $types);
                    call_user_func_array(array($stmt, 'bind_param'), $values);
                }
                $ok = $stmt->execute();
                if ($ok) {
                    if (method_exists($stmt, 'get_result')) {
                        $result = @$stmt->get_result();
                        if ($result) {
                            $row = $result->fetch_row();
                            $stmt->close();
                            return $row ? $row[0] : null;
                        }
                    } else {
                        // mysqlnd 미사용 환경 폴백: bind_result 후 fetch로 첫 컬럼 값 획득
                        $val = null;
                        // 시도: 하나의 컬럼만 바인딩
                        if (method_exists($stmt, 'bind_result')) {
                            @$stmt->bind_result($val);
                            if (@$stmt->fetch()) {
                                $stmt->close();
                                return $val;
                            }
                        }
                        if (method_exists($stmt, 'store_result')) { @$stmt->store_result(); }
                    }
                }
                $stmt->close();
            }
        } else {
            $result = $conn->query($sql);
            if ($result) {
                $row = $result->fetch_row();
                return $row ? $row[0] : null;
            }
        }
    }
    return null;
}

// INSERT SQL 생성 (존재하는 컬럼만 포함, 안전한 파라미터 바인딩)
function dbu_build_insert_sql($conn, $table, $data, $skip_columns = array()) {
    // 테이블 컬럼 메타 조회
    $columns = dbu_get_columns($conn, $table);
    $cols = array();
    $placeholders = array();
    $params = array();
    foreach ($data as $key => $val) {
        // 명시적으로 스킵할 컬럼
        if (is_array($skip_columns) && in_array($key, $skip_columns)) { continue; }
        // 테이블에 존재하는 컬럼만 포함
        if (isset($columns[$key])) {
            $cols[] = '`' . $key . '`';
            $placeholders[] = '?';
            // 배열/객체는 문자열로 직렬화 시도
            if (is_array($val) || is_object($val)) {
                if (function_exists('json_encode')) { $val = @json_encode($val); }
                else { $val = (string)$val; }
            }
            $params[] = $val;
        }
    }
    // 컬럼이 하나도 없으면 안전하게 빈 INSERT 방지
    if (empty($cols)) {
        // 최후 폴백: 데이터가 있으나 컬럼 메타 조회 실패 시 첫 키만 삽입 시도
        foreach ($data as $key => $val) {
            $cols[] = '`' . dbu_escape_string($conn, $key) . '`';
            $placeholders[] = '?';
            if (is_array($val) || is_object($val)) {
                if (function_exists('json_encode')) { $val = @json_encode($val); }
                else { $val = (string)$val; }
            }
            $params[] = $val;
            break;
        }
    }
    $sql = 'INSERT INTO `' . $table . '` (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
    return array($sql, $params);
}

// 마지막 삽입 ID 가져오기
function dbu_last_insert_id($conn) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        return $conn->insert_id;
    }
    return 0;
}

// 문자열 이스케이프 함수
function dbu_escape_string($conn, $string) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        return $conn->real_escape_string($string);
    }
    return addslashes($string);
}

// 주문 테이블의 기본키 컬럼명 가져오기
function dbu_get_order_pk($conn) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        $result = $conn->query("SHOW KEYS FROM partner_lab_orders WHERE Key_name = 'PRIMARY'");
        if ($result && $row = $result->fetch_assoc()) {
            return $row['Column_name'];
        }
    }
    return 'id'; // 기본값
}

// 파일 테이블의 날짜 컬럼명 가져오기
function dbu_get_files_date_col($conn) {
    $columns = dbu_get_columns($conn, 'partner_lab_order_files');
    if (isset($columns['uploaded_at'])) return 'uploaded_at';
    if (isset($columns['created_at'])) return 'created_at';
    return null;
}

// 트랜잭션 시작
function dbu_begin($conn) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        // PHP 5.2 호환: begin_transaction 미지원
        // 1) autocommit(false)로 트랜잭션 시작
        $ok1 = true;
        if (method_exists($conn, 'autocommit')) {
            $ok1 = @$conn->autocommit(false);
        }
        // 2) 엔진에 따라 명시적 START TRANSACTION 필요할 수 있음
        $ok2 = @$conn->query('START TRANSACTION');
        // 둘 중 하나라도 성공하면 트랜잭션 시작으로 간주
        return ($ok1 !== false) || ($ok2 !== false);
    }
    return false;
}

// 트랜잭션 커밋
function dbu_commit($conn) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        $ok = @$conn->commit();
        // autocommit 원복
        if (method_exists($conn, 'autocommit')) { @ $conn->autocommit(true); }
        return $ok;
    }
    return false;
}

// 트랜잭션 롤백
function dbu_rollback($conn) {
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        $ok = @$conn->rollback();
        // autocommit 원복
        if (method_exists($conn, 'autocommit')) { @ $conn->autocommit(true); }
        return $ok;
    }
    return false;
}
?>