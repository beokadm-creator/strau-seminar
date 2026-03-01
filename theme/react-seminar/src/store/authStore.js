import { create } from 'zustand'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_BASE_URL || '/api'

const useAuthStore = create((set, get) => ({
  user: null,
  isAuthenticated: false,
  isAdmin: false,
  isStaff: false,
  isLoading: true,

  fetchAuth: async () => {
    try {
      set({ isLoading: true })
      const response = await axios.get(`${API_BASE}/auth/me`, {
        withCredentials: true,
      })

      if (response.data.authenticated && response.data.user) {
        set({
          user: response.data.user,
          isAuthenticated: true,
          isAdmin: response.data.user.is_admin,
          isStaff: response.data.user.is_staff,
          isLoading: false,
        })
      } else {
        set({
          user: null,
          isAuthenticated: false,
          isAdmin: false,
          isStaff: false,
          isLoading: false,
        })
      }
    } catch (error) {
      console.error('Auth check failed:', error)
      set({
        user: null,
        isAuthenticated: false,
        isAdmin: false,
        isStaff: false,
        isLoading: false,
      })
    }
  },

  logout: async () => {
    try {
      await axios.post(`${API_BASE}/auth/logout`, {}, { withCredentials: true })
    } catch (error) {
      console.error('Logout failed:', error)
    } finally {
      set({
        user: null,
        isAuthenticated: false,
        isAdmin: false,
        isStaff: false,
        isLoading: false,
      })
      window.location.href = '/bbs/logout.php'
    }
  },

  requireAuth: () => {
    const { isAuthenticated, isAdmin, isStaff } = get()
    return { isAuthenticated, isAdmin, isStaff }
  },
}))

export default useAuthStore
