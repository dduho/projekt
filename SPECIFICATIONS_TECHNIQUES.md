# 📋 Spécifications Techniques - Moov Project Manager

## Stack Technologique Détaillée

### Backend
```
PHP 8.2+
Laravel 11.x
PostgreSQL 15
Redis 7.x
Laravel Sanctum (Auth)
Laravel Reverb (WebSockets)
Laravel Horizon (Queues)
Maatwebsite/Excel (Import)
Spatie/Laravel-Permission (RBAC)
```

### Frontend
```
Vue.js 3.4+
Inertia.js 1.x
TailwindCSS 3.4
Pinia (State)
ApexCharts / Chart.js
Lucide Icons
VueUse
```

---

## 🗄️ Migrations de Base de Données

### Migration: categories
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#5C6BC0');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

### Migration: projects
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('business_area', 100)->nullable();
            $table->enum('priority', ['High', 'Medium', 'Low'])->default('Medium');
            $table->enum('frs_status', ['Draft', 'Review', 'Signoff'])->default('Draft');
            $table->enum('dev_status', [
                'Not Started', 
                'In Development', 
                'Testing', 
                'UAT', 
                'Deployed', 
                'On Hold'
            ])->default('Not Started');
            $table->string('current_progress', 100)->nullable();
            $table->text('blockers')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('planned_release', 50)->nullable();
            $table->date('submission_date')->nullable();
            $table->date('target_date')->nullable();
            $table->date('go_live_date')->nullable();
            $table->enum('rag_status', ['Green', 'Amber', 'Red'])->default('Green');
            $table->unsignedTinyInteger('completion_percent')->default(0);
            $table->string('service_type', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('last_update')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['rag_status', 'priority']);
            $table->index('category_id');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
```

### Migration: project_phases
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('phase', ['FRS', 'Development', 'Testing', 'UAT', 'Deployment']);
            $table->enum('status', ['Pending', 'In Progress', 'Completed', 'Blocked'])->default('Pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->unique(['project_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_phases');
    }
};
```

