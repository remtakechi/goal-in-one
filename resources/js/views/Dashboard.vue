<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Welcome Header -->
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          おかえりなさい、{{ authStore.user?.name }}さん！
        </h1>
        <p class="text-gray-600">今日も目標に向かって頑張りましょう</p>
      </div>

      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Goals -->
        <BaseCard class="text-center fade-in-up stagger-1 hover-lift">
          <div class="flex items-center justify-center w-12 h-12 bg-primary-100 rounded-lg mx-auto mb-4">
            <ChartBarIcon class="w-6 h-6 text-primary-600" />
          </div>
          <h3 class="text-2xl font-bold text-gray-900">{{ dashboardStore.stats?.goals.total || 0 }}</h3>
          <p class="text-sm text-gray-600">総目標数</p>
          <div class="mt-2">
            <span class="text-xs text-green-600 font-medium">
              完了率 {{ dashboardStore.stats?.goals.completion_rate || 0 }}%
            </span>
          </div>
        </BaseCard>

        <!-- Active Goals -->
        <BaseCard class="text-center fade-in-up stagger-2 hover-lift">
          <div class="flex items-center justify-center w-12 h-12 bg-secondary-100 rounded-lg mx-auto mb-4">
            <PlayIcon class="w-6 h-6 text-secondary-600" />
          </div>
          <h3 class="text-2xl font-bold text-gray-900">{{ dashboardStore.stats?.goals.active || 0 }}</h3>
          <p class="text-sm text-gray-600">進行中の目標</p>
        </BaseCard>

        <!-- Total Tasks -->
        <BaseCard class="text-center fade-in-up stagger-3 hover-lift">
          <div class="flex items-center justify-center w-12 h-12 bg-success-100 rounded-lg mx-auto mb-4">
            <ClipboardDocumentListIcon class="w-6 h-6 text-success-600" />
          </div>
          <h3 class="text-2xl font-bold text-gray-900">{{ dashboardStore.stats?.tasks.total || 0 }}</h3>
          <p class="text-sm text-gray-600">総タスク数</p>
          <div class="mt-2">
            <span class="text-xs text-green-600 font-medium">
              完了率 {{ dashboardStore.stats?.tasks.completion_rate || 0 }}%
            </span>
          </div>
        </BaseCard>

        <!-- Overdue Tasks -->
        <BaseCard class="text-center fade-in-up stagger-4 hover-lift">
          <div class="flex items-center justify-center w-12 h-12 bg-warning-100 rounded-lg mx-auto mb-4">
            <ExclamationTriangleIcon class="w-6 h-6 text-warning-600" />
          </div>
          <h3 class="text-2xl font-bold text-gray-900">{{ dashboardStore.stats?.tasks.overdue || 0 }}</h3>
          <p class="text-sm text-gray-600">期限切れタスク</p>
        </BaseCard>
      </div>

      <!-- Progress Overview -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Weekly Progress Chart -->
        <BaseCard title="今週の進捗" class="fade-in-left hover-lift">
          <div v-if="weeklyChartData" class="h-64">
            <Line :data="weeklyChartData" :options="chartOptions" />
          </div>
          <div v-else class="h-64 flex items-center justify-center text-gray-500">
            <div class="text-center">
              <ChartBarIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
              <p>データを読み込み中...</p>
            </div>
          </div>
        </BaseCard>

        <!-- Task Type Distribution -->
        <BaseCard title="タスクタイプ分布" class="fade-in-right hover-lift">
          <div v-if="taskTypeChartData" class="h-64">
            <Doughnut :data="taskTypeChartData" :options="doughnutOptions" />
          </div>
          <div v-else class="h-64 flex items-center justify-center text-gray-500">
            <div class="text-center">
              <ChartPieIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
              <p>データを読み込み中...</p>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- Recent Goals and Upcoming Tasks -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Goals -->
        <BaseCard title="最近の目標" class="fade-in-up hover-lift">
          <div v-if="dashboardStore.recentGoals.length > 0" class="space-y-4">
            <div
              v-for="goal in dashboardStore.recentGoals"
              :key="goal.uuid"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200 cursor-pointer"
              @click="$router.push(`/goals/${goal.uuid}`)"
            >
              <div class="flex-1">
                <h4 class="font-medium text-gray-900">{{ goal.title }}</h4>
                <div class="flex items-center mt-2">
                  <ProgressBar
                    :value="goal.progress_percentage"
                    size="sm"
                    class="flex-1 mr-3"
                  />
                  <span class="text-sm text-gray-600">{{ goal.progress_percentage }}%</span>
                </div>
              </div>
              <div class="ml-4">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    goal.status === 'completed' ? 'bg-green-100 text-green-800' :
                    goal.status === 'active' ? 'bg-blue-100 text-blue-800' :
                    'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ getStatusText(goal.status) }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            <ChartBarIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
            <p>目標がありません</p>
            <router-link
              to="/goals"
              class="text-primary-600 hover:text-primary-500 text-sm font-medium mt-2 inline-block"
            >
              最初の目標を作成する
            </router-link>
          </div>
        </BaseCard>

        <!-- Upcoming Tasks -->
        <BaseCard title="今後のタスク" class="fade-in-up hover-lift">
          <div v-if="dashboardStore.upcomingTasks.length > 0" class="space-y-4">
            <div
              v-for="task in dashboardStore.upcomingTasks"
              :key="task.uuid"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200"
            >
              <div class="flex-1">
                <h4 class="font-medium text-gray-900">{{ task.title }}</h4>
                <p class="text-sm text-gray-600 mt-1">{{ task.goal_title }}</p>
                <div class="flex items-center mt-2">
                  <CalendarIcon class="w-4 h-4 text-gray-400 mr-1" />
                  <span class="text-sm text-gray-600">
                    {{ formatDueDate(task.due_date) }}
                  </span>
                  <span
                    v-if="task.days_until_due !== null && task.days_until_due !== undefined"
                    :class="[
                      'ml-2 text-xs px-2 py-1 rounded-full',
                      (task.days_until_due ?? 0) < 0 ? 'bg-red-100 text-red-800' :
                      (task.days_until_due ?? 0) <= 1 ? 'bg-yellow-100 text-yellow-800' :
                      'bg-green-100 text-green-800'
                    ]"
                  >
                    {{ getDaysUntilDueText(task.days_until_due ?? 0) }}
                  </span>
                </div>
              </div>
              <div class="ml-4">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    task.type === 'simple' ? 'bg-blue-100 text-blue-800' :
                    task.type === 'recurring' ? 'bg-purple-100 text-purple-800' :
                    'bg-orange-100 text-orange-800'
                  ]"
                >
                  {{ getTaskTypeText(task.type) }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            <ClipboardDocumentListIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
            <p>今後のタスクがありません</p>
            <router-link
              to="/tasks"
              class="text-primary-600 hover:text-primary-500 text-sm font-medium mt-2 inline-block"
            >
              タスクを作成する
            </router-link>
          </div>
        </BaseCard>
      </div>

      <!-- Quick Actions -->
      <BaseCard title="クイックアクション" class="fade-in-up">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <router-link
            to="/goals"
            class="flex items-center p-4 bg-primary-50 rounded-lg hover:bg-primary-100 transition-all duration-200 group hover-scale fade-in-up stagger-1"
          >
            <div class="flex items-center justify-center w-10 h-10 bg-primary-600 rounded-lg mr-3 group-hover:bg-primary-700 transition-colors duration-200">
              <PlusIcon class="w-5 h-5 text-white" />
            </div>
            <div>
              <h4 class="font-medium text-gray-900">新しい目標</h4>
              <p class="text-sm text-gray-600">目標を作成</p>
            </div>
          </router-link>

          <router-link
            to="/tasks"
            class="flex items-center p-4 bg-secondary-50 rounded-lg hover:bg-secondary-100 transition-all duration-200 group hover-scale fade-in-up stagger-2"
          >
            <div class="flex items-center justify-center w-10 h-10 bg-secondary-600 rounded-lg mr-3 group-hover:bg-secondary-700 transition-colors duration-200">
              <PlusIcon class="w-5 h-5 text-white" />
            </div>
            <div>
              <h4 class="font-medium text-gray-900">新しいタスク</h4>
              <p class="text-sm text-gray-600">タスクを作成</p>
            </div>
          </router-link>

          <router-link
            to="/statistics"
            class="flex items-center p-4 bg-success-50 rounded-lg hover:bg-success-100 transition-all duration-200 group hover-scale fade-in-up stagger-3"
          >
            <div class="flex items-center justify-center w-10 h-10 bg-success-600 rounded-lg mr-3 group-hover:bg-success-700 transition-colors duration-200">
              <ChartBarIcon class="w-5 h-5 text-white" />
            </div>
            <div>
              <h4 class="font-medium text-gray-900">進捗確認</h4>
              <p class="text-sm text-gray-600">目標の進捗</p>
            </div>
          </router-link>

          <router-link
            to="/profile"
            class="flex items-center p-4 bg-warning-50 rounded-lg hover:bg-warning-100 transition-all duration-200 group hover-scale fade-in-up stagger-4"
          >
            <div class="flex items-center justify-center w-10 h-10 bg-warning-600 rounded-lg mr-3 group-hover:bg-warning-700 transition-colors duration-200">
              <UserIcon class="w-5 h-5 text-white" />
            </div>
            <div>
              <h4 class="font-medium text-gray-900">プロフィール</h4>
              <p class="text-sm text-gray-600">設定変更</p>
            </div>
          </router-link>
        </div>
      </BaseCard>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useDashboardStore } from '../stores/dashboard'
