import api from './axios'

export const seminarAPI = {
  // 목록 조회
  getList: (params = {}) => {
    return api.get('/seminars', { params })
  },
  
  // 상세 조회
  getDetail: (id) => {
    return api.get(`/seminars/${id}`)
  },
  
  // 관리자: 전체 목록 (임시저장 포함)
  adminGetList: (params = {}) => {
    return api.get('/admin/seminars', { params })
  },
  
  // 관리자: 세미나 생성
  adminCreate: (data) => {
    return api.post('/admin/seminars', data)
  },
  
  // 관리자: 세미나 수정
  adminUpdate: (id, data) => {
    return api.put(`/admin/seminars/${id}`, data)
  },
  
  // 관리자: 세미나 삭제
  adminDelete: (id) => {
    return api.delete(`/admin/seminars/${id}`)
  },
}

export const registrationAPI = {
  // 신청 생성
  create: (data) => {
    return api.post('/registrations', data)
  },
  
  // 내 신청 내역
  getMyRegistrations: (params = {}) => {
    return api.get('/registrations/my', { params })
  },
  
  // QR코드 조회
  getQRCode: (id) => {
    return api.get(`/registrations/${id}/qr`)
  },
  
  // 취소/환불
  cancel: (id) => {
    return api.post(`/registrations/${id}/cancel`)
  },
  
  // 관리자: 전체 신청 내역
  adminGetList: (params = {}) => {
    return api.get('/admin/registrations', { params })
  },
  
  // 관리자: 신청 상태 변경
  adminUpdateStatus: (id, status) => {
    return api.put(`/admin/registrations/${id}/status`, { status })
  },
  
  // 관리자: 출석 체크
  adminCheckAttendance: (id) => {
    return api.post(`/admin/registrations/${id}/attendance`)
  },
}

export const attendanceAPI = {
  // QR 스캔 출석 체크
  scan: (qrToken) => {
    return api.post('/attendance/scan', { qr_token: qrToken })
  },
}

export const certificateAPI = {
  // 수료증 PDF 다운로드
  download: (id) => {
    return api.get(`/certificates/${id}`, { responseType: 'blob' })
  },
}

export const surveyAPI = {
  // 설문조사 제출
  submit: (data) => {
    return api.post('/surveys', data)
  },
  
  // 내 설문조사 조회
  getMySurvey: (registrationId) => {
    return api.get(`/surveys/my/${registrationId}`)
  },
}

export const adminAPI = {
  // 대시보드 통계
  getDashboard: () => {
    return api.get('/admin/dashboard')
  },
  
  // 세미나별 통계
  getSeminarStats: (seminarId) => {
    return api.get(`/admin/seminars/${seminarId}/stats`)
  },
}