### Migration: risks
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->string('risk_code', 20)->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['Risk', 'Issue'])->default('Risk');
            $table->text('description');
            $table->enum('impact', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->enum('probability', ['Low', 'Medium', 'High'])->default('Medium');
            $table->enum('risk_score', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->text('mitigation_plan')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['Open', 'In Progress', 'Mitigated', 'Closed'])->default('Open');
            $table->timestamp('identified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'risk_score']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
```

### Migration: change_requests
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('change_code', 20)->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->enum('change_type', ['Scope Change', 'Schedule Change', 'Budget Change', 'Resource Change']);
            $table->text('description');
            $table->text('impact_analysis')->nullable();
            $table->foreignId('requested_by_id')->constrained('users');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['Pending', 'Under Review', 'Approved', 'Rejected'])->default('Pending');
            $table->timestamp('requested_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
```

### Migration: activity_logs
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('loggable'); // loggable_type, loggable_id
            $table->string('action', 50); // created, updated, deleted, status_changed
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['loggable_type', 'loggable_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
```

### Migration: comments
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable');
            $table->text('content');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(['commentable_type', 'commentable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
```

---

## 📦 Models Eloquent

### Model: Project
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_code',
        'name',
        'description',
        'category_id',
        'business_area',
        'priority',
        'frs_status',
        'dev_status',
        'current_progress',
        'blockers',
        'owner_id',
        'planned_release',
        'submission_date',
        'target_date',
        'go_live_date',
        'rag_status',
        'completion_percent',
        'service_type',
        'remarks',
        'last_update',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'target_date' => 'date',
        'go_live_date' => 'date',
        'last_update' => 'datetime',
        'completion_percent' => 'integer',
    ];

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Scopes
    public function scopeByRagStatus($query, string $status)
    {
        return $query->where('rag_status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeDeployed($query)
    {
        return $query->where('dev_status', 'Deployed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('dev_status', 'In Development');
    }

    public function scopeAwaitingAction($query)
    {
        return $query->whereIn('dev_status', ['On Hold', 'Not Started'])
                     ->orWhereNotNull('blockers');
    }

    public function scopeWithFrsSignoff($query)
    {
        return $query->where('frs_status', 'Signoff');
    }

    // Accessors
    public function getIsBlockedAttribute(): bool
    {
        return !empty($this->blockers);
    }

    public function getCriticalRisksCountAttribute(): int
    {
        return $this->risks()
            ->where('risk_score', 'Critical')
            ->where('status', 'Open')
            ->count();
    }

    // Auto-generate project code
    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->project_code)) {
                $lastCode = static::withTrashed()
                    ->where('project_code', 'like', 'MOOV-%')
                    ->orderByRaw('CAST(SUBSTRING(project_code, 6) AS UNSIGNED) DESC')
                    ->value('project_code');
                
                $nextNumber = $lastCode 
                    ? (int)substr($lastCode, 5) + 1 
                    : 1;
                
                $project->project_code = 'MOOV-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }
}
```

### Model: Risk
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Risk extends Model
{
    use HasFactory;

    protected $fillable = [
        'risk_code',
        'project_id',
        'type',
        'description',
        'impact',
        'probability',
        'risk_score',
        'mitigation_plan',
        'owner_id',
        'status',
        'identified_at',
        'resolved_at',
    ];

    protected $casts = [
        'identified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Auto-calculate risk score
    protected static function booted(): void
    {
        static::saving(function (Risk $risk) {
            $risk->risk_score = $risk->calculateRiskScore();
        });

        static::creating(function (Risk $risk) {
            if (empty($risk->risk_code)) {
                $lastCode = static::where('risk_code', 'like', 'RISK-%')
                    ->orderByRaw('CAST(SUBSTRING(risk_code, 6) AS UNSIGNED) DESC')
                    ->value('risk_code');
                
                $nextNumber = $lastCode ? (int)substr($lastCode, 5) + 1 : 1;
                $risk->risk_code = 'RISK-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            
            $risk->identified_at = $risk->identified_at ?? now();
        });
    }

    public function calculateRiskScore(): string
    {
        $matrix = [
            'Low' => ['Low' => 'Low', 'Medium' => 'Low', 'High' => 'Medium'],
            'Medium' => ['Low' => 'Medium', 'Medium' => 'Medium', 'High' => 'High'],
            'High' => ['Low' => 'Medium', 'Medium' => 'High', 'High' => 'Critical'],
            'Critical' => ['Low' => 'High', 'Medium' => 'Critical', 'High' => 'Critical'],
        ];

        return $matrix[$this->impact][$this->probability] ?? 'Medium';
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Scopes
    public function scopeCritical($query)
    {
        return $query->where('risk_score', 'Critical');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'Open');
    }
}
```

---

## 🎮 Controllers API

### ProjectController
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectCollection;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    public function index(Request $request): ProjectCollection
    {
        $projects = Project::query()
            ->with(['category', 'owner', 'phases'])
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('project_code', 'like', "%{$search}%")
            )
            ->when($request->category_id, fn($q, $id) => $q->where('category_id', $id))
            ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
            ->when($request->rag_status, fn($q, $s) => $q->where('rag_status', $s))
            ->when($request->dev_status, fn($q, $s) => $q->where('dev_status', $s))
            ->when($request->owner_id, fn($q, $id) => $q->where('owner_id', $id))
            ->orderBy($request->sort_by ?? 'project_code', $request->sort_dir ?? 'asc')
            ->paginate($request->per_page ?? 15);

        return new ProjectCollection($projects);
    }

    public function show(Project $project): ProjectResource
    {
        $project->load([
            'category',
            'owner',
            'phases',
            'risks' => fn($q) => $q->orderBy('risk_score', 'desc'),
            'changeRequests' => fn($q) => $q->latest(),
            'comments.user',
            'activities' => fn($q) => $q->latest()->limit(20),
        ]);

        return new ProjectResource($project);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated());

        return response()->json([
            'message' => 'Project created successfully',
            'data' => new ProjectResource($project),
        ], 201);
    }

    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projectService->update($project, $request->validated());

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($project),
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->projectService->delete($project);

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    public function updatePhase(Request $request, Project $project, string $phase): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:Pending,In Progress,Completed,Blocked',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $projectPhase = $this->projectService->updatePhase(
            $project,
            $phase,
            $request->status,
            $request->remarks
        );

        return response()->json([
            'message' => 'Phase updated successfully',
            'data' => $projectPhase,
        ]);
    }
}
```

### DashboardController
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Risk;
use App\Models\ChangeRequest;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function kpis(): JsonResponse
    {
        $totalProjects = Project::count();
        
        $kpis = [
            'total_projects' => $totalProjects,
            'deployed' => [
                'count' => Project::deployed()->count(),
                'percent' => $totalProjects > 0 
                    ? round(Project::deployed()->count() / $totalProjects * 100) 
                    : 0,
            ],
            'in_progress' => [
                'count' => Project::inProgress()->count(),
                'percent' => $totalProjects > 0 
                    ? round(Project::inProgress()->count() / $totalProjects * 100) 
                    : 0,
            ],
            'awaiting_action' => [
                'count' => Project::awaitingAction()->count(),
                'percent' => $totalProjects > 0 
                    ? round(Project::awaitingAction()->count() / $totalProjects * 100) 
                    : 0,
            ],
            'frs_signoff' => [
                'count' => Project::withFrsSignoff()->count(),
                'percent' => $totalProjects > 0 
                    ? round(Project::withFrsSignoff()->count() / $totalProjects * 100) 
                    : 0,
            ],
            'critical_risks' => Risk::critical()->open()->count(),
            'pending_changes' => ChangeRequest::where('status', 'Pending')->count(),
        ];

        return response()->json($kpis);
    }

    public function ragDistribution(): JsonResponse
    {
        $distribution = Project::select('rag_status', DB::raw('COUNT(*) as count'))
            ->groupBy('rag_status')
            ->pluck('count', 'rag_status')
            ->toArray();

        return response()->json([
            'Green' => $distribution['Green'] ?? 0,
            'Amber' => $distribution['Amber'] ?? 0,
            'Red' => $distribution['Red'] ?? 0,
        ]);
    }

    public function categoryDistribution(): JsonResponse
    {
        $distribution = Project::select('categories.name', DB::raw('COUNT(*) as count'))
            ->join('categories', 'projects.category_id', '=', 'categories.id')
            ->groupBy('categories.name')
            ->orderByDesc('count')
            ->get();

        return response()->json($distribution);
    }

    public function recentActivity(): JsonResponse
    {
        $activities = ActivityLog::with(['user', 'loggable'])
            ->latest()
            ->limit(15)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'user' => $activity->user?->name ?? 'System',
                    'action' => $activity->action,
                    'type' => class_basename($activity->loggable_type),
                    'subject' => $activity->loggable?->name ?? $activity->loggable?->project_code ?? 'N/A',
                    'changes' => $activity->changes,
                    'created_at' => $activity->created_at->diffForHumans(),
                ];
            });

        return response()->json($activities);
    }

    public function criticalProjects(): JsonResponse
    {
        $projects = Project::with(['category', 'owner'])
            ->where('rag_status', 'Red')
            ->orWhereHas('risks', fn($q) => $q->critical()->open())
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->name,
                'rag_status' => $p->rag_status,
                'category' => $p->category->name,
                'owner' => $p->owner?->name,
                'critical_risks' => $p->critical_risks_count,
                'blockers' => $p->blockers,
            ]);

        return response()->json($projects);
    }
}
```

