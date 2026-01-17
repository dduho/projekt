<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\Risk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_dashboard_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_shows_correct_kpis(): void
    {
        $category = Category::factory()->create();

        // Create projects with different statuses
        Project::factory()->count(3)->deployed()->create(['category_id' => $category->id]);
        Project::factory()->count(2)->inDevelopment()->create(['category_id' => $category->id]);
        Project::factory()->count(1)->critical()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('stats')
            ->has('stats.total_projects')
        );
    }

    public function test_dashboard_shows_rag_distribution(): void
    {
        $category = Category::factory()->create();

        Project::factory()->count(5)->create([
            'category_id' => $category->id,
            'rag_status' => 'Green'
        ]);
        Project::factory()->count(3)->create([
            'category_id' => $category->id,
            'rag_status' => 'Amber'
        ]);
        Project::factory()->count(2)->create([
            'category_id' => $category->id,
            'rag_status' => 'Red'
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('ragDistribution')
        );
    }

    public function test_dashboard_shows_critical_risks(): void
    {
        $category = Category::factory()->create();
        $project = Project::factory()->create(['category_id' => $category->id]);

        Risk::factory()->count(3)->critical()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('stats.critical_risks')
        );
    }
}
