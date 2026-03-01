import { Link } from 'react-router-dom'
import { useEffect, useState } from 'react'
import { seminarAPI } from '../api/endpoints'

function Home() {
  const [featuredSeminars, setFeaturedSeminars] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetchFeaturedSeminars()
  }, [])

  const fetchFeaturedSeminars = async () => {
    try {
      const response = await seminarAPI.getList({ limit: 3, status: 'published' })
      if (response.success) {
        setFeaturedSeminars(response.data.seminars)
      }
    } catch (error) {
      console.error('세미나 목록 조회 실패:', error)
    } finally {
      setLoading(false)
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

  return (
    <div>
      <section className="mb-16">
        <div className="bg-gradient-to-r from-primary-600 to-primary-800 rounded-2xl p-12 text-white">
          <h1 className="text-4xl md:text-5xl font-bold mb-4">
            Straumann Campus
          </h1>
          <p className="text-xl mb-8 text-primary-100">
            치과 의료진을 위한 전문 교육 플랫폼
          </p>
          <div className="flex flex-wrap gap-4">
            <Link
              to="/seminars"
              className="px-6 py-3 bg-white text-primary-600 font-semibold rounded-lg hover:bg-primary-50 transition-colors"
            >
              세미나 둘러보기
            </Link>
            <a
              href="/bbs/register.php"
              className="px-6 py-3 bg-primary-700 text-white font-semibold rounded-lg hover:bg-primary-900 transition-colors"
            >
              회원가입
            </a>
          </div>
        </div>
      </section>

      <section className="mb-16">
        <div className="flex justify-between items-center mb-8">
          <h2 className="text-3xl font-bold text-gray-900">공지사항</h2>
          <a
            href="/bbs/board.php?bo_table=notice"
            className="text-primary-600 hover:text-primary-700 font-medium"
          >
            더보기 &rarr;
          </a>
        </div>
        <div className="card">
          <div className="space-y-4">
            {[1, 2, 3].map((item) => (
              <div key={item} className="flex items-start justify-between py-3 border-b last:border-b-0">
                <div className="flex-1">
                  <p className="text-gray-900 font-medium mb-1">
                    공지사항 제목 {item}
                  </p>
                  <p className="text-sm text-gray-600">
                    2024.03.01
                  </p>
                </div>
                <span className="px-3 py-1 text-xs font-medium bg-primary-100 text-primary-700 rounded-full">
                  공지
                </span>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="mb-16">
        <div className="flex justify-between items-center mb-8">
          <h2 className="text-3xl font-bold text-gray-900"> upcoming 세미나</h2>
          <Link
            to="/seminars"
            className="text-primary-600 hover:text-primary-700 font-medium"
          >
            전체보기 &rarr;
          </Link>
        </div>

        {loading ? (
          <div className="flex justify-center items-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
          </div>
        ) : featuredSeminars.length === 0 ? (
          <div className="card text-center py-12">
            <p className="text-gray-600">예정된 세미나가 없습니다.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {featuredSeminars.map((seminar) => (
              <Link
                key={seminar.id}
                to={`/seminars/${seminar.id}`}
                className="card hover:shadow-xl transition-all duration-300 group"
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
                <div className="pt-4">
                  <h3 className="text-xl font-semibold text-gray-900 mb-3 group-hover:text-primary-600 transition-colors">
                    {seminar.title}
                  </h3>
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
                      <span className="font-semibold text-primary-600 text-lg">
                        {formatPrice(seminar.price)}
                      </span>
                    </p>
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </section>

      <section className="mb-16">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="card bg-gradient-to-br from-blue-50 to-blue-100 p-8">
            <h3 className="text-2xl font-bold text-gray-900 mb-4">자유게시판</h3>
            <p className="text-gray-700 mb-6">
              자유롭게 소통하고 정보를 공유하세요
            </p>
            <a
              href="/bbs/board.php?bo_table=free"
              className="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors"
            >
              바로가기
            </a>
          </div>

          <div className="card bg-gradient-to-br from-green-50 to-green-100 p-8">
            <h3 className="text-2xl font-bold text-gray-900 mb-4">갤러리</h3>
            <p className="text-gray-700 mb-6">
              활동 사진을 구경하세요
            </p>
            <a
              href="/bbs/board.php?bo_table=gallery"
              className="inline-block px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors"
            >
              바로가기
            </a>
          </div>
        </div>
      </section>
    </div>
  )
}

export default Home
