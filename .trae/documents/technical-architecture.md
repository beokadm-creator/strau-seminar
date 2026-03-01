# 스트라우만 세미나 통합 관리 시스템 - 기술 아키텍처 문서

## 1. 시스템 아키텍처 개요

### 1.1 아키텍처 다이어그램
```
┌─────────────────────────────────────────────────────────────────┐
│                         사용자 (회원, 스태프)                       │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                     React Frontend (SPA)                        │
│  ┌───────────────┐  ┌──────────────┐  ┌──────────────────┐    │
│  │ 세미나 목록/    │  │  마이페이지   │  │   QR 스캐너      │    │
│  │   상세         │  │              │  │   (스태프용)      │    │
│  └───────────────┘  └──────────────┘  └──────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼ HTTPS/JSON
┌─────────────────────────────────────────────────────────────────┐
│                    RESTful API Layer (PHP)                      │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  /api/seminars/*        - 세미나 관련 API                  │  │
│  │  /api/registrations/*   - 신청 관련 API                    │  │
│  │  /api/attendance/*      - 출석 관련 API                    │  │
│  │  /api/certificates/*    - 수료증 관련 API                  │  │
│  │  /api/surveys/*         - 설문조사 관련 API                 │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Business Logic Layer (PHP)                   │
│  - 인증/권한 체크                                                 │
│  - 결제 로직                                                    │
│  - QR코드 생성/검증                                             │
│  - 수료증 생성 (PDF)                                            │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Database Layer (MySQL)                       │
│  ┌──────────────┐  ┌──────────────────┐  ┌─────────────────┐  │
│  │ g5_member    │  │ seminar_info     │  │ seminar_        │  │
│  │ (기존)       │  │ seminar_         │  │ registration    │  │
│  │              │  │ registration     │  │                 │  │
│  └──────────────┘  └──────────────────┘  └─────────────────┘  │
│                                                                     │
│  ┌──────────────────┐  ┌──────────────────┐                       │
│  │ seminar_survey   │  │ g5_campus_       │                       │
│  │                  │  │ access_log       │                       │
│  └──────────────────┘  └──────────────────┘                       │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2 기술 스택

#### Frontend
- **Framework**: React 18+
- **Build Tool**: Vite
- **Routing**: React Router DOM
- **State Management**: Zustand (기존 프로젝트와 동일)
- **Styling**: Tailwind CSS (기존 프로젝트와 동일)
- **HTTP Client**: Axios 또는 fetch API
- **QR Code**: react-qr-code 또는 qrcode.react
- **QR Scanner**: react-qr-reader (스태프용)

#### Backend
- **Language**: PHP 8.x
- **Database**: MySQL 5.7+
- **Architecture**: RESTful API
- **Authentication**: PHP Session 기반, JWT 고려

#### DevOps
- **Deployment**: FTP (기존 방식) 또는 Git 배포
- **Environment**: Preview → Live (2단계)

## 2. 데이터베이스 설계

### 2.1 ERD
```
g5_member (기존)
    │
    ├─ 1:N ── seminar_registration
                   │
                   ├─ N:1 ── seminar_info
                   │
                   ├─ 1:1 ── seminar_survey (출석 완료 후 생성)
