<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();

        $category = Category::factory()->create();
        $this->project = Project::factory()->create([
            'category_id' => $category->id,
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_change_requests_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get('/change-requests');

        $response->assertStatus(200);
    }

    public function test_change_request_can_be_created(): void
    {
        $response = $this->actingAs($this->user)->post('/change-requests', [
            'project_id' => $this->project->id,
            'change_type' => 'Scope Change',
            'description' => 'Test change request description',
            'impact_analysis' => 'Impact analysis text',
        ]);

        $response->assertRedirect('/change-requests');
        $this->assertDatabaseHas('change_requests', [
            'project_id' => $this->project->id,
            'change_type' => 'Scope Change',
            'status' => 'Pending',
            'requested_by_id' => $this->user->id,
        ]);
    }

    public function test_change_request_can_be_approved(): void
    {
        $changeRequest = ChangeRequest::factory()->create([
            'project_id' => $this->project->id,
            'requested_by_id' => $this->user->id,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/change-requests/{$changeRequest->id}/approve");

        $response->assertRedirect();
        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequest->id,
            'status' => 'Approved',
            'approved_by_id' => $this->admin->id,
        ]);
    }

    public function test_change_request_can_be_rejected(): void
    {
        $changeRequest = ChangeRequest::factory()->create([
            'project_id' => $this->project->id,
            'requested_by_id' => $this->user->id,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/change-requests/{$changeRequest->id}/reject");

        $response->assertRedirect();
        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequest->id,
            'status' => 'Rejected',
        ]);
    }

    public function test_change_request_generates_code_automatically(): void
    {
        $changeRequest = ChangeRequest::factory()->create([
            'project_id' => $this->project->id,
            'requested_by_id' => $this->user->id,
        ]);

        $this->assertMatchesRegularExpression('/^CHG-\d+$/', $changeRequest->change_code);
    }
}
