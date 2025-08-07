<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <!-- Logo and Navigation -->
          <div class="flex items-center">
            <router-link to="/dashboard" class="flex items-center">
              <div class="flex-shrink-0 flex items-center">
                <div class="w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-lg flex items-center justify-center">
                  <span class="text-white font-bold text-sm">G</span>
                </div>
                <span class="ml-2 text-xl font-bold text-gray-900">Goal in One</span>
              </div>
            </router-link>
            
            <!-- Desktop Navigation -->
            <div class="hidden md:ml-10 md:flex md:space-x-8">
              <router-link
                v-for="item in navigation"
                :key="item.name"
                :to="item.href"
                :class="[
                  'inline-flex items-center px-1 pt-1 text-sm font-medium transition-colors duration-200',
                  $route.path === item.href
                    ? 'border-b-2 border-primary-500 text-gray-900'
                    : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
              >
                <component :is="item.icon" class="w-4 h-4 mr-2" />
                {{ item.name }}
              </router-link>
            </div>
          </div>
          
          <!-- User Menu -->
          <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <button class="p-2 text-gray-400 hover:text-gray-500 transition-colors duration-200">
              <BellIcon class="w-5 h-5" />
            </button>
            
            <!-- User Dropdown -->
            <div class="relative">
              <button
                @click="showUserMenu = !showUserMenu"
                class="flex items-center space-x-2 p-2 text-sm text-gray-700 hover:text-gray-900 transition-colors duration-200"
              >
                <div class="w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center">
                  <span class="text-white font-medium text-sm">{{ userInitials }}</span>
                </div>
                <span class="hidden md:block">{{ authStore.user?.name }}</span>
                <ChevronDownIcon class="w-4 h-4" />
              </button>
              
              <!-- Dropdown Menu -->
              <div
                v-show="showUserMenu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                @click="showUserMenu = false"
              >
                <router-link
                  to="/profile"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200"
                >
                  プロフィール
                </router-link>
                <hr class="my-1">
                <button
                  @click="handleLogout"
                  class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200"
                >
                  ログアウト
                </button>
              </div>
            </div>
            
            <!-- Mobile menu button -->
            <button
              @click="showMobileMenu = !showMobileMenu"
              class="md:hidden p-2 text-gray-400 hover:text-gray-500 transition-colors duration-200"
            >
              <Bars3Icon v-if="!showMobileMenu" class="w-6 h-6" />
              <XMarkIcon v-else class="w-6 h-6" />
            </button>
          </div>
        </div>
      </div>
      
      <!-- Mobile Navigation -->
      <div v-show="showMobileMenu" class="md:hidden border-t border-gray-200">
        <div class="px-2 pt-2 pb-3 space-y-1">
          <router-link
            v-for="item in navigation"
            :key="item.name"
            :to="item.href"
            :class="[
              'block px-3 py-2 text-base font-medium rounded-lg transition-colors duration-200',
              $route.path === item.href
                ? 'bg-primary-50 text-primary-700'
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
            ]"
            @click="showMobileMenu = false"
          >
            <div class="flex items-center">
              <component :is="item.icon" class="w-5 h-5 mr-3" />
              {{ item.name }}
            </div>
          </router-link>
        </div>
      </div>
    </nav>
    
    <!-- Main Content -->
    <main class="flex-1">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon,
  ChartBarIcon,
  ClipboardDocumentListIcon,
  CheckCircleIcon,
  BellIcon,
  ChevronDownIcon,
  Bars3Icon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()

const showUserMenu = ref(false)
const showMobileMenu = ref(false)

const navigation = [
  { name: 'ダッシュボード', href: '/dashboard', icon: HomeIcon },
  { name: '目標', href: '/goals', icon: ChartBarIcon },
  { name: 'タスク', href: '/tasks', icon: ClipboardDocumentListIcon },
  { name: '統計', href: '/statistics', icon: CheckCircleIcon },
]

const userInitials = computed(() => {
  if (!authStore.user?.name) return 'U'
  return authStore.user.name
    .split(' ')
    .map(name => name.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

const handleLogout = async () => {
  try {
    await authStore.logout()
    router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
  }
}

// Close dropdowns when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement
  if (!target.closest('.relative')) {
    showUserMenu.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>