---

## 📝 Form Requests

### ProjectRequest
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('project')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],
            'business_area' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', Rule::in(['High', 'Medium', 'Low'])],
            'frs_status' => ['required', Rule::in(['Draft', 'Review', 'Signoff'])],
            'dev_status' => ['required', Rule::in([
                'Not Started', 'In Development', 'Testing', 
                'UAT', 'Deployed', 'On Hold'
            ])],
            'current_progress' => ['nullable', 'string', 'max:100'],
            'blockers' => ['nullable', 'string', 'max:2000'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'planned_release' => ['nullable', 'string', 'max:50'],
            'submission_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:submission_date'],
            'go_live_date' => ['nullable', 'date'],
            'rag_status' => ['required', Rule::in(['Green', 'Amber', 'Red'])],
            'completion_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'service_type' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du projet est obligatoire.',
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'target_date.after_or_equal' => 'La date cible doit être après la date de soumission.',
        ];
    }
}
```

---

## 🔌 API Resources

### ProjectResource
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_code' => $this->project_code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'business_area' => $this->business_area,
            'priority' => $this->priority,
            'frs_status' => $this->frs_status,
            'dev_status' => $this->dev_status,
            'current_progress' => $this->current_progress,
            'blockers' => $this->blockers,
            'is_blocked' => $this->is_blocked,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'planned_release' => $this->planned_release,
            'submission_date' => $this->submission_date?->format('Y-m-d'),
            'target_date' => $this->target_date?->format('Y-m-d'),
            'go_live_date' => $this->go_live_date?->format('Y-m-d'),
            'rag_status' => $this->rag_status,
            'completion_percent' => $this->completion_percent,
            'service_type' => $this->service_type,
            'remarks' => $this->remarks,
            'last_update' => $this->last_update?->format('Y-m-d H:i'),
            'phases' => ProjectPhaseResource::collection($this->whenLoaded('phases')),
            'risks' => RiskResource::collection($this->whenLoaded('risks')),
            'risks_count' => $this->whenCounted('risks'),
            'critical_risks_count' => $this->critical_risks_count,
            'change_requests' => ChangeRequestResource::collection($this->whenLoaded('changeRequests')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
```

---

## 🎨 Composants Vue.js Glass

### GlassCard.vue
```vue
<template>
  <div 
    :class="[
      'glass-card',
      { 'glass-card--hoverable': hoverable },
      { 'glass-card--clickable': clickable },
      sizeClass,
    ]"
    @click="handleClick"
  >
    <div v-if="$slots.header" class="glass-card__header">
      <slot name="header" />
    </div>
    
    <div class="glass-card__body" :class="{ 'p-0': noPadding }">
      <slot />
    </div>
    
    <div v-if="$slots.footer" class="glass-card__footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  hoverable: { type: Boolean, default: false },
  clickable: { type: Boolean, default: false },
  size: { type: String, default: 'md', validator: v => ['sm', 'md', 'lg'].includes(v) },
  noPadding: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

const sizeClass = computed(() => `glass-card--${props.size}`);

const handleClick = () => {
  if (props.clickable) emit('click');
};
</script>

<style scoped>
.glass-card {
  @apply relative overflow-hidden;
  @apply bg-white/20 backdrop-blur-xl;
  @apply border border-white/30;
  @apply rounded-2xl shadow-xl;
  @apply transition-all duration-300 ease-out;
}

.glass-card--hoverable:hover {
  @apply bg-white/30;
  @apply shadow-2xl;
  @apply -translate-y-1;
}

.glass-card--clickable {
  @apply cursor-pointer;
}

.glass-card--sm {
  @apply rounded-xl;
}

.glass-card--lg {
  @apply rounded-3xl;
}

.glass-card__header {
  @apply px-6 py-4;
  @apply border-b border-white/20;
  @apply bg-white/10;
}

.glass-card__body {
  @apply px-6 py-5;
}

.glass-card__footer {
  @apply px-6 py-4;
  @apply border-t border-white/20;
  @apply bg-white/10;
}
</style>
```

