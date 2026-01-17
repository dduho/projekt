<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\Risk;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $service;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DashboardService::class);
        $this->category = Category::factory()->create();
        Cache::flush();
    }

    public function test_get_kpis_returns_correct_structure(): void
    {
        $kpis = $this->service->getKpis();

        $this->assertArrayHasKey('total_projects', $kpis);
        $this->assertArrayHasKey('deployed', $kpis);
        $this->assertArrayHasKey('in_progress', $kpis);
        $this->assertArrayHasKey('critical_risks', $kpis);
        $this->assertArrayHasKey('pending_changes', $kpis);
    }

    public function test_get_kpis_counts_projects_correctly(): void
    {
        Project::factory()->count(3)->deployed()->create(['category_id' => $this->category->id]);
        Project::factory()->count(2)->inDevelopment()->create(['category_id' => $this->category->id]);
        Project::factory()->count(1)->create([
            'category_id' => $this->category->id,
            'dev_status' => 'Testing'
        ]);

        Cache::flush();
        $kpis = $this->service->getKpis();

        $this->assertEquals(6, $kpis['total_projects']);
        $this->assertEquals(3, $kpis['deployed']['count']);
        $this->assertEquals(2, $kpis['in_progress']['count']);
        $this->assertEquals(1, $kpis['testing']['count']);
    }

    public function test_get_rag_distribution(): void
    {
        Project::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'rag_status' => 'Green'
        ]);
        Project::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'rag_status' => 'Amber'
        ]);
        Project::factory()->count(2)->create([
            'category_id' => $this->category->id,
            'rag_status' => 'Red'
        ]);

        Cache::flush();
        $distribution = $this->service->getRagDistribution();

        $this->assertEquals(5, $distribution['Green']);
        $this->assertEquals(3, $distribution['Amber']);
        $this->assertEquals(2, $distribution['Red']);
    }

    public function test_get_category_distribution(): void
    {
        $category2 = Category::factory()->create(['name' => 'Mobile']);

        Project::factory()->count(4)->create(['category_id' => $this->category->id]);
        Project::factory()->count(2)->create(['category_id' => $category2->id]);

        Cache::flush();
        $distribution = $this->service->getCategoryDistribution();

        $this->assertCount(2, $distribution);
    }

    public function test_counts_critical_risks(): void
    {
        $project = Project::factory()->create(['category_id' => $this->category->id]);

        Risk::factory()->count(3)->critical()->create(['project_id' => $project->id]);
        Risk::factory()->count(2)->resolved()->create(['project_id' => $project->id]);

        Cache::flush();
        $kpis = $this->service->getKpis();

        $this->assertEquals(3, $kpis['critical_risks']);
    }

    public function test_counts_pending_change_requests(): void
    {
        $project = Project::factory()->create(['category_id' => $this->category->id]);
        $user = User::factory()->create();

        ChangeRequest::factory()->count(2)->create([
            'project_id' => $project->id,
            'requested_by_id' => $user->id,
            'status' => 'Pending',
        ]);
        ChangeRequest::factory()->count(1)->approved()->create([
            'project_id' => $project->id,
            'requested_by_id' => $user->id,
        ]);

        Cache::flush();
        $kpis = $this->service->getKpis();

        $this->assertEquals(2, $kpis['pending_changes']);
    }

    public function test_cache_is_used_for_kpis(): void
    {
        $this->service->getKpis();

        $this->assertTrue(Cache::has('dashboard.kpis'));
    }

    public function test_clear_cache_removes_all_dashboard_cache(): void
    {
        $this->service->getKpis();
        $this->service->getRagDistribution();

        $this->service->clearCache();

        $this->assertFalse(Cache::has('dashboard.kpis'));
        $this->assertFalse(Cache::has('dashboard.rag'));
    }
}
