# GNUBoard 5 프론트엔드 현대화 빌드 요구사항

## 1. 프로젝트 개요

GNUBoard 5의 레거시 jQuery/PHP 기반 프론트엔드를 현대적인 Vite 기반 빌드 시스템으로 마이그레이션합니다. 기존 기능을 유지하면서 개발 생산성과 성능을 향상시킵니다.

## 2. 마이그레이션 전략

### 2.1 단계별 접근 방식

#### Phase 1: 기본 인프라 구축 (1-2주)
- Vite 프로젝트 설정
- 기본 빌드 도구 구성
- PHP 템플릿과의 통합

#### Phase 2: 핵심 기능 마이그레이션 (2-3주)
- 공통 JavaScript 모듈화
- CSS 모듈 시스템 도입
- 주요 페이지 변환

#### Phase 3: 고급 기능 구현 (3-4주)
- 반응형 이미지 최적화
- 코드 스플리팅
- 성능 최적화

## 3. 기술 스택

### 3.1 빌드 도구
- **Vite 5.x**: 빠른 개발 서버 및 빌드
- **Rollup**: 프로덕션 번들링
- **ESBuild**: 빠른 트랜스파일링

### 3.2 프론트엔드 기술
- **Vanilla JavaScript**: jQuery 의존성 점진적 제거
- **CSS Modules**: 스코프가 지정된 스타일링
- **PostCSS**: 자동 벤더 프리픽스 및 최적화
- **TypeScript**: 점진적 도입 (선택사항)

### 3.3 개발 도구
- **ESLint**: 코드 품질 관리
- **Prettier**: 코드 포맷팅
- **Stylelint**: CSS 린팅
- **Husky**: Git 훅 관리

## 4. 디렉토리 구조

```
/build/                    # 빌드 출력 디렉토리
/src/
  ├── assets/              # 정적 자산
  │   ├── images/
  │   ├── fonts/
  │   └── icons/
  ├── styles/              # 글로벌 스타일
  │   ├── base/
  │   ├── components/
  │   ├── layouts/
  │   └── utilities/
  ├── scripts/             # JavaScript 모듈
  │   ├── modules/
  │   ├── components/
  │   ├── utils/
  │   └── vendors/
  └── entries/             # 진입점 파일
      ├── main.js
      ├── admin.js
      └── shop.js
/public/                    # 정적 파일
  ├── dist/               # 빌드된 파일
  └── assets/             # 원본 자산
```

## 5. 빌드 구성

### 5.1 Vite 설정 (vite.config.js)
```javascript
export default {
  build: {
    rollupOptions: {
      input: {
        main: './src/entries/main.js',
        admin: './src/entries/admin.js',
        shop: './src/entries/shop.js'
      },
      output: {
        dir: '../dist',
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]'
      }
    },
    manifest: true,
    sourcemap: true
  },
  css: {
    modules: {
      localsConvention: 'camelCase'
    }
  }
}
```

### 5.2 PHP 통합
```php
// theme/basic/head.php
function vite_assets($entry) {
    $manifest = json_decode(file_get_contents('../dist/manifest.json'), true);
    $assets = $manifest["src/entries/{$entry}.js"];
    
    // CSS 파일
    if (isset($assets['css'])) {
        foreach ($assets['css'] as $css) {
            echo '<link rel="stylesheet" href="' . G5_URL . '/dist/' . $css . '">';
        }
    }
    
    // JS 파일
    echo '<script type="module" src="' . G5_URL . '/dist/' . $assets['file'] . '"></script>';
}
```

## 6. 마이그레이션 체크리스트

### 6.1 JavaScript 마이그레이션
- [ ] jQuery 의존성 분석
- [ ] 전역 함수 모듈화
- [ ] 이벤트 핸들러 현대화
- [ ] AJAX 호출 fetch API로 전환
- [ ] 유틸리티 함수 정리

### 6.2 CSS 마이그레이션
- [ ] CSS 파일 모듈화
- [ ] 미디어 쿼리 최적화
- [ ] 벤더 프리픽스 자동화
- [ ] 다크 모드 지원 검토
- [ ] 접근성 개선

### 6.3 성능 최적화
- [ ] 코드 스플리팅 구현
- [ ] 이미지 최적화 (WebP, lazy loading)
- [ ] 폰트 로딩 최적화
- [ ] 번들 크기 분석 및 최적화
- [ ] 캐싱 전략 수립

## 7. 호환성 요구사항

### 7.1 브라우저 지원
- **최신 브라우저**: Chrome, Firefox, Safari, Edge (최신 2개 버전)
- **레거시 지원**: IE11 (폴리필 필요)
- **모바일**: iOS Safari, Android Chrome

### 7.2 PHP 통합
- 기존 PHP 템플릿 구조 유지
- 데이터 바인딩 방식 유지
- 세션 및 인증 시스템 통합

## 8. 개발 워크플로우

### 8.1 개발 환경
```bash
# 개발 서버 실행
npm run dev

# 프로덕션 빌드
npm run build

# 정적 분석
npm run lint

# 테스트 실행
npm run test
```

### 8.2 배포 프로세스
1. 코드 품질 검사
2. 테스트 실행
3. 프로덕션 빌드
4. 정적 파일 배포
5. PHP 템플릿 업데이트

## 9. 성능 목표

### 9.1 로딩 성능
- First Contentful Paint: < 1.5초
- Largest Contentful Paint: < 2.5초
- Time to Interactive: < 3.0초

### 9.2 번들 크기
- 초기 번들: < 100KB (gzipped)
- 전체 번들: < 500KB (코드 스플리팅 후)

## 10. 보안 요구사항

### 10.1 콘텐츠 보안 정책
```javascript
// CSP 헤더 설정
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'
```

### 10.2 XSS 보호
- 입력값 검증
- 출력 이스케이핑
- DOM 기반 XSS 방지

## 11. 모니터링 및 유지보수

### 11.1 성능 모니터링
- Lighthouse CI 통합
- Web Vitals 측정
- 에러 추적 시스템

### 11.2 코드 품질
- 정적 분석 자동화
- 코드 커버리지 측정
- 의존성 취약점 스캔