```

### 2.2 테이블 상세

#### seminar_info (세미나 정보)
| 컬럼명 | 타입 | NULL | 설명 |
|--------|------|------|------|
| id | INT | NO | PK, AUTO_INCREMENT |
| title | VARCHAR(255) | NO | 세미나 제목 |
| description | TEXT | YES | 세미나 상세 설명 |
| event_date | DATETIME | NO | 세미나 일시 |
| location | VARCHAR(255) | NO | 장소 |
| capacity | INT | NO | 정원 |
| price | INT | NO | 가격 (원) |
| thumbnail_url | VARCHAR(512) | YES | 썸네일 이미지 URL |
| poster_url | VARCHAR(512) | YES | 포스터 이미지 URL |
| certificate_template_url | VARCHAR(512) | YES | 수료증 템플릿 이미지 URL |
| registration_start | DATETIME | NO | 신청 시작일시 |
| registration_end | DATETIME | NO | 신청 종료일시 |
| status | ENUM | NO | 상태 (draft/published/closed) |
| created_at | DATETIME | NO | 생성일시 |
| updated_at | DATETIME | NO | 수정일시 |

#### seminar_registration (신청 내역)
| 컬럼명 | 타입 | NULL | 설명 |
|--------|------|------|------|
| id | INT | NO | PK, AUTO_INCREMENT |
| seminar_id | INT | NO | FK → seminar_info.id |
| mb_id | VARCHAR(20) | NO | FK → g5_member.mb_id |
| payment_status | ENUM | NO | 결제상태 (pending/paid/cancelled/refunded) |
| attendance_status | ENUM | NO | 출석상태 (pending/attended) |
| payment_method | VARCHAR(50) | YES | 결제수단 (card/vbank 등) |
| payment_amount | INT | NO | 결제금액 |
| paid_at | DATETIME | YES | 결제일시 |
| cancelled_at | DATETIME | YES | 취소일시 |
| refunded_at | DATETIME | YES | 환불일시 |
| qr_code_token | VARCHAR(255) | YES | QR코드 토큰 (출석용) |
| created_at | DATETIME | NO | 신청일시 |

#### seminar_survey (설문조사 응답)
| 컬럼명 | 타입 | NULL | 설명 |
|--------|------|------|------|
| id | INT | NO | PK, AUTO_INCREMENT |
| registration_id | INT | NO | FK → seminar_registration.id |
| content_satisfaction | INT | NO | 내용 만족도 (1-5) |
| instructor_satisfaction | INT | NO | 강사 만족도 (1-5) |
| facility_satisfaction | INT | NO | 시설 만족도 (1-5) |
| overall_satisfaction | INT | NO | 전체 만족도 (1-5) |
| suggestions | TEXT | YES | 건의사항 |
| created_at | DATETIME | NO | 응답일시 |

## 3. API 설계

### 3.1 공통 사항
- **Base URL**: `/api`
- **Response Format**: JSON
- **인증**: PHP Session 기반 (쿠키)
- **에러 처리**: HTTP Status Code + 메시지

### 3.2 API 명세

#### 세미나 (Seminars)

**목록 조회**
```
GET /api/seminars
Query Parameters:
  - page: int (default: 1)
  - limit: int (default: 10)
  - status: enum (draft/published/closed)
Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "임플란트 기초 세미나",
      "event_date": "2024-03-15 14:00:00",
      "location": "서울 강남구",
      "capacity": 50,
      "current_registrations": 32,
      "price": 100000,
      "thumbnail_url": "/images/seminar/1/thumb.jpg",
      "status": "published"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 25,
    "total_pages": 3
  }
}
```

**상세 조회**
```
GET /api/seminars/{id}
Response:
{
  "success": true,
  "data": {
    "id": 1,
    "title": "임플란트 기초 세미나",
    "description": "...",
    "event_date": "2024-03-15 14:00:00",
    "location": "서울 강남구",
    "capacity": 50,
    "current_registrations": 32,
    "price": 100000,
    "thumbnail_url": "/images/seminar/1/thumb.jpg",
    "poster_url": "/images/seminar/1/poster.jpg",
    "registration_start": "2024-02-01 00:00:00",
    "registration_end": "2024-03-10 23:59:59",
    "status": "published",
    "is_registered": false  // 현재 회원의 신청 여부
  }
}
```

#### 신청 (Registrations)

**신청 생성**
```
POST /api/registrations
Request Body:
{
  "seminar_id": 1,
  "payment_method": "card"
}
Response:
{
  "success": true,
  "data": {
    "id": 123,
    "seminar_id": 1,
    "payment_status": "pending",
    "payment_url": "https://pg.com/payment/..."  // PG사 결제 페이지
  }
}
```

**내 신청 내역**
```
GET /api/registrations/my
Query Parameters:
  - status: enum (pending/paid/cancelled/refunded) optional