### KpiCard.vue
```vue
<template>
  <GlassCard hoverable class="kpi-card">
    <div class="flex items-center justify-between">
      <div>
        <p class="kpi-card__label">{{ label }}</p>
        <p class="kpi-card__value">
          {{ formattedValue }}
          <span v-if="suffix" class="kpi-card__suffix">{{ suffix }}</span>
        </p>
        <p v-if="subtext" class="kpi-card__subtext" :class="subtextClass">
          <component :is="trendIcon" v-if="trend" class="w-4 h-4 inline mr-1" />
          {{ subtext }}
        </p>
      </div>
      <div :class="['kpi-card__icon', iconBgClass]">
        <component :is="icon" class="w-8 h-8" />
      </div>
    </div>
    
    <div v-if="showProgress" class="mt-4">
      <div class="kpi-card__progress-bar">
        <div 
          class="kpi-card__progress-fill" 
          :class="progressClass"
          :style="{ width: `${percent}%` }"
        />
      </div>
      <p class="kpi-card__progress-label">{{ percent }}% du total</p>
    </div>
  </GlassCard>
</template>

<script setup>
import { computed } from 'vue';
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';
import GlassCard from './GlassCard.vue';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], required: true },
  suffix: { type: String, default: '' },
  icon: { type: Object, required: true },
  color: { type: String, default: 'blue' },
  trend: { type: String, default: null, validator: v => [null, 'up', 'down', 'stable'].includes(v) },
  subtext: { type: String, default: '' },
  percent: { type: Number, default: null },
  showProgress: { type: Boolean, default: false },
});

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('fr-FR');
  }
  return props.value;
});

const iconBgClass = computed(() => {
  const colors = {
    blue: 'bg-blue-500/30 text-blue-100',
    green: 'bg-emerald-500/30 text-emerald-100',
    amber: 'bg-amber-500/30 text-amber-100',
    red: 'bg-red-500/30 text-red-100',
    purple: 'bg-purple-500/30 text-purple-100',
  };
  return colors[props.color] || colors.blue;
});

const progressClass = computed(() => {
  const colors = {
    blue: 'bg-blue-500',
    green: 'bg-emerald-500',
    amber: 'bg-amber-500',
    red: 'bg-red-500',
    purple: 'bg-purple-500',
  };
  return colors[props.color] || colors.blue;
});

const subtextClass = computed(() => {
  if (props.trend === 'up') return 'text-emerald-300';
  if (props.trend === 'down') return 'text-red-300';
  return 'text-white/60';
});

const trendIcon = computed(() => {
  if (props.trend === 'up') return TrendingUp;
  if (props.trend === 'down') return TrendingDown;
  return Minus;
});
</script>

<style scoped>
.kpi-card__label {
  @apply text-sm font-medium text-white/70 uppercase tracking-wide;
}

.kpi-card__value {
  @apply text-3xl font-bold text-white mt-1;
}

.kpi-card__suffix {
  @apply text-lg font-normal text-white/60;
}

.kpi-card__subtext {
  @apply text-sm mt-2;
}

.kpi-card__icon {
  @apply p-4 rounded-2xl;
}

.kpi-card__progress-bar {
  @apply h-2 bg-white/20 rounded-full overflow-hidden;
}

.kpi-card__progress-fill {
  @apply h-full rounded-full transition-all duration-500;
}

.kpi-card__progress-label {
  @apply text-xs text-white/50 mt-1;
}
</style>
```

### RagBadge.vue
```vue
<template>
  <span :class="['rag-badge', statusClass, sizeClass]">
    <span class="rag-badge__dot" />
    <span v-if="showLabel">{{ status }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { 
    type: String, 
    required: true,
    validator: v => ['Green', 'Amber', 'Red'].includes(v)
  },
  size: { 
    type: String, 
    default: 'md',
    validator: v => ['sm', 'md', 'lg'].includes(v)
  },
  showLabel: { type: Boolean, default: true },
});

const statusClass = computed(() => {
  const classes = {
    Green: 'rag-badge--green',
    Amber: 'rag-badge--amber',
    Red: 'rag-badge--red',
  };
  return classes[props.status];
});

const sizeClass = computed(() => `rag-badge--${props.size}`);
</script>

<style scoped>
.rag-badge {
  @apply inline-flex items-center gap-2 px-3 py-1 rounded-full;
  @apply font-medium backdrop-blur-sm;
}

.rag-badge__dot {
  @apply w-2 h-2 rounded-full animate-pulse;
}

.rag-badge--green {
  @apply bg-emerald-500/30 text-emerald-100;
}
.rag-badge--green .rag-badge__dot {
  @apply bg-emerald-400;
}

.rag-badge--amber {
  @apply bg-amber-500/30 text-amber-100;
}
.rag-badge--amber .rag-badge__dot {
  @apply bg-amber-400;
}

.rag-badge--red {
  @apply bg-red-500/30 text-red-100;
}
.rag-badge--red .rag-badge__dot {
  @apply bg-red-400;
}

.rag-badge--sm {
  @apply text-xs px-2 py-0.5;
}
.rag-badge--sm .rag-badge__dot {
  @apply w-1.5 h-1.5;
}

.rag-badge--lg {
  @apply text-base px-4 py-1.5;
}
.rag-badge--lg .rag-badge__dot {
  @apply w-3 h-3;
}
</style>
```

