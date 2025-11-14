<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GoalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $goals = $user->goals()
            ->with(['tasks' => function ($query) {
                $query->select('uuid', 'goal_id', 'status');
            }])
            ->get()
            ->map(function ($goal) {
                return [
                    'uuid' => $goal->uuid,
                    'title' => $goal->title,
                    'description' => $goal->description,
                    'status' => $goal->status,
                    'completed_at' => $goal->completed_at,
                    'created_at' => $goal->created_at,
                    'updated_at' => $goal->updated_at,
                    'progress_percentage' => $goal->progress_percentage,
                    'total_tasks_count' => $goal->total_tasks_count,
                    'completed_tasks_count' => $goal->completed_tasks_count,
                ];
            });

        return response()->json([
            'goals' => $goals,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGoalRequest $request): JsonResponse
    {
        $goal = $request->user()->goals()->create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => '目標を作成しました。',
            'goal' => [
                'uuid' => $goal->uuid,
                'title' => $goal->title,
                'description' => $goal->description,
                'status' => $goal->status,
                'completed_at' => $goal->completed_at,
                'created_at' => $goal->created_at,
                'updated_at' => $goal->updated_at,
                'progress_percentage' => 0,
                'total_tasks_count' => 0,
                'completed_tasks_count' => 0,
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $goal = $request->user()->goals()
            ->where('uuid', $uuid)
            ->with(['tasks' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->first();

        if (!$goal) {
            return response()->json([
                'message' => '目標が見つかりません。',
            ], 404);
        }

        return response()->json([
            'goal' => [
                'uuid' => $goal->uuid,
                'title' => $goal->title,
                'description' => $goal->description,
                'status' => $goal->status,
                'completed_at' => $goal->completed_at,
                'created_at' => $goal->created_at,
                'updated_at' => $goal->updated_at,
                'progress_percentage' => $goal->progress_percentage,
                'total_tasks_count' => $goal->total_tasks_count,
                'completed_tasks_count' => $goal->completed_tasks_count,
                'tasks' => $goal->tasks->map(function ($task) {
                    return [
                        'uuid' => $task->uuid,
                        'title' => $task->title,
                        'description' => $task->description,
                        'type' => $task->type,
                        'status' => $task->status,
                        'recurrence_type' => $task->recurrence_type,
                        'due_date' => $task->due_date,
                        'completed_at' => $task->completed_at,
                        'is_overdue' => $task->is_overdue,
                        'days_until_due' => $task->days_until_due,
                        'created_at' => $task->created_at,
                        'updated_at' => $task->updated_at,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGoalRequest $request, string $uuid): JsonResponse
    {
        $goal = $request->user()->goals()->where('uuid', $uuid)->first();

        if (!$goal) {
            return response()->json([
                'message' => '目標が見つかりません。',
            ], 404);
        }

        $updateData = $request->only(['title', 'description', 'status']);

        // If marking as completed, set completed_at timestamp
        if (isset($updateData['status']) && $updateData['status'] === 'completed') {
            $updateData['completed_at'] = now();
        } elseif (isset($updateData['status']) && $updateData['status'] !== 'completed') {
            $updateData['completed_at'] = null;
        }

        $goal->update($updateData);

        return response()->json([
            'message' => '目標を更新しました。',
            'goal' => [
                'uuid' => $goal->uuid,
                'title' => $goal->title,
                'description' => $goal->description,
                'status' => $goal->status,
                'completed_at' => $goal->completed_at,
                'created_at' => $goal->created_at,
                'updated_at' => $goal->updated_at,
                'progress_percentage' => $goal->progress_percentage,
                'total_tasks_count' => $goal->total_tasks_count,
                'completed_tasks_count' => $goal->completed_tasks_count,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $uuid): Response|JsonResponse
    {
        $goal = $request->user()->goals()->where('uuid', $uuid)->first();

        if (!$goal) {
            return response()->json([
                'message' => '目標が見つかりません。',
            ], 404);
        }

        $goal->delete();

        return response()->noContent();
    }
}
