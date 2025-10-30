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

    private function authenticatedUser()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [$user, $token];
    }

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
                'id',
                'uuid',
                'title',
                'description',
                'target_date',
                'status',
                'progress_percentage',
                'created_at',
                'updated_at',
            ]);

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'title' => $goalData['title'],
            'description' => $goalData['description'],
        ]);
    }

    public function test_authenticated_user_can_list_their_goals()
    {
        [$user, $token] = $this->authenticatedUser();

        // Create some goals for the user
        Goal::factory()->count(3)->create(['user_id' => $user->id]);

        // Create goals for another user (should not be returned)
        $otherUser = User::factory()->create();
        Goal::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/goals');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'uuid',
                    'title',
                    'description',
                    'target_date',
                    'status',
                    'progress_percentage',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_authenticated_user_can_view_specific_goal()
    {
        [$user, $token] = $this->authenticatedUser();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("/api/goals/{$goal->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'uuid',
                'title',
                'description',
                'target_date',
                'status',
                'progress_percentage',
                'created_at',
                'updated_at',
            ]);
    }

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
                'title' => $updateData['title'],
                'description' => $updateData['description'],
                'status' => $updateData['status'],
            ]);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'title' => $updateData['title'],
            'description' => $updateData['description'],
            'status' => $updateData['status'],
        ]);
    }

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

    public function test_user_cannot_access_other_users_goals()
    {
        [$user, $token] = $this->authenticatedUser();
        $otherUser = User::factory()->create();
        $otherGoal = Goal::factory()->create(['user_id' => $otherUser->id]);

        // Try to view other user's goal
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson("/api/goals/{$otherGoal->uuid}");

        $response->assertStatus(404);

        // Try to update other user's goal
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->putJson("/api/goals/{$otherGoal->uuid}", ['title' => 'Hacked']);

        $response->assertStatus(404);

        // Try to delete other user's goal
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson("/api/goals/{$otherGoal->uuid}");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_access_goals()
    {
        $goal = Goal::factory()->create();

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

    public function test_goal_creation_requires_valid_data()
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/goals', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

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