### GlassDataTable.vue
```vue
<template>
  <GlassCard no-padding class="overflow-hidden">
    <!-- Header avec recherche et filtres -->
    <div class="p-4 border-b border-white/20 bg-white/10">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="relative flex-1 min-w-64">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-white/50" />
          <input
            v-model="search"
            type="text"
            :placeholder="searchPlaceholder"
            class="glass-input pl-10 w-full"
            @input="debouncedSearch"
          />
        </div>
        
        <div class="flex items-center gap-2">
          <slot name="filters" />
          
          <button 
            v-if="exportable" 
            @click="$emit('export')"
            class="glass-button glass-button--secondary"
          >
            <Download class="w-4 h-4 mr-2" />
            Export
          </button>
          
          <button 
            v-if="creatable"
            @click="$emit('create')"
            class="glass-button glass-button--primary"
          >
            <Plus class="w-4 h-4 mr-2" />
            Nouveau
          </button>
        </div>
      </div>
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="bg-white/10">
            <th 
              v-for="column in columns" 
              :key="column.key"
              :class="[
                'glass-table__th',
                { 'cursor-pointer hover:bg-white/10': column.sortable }
              ]"
              @click="column.sortable && toggleSort(column.key)"
            >
              <div class="flex items-center gap-2">
                {{ column.label }}
                <template v-if="column.sortable">
                  <ArrowUpDown 
                    v-if="sortBy !== column.key" 
                    class="w-4 h-4 text-white/40"
                  />
                  <ArrowUp 
                    v-else-if="sortDir === 'asc'" 
                    class="w-4 h-4 text-white"
                  />
                  <ArrowDown 
                    v-else 
                    class="w-4 h-4 text-white"
                  />
                </template>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="(row, index) in data" 
            :key="row.id || index"
            class="glass-table__row"
            @click="$emit('row-click', row)"
          >
            <td 
              v-for="column in columns" 
              :key="column.key"
              class="glass-table__td"
            >
              <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                {{ row[column.key] }}
              </slot>
            </td>
          </tr>
          
          <tr v-if="data.length === 0">
            <td :colspan="columns.length" class="glass-table__td text-center py-12">
              <div class="text-white/50">
                <Database class="w-12 h-12 mx-auto mb-3 opacity-50" />
                <p>Aucune donnée disponible</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <div 
      v-if="pagination" 
      class="p-4 border-t border-white/20 bg-white/10 flex items-center justify-between"
    >
      <p class="text-sm text-white/60">
        Affichage {{ pagination.from }}-{{ pagination.to }} sur {{ pagination.total }}
      </p>
      
      <div class="flex items-center gap-2">
        <button 
          :disabled="!pagination.prev_page_url"
          @click="$emit('page-change', pagination.current_page - 1)"
          class="glass-button glass-button--sm"
        >
          <ChevronLeft class="w-4 h-4" />
        </button>
        
        <span class="px-4 text-white">
          Page {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        
        <button 
          :disabled="!pagination.next_page_url"
          @click="$emit('page-change', pagination.current_page + 1)"
          class="glass-button glass-button--sm"
        >
          <ChevronRight class="w-4 h-4" />
        </button>
      </div>
    </div>
  </GlassCard>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { 
  Search, Download, Plus, ArrowUpDown, ArrowUp, ArrowDown,
  ChevronLeft, ChevronRight, Database 
} from 'lucide-vue-next';
import GlassCard from './GlassCard.vue';

const props = defineProps({
  columns: { type: Array, required: true },
  data: { type: Array, required: true },
  pagination: { type: Object, default: null },
  searchPlaceholder: { type: String, default: 'Rechercher...' },
  exportable: { type: Boolean, default: false },
  creatable: { type: Boolean, default: false },
});

const emit = defineEmits(['search', 'sort', 'page-change', 'row-click', 'export', 'create']);

const search = ref('');
const sortBy = ref(null);
const sortDir = ref('asc');

const debouncedSearch = useDebounceFn(() => {
  emit('search', search.value);
}, 300);

const toggleSort = (key) => {
  if (sortBy.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = key;
    sortDir.value = 'asc';
  }
  emit('sort', { key: sortBy.value, dir: sortDir.value });
};
</script>

<style scoped>
.glass-table__th {
  @apply px-4 py-3 text-left text-sm font-semibold text-white/80;
  @apply uppercase tracking-wider;
}

.glass-table__row {
  @apply border-b border-white/10 transition-colors;
  @apply hover:bg-white/10 cursor-pointer;
}

.glass-table__td {
  @apply px-4 py-3 text-sm text-white/90;
}

.glass-input {
  @apply bg-white/10 border border-white/20 rounded-lg;
  @apply px-4 py-2 text-white placeholder-white/40;
  @apply focus:outline-none focus:ring-2 focus:ring-white/30;
  @apply transition-all duration-200;
}

.glass-button {
  @apply inline-flex items-center px-4 py-2 rounded-lg;
  @apply font-medium transition-all duration-200;
  @apply focus:outline-none focus:ring-2 focus:ring-white/30;
}

.glass-button--primary {
  @apply bg-blue-500/80 text-white hover:bg-blue-500;
}

.glass-button--secondary {
  @apply bg-white/20 text-white hover:bg-white/30;
}

.glass-button--sm {
  @apply px-2 py-1 text-sm;
}

.glass-button:disabled {
  @apply opacity-50 cursor-not-allowed;
}
</style>
```

---

## 📥 Service d'Import Excel

