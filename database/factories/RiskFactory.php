<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Risk>
 */
class RiskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $impact = fake()->randomElement(['Low', 'Medium', 'High', 'Critical']);
        $probability = fake()->randomElement(['Low', 'Medium', 'High']);

        return [
            'project_id' => Project::factory(),
            'type' => fake()->randomElement(['Risk', 'Issue']),
            'description' => fake()->paragraph(),
            'impact' => $impact,
            'probability' => $probability,
            'mitigation_plan' => fake()->optional()->paragraph(),
            'owner_id' => User::factory(),
            'status' => fake()->randomElement(['Open', 'In Progress', 'Mitigated', 'Closed']),
            'identified_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'resolved_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
        ];
    }

    /**
     * Indicate that the risk is critical.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'impact' => 'Critical',
            'probability' => 'High',
            'status' => 'Open',
        ]);
    }

    /**
     * Indicate that the risk is an issue.
     */
    public function issue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Issue',
            'status' => 'Open',
        ]);
    }

    /**
     * Indicate that the risk is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Closed',
            'resolved_at' => now(),
        ]);
    }
}
