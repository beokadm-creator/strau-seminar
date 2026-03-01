# Straumann Campus 세미나 시스템

치과 의료진을 위한 세미나 예약 및 관리 시스템 (React SPA)

## 개요

이 프로젝트는 기존 GNUBoard PHP 시스템과 통합되는 세미나 전용 React SPA입니다. 전체 사이트의 LMS 기능과 기존 게시판 시스템을 유지하면서, 세미나 기능만 현대적인 React 앱으로 제공합니다.

## 기술 스택

- **프론트엔드**: React 18, Vite 5, JavaScript
- **라우팅**: React Router v6
- **상태 관리**: Zustand
- **스타일링**: Tailwind CSS
- **HTTP 클라이언트**: Axios
- **백엔드**: 기존 PHP API (GNUBoard)

## 프로젝트 구조

```
theme/react-seminar/
├── src/
│   ├── api/           # API 클라이언트
│   ├── constants/     # 경로 및 상수
│   ├── pages/         # 페이지 컴포넌트
│   │   ├── SeminarList.jsx         # 세미나 목록
│   │   ├── SeminarDetail.jsx       # 세미나 상세
│   │   ├── Registration.jsx        # 세미나 신청
│   │   ├── MyPage.jsx              # 마이페이지
│   │   └── admin/                  # 관리자 페이지
│   │       ├── AdminDashboard.jsx  # 대시보드
│   │       ├── AdminSeminars.jsx   # 세미나 관리
│   │       ├── AdminSeminarForm.jsx # 세미나 생성/수정
│   │       └── AdminRegistrations.jsx # 참가자 관리
│   ├── App.jsx        # 라우팅 설정
│   └── main.jsx       # 엔트리 포인트
├── public/            # 정적 파일
├── vite.config.ts     # Vite 설정
├── tailwind.config.js # Tailwind 설정
└── package.json       # 의존성

/seminar/              # 빌드 결과물 배포 위치
├── index.html         # React 앱 엔트리
├── index.php          # PHP 통합 진입점
└── .htaccess          # URL 리라이팅
```

## 개발 모드 실행

```bash
# 1. 의존성 설치
cd theme/react-seminar
npm install

# 2. 개발 서버 시작
npm run dev

# 개발 서버는 http://localhost:3000/seminar/에서 실행됩니다
# 포트가 사용 중인 경우 3001, 3002 등으로 자동 변경됩니다
```

## 프로덕션 빌드

```bash
# 1. 프로덕션 빌드
npm run build

# 빌드 결과물이 ../seminar/ 디렉토리에 생성됩니다

# 2. 빌드된 파일 확인
ls ../seminar/
# index.html, assets/ 폴더가 생성되어야 합니다
```

## 페이지 구조

### 사용자 페이지

```
/seminar/              → 세미나 목록 (리다이렉트)
/seminar/seminars      → 세미나 목록
/seminar/seminars/:id  → 세미나 상세
/seminar/seminars/:id/register → 신청 페이지
/seminar/mypage        → 마이페이지
```

### 관리자 페이지

```
/seminar/admin                  → 관리자 대시보드 (리다이렉트)
/seminar/admin/dashboard        → 대시보드
/seminar/admin/seminars         → 세미나 관리 (목록)
/seminar/admin/seminars/new     → 세미나 추가
/seminar/admin/seminars/:id/edit → 세미나 수정
/seminar/admin/registrations    → 참가자 관리
```

## 주요 기능

### 사용자 기능

1. **세미나 목록**
   - 그리드 레이아웃 카드 디스플레이
   - 페이지네이션
   - 썸네일, 일시, 장소, 정원, 참가비 표시

2. **세미나 상세**
   - 포스터/썸네일 표시
   - 상세 정보 (일시, 장소, 정원, 참가비)
   - 신청 상태 표시
   - 즉시 신청 버튼

3. **세미나 신청**
   - 결제 수단 선택 (신용카드, 가상계좌)
   - 신청 유의사항 안내
   - 중복 신청 방지

4. **마이페이지**
   - 신청 내역 목록
   - 상태별 필터링
   - QR코드 생성 (출석용)
   - 신청 취소 기능

### 관리자 기능

1. **대시보드**
   - 통계 카드 (전체 세미나, 진행 중, 참가자, 수익)
   - 빠른 링크 (세미나 관리, 참가자 관리)

2. **세미나 관리**
   - 전체 목록 조회 (임시저장 포함)
   - 상태별 필터링 (임시저장, 공개, 마감)
   - 세미나 생성/수정/삭제
   - 테이블 형태 목록

