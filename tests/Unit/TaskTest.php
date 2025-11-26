<?php

namespace Tests\Unit;

use App\Models\Goal;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    /**
     * タスク完了時にステータスが更新されることを確認
     */
    public function test_mark_as_completed_updates_status(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'goal_id' => $goal->id,
            'status' => 'pending',
        ]);

        $task->markAsCompleted();

        $this->assertEquals('completed', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    /**
     * タスク完了時にTaskCompletionレコードが作成されることを確認
     */
    public function test_mark_as_completed_creates_completion_record(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['goal_id' => $goal->id]);

        $task->markAsCompleted('テストノート');

        $this->assertDatabaseHas('task_completions', [
            'task_id' => $task->id,
            'notes' => 'テストノート',
        ]);
    }

    /**
     * タスク完了時にノートが指定されていない場合でもTaskCompletionが作成されることを確認
     */
    public function test_mark_as_completed_creates_completion_record_without_notes(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['goal_id' => $goal->id]);

        $task->markAsCompleted();

        $this->assertDatabaseHas('task_completions', [
            'task_id' => $task->id,
            'notes' => null,
        ]);
    }

    /**
     * 繰り返しタスク（daily）が完了時に自動リセットされることを確認
     */
    public function test_recurring_daily_task_resets_after_completion(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->recurring()->create([
            'goal_id' => $goal->id,
            'recurrence_type' => 'daily',
            'status' => 'pending',
        ]);

        $task->markAsCompleted();

        $freshTask = $task->fresh();
        $this->assertEquals('pending', $freshTask->status);
        $this->assertNull($freshTask->completed_at);
        $this->assertNotNull($freshTask->last_reset_at);
    }

    /**
     * 繰り返しタスク（weekly）が完了時に自動リセットされることを確認
     */
    public function test_recurring_weekly_task_resets_after_completion(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->recurring()->create([
            'goal_id' => $goal->id,
            'recurrence_type' => 'weekly',
            'status' => 'pending',
        ]);

        $task->markAsCompleted();

        $freshTask = $task->fresh();
        $this->assertEquals('pending', $freshTask->status);
        $this->assertNull($freshTask->completed_at);
    }

    /**
     * 繰り返しタスク（monthly）が完了時に自動リセットされることを確認
     */
    public function test_recurring_monthly_task_resets_after_completion(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->recurring()->create([
            'goal_id' => $goal->id,
            'recurrence_type' => 'monthly',
            'status' => 'pending',
        ]);

        $task->markAsCompleted();

        $freshTask = $task->fresh();
        $this->assertEquals('pending', $freshTask->status);
        $this->assertNull($freshTask->completed_at);
    }

    /**
     * 繰り返しタイプがnullの場合、リセットされないことを確認
     */
    public function test_recurring_task_with_null_type_does_not_reset(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->recurring()->create([
            'goal_id' => $goal->id,
            'recurrence_type' => null,
            'status' => 'pending',
        ]);

        $task->markAsCompleted();

        $freshTask = $task->fresh();
        $this->assertEquals('completed', $freshTask->status);
        $this->assertNotNull($freshTask->completed_at);
    }

    /**
     * シンプルタスクは完了時にリセットされないことを確認
     */
    public function test_simple_task_does_not_reset_after_completion(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'goal_id' => $goal->id,
            'type' => 'simple',
            'status' => 'pending',
        ]);

        $task->markAsCompleted();

        $freshTask = $task->fresh();
        $this->assertEquals('completed', $freshTask->status);
        $this->assertNotNull($freshTask->completed_at);
    }

    /**
     * 期限が過ぎている場合、期限切れと判定されることを確認
     */
    public function test_is_overdue_returns_true_for_past_due_date(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->deadline()->create([
            'goal_id' => $goal->id,
            'status' => 'pending',
            'due_date' => Carbon::yesterday(),
        ]);

        $this->assertTrue($task->is_overdue);
    }

    /**
     * 期限が未来の場合、期限切れと判定されないことを確認
     */
    public function test_is_overdue_returns_false_for_future_due_date(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->deadline()->create([
            'goal_id' => $goal->id,
            'status' => 'pending',
            'due_date' => Carbon::tomorrow(),
        ]);

        $this->assertFalse($task->is_overdue);
    }

    /**
     * 期限がない場合、期限切れと判定されないことを確認
     */
    public function test_is_overdue_returns_false_when_no_due_date(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'goal_id' => $goal->id,
            'status' => 'pending',
            'due_date' => null,
        ]);

        $this->assertFalse($task->is_overdue);
    }

    /**
     * 既に完了している場合、期限切れと判定されないことを確認
     */
    public function test_is_overdue_returns_false_when_task_is_completed(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->deadline()->completed()->create([
            'goal_id' => $goal->id,
            'due_date' => Carbon::yesterday(),
        ]);

        $this->assertFalse($task->is_overdue);
    }

    /**
     * 期限までの日数が正しく計算されることを確認（未来の日付）
     */
    public function test_days_until_due_calculates_future_days(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        // 日付を正規化して時間の丸め誤差を避ける
        $futureDate = Carbon::now()->startOfDay()->addDays(5);
        $task = Task::factory()->deadline()->create([
            'goal_id' => $goal->id,
            'status' => 'pending',
            'due_date' => $futureDate,
        ]);

        // diffInDaysは符号付きの日数差を返す（未来の場合は正の値）
        $this->assertGreaterThanOrEqual(4, $task->days_until_due);
        $this->assertLessThanOrEqual(5, $task->days_until_due);
    }

    /**
     * 期限までの日数が負の値で返されることを確認（過去の日付）
     */
    public function test_days_until_due_returns_negative_for_past_due_date(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $pastDate = Carbon::now()->subDays(3);
        $task = Task::factory()->deadline()->create([
            'goal_id' => $goal->id,
            'status' => 'pending',
            'due_date' => $pastDate,
        ]);

        $this->assertEquals(-3, $task->days_until_due);
    }

    /**
     * 期限がない場合、nullを返すことを確認
     */
    public function test_days_until_due_returns_null_when_no_due_date(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create([
            'goal_id' => $goal->id,
            'status' => 'pending',
            'due_date' => null,
        ]);

        $this->assertNull($task->days_until_due);
    }

    /**
     * タスクが完了している場合、nullを返すことを確認
     */
    public function test_days_until_due_returns_null_when_task_is_completed(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $futureDate = Carbon::now()->addDays(5);
        $task = Task::factory()->deadline()->completed()->create([
            'goal_id' => $goal->id,
            'due_date' => $futureDate,
        ]);

        $this->assertNull($task->days_until_due);
    }

    /**
     * タスク作成時にUUIDが自動生成されることを確認
     */
    public function test_uuid_is_auto_generated_on_creation(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['goal_id' => $goal->id]);

        $this->assertNotNull($task->uuid);
        $this->assertNotEmpty($task->uuid);
    }

    /**
     * 既にUUIDが設定されている場合、自動生成されないことを確認
     */
    public function test_uuid_is_not_overwritten_if_already_set(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $customUuid = 'custom-uuid-12345';

        $task = Task::factory()->create([
            'goal_id' => $goal->id,
            'uuid' => $customUuid,
        ]);

        $this->assertEquals($customUuid, $task->uuid);
    }

    /**
     * ルートキーとしてUUIDが使用されることを確認
     */
    public function test_route_key_name_is_uuid(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['goal_id' => $goal->id]);

        $this->assertEquals('uuid', $task->getRouteKeyName());
    }
}

