<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * 認証済みユーザーとトークンを生成するヘルパーメソッド
     */
    private function authenticatedUser()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$user, $token];
    }

    /**
     * 認証済みユーザーがシンプルタスクを作成できることを確認
     */
    public function test_authenticated_user_can_create_simple_task()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $taskData = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'type' => 'simple',
            'goal_uuid' => $goal->uuid,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'task' => [
                    'uuid',
                    'title',
                    'description',
                    'type',
                    'status',
                    'goal_uuid',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'goal_id' => $goal->id,
            'title' => $taskData['title'],
            'type' => 'simple',
        ]);
    }

    /**
     * 認証済みユーザーが繰り返しタスクを作成できることを確認
     */
    public function test_authenticated_user_can_create_recurring_task()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $taskData = [
            'title' => 'Daily Exercise',
            'description' => 'Exercise for 30 minutes',
            'type' => 'recurring',
            'goal_uuid' => $goal->uuid,
            'recurrence_type' => 'daily',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJson([
                'task' => [
                    'type' => 'recurring',
                    'recurrence_type' => 'daily',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'goal_id' => $goal->id,
            'title' => $taskData['title'],
            'type' => 'recurring',
            'recurrence_type' => 'daily',
        ]);
    }

    /**
     * 認証済みユーザーが期限付きタスクを作成できることを確認
     */
    public function test_authenticated_user_can_create_deadline_task()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $dueDate = $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d H:i:s');

        $taskData = [
            'title' => 'Submit Report',
            'description' => 'Submit monthly report',
            'type' => 'deadline',
            'goal_uuid' => $goal->uuid,
            'due_date' => $dueDate,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJson([
                'task' => [
                    'type' => 'deadline',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'goal_id' => $goal->id,
            'title' => $taskData['title'],
            'type' => 'deadline',
        ]);
    }

    /**
     * 認証済みユーザーがゴールに紐づかない独立したタスクを作成できることを確認
     */
    public function test_authenticated_user_can_create_independent_task()
    {
        [$user, $token] = $this->authenticatedUser();

        $taskData = [
            'title' => 'Independent Task',
            'description' => 'Task without goal',
            'type' => 'simple',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJson([
                'task' => [
                    'goal_uuid' => null,
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => $taskData['title'],
            'goal_id' => null,
        ]);
    }

    /**
     * 認証済みユーザーが自分のタスク一覧を取得できることを確認
     */
    public function test_authenticated_user_can_list_their_tasks()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        // ユーザーのタスクを作成
        Task::factory()->count(3)->create([
            'goal_id' => $goal->id,
        ]);

        // 他のユーザーのタスクを作成（返却されないことを確認）
        $otherUser = User::factory()->create();
        $otherGoal = Goal::factory()->create(['user_id' => $otherUser->id]);
        Task::factory()->count(2)->create([
            'goal_id' => $otherGoal->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tasks' => [
                    '*' => [
                        'uuid',
                        'title',
                        'description',
                        'type',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'tasks');
    }

    /**
     * 認証済みユーザーがタスクを完了できることを確認
     */
    public function test_authenticated_user_can_complete_task()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'goal_id' => $goal->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson("/api/tasks/{$task->uuid}/complete");

        $response->assertStatus(200)
            ->assertJson([
                'task' => [
                    'status' => 'completed',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);

        // タスク完了記録が作成されたことを確認
        $this->assertDatabaseHas('task_completions', [
            'task_id' => $task->id,
        ]);
    }

    /**
     * 認証済みユーザーが自分のタスクを更新できることを確認
     */
    public function test_authenticated_user_can_update_their_task()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'goal_id' => $goal->id,
        ]);

        $updateData = [
            'title' => 'Updated Task Title',
            'description' => 'Updated description',
            'status' => 'completed',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson("/api/tasks/{$task->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'task' => [
                    'title' => $updateData['title'],
                    'description' => $updateData['description'],
                    'status' => $updateData['status'],
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => $updateData['title'],
            'description' => $updateData['description'],
            'status' => $updateData['status'],
        ]);
    }

    /**
     * 認証済みユーザーが自分のタスクを削除できることを確認
     */
    public function test_authenticated_user_can_delete_their_task()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'goal_id' => $goal->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson("/api/tasks/{$task->uuid}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    /**
     * ユーザーが他のユーザーのタスクにアクセスできないことを確認
     */
    public function test_user_cannot_access_other_users_tasks()
    {
        [$user, $token] = $this->authenticatedUser();
        $otherUser = User::factory()->create();
        $otherGoal = Goal::factory()->create(['user_id' => $otherUser->id]);
        $otherTask = Task::factory()->create([
            'goal_id' => $otherGoal->id,
        ]);

        // 他のユーザーのタスクを閲覧しようとする
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("/api/tasks/{$otherTask->uuid}");

        $response->assertStatus(404);

        // 他のユーザーのタスクを更新しようとする
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson("/api/tasks/{$otherTask->uuid}", ['title' => 'Hacked']);

        $response->assertStatus(404);

        // 他のユーザーのタスクを削除しようとする
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson("/api/tasks/{$otherTask->uuid}");

        $response->assertStatus(404);
    }

    /**
     * タスク作成には有効なデータが必要であることを確認
     */
    public function test_task_creation_requires_valid_data()
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'type']);
    }

    /**
     * 繰り返しタスクには繰り返しタイプが必要であることを確認
     */
    public function test_recurring_task_requires_recurring_type()
    {
        [$user, $token] = $this->authenticatedUser();

        $taskData = [
            'title' => 'Recurring Task',
            'type' => 'recurring',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recurrence_type']);
    }

    /**
     * 期限付きタスクには期限日が必要であることを確認
     */
    public function test_deadline_task_requires_due_date()
    {
        [$user, $token] = $this->authenticatedUser();

        $taskData = [
            'title' => 'Deadline Task',
            'type' => 'deadline',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/tasks', $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }
}
