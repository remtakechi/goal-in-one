<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">統計ダッシュボード</h1>
        <p class="text-gray-600">詳細な進捗分析と生産性指標</p>
      </div>

      <!-- Key Metrics -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatisticsCard
          title="生産性スコア"
          subtitle="総合評価"
          :value="productivityScore"
          :icon="ChartBarIcon"
          variant="primary"
          :loading="loading"
          :show-progress="true"
          :progress-value="productivityScore"
          :animated="true"
          :trend="productivityTrend"
          format="number"
        />

        <StatisticsCard
          title="目標達成率"
          subtitle="完了した目標"
          :value="goalCompletionRate"
          :icon="CheckCircleIcon"
          variant="success"
          :loading="loading"
          :show-progress="true"
          :progress-value="goalCompletionRate"
          progress-variant="success"
          :animated="true"
          :trend="goalTrend"
          format="percentage"
        />

        <StatisticsCard
          class="fade-in-up stagger-2 hover-lift"
          title="タスク完了率"
          subtitle="完了したタスク"
          :value="taskCompletionRate"
          :icon="ClipboardDocumentListIcon"
          variant="secondary"
          :loading="loading"
          :show-progress="true"
          :progress-value="taskCompletionRate"
          progress-variant="secondary"
          :animated="true"
          :trend="taskTrend"
          format="percentage"
        />

        <StatisticsCard
          class="fade-in-up stagger-4 hover-lift"
          title="連続達成日数"
          subtitle="継続記録"
          :value="streakDays"
          :icon="FireIcon"
          variant="warning"
          :loading="loading"
          :trend="streakTrend"
          format="number"
        />
      </div>

      <!-- Detailed Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Progress Trend Chart -->
        <ProgressChart
          title="進捗トレンド"
          :chart-data="progressTrendData"
          :loading="loading"
          type="progress"
        />

        <!-- Goal vs Task Completion -->
        <ProgressChart
          title="目標・タスク完了比較"
          :chart-data="completionComparisonData"
          :loading="loading"
          type="completion"
        />
      </div>

      <!-- Performance Analysis -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Task Type Distribution -->
        <BaseCard title="タスクタイプ分布" class="lg:col-span-1">
          <div v-if="loading" class="flex items-center justify-center h-48">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
          </div>
          <div v-else class="h-48">
            <Doughnut :data="taskTypeDistributionData" :options="doughnutOptions" />
          </div>
        </BaseCard>

        <!-- Weekly Performance -->
        <BaseCard title="週間パフォーマンス" class="lg:col-span-2">
          <div v-if="loading" class="flex items-center justify-center h-48">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
          </div>
          <div v-else class="h-48">
            <Bar :data="weeklyPerformanceData" :options="barOptions" />
          </div>
        </BaseCard>
      </div>

      <!-- Achievement Timeline -->
      <BaseCard title="達成タイムライン">
        <div v-if="loading" class="flex items-center justify-center h-32">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>
        <div v-else-if="achievements.length === 0" class="text-center py-8 text-gray-500">
          <TrophyIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
          <p>まだ達成記録がありません</p>
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="achievement in achievements"
            :key="achievement.id"
            class="flex items-center space-x-4 p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border border-yellow-200"
          >
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <TrophyIcon class="w-5 h-5 text-yellow-600" />
              </div>
            </div>
            <div class="flex-1">
              <h4 class="font-medium text-gray-900">{{ achievement.title }}</h4>
              <p class="text-sm text-gray-600">{{ achievement.description }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">{{ formatDate(achievement.achieved_at) }}</p>
              <p class="text-xs text-gray-500">{{ achievement.type }}</p>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import AppLayout from '@/components/layout/AppLayout.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import StatisticsCard from '@/components/charts/StatisticsCard.vue'
import ProgressChart from '@/components/charts/ProgressChart.vue'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  ChartBarIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  FireIcon,
  TrophyIcon
} from '@heroicons/vue/24/outline'

