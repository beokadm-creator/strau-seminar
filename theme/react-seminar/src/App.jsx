import { useEffect } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import SeminarList from './pages/SeminarList'
import SeminarDetail from './pages/SeminarDetail'
import Registration from './pages/Registration'
import MyPage from './pages/MyPage'
import AdminDashboard from './pages/admin/AdminDashboard'
import AdminSeminars from './pages/admin/AdminSeminars'
import AdminSeminarForm from './pages/admin/AdminSeminarForm'
import AdminRegistrations from './pages/admin/AdminRegistrations'
import ProtectedRoute from './components/ProtectedRoute'
import useAuthStore from './store/authStore'
import { BASE_PATH } from './constants/paths'

function App() {
  const fetchAuth = useAuthStore((state) => state.fetchAuth)

  useEffect(() => {
    fetchAuth()
  }, [fetchAuth])

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <Routes>
          {/* 사용자 페이지 */}
          <Route path="/" element={<Navigate to={`${BASE_PATH}/seminars`} replace />} />
          <Route path={`${BASE_PATH}/`} element={<Navigate to={`${BASE_PATH}/seminars`} replace />} />
          <Route path={`${BASE_PATH}/seminars`} element={<SeminarList />} />
          <Route path={`${BASE_PATH}/seminars/:id`} element={<SeminarDetail />} />
          <Route path={`${BASE_PATH}/seminars/:id/register`} element={<Registration />} />
          <Route path={`${BASE_PATH}/mypage`} element={
            <ProtectedRoute>
              <MyPage />
            </ProtectedRoute>
          } />
          
          {/* 관리자 페이지 - 스태프 권한 필요 */}
          <Route path={`${BASE_PATH}/admin`} element={<Navigate to={`${BASE_PATH}/admin/dashboard`} replace />} />
          <Route path={`${BASE_PATH}/admin/dashboard`} element={
            <ProtectedRoute requireStaff>
              <AdminDashboard />
            </ProtectedRoute>
          } />
          <Route path={`${BASE_PATH}/admin/seminars`} element={
            <ProtectedRoute requireStaff>
              <AdminSeminars />
            </ProtectedRoute>
          } />
          <Route path={`${BASE_PATH}/admin/seminars/new`} element={
            <ProtectedRoute requireStaff>
              <AdminSeminarForm />
            </ProtectedRoute>
          } />
          <Route path={`${BASE_PATH}/admin/seminars/:id/edit`} element={
            <ProtectedRoute requireStaff>
              <AdminSeminarForm />
            </ProtectedRoute>
          } />
          <Route path={`${BASE_PATH}/admin/registrations`} element={
            <ProtectedRoute requireStaff>
              <AdminRegistrations />
            </ProtectedRoute>
          } />
        </Routes>
      </div>
    </div>
  )
}

export default App
