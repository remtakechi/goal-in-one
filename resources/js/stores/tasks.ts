import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import type { Task } from '../types/index'

export const useTasksStore = defineStore('tasks', () => {
  // State
  const tasks = ref<Task[]>([])
  const currentTask = ref<Task | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const pendingTasks = computed(() => tasks.value.filter(task => task.status === 'pending'))
  const completedTasks = computed(() => tasks.value.filter(task => task.status === 'completed'))
  const overdueTasks = computed(() => tasks.value.filter(task => task.is_overdue))
  
  const tasksByType = computed(() => {
    return {
      simple: tasks.value.filter(task => task.type === 'simple'),
      recurring: tasks.value.filter(task => task.type === 'recurring'),
      deadline: tasks.value.filter(task => task.type === 'deadline')
    }
  })
  
  const totalTasks = computed(() => tasks.value.length)
  const completionRate = computed(() => {
    if (totalTasks.value === 0) return 0
    return Math.round((completedTasks.value.length / totalTasks.value) * 100)
  })

  // Actions
  const fetchTasks = async (goalUuid?: string): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      const url = goalUuid ? `/goals/${goalUuid}/tasks` : '/tasks'
      const response = await axios.get<{ tasks: Task[] }>(url)
      tasks.value = response.data.tasks
    } catch (err: any) {
      error.value = err.response?.data?.message || 'タスクの取得に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchTask = async (uuid: string): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get<{ task: Task }>(`/tasks/${uuid}`)
      currentTask.value = response.data.task
    } catch (err: any) {
      error.value = err.response?.data?.message || 'タスクの取得に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createTask = async (taskData: {
    title: string
    description?: string
    goal_uuid?: string
    type: Task['type']
    recurrence_type?: Task['recurrence_type']
    due_date?: string
    recurring_interval?: string
  }): Promise<Task> => {
    loading.value = true
    error.value = null
    
    try {
      // Convert recurring_interval to recurrence_type for API compatibility
      const apiData = { ...taskData }
      if (apiData.recurring_interval) {
        apiData.recurrence_type = apiData.recurring_interval as Task['recurrence_type']
        delete apiData.recurring_interval
      }
      
      const response = await axios.post<{ task: Task }>('/tasks', apiData)
      const newTask = response.data.task
      
      tasks.value.unshift(newTask)
      return newTask
    } catch (err: any) {
      error.value = err.response?.data?.message || 'タスクの作成に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateTask = async (
    uuid: string,
    taskData: {
      title?: string
      description?: string
      goal_uuid?: string
      type?: Task['type']
      recurrence_type?: Task['recurrence_type']
      due_date?: string
      recurring_interval?: string
      status?: Task['status']
    }
  ): Promise<Task> => {
    loading.value = true
    error.value = null
    
    try {
      // Convert recurring_interval to recurrence_type for API compatibility
      const apiData = { ...taskData }
      if (apiData.recurring_interval) {
        apiData.recurrence_type = apiData.recurring_interval as Task['recurrence_type']
        delete apiData.recurring_interval
      }
      
      const response = await axios.put<{ task: Task }>(`/tasks/${uuid}`, apiData)
      const updatedTask = response.data.task
      
      // Update in tasks array
      const index = tasks.value.findIndex(task => task.uuid === uuid)
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }
      
      // Update current task if it's the same
      if (currentTask.value?.uuid === uuid) {
        currentTask.value = updatedTask
      }
      
      return updatedTask
    } catch (err: any) {
      error.value = err.response?.data?.message || 'タスクの更新に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteTask = async (uuid: string): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      await axios.delete(`/tasks/${uuid}`)
      
      // Remove from tasks array
      tasks.value = tasks.value.filter(task => task.uuid !== uuid)
      
      // Clear current task if it's the same
      if (currentTask.value?.uuid === uuid) {
        currentTask.value = null
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || 'タスクの削除に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const completeTask = async (uuid: string, notes?: string): Promise<Task> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post<{ task: Task }>(`/tasks/${uuid}/complete`, { notes })
      const completedTask = response.data.task
      
      // Update in tasks array
      const index = tasks.value.findIndex(task => task.uuid === uuid)
      if (index !== -1) {
        tasks.value[index] = completedTask
      }
      
      // Update current task if it's the same
      if (currentTask.value?.uuid === uuid) {
        currentTask.value = completedTask
      }
      
      return completedTask
    } catch (err: any) {
      error.value = err.response?.data?.message || 'タスクの完了に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getUpcomingTasks = (limit: number = 5): Task[] => {
    return tasks.value
      .filter(task => task.status === 'pending' && task.due_date)
      .sort((a, b) => new Date(a.due_date!).getTime() - new Date(b.due_date!).getTime())
      .slice(0, limit)
  }

  const getTasksByStatus = (status: Task['status']): Task[] => {
    return tasks.value.filter(task => task.status === status)
  }

  const getTasksByType = (type: Task['type']): Task[] => {
    return tasks.value.filter(task => task.type === type)
  }

  const clearError = () => {
    error.value = null
  }

  const clearCurrentTask = () => {
    currentTask.value = null
  }

  const clearTasks = () => {
    tasks.value = []
  }

  // Helper method for GoalDetail component
  const getTasksByGoal = async (goalUuid: string): Promise<Task[]> => {
    await fetchTasks(goalUuid)
    return tasks.value
  }

  return {
    // State
    tasks,
    currentTask,
    loading,
    error,
    
    // Getters
    pendingTasks,
    completedTasks,
    overdueTasks,
    tasksByType,
    totalTasks,
    completionRate,
    
    // Actions
    fetchTasks,
    fetchTask,
    createTask,
    updateTask,
    deleteTask,
    completeTask,
    getUpcomingTasks,
    getTasksByStatus,
    getTasksByType,
    getTasksByGoal,
    clearError,
    clearCurrentTask,
    clearTasks
  }
})