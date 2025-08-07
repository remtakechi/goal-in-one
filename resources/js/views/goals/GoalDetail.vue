<template>
  <AppLayout>
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
      <p class="text-gray-600 mt-4">読み込み中...</p>
    </div>

    <div v-else-if="goal" class="space-y-8">
      <!-- Header -->
      <div class="flex justify-between items-start">
        <div class="flex-1">
          <div class="flex items-center space-x-4 mb-4">
            <router-link
              to="/goals"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors duration-200"
            >
              <ArrowLeftIcon class="w-5 h-5" />
            </router-link>
            <h1 class="text-3xl font-bold text-gray-900">{{ goal.title }}</h1>
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
          </div>
          <p v-if="goal.description" class="text-gray-600">{{ goal.description }}</p>
        </div>
        
        <div class="flex space-x-3">
          <BaseButton variant="secondary" @click="editGoal">
            <PencilIcon class="w-4 h-4 mr-2" />
            編集
          </BaseButton>
          <BaseButton
            v-if="goal.status !== 'completed'"
            @click="markAsCompleted"
            :loading="updating"
          >
            <CheckCircleIcon class="w-4 h-4 mr-2" />
            完了にする
          </BaseButton>
        </div>
      </div>

      <!-- Progress Overview -->
      <BaseCard title="進捗概要">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="text-3xl font-bold text-primary-600 mb-2">{{ goal.progress_percentage }}%</div>
            <p class="text-sm text-gray-600">全体の進捗</p>
            <ProgressBar :value="goal.progress_percentage" class="mt-3" />
          </div>
          
          <div class="text-center">
            <div class="text-3xl font-bold text-success-600 mb-2">{{ completedTasksCount }}</div>
            <p class="text-sm text-gray-600">完了タスク</p>
          </div>
          
          <div class="text-center">
            <div class="text-3xl font-bold text-secondary-600 mb-2">{{ totalTasksCount }}</div>
            <p class="text-sm text-gray-600">総タスク数</p>
          </div>
        </div>

        <div v-if="goal.target_date" class="mt-6 p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <CalendarIcon class="w-5 h-5 text-gray-400 mr-2" />
              <span class="text-sm text-gray-600">目標達成日</span>
            </div>
            <span class="text-sm font-medium text-gray-900">
              {{ formatDate(goal.target_date) }}
            </span>
          </div>
        </div>
      </BaseCard>

      <!-- Tasks Section -->
      <BaseCard>
        <template #title>
          <div class="flex justify-between items-center">
            <span>関連タスク</span>
            <BaseButton size="sm" @click="showCreateTaskModal = true">
              <PlusIcon class="w-4 h-4 mr-2" />
              タスク追加
            </BaseButton>
          </div>
        </template>

        <div v-if="tasks.length === 0" class="text-center py-12">
          <ClipboardDocumentListIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 mb-2">タスクがありません</h3>
          <p class="text-gray-600 mb-6">この目標に関連するタスクを作成しましょう</p>
          <BaseButton @click="showCreateTaskModal = true">
            <PlusIcon class="w-5 h-5 mr-2" />
            最初のタスクを作成
          </BaseButton>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="task in tasks"
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
                  <h4 class="font-medium text-gray-900">{{ task.title }}</h4>
                  <p v-if="task.description" class="text-sm text-gray-600 mt-1">{{ task.description }}</p>
                  
                  <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                    <span
                      :class="[
                        'inline-flex items-center px-2 py-1 rounded-full',
                        task.type === 'simple' ? 'bg-blue-100 text-blue-800' :
                        task.type === 'recurring' ? 'bg-purple-100 text-purple-800' :
                        'bg-orange-100 text-orange-800'
                      ]"
                    >
                      {{ getTaskTypeText(task.type) }}
                    </span>
                    
                    <span v-if="task.due_date" class="flex items-center">
                      <ClockIcon class="w-3 h-3 mr-1" />
                      {{ formatDate(task.due_date) }}
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

    <div v-else class="text-center py-12">
      <ExclamationTriangleIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">目標が見つかりません</h3>
      <p class="text-gray-600 mb-6">指定された目標は存在しないか、削除された可能性があります</p>
      <router-link to="/goals">
        <BaseButton>目標一覧に戻る</BaseButton>
      </router-link>
    </div>

    <!-- Create Task Modal -->
    <div
      v-if="showCreateTaskModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
      @click="closeTaskModal"
    >
      <div
        class="bg-white rounded-lg shadow-xl max-w-md w-full p-6"
        @click.stop
      >
        <h2 class="text-xl font-bold text-gray-900 mb-4">新しいタスク</h2>
        
        <form @submit.prevent="createTask" class="space-y-4">
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
            <input
              v-model="taskForm.due_date"
              type="date"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <BaseButton
              type="button"
              variant="secondary"
              @click="closeTaskModal"
            >
              キャンセル
            </BaseButton>
            <BaseButton
              type="submit"
              :loading="creatingTask"
            >
              作成
            </BaseButton>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useGoalsStore } from '../../stores/goals'