Response:
{
  "success": true,
  "data": [
    {
      "id": 123,
      "seminar": {
        "id": 1,
        "title": "임플란트 기초 세미나",
        "event_date": "2024-03-15 14:00:00",
        "location": "서울 강남구"
      },
      "payment_status": "paid",
      "attendance_status": "pending",
      "qr_code_token": "abc123...",
      "created_at": "2024-02-15 10:30:00"
    }
  ]
}
```

**QR코드 조회**
```
GET /api/registrations/{id}/qr
Response:
{
  "success": true,
  "data": {
    "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?data=...",
    "token": "abc123...",
    "seminar_title": "임플란트 기초 세미나",
    "event_date": "2024-03-15 14:00:00"
  }
}
```

**취소/환불**
```
POST /api/registrations/{id}/cancel
Response:
{
  "success": true,
  "data": {
    "id": 123,
    "payment_status": "cancelled"
  }
}
```

#### 출석 (Attendance) - 스태프용

**QR 스캔 (출석 체크)**
```
POST /api/attendance/scan
Request Body:
{
  "qr_token": "abc123..."
}
Response (Success):
{
  "success": true,
  "data": {
    "registration": {
      "id": 123,
      "member": {
        "mb_id": "user01",
        "mb_name": "홍길동"
      },
      "seminar": {
        "id": 1,
        "title": "임플란트 기초 세미나"
      },
      "attendance_status": "attended"
    }
  }
}
Response (Already Attended):
{
  "success": false,
  "error": "already_attended",
  "message": "이미 출석 체크된 참여자입니다."
}
Response (Invalid Token):
{
  "success": false,
  "error": "invalid_token",
  "message": "유효하지 않은 QR코드입니다."
}
```

#### 수료증 (Certificates)

**수료증 PDF 다운로드**
```
GET /api/certificates/{registration_id}
Response: PDF File (Content-Type: application/pdf)
```

#### 설문조사 (Surveys)

**설문조사 제출**
```
POST /api/surveys
Request Body:
{
  "registration_id": 123,
  "content_satisfaction": 5,
  "instructor_satisfaction": 4,
  "facility_satisfaction": 5,
  "overall_satisfaction": 5,
  "suggestions": "좋은 세미나였습니다."
}
Response:
{
  "success": true,
  "data": {
    "id": 456
  }
}
```

**내 설문조사 조회**
```
GET /api/surveys/my/{registration_id}
Response:
{
  "success": true,
  "data": {
    "id": 456,
    "registration_id": 123,
    "content_satisfaction": 5,
    "instructor_satisfaction": 4,
    "facility_satisfaction": 5,
    "overall_satisfaction": 5,
    "suggestions": "좋은 세미나였습니다.",
    "created_at": "2024-03-16 10:00:00"
  }
}
```

## 4. 인증 및 권한

### 4.1 인증 방식
- **기본**: PHP Session (기존 gnuBoard 방식)
- **진입점**: `/api` 접근 전 세션 확인
- **구현**: PHP `$_SESSION` 변수로 로그인 상태 확인

### 4.2 권한 레벨

| 역할 | 레벨 | 접근 가능 기능 |
|------|------|----------------|
| 비회원 | - | 세미나 목록/상세 조회만 가능 |
| 회원 | 2~10 | 세미나 신청, 마이페이지, QR코드 생성 |
| 스태프 | Staff | QR 스캔, 출석 관리 |
| 관리자 | Super | 전체 기능 |

### 4.3 스태프 권한 구현
- **기존**: `g5_auth` 테이블 활용
- **신규**: `au_auth` 컬럼에 'seminar_staff' 권한 추가
- **체크 로직**:
```php
$is_staff = false;
if ($is_admin == 'super') {
    $is_staff = true;
} else {
    $sql = " SELECT COUNT(*) AS cnt FROM g5_auth WHERE mb_id = '{$member['mb_id']}' AND au_auth LIKE '%seminar_staff%' ";
    $row = sql_fetch($sql);
    if ($row['cnt'] > 0) $is_staff = true;
}
```

## 5. 프론트엔드 아키텍처

### 5.1 디렉토리 구조
```
theme/react-seminar/
├── public/
│   └── index.html
├── src/
│   ├── api/               # API 클라이언트
│   │   ├── axios.js
│   │   └── endpoints.js
│   ├── components/        # 공통 컴포넌트
│   │   ├── Button.jsx
│   │   ├── Card.jsx
│   │   ├── Modal.jsx
│   │   └── ...
│   ├── pages/            # 페이지 컴포넌트
│   │   ├── SeminarList.jsx
│   │   ├── SeminarDetail.jsx
│   │   ├── Registration.jsx
│   │   ├── MyPage.jsx
│   │   └── StaffScanner.jsx
│   ├── hooks/            # 커스텀 훅
│   │   ├── useAuth.js
│   │   ├── useSeminar.js
│   │   └── useRegistration.js
│   ├── store/            # 상태 관리 (Zustand)
│   │   ├── authStore.js
│   │   └── seminarStore.js
│   ├── utils/            # 유틸리티
│   │   └── formatters.js
│   ├── App.jsx
│   └── main.jsx
├── package.json
├── vite.config.js
└── tailwind.config.js
```

### 5.2 상태 관리 (Zustand)
```javascript
// authStore.js
export const useAuthStore = create((set) => ({
  user: null,
  isAuthenticated: false,
  login: (userData) => set({ user: userData, isAuthenticated: true }),
  logout: () => set({ user: null, isAuthenticated: false })
}));

