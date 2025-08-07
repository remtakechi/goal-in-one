import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'
import type { DashboardStats, Goal, Task, WeeklyProgress, MonthlyProgress } from '../types'

export const useDashboardStore = defineStore('dashboard', () => {
  // State
  const stats = ref<DashboardStats | null>(null)
  const recentGoals = ref<Goal[]>([])
  const upcomingTasks = ref<Task[]>([])
  const weeklyProgress = ref<WeeklyProgress[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Actions
  const fetchDashboardStats = async (): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get<{
        stats: DashboardStats
        recent_goals: Goal[]
        upcoming_tasks: Task[]
        weekly_progress: WeeklyProgress[]
      }>('/dashboard/stats')
      
      stats.value = response.data.stats
      recentGoals.value = response.data.recent_goals
      upcomingTasks.value = response.data.upcoming_tasks
      weeklyProgress.value = response.data.weekly_progress
    } catch (err: any) {
      error.value = err.response?.data?.message || 'ダッシュボードデータの取得に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchGoalProgress = async (goalUuid: string): Promise<{
    goal: Goal
    task_distribution: {
      by_status: Record<string, number>
      by_type: Record<string, number>
    }
    monthly_progress: MonthlyProgress[]
  }> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get<{
        goal: Goal
        task_distribution: {
          by_status: Record<string, number>
          by_type: Record<string, number>
        }
        monthly_progress: MonthlyProgress[]
      }>(`/dashboard/goals/${goalUuid}/progress`)
      
      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || '目標の進捗データの取得に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const getCompletionTrend = (): { date: string; completed: number }[] => {
    return weeklyProgress.value.map(day => ({
      date: day.date,
      completed: day.completed_tasks
    }))
  }

  const getTotalCompletedThisWeek = (): number => {
    return weeklyProgress.value.reduce((total, day) => total + day.completed_tasks, 0)
  }

  const getAverageCompletionRate = (): number => {
    if (!stats.value) return 0
    return Math.round((stats.value.goals.completion_rate + stats.value.tasks.completion_rate) / 2)
  }

  const getProductivityScore = (): number => {
    if (!stats.value) return 0
    
    const goalScore = stats.value.goals.completion_rate * 0.4
    const taskScore = stats.value.tasks.completion_rate * 0.6
    
    return Math.round(goalScore + taskScore)
  }

  const getTaskTypeDistribution = (): { type: string; count: number; percentage: number }[] => {
    if (!stats.value) return []
    
    const distribution = stats.value.task_type_distribution
    const total = Object.values(distribution).reduce((sum, count) => (sum as number) + (count as number), 0) as number
    
    return Object.entries(distribution).map(([type, count]) => ({
      type,
      count: count as number,
      percentage: total > 0 ? Math.round(((count as number) / total) * 100) : 0
    }))
  }

  const getOverdueTasksCount = (): number => {
    return stats.value?.tasks.overdue || 0
  }

  const getPendingTasksCount = (): number => {
    return stats.value?.tasks.pending || 0
  }

  const getActiveGoalsCount = (): number => {
    return stats.value?.goals.active || 0
  }

  const clearError = () => {
    error.value = null
  }

  const clearData = () => {
    stats.value = null
    recentGoals.value = []
    upcomingTasks.value = []
    weeklyProgress.value = []
  }

  return {
    // State
    stats,
    recentGoals,
    upcomingTasks,
    weeklyProgress,
    loading,
    error,
    
    // Actions
    fetchDashboardStats,
    fetchGoalProgress,
    getCompletionTrend,
    getTotalCompletedThisWeek,
    getAverageCompletionRate,
    getProductivityScore,
    getTaskTypeDistribution,
    getOverdueTasksCount,
    getPendingTasksCount,
    getActiveGoalsCount,
    clearError,
    clearData
  }
})