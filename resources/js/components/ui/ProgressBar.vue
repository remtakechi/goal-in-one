<template>
  <div class="w-full">
    <div v-if="showLabel" class="flex justify-between items-center mb-2">
      <span class="text-sm font-medium text-gray-700">{{ label }}</span>
      <span class="text-sm text-gray-500">{{ displayValue }}</span>
    </div>
    
    <div :class="containerClasses">
      <div
        :class="barClasses"
        :style="{ width: `${percentage}%` }"
        role="progressbar"
        :aria-valuenow="value"
        :aria-valuemin="min"
        :aria-valuemax="max"
        :aria-label="label || 'Progress'"
      >
        <div v-if="animated" class="progress-bar-stripes"></div>
      </div>
    </div>
    
    <div v-if="description" class="mt-1 text-xs text-gray-500">
      {{ description }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  value: number
  max?: number
  min?: number
  label?: string
  description?: string
  variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'danger'
  size?: 'xs' | 'sm' | 'md' | 'lg'
  showLabel?: boolean
  showPercentage?: boolean
  animated?: boolean
  rounded?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  max: 100,
  min: 0,
  variant: 'primary',
  size: 'md',
  showLabel: true,
  showPercentage: true,
  animated: false,
  rounded: true
})

const percentage = computed(() => {
  const range = props.max - props.min
  const adjustedValue = Math.max(props.min, Math.min(props.max, props.value))
  return ((adjustedValue - props.min) / range) * 100
})

const displayValue = computed(() => {
  if (props.showPercentage) {
    return `${Math.round(percentage.value)}%`
  }
  return `${props.value}/${props.max}`
})

const containerClasses = computed(() => {
  const baseClasses = ['w-full bg-gray-200 overflow-hidden']
  
  // Size classes
  const sizeClasses = {
    xs: 'h-1',
    sm: 'h-2',
    md: 'h-3',
    lg: 'h-4'
  }
  
  // Rounded classes
  const roundedClasses = props.rounded ? 'rounded-full' : 'rounded-none'
  
  return [
    ...baseClasses,
    sizeClasses[props.size],
    roundedClasses
  ].join(' ')
})

const barClasses = computed(() => {
  const baseClasses = ['h-full transition-all duration-1000 ease-out relative']
  
  // Variant classes
  const variantClasses = {
    primary: 'bg-primary-600',
    secondary: 'bg-secondary-600',
    success: 'bg-success-600',
    warning: 'bg-warning-600',
    danger: 'bg-danger-600'
  }
  
  // Rounded classes
  const roundedClasses = props.rounded ? 'rounded-full' : 'rounded-none'
  
  return [
    ...baseClasses,
    variantClasses[props.variant],
    roundedClasses
  ].join(' ')
})
</script>

<style scoped>
.progress-bar-stripes {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  background-image: linear-gradient(
    45deg,
    rgba(255, 255, 255, 0.15) 25%,
    transparent 25%,
    transparent 50%,
    rgba(255, 255, 255, 0.15) 50%,
    rgba(255, 255, 255, 0.15) 75%,
    transparent 75%,
    transparent
  );
  background-size: 1rem 1rem;
  animation: progress-bar-stripes 1s linear infinite;
}

@keyframes progress-bar-stripes {
  0% {
    background-position: 1rem 0;
  }
  100% {
    background-position: 0 0;
  }
}

/* Progress bar fill animation */
@keyframes progress-fill {
  0% {
    width: 0%;
  }
}

.progress-bar-animated {
  animation: progress-fill 1.5s ease-out;
}
</style>