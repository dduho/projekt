<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_projects_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get('/projects');

        $response->assertStatus(200);
    }

    public function test_project_create_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get('/projects/create');

        $response->assertStatus(200);
    }

    public function test_project_can_be_created(): void
    {
        $response = $this->actingAs($this->user)->post('/projects', [
            'name' => 'Test Project',
            'description' => 'Test description',
            'category_id' => $this->category->id,
            'priority' => 'High',
            'rag_status' => 'Green',
            'dev_status' => 'Not Started',
        ]);

        $response->assertRedirect('/projects');
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'category_id' => $this->category->id,
        ]);
    }

    public function test_project_can_be_viewed(): void
    {
        $project = Project::factory()->create([
            'category_id' => $this->category->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get("/projects/{$project->id}");

        $response->assertStatus(200);
    }

    public function test_project_can_be_updated(): void
    {
        $project = Project::factory()->create([
            'category_id' => $this->category->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->put("/projects/{$project->id}", [
            'name' => 'Updated Project Name',
            'description' => 'Updated description',
            'category_id' => $this->category->id,
            'priority' => 'Medium',
            'rag_status' => 'Amber',
            'dev_status' => 'In Development',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
            'rag_status' => 'Amber',
        ]);
    }

    public function test_project_can_be_deleted(): void
    {
        $project = Project::factory()->create([
            'category_id' => $this->category->id,
            'owner_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete("/projects/{$project->id}");

        $response->assertRedirect('/projects');
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_project_creates_phases_on_creation(): void
    {
        $project = Project::factory()->create([
            'category_id' => $this->category->id,
        ]);

        $this->assertCount(5, $project->phases);
        $this->assertEquals(['FRS', 'Development', 'Testing', 'UAT', 'Deployment'],
            $project->phases->pluck('phase')->toArray());
    }

    public function test_project_generates_code_automatically(): void
    {
        $project = Project::factory()->create([
            'category_id' => $this->category->id,
        ]);

        $this->assertMatchesRegularExpression('/^PRISM-\d{3}$/', $project->project_code);
    }
}