import { useTasksStore } from '../../stores/tasks'
import type { Goal, Task } from '../../types/index'
import AppLayout from '../../components/layout/AppLayout.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseInput from '../../components/ui/BaseInput.vue'
import ProgressBar from '../../components/ui/ProgressBar.vue'
import {
  ArrowLeftIcon,
  PencilIcon,
  CheckCircleIcon,
  CalendarIcon,
  PlusIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  TrashIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const route = useRoute()
const goalsStore = useGoalsStore()
const tasksStore = useTasksStore()

// State
const loading = ref(true)
const updating = ref(false)
const creatingTask = ref(false)
const goal = ref<Goal | null>(null)
const tasks = ref<Task[]>([])
const showCreateTaskModal = ref(false)

// Task form
const taskForm = ref<{
  title: string
  description: string
  type: 'simple' | 'recurring' | 'deadline'
  due_date: string
}>({
  title: '',
  description: '',
  type: 'simple',
  due_date: ''
})

// Computed
const completedTasksCount = computed(() => 
  tasks.value.filter(task => task.status === 'completed').length
)

const totalTasksCount = computed(() => tasks.value.length)

// Methods
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

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const fetchGoalData = async () => {
  try {
    loading.value = true
    const goalUuid = route.params.uuid as string
    
    // Fetch goal details
    goal.value = await goalsStore.getGoal(goalUuid)
    
    // Fetch related tasks
    tasks.value = await tasksStore.getTasksByGoal(goalUuid)
  } catch (error) {
    console.error('Failed to fetch goal data:', error)
    goal.value = null
  } finally {
    loading.value = false
  }
}

const markAsCompleted = async () => {
  if (!goal.value) return
  
  try {
    updating.value = true
    await goalsStore.updateGoal(goal.value.uuid, { status: 'completed' })
    goal.value.status = 'completed'
  } catch (error) {
    console.error('Failed to mark goal as completed:', error)
  } finally {
    updating.value = false
  }
}

const editGoal = () => {
  // Navigate to edit mode or show edit modal
  console.log('Edit goal functionality to be implemented')
}

const toggleTaskCompletion = async (task: any) => {
  try {
    const newStatus = task.status === 'completed' ? 'pending' : 'completed'
    await tasksStore.updateTask(task.uuid, { status: newStatus })
    task.status = newStatus
    
    // Refresh goal progress
    await fetchGoalData()
  } catch (error) {
    console.error('Failed to toggle task completion:', error)
  }
}

const createTask = async () => {
  if (!goal.value) return
  
  try {
    creatingTask.value = true
    const taskData = {
      ...taskForm.value,
      goal_uuid: goal.value.uuid
    }
    
    await tasksStore.createTask(taskData)
    await fetchGoalData() // Refresh data
    closeTaskModal()
  } catch (error) {
    console.error('Failed to create task:', error)
  } finally {
    creatingTask.value = false
  }
}

const editTask = (task: any) => {
  console.log('Edit task functionality to be implemented', task)
}

const deleteTask = async (task: any) => {
  if (confirm(`「${task.title}」を削除してもよろしいですか？`)) {
    try {
      await tasksStore.deleteTask(task.uuid)
      await fetchGoalData() // Refresh data
    } catch (error) {
      console.error('Failed to delete task:', error)
    }
  }
}

const closeTaskModal = () => {
  showCreateTaskModal.value = false
  taskForm.value = {
    title: '',
    description: '',
    type: 'simple',
    due_date: ''
  }
}

onMounted(() => {
  fetchGoalData()
})
</script>