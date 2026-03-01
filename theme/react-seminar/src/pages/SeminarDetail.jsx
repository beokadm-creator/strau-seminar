import { useEffect, useState } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { seminarAPI } from '../api/endpoints'
import { BASE_PATH } from '../constants/paths'

function SeminarDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [seminar, setSeminar] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchSeminar()
  }, [id])

  const fetchSeminar = async () => {
    try {
      setLoading(true)
      const response = await seminarAPI.getDetail(id)
      
      if (response.success) {
        setSeminar(response.data)
      } else {
        alert('세미나를 찾을 수 없습니다.')
        navigate(`${BASE_PATH}/seminars`)
      }
    } catch (error) {
      console.error('세미나 상세 조회 실패:', error)
      alert('세미나 정보를 불러오는데 실패했습니다.')
      navigate(`${BASE_PATH}/seminars`)
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

  const handleRegister = () => {
    if (!seminar.is_registered) {
      navigate(`${BASE_PATH}/seminars/${id}/register`)
    }
  }

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    )
  }

  if (!seminar) {
    return null
  }

  const isRegistrationOpen = seminar.status === 'published' &&
    seminar.current_registrations < seminar.capacity

  return (
    <div>
      <Link
        to={`${BASE_PATH}/seminars`}
        className="inline-flex items-center text-gray-600 hover:text-primary-600 mb-6"
      >
        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        목록으로
      </Link>

      <div className="bg-white rounded-lg shadow-md p-6">
        {seminar.poster_url && (
          <img
            src={seminar.poster_url}
            alt={seminar.title}
            className="w-full max-w-2xl mx-auto rounded-lg mb-8"
          />
        )}

        <h1 className="text-3xl font-bold text-gray-900 mb-6">
          {seminar.title}
        </h1>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div className="space-y-4">
            <div className="flex items-center text-gray-700">
              <svg className="w-5 h-5 mr-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span className="font-medium">일시:</span>
              <span className="ml-2">{formatDate(seminar.event_date)}</span>
            </div>

            <div className="flex items-center text-gray-700">
              <svg className="w-5 h-5 mr-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span className="font-medium">장소:</span>
              <span className="ml-2">{seminar.location}</span>
            </div>

            <div className="flex items-center text-gray-700">
              <svg className="w-5 h-5 mr-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span className="font-medium">정원:</span>
              <span className="ml-2">
                {seminar.current_registrations}/{seminar.capacity}명
              </span>
            </div>
          </div>

          <div className="space-y-4">
            <div className="flex items-center text-gray-700">
              <svg className="w-5 h-5 mr-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span className="font-medium">참가비:</span>
              <span className="ml-2 text-2xl font-bold text-primary-600">
                {formatPrice(seminar.price)}
              </span>
            </div>

            <div className="flex items-center text-gray-700">
              <svg className="w-5 h-5 mr-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span className="font-medium">신청 기간:</span>
              <span className="ml-2">
                {formatDate(seminar.registration_start)} ~ {formatDate(seminar.registration_end)}
              </span>
            </div>

            <div className="flex items-center text-gray-700">
              <svg className="w-5 h-5 mr-3 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span className="font-medium">상태:</span>
              <span className="ml-2">
                {seminar.status === 'published' ? '신청 가능' : 
                 seminar.status === 'closed' ? '마감' : '비공개'}
              </span>
            </div>
          </div>
        </div>

        {seminar.description && (
          <div className="border-t pt-6 mb-8">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">상세 정보</h2>
            <div
              className="prose max-w-none text-gray-700"
              dangerouslySetInnerHTML={{ __html: seminar.description }}
            />
          </div>
        )}

        <div className="flex justify-end">
          {seminar.is_registered ? (
            <button disabled className="px-6 py-3 text-white font-semibold bg-green-600 rounded-lg opacity-75 cursor-not-allowed">
              이미 신청한 세미나입니다
            </button>
          ) : !isRegistrationOpen ? (
            <button disabled className="px-6 py-3 text-gray-700 font-semibold bg-gray-200 rounded-lg opacity-75 cursor-not-allowed">
              {seminar.current_registrations >= seminar.capacity ? '정원 마감' : '신청 기간 아님'}
            </button>
          ) : (
            <button onClick={handleRegister} className="px-6 py-3 text-white font-semibold bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
              신청하기
            </button>
          )}
        </div>
      </div>
    </div>
  )
}

export default SeminarDetail