### ExcelImportService
```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Category;
use App\Models\Risk;
use App\Models\ChangeRequest;
use App\Models\ProjectPhase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportService
{
    private array $stats = [
        'projects' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'risks' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'changes' => ['created' => 0, 'updated' => 0, 'errors' => 0],
    ];

    private array $errors = [];

    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        
        DB::beginTransaction();
        
        try {
            // Import dans l'ordre des dépendances
            $this->importProjectRegister($spreadsheet->getSheetByName('PROJECT REGISTER'));
            $this->importStatusTracking($spreadsheet->getSheetByName('STATUS TRACKING'));
            $this->importRisks($spreadsheet->getSheetByName('RISK & ISSUES LOG'));
            $this->importChanges($spreadsheet->getSheetByName('CHANGE LOG'));
            
            DB::commit();
            
            return [
                'success' => true,
                'stats' => $this->stats,
                'errors' => $this->errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Excel import failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => $this->stats,
                'errors' => $this->errors,
            ];
        }
    }

    private function importProjectRegister($sheet): void
    {
        if (!$sheet) return;
        
        $rows = $sheet->toArray();
        $headers = null;
        
        foreach ($rows as $index => $row) {
            // Trouver la ligne d'en-tête (contient "ID")
            if ($headers === null && isset($row[0]) && $row[0] === 'ID') {
                $headers = $this->mapHeaders($row);
                continue;
            }
            
            if ($headers === null || empty($row[0]) || !str_starts_with($row[0], 'MOOV-')) {
                continue;
            }
            
            try {
                $data = $this->mapRowToData($row, $headers);
                $this->upsertProject($data);
                $this->stats['projects']['created']++;
            } catch (\Exception $e) {
                $this->errors[] = "Ligne {$index}: " . $e->getMessage();
                $this->stats['projects']['errors']++;
            }
        }
    }

    private function mapHeaders(array $row): array
    {
        $mapping = [];
        foreach ($row as $index => $header) {
            $key = strtolower(trim($header ?? ''));
            $key = str_replace([' ', '-', '/'], '_', $key);
            $mapping[$key] = $index;
        }
        return $mapping;
    }

    private function mapRowToData(array $row, array $headers): array
    {
        return [
            'project_code' => $row[$headers['id']] ?? null,
            'name' => $row[$headers['project_name']] ?? null,
            'submission_date' => $this->parseDate($row[$headers['submission_date']] ?? null),
            'category_name' => $row[$headers['category']] ?? 'General',
            'business_area' => $row[$headers['business_area']] ?? null,
            'priority' => $this->normalizePriority($row[$headers['priority']] ?? 'Medium'),
            'description' => $row[$headers['description']] ?? null,
            'planned_release' => $row[$headers['planned_release']] ?? null,
            'frs_status' => $this->normalizeFrsStatus($row[$headers['frs_status']] ?? 'Draft'),
            'dev_status' => $this->normalizeDevStatus($row[$headers['development_status']] ?? 'Not Started'),
            'current_progress' => $row[$headers['current_progress']] ?? null,
            'blockers' => $row[$headers['blockers']] ?? null,
            'target_date' => $this->parseDate($row[$headers['target_date']] ?? null),
            'rag_status' => $this->normalizeRagStatus($row[$headers['rag_status']] ?? 'Green'),
        ];
    }

    private function upsertProject(array $data): Project
    {
        // Trouver ou créer la catégorie
        $category = Category::firstOrCreate(
            ['name' => $data['category_name']],
            ['slug' => \Str::slug($data['category_name'])]
        );
        
        return Project::updateOrCreate(
            ['project_code' => $data['project_code']],
            [
                'name' => $data['name'],
                'description' => $data['description'],
                'category_id' => $category->id,
                'business_area' => $data['business_area'],
                'priority' => $data['priority'],
                'frs_status' => $data['frs_status'],
                'dev_status' => $data['dev_status'],
                'current_progress' => $data['current_progress'],
                'blockers' => $data['blockers'],
                'planned_release' => $data['planned_release'],
                'submission_date' => $data['submission_date'],
                'target_date' => $data['target_date'],
                'rag_status' => $data['rag_status'],
                'last_update' => now(),
            ]
        );
    }

    private function importStatusTracking($sheet): void
    {
        if (!$sheet) return;
        
        $rows = $sheet->toArray();
        $headers = null;
        
        foreach ($rows as $row) {
            if ($headers === null && isset($row[0]) && $row[0] === 'Project ID') {
                $headers = $this->mapHeaders($row);
                continue;
            }
            
            if ($headers === null || empty($row[0]) || !str_starts_with($row[0], 'MOOV-')) {
                continue;
            }
            
            $projectCode = $row[$headers['project_id']];
            $project = Project::where('project_code', $projectCode)->first();
            
            if (!$project) continue;
            
            // Mise à jour des phases
            $phases = ['FRS', 'Development', 'Testing', 'UAT', 'Deployment'];
            foreach ($phases as $phase) {
                $phaseKey = strtolower($phase);
                $status = $this->parsePhaseStatus($row[$headers[$phaseKey]] ?? null);
                
                ProjectPhase::updateOrCreate(
                    ['project_id' => $project->id, 'phase' => $phase],
                    [
                        'status' => $status,
                        'completed_at' => $status === 'Completed' ? now() : null,
                    ]
                );
            }
            
            // Mise à jour du pourcentage
            $completion = intval($row[$headers['completion_%']] ?? 0);
            $project->update(['completion_percent' => min(100, max(0, $completion))]);
        }
    }

    private function importRisks($sheet): void
    {
        if (!$sheet) return;
        
        $rows = $sheet->toArray();
        $headers = null;
        
        foreach ($rows as $index => $row) {
            if ($headers === null && isset($row[0]) && $row[0] === 'ID') {
                $headers = $this->mapHeaders($row);
                continue;
            }
            
            if ($headers === null || empty($row[0]) || !str_starts_with($row[0], 'RISK-')) {
                continue;
            }
            
            try {
                $projectCode = $row[$headers['related_project']] ?? null;
                $project = Project::where('project_code', $projectCode)->first();
                
                if (!$project) {
                    throw new \Exception("Projet {$projectCode} non trouvé");
                }
                
                Risk::updateOrCreate(
                    ['risk_code' => $row[$headers['id']]],
                    [
                        'project_id' => $project->id,
                        'type' => $this->normalizeRiskType($row[$headers['type']] ?? 'Risk'),
                        'description' => $row[$headers['description']] ?? '',
                        'impact' => $this->normalizeImpact($row[$headers['impact']] ?? 'Medium'),
                        'probability' => $this->normalizeProbability($row[$headers['probability']] ?? 'Medium'),
                        'mitigation_plan' => $row[$headers['mitigation_plan']] ?? null,
                        'status' => $this->normalizeRiskStatus($row[$headers['status']] ?? 'Open'),
                    ]
                );
                
                $this->stats['risks']['created']++;
            } catch (\Exception $e) {
                $this->errors[] = "Risque ligne {$index}: " . $e->getMessage();
                $this->stats['risks']['errors']++;
            }
        }
    }

    private function importChanges($sheet): void
    {
        if (!$sheet) return;
        
        $rows = $sheet->toArray();
        $headers = null;
        
        foreach ($rows as $index => $row) {
            if ($headers === null && isset($row[0]) && $row[0] === 'Change ID') {
                $headers = $this->mapHeaders($row);
                continue;
            }
            
            if ($headers === null || empty($row[0]) || !str_starts_with($row[0], 'CHG-')) {
                continue;
            }
            
            // Ignorer les lignes vides
            if (empty($row[$headers['project_id']])) continue;
            
            try {
                $projectCode = $row[$headers['project_id']];
                $project = Project::where('project_code', $projectCode)->first();
                
                if (!$project) {
                    throw new \Exception("Projet {$projectCode} non trouvé");
                }
                
                ChangeRequest::updateOrCreate(
                    ['change_code' => $row[$headers['change_id']]],
                    [
                        'project_id' => $project->id,
                        'change_type' => $this->normalizeChangeType($row[$headers['change_type']] ?? 'Scope Change'),
                        'description' => $row[$headers['description']] ?? '',
                        'requested_by_id' => 1, // Admin par défaut
                        'status' => $this->normalizeChangeStatus($row[$headers['status']] ?? 'Pending'),
                        'requested_at' => $this->parseDate($row[$headers['date']] ?? null) ?? now(),
                    ]
                );
                
                $this->stats['changes']['created']++;
            } catch (\Exception $e) {
                $this->errors[] = "Changement ligne {$index}: " . $e->getMessage();
                $this->stats['changes']['errors']++;
            }
        }
    }

    // Méthodes de normalisation
    private function parseDate($value): ?string
    {
        if (empty($value)) return null;
        
        // Excel date serial number
        if (is_numeric($value)) {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            return $date->format('Y-m-d');
        }
        
        // Parse string date
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizePriority(string $value): string
    {
        $map = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];
        return $map[strtolower(trim($value))] ?? 'Medium';
    }

    private function normalizeRagStatus(string $value): string
    {
        $map = ['green' => 'Green', 'amber' => 'Amber', 'red' => 'Red'];
        return $map[strtolower(trim($value))] ?? 'Green';
    }

    private function normalizeFrsStatus(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'sign')) return 'Signoff';
        if (str_contains($val, 'review')) return 'Review';
        return 'Draft';
    }

    private function normalizeDevStatus(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'deploy')) return 'Deployed';
        if (str_contains($val, 'uat')) return 'UAT';
        if (str_contains($val, 'test')) return 'Testing';
        if (str_contains($val, 'develop') || str_contains($val, 'progress')) return 'In Development';
        if (str_contains($val, 'hold')) return 'On Hold';
        return 'Not Started';
    }

    private function parsePhaseStatus(?string $value): string
    {
        if (empty($value)) return 'Pending';
        $val = trim($value);
        if ($val === '✓' || strtolower($val) === 'completed') return 'Completed';
        if ($val === '-' || strtolower($val) === 'pending') return 'Pending';
        if (strtolower($val) === 'blocked') return 'Blocked';
        return 'In Progress';
    }

    private function normalizeRiskType(string $value): string
    {
        return strtolower(trim($value)) === 'issue' ? 'Issue' : 'Risk';
    }

    private function normalizeImpact(string $value): string
    {
        $map = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
        return $map[strtolower(trim($value))] ?? 'Medium';
    }

    private function normalizeProbability(string $value): string
    {
        $map = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
        return $map[strtolower(trim($value))] ?? 'Medium';
    }

    private function normalizeRiskStatus(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'closed')) return 'Closed';
        if (str_contains($val, 'mitigat')) return 'Mitigated';
        if (str_contains($val, 'progress')) return 'In Progress';
        return 'Open';
    }

    private function normalizeChangeType(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'schedule')) return 'Schedule Change';
        if (str_contains($val, 'budget')) return 'Budget Change';
        if (str_contains($val, 'resource')) return 'Resource Change';
        return 'Scope Change';
    }

    private function normalizeChangeStatus(string $value): string
    {
        $val = strtolower(trim($value));
        if (str_contains($val, 'approved')) return 'Approved';
        if (str_contains($val, 'reject')) return 'Rejected';
        if (str_contains($val, 'review')) return 'Under Review';
        return 'Pending';
    }
}
```

