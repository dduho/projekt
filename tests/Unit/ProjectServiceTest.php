<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectService $service;
    protected Category $category;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProjectService::class);
        $this->category = Category::factory()->create();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_projects(): void
    {
        Project::factory()->count(5)->create(['category_id' => $this->category->id]);

        $result = $this->service->list();

        $this->assertCount(5, $result->items());
    }

    public function test_can_filter_projects_by_rag_status(): void
    {
        Project::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'rag_status' => 'Green'
        ]);
        Project::factory()->count(2)->create([
            'category_id' => $this->category->id,
            'rag_status' => 'Red'
        ]);

        $result = $this->service->list(['rag_status' => 'Red']);

        $this->assertCount(2, $result->items());
    }

    public function test_can_search_projects(): void
    {
        Project::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Mobile Payment App'
        ]);
        Project::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Web Portal'
        ]);

        $result = $this->service->list(['search' => 'Mobile']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Mobile Payment App', $result->items()[0]->name);
    }

    public function test_can_create_project(): void
    {
        $data = [
            'name' => 'New Test Project',
            'description' => 'Test description',
            'category_id' => $this->category->id,
            'priority' => 'High',
            'rag_status' => 'Green',
            'dev_status' => 'Not Started',
        ];

        $project = $this->service->create($data);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('New Test Project', $project->name);
        $this->assertNotNull($project->project_code);
    }

    public function test_can_update_project(): void
    {
        $project = Project::factory()->create(['category_id' => $this->category->id]);

        $updated = $this->service->update($project, [
            'name' => 'Updated Name',
            'rag_status' => 'Amber',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('Amber', $updated->rag_status);
    }

    public function test_can_duplicate_project(): void
    {
        $original = Project::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Original Project',
        ]);

        $copy = $this->service->duplicate($original);

        $this->assertNotEquals($original->id, $copy->id);
        $this->assertNotEquals($original->project_code, $copy->project_code);
        $this->assertEquals('Original Project (Copy)', $copy->name);
        $this->assertEquals('Not Started', $copy->dev_status);
    }

    public function test_can_update_project_phase(): void
    {
        $project = Project::factory()->create(['category_id' => $this->category->id]);

        $phase = $this->service->updatePhase($project, 'FRS', 'In Progress', 'Started working on FRS');

        $this->assertEquals('In Progress', $phase->status);
        $this->assertNotNull($phase->started_at);
        $this->assertEquals('Started working on FRS', $phase->remarks);
    }
}
