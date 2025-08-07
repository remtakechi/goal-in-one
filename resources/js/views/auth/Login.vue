<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div class="mx-auto w-16 h-16 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center mb-4">
          <span class="text-white font-bold text-2xl">G</span>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Goal in One</h2>
        <p class="text-gray-600">アカウントにログインしてください</p>
      </div>

      <!-- Login Form -->
      <BaseCard class="mt-8 fade-in-up stagger-1">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email -->
          <BaseInput
            v-model="form.email"
            type="email"
            label="メールアドレス"
            placeholder="your@email.com"
            required
            :error="errors.email"
            autocomplete="email"
          />

          <!-- Password -->
          <BaseInput
            v-model="form.password"
            type="password"
            label="パスワード"
            placeholder="パスワードを入力"
            required
            :error="errors.password"
            autocomplete="current-password"
          />

          <!-- Error Message -->
          <div v-if="authStore.error" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-800">{{ authStore.error }}</p>
            </div>
          </div>

          <!-- Submit Button -->
          <BaseButton
            type="submit"
            variant="primary"
            size="lg"
            :loading="authStore.loading"
            loading-text="ログイン中..."
            full-width
            class="animate-fade-in hover-scale"
          >
            ログイン
          </BaseButton>
        </form>

        <!-- Divider -->
        <div class="mt-6">
          <div class="relative">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300" />
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">または</span>
            </div>
          </div>
        </div>

        <!-- Register Link -->
        <div class="mt-6 text-center fade-in-up stagger-2">
          <p class="text-sm text-gray-600">
            アカウントをお持ちでない方は
            <router-link
              to="/register"
              class="font-medium text-primary-600 hover:text-primary-500 transition-colors duration-200 hover-scale"
            >
              新規登録
            </router-link>
          </p>
        </div>
      </BaseCard>

      <!-- Features -->
      <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3 fade-in-up stagger-3">
        <div class="text-center p-4 bg-white/50 rounded-lg backdrop-blur-sm">
          <ChartBarIcon class="h-8 w-8 text-primary-600 mx-auto mb-2" />
          <h3 class="text-sm font-medium text-gray-900">進捗管理</h3>
          <p class="text-xs text-gray-600 mt-1">目標の進捗を可視化</p>
        </div>
        <div class="text-center p-4 bg-white/50 rounded-lg backdrop-blur-sm">
          <ClipboardDocumentListIcon class="h-8 w-8 text-secondary-600 mx-auto mb-2" />
          <h3 class="text-sm font-medium text-gray-900">タスク管理</h3>
          <p class="text-xs text-gray-600 mt-1">効率的なタスク管理</p>
        </div>
        <div class="text-center p-4 bg-white/50 rounded-lg backdrop-blur-sm">
          <SparklesIcon class="h-8 w-8 text-success-600 mx-auto mb-2" />
          <h3 class="text-sm font-medium text-gray-900">ゲーミフィケーション</h3>
          <p class="text-xs text-gray-600 mt-1">楽しく目標達成</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import {
  ExclamationTriangleIcon,
  ChartBarIcon,
  ClipboardDocumentListIcon,
  SparklesIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: ''
})

const errors = reactive({
  email: '',
  password: ''
})

const validateForm = (): boolean => {
  // Reset errors
  errors.email = ''
  errors.password = ''

  let isValid = true

  // Email validation
  if (!form.email) {
    errors.email = 'メールアドレスは必須です'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = '有効なメールアドレスを入力してください'
    isValid = false
  }

  // Password validation
  if (!form.password) {
    errors.password = 'パスワードは必須です'
    isValid = false
  } else if (form.password.length < 8) {
    errors.password = 'パスワードは8文字以上で入力してください'
    isValid = false
  }

  return isValid
}

const handleLogin = async () => {
  if (!validateForm()) return

  try {
    await authStore.login({
      email: form.email,
      password: form.password
    })
    
    router.push('/dashboard')
  } catch (error) {
    // Error is handled by the store
    console.error('Login failed:', error)
  }
}

onMounted(() => {
  // Clear any previous errors
  authStore.clearError()
})
</script>