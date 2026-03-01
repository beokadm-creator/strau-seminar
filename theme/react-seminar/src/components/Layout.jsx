import { Link } from 'react-router-dom'
import useAuthStore from '../store/authStore'

export default function Layout({ children }) {
  const { user, isAuthenticated, isAdmin, isStaff, logout } = useAuthStore()

  const handleLogout = async () => {
    if (confirm('로그아웃 하시겠습니까?')) {
      await logout()
    }
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-white shadow-sm sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-4">
            <a href="/" className="flex items-center space-x-2">
              <svg className="w-8 h-8 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
              </svg>
              <span className="text-2xl font-bold text-primary-600">Straumann Campus</span>
            </a>
            
            <nav className="hidden md:flex items-center space-x-8">
              <a href="/" className="text-gray-700 hover:text-primary-600 font-medium transition-colors">
                홈
              </a>
              <a href="/seminar/" className="text-gray-700 hover:text-primary-600 font-medium transition-colors">
                세미나
              </a>
              {isAuthenticated && (
                <a href="/seminar/mypage" className="text-gray-700 hover:text-primary-600 font-medium transition-colors">
                  마이페이지
                </a>
              )}
              {(isAdmin || isStaff) && (
                <a href="/seminar/admin" className="text-gray-700 hover:text-primary-600 font-medium transition-colors">
                  관리자
                </a>
              )}
            </nav>

            <div className="flex items-center space-x-4">
              {isAuthenticated && user ? (
                <div className="flex items-center space-x-4">
                  <div className="text-right">
                    <p className="text-sm font-medium text-gray-900">{user.name}</p>
                    {(isAdmin || isStaff) && (
                      <span className="text-xs text-primary-600">관리자</span>
                    )}
                  </div>
                  <button
                    onClick={handleLogout}
                    className="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                  >
                    로그아웃
                  </button>
                </div>
              ) : (
                <>
                  <a href="/bbs/login.php" className="px-4 py-2 text-sm font-medium text-gray-700 hover:text-primary-600 transition-colors">
                    로그인
                  </a>
                  <a href="/bbs/register.php" className="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors">
                    회원가입
                  </a>
                </>
              )}
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </main>

      <footer className="bg-white border-t mt-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Straumann Campus</h3>
              <p className="text-gray-600 text-sm">
                치과 의료진을 위한 전문 교육 플랫폼
              </p>
            </div>
            
            <div>
              <h3 className="text-lg font-semibold text-gray-900 mb-4">빠른 링크</h3>
              <ul className="space-y-2 text-sm">
                <li><Link to="/seminars" className="text-gray-600 hover:text-primary-600">세미나</Link></li>
                <li><a href="/bbs/board.php?bo_table=notice" className="text-gray-600 hover:text-primary-600">공지사항</a></li>
                <li><a href="/bbs/board.php?bo_table=free" className="text-gray-600 hover:text-primary-600">자유게시판</a></li>
              </ul>
            </div>
            
            <div>
              <h3 className="text-lg font-semibold text-gray-900 mb-4">고객지원</h3>
              <ul className="space-y-2 text-sm">
                <li><a href="/bbs/faq.php" className="text-gray-600 hover:text-primary-600">자주묻는질문</a></li>
                <li><a href="/bbs/qalist.php" className="text-gray-600 hover:text-primary-600">1:1 문의</a></li>
                <li><a href="/bbs/content.php?co_id=company" className="text-gray-600 hover:text-primary-600">회사소개</a></li>
              </ul>
            </div>
          </div>
          
          <div className="border-t mt-8 pt-8 text-center">
            <p className="text-gray-600 text-sm">
              &copy; 2024 Institut Straumann AG. All rights reserved.
            </p>
          </div>
        </div>
      </footer>
    </div>
  )
}