const dashboardStore = useDashboardStore()
const loading = ref(true)

// Mock achievements data
const achievements = ref([
  {
    id: 1,
    title: '初回目標達成',
    description: '最初の目標を完了しました',
    type: '目標達成',
    achieved_at: '2024-01-15'
  },
  {
    id: 2,
    title: '7日連続タスク完了',
    description: '1週間連続でタスクを完了しました',
    type: '継続記録',
    achieved_at: '2024-01-20'
  }
])

// Computed statistics
const productivityScore = computed(() => {
  if (!dashboardStore.stats) return 0
  const goalScore = dashboardStore.stats.goals.completion_rate * 0.4
  const taskScore = dashboardStore.stats.tasks.completion_rate * 0.6
  return Math.round(goalScore + taskScore)
})

const goalCompletionRate = computed(() => 
  dashboardStore.stats?.goals.completion_rate || 0
)

const taskCompletionRate = computed(() => 
  dashboardStore.stats?.tasks.completion_rate || 0
)

const streakDays = computed(() => 7) // Mock data

// Mock trend data
const productivityTrend = computed(() => 12)
const goalTrend = computed(() => 8)
const taskTrend = computed(() => -3)
const streakTrend = computed(() => 15)

// Chart data
const progressTrendData = computed(() => ({
  week: {
    labels: ['月', '火', '水', '木', '金', '土', '日'],
    datasets: [
      {
        label: '完了タスク数',
        data: [3, 5, 2, 8, 6, 4, 7],
        borderColor: '#2563EB',
        backgroundColor: '#3B82F680',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#2563EB',
        pointBorderColor: '#FFFFFF',
        pointBorderWidth: 2,
        pointRadius: 5
      }
    ]
  },
  month: {
    labels: ['第1週', '第2週', '第3週', '第4週'],
    datasets: [
      {
        label: '完了タスク数',
        data: [25, 32, 28, 35],
        borderColor: '#2563EB',
        backgroundColor: '#3B82F680',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#2563EB',
        pointBorderColor: '#FFFFFF',
        pointBorderWidth: 2,
        pointRadius: 5
      }
    ]
  }
}))

const completionComparisonData = computed(() => ({
  week: {
    labels: ['月', '火', '水', '木', '金', '土', '日'],
    datasets: [
      {
        label: '目標完了',
        data: [1, 0, 1, 2, 1, 0, 1],
        borderColor: '#059669',
        backgroundColor: '#10B98160',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#059669',
        pointBorderColor: '#FFFFFF',
        pointBorderWidth: 2,
        pointRadius: 5
      },
      {
        label: 'タスク完了',
        data: [3, 5, 2, 8, 6, 4, 7],
        borderColor: '#2563EB',
        backgroundColor: '#3B82F660',
        borderWidth: 3,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: '#2563EB',
        pointBorderColor: '#FFFFFF',
        pointBorderWidth: 2,
        pointRadius: 5
      }
    ]
  }
}))

const taskTypeDistributionData = computed(() => ({
  labels: ['単純', '繰り返し', '期限付き'],
  datasets: [
    {
      data: [45, 30, 25],
      backgroundColor: [
        '#3B82F6',
        '#8B5CF6',
        '#F59E0B'
      ],
      borderWidth: 0
    }
  ]
}))

const weeklyPerformanceData = computed(() => ({
  labels: ['第1週', '第2週', '第3週', '第4週'],
  datasets: [
    {
      label: '生産性スコア',
      data: [75, 82, 78, 88],
      backgroundColor: '#3B82F6',
      borderRadius: 4
    }
  ]
}))

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        usePointStyle: true,
        padding: 20
      }
    }
  }
}

const barOptions = {
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
      max: 100
    }
  }
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(async () => {
  try {
    await dashboardStore.fetchDashboardStats()
  } catch (error) {
    console.error('Failed to fetch statistics:', error)
  } finally {
    loading.value = false
  }
})
</script>