import AppLayout from '../components/layout/AppLayout.vue'
import BaseCard from '../components/ui/BaseCard.vue'
import ProgressBar from '../components/ui/ProgressBar.vue'
import {
  ChartBarIcon,
  ClipboardDocumentListIcon,
  ExclamationTriangleIcon,
  PlayIcon,
  CalendarIcon,
  PlusIcon,
  UserIcon,
  ChartPieIcon
} from '@heroicons/vue/24/outline'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  Filler
} from 'chart.js'
import { Line, Doughnut } from 'vue-chartjs'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  Filler
)

const authStore = useAuthStore()
const dashboardStore = useDashboardStore()

const loading = ref(true)

// Chart data
const weeklyChartData = computed(() => {
  if (!dashboardStore.weeklyProgress.length) return null

  return {
    labels: dashboardStore.weeklyProgress.map(day => day.day),
    datasets: [
      {
        label: '完了タスク数',
        data: dashboardStore.weeklyProgress.map(day => day.completed_tasks),
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
        fill: true
      }
    ]
  }
})

const taskTypeChartData = computed(() => {
  const distribution = dashboardStore.getTaskTypeDistribution()
  if (!distribution.length) return null

  return {
    labels: distribution.map(item => getTaskTypeText(item.type)),
    datasets: [
      {
        data: distribution.map(item => item.count),
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(147, 51, 234, 0.8)',
          'rgba(249, 115, 22, 0.8)',
          'rgba(34, 197, 94, 0.8)'
        ],
        borderColor: [
          'rgb(59, 130, 246)',
          'rgb(147, 51, 234)',
          'rgb(249, 115, 22)',
          'rgb(34, 197, 94)'
        ],
        borderWidth: 2
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1
      }
    }
  }
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const
    }
  }
}

// Helper functions
const getStatusText = (status: string): string => {
  switch (status) {
    case 'active': return '進行中'
    case 'completed': return '完了'
    case 'archived': return 'アーカイブ'
    default: return status
  }
}

const getTaskTypeText = (type: string): string => {
  switch (type) {
    case 'simple': return '単純'
    case 'recurring': return '繰り返し'
    case 'deadline': return '期限付き'
    default: return type
  }
}

const formatDueDate = (dateString?: string): string => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = date.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return '今日'
  if (diffDays === 1) return '明日'
  if (diffDays === -1) return '昨日'
  
  return date.toLocaleDateString('ja-JP', {
    month: 'short',
    day: 'numeric'
  })
}

const getDaysUntilDueText = (days: number): string => {
  if (days < 0) return `${Math.abs(days)}日遅れ`
  if (days === 0) return '今日'
  if (days === 1) return '明日'
  return `${days}日後`
}

onMounted(async () => {
  try {
    await dashboardStore.fetchDashboardStats()
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  } finally {
    loading.value = false
  }
})
</script>