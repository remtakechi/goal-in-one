<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex justify-between items-center fade-in-down">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">目標管理</h1>
          <p class="text-gray-600 mt-2">あなたの目標を管理し、進捗を追跡しましょう</p>
        </div>
        <BaseButton @click="showCreateModal = true" class="flex items-center hover-scale">
          <PlusIcon class="w-5 h-5 mr-2" />
          新しい目標
        </BaseButton>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <BaseCard class="text-center fade-in-up stagger-1 hover-lift">
          <div class="flex items-center justify-center w-12 h-12 bg-primary-100 rounded-lg mx-auto mb-4">
            <ChartBarIcon class="w-6 h-6 text-primary-600" />
          </div>
          <h3 class="text-2xl font-bold text-gray-900">{{ goalsStore.goals.length }}</h3>
          <p class="text-sm text-gray-600">総目標数</p>
        </BaseCard>

        <BaseCard class="text-center fade-in-up stagger-2 hover-lift">
          <div class="flex items-center justify-center w-12 h-12 bg-success-100 rounded-lg mx-auto mb-4">
            <CheckCircleIcon class="w-6 h-6 text-success-600" />
          </div>
          <h3 class="text-2xl font-bold text-gray-900">{{ completedGoalsCount }}</h3>
          <p class="text-sm text-gray-600">完了した目標</p>
        </BaseCard>

        <BaseCard class="text-center fade-in-up stagger-3 hover-lift">
          <div class="flex items-center justify-center w-12 h-12 bg-secondary-100 rounded-lg mx-auto mb-4">
            <PlayIcon class="w-6 h-6 text-secondary-600" />
          </div>
          <h3 class="text-2xl font-bold text-gray-900">{{ activeGoalsCount }}</h3>
          <p class="text-sm text-gray-600">進行中の目標</p>
        </BaseCard>
      </div>

      <!-- Goals List -->
      <BaseCard title="目標一覧" class="fade-in-up">
        <div v-if="goalsStore.loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
          <p class="text-gray-600 mt-2">読み込み中...</p>
        </div>

        <div v-else-if="goalsStore.goals.length === 0" class="text-center py-12">
          <ChartBarIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">目標がありません</h3>
          <p class="text-gray-600 mb-6">最初の目標を作成して、目標達成の旅を始めましょう！</p>
          <BaseButton @click="showCreateModal = true" class="hover-scale">
            <PlusIcon class="w-5 h-5 mr-2" />
            最初の目標を作成
          </BaseButton>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="(goal, index) in goalsStore.goals"
            :key="goal.uuid"
            class="p-6 border border-gray-200 rounded-lg hover:shadow-md transition-all duration-200 cursor-pointer fade-in-up hover-lift"
            :class="`stagger-${(index % 4) + 1}`"
            @click="$router.push(`/goals/${goal.uuid}`)"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ goal.title }}</h3>
                <p v-if="goal.description" class="text-gray-600 mb-4">{{ goal.description }}</p>
                
                <!-- Progress Bar -->
                <div class="mb-4">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">進捗</span>
                    <span class="text-sm font-medium text-gray-900">{{ goal.progress_percentage }}%</span>
                  </div>
                  <ProgressBar :value="goal.progress_percentage" />
                </div>

                <!-- Meta Info -->
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                  <div class="flex items-center">
                    <CalendarIcon class="w-4 h-4 mr-1" />
                    作成日: {{ formatDate(goal.created_at) }}
                  </div>
                  <div v-if="goal.target_date" class="flex items-center">
                    <ClockIcon class="w-4 h-4 mr-1" />
                    目標日: {{ formatDate(goal.target_date) }}
                  </div>
                </div>
              </div>

              <div class="ml-6 flex flex-col items-end space-y-2">
                <span
                  :class="[
                    'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                    goal.status === 'completed' ? 'bg-green-100 text-green-800' :
                    goal.status === 'active' ? 'bg-blue-100 text-blue-800' :
                    'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ getStatusText(goal.status) }}
                </span>
                
                <div class="flex space-x-2">
                  <button
                    @click.stop="editGoal(goal)"
                    class="p-2 text-gray-400 hover:text-gray-600 transition-all duration-200 hover-scale"
                  >
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button
                    @click.stop="deleteGoal(goal)"
                    class="p-2 text-gray-400 hover:text-red-600 transition-all duration-200 hover-scale"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Create/Edit Goal Modal -->
    <div
      v-if="showCreateModal || showEditModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50 modal-overlay"
      @click="closeModals"
    >
      <div
        class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 modal-content"
        @click.stop
      >
        <h2 class="text-xl font-bold text-gray-900 mb-4">
          {{ showCreateModal ? '新しい目標' : '目標を編集' }}
        </h2>
        
        <form @submit.prevent="submitGoal" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              目標タイトル
            </label>
            <BaseInput
              v-model="goalForm.title"
              placeholder="目標のタイトルを入力"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              説明（任意）
            </label>
            <textarea
              v-model="goalForm.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="目標の詳細説明"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              目標達成日（任意）
            </label>
            <BaseInput
              v-model="goalForm.target_date"
              type="date"
              placeholder=""
            />
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <BaseButton
              type="button"
              variant="secondary"
              @click="closeModals"
              class="hover-scale"
            >
              キャンセル
            </BaseButton>
            <BaseButton
              type="submit"
              :loading="goalsStore.loading"
              class="hover-scale"
            >
              {{ showCreateModal ? '作成' : '更新' }}
            </BaseButton>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useGoalsStore } from '../../stores/goals'
import AppLayout from '../../components/layout/AppLayout.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import ProgressBar from '../../components/ui/ProgressBar.vue'
import {
  PlusIcon,
  ChartBarIcon,
  CheckCircleIcon,
  PlayIcon,
  CalendarIcon,
  ClockIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

const goalsStore = useGoalsStore()

// Modal states
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingGoal = ref<any>(null)

// Form data
const goalForm = ref({
  title: '',
  description: '',
  target_date: ''
})

// Computed properties
const completedGoalsCount = computed(() => 
  goalsStore.goals.filter(goal => goal.status === 'completed').length
)

const activeGoalsCount = computed(() => 
  goalsStore.goals.filter(goal => goal.status === 'active').length
)

// Methods
const getStatusText = (status: string): string => {
  switch (status) {
    case 'active': return '進行中'
    case 'completed': return '完了'
    case 'archived': return 'アーカイブ'
    default: return status
  }
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingGoal.value = null
  resetForm()
}

const resetForm = () => {
  goalForm.value = {
    title: '',
    description: '',
    target_date: ''
  }
}

const editGoal = (goal: any) => {
  editingGoal.value = goal
  goalForm.value = {
    title: goal.title,
    description: goal.description || '',
    target_date: goal.target_date ? goal.target_date.split('T')[0] : ''
  }
  showEditModal.value = true
}

const submitGoal = async () => {
  try {
    if (showCreateModal.value) {
      await goalsStore.createGoal(goalForm.value)
    } else if (showEditModal.value && editingGoal.value) {
      await goalsStore.updateGoal(editingGoal.value.uuid, goalForm.value)
    }
    closeModals()
  } catch (error) {
    console.error('Failed to save goal:', error)
  }
}

const deleteGoal = async (goal: any) => {
  if (confirm(`「${goal.title}」を削除してもよろしいですか？`)) {
    try {
      await goalsStore.deleteGoal(goal.uuid)
    } catch (error) {
      console.error('Failed to delete goal:', error)
    }
  }
}

onMounted(async () => {
  try {
    await goalsStore.fetchGoals()
  } catch (error) {
    console.error('Failed to fetch goals:', error)
  }
})
</script>