---

## 🛣️ Routes API

### routes/api.php
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    DashboardController,
    ProjectController,
    RiskController,
    ChangeRequestController,
    CategoryController,
    UserController,
    ImportController,
    ExportController,
    NotificationController,
};

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // User
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/kpis', [DashboardController::class, 'kpis']);
        Route::get('/charts/rag', [DashboardController::class, 'ragDistribution']);
        Route::get('/charts/categories', [DashboardController::class, 'categoryDistribution']);
        Route::get('/charts/timeline', [DashboardController::class, 'deploymentTimeline']);
        Route::get('/activity', [DashboardController::class, 'recentActivity']);
        Route::get('/critical', [DashboardController::class, 'criticalProjects']);
        Route::get('/deadlines', [DashboardController::class, 'upcomingDeadlines']);
    });
    
    // Projects
    Route::apiResource('projects', ProjectController::class);
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/phases', [ProjectController::class, 'phases']);
        Route::put('/phases/{phase}', [ProjectController::class, 'updatePhase']);
        Route::get('/risks', [ProjectController::class, 'risks']);
        Route::get('/changes', [ProjectController::class, 'changes']);
        Route::get('/activity', [ProjectController::class, 'activity']);
        Route::post('/comments', [ProjectController::class, 'addComment']);
        Route::post('/duplicate', [ProjectController::class, 'duplicate']);
        Route::post('/archive', [ProjectController::class, 'archive']);
    });
    
    // Risks
    Route::apiResource('risks', RiskController::class);
    Route::put('/risks/{risk}/status', [RiskController::class, 'updateStatus']);
    Route::get('/risks-matrix', [RiskController::class, 'matrix']);
    
    // Change Requests
    Route::apiResource('changes', ChangeRequestController::class);
    Route::put('/changes/{change}/approve', [ChangeRequestController::class, 'approve']);
    Route::put('/changes/{change}/reject', [ChangeRequestController::class, 'reject']);
    
    // Categories
    Route::apiResource('categories', CategoryController::class);
    
    // Users (Admin only)
    Route::middleware('can:manage-users')->group(function () {
        Route::apiResource('users', UserController::class);
    });
    
    // Import/Export
    Route::post('/import/excel', [ImportController::class, 'excel']);
    Route::post('/import/validate', [ImportController::class, 'validate']);
    Route::get('/export/projects', [ExportController::class, 'projects']);
    Route::get('/export/risks', [ExportController::class, 'risks']);
    Route::get('/export/portfolio', [ExportController::class, 'portfolio']);
    
    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
