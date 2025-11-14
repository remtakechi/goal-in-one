<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Header -->
      <div class="text-center">
        <div class="mx-auto w-16 h-16 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center mb-4">
          <span class="text-white font-bold text-2xl">G</span>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-2">Goal in One</h2>
        <p class="text-gray-600">新しいアカウントを作成してください</p>
      </div>

      <!-- Register Form -->
      <BaseCard class="mt-8 fade-in-up stagger-1">
        <form @submit.prevent="handleRegister" class="space-y-6">
          <!-- Name -->
          <BaseInput
            v-model="form.name"
            type="text"
            label="お名前"
            placeholder="山田太郎"
            required
            :error="errors.name"
            autocomplete="name"
          />

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
          <div>
            <BaseInput
              v-model="form.password"
              type="password"
              label="パスワード"
              placeholder="8文字以上のパスワード"
              required
              :error="errors.password"
              autocomplete="new-password"
            />
            <p class="text-xs text-gray-500 mt-1 ml-1">
              8文字以上、大文字・小文字・数字・記号を含む必要があります
            </p>
          </div>

          <!-- Password Confirmation -->
          <BaseInput
            v-model="form.password_confirmation"
            type="password"
            label="パスワード確認"
            placeholder="パスワードを再入力"
            required
            :error="errors.password_confirmation"
            autocomplete="new-password"
          />

          <!-- Password Strength Indicator -->
          <div v-if="form.password" class="space-y-2">
            <div class="text-sm text-gray-600">パスワード強度:</div>
            <div class="flex space-x-1">
              <div
                v-for="i in 5"
                :key="i"
                :class="[
                  'h-2 flex-1 rounded-full transition-colors duration-200',
                  passwordStrength >= i ? getStrengthColor(passwordStrength) : 'bg-gray-200'
                ]"
              />
            </div>
            <div class="text-xs text-gray-500">
              {{ getStrengthText(passwordStrength) }}
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="authStore.error" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-800">{{ authStore.error }}</p>
            </div>
          </div>

          <!-- Terms Agreement -->
          <div class="flex items-start">
            <input
              id="terms"
              v-model="form.agreeToTerms"
              type="checkbox"
              class="mt-1 h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            />
            <label for="terms" class="ml-2 text-sm text-gray-600">
              <span class="text-primary-600 hover:text-primary-500 cursor-pointer">利用規約</span>
              および
              <span class="text-primary-600 hover:text-primary-500 cursor-pointer">プライバシーポリシー</span>
              に同意します
            </label>
          </div>
          <div v-if="errors.agreeToTerms" class="text-sm text-red-600">
            {{ errors.agreeToTerms }}
          </div>

          <!-- Submit Button -->
          <BaseButton
            type="submit"
            variant="primary"
            size="lg"
            :loading="authStore.loading"
            loading-text="登録中..."
            full-width
            class="animate-fade-in hover-scale"
          >
            アカウント作成
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

        <!-- Login Link -->
        <div class="mt-6 text-center fade-in-up stagger-2">
          <p class="text-sm text-gray-600">
            すでにアカウントをお持ちの方は
            <router-link
              to="/login"
              class="font-medium text-primary-600 hover:text-primary-500 transition-colors duration-200 hover-scale"
            >
              ログイン
            </router-link>
          </p>
        </div>
      </BaseCard>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  agreeToTerms: false
})

const errors = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  agreeToTerms: ''
})

const passwordStrength = computed(() => {
  const password = form.password
  if (!password) return 0

  let strength = 0

  // Length check (8+ characters)
  if (password.length >= 8) strength++

  // Contains lowercase
  if (/[a-z]/.test(password)) strength++

  // Contains uppercase
  if (/[A-Z]/.test(password)) strength++

  // Contains numbers
  if (/\d/.test(password)) strength++

  // Contains special characters
  if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++

  return strength
})

const getStrengthColor = (strength: number): string => {
  switch (strength) {
    case 1: return 'bg-red-400'
    case 2: return 'bg-orange-400'
    case 3: return 'bg-yellow-400'
    case 4: return 'bg-blue-400'
    case 5: return 'bg-green-400'
    default: return 'bg-gray-200'
  }
}

const getStrengthText = (strength: number): string => {
  switch (strength) {
    case 1: return '非常に弱い'
    case 2: return '弱い'
    case 3: return '普通'
    case 4: return '強い'
    case 5: return '非常に強い（すべての要件を満たしています）'
    default: return ''
  }
}

const validateForm = (): boolean => {
  // Reset errors
  Object.keys(errors).forEach(key => {
    errors[key as keyof typeof errors] = ''
  })

  let isValid = true

  // Name validation
  if (!form.name.trim()) {
    errors.name = 'お名前は必須です'
    isValid = false
  } else if (form.name.trim().length < 2) {
    errors.name = 'お名前は2文字以上で入力してください'
    isValid = false
  }

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
  } else if (!/[a-z]/.test(form.password)) {
    errors.password = 'パスワードには小文字を含める必要があります'
    isValid = false
  } else if (!/[A-Z]/.test(form.password)) {
    errors.password = 'パスワードには大文字を含める必要があります'
    isValid = false
  } else if (!/\d/.test(form.password)) {
    errors.password = 'パスワードには数字を含める必要があります'
    isValid = false
  } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(form.password)) {
    errors.password = 'パスワードには記号を含める必要があります'
    isValid = false
  }

  // Password confirmation validation
  if (!form.password_confirmation) {
    errors.password_confirmation = 'パスワード確認は必須です'
    isValid = false
  } else if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'パスワードが一致しません'
    isValid = false
  }

  // Terms agreement validation
  if (!form.agreeToTerms) {
    errors.agreeToTerms = '利用規約とプライバシーポリシーに同意してください'
    isValid = false
  }

  return isValid
}

const handleRegister = async () => {
  if (!validateForm()) return

  try {
    await authStore.register({
      name: form.name.trim(),
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation
    })
    
    router.push('/dashboard')
  } catch (error) {
    // Error is handled by the store
    console.error('Registration failed:', error)
  }
}

onMounted(() => {
  // Clear any previous errors
  authStore.clearError()
})
</script>