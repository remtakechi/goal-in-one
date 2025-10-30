<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Display a listing of all tasks for the authenticated user.
     */
    public function indexAll(Request $request): JsonResponse
    {
        $tasks = Task::whereHas('goal', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->with('goal:uuid,title')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($task) {
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
                    'goal_title' => $task->goal->title ?? null,
                    'goal_uuid' => $task->goal->uuid ?? null,
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                ];
            });

        return response()->json([
            'tasks' => $tasks,
        ]);
    }

    /**
     * Store a newly created task (independent of goal).
     */
    public function storeIndependent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goal_uuid' => 'nullable|string|exists:goals,uuid',
            'type' => 'required|in:simple,recurring,deadline',
            'recurrence_type' => 'nullable|required_if:type,recurring|in:daily,weekly,monthly',
            'due_date' => 'nullable|required_if:type,deadline|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find goal if provided
        $goal = null;
        if ($request->goal_uuid) {
            $goal = $request->user()->goals()->where('uuid', $request->goal_uuid)->first();
            if (! $goal) {
                return response()->json([
                    'message' => '指定された目標が見つかりません。',
                ], 404);
            }
        }

        $task = new Task([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'recurrence_type' => $request->recurrence_type,
            'due_date' => $request->due_date,
        ]);

        if ($goal) {
            $task->goal_id = $goal->id;
        }

        $task->save();

        return response()->json([
            'message' => 'タスクを作成しました。',
            'task' => [
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
                'goal_title' => $goal->title ?? null,
                'goal_uuid' => $goal->uuid ?? null,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ],
        ], 201);
    }

    /**
     * Display a listing of tasks for a goal.
     */
    public function index(Request $request, string $goalUuid): JsonResponse
    {
        $goal = $request->user()->goals()->where('uuid', $goalUuid)->first();

        if (! $goal) {
            return response()->json([
                'message' => '目標が見つかりません。',
            ], 404);
        }

        $tasks = $goal->tasks()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($task) {
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
            });

        return response()->json([
            'tasks' => $tasks,
        ]);
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request, string $goalUuid): JsonResponse
    {
        $goal = $request->user()->goals()->where('uuid', $goalUuid)->first();

        if (! $goal) {
            return response()->json([
                'message' => '目標が見つかりません。',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,recurring,deadline',
            'recurrence_type' => 'nullable|required_if:type,recurring|in:daily,weekly,monthly',
            'due_date' => 'nullable|required_if:type,deadline|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $validator->errors(),
            ], 422);
        }

        $task = $goal->tasks()->create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'recurrence_type' => $request->recurrence_type,
            'due_date' => $request->due_date,
        ]);

        return response()->json([
            'message' => 'タスクを作成しました。',
            'task' => [
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
            ],
        ], 201);
    }

    /**
     * Display the specified task.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $task = Task::whereHas('goal', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->where('uuid', $uuid)->first();

        if (! $task) {
            return response()->json([
                'message' => 'タスクが見つかりません。',
            ], 404);
        }

        return response()->json([
            'task' => [
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
            ],
        ]);
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $task = Task::whereHas('goal', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->where('uuid', $uuid)->first();

        if (! $task) {
            return response()->json([
                'message' => 'タスクが見つかりません。',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:simple,recurring,deadline',
            'recurrence_type' => 'nullable|required_if:type,recurring|in:daily,weekly,monthly',
            'due_date' => 'nullable|required_if:type,deadline|date',
            'status' => 'sometimes|required|in:pending,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = $request->only(['title', 'description', 'type', 'recurrence_type', 'due_date', 'status']);

        // Handle status change to completed
        if (isset($updateData['status']) && $updateData['status'] === 'completed' && $task->status !== 'completed') {
            $task->markAsCompleted();
        } elseif (isset($updateData['status']) && $updateData['status'] === 'pending' && $task->status === 'completed') {
            $task->status = 'pending';
            $task->completed_at = null;
            $task->save();
        } else {
            $task->update($updateData);
        }

        return response()->json([
            'message' => 'タスクを更新しました。',
            'task' => [
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
            ],
        ]);
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $task = Task::whereHas('goal', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->where('uuid', $uuid)->first();

        if (! $task) {
            return response()->json([
                'message' => 'タスクが見つかりません。',
            ], 404);
        }

        $task->delete();

        return response()->json([
            'message' => 'タスクを削除しました。',
        ]);
    }

    /**
     * Mark task as completed.
     */
    public function complete(Request $request, string $uuid): JsonResponse
    {
        $task = Task::whereHas('goal', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->where('uuid', $uuid)->first();

        if (! $task) {
            return response()->json([
                'message' => 'タスクが見つかりません。',
            ], 404);
        }

        if ($task->status === 'completed') {
            return response()->json([
                'message' => 'タスクは既に完了しています。',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラーが発生しました。',
                'errors' => $validator->errors(),
            ], 422);
        }

        $task->markAsCompleted($request->notes);

        return response()->json([
            'message' => 'タスクを完了しました。',
            'task' => [
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
            ],
        ]);
    }
}
