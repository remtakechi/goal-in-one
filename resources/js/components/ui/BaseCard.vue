<template>
  <div :class="cardClasses">
    <div v-if="$slots.header || title" class="px-6 py-4 border-b border-gray-200">
      <slot name="header">
        <h3 v-if="title" class="text-lg font-medium text-gray-900">{{ title }}</h3>
      </slot>
    </div>
    
    <div :class="bodyClasses">
      <slot />
    </div>
    
    <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  title?: string
  variant?: 'default' | 'outlined' | 'elevated' | 'flat'
  padding?: 'none' | 'sm' | 'md' | 'lg'
  rounded?: boolean
  shadow?: 'none' | 'sm' | 'md' | 'lg' | 'xl'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  padding: 'md',
  rounded: true,
  shadow: 'sm'
})

const cardClasses = computed(() => {
  const baseClasses = ['bg-white overflow-hidden']

  // Variant classes
  const variantClasses = {
    default: 'border border-gray-200',
    outlined: 'border-2 border-gray-300',
    elevated: 'border-0',
    flat: 'border-0 shadow-none'
  }

  // Shadow classes
  const shadowClasses = {
    none: 'shadow-none',
    sm: 'shadow-sm',
    md: 'shadow-md',
    lg: 'shadow-lg',
    xl: 'shadow-xl'
  }

  // Rounded classes
  const roundedClasses = props.rounded ? 'rounded-xl' : 'rounded-none'

  return [
    ...baseClasses,
    variantClasses[props.variant],
    shadowClasses[props.shadow],
    roundedClasses
  ].filter(Boolean).join(' ')
})

const bodyClasses = computed(() => {
  const paddingClasses = {
    none: '',
    sm: 'p-4',
    md: 'p-6',
    lg: 'p-8'
  }

  return paddingClasses[props.padding]
})
</script>