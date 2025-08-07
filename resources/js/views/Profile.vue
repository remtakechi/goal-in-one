<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">プロフィール設定</h1>
        <p class="text-gray-600">アカウント情報を管理できます</p>
      </div>

      <!-- Profile Information -->
      <BaseCard title="プロフィール情報">
        <form @submit.prevent="updateProfile" class="space-y-6">
          <div class="flex items-center space-x-6">
            <!-- Avatar -->
            <div class="flex-shrink-0">
              <div class="w-20 h-20 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center">
                <span class="text-white font-bold text-2xl">{{ userInitials }}</span>
              </div>
            </div>
            
            <!-- User Info -->
            <div class="flex-1 space-y-4">
              <BaseInput
                v-model="profileForm.name"
                label="お名前"
                placeholder="山田太郎"
                :error="profileErrors.name"
                required
              />
              
              <BaseInput
                v-model="profileForm.email"
                type="email"
                label="メールアドレス"
                placeholder="your@email.com"
                :error="profileErrors.email"
                required
              />
            </div>
          </div>

          <!-- Success Message -->
          <div v-if="profileSuccess" class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
              <CheckCircleIcon class="h-5 w-5 text-green-400 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-green-800">プロフィールを更新しました</p>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="profileError" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-800">{{ profileError }}</p>
            </div>
          </div>

          <div class="flex justify-end">
            <BaseButton
              type="submit"
              variant="primary"
              :loading="profileLoading"
              loading-text="更新中..."
            >
              プロフィール更新
            </BaseButton>
          </div>
        </form>
      </BaseCard>

      <!-- Password Change -->
      <BaseCard title="パスワード変更">
        <form @submit.prevent="changePassword" class="space-y-6">
          <BaseInput
            v-model="passwordForm.currentPassword"
            type="password"
            label="現在のパスワード"
            placeholder="現在のパスワードを入力"
            :error="passwordErrors.currentPassword"
            autocomplete="current-password"
            required
          />

          <BaseInput
            v-model="passwordForm.newPassword"
            type="password"
            label="新しいパスワード"
            placeholder="8文字以上の新しいパスワード"
            :error="passwordErrors.newPassword"
            autocomplete="new-password"
            required
          />

          <BaseInput
            v-model="passwordForm.confirmPassword"
            type="password"
            label="新しいパスワード確認"
            placeholder="新しいパスワードを再入力"
            :error="passwordErrors.confirmPassword"
            autocomplete="new-password"
            required
          />

          <!-- Password Strength Indicator -->
          <div v-if="passwordForm.newPassword" class="space-y-2">
            <div class="text-sm text-gray-600">パスワード強度:</div>
            <div class="flex space-x-1">
              <div
                v-for="i in 4"
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

          <!-- Success Message -->
          <div v-if="passwordSuccess" class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
              <CheckCircleIcon class="h-5 w-5 text-green-400 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-green-800">パスワードを変更しました</p>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="passwordError" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" />
              <p class="text-sm text-red-800">{{ passwordError }}</p>
            </div>
          </div>

          <div class="flex justify-end">
            <BaseButton
              type="submit"
              variant="primary"
              :loading="passwordLoading"
              loading-text="変更中..."
              class="hover-scale"
            >
              パスワード変更
            </BaseButton>
          </div>
        </form>
      </BaseCard>

      <!-- Danger Zone -->
      <BaseCard title="危険な操作" variant="outlined" class="fade-in-up hover-lift">
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
          <div class="flex items-start">
            <ExclamationTriangleIcon class="h-6 w-6 text-red-600 mr-3 flex-shrink-0 mt-1" />
            <div class="flex-1">
              <h3 class="text-lg font-medium text-red-900 mb-2">アカウント削除</h3>
              <p class="text-sm text-red-700 mb-4">
                アカウントを削除すると、すべてのデータが永久に失われます。この操作は取り消すことができません。
              </p>
              
              <BaseButton
                variant="danger"
                @click="showDeleteModal = true"
                class="hover-scale"
              >
                アカウントを削除
              </BaseButton>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Delete Account Modal -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 modal-overlay"
      @click="showDeleteModal = false"
    >
      <div
        class="bg-white rounded-xl p-6 max-w-md w-full"
        @click.stop
      >
        <div class="flex items-center mb-4">
          <ExclamationTriangleIcon class="h-8 w-8 text-red-600 mr-3" />
          <h3 class="text-lg font-medium text-gray-900">アカウント削除の確認</h3>
        </div>
        
        <p class="text-sm text-gray-600 mb-4">
          本当にアカウントを削除しますか？この操作は取り消すことができません。
        </p>
        
        <BaseInput
          v-model="deleteForm.password"
          type="password"
          label="パスワードを入力して確認"
          placeholder="現在のパスワード"
          :error="deleteErrors.password"
          autocomplete="current-password"
          required
        />

        <!-- Error Message -->
        <div v-if="deleteError" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" />
            <p class="text-sm text-red-800">{{ deleteError }}</p>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <BaseButton
            variant="outline"
            @click="showDeleteModal = false"
          >
            キャンセル
          </BaseButton>
          <BaseButton
            variant="danger"
            :loading="deleteLoading"
            loading-text="削除中..."
            @click="handleDeleteAccount"
          >
            削除する
          </BaseButton>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/components/layout/AppLayout.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import {
  CheckCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()

// Profile form
const profileForm = reactive({
  name: '',
  email: ''
})

const profileErrors = reactive({
  name: '',
  email: ''
})

const profileLoading = ref(false)
const profileSuccess = ref(false)
const profileError = ref('')

// Password form
const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const passwordErrors = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const passwordLoading = ref(false)
const passwordSuccess = ref(false)
const passwordError = ref('')

// Delete account
const showDeleteModal = ref(false)
const deleteForm = reactive({
  password: ''
})

const deleteErrors = reactive({
  password: ''
})

const deleteLoading = ref(false)
const deleteError = ref('')

const userInitials = computed(() => {
  if (!authStore.user?.name) return 'U'
  return authStore.user.name
    .split(' ')
    .map(name => name.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const passwordStrength = computed(() => {
  const password = passwordForm.newPassword
  if (!password) return 0
  
  let strength = 0
  if (password.length >= 8) strength++
  if (/[a-z]/.test(password)) strength++
  if (/[A-Z]/.test(password) || /\d/.test(password)) strength++
  if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++
  
  return strength
})

const getStrengthColor = (strength: number): string => {
  switch (strength) {
    case 1: return 'bg-red-400'
    case 2: return 'bg-yellow-400'
    case 3: return 'bg-blue-400'
    case 4: return 'bg-green-400'
    default: return 'bg-gray-200'
  }
}

const getStrengthText = (strength: number): string => {
  switch (strength) {
    case 1: return '弱い'
    case 2: return '普通'
    case 3: return '強い'
    case 4: return '非常に強い'
    default: return ''
  }
}

const updateProfile = async () => {
  // Reset states
  profileSuccess.value = false
  profileError.value = ''
  Object.keys(profileErrors).forEach(key => {
    profileErrors[key as keyof typeof profileErrors] = ''
  })

  // Validation
  if (!profileForm.name.trim()) {
    profileErrors.name = 'お名前は必須です'
    return
  }

  if (!profileForm.email) {
    profileErrors.email = 'メールアドレスは必須です'
    return
  }

  profileLoading.value = true

  try {
    // Here you would call an API to update the profile
    // For now, we'll simulate success
    await new Promise(resolve => setTimeout(resolve, 1000))
    profileSuccess.value = true
    
    // Update the auth store user data
    if (authStore.user) {
      authStore.user.name = profileForm.name.trim()
      authStore.user.email = profileForm.email
    }
  } catch (error) {
    profileError.value = 'プロフィールの更新に失敗しました'
  } finally {
    profileLoading.value = false
  }
}

const changePassword = async () => {
  // Reset states
  passwordSuccess.value = false
  passwordError.value = ''
  Object.keys(passwordErrors).forEach(key => {
    passwordErrors[key as keyof typeof passwordErrors] = ''
  })

  // Validation
  if (!passwordForm.currentPassword) {
    passwordErrors.currentPassword = '現在のパスワードは必須です'
    return
  }

  if (!passwordForm.newPassword) {
    passwordErrors.newPassword = '新しいパスワードは必須です'
    return
  }

  if (passwordForm.newPassword.length < 8) {
    passwordErrors.newPassword = 'パスワードは8文字以上で入力してください'
    return
  }

  if (passwordForm.newPassword !== passwordForm.confirmPassword) {
    passwordErrors.confirmPassword = 'パスワードが一致しません'
    return
  }

  passwordLoading.value = true

  try {
    // Here you would call an API to change the password
    await new Promise(resolve => setTimeout(resolve, 1000))
    passwordSuccess.value = true
    
    // Reset form
    passwordForm.currentPassword = ''
    passwordForm.newPassword = ''
    passwordForm.confirmPassword = ''
  } catch (error) {
    passwordError.value = 'パスワードの変更に失敗しました'
  } finally {
    passwordLoading.value = false
  }
}

const handleDeleteAccount = async () => {
  // Reset states
  deleteError.value = ''
  deleteErrors.password = ''

  if (!deleteForm.password) {
    deleteErrors.password = 'パスワードは必須です'
    return
  }

  deleteLoading.value = true

  try {
    await authStore.deleteAccount(deleteForm.password)
    router.push('/login')
  } catch (error) {
    deleteError.value = 'アカウントの削除に失敗しました'
  } finally {
    deleteLoading.value = false
  }
}

onMounted(() => {
  // Initialize form with current user data
  if (authStore.user) {
    profileForm.name = authStore.user.name
    profileForm.email = authStore.user.email
  }
})
</script>