import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const company = ref(null)
  const isAuthenticated = ref(false)
  const loading = ref(false)

  // Getters
  const isLoggedIn = computed(() => isAuthenticated.value && user.value !== null)
  const userName = computed(() => user.value?.name || '')
  const userEmail = computed(() => user.value?.email || '')
  const companyName = computed(() => company.value?.name || '')
  const isOnTrial = computed(() => {
    if (!company.value?.trial_ends_at) return false
    return new Date(company.value.trial_ends_at) > new Date()
  })

  // Actions
  const initializeAuth = async () => {
    try {
      const response = await axios.get('/api/user')
      if (response.data.user) {
        user.value = response.data.user
        company.value = response.data.user.company
        isAuthenticated.value = true
      }
    } catch (error) {
      // User not authenticated
      isAuthenticated.value = false
      user.value = null
      company.value = null
    }
  }

  const login = async (credentials) => {
    loading.value = true
    try {
      const response = await axios.post('/login', credentials)
      
      if (response.status === 200) {
        await initializeAuth()
        return { success: true }
      }
    } catch (error) {
      return {
        success: false,
        errors: error.response?.data?.errors || { email: ['Error de autenticación'] }
      }
    } finally {
      loading.value = false
    }
  }

  const register = async (userData) => {
    loading.value = true
    try {
      const response = await axios.post('/register', userData)
      
      if (response.status === 200) {
        await initializeAuth()
        return { success: true }
      }
    } catch (error) {
      return {
        success: false,
        errors: error.response?.data?.errors || { error: ['Error en el registro'] }
      }
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    try {
      await axios.post('/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      user.value = null
      company.value = null
      isAuthenticated.value = false
      // Redirect handled by route guard
    }
  }

  const updateLastLogin = () => {
    if (user.value) {
      user.value.last_login_at = new Date().toISOString()
    }
  }

  return {
    // State
    user,
    company,
    isAuthenticated,
    loading,
    
    // Getters
    isLoggedIn,
    userName,
    userEmail,
    companyName,
    isOnTrial,
    
    // Actions
    initializeAuth,
    login,
    register,
    logout,
    updateLastLogin
  }
})