```

---

## 🐳 Configuration Docker

### docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: moov_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - moov_network
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    container_name: moov_nginx
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/conf.d:/etc/nginx/conf.d
    networks:
      - moov_network
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: moov_mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: moov_projects
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_USER: moov
      MYSQL_PASSWORD: secret
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - moov_network

  redis:
    image: redis:alpine
    container_name: moov_redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - moov_network

  mailpit:
    image: axllent/mailpit
    container_name: moov_mailpit
    restart: unless-stopped
    ports:
      - "1025:1025"
      - "8025:8025"
    networks:
      - moov_network

networks:
  moov_network:
    driver: bridge

volumes:
  mysql_data:
```

### Dockerfile
```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Install Node dependencies and build
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

---

## ✅ Tests

### Tests Feature: ProjectTest
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_can_list_projects(): void
    {
        Project::factory()->count(5)->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects');
        
        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'project_code', 'name', 'rag_status', 'priority']
                ],
                'meta' => ['current_page', 'total']
            ]);
    }

    public function test_can_create_project(): void
    {
        $data = [
            'name' => 'Test Project',
            'category_id' => $this->category->id,
            'priority' => 'High',
            'frs_status' => 'Draft',
            'dev_status' => 'Not Started',
            'rag_status' => 'Green',
            'completion_percent' => 0,
        ];
        
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/projects', $data);
        
        $response->assertCreated()
            ->assertJsonPath('data.name', 'Test Project')
            ->assertJsonPath('data.project_code', 'MOOV-001');
        
        $this->assertDatabaseHas('projects', ['name' => 'Test Project']);
    }

    public function test_can_update_project(): void
    {
        $project = Project::factory()->create(['category_id' => $this->category->id]);
        
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/projects/{$project->id}", [
                'name' => 'Updated Name',
                'category_id' => $this->category->id,
                'priority' => 'Low',
                'frs_status' => 'Signoff',
                'dev_status' => 'Deployed',
                'rag_status' => 'Green',
                'completion_percent' => 100,
            ]);
        
        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_filter_projects_by_rag_status(): void
    {
        Project::factory()->create(['category_id' => $this->category->id, 'rag_status' => 'Green']);
        Project::factory()->create(['category_id' => $this->category->id, 'rag_status' => 'Red']);
        Project::factory()->create(['category_id' => $this->category->id, 'rag_status' => 'Red']);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/projects?rag_status=Red');
        
        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_dashboard_kpis_are_calculated_correctly(): void
    {
        Project::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'dev_status' => 'Deployed'
        ]);
        Project::factory()->count(2)->create([
            'category_id' => $this->category->id,
            'dev_status' => 'In Development'
        ]);
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/dashboard/kpis');
        
        $response->assertOk()
            ->assertJsonPath('total_projects', 5)
            ->assertJsonPath('deployed.count', 3)
            ->assertJsonPath('in_progress.count', 2);
    }
}
```

---

## 📋 Checklist de Développement

### Phase 1: Setup (Semaine 1)
- [ ] Initialiser projet Laravel 11
- [ ] Configurer Docker & docker-compose
- [ ] Configurer base de données MySQL
- [ ] Installer et configurer Redis
- [ ] Configurer Laravel Sanctum
- [ ] Configurer CI/CD (GitHub Actions)

### Phase 2: Backend Core (Semaines 2-3)
- [ ] Créer toutes les migrations
- [ ] Créer les Models avec relations
- [ ] Implémenter les Services
- [ ] Créer les Form Requests
- [ ] Créer les API Resources
- [ ] Implémenter les Controllers
- [ ] Configurer les routes API

### Phase 3: Frontend Base (Semaines 4-5)
- [ ] Configurer Vue.js 3 + Inertia
- [ ] Configurer TailwindCSS avec thème Glass
- [ ] Créer le Layout principal
- [ ] Créer les composants Glass de base
- [ ] Implémenter la navigation
- [ ] Configurer Pinia stores

### Phase 4-8: Modules (Semaines 6-10)
- [ ] Module Dashboard
- [ ] Module Projects
- [ ] Module Status Tracking
- [ ] Module Risks
- [ ] Module Changes
- [ ] Module Governance

### Phase 9: Import (Semaine 11)
- [ ] Implémenter ExcelImportService
- [ ] Interface d'import avec preview
- [ ] Validation et rapport d'erreurs

### Phase 10: Notifications (Semaine 12)
- [ ] Configurer Laravel Reverb
- [ ] Implémenter les Events
- [ ] Créer les notifications
- [ ] Interface notifications temps réel

### Phase 11: Tests (Semaines 13-14)
- [ ] Tests unitaires (Models, Services)
- [ ] Tests Feature (API)
- [ ] Tests E2E (Cypress/Playwright)

### Phase 12: Déploiement (Semaine 15-16)
- [ ] Configuration production
- [ ] Documentation complète
- [ ] Formation utilisateurs
- [ ] Go-live
