import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import type { User, LoginCredentials, RegisterCredentials, AuthResponse } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  // Actions
  const initializeAuth = () => {
    const storedToken = localStorage.getItem('auth_token')
    const storedUser = localStorage.getItem('auth_user')
    
    if (storedToken && storedUser) {
      token.value = storedToken
      user.value = JSON.parse(storedUser)
    }
  }

  const login = async (credentials: LoginCredentials): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post<AuthResponse>('/auth/login', credentials)
      const { user: userData, token: authToken } = response.data
      
      user.value = userData
      token.value = authToken
      
      // Store in localStorage
      localStorage.setItem('auth_token', authToken)
      localStorage.setItem('auth_user', JSON.stringify(userData))
      
    } catch (err: any) {
      error.value = err.response?.data?.message || 'ログインに失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const register = async (credentials: RegisterCredentials): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post<AuthResponse>('/auth/register', credentials)
      const { user: userData, token: authToken } = response.data
      
      user.value = userData
      token.value = authToken
      
      // Store in localStorage
      localStorage.setItem('auth_token', authToken)
      localStorage.setItem('auth_user', JSON.stringify(userData))
      
    } catch (err: any) {
      error.value = err.response?.data?.message || 'ユーザー登録に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const logout = async (): Promise<void> => {
    loading.value = true
    
    try {
      await axios.post('/auth/logout')
    } catch (err) {
      // Ignore logout errors
      console.warn('Logout request failed:', err)
    } finally {
      // Clear state regardless of API response
      user.value = null
      token.value = null
      
      // Clear localStorage
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      
      loading.value = false
    }
  }

  const deleteAccount = async (password: string): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      await axios.delete('/auth/account', {
        data: { password }
      })
      
      // Clear state
      user.value = null
      token.value = null
      
      // Clear localStorage
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      
    } catch (err: any) {
      error.value = err.response?.data?.message || 'アカウント削除に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchUser = async (): Promise<void> => {
    if (!token.value) return
    
    try {
      const response = await axios.get<{ user: User }>('/auth/user')
      user.value = response.data.user
      
      // Update localStorage
      localStorage.setItem('auth_user', JSON.stringify(response.data.user))
    } catch (err) {
      // If user fetch fails, clear auth state
      await logout()
    }
  }

  const clearError = () => {
    error.value = null
  }

  return {
    // State
    user,
    token,
    loading,
    error,
    
    // Getters
    isAuthenticated,
    
    // Actions
    initializeAuth,
    login,
    register,
    logout,
    deleteAccount,
    fetchUser,
    clearError
  }
})