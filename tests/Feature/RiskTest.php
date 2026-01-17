<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\Risk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $category = Category::factory()->create();
        $this->project = Project::factory()->create([
            'category_id' => $category->id,
            'owner_id' => $this->user->id,
        ]);
    }

    public function test_risks_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get('/risks');

        $response->assertStatus(200);
    }

    public function test_risks_matrix_page_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get('/risks/matrix');

        $response->assertStatus(200);
    }

    public function test_risk_can_be_created(): void
    {
        $response = $this->actingAs($this->user)->post('/risks', [
            'project_id' => $this->project->id,
            'type' => 'Risk',
            'description' => 'Test risk description',
            'impact' => 'High',
            'probability' => 'Medium',
            'mitigation_plan' => 'Mitigation steps',
        ]);

        $response->assertRedirect('/risks');
        $this->assertDatabaseHas('risks', [
            'project_id' => $this->project->id,
            'description' => 'Test risk description',
        ]);
    }

    public function test_risk_score_is_calculated_automatically(): void
    {
        $risk = Risk::factory()->create([
            'project_id' => $this->project->id,
            'impact' => 'High',
            'probability' => 'High',
        ]);

        // High impact + High probability should equal Critical or High
        $this->assertContains($risk->risk_score, ['High', 'Critical']);
    }

    public function test_risk_generates_code_automatically(): void
    {
        $risk = Risk::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $this->assertMatchesRegularExpression('/^RISK-\d+$/', $risk->risk_code);
    }
}