// seminarStore.js
export const useSeminarStore = create((set) => ({
  seminars: [],
  currentSeminar: null,
  setSeminars: (seminars) => set({ seminars }),
  setCurrentSeminar: (seminar) => set({ currentSeminar: seminar })
}));
```

### 5.3 라우팅 (React Router)
```javascript
// App.jsx
<Routes>
  <Route path="/" element={<SeminarList />} />
  <Route path="/seminars/:id" element={<SeminarDetail />} />
  <Route path="/seminars/:id/register" element={<Registration />} />
  <Route path="/mypage" element={<MyPage />} />
  <Route path="/staff/scanner" element={<StaffScanner />} />
</Routes>
```

## 6. 배포 전략

### 6.1 Preview 환경 구축
- **목적**: 기능 검증 및 테스트
- **URL**: `https://stkr-edu.com/theme/react-seminar/`
- **빌드**: `npm run build`
- **배포**: `theme/react-seminar/dist/` 폴더를 FTP로 업로드

### 6.2 Live 환경 배포
- **조건**: Preview 환경에서 충분한 테스트 완료 후
- **URL**: `https://stkr-edu.com/seminars/` (공식 URL)
- **절차**:
  1. API 백엔드를 Live DB에 연결
  2. React 빌드 후 Live 경로로 배포
  3. DNS 또는 라우팅 설정

## 7. 보안 고려사항

### 7.1 API 보안
- 모든 API 요청에 CSRF 토큰 확인
- SQL Injection 방지 (prepared statement 사용)
- XSS 방지 (입력값 sanitize)

### 7.2 결제 보안
- PG사 결제 모듈 사용 (직접 카드 정보 저장 X)
- 결제 완료 후 서버 검증 (webhook)

### 7.3 QR코드 보안
- 토큰 기반 UUID 생성
- 일회성 또는 만료 기간 설정
- HTTPS 통신만 허용

## 8. 개발 일정

| 단계 | 주요 작업 | 예상 기간 |
|------|----------|----------|
| Phase 1 | DB 스키마 생성, API 개발 | 2주 |
| Phase 2 | React 프론트엔드 개발 | 2주 |
| Phase 3 | 스태프 기능 개발 | 1주 |
| Phase 4 | 수료증, 설문조사 | 1주 |
| Phase 5 | 테스트 및 수정 | 1주 |
| Phase 6 | Preview 배포 및 검증 | 1주 |
| Phase 7 | Live 배포 | - |
