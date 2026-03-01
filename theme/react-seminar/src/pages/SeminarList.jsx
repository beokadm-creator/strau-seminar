import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { seminarAPI } from '../api/endpoints'
import { BASE_PATH } from '../constants/paths'

function SeminarList() {
  const [seminars, setSeminars] = useState([])
  const [loading, setLoading] = useState(true)
  const [page, setPage] = useState(1)
  const [pagination, setPagination] = useState(null)

  useEffect(() => {
    fetchSeminars()
  }, [page])

  const fetchSeminars = async () => {
    try {
      setLoading(true)
      const response = await seminarAPI.getList({ page, limit: 9 })
      
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

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('ko-KR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  const formatPrice = (price) => {
    return price === 0 ? '무료' : `${price.toLocaleString()}원`
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
      <h1 className="text-3xl font-bold text-gray-900 mb-8">세미나 목록</h1>

      {seminars.length === 0 ? (
        <div className="bg-white rounded-lg shadow-md p-6 text-center py-12">
          <p className="text-gray-600">등록된 세미나가 없습니다.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {seminars.map((seminar) => (
            <Link
              key={seminar.id}
              to={`${BASE_PATH}/seminars/${seminar.id}`}
              className="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 group"
            >
              {seminar.thumbnail_url && (
                <div className="relative overflow-hidden rounded-t-lg">
                  <img
                    src={seminar.thumbnail_url}
                    alt={seminar.title}
                    className="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                  />
                </div>
              )}
              <div className="p-4">
                <h2 className="text-xl font-semibold text-gray-900 mb-2 group-hover:text-primary-600 transition-colors">
                  {seminar.title}
                </h2>
                <div className="space-y-2 text-sm text-gray-600">
                  <p className="flex items-center">
                    <svg className="w-4 h-4 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {formatDate(seminar.event_date)}
                  </p>
                  <p className="flex items-center">
                    <svg className="w-4 h-4 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {seminar.location}
                  </p>
                  <p className="flex items-center justify-between">
                    <span className="flex items-center">
                      <svg className="w-4 h-4 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      {seminar.current_registrations}/{seminar.capacity}명
                    </span>
                    <span className="font-semibold text-primary-600">
                      {formatPrice(seminar.price)}
                    </span>
                  </p>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}

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

export default SeminarList
