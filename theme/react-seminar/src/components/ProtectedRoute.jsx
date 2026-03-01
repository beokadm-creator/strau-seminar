import { Navigate } from 'react-router-dom'
import useAuthStore from '../store/authStore'

function ProtectedRoute({ children, requireAdmin = false, requireStaff = false }) {
  const { isAuthenticated, isAdmin, isStaff, isLoading } = useAuthStore()

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    )
  }

  if (!isAuthenticated) {
    return <Navigate to="/seminar/" replace />
  }

  if (requireAdmin && !isAdmin) {
    return <Navigate to="/seminar/" replace />
  }

  if (requireStaff && !isStaff && !isAdmin) {
    return <Navigate to="/seminar/" replace />
  }

  return children
}

export default ProtectedRoute
