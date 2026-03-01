import { useEffect, useState } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { seminarAPI } from '../../api/endpoints'
import { BASE_PATH } from '../../constants/paths'

function AdminSeminarForm() {
  const { id } = useParams()
  const navigate = useNavigate()
  const isEdit = !!id

  const [loading, setLoading] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    event_date: '',
    location: '',
    capacity: 50,
    price: 0,
    thumbnail_url: '',
    poster_url: '',
    registration_start: '',
    registration_end: '',
    status: 'draft',
  })

  useEffect(() => {
    if (isEdit) {
      fetchSeminar()
    }
  }, [id])

  const fetchSeminar = async () => {
    try {
      setLoading(true)
      const response = await seminarAPI.getDetail(id)
      
      if (response.success) {
        const seminar = response.data
        setFormData({
          title: seminar.title || '',
          description: seminar.description || '',
          event_date: seminar.event_date ? seminar.event_date.slice(0, 16) : '',
          location: seminar.location || '',
          capacity: seminar.capacity || 50,
          price: seminar.price || 0,
          thumbnail_url: seminar.thumbnail_url || '',
          poster_url: seminar.poster_url || '',
          registration_start: seminar.registration_start ? seminar.registration_start.slice(0, 16) : '',
          registration_end: seminar.registration_end ? seminar.registration_end.slice(0, 16) : '',
          status: seminar.status || 'draft',
        })
      }
    } catch (error) {
      console.error('세미나 조회 실패:', error)
      alert('세미나를 불러오는데 실패했습니다.')
      navigate(`${BASE_PATH}/admin/seminars`)
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (e) => {
    const { name, value, type } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: type === 'number' ? parseInt(value) || 0 : value
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()

    try {
      setSubmitting(true)

      const response = isEdit
        ? await seminarAPI.adminUpdate(id, formData)
        : await seminarAPI.adminCreate(formData)

      if (response.success) {
        alert(isEdit ? '수정되었습니다.' : '생성되었습니다.')
        navigate(`${BASE_PATH}/admin/seminars`)
      }
    } catch (error) {
      console.error('저장 실패:', error)
      alert(error.message || '저장 중 오류가 발생했습니다.')
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    )
  }

  return (
    <div className="max-w-4xl mx-auto">
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-3xl font-bold text-gray-900">
          {isEdit ? '세미나 수정' : '세미나 추가'}
        </h1>
        <Link
          to={`${BASE_PATH}/admin/seminars`}
          className="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
        >
          목록으로
        </Link>
      </div>

      <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-md p-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* 제목 */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              제목 <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              name="title"
              value={formData.title}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="세미나 제목을 입력하세요"
            />
          </div>

          {/* 설명 */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              상세 설명
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              rows="6"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="세미나 상세 설명을 입력하세요 (HTML 지원)"
            />
          </div>

          {/* 일시 */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              세미나 일시 <span className="text-red-500">*</span>
            </label>
            <input
              type="datetime-local"
              name="event_date"
              value={formData.event_date}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          {/* 장소 */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              장소 <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              name="location"
              value={formData.location}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="예: 서울시 강남구 OO 교육장"
            />
          </div>

          {/* 정원 */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              정원 <span className="text-red-500">*</span>
            </label>
            <input
              type="number"
              name="capacity"
              value={formData.capacity}
              onChange={handleChange}
              required
              min="1"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          {/* 참가비 */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              참가비 (원) <span className="text-red-500">*</span>
            </label>
            <input
              type="number"
              name="price"
              value={formData.price}
              onChange={handleChange}
              required
              min="0"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="0"
            />
            <p className="text-sm text-gray-500 mt-1">0 입력 시 무료</p>
          </div>

          {/* 신청 시작일 */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              신청 시작일 <span className="text-red-500">*</span>
            </label>
            <input
              type="datetime-local"
              name="registration_start"
              value={formData.registration_start}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          {/* 신청 종료일 */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              신청 종료일 <span className="text-red-500">*</span>
            </label>
            <input
              type="datetime-local"
              name="registration_end"
              value={formData.registration_end}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          {/* 썸네일 URL */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              썸네일 이미지 URL
            </label>
            <input
              type="url"
              name="thumbnail_url"
              value={formData.thumbnail_url}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="https://example.com/image.jpg"
            />
          </div>

          {/* 포스터 URL */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              포스터 이미지 URL
            </label>
            <input
              type="url"
              name="poster_url"
              value={formData.poster_url}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="https://example.com/poster.jpg"
            />
          </div>

          {/* 상태 */}
          <div className="md:col-span-2">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              상태 <span className="text-red-500">*</span>
            </label>
            <select
              name="status"
              value={formData.status}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="draft">임시저장</option>
              <option value="published">공개</option>
              <option value="closed">마감</option>
            </select>
          </div>
        </div>

        {/* 버튼 */}
        <div className="flex justify-end space-x-4 mt-8">
          <Link
            to={`${BASE_PATH}/admin/seminars`}
            className="px-6 py-3 text-gray-700 font-semibold bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
          >
            취소
          </Link>
          <button
            type="submit"
            disabled={submitting}
            className="px-6 py-3 text-white font-semibold bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {submitting ? '저장 중...' : (isEdit ? '수정하기' : '생성하기')}
          </button>
        </div>
      </form>
    </div>
  )
}

export default AdminSeminarForm
