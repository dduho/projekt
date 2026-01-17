<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChangeRequest>
 */
class ChangeRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'change_type' => fake()->randomElement(['Scope Change', 'Schedule Change', 'Budget Change', 'Resource Change']),
            'description' => fake()->paragraph(),
            'impact_analysis' => fake()->optional()->paragraph(),
            'requested_by_id' => User::factory(),
            'approved_by_id' => null,
            'status' => 'Pending',
            'requested_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'resolved_at' => null,
        ];
    }

    /**
     * Indicate that the change request is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Approved',
            'approved_by_id' => User::factory(),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate that the change request is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Rejected',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate that the change request is under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Under Review',
        ]);
    }
}
