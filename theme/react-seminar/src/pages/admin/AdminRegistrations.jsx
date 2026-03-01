import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { registrationAPI } from '../../api/endpoints'
import { BASE_PATH } from '../../constants/paths'

function AdminRegistrations() {
  const [registrations, setRegistrations] = useState([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [pagination, setPagination] = useState(null)
  const [statusFilter, setStatusFilter] = useState('')
  const [seminarFilter, setSeminarFilter] = useState('')

  useEffect(() => {
    fetchRegistrations()
  }, [page, statusFilter, seminarFilter])

  const fetchRegistrations = async () => {
    try {
      setLoading(true)
      const params = { page, limit: 20 }
      if (statusFilter) params.payment_status = statusFilter
      if (seminarFilter) params.seminar_id = seminarFilter
      
      const response = await registrationAPI.adminGetList(params)
      
      if (response.success) {
        setRegistrations(response.data.registrations)
        setPagination(response.data.pagination)
      }
    } catch (error) {
      console.error('신청 내역 조회 실패:', error)
      alert('신청 내역을 불러오는데 실패했습니다.')
    } finally {
      setLoading(false)
    }
  }

  const handleStatusChange = async (id, newStatus) => {
    try {
      const response = await registrationAPI.adminUpdateStatus(id, newStatus)
      if (response.success) {
        alert('상태가 변경되었습니다.')
        fetchRegistrations()
      }
    } catch (error) {
      console.error('상태 변경 실패:', error)
      alert(error.message || '상태 변경 중 오류가 발생했습니다.')
    }
  }

  const handleAttendance = async (id) => {
    try {
      const response = await registrationAPI.adminCheckAttendance(id)
      if (response.success) {
        alert('출석이 체크되었습니다.')
        fetchRegistrations()
      }
    } catch (error) {
      console.error('출석 체크 실패:', error)
      alert(error.message || '출석 체크 중 오류가 발생했습니다.')
    }
  }

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('ko-KR')
  }

  const getPaymentStatusBadge = (status) => {
    const badges = {
      paid: 'bg-green-100 text-green-800',
      pending: 'bg-yellow-100 text-yellow-800',
      cancelled: 'bg-red-100 text-red-800',
      refunded: 'bg-gray-100 text-gray-800',
    }
    
    const labels = {
      paid: '결제 완료',
      pending: '결제 대기',
      cancelled: '취소됨',
      refunded: '환불됨',
    }
    
    return (
      <span className={`px-3 py-1 rounded-full text-sm font-medium ${badges[status] || badges.pending}`}>
        {labels[status] || status}
      </span>
    )
  }

  const getAttendanceBadge = (status) => {
    return status === 'attended' ? (
      <span className="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
        출석 완료
      </span>
    ) : (
      <span className="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
        미출석
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
        <h1 className="text-3xl font-bold text-gray-900">참가자 관리</h1>
      </div>

      {/* 필터 */}
      <div className="bg-white rounded-lg shadow-md p-4 mb-6">
        <div className="flex flex-wrap items-center gap-4">
          <div className="flex items-center gap-2">
            <label className="text-gray-700 font-medium">결제 상태:</label>
            <select
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value)
                setPage(1)
              }}
              className="border border-gray-300 rounded-lg px-4 py-2"
            >
              <option value="">전체</option>
              <option value="paid">결제 완료</option>
              <option value="pending">결제 대기</option>
              <option value="cancelled">취소됨</option>
              <option value="refunded">환불됨</option>
            </select>
          </div>

          <div className="flex items-center gap-2">
            <label className="text-gray-700 font-medium">세미나:</label>
            <input
              type="text"
              value={seminarFilter}
              onChange={(e) => {
                setSeminarFilter(e.target.value)
                setPage(1)
              }}
              placeholder="세미나 ID 입력"
              className="border border-gray-300 rounded-lg px-4 py-2"
            />
          </div>
        </div>
      </div>

      {registrations.length === 0 ? (
        <div className="bg-white rounded-lg shadow-md p-6 text-center py-12">
          <p className="text-gray-600">신청 내역이 없습니다.</p>
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  신청자
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  세미나
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  신청일
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  결제 상태
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  출석
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  관리
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {registrations.map((reg) => (
                <tr key={reg.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-gray-900">
                      {reg.user_name || reg.member_id}
                    </div>
                    <div className="text-sm text-gray-500">
                      {reg.user_email}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="text-sm text-gray-900">
                      {reg.seminar_title}
                    </div>
                    <div className="text-sm text-gray-500">
                      {new Date(reg.event_date).toLocaleDateString('ko-KR')}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      {formatDate(reg.created_at)}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    {getPaymentStatusBadge(reg.payment_status)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    {getAttendanceBadge(reg.attendance_status)}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                    <div className="flex flex-col gap-2">
                      {reg.payment_status === 'paid' && reg.attendance_status !== 'attended' && (
                        <button
                          onClick={() => handleAttendance(reg.id)}
                          className="text-blue-600 hover:text-blue-900"
                        >
                          출석 체크
                        </button>
                      )}
                      
                      {reg.payment_status === 'pending' && (
                        <button
                          onClick={() => handleStatusChange(reg.id, 'paid')}
                          className="text-green-600 hover:text-green-900"
                        >
                          결제 확인
                        </button>
                      )}
                      
                      {reg.payment_status === 'paid' && reg.attendance_status !== 'attended' && (
                        <button
                          onClick={() => handleStatusChange(reg.id, 'refunded')}
                          className="text-red-600 hover:text-red-900"
                        >
                          환불
                        </button>
                      )}
                    </div>
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

export default AdminRegistrations
