<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'type' => 'simple',
            'status' => 'pending',
            'recurrence_type' => null,
            'due_date' => null,
            'completed_at' => null,
            'last_reset_at' => null,
        ];
    }

    /**
     * Indicate that the task is recurring.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'recurring',
            'recurrence_type' => fake()->randomElement(['daily', 'weekly', 'monthly']),
        ]);
    }

    /**
     * Indicate that the task has a deadline.
     */
    public function deadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deadline',
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Indicate that the task is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
