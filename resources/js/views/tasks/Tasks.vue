<template>
  <AppLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">タスク管理</h1>
          <p class="text-gray-600 mt-2">すべてのタスクを一覧で管理できます</p>
        </div>
        <BaseButton @click="showCreateModal = true" class="flex items-center">
          <PlusIcon class="w-5 h-5 mr-2" />
          新しいタスク
        </BaseButton>
      </div>

      <!-- Filter Tabs -->
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in filterTabs"
            :key="tab.key"
            @click="activeFilter = tab.key"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
              activeFilter === tab.key
                ? 'border-primary-500 text-primary-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ tab.label }}
            <span
              v-if="tab.count !== undefined"
              :class="[
                'ml-2 py-0.5 px-2 rounded-full text-xs',
                activeFilter === tab.key
                  ? 'bg-primary-100 text-primary-600'
                  : 'bg-gray-100 text-gray-600'
              ]"
            >
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Tasks List -->
      <BaseCard>
        <div v-if="loading" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
          <p class="text-gray-600 mt-2">読み込み中...</p>
        </div>

        <div v-else-if="filteredTasks.length === 0" class="text-center py-12">
          <ClipboardDocumentListIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">
            {{ getEmptyStateTitle() }}
          </h3>
          <p class="text-gray-600 mb-6">
            {{ getEmptyStateDescription() }}
          </p>
          <BaseButton @click="showCreateModal = true" class="hover-scale">
            <PlusIcon class="w-5 h-5 mr-2" />
            最初のタスクを作成
          </BaseButton>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="task in filteredTasks"
            :key="task.uuid"
            class="p-4 border border-gray-200 rounded-lg hover:shadow-sm transition-shadow duration-200"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-start space-x-3 flex-1">
                <input
                  type="checkbox"
                  :checked="task.status === 'completed'"
                  @change="toggleTaskCompletion(task)"
                  class="mt-1 h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                />
                <div class="flex-1">
                  <div class="flex items-center space-x-2 mb-2">
                    <h4 class="font-medium text-gray-900">{{ task.title }}</h4>
                    <span
                      :class="[
                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                        task.type === 'simple' ? 'bg-blue-100 text-blue-800' :
                        task.type === 'recurring' ? 'bg-purple-100 text-purple-800' :
                        'bg-orange-100 text-orange-800'
                      ]"
                    >
                      {{ getTaskTypeText(task.type) }}
                    </span>
                  </div>
                  
                  <p v-if="task.description" class="text-sm text-gray-600 mb-2">{{ task.description }}</p>
                  
                  <div class="flex items-center space-x-4 text-xs text-gray-500">
                    <span v-if="task.goal_title" class="flex items-center">
                      <ChartBarIcon class="w-3 h-3 mr-1" />
                      {{ task.goal_title }}
                    </span>
                    
                    <span v-if="task.due_date" class="flex items-center">
                      <ClockIcon class="w-3 h-3 mr-1" />
                      {{ formatDate(task.due_date) }}
                      <span
                        v-if="task.days_until_due !== null && task.days_until_due !== undefined"
                        :class="[
                          'ml-1 px-1 py-0.5 rounded text-xs',
                          (task.days_until_due ?? 0) < 0 ? 'bg-red-100 text-red-800' :
                          (task.days_until_due ?? 0) <= 1 ? 'bg-yellow-100 text-yellow-800' :
                          'bg-green-100 text-green-800'
                        ]"
                      >
                        {{ getDaysUntilDueText(task.days_until_due ?? 0) }}
                      </span>
                    </span>
                    
                    <span class="flex items-center">
                      <CalendarIcon class="w-3 h-3 mr-1" />
                      作成: {{ formatDate(task.created_at) }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="flex space-x-2">
                <button
                  @click="editTask(task)"
                  class="p-1 text-gray-400 hover:text-gray-600 transition-colors duration-200"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button
                  @click="deleteTask(task)"
                  class="p-1 text-gray-400 hover:text-red-600 transition-colors duration-200"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Create/Edit Task Modal -->
    <div
      v-if="showCreateModal || showEditModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
      @click="closeModals"
    >
      <div
        class="bg-white rounded-lg shadow-xl max-w-md w-full p-6"
        @click.stop
      >
        <h2 class="text-xl font-bold text-gray-900 mb-4">
          {{ showCreateModal ? '新しいタスク' : 'タスクを編集' }}
        </h2>
        
        <form @submit.prevent="submitTask" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              タスクタイトル
            </label>
            <BaseInput
              v-model="taskForm.title"
              placeholder="タスクのタイトルを入力"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              説明（任意）
            </label>
            <textarea
              v-model="taskForm.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="タスクの詳細説明"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              関連する目標（任意）
            </label>
            <select
              v-model="taskForm.goal_uuid"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="">目標を選択してください</option>
              <option
                v-for="goal in availableGoals"
                :key="goal.uuid"
                :value="goal.uuid"
              >
                {{ goal.title }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              タスクタイプ
            </label>
            <select
              v-model="taskForm.type"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="simple">単純タスク</option>
              <option value="recurring">繰り返しタスク</option>
              <option value="deadline">期限付きタスク</option>
            </select>
          </div>

          <div v-if="taskForm.type === 'deadline'">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              期限日
            </label>
            <BaseInput
              v-model="taskForm.due_date"
              type="date"
              required
            />
          </div>

          <div v-if="taskForm.type === 'recurring'">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              繰り返し間隔
            </label>
            <select
              v-model="taskForm.recurring_interval"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="daily">毎日</option>
              <option value="weekly">毎週</option>
              <option value="monthly">毎月</option>
            </select>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <BaseButton
              type="button"
              variant="secondary"
              @click="closeModals"
            >
              キャンセル
            </BaseButton>
            <BaseButton
              type="submit"
              :loading="loading"
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
import { useTasksStore } from '../../stores/tasks'
import { useGoalsStore } from '../../stores/goals'
import AppLayout from '../../components/layout/AppLayout.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import {
  PlusIcon,
  ClipboardDocumentListIcon,
  ChartBarIcon,
  ClockIcon,
  CalendarIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

const tasksStore = useTasksStore()
const goalsStore = useGoalsStore()

// State
const loading = ref(false)
const activeFilter = ref('all')
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingTask = ref<any>(null)

// Form data
const taskForm = ref({
  title: '',
  description: '',
  goal_uuid: '',
  type: 'simple' as 'simple' | 'recurring' | 'deadline',
  due_date: '',
  recurring_interval: 'daily' as 'daily' | 'weekly' | 'monthly'
})

// Filter tabs
const filterTabs = computed(() => [
  {
    key: 'all',
    label: 'すべて',
    count: tasksStore.tasks.length
  },
  {
    key: 'pending',
    label: '未完了',
    count: tasksStore.tasks.filter(task => task.status === 'pending').length
  },
  {
    key: 'completed',
    label: '完了',
    count: tasksStore.tasks.filter(task => task.status === 'completed').length
  },
  {
    key: 'overdue',
    label: '期限切れ',
    count: tasksStore.tasks.filter(task => (task.days_until_due ?? 0) < 0).length
  }
])

// Computed properties
const filteredTasks = computed(() => {
  switch (activeFilter.value) {
    case 'pending':
      return tasksStore.tasks.filter(task => task.status === 'pending')
    case 'completed':
      return tasksStore.tasks.filter(task => task.status === 'completed')
    case 'overdue':
      return tasksStore.tasks.filter(task => (task.days_until_due ?? 0) < 0)
    default:
      return tasksStore.tasks
  }
})

const availableGoals = computed(() => 
  goalsStore.goals.filter(goal => goal.status === 'active')
)

// Methods
const getTaskTypeText = (type: string): string => {
  switch (type) {
    case 'simple': return '単純'
    case 'recurring': return '繰り返し'
    case 'deadline': return '期限付き'
    default: return type
  }
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('ja-JP', {
    year: 'numeric',
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

const getEmptyStateTitle = (): string => {
  switch (activeFilter.value) {
    case 'pending': return '未完了のタスクがありません'
    case 'completed': return '完了したタスクがありません'
    case 'overdue': return '期限切れのタスクがありません'
    default: return 'タスクがありません'
  }
}

const getEmptyStateDescription = (): string => {
  switch (activeFilter.value) {
    case 'pending': return 'すべてのタスクが完了しています！'
    case 'completed': return 'まだ完了したタスクがありません'
    case 'overdue': return '期限切れのタスクはありません'
    default: return '最初のタスクを作成して、目標達成に向けて行動を始めましょう'
  }
}

const toggleTaskCompletion = async (task: any) => {
  try {
    const newStatus = task.status === 'completed' ? 'pending' : 'completed'
    await tasksStore.updateTask(task.uuid, { status: newStatus })
  } catch (error) {
    console.error('Failed to toggle task completion:', error)
  }
}

const editTask = (task: any) => {
  editingTask.value = task
  taskForm.value = {
    title: task.title,
    description: task.description || '',
    goal_uuid: task.goal_uuid || '',
    type: task.type,
    due_date: task.due_date ? task.due_date.split('T')[0] : '',
    recurring_interval: task.recurring_interval || 'daily'
  }
  showEditModal.value = true
}

const deleteTask = async (task: any) => {
  if (confirm(`「${task.title}」を削除してもよろしいですか？`)) {
    try {
      await tasksStore.deleteTask(task.uuid)
    } catch (error) {
      console.error('Failed to delete task:', error)
    }
  }
}

const submitTask = async () => {
  try {
    loading.value = true
    
    if (showCreateModal.value) {
      await tasksStore.createTask(taskForm.value)
    } else if (showEditModal.value && editingTask.value) {
      await tasksStore.updateTask(editingTask.value.uuid, taskForm.value)
    }
    
    closeModals()
  } catch (error) {
    console.error('Failed to save task:', error)
  } finally {
    loading.value = false
  }
}

const closeModals = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingTask.value = null
  resetForm()
}

const resetForm = () => {
  taskForm.value = {
    title: '',
    description: '',
    goal_uuid: '',
    type: 'simple' as 'simple' | 'recurring' | 'deadline',
    due_date: '',
    recurring_interval: 'daily' as 'daily' | 'weekly' | 'monthly'
  }
}

onMounted(async () => {
  try {
    await Promise.all([
      tasksStore.fetchTasks(),
      goalsStore.fetchGoals()
    ])
  } catch (error) {
    console.error('Failed to fetch data:', error)
  }
})
</script>