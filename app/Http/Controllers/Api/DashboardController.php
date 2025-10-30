<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Basic counts
        $totalGoals = $user->goals()->count();
        $activeGoals = $user->goals()->where('status', 'active')->count();
        $completedGoals = $user->goals()->where('status', 'completed')->count();

        $totalTasks = Task::whereHas('goal', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        $completedTasks = Task::whereHas('goal', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'completed')->count();

        $pendingTasks = Task::whereHas('goal', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'pending')->count();

        $overdueTasks = Task::whereHas('goal', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'pending')
            ->where('due_date', '<', now())
            ->whereNotNull('due_date')
            ->count();

        // Recent activity
        $recentGoals = $user->goals()
            ->with(['tasks' => function ($query) {
                $query->select('id', 'goal_id', 'status');
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($goal) {
                return [
                    'uuid' => $goal->uuid,
                    'title' => $goal->title,
                    'status' => $goal->status,
                    'progress_percentage' => $goal->progress_percentage,
                    'updated_at' => $goal->updated_at,
                ];
            });

        $upcomingTasks = Task::whereHas('goal', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '>', now())
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($task) {
                return [
                    'uuid' => $task->uuid,
                    'title' => $task->title,
                    'due_date' => $task->due_date,
                    'days_until_due' => $task->days_until_due,
                    'goal_title' => $task->goal->title,
                ];
            });

        // Weekly progress
        $weeklyProgress = $this->getWeeklyProgress($user);

        // Task type distribution
        $taskTypeDistribution = Task::whereHas('goal', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return response()->json([
            'stats' => [
                'goals' => [
                    'total' => $totalGoals,
                    'active' => $activeGoals,
                    'completed' => $completedGoals,
                    'completion_rate' => $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100, 2) : 0,
                ],
                'tasks' => [
                    'total' => $totalTasks,
                    'completed' => $completedTasks,
                    'pending' => $pendingTasks,
                    'overdue' => $overdueTasks,
                    'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
                ],
                'task_type_distribution' => $taskTypeDistribution,
            ],
            'recent_goals' => $recentGoals,
            'upcoming_tasks' => $upcomingTasks,
            'weekly_progress' => $weeklyProgress,
        ]);
    }

    /**
     * Get weekly progress data.
     */
    private function getWeeklyProgress($user): array
    {
        $weeklyData = [];
        $startDate = Carbon::now()->subDays(6)->startOfDay();

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);

            $completedTasks = Task::whereHas('goal', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->whereDate('completed_at', $date->toDateString())
                ->count();

            $weeklyData[] = [
                'date' => $date->toDateString(),
                'day' => $date->format('D'),
                'completed_tasks' => $completedTasks,
            ];
        }

        return $weeklyData;
    }

    /**
     * Get goal progress details.
     */
    public function goalProgress(Request $request, string $goalUuid): JsonResponse
    {
        $goal = $request->user()->goals()
            ->where('uuid', $goalUuid)
            ->with(['tasks'])
            ->first();

        if (! $goal) {
            return response()->json([
                'message' => '目標が見つかりません。',
            ], 404);
        }

        $tasksByStatus = $goal->tasks->groupBy('status');
        $tasksByType = $goal->tasks->groupBy('type');

        // Monthly progress for this goal
        $monthlyProgress = [];
        $startDate = Carbon::now()->subMonths(5)->startOfMonth();

        for ($i = 0; $i < 6; $i++) {
            $date = $startDate->copy()->addMonths($i);

            $completedInMonth = $goal->tasks()
                ->whereYear('completed_at', $date->year)
                ->whereMonth('completed_at', $date->month)
                ->count();

            $monthlyProgress[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->format('M Y'),
                'completed_tasks' => $completedInMonth,
            ];
        }

        return response()->json([
            'goal' => [
                'uuid' => $goal->uuid,
                'title' => $goal->title,
                'description' => $goal->description,
                'status' => $goal->status,
                'progress_percentage' => $goal->progress_percentage,
                'total_tasks_count' => $goal->total_tasks_count,
                'completed_tasks_count' => $goal->completed_tasks_count,
                'created_at' => $goal->created_at,
            ],
            'task_distribution' => [
                'by_status' => $tasksByStatus->map->count(),
                'by_type' => $tasksByType->map->count(),
            ],
            'monthly_progress' => $monthlyProgress,
        ]);
    }
}
