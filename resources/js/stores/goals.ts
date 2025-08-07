import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import type { Goal } from '../types/index'

export const useGoalsStore = defineStore('goals', () => {
  // State
  const goals = ref<Goal[]>([])
  const currentGoal = ref<Goal | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const activeGoals = computed(() => goals.value.filter(goal => goal.status === 'active'))
  const completedGoals = computed(() => goals.value.filter(goal => goal.status === 'completed'))
  const archivedGoals = computed(() => goals.value.filter(goal => goal.status === 'archived'))
  
  const totalGoals = computed(() => goals.value.length)
  const completionRate = computed(() => {
    if (totalGoals.value === 0) return 0
    return Math.round((completedGoals.value.length / totalGoals.value) * 100)
  })

  // Actions
  const fetchGoals = async (): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get<{ goals: Goal[] }>('/goals')
      goals.value = response.data.goals
    } catch (err: any) {
      error.value = err.response?.data?.message || '目標の取得に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchGoal = async (uuid: string): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.get<{ goal: Goal }>(`/goals/${uuid}`)
      currentGoal.value = response.data.goal
    } catch (err: any) {
      error.value = err.response?.data?.message || '目標の取得に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const createGoal = async (goalData: { title: string; description?: string; target_date?: string }): Promise<Goal> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.post<{ goal: Goal }>('/goals', goalData)
      const newGoal = response.data.goal
      
      goals.value.unshift(newGoal)
      return newGoal
    } catch (err: any) {
      error.value = err.response?.data?.message || '目標の作成に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateGoal = async (
    uuid: string,
    goalData: { title?: string; description?: string; status?: Goal['status']; target_date?: string }
  ): Promise<Goal> => {
    loading.value = true
    error.value = null
    
    try {
      const response = await axios.put<{ goal: Goal }>(`/goals/${uuid}`, goalData)
      const updatedGoal = response.data.goal
      
      // Update in goals array
      const index = goals.value.findIndex(goal => goal.uuid === uuid)
      if (index !== -1) {
        goals.value[index] = updatedGoal
      }
      
      // Update current goal if it's the same
      if (currentGoal.value?.uuid === uuid) {
        currentGoal.value = updatedGoal
      }
      
      return updatedGoal
    } catch (err: any) {
      error.value = err.response?.data?.message || '目標の更新に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteGoal = async (uuid: string): Promise<void> => {
    loading.value = true
    error.value = null
    
    try {
      await axios.delete(`/goals/${uuid}`)
      
      // Remove from goals array
      goals.value = goals.value.filter(goal => goal.uuid !== uuid)
      
      // Clear current goal if it's the same
      if (currentGoal.value?.uuid === uuid) {
        currentGoal.value = null
      }
    } catch (err: any) {
      error.value = err.response?.data?.message || '目標の削除に失敗しました。'
      throw err
    } finally {
      loading.value = false
    }
  }

  const completeGoal = async (uuid: string): Promise<Goal> => {
    return updateGoal(uuid, { status: 'completed' })
  }

  const archiveGoal = async (uuid: string): Promise<Goal> => {
    return updateGoal(uuid, { status: 'archived' })
  }

  const reactivateGoal = async (uuid: string): Promise<Goal> => {
    return updateGoal(uuid, { status: 'active' })
  }

  const clearError = () => {
    error.value = null
  }

  const clearCurrentGoal = () => {
    currentGoal.value = null
  }

  // Helper method for GoalDetail component
  const getGoal = async (uuid: string): Promise<Goal | null> => {
    await fetchGoal(uuid)
    return currentGoal.value
  }

  return {
    // State
    goals,
    currentGoal,
    loading,
    error,
    
    // Getters
    activeGoals,
    completedGoals,
    archivedGoals,
    totalGoals,
    completionRate,
    
    // Actions
    fetchGoals,
    fetchGoal,
    getGoal,
    createGoal,
    updateGoal,
    deleteGoal,
    completeGoal,
    archiveGoal,
    reactivateGoal,
    clearError,
    clearCurrentGoal
  }
})