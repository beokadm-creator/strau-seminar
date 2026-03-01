import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true, // PHP 세션 쿠키를 위한 설정
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    return response.data
  },
  (error) => {
    if (error.response) {
      // 서버 응답이 있는 에러
      const { status, data } = error.response
      
      // 401 Unauthorized: 로그인 페이지로 이동
      if (status === 401) {
        window.location.href = '/bbs/login.php?url=' + encodeURIComponent(window.location.pathname)
        return Promise.reject(error)
      }
      
      // 403 Forbidden
      if (status === 403) {
        console.error('권한이 없습니다:', data?.message || 'Forbidden')
      }
      
      // 404 Not Found
      if (status === 404) {
        console.error('리소스를 찾을 수 없습니다:', data?.message || 'Not Found')
      }
      
      return Promise.reject(data)
    } else if (error.request) {
      // 요청은 보냈지만 응답이 없는 에러
      console.error('네트워크 에러가 발생했습니다.')
      return Promise.reject({ message: '네트워크 에러가 발생했습니다.' })
    } else {
      // 요청 설정 중 에러
      console.error('요청 설정 중 에러가 발생했습니다:', error.message)
      return Promise.reject(error)
    }
  }
)

export default api
