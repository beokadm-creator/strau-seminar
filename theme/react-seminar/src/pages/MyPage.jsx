import { useEffect, useState } from 'react'
import { registrationAPI } from '../api/endpoints'
import QRCode from 'react-qr-code'

function MyPage() {
  const [registrations, setRegistrations] = useState([])
  const [loading, setLoading] = useState(true)
  const [selectedQR, setSelectedQR] = useState(null)
  const [statusFilter, setStatusFilter] = useState('')

  useEffect(() => {
    fetchRegistrations()
  }, [statusFilter])

  const fetchRegistrations = async () => {
    try {
      setLoading(true)
      const response = await registrationAPI.getMyRegistrations(
        statusFilter ? { status: statusFilter } : {}
      )
      
      if (response.success) {
        setRegistrations(response.data)
      }
    } catch (error) {
      console.error('신청 내역 조회 실패:', error)
      alert('신청 내역을 불러오는데 실패했습니다.')
    } finally {
      setLoading(false)
    }
  }

  const handleShowQR = async (registration) => {
    try {
      const response = await registrationAPI.getQRCode(registration.id)
      
      if (response.success) {
        setSelectedQR(response.data)
      }
    } catch (error) {
      console.error('QR코드 조회 실패:', error)
      alert('QR코드를 불러오는데 실패했습니다.')
    }
  }

  const handleCancel = async (id) => {
    if (!window.confirm('정말로 취소하시겠습니까?')) {
      return
    }

    try {
      const response = await registrationAPI.cancel(id)
      
      if (response.success) {
        alert('취소되었습니다.')
        fetchRegistrations()
      }
    } catch (error) {
      console.error('취소 실패:', error)
      alert(error.message || '취소 중 오류가 발생했습니다.')
    }
  }

  const getStatusBadge = (status) => {
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
      <h1 className="text-3xl font-bold text-gray-900 mb-8">마이페이지</h1>

      {/* Status Filter */}
      <div className="mb-6">
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="input-field max-w-xs"
        >
          <option value="">전체</option>
          <option value="paid">결제 완료</option>
          <option value="pending">결제 대기</option>
          <option value="cancelled">취소됨</option>
          <option value="refunded">환불됨</option>
        </select>
      </div>

      {registrations.length === 0 ? (
        <div className="card text-center py-12">
          <p className="text-gray-600">신청한 세미나가 없습니다.</p>
        </div>
      ) : (
        <div className="space-y-4">
          {registrations.map((reg) => (
            <div key={reg.id} className="card">
              <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                <div className="flex-1">
                  <h3 className="text-xl font-semibold text-gray-900 mb-2">
                    {reg.title}
                  </h3>
                  <div className="space-y-1 text-sm text-gray-600">
                    <p>
                      <span className="font-medium">일시:</span>{' '}
                      {new Date(reg.event_date).toLocaleString('ko-KR')}
                    </p>
                    <p>
                      <span className="font-medium">장소:</span> {reg.location}
                    </p>
                    <p>
                      <span className="font-medium">신청일:</span>{' '}
                      {new Date(reg.created_at).toLocaleDateString('ko-KR')}
                    </p>
                  </div>
                  <div className="mt-3 flex flex-wrap gap-2">
                    {getStatusBadge(reg.payment_status)}
                    {getAttendanceBadge(reg.attendance_status)}
                  </div>
                </div>

                <div className="mt-4 md:mt-0 md:ml-6 flex flex-col space-y-2">
                  {reg.payment_status === 'paid' && (
                    <button
                      onClick={() => handleShowQR(reg)}
                      className="btn-secondary text-sm"
                    >
                      QR코드
                    </button>
                  )}
                  
                  {reg.payment_status === 'paid' && reg.attendance_status !== 'attended' && (
                    <button
                      onClick={() => handleCancel(reg.id)}
                      className="btn-secondary text-sm text-red-600 hover:bg-red-50"
                    >
                      취소하기
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* QR Code Modal */}
      {selectedQR && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
          onClick={() => setSelectedQR(null)}
        >
          <div
            className="bg-white rounded-lg p-8 max-w-sm w-full"
            onClick={(e) => e.stopPropagation()}
          >
            <h3 className="text-xl font-semibold text-gray-900 mb-4 text-center">
              출석 QR코드
            </h3>
            <div className="bg-white p-4 rounded-lg flex justify-center mb-4">
              <QRCode value={selectedQR.token} size={200} />
            </div>
            <p className="text-center text-gray-700 font-medium mb-1">
              {selectedQR.seminar_title}
            </p>
            <p className="text-center text-sm text-gray-600 mb-4">
              {new Date(selectedQR.event_date).toLocaleString('ko-KR')}
            </p>
            <p className="text-xs text-gray-500 text-center">
              현장 스태프에게 이 QR코드를 보여주세요.
            </p>
            <button
              onClick={() => setSelectedQR(null)}
              className="w-full mt-4 btn-primary"
            >
              닫기
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

export default MyPage
