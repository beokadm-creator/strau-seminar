import { useEffect, useState } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { seminarAPI, registrationAPI } from '../api/endpoints'
import { BASE_PATH } from '../constants/paths'

function Registration() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [seminar, setSeminar] = useState(null)
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [paymentMethod, setPaymentMethod] = useState('card')

  useEffect(() => {
    fetchSeminar()
  }, [id])

  const fetchSeminar = async () => {
    try {
      setLoading(true)
      const response = await seminarAPI.getDetail(id)
      
      if (response.success) {
        if (response.data.is_registered) {
          alert('이미 신청한 세미나입니다.')
          navigate(`${BASE_PATH}/seminars/${id}`)
          return
        }
        setSeminar(response.data)
      } else {
        alert('세미나를 찾을 수 없습니다.')
        navigate(`${BASE_PATH}/seminars`)
      }
    } catch (error) {
      console.error('세미나 조회 실패:', error)
      alert('세미나 정보를 불러오는데 실패했습니다.')
      navigate(`${BASE_PATH}/seminars`)
    } finally {
      setLoading(false)
    }
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    if (!seminar) return

    if (window.confirm('정말로 신청하시겠습니까?')) {
      try {
        setSubmitting(true)
        
        const response = await registrationAPI.create({
          seminar_id: parseInt(id),
          payment_method: paymentMethod,
        })

        if (response.success) {
          alert('세미나 신청이 완료되었습니다.')
          navigate(`${BASE_PATH}/mypage`)
        }
      } catch (error) {
        console.error('신청 실패:', error)
        alert(error.message || '신청 중 오류가 발생했습니다.')
      } finally {
        setSubmitting(false)
      }
    }
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

  if (!seminar) {
    return null
  }

  return (
    <div className="max-w-2xl mx-auto">
      <Link
        to={`${BASE_PATH}/seminars/${id}`}
        className="inline-flex items-center text-gray-600 hover:text-primary-600 mb-6"
      >
        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        상세 정보로
      </Link>

      <div className="bg-white rounded-lg shadow-md p-6">
        <h1 className="text-2xl font-bold text-gray-900 mb-6">세미나 신청</h1>

        <div className="bg-gray-50 rounded-lg p-6 mb-6">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">
            {seminar.title}
          </h2>
          <div className="space-y-2 text-gray-700">
            <p>
              <span className="font-medium">일시:</span>{' '}
              {new Date(seminar.event_date).toLocaleString('ko-KR')}
            </p>
            <p>
              <span className="font-medium">장소:</span> {seminar.location}
            </p>
            <p>
              <span className="font-medium">참가비:</span>{' '}
              <span className="text-xl font-bold text-primary-600">
                {formatPrice(seminar.price)}
              </span>
            </p>
          </div>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              결제 수단 <span className="text-red-500">*</span>
            </label>
            <div className="space-y-2">
              <label className="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  type="radio"
                  name="payment_method"
                  value="card"
                  checked={paymentMethod === 'card'}
                  onChange={(e) => setPaymentMethod(e.target.value)}
                  className="mr-3"
                  required
                />
                <span>신용카드</span>
              </label>
              <label className="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  type="radio"
                  name="payment_method"
                  value="vbank"
                  checked={paymentMethod === 'vbank'}
                  onChange={(e) => setPaymentMethod(e.target.value)}
                  className="mr-3"
                />
                <span>가상계좌</span>
              </label>
            </div>
          </div>

          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p className="text-sm text-yellow-800">
              <strong>유의사항:</strong>
              <br />
              • 신청 후 24시간 이내에는 취소/환불이 가능합니다.
              <br />
              • 세미나 당일 24시간 전부터는 취소/환불이 불가능합니다.
              <br />
              • 결제 완료 후 마이페이지에서 신청 내역을 확인할 수 있습니다.
            </p>
          </div>

          <div className="flex space-x-4">
            <button
              type="button"
              onClick={() => navigate(`${BASE_PATH}/seminars/${id}`)}
              className="px-6 py-3 text-gray-700 font-semibold bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors flex-1 disabled:opacity-50 disabled:cursor-not-allowed"
              disabled={submitting}
            >
              취소
            </button>
            <button
              type="submit"
              className="px-6 py-3 text-white font-semibold bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors flex-1 disabled:opacity-50 disabled:cursor-not-allowed"
              disabled={submitting}
            >
              {submitting ? '신청 중...' : '신청하기'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default Registration
