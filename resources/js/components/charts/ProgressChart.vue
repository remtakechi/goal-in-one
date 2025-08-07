<template>
  <BaseCard :title="title" class="h-full">
    <div v-if="loading" class="flex items-center justify-center h-64">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>
    
    <div v-else-if="!chartData" class="flex items-center justify-center h-64 text-gray-500">
      <div class="text-center">
        <ChartBarIcon class="w-12 h-12 mx-auto mb-2 text-gray-300" />
        <p>データがありません</p>
      </div>
    </div>
    
    <div v-else>
      <!-- Chart Type Selector -->
      <div class="flex justify-between items-center mb-4">
        <div class="flex space-x-2">
          <button
            v-for="type in chartTypes"
            :key="type.key"
            @click="activeChartType = type.key"
            :class="[
              'px-3 py-1 text-sm font-medium rounded-md transition-colors duration-200',
              activeChartType === type.key
                ? 'bg-primary-100 text-primary-700'
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
            ]"
          >
            {{ type.label }}
          </button>
        </div>
        
        <div class="flex space-x-2">
          <button
            v-for="period in timePeriods"
            :key="period.key"
            @click="activePeriod = period.key"
            :class="[
              'px-3 py-1 text-xs font-medium rounded-md transition-colors duration-200',
              activePeriod === period.key
                ? 'bg-secondary-100 text-secondary-700'
                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]"
          >
            {{ period.label }}
          </button>
        </div>
      </div>
      
      <!-- Chart Container -->
      <div class="h-64 relative">
        <Line 
          v-if="activeChartType === 'line'"
          :data="currentChartData" 
          :options="lineChartOptions"
        />
        <Bar 
          v-else-if="activeChartType === 'bar'"
          :data="currentChartData" 
          :options="barChartOptions"
        />
        <Doughnut 
          v-else-if="activeChartType === 'doughnut'"
          :data="currentChartData" 
          :options="doughnutChartOptions"
        />
      </div>
      
      <!-- Chart Legend/Stats -->
      <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div 
          v-for="stat in chartStats"
          :key="stat.label"
          class="text-center"
        >
          <p class="text-lg font-semibold" :style="{ color: stat.color }">
            {{ stat.value }}
          </p>
          <p class="text-xs text-gray-600">{{ stat.label }}</p>
        </div>
      </div>
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { ChartBarIcon } from '@heroicons/vue/24/outline'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

interface Props {
  title: string
  chartData?: any
  loading?: boolean
  type?: 'progress' | 'completion' | 'distribution'
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  type: 'progress'
})

const activeChartType = ref('line')
const activePeriod = ref('week')

const chartTypes = [
  { key: 'line', label: 'ライン' },
  { key: 'bar', label: 'バー' },
  { key: 'doughnut', label: '円グラフ' }
]

const timePeriods = [
  { key: 'week', label: '週' },
  { key: 'month', label: '月' },
  { key: 'quarter', label: '四半期' },
  { key: 'year', label: '年' }
]

const currentChartData = computed(() => {
  if (!props.chartData) return null
  
  // Filter data based on active period
  const data = props.chartData[activePeriod.value] || props.chartData
  
  return {
    ...data,
    datasets: data.datasets?.map((dataset: any) => ({
      ...dataset,
      tension: activeChartType.value === 'line' ? 0.4 : 0,
      fill: activeChartType.value === 'line' ? true : false,
      backgroundColor: activeChartType.value === 'line' 
        ? `${dataset.borderColor}20` 
        : dataset.backgroundColor
    }))
  }
})

const chartStats = computed(() => {
  if (!props.chartData) return []
  
  const data = currentChartData.value
  if (!data?.datasets?.[0]?.data) return []
  
  const values = data.datasets[0].data
  const total = values.reduce((sum: number, val: number) => sum + val, 0)
  const average = total / values.length
  const max = Math.max(...values)
  const min = Math.min(...values)
  
  return [
    {
      label: '合計',
      value: total.toLocaleString(),
      color: '#3B82F6'
    },
    {
      label: '平均',
      value: Math.round(average).toLocaleString(),
      color: '#10B981'
    },
    {
      label: '最大',
      value: max.toLocaleString(),
      color: '#F59E0B'
    },
    {
      label: '最小',
      value: min.toLocaleString(),
      color: '#EF4444'
    }
  ]
})

const baseChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top' as const,
      labels: {
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      mode: 'index' as const,
      intersect: false,
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff',
      borderColor: '#374151',
      borderWidth: 1
    }
  },
  scales: {
    x: {
      grid: {
        display: false
      },
      ticks: {
        color: '#6B7280'
      }
    },
    y: {
      beginAtZero: true,
      grid: {
        color: '#F3F4F6'
      },
      ticks: {
        color: '#6B7280'
      }
    }
  }
}

const lineChartOptions = computed(() => ({
  ...baseChartOptions,
  elements: {
    point: {
      radius: 4,
      hoverRadius: 6
    }
  },
  interaction: {
    intersect: false,
    mode: 'index' as const
  }
}))

const barChartOptions = computed(() => ({
  ...baseChartOptions,
  plugins: {
    ...baseChartOptions.plugins,
    legend: {
      ...baseChartOptions.plugins.legend,
      display: false
    }
  }
}))

const doughnutChartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right' as const,
      labels: {
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      titleColor: '#fff',
      bodyColor: '#fff'
    }
  },
  cutout: '50%'
}))

// Watch for chart type changes to trigger animations
watch(activeChartType, () => {
  // Trigger chart re-render with animation
})
</script>