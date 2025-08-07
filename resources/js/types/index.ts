export interface User {
  uuid: string
  name: string
  email: string
}

export interface Goal {
  uuid: string
  title: string
  description?: string
  status: 'active' | 'completed' | 'archived'
  target_date?: string
  completed_at?: string
  created_at: string
  updated_at: string
  progress_percentage: number
  total_tasks_count: number
  completed_tasks_count: number
  tasks?: Task[]
}

export interface Task {
  uuid: string
  title: string
  description?: string
  type: 'simple' | 'recurring' | 'deadline'
  status: 'pending' | 'completed'
  recurrence_type?: 'daily' | 'weekly' | 'monthly'
  due_date?: string
  completed_at?: string
  is_overdue: boolean
  days_until_due?: number
  created_at: string
  updated_at: string
  goal_title?: string
}

export interface TaskCompletion {
  completed_at: string
  notes?: string
}

export interface DashboardStats {
  goals: {
    total: number
    active: number
    completed: number
    completion_rate: number
  }
  tasks: {
    total: number
    completed: number
    pending: number
    overdue: number
    completion_rate: number
  }
  task_type_distribution: Record<string, number>
}

export interface WeeklyProgress {
  date: string
  day: string
  completed_tasks: number
}

export interface MonthlyProgress {
  month: string
  month_name: string
  completed_tasks: number
}

export interface ApiResponse<T = any> {
  message?: string
  data?: T
  errors?: Record<string, string[]>
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterCredentials {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface AuthResponse {
  user: User
  token: string
  message: string
}