3. **세미나 생성/수정**
   - 제목, 설명, 일시, 장소
   - 정원, 참가비
   - 신청 기간 (시작/종료)
   - 이미지 URL (썸네일, 포스터)
   - 상태 설정 (임시저장, 공개, 마감)

4. **참가자 관리**
   - 전체 신청 내역 조회
   - 결제 상태별 필터링
   - 세미나별 필터링
   - 상태 변경 (결제 확인, 환불)
   - 출석 체크

## API 연동

React 앱은 기존 PHP API를 호출합니다:

### 사용자 API

```
GET    /api/seminars              # 세미나 목록
GET    /api/seminars/:id          # 세미나 상세
POST   /api/registrations         # 세미나 신청
GET    /api/registrations/my      # 내 신청 내역
GET    /api/registrations/:id/qr  # QR 코드
POST   /api/registrations/:id/cancel # 신청 취소
```

### 관리자 API

```
GET    /api/admin/dashboard            # 대시보드 통계
GET    /api/admin/seminars             # 세미나 목록 (전체)
POST   /api/admin/seminars             # 세미나 생성
PUT    /api/admin/seminars/:id         # 세미나 수정
DELETE /api/admin/seminars/:id         # 세미나 삭제
GET    /api/admin/registrations        # 참가자 목록
PUT    /api/admin/registrations/:id/status  # 상태 변경
POST   /api/admin/registrations/:id/attendance  # 출석 체크
```

## 배포 프로세스

### 1. 로컬 개발

```bash
cd theme/react-seminar
npm run dev
```

### 2. 프로덕션 빌드

```bash
npm run build
```

빌드가 완료되면 `../seminar/` 폴더에 다음 파일들이 생성됩니다:

```
/seminar/
├── index.html
├── assets/
│   ├── index-XXXX.js
│   └── index-XXXX.css
├── index.php       # PHP 통합 파일
└── .htaccess       # Apache URL 리라이팅
```

### 3. 서버 배포

```bash
# 빌드된 파일을 서버로 업로드
scp -r seminar/* user@server:/path/to/straumann-campus/seminar/

# 또는 Git을 사용하는 경우
git add seminar/
git commit -m "Build seminar React app with admin features"
git push
```

### 4. 서버 설정

#### Apache 설정

`.htaccess`가 이미 포함되어 있어야 합니다:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /seminar/
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /seminar/index.html [L]
</IfModule>
```

#### Nginx 설정

```nginx
location /seminar {
    try_files $uri $uri/ /seminar/index.html;
}
```

## 환경 변수

```env
# .env
VITE_API_BASE_URL=/api
```

개발 모드에서 Vite 프록시를 통해 API 요청을 처리합니다:

```javascript
// vite.config.ts
server: {
  proxy: {
    '/api': {
      target: 'http://localhost:8080',
      changeOrigin: true,
    }
  }
}
```

## 디자인 시스템

- **색상**: Primary Blue (#2563eb), Gray 스케일
- **타이포그래피**: 시스템 기본 폰트
- **스페이싱**: Tailwind 기본 간격
- **컴포넌트**: 카드, 버튼, 입력 필드 통일

## 성능 최적화

- Vite를 통한 빠른 빌드
- 코드 스플리팅 (React Router lazy loading)
- 이미지 최적화 (thumbnail_url 사용)
- API 응답 캐싱 고려

## 브라우저 지원

- Chrome/Edge (최신 2버전)
- Firefox (최신 2버전)
- Safari (최신 2버전)
- 모바일 브라우저 지원

## 트러블슈팅

### 개발 서버가 시작되지 않음

```bash
# 포트 충돌 확인
netstat -ano | findstr :3000

# 다른 포트 사용
npm run dev -- --port 3002
```

### API 호출 실패

```bash
# Vite 프록시 설정 확인 (vite.config.ts)
# PHP API 서버가 실행 중인지 확인
# CORS 설정 확인
```

### 빌드 후 404 오류

```bash
# .htaccess 파일 확인
# Apache mod_rewrite 활성화 확인
# Nginx try_files 설정 확인
```

## 향후 개선 사항

- [ ] SEO 최적화 (meta tags, Open Graph)
- [ ] PWA 지원 (Service Worker)
- [ ] 다국어 지원 (i18n)
- [ ] 알림 기능 (Push, Email)
- [ ] 결제 모듈 직접 연동
- [ ] 실시간 알림 (WebSocket)
- [ ] 이미지 업로드 기능
- [ ] 설문조사 관리
- [ ] 수료증 발급
- [ ] 통계 리포트 다운로드

## 라이선스

Copyright © 2024 Institut Straumann AG. All rights reserved.

## 연락처

개발팀: dev@straumann.com
