<template>
  <BaseCard :title="title" class="relative overflow-hidden">
    <div class="flex items-center justify-between">
      <div class="flex-1">
        <div class="flex items-center space-x-3">
          <div :class="iconContainerClasses">
            <component :is="icon" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900" :class="{ 'animate-pulse': loading }">
              {{ loading ? '---' : displayValue }}
            </p>
            <p class="text-sm text-gray-600">{{ subtitle }}</p>
          </div>
        </div>
        
        <div v-if="showProgress && !loading" class="mt-4">
          <ProgressBar
            :value="progressValue"
            :variant="progressVariant"
            size="sm"
            :animated="animated"
            :show-label="false"
          />
        </div>
        
        <div v-if="trend !== undefined" class="mt-2 flex items-center space-x-1">
          <component 
            :is="trendIcon" 
            :class="trendClasses"
            class="w-4 h-4"
          />
          <span :class="trendClasses" class="text-sm font-medium">
            {{ Math.abs(trend) }}%
          </span>
          <span class="text-xs text-gray-500">前週比</span>
        </div>
      </div>
      
      <div v-if="chartData" class="ml-4">
        <div class="w-16 h-16">
          <Doughnut 
            :data="chartData" 
            :options="miniChartOptions"
            class="opacity-75"
          />
        </div>
      </div>
    </div>
    
    <!-- Decorative background -->
    <div class="absolute top-2 right-2 w-16 h-16 opacity-5 pointer-events-none">
      <component :is="icon" class="w-full h-full" />
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import ProgressBar from '@/components/ui/ProgressBar.vue'
import { Doughnut } from 'vue-chartjs'
import {
  ArrowUpIcon,
  ArrowDownIcon,
  MinusIcon
} from '@heroicons/vue/24/outline'

interface Props {
  title: string
  subtitle: string
  value: number | string
  icon: any
  variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger'
  loading?: boolean
  showProgress?: boolean
  progressValue?: number
  progressVariant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger'
  animated?: boolean
  trend?: number
  chartData?: any
  format?: 'number' | 'percentage' | 'currency' | 'custom'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  loading: false,
  showProgress: false,
  progressValue: 0,
  progressVariant: 'primary',
  animated: false,
  format: 'number'
})

const displayValue = computed(() => {
  if (typeof props.value === 'string') return props.value
  
  switch (props.format) {
    case 'percentage':
      return `${props.value}%`
    case 'currency':
      return `¥${props.value.toLocaleString()}`
    case 'number':
      return props.value.toLocaleString()
    default:
      return props.value
  }
})

const iconContainerClasses = computed(() => {
  const baseClasses = 'flex items-center justify-center w-12 h-12 rounded-lg'
  
  const variantClasses = {
    primary: 'bg-primary-100 text-primary-600',
    secondary: 'bg-secondary-100 text-secondary-600',
    success: 'bg-success-100 text-success-600',
    warning: 'bg-warning-100 text-warning-600',
    danger: 'bg-danger-100 text-danger-600'
  }
  
  return `${baseClasses} ${variantClasses[props.variant]}`
})

const trendIcon = computed(() => {
  if (props.trend === undefined) return MinusIcon
  if (props.trend > 0) return ArrowUpIcon
  if (props.trend < 0) return ArrowDownIcon
  return MinusIcon
})

const trendClasses = computed(() => {
  if (props.trend === undefined) return 'text-gray-500'
  if (props.trend > 0) return 'text-success-600'
  if (props.trend < 0) return 'text-danger-600'
  return 'text-gray-500'
})

const miniChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      enabled: false
    }
  },
  elements: {
    arc: {
      borderWidth: 0
    }
  },
  cutout: '70%'
}
</script>