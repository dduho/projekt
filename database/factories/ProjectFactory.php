<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'business_area' => fake()->randomElement(['Digital Services', 'Core Banking', 'Mobile Money', 'Merchant Services']),
            'priority' => fake()->randomElement(['High', 'Medium', 'Low']),
            'frs_status' => fake()->randomElement(['Draft', 'Review', 'Signoff']),
            'dev_status' => fake()->randomElement(['Not Started', 'In Development', 'Testing', 'UAT', 'Deployed', 'On Hold']),
            'current_progress' => fake()->sentence(),
            'blockers' => fake()->optional()->sentence(),
            'owner_id' => User::factory(),
            'planned_release' => fake()->randomElement(['Q1 2026', 'Q2 2026', 'Q3 2026', 'Q4 2026']),
            'submission_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'target_date' => fake()->dateTimeBetween('now', '+6 months'),
            'go_live_date' => fake()->optional()->dateTimeBetween('now', '+1 year'),
            'rag_status' => fake()->randomElement(['Green', 'Amber', 'Red']),
            'completion_percent' => fake()->numberBetween(0, 100),
            'service_type' => fake()->randomElement(['API', 'USSD', 'Web', 'Mobile', 'Backend']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the project is deployed.
     */
    public function deployed(): static
    {
        return $this->state(fn (array $attributes) => [
            'dev_status' => 'Deployed',
            'completion_percent' => 100,
            'rag_status' => 'Green',
            'go_live_date' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Indicate that the project is critical.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'rag_status' => 'Red',
            'blockers' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the project is in development.
     */
    public function inDevelopment(): static
    {
        return $this->state(fn (array $attributes) => [
            'dev_status' => 'In Development',
            'completion_percent' => fake()->numberBetween(20, 60),
            'rag_status' => fake()->randomElement(['Green', 'Amber']),
        ]);
    }
}
