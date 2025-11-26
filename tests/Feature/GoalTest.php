<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GoalTest extends TestCase
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
     * 認証済みユーザーがゴールを作成できることを確認
     */
    public function test_authenticated_user_can_create_goal()
    {
        [$user, $token] = $this->authenticatedUser();

        $goalData = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'target_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/goals', $goalData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'goal' => [
                    'uuid',
                    'title',
                    'description',
                    'status',
                    'progress_percentage',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'title' => $goalData['title'],
            'description' => $goalData['description'],
        ]);
    }

    /**
     * 認証済みユーザーが自分のゴール一覧を取得できることを確認
     */
    public function test_authenticated_user_can_list_their_goals()
    {
        [$user, $token] = $this->authenticatedUser();

        // ユーザーのゴールを作成
        Goal::factory()->count(3)->create(['user_id' => $user->id]);

        // 他のユーザーのゴールを作成（返却されないことを確認）
        $otherUser = User::factory()->create();
        Goal::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/goals');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'goals' => [
                    '*' => [
                        'uuid',
                        'title',
                        'description',
                        'status',
                        'progress_percentage',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'goals');
    }

    /**
     * 認証済みユーザーが特定のゴールを閲覧できることを確認
     */
    public function test_authenticated_user_can_view_specific_goal()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("/api/goals/{$goal->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'goal' => [
                    'uuid',
                    'title',
                    'description',
                    'status',
                    'progress_percentage',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * 認証済みユーザーが自分のゴールを更新できることを確認
     */
    public function test_authenticated_user_can_update_their_goal()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'title' => 'Updated Goal Title',
            'description' => 'Updated description',
            'status' => 'completed',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson("/api/goals/{$goal->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'goal' => [
                    'title' => $updateData['title'],
                    'description' => $updateData['description'],
                    'status' => $updateData['status'],
                ],
            ]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'title' => $updateData['title'],
            'description' => $updateData['description'],
            'status' => $updateData['status'],
        ]);
    }

    /**
     * 認証済みユーザーが自分のゴールを削除できることを確認
     */
    public function test_authenticated_user_can_delete_their_goal()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson("/api/goals/{$goal->uuid}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('goals', [
            'id' => $goal->id,
        ]);
    }

    /**
     * ユーザーが他のユーザーのゴールにアクセスできないことを確認
     */
    public function test_user_cannot_access_other_users_goals()
    {
        [$user, $token] = $this->authenticatedUser();
        $otherUser = User::factory()->create();
        $otherGoal = Goal::factory()->create(['user_id' => $otherUser->id]);

        // 他のユーザーのゴールを閲覧しようとする
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("/api/goals/{$otherGoal->uuid}");

        $response->assertStatus(404);

        // 他のユーザーのゴールを更新しようとする
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson("/api/goals/{$otherGoal->uuid}", ['title' => 'Hacked']);

        $response->assertStatus(404);

        // 他のユーザーのゴールを削除しようとする
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson("/api/goals/{$otherGoal->uuid}");

        $response->assertStatus(404);
    }

    /**
     * 未認証ユーザーがゴールにアクセスできないことを確認
     */
    public function test_unauthenticated_user_cannot_access_goals()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/goals');
        $response->assertStatus(401);

        $response = $this->getJson("/api/goals/{$goal->uuid}");
        $response->assertStatus(401);

        $response = $this->postJson('/api/goals', ['title' => 'Test Goal']);
        $response->assertStatus(401);

        $response = $this->putJson("/api/goals/{$goal->uuid}", ['title' => 'Updated']);
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/goals/{$goal->uuid}");
        $response->assertStatus(401);
    }

    /**
     * ゴール作成には有効なデータが必要であることを確認
     */
    public function test_goal_creation_requires_valid_data()
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/goals', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * ゴールのタイトルは文字列である必要があることを確認
     */
    public function test_goal_title_must_be_string()
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/goals', ['title' => 123]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }
}
