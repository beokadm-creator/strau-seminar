import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { seminarAPI } from '../../api/endpoints'
import { BASE_PATH } from '../../constants/paths'

function AdminSeminars() {
  const [seminars, setSeminars] = useState([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [pagination, setPagination] = useState(null)
  const [statusFilter, setStatusFilter] = useState('')

  useEffect(() => {
    fetchSeminars()
  }, [page, statusFilter])

  const fetchSeminars = async () => {
    try {
      setLoading(true)
      const params = { page, limit: 20 }
      if (statusFilter) params.status = statusFilter
      
      const response = await seminarAPI.adminGetList(params)
      
      if (response.success) {
        setSeminars(response.data.seminars)
        setPagination(response.data.pagination)
      }
    } catch (error) {
      console.error('세미나 목록 조회 실패:', error)
      alert('세미나 목록을 불러오는데 실패했습니다.')
    } finally {
      setLoading(false)
    }
  }

  const handleDelete = async (id) => {
    if (!window.confirm('정말로 삭제하시겠습니까?')) return

    try {
      const response = await seminarAPI.adminDelete(id)
      if (response.success) {
        alert('삭제되었습니다.')
        fetchSeminars()
      }
    } catch (error) {
      console.error('삭제 실패:', error)
      alert(error.message || '삭제 중 오류가 발생했습니다.')
    }
  }

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('ko-KR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  }

  const formatPrice = (price) => {
    return price === 0 ? '무료' : `${price.toLocaleString()}원`
  }

  const getStatusBadge = (status) => {
    const badges = {
      draft: 'bg-gray-100 text-gray-800',
      published: 'bg-green-100 text-green-800',
      closed: 'bg-red-100 text-red-800',
    }
    
    const labels = {
      draft: '임시저장',
      published: '공개',
      closed: '마감',
    }
    
    return (
      <span className={`px-3 py-1 rounded-full text-sm font-medium ${badges[status] || badges.draft}`}>
        {labels[status] || status}
      </span>
    )
  }

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    )
  }

  return (
    <div>
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-3xl font-bold text-gray-900">세미나 관리</h1>
        <Link
          to={`${BASE_PATH}/admin/seminars/new`}
          className="px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition-colors"
        >
          세미나 추가
        </Link>
      </div>

      {/* 필터 */}
      <div className="bg-white rounded-lg shadow-md p-4 mb-6">
        <div className="flex items-center gap-4">
          <label className="text-gray-700 font-medium">상태:</label>
          <select
            value={statusFilter}
            onChange={(e) => {
              setStatusFilter(e.target.value)
              setPage(1)
            }}
            className="border border-gray-300 rounded-lg px-4 py-2"
          >
            <option value="">전체</option>
            <option value="draft">임시저장</option>
            <option value="published">공개</option>
            <option value="closed">마감</option>
          </select>
        </div>
      </div>

      {seminars.length === 0 ? (
        <div className="bg-white rounded-lg shadow-md p-6 text-center py-12">
          <p className="text-gray-600">등록된 세미나가 없습니다.</p>
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  세미나
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  일시
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  신청/정원
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  참가비
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  상태
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  관리
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {seminars.map((seminar) => (
                <tr key={seminar.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-gray-900">
                      {seminar.title}
                    </div>
                    <div className="text-sm text-gray-500">
                      {seminar.location}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      {formatDate(seminar.event_date)}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      {seminar.current_registrations}/{seminar.capacity}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-semibold text-gray-900">
                      {formatPrice(seminar.price)}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    {getStatusBadge(seminar.status)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <Link
                      to={`${BASE_PATH}/admin/seminars/${seminar.id}/edit`}
                      className="text-primary-600 hover:text-primary-900 mr-4"
                    >
                      수정
                    </Link>
                    <button
                      onClick={() => handleDelete(seminar.id)}
                      className="text-red-600 hover:text-red-900"
                    >
                      삭제
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* 페이지네이션 */}
      {pagination && pagination.total_pages > 1 && (
        <div className="flex justify-center items-center mt-8 space-x-2">
          <button
            onClick={() => setPage(p => Math.max(1, p - 1))}
            disabled={page === 1}
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            이전
          </button>
          <span className="text-gray-700">
            {page} / {pagination.total_pages}
          </span>
          <button
            onClick={() => setPage(p => Math.min(pagination.total_pages, p + 1))}
            disabled={page === pagination.total_pages}
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            다음
          </button>
        </div>
      )}
    </div>
  )
}

export default AdminSeminars
