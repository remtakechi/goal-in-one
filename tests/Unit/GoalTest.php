<?php

namespace Tests\Unit;

use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * タスクが0件の場合、進捗率は0%を返すことを確認
     */
    public function test_progress_percentage_is_zero_when_no_tasks(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(0, $goal->progress_percentage);
    }

    /**
     * 全てのタスクが完了している場合、進捗率は100%を返すことを確認
     */
    public function test_progress_percentage_is_100_when_all_tasks_completed(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        // 完了済みタスクを3件作成
        Task::factory()->count(3)->completed()->create(['goal_id' => $goal->id]);

        $this->assertEquals(100.0, $goal->progress_percentage);
    }

    /**
     * 一部のタスクが完了している場合、正しい進捗率を返すことを確認
     */
    public function test_progress_percentage_calculates_partial_completion(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        // 完了済みタスクを2件、未完了タスクを3件作成
        Task::factory()->count(2)->completed()->create(['goal_id' => $goal->id]);
        Task::factory()->count(3)->create(['goal_id' => $goal->id, 'status' => 'pending']);

        // 2/5 = 40%
        $this->assertEquals(40.0, $goal->progress_percentage);
    }

    /**
     * 進捗率の小数点が正しく丸められることを確認
     */
    public function test_progress_percentage_rounds_to_two_decimal_places(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        // 完了済みタスクを1件、未完了タスクを3件作成（1/3 = 33.333...%）
        Task::factory()->count(1)->completed()->create(['goal_id' => $goal->id]);
        Task::factory()->count(2)->create(['goal_id' => $goal->id, 'status' => 'pending']);

        // 33.33%に丸められる
        $this->assertEquals(33.33, $goal->progress_percentage);
    }

    /**
     * 完了タスク数が正しく計算されることを確認
     */
    public function test_completed_tasks_count_attribute(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        // 完了済みタスクを2件、未完了タスクを3件作成
        Task::factory()->count(2)->completed()->create(['goal_id' => $goal->id]);
        Task::factory()->count(3)->create(['goal_id' => $goal->id, 'status' => 'pending']);

        $this->assertEquals(2, $goal->completed_tasks_count);
    }

    /**
     * 総タスク数が正しく計算されることを確認
     */
    public function test_total_tasks_count_attribute(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        // タスクを5件作成
        Task::factory()->count(5)->create(['goal_id' => $goal->id]);

        $this->assertEquals(5, $goal->total_tasks_count);
    }

    /**
     * タスクがない場合、完了タスク数は0を返すことを確認
     */
    public function test_completed_tasks_count_is_zero_when_no_tasks(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(0, $goal->completed_tasks_count);
    }

    /**
     * タスクがない場合、総タスク数は0を返すことを確認
     */
    public function test_total_tasks_count_is_zero_when_no_tasks(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(0, $goal->total_tasks_count);
    }

    /**
     * ゴール作成時にUUIDが自動生成されることを確認
     */
    public function test_uuid_is_auto_generated_on_creation(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->assertNotNull($goal->uuid);
        $this->assertNotEmpty($goal->uuid);
    }

    /**
     * 既にUUIDが設定されている場合、自動生成されないことを確認
     */
    public function test_uuid_is_not_overwritten_if_already_set(): void
    {
        $user = User::factory()->create();
        $customUuid = 'custom-uuid-12345';

        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'uuid' => $customUuid,
        ]);

        $this->assertEquals($customUuid, $goal->uuid);
    }

    /**
     * ルートキーとしてUUIDが使用されることを確認
     */
    public function test_route_key_name_is_uuid(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('uuid', $goal->getRouteKeyName());
    }
}

