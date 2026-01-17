# CAHIER DES CHARGES DEVELOPPEMENT

## PRISM - Project Intelligence & Status Manager

---

### Identite du Projet

| Element | Description |
|---------|-------------|
| **Nom** | PRISM - Project Intelligence & Status Manager |
| **Tagline** | "Clarity through transparency" |
| **Client** | MOOV MONEY TOGO |
| **Version** | 1.0.0 |
| **Date** | Janvier 2026 |

### Concept du Logo

```
    ◇
   /|\
  / | \
 /  |  \
◇───◇───◇
 \  |  /
  \ | /
   \|/
    ◇

PRISM
```

**Design du logo :**
- Forme de prisme geometrique en 3D avec effet glass
- Degradee de couleurs : #667eea (violet) vers #764ba2 (magenta)
- Reflets lumineux simulant la refraction de la lumiere
- Police moderne sans-serif pour "PRISM"
- Effet glassmorphism sur le logo lui-meme

---

## TABLE DES MATIERES

1. [Architecture Technique](#1-architecture-technique)
2. [Configuration Docker](#2-configuration-docker)
3. [Base de Donnees](#3-base-de-donnees)
4. [Backend Laravel](#4-backend-laravel)
5. [Frontend Vue.js](#5-frontend-vuejs)
6. [Modules Fonctionnels](#6-modules-fonctionnels)
7. [API REST](#7-api-rest)
8. [Securite](#8-securite)
9. [Import de Donnees](#9-import-de-donnees)
10. [Tests](#10-tests)
11. [Deploiement](#11-deploiement)
12. [Checklist de Livraison](#12-checklist-de-livraison)

---

## 1. ARCHITECTURE TECHNIQUE

### 1.1 Stack Technologique

#### Backend
| Technologie | Version | Role |
|-------------|---------|------|
| PHP | 8.2+ | Langage serveur |
| Laravel | 11.x | Framework PHP |
| PostgreSQL | 15 | Base de donnees principale |
| Redis | 7.x | Cache & Sessions & Queues |
| Laravel Sanctum | - | Authentification API/SPA |
| Laravel Reverb | - | WebSockets temps reel |
| Laravel Horizon | - | Gestion des queues |
| Spatie Permission | - | RBAC (Roles & Permissions) |
| Maatwebsite Excel | - | Import/Export Excel |

#### Frontend
| Technologie | Version | Role |
|-------------|---------|------|
| Vue.js | 3.4+ | Framework UI |
| Inertia.js | 1.x | Bridge Laravel/Vue |
| TailwindCSS | 3.4 | Framework CSS |
| Pinia | - | State Management |
| ApexCharts | - | Graphiques |
| Lucide Icons | - | Icones |
| VueUse | - | Utilitaires Vue |

#### Infrastructure
| Technologie | Role |
|-------------|------|
| Docker | Containerisation |
| Nginx | Serveur web |
| GitHub Actions | CI/CD |

### 1.2 Schema d'Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         NGINX (Port 80/443)                      │
└─────────────────────────────┬───────────────────────────────────┘
                              │
┌─────────────────────────────▼───────────────────────────────────┐
│                    LARAVEL APP (PHP-FPM)                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │ Controllers │  │  Services   │  │ Inertia.js + Vue.js     │  │
│  │    API      │  │   Domain    │  │  Frontend SPA           │  │
│  └──────┬──────┘  └──────┬──────┘  └─────────────────────────┘  │
│         │                │                                       │
│  ┌──────▼────────────────▼──────┐                               │
│  │     Eloquent ORM + Models     │                               │
│  └──────────────┬───────────────┘                               │
└─────────────────┼───────────────────────────────────────────────┘
                  │
     ┌────────────┼────────────┐
     │            │            │
┌────▼────┐  ┌────▼────┐  ┌────▼────┐
│PostgreSQL│  │  Redis  │  │ Reverb  │
│   :5432  │  │  :6379  │  │  :8080  │
└──────────┘  └─────────┘  └─────────┘
```

---

## 2. CONFIGURATION DOCKER

### 2.1 Structure des Fichiers Docker

```
prism/
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   └── postgres/
│       └── init.sql
├── docker-compose.yml
├── docker-compose.prod.yml
└── .dockerignore
```

### 2.2 docker-compose.yml

```yaml
version: '3.8'

services:
  # Application PHP
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: prism_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    networks:
      - prism_network
    depends_on:
      - postgres
      - redis

  # Serveur Web Nginx
  nginx:
    image: nginx:alpine
    container_name: prism_nginx
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - prism_network
    depends_on:
      - app

  # Base de donnees PostgreSQL
  postgres:
    image: postgres:15-alpine
    container_name: prism_postgres
    restart: unless-stopped
    environment:
      POSTGRES_DB: prism
      POSTGRES_USER: prism
      POSTGRES_PASSWORD: ${DB_PASSWORD:-secret}
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./docker/postgres/init.sql:/docker-entrypoint-initdb.d/init.sql
    networks:
      - prism_network

  # Cache Redis
  redis:
    image: redis:7-alpine
    container_name: prism_redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    networks:
      - prism_network

  # Laravel Reverb (WebSockets)
  reverb:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: prism_reverb
    restart: unless-stopped
    working_dir: /var/www
    command: php artisan reverb:start --host=0.0.0.0 --port=8080
    volumes:
      - ./:/var/www
    ports:
      - "6001:8080"
    networks:
      - prism_network
    depends_on:
      - app

  # Laravel Horizon (Queues)
  horizon:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: prism_horizon
    restart: unless-stopped
    working_dir: /var/www
    command: php artisan horizon
    volumes:
      - ./:/var/www
    networks:
      - prism_network
    depends_on:
      - app
      - redis

  # Mailpit (Dev email testing)
  mailpit:
    image: axllent/mailpit
    container_name: prism_mailpit
    restart: unless-stopped
    ports:
      - "1025:1025"
      - "8025:8025"
    networks:
      - prism_network

networks:
  prism_network:
    driver: bridge

volumes:
  postgres_data:
  redis_data:
```

### 2.3 Dockerfile PHP

```dockerfile
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    postgresql-dev \
    nodejs \
    npm \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache

# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
RUN npm ci && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

### 2.4 Configuration Nginx

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/public;
    index index.php;

    charset utf-8;
    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 3. BASE DE DONNEES

### 3.1 Diagramme Entite-Relation

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│   users     │       │  categories │       │  projects   │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ id          │       │ id          │       │ id          │
│ name        │       │ name        │       │ project_code│
│ email       │       │ slug        │       │ name        │
│ password    │       │ color       │       │ category_id │◄──┐
│ role        │       │ description │       │ owner_id    │◄┐ │
│ avatar      │       └─────────────┘       │ priority    │ │ │
│ settings    │              │              │ rag_status  │ │ │
└─────────────┘              │              │ dev_status  │ │ │
      │                      └──────────────│ frs_status  │ │ │
      │                                     │ ...         │ │ │
      │                                     └─────────────┘ │ │
      │                                           │         │ │
      └───────────────────────────────────────────┼─────────┘ │
                                                  │           │
┌─────────────┐       ┌─────────────┐       ┌─────┴───────┐   │
│   risks     │       │change_reqs  │       │project_phases│   │
├─────────────┤       ├─────────────┤       ├─────────────┤   │
│ id          │       │ id          │       │ id          │   │
│ risk_code   │       │ change_code │       │ project_id  │───┘
│ project_id  │───────│ project_id  │───────│ phase       │
│ type        │       │ change_type │       │ status      │
│ impact      │       │ description │       │ started_at  │
│ probability │       │ status      │       │ completed_at│
│ owner_id    │       │ requested_by│       └─────────────┘
│ status      │       │ approved_by │
└─────────────┘       └─────────────┘

┌─────────────┐       ┌─────────────┐
│activity_logs│       │  comments   │
├─────────────┤       ├─────────────┤
│ id          │       │ id          │
│ user_id     │       │ user_id     │
│ loggable_*  │       │commentable_*│
│ action      │       │ content     │
│ changes     │       │ parent_id   │
└─────────────┘       └─────────────┘
```

### 3.2 Liste des Migrations

Creer les migrations dans cet ordre :

```
1. 2026_01_01_000001_create_users_table.php
2. 2026_01_01_000002_create_categories_table.php
3. 2026_01_01_000003_create_projects_table.php
4. 2026_01_01_000004_create_project_phases_table.php
5. 2026_01_01_000005_create_risks_table.php
6. 2026_01_01_000006_create_change_requests_table.php
7. 2026_01_01_000007_create_activity_logs_table.php
8. 2026_01_01_000008_create_comments_table.php
9. 2026_01_01_000009_create_notifications_table.php
10. 2026_01_01_000010_create_attachments_table.php
```

### 3.3 Details des Tables

#### Table: users
```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    settings JSONB DEFAULT '{}',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Table: categories
```sql
CREATE TABLE categories (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    color VARCHAR(7) DEFAULT '#5C6BC0',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Table: projects
```sql
CREATE TABLE projects (
    id BIGSERIAL PRIMARY KEY,
    project_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category_id BIGINT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    business_area VARCHAR(100) NULL,
    priority VARCHAR(10) DEFAULT 'Medium' CHECK (priority IN ('High', 'Medium', 'Low')),
    frs_status VARCHAR(10) DEFAULT 'Draft' CHECK (frs_status IN ('Draft', 'Review', 'Signoff')),
    dev_status VARCHAR(20) DEFAULT 'Not Started' CHECK (dev_status IN (
        'Not Started', 'In Development', 'Testing', 'UAT', 'Deployed', 'On Hold'
    )),
    current_progress VARCHAR(100) NULL,
    blockers TEXT NULL,
    owner_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    planned_release VARCHAR(50) NULL,
    submission_date DATE NULL,
    target_date DATE NULL,
    go_live_date DATE NULL,
    rag_status VARCHAR(10) DEFAULT 'Green' CHECK (rag_status IN ('Green', 'Amber', 'Red')),
    completion_percent SMALLINT DEFAULT 0 CHECK (completion_percent BETWEEN 0 AND 100),
    service_type VARCHAR(50) NULL,
    remarks TEXT NULL,
    last_update TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_projects_rag_priority ON projects(rag_status, priority);
CREATE INDEX idx_projects_category ON projects(category_id);
CREATE INDEX idx_projects_owner ON projects(owner_id);
```

#### Table: project_phases
```sql
CREATE TABLE project_phases (
    id BIGSERIAL PRIMARY KEY,
    project_id BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    phase VARCHAR(20) NOT NULL CHECK (phase IN ('FRS', 'Development', 'Testing', 'UAT', 'Deployment')),
    status VARCHAR(20) DEFAULT 'Pending' CHECK (status IN ('Pending', 'In Progress', 'Completed', 'Blocked')),
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(project_id, phase)
);
```

#### Table: risks
```sql
CREATE TABLE risks (
    id BIGSERIAL PRIMARY KEY,
    risk_code VARCHAR(20) UNIQUE NOT NULL,
    project_id BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    type VARCHAR(10) DEFAULT 'Risk' CHECK (type IN ('Risk', 'Issue')),
    description TEXT NOT NULL,
    impact VARCHAR(10) DEFAULT 'Medium' CHECK (impact IN ('Low', 'Medium', 'High', 'Critical')),
    probability VARCHAR(10) DEFAULT 'Medium' CHECK (probability IN ('Low', 'Medium', 'High')),
    risk_score VARCHAR(10) DEFAULT 'Medium' CHECK (risk_score IN ('Low', 'Medium', 'High', 'Critical')),
    mitigation_plan TEXT NULL,
    owner_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'Open' CHECK (status IN ('Open', 'In Progress', 'Mitigated', 'Closed')),
    identified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_risks_status_score ON risks(status, risk_score);
CREATE INDEX idx_risks_project ON risks(project_id);
```

#### Table: change_requests
```sql
CREATE TABLE change_requests (
    id BIGSERIAL PRIMARY KEY,
    change_code VARCHAR(20) UNIQUE NOT NULL,
    project_id BIGINT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    change_type VARCHAR(20) NOT NULL CHECK (change_type IN (
        'Scope Change', 'Schedule Change', 'Budget Change', 'Resource Change'
    )),
    description TEXT NOT NULL,
    impact_analysis TEXT NULL,
    requested_by_id BIGINT NOT NULL REFERENCES users(id),
    approved_by_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'Pending' CHECK (status IN (
        'Pending', 'Under Review', 'Approved', 'Rejected'
    )),
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_changes_status_project ON change_requests(status, project_id);
```

#### Table: activity_logs
```sql
CREATE TABLE activity_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    loggable_type VARCHAR(100) NOT NULL,
    loggable_id BIGINT NOT NULL,
    action VARCHAR(50) NOT NULL,
    changes JSONB NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_activity_loggable ON activity_logs(loggable_type, loggable_id);
CREATE INDEX idx_activity_created ON activity_logs(created_at);
```

#### Table: comments
```sql
CREATE TABLE comments (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    commentable_type VARCHAR(100) NOT NULL,
    commentable_id BIGINT NOT NULL,
    content TEXT NOT NULL,
    parent_id BIGINT NULL REFERENCES comments(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_comments_commentable ON comments(commentable_type, commentable_id);
```

---

## 4. BACKEND LARAVEL

### 4.1 Structure des Dossiers

```
app/
├── Actions/
│   ├── Project/
│   │   ├── CreateProjectAction.php
│   │   ├── UpdateProjectAction.php
│   │   └── DeleteProjectAction.php
│   ├── Risk/
│   └── ChangeRequest/
├── DTOs/
│   ├── ProjectData.php
│   ├── RiskData.php
│   └── ChangeRequestData.php
├── Events/
│   ├── ProjectStatusChanged.php
│   ├── RiskCreated.php
│   └── ChangeRequestApproved.php
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ProjectController.php
│   │   │   ├── RiskController.php
│   │   │   ├── ChangeRequestController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── UserController.php
│   │   │   ├── ImportController.php
│   │   │   ├── ExportController.php
│   │   │   └── NotificationController.php
│   │   └── Web/
│   │       └── PageController.php
│   ├── Middleware/
│   │   └── EnsureUserHasRole.php
│   ├── Requests/
│   │   ├── ProjectRequest.php
│   │   ├── RiskRequest.php
│   │   ├── ChangeRequestRequest.php
│   │   └── ImportRequest.php
│   └── Resources/
│       ├── ProjectResource.php
│       ├── ProjectCollection.php
│       ├── RiskResource.php
│       ├── ChangeRequestResource.php
│       ├── CategoryResource.php
│       └── UserResource.php
├── Listeners/
│   ├── LogProjectActivity.php
│   ├── SendRiskNotification.php
│   └── UpdateProjectRAGStatus.php
├── Models/
│   ├── User.php
│   ├── Category.php
│   ├── Project.php
│   ├── ProjectPhase.php
│   ├── Risk.php
│   ├── ChangeRequest.php
│   ├── ActivityLog.php
│   ├── Comment.php
│   └── Notification.php
├── Notifications/
│   ├── ProjectDeadlineApproaching.php
│   ├── CriticalRiskCreated.php
│   └── ChangeRequestPending.php
├── Policies/
│   ├── ProjectPolicy.php
│   ├── RiskPolicy.php
│   └── ChangeRequestPolicy.php
└── Services/
    ├── ProjectService.php
    ├── RiskService.php
    ├── DashboardService.php
    ├── ExcelImportService.php
    └── ExportService.php
```

### 4.2 Models Eloquent

#### Model: User
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'settings',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'settings' => 'array',
    ];

    // Relations
    public function ownedProjects()
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function ownedRisks()
    {
        return $this->hasMany(Risk::class, 'owner_id');
    }

    public function changeRequests()
    {
        return $this->hasMany(ChangeRequest::class, 'requested_by_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function activities()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
```

#### Model: Project (Complet)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

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

    // =====================
    // RELATIONS
    // =====================

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
        return $this->hasMany(ProjectPhase::class)->orderByRaw("
            CASE phase
                WHEN 'FRS' THEN 1
                WHEN 'Development' THEN 2
                WHEN 'Testing' THEN 3
                WHEN 'UAT' THEN 4
                WHEN 'Deployment' THEN 5
            END
        ");
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

    // =====================
    // SCOPES
    // =====================

    public function scopeByRagStatus(Builder $query, string $status): Builder
    {
        return $query->where('rag_status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeByDevStatus(Builder $query, string $status): Builder
    {
        return $query->where('dev_status', $status);
    }

    public function scopeDeployed(Builder $query): Builder
    {
        return $query->where('dev_status', 'Deployed');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('dev_status', 'In Development');
    }

    public function scopeAwaitingAction(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereIn('dev_status', ['On Hold', 'Not Started'])
              ->orWhereNotNull('blockers');
        });
    }

    public function scopeWithFrsSignoff(Builder $query): Builder
    {
        return $query->where('frs_status', 'Signoff');
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('rag_status', 'Red')
            ->orWhereHas('risks', fn($q) => $q->critical()->open());
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
              ->orWhere('project_code', 'ilike', "%{$search}%")
              ->orWhere('description', 'ilike', "%{$search}%");
        });
    }

    // =====================
    // ACCESSORS
    // =====================

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

    public function getOpenRisksCountAttribute(): int
    {
        return $this->risks()
            ->whereIn('status', ['Open', 'In Progress'])
            ->count();
    }

    public function getPendingChangesCountAttribute(): int
    {
        return $this->changeRequests()
            ->whereIn('status', ['Pending', 'Under Review'])
            ->count();
    }

    public function getCurrentPhaseAttribute(): ?string
    {
        $phase = $this->phases()
            ->where('status', 'In Progress')
            ->first();

        return $phase?->phase;
    }

    // =====================
    // BOOT
    // =====================

    protected static function booted(): void
    {
        // Auto-generate project code
        static::creating(function (Project $project) {
            if (empty($project->project_code)) {
                $lastCode = static::withTrashed()
                    ->where('project_code', 'like', 'PRISM-%')
                    ->orderByRaw("CAST(SUBSTRING(project_code FROM 7) AS INTEGER) DESC")
                    ->value('project_code');

                $nextNumber = $lastCode
                    ? (int) substr($lastCode, 6) + 1
                    : 1;

                $project->project_code = 'PRISM-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });

        // Update last_update timestamp
        static::updating(function (Project $project) {
            $project->last_update = now();
        });

        // Initialize phases on create
        static::created(function (Project $project) {
            $phases = ['FRS', 'Development', 'Testing', 'UAT', 'Deployment'];
            foreach ($phases as $phase) {
                $project->phases()->create([
                    'phase' => $phase,
                    'status' => 'Pending',
                ]);
            }
        });
    }
}
```

### 4.3 Services

#### ProjectService
```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Events\ProjectStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        return Project::query()
            ->with(['category', 'owner', 'phases'])
            ->withCount(['risks', 'changeRequests'])
            ->when($filters['search'] ?? null, fn($q, $s) => $q->search($s))
            ->when($filters['category_id'] ?? null, fn($q, $id) => $q->where('category_id', $id))
            ->when($filters['priority'] ?? null, fn($q, $p) => $q->byPriority($p))
            ->when($filters['rag_status'] ?? null, fn($q, $s) => $q->byRagStatus($s))
            ->when($filters['dev_status'] ?? null, fn($q, $s) => $q->byDevStatus($s))
            ->when($filters['owner_id'] ?? null, fn($q, $id) => $q->where('owner_id', $id))
            ->when($filters['has_blockers'] ?? false, fn($q) => $q->whereNotNull('blockers'))
            ->orderBy($filters['sort_by'] ?? 'project_code', $filters['sort_dir'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $project = Project::create($data);

            // Log activity
            $project->activities()->create([
                'user_id' => auth()->id(),
                'action' => 'created',
                'changes' => $data,
            ]);

            return $project->load(['category', 'owner', 'phases']);
        });
    }

    public function update(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $oldData = $project->toArray();
            $project->update($data);

            // Check for RAG status change
            if (isset($data['rag_status']) && $oldData['rag_status'] !== $data['rag_status']) {
                event(new ProjectStatusChanged($project, $oldData['rag_status'], $data['rag_status']));
            }

            // Log activity
            $project->activities()->create([
                'user_id' => auth()->id(),
                'action' => 'updated',
                'changes' => [
                    'old' => array_intersect_key($oldData, $data),
                    'new' => $data,
                ],
            ]);

            return $project->fresh(['category', 'owner', 'phases']);
        });
    }

    public function delete(Project $project): void
    {
        DB::transaction(function () use ($project) {
            $project->activities()->create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
            ]);

            $project->delete();
        });
    }

    public function updatePhase(Project $project, string $phase, string $status, ?string $remarks = null): ProjectPhase
    {
        $projectPhase = $project->phases()->where('phase', $phase)->firstOrFail();

        $projectPhase->update([
            'status' => $status,
            'started_at' => $status === 'In Progress' && !$projectPhase->started_at
                ? now()
                : $projectPhase->started_at,
            'completed_at' => $status === 'Completed' ? now() : null,
            'remarks' => $remarks,
        ]);

        // Update project dev_status based on phases
        $this->syncProjectDevStatus($project);

        return $projectPhase;
    }

    public function duplicate(Project $project): Project
    {
        return DB::transaction(function () use ($project) {
            $newProject = $project->replicate([
                'project_code',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);

            $newProject->name = $project->name . ' (Copy)';
            $newProject->dev_status = 'Not Started';
            $newProject->completion_percent = 0;
            $newProject->rag_status = 'Green';
            $newProject->save();

            return $newProject->load(['category', 'owner', 'phases']);
        });
    }

    private function syncProjectDevStatus(Project $project): void
    {
        $phases = $project->phases()->get();

        $statusMap = [
            'Deployment' => 'Deployed',
            'UAT' => 'UAT',
            'Testing' => 'Testing',
            'Development' => 'In Development',
            'FRS' => 'Not Started',
        ];

        foreach (['Deployment', 'UAT', 'Testing', 'Development', 'FRS'] as $phase) {
            $projectPhase = $phases->firstWhere('phase', $phase);
            if ($projectPhase && in_array($projectPhase->status, ['In Progress', 'Completed'])) {
                if ($projectPhase->status === 'Completed' && $phase === 'Deployment') {
                    $project->update(['dev_status' => 'Deployed', 'completion_percent' => 100]);
                } else {
                    $project->update(['dev_status' => $statusMap[$phase] ?? 'In Development']);
                }
                break;
            }
        }

        // Check for blocked status
        if ($phases->contains('status', 'Blocked')) {
            $project->update(['dev_status' => 'On Hold']);
        }
    }
}
```

#### DashboardService
```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Risk;
use App\Models\ChangeRequest;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getKpis(): array
    {
        return Cache::remember('dashboard.kpis', 300, function () {
            $totalProjects = Project::count();

            return [
                'total_projects' => $totalProjects,
                'deployed' => $this->getStatusKpi('Deployed', $totalProjects),
                'in_progress' => $this->getStatusKpi('In Development', $totalProjects),
                'testing' => $this->getStatusKpi('Testing', $totalProjects),
                'uat' => $this->getStatusKpi('UAT', $totalProjects),
                'on_hold' => $this->getStatusKpi('On Hold', $totalProjects),
                'not_started' => $this->getStatusKpi('Not Started', $totalProjects),
                'frs_signoff' => [
                    'count' => Project::withFrsSignoff()->count(),
                    'percent' => $totalProjects > 0
                        ? round(Project::withFrsSignoff()->count() / $totalProjects * 100)
                        : 0,
                ],
                'critical_risks' => Risk::critical()->open()->count(),
                'open_risks' => Risk::open()->count(),
                'pending_changes' => ChangeRequest::where('status', 'Pending')->count(),
                'blocked_projects' => Project::whereNotNull('blockers')->count(),
            ];
        });
    }

    private function getStatusKpi(string $status, int $total): array
    {
        $count = Project::byDevStatus($status)->count();
        return [
            'count' => $count,
            'percent' => $total > 0 ? round($count / $total * 100) : 0,
        ];
    }

    public function getRagDistribution(): array
    {
        return Cache::remember('dashboard.rag', 300, function () {
            $distribution = Project::select('rag_status', DB::raw('COUNT(*) as count'))
                ->groupBy('rag_status')
                ->pluck('count', 'rag_status')
                ->toArray();

            return [
                'Green' => $distribution['Green'] ?? 0,
                'Amber' => $distribution['Amber'] ?? 0,
                'Red' => $distribution['Red'] ?? 0,
            ];
        });
    }

    public function getCategoryDistribution(): array
    {
        return Cache::remember('dashboard.categories', 300, function () {
            return Project::select('categories.name', 'categories.color', DB::raw('COUNT(*) as count'))
                ->join('categories', 'projects.category_id', '=', 'categories.id')
                ->groupBy('categories.name', 'categories.color')
                ->orderByDesc('count')
                ->get()
                ->toArray();
        });
    }

    public function getDeploymentTimeline(): array
    {
        return Cache::remember('dashboard.timeline', 300, function () {
            return Project::where('dev_status', 'Deployed')
                ->whereNotNull('go_live_date')
                ->select(
                    DB::raw("TO_CHAR(go_live_date, 'YYYY-MM') as month"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->limit(12)
                ->pluck('count', 'month')
                ->toArray();
        });
    }

    public function getRecentActivity(int $limit = 15): array
    {
        return ActivityLog::with(['user', 'loggable'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($activity) => [
                'id' => $activity->id,
                'user' => $activity->user?->name ?? 'System',
                'user_avatar' => $activity->user?->avatar,
                'action' => $activity->action,
                'type' => class_basename($activity->loggable_type),
                'subject' => $activity->loggable?->name ?? $activity->loggable?->project_code ?? 'N/A',
                'subject_id' => $activity->loggable_id,
                'changes' => $activity->changes,
                'created_at' => $activity->created_at->diffForHumans(),
                'created_at_full' => $activity->created_at->format('d/m/Y H:i'),
            ])
            ->toArray();
    }

    public function getCriticalProjects(int $limit = 10): array
    {
        return Project::with(['category', 'owner'])
            ->withCount(['risks' => fn($q) => $q->critical()->open()])
            ->where('rag_status', 'Red')
            ->orWhereHas('risks', fn($q) => $q->critical()->open())
            ->orderByDesc('risks_count')
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->name,
                'rag_status' => $p->rag_status,
                'category' => $p->category->name,
                'category_color' => $p->category->color,
                'owner' => $p->owner?->name,
                'critical_risks' => $p->risks_count,
                'blockers' => $p->blockers,
                'completion_percent' => $p->completion_percent,
            ])
            ->toArray();
    }

    public function getUpcomingDeadlines(int $days = 30): array
    {
        return Project::with(['category', 'owner'])
            ->whereNotNull('target_date')
            ->whereBetween('target_date', [now(), now()->addDays($days)])
            ->whereNotIn('dev_status', ['Deployed', 'On Hold'])
            ->orderBy('target_date')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->project_code,
                'name' => $p->name,
                'target_date' => $p->target_date->format('d/m/Y'),
                'days_remaining' => now()->diffInDays($p->target_date),
                'rag_status' => $p->rag_status,
                'dev_status' => $p->dev_status,
                'completion_percent' => $p->completion_percent,
            ])
            ->toArray();
    }

    public function clearCache(): void
    {
        Cache::forget('dashboard.kpis');
        Cache::forget('dashboard.rag');
        Cache::forget('dashboard.categories');
        Cache::forget('dashboard.timeline');
    }
}
```

### 4.4 Controllers API

#### DashboardController
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function kpis(): JsonResponse
    {
        return response()->json($this->dashboardService->getKpis());
    }

    public function ragDistribution(): JsonResponse
    {
        return response()->json($this->dashboardService->getRagDistribution());
    }

    public function categoryDistribution(): JsonResponse
    {
        return response()->json($this->dashboardService->getCategoryDistribution());
    }

    public function deploymentTimeline(): JsonResponse
    {
        return response()->json($this->dashboardService->getDeploymentTimeline());
    }

    public function recentActivity(): JsonResponse
    {
        return response()->json($this->dashboardService->getRecentActivity());
    }

    public function criticalProjects(): JsonResponse
    {
        return response()->json($this->dashboardService->getCriticalProjects());
    }

    public function upcomingDeadlines(): JsonResponse
    {
        return response()->json($this->dashboardService->getUpcomingDeadlines());
    }
}
```

#### ProjectController
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
        $projects = $this->projectService->list($request->all());
        return new ProjectCollection($projects);
    }

    public function show(Project $project): ProjectResource
    {
        $project->load([
            'category',
            'owner',
            'phases',
            'risks' => fn($q) => $q->orderByRaw("
                CASE risk_score
                    WHEN 'Critical' THEN 1
                    WHEN 'High' THEN 2
                    WHEN 'Medium' THEN 3
                    ELSE 4
                END
            ")->limit(10),
            'changeRequests' => fn($q) => $q->latest()->limit(10),
            'comments.user',
            'activities' => fn($q) => $q->with('user')->latest()->limit(20),
        ]);

        return new ProjectResource($project);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated());

        return response()->json([
            'message' => 'Projet cree avec succes',
            'data' => new ProjectResource($project),
        ], 201);
    }

    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projectService->update($project, $request->validated());

        return response()->json([
            'message' => 'Projet mis a jour avec succes',
            'data' => new ProjectResource($project),
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->projectService->delete($project);

        return response()->json([
            'message' => 'Projet supprime avec succes',
        ]);
    }

    public function phases(Project $project): JsonResponse
    {
        return response()->json($project->phases);
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
            'message' => 'Phase mise a jour avec succes',
            'data' => $projectPhase,
        ]);
    }

    public function duplicate(Project $project): JsonResponse
    {
        $newProject = $this->projectService->duplicate($project);

        return response()->json([
            'message' => 'Projet duplique avec succes',
            'data' => new ProjectResource($newProject),
        ], 201);
    }

    public function archive(Project $project): JsonResponse
    {
        $project->update(['dev_status' => 'On Hold']);

        return response()->json([
            'message' => 'Projet archive avec succes',
        ]);
    }

    public function activity(Project $project): JsonResponse
    {
        $activities = $project->activities()
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json($activities);
    }

    public function addComment(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $project->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'message' => 'Commentaire ajoute',
            'data' => $comment->load('user'),
        ], 201);
    }
}
```

---

## 5. FRONTEND VUE.JS

### 5.1 Structure des Dossiers

```
resources/js/
├── app.js                    # Point d'entree
├── bootstrap.js              # Configuration axios, echo
├── Components/
│   ├── Glass/               # Composants Glass UI
│   │   ├── GlassCard.vue
│   │   ├── GlassButton.vue
│   │   ├── GlassInput.vue
│   │   ├── GlassSelect.vue
│   │   ├── GlassModal.vue
│   │   ├── GlassTable.vue
│   │   ├── GlassDropdown.vue
│   │   └── GlassBadge.vue
│   ├── Charts/
│   │   ├── DonutChart.vue
│   │   ├── BarChart.vue
│   │   ├── LineChart.vue
│   │   └── HeatmapChart.vue
│   ├── Dashboard/
│   │   ├── KpiCard.vue
│   │   ├── ActivityFeed.vue
│   │   ├── CriticalProjects.vue
│   │   └── DeadlinesWidget.vue
│   ├── Project/
│   │   ├── ProjectCard.vue
│   │   ├── ProjectForm.vue
│   │   ├── PhaseTimeline.vue
│   │   ├── RagBadge.vue
│   │   └── PriorityBadge.vue
│   ├── Risk/
│   │   ├── RiskCard.vue
│   │   ├── RiskMatrix.vue
│   │   └── RiskForm.vue
│   ├── Layout/
│   │   ├── AppLayout.vue
│   │   ├── Sidebar.vue
│   │   ├── Header.vue
│   │   ├── Breadcrumb.vue
│   │   └── NotificationBell.vue
│   └── Shared/
│       ├── Pagination.vue
│       ├── SearchInput.vue
│       ├── FilterDropdown.vue
│       ├── ConfirmModal.vue
│       └── Toast.vue
├── Layouts/
│   ├── AuthLayout.vue
│   └── GuestLayout.vue
├── Pages/
│   ├── Auth/
│   │   ├── Login.vue
│   │   └── ForgotPassword.vue
│   ├── Dashboard/
│   │   └── Index.vue
│   ├── Projects/
│   │   ├── Index.vue
│   │   ├── Show.vue
│   │   ├── Create.vue
│   │   └── Edit.vue
│   ├── Risks/
│   │   ├── Index.vue
│   │   └── Matrix.vue
│   ├── Changes/
│   │   └── Index.vue
│   ├── Settings/
│   │   ├── Profile.vue
│   │   └── Preferences.vue
│   └── Admin/
│       ├── Users.vue
│       └── Categories.vue
├── Stores/
│   ├── auth.js
│   ├── projects.js
│   ├── dashboard.js
│   ├── risks.js
│   ├── notifications.js
│   └── ui.js
├── Composables/
│   ├── useApi.js
│   ├── useFilters.js
│   ├── useToast.js
│   └── useConfirm.js
└── Utils/
    ├── formatters.js
    ├── validators.js
    └── constants.js
```

### 5.2 Configuration TailwindCSS Glass

```javascript
// tailwind.config.js
import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#667eea',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                },
                glass: {
                    white: 'rgba(255, 255, 255, 0.25)',
                    border: 'rgba(255, 255, 255, 0.18)',
                    dark: 'rgba(0, 0, 0, 0.25)',
                },
                rag: {
                    green: '#10B981',
                    amber: '#F59E0B',
                    red: '#EF4444',
                },
            },
            backdropBlur: {
                xs: '2px',
            },
            boxShadow: {
                glass: '0 8px 32px 0 rgba(31, 38, 135, 0.37)',
                'glass-sm': '0 4px 16px 0 rgba(31, 38, 135, 0.2)',
            },
            backgroundImage: {
                'gradient-main': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'gradient-dark': 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
```

### 5.3 Composants Glass UI

#### GlassCard.vue
```vue
<template>
  <div
    :class="[
      'glass-card',
      { 'glass-card--hoverable': hoverable },
      { 'glass-card--clickable': clickable },
      sizeClass,
      colorClass,
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

    <!-- Effet de brillance au survol -->
    <div v-if="hoverable" class="glass-card__shine" />
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  hoverable: { type: Boolean, default: false },
  clickable: { type: Boolean, default: false },
  size: {
    type: String,
    default: 'md',
    validator: v => ['sm', 'md', 'lg'].includes(v)
  },
  color: {
    type: String,
    default: 'default',
    validator: v => ['default', 'success', 'warning', 'danger', 'info'].includes(v)
  },
  noPadding: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

const sizeClass = computed(() => `glass-card--${props.size}`);

const colorClass = computed(() => {
  if (props.color === 'default') return '';
  return `glass-card--${props.color}`;
});

const handleClick = () => {
  if (props.clickable) emit('click');
};
</script>

<style scoped>
.glass-card {
  @apply relative overflow-hidden;
  @apply bg-white/20 backdrop-blur-xl;
  @apply border border-white/30;
  @apply rounded-2xl;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
  @apply transition-all duration-300 ease-out;
}

.glass-card--hoverable:hover {
  @apply bg-white/30;
  @apply border-white/40;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.25);
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

/* Variantes de couleur */
.glass-card--success {
  @apply border-emerald-400/30 bg-emerald-500/10;
}

.glass-card--warning {
  @apply border-amber-400/30 bg-amber-500/10;
}

.glass-card--danger {
  @apply border-red-400/30 bg-red-500/10;
}

.glass-card--info {
  @apply border-blue-400/30 bg-blue-500/10;
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

/* Effet de brillance */
.glass-card__shine {
  @apply absolute inset-0 pointer-events-none;
  background: linear-gradient(
    135deg,
    transparent 40%,
    rgba(255, 255, 255, 0.1) 50%,
    transparent 60%
  );
  @apply opacity-0 transition-opacity duration-500;
}

.glass-card--hoverable:hover .glass-card__shine {
  @apply opacity-100;
  animation: shine 0.8s ease-in-out;
}

@keyframes shine {
  0% { transform: translateX(-100%) translateY(-100%); }
  100% { transform: translateX(100%) translateY(100%); }
}
</style>
```

#### GlassButton.vue
```vue
<template>
  <component
    :is="tag"
    :class="[
      'glass-button',
      `glass-button--${variant}`,
      `glass-button--${size}`,
      { 'glass-button--loading': loading },
      { 'glass-button--icon-only': iconOnly },
    ]"
    :disabled="disabled || loading"
    :href="href"
    :to="to"
    v-bind="$attrs"
  >
    <span v-if="loading" class="glass-button__spinner">
      <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
        <circle
          class="opacity-25"
          cx="12" cy="12" r="10"
          stroke="currentColor"
          stroke-width="4"
          fill="none"
        />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
        />
      </svg>
    </span>

    <component
      v-if="icon && !loading"
      :is="icon"
      :class="['glass-button__icon', iconClass]"
    />

    <span v-if="!iconOnly" class="glass-button__text">
      <slot />
    </span>
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: v => ['primary', 'secondary', 'success', 'danger', 'ghost'].includes(v)
  },
  size: {
    type: String,
    default: 'md',
    validator: v => ['xs', 'sm', 'md', 'lg'].includes(v)
  },
  icon: { type: Object, default: null },
  iconOnly: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  href: { type: String, default: null },
  to: { type: [String, Object], default: null },
});

const tag = computed(() => {
  if (props.to) return Link;
  if (props.href) return 'a';
  return 'button';
});

const iconClass = computed(() => {
  const sizes = {
    xs: 'w-3 h-3',
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6',
  };
  return sizes[props.size];
});
</script>

<style scoped>
.glass-button {
  @apply inline-flex items-center justify-center gap-2;
  @apply font-medium rounded-xl;
  @apply transition-all duration-200;
  @apply focus:outline-none focus:ring-2 focus:ring-white/30;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Tailles */
.glass-button--xs {
  @apply px-2 py-1 text-xs;
}

.glass-button--sm {
  @apply px-3 py-1.5 text-sm;
}

.glass-button--md {
  @apply px-4 py-2 text-sm;
}

.glass-button--lg {
  @apply px-6 py-3 text-base;
}

/* Variantes */
.glass-button--primary {
  @apply bg-primary-500/80 text-white;
  @apply hover:bg-primary-500 hover:shadow-lg;
  @apply active:bg-primary-600;
}

.glass-button--secondary {
  @apply bg-white/20 text-white backdrop-blur-sm;
  @apply border border-white/30;
  @apply hover:bg-white/30;
  @apply active:bg-white/40;
}

.glass-button--success {
  @apply bg-emerald-500/80 text-white;
  @apply hover:bg-emerald-500;
  @apply active:bg-emerald-600;
}

.glass-button--danger {
  @apply bg-red-500/80 text-white;
  @apply hover:bg-red-500;
  @apply active:bg-red-600;
}

.glass-button--ghost {
  @apply bg-transparent text-white/80;
  @apply hover:bg-white/10 hover:text-white;
}

/* Icon only */
.glass-button--icon-only {
  @apply p-2;
}

.glass-button--icon-only.glass-button--xs {
  @apply p-1;
}

.glass-button--icon-only.glass-button--lg {
  @apply p-3;
}

/* Loading */
.glass-button--loading {
  @apply cursor-wait;
}

.glass-button__spinner {
  @apply absolute;
}

.glass-button--loading .glass-button__text,
.glass-button--loading .glass-button__icon {
  @apply invisible;
}
</style>
```

#### GlassInput.vue
```vue
<template>
  <div class="glass-input-wrapper">
    <label v-if="label" :for="id" class="glass-input__label">
      {{ label }}
      <span v-if="required" class="text-red-400">*</span>
    </label>

    <div class="glass-input__container" :class="{ 'glass-input__container--error': error }">
      <component
        v-if="prefixIcon"
        :is="prefixIcon"
        class="glass-input__prefix-icon"
      />

      <input
        v-if="type !== 'textarea'"
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        class="glass-input"
        :class="{ 'pl-10': prefixIcon, 'pr-10': suffixIcon }"
        @input="$emit('update:modelValue', $event.target.value)"
        v-bind="$attrs"
      />

      <textarea
        v-else
        :id="id"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :rows="rows"
        class="glass-input glass-input--textarea"
        @input="$emit('update:modelValue', $event.target.value)"
        v-bind="$attrs"
      />

      <component
        v-if="suffixIcon"
        :is="suffixIcon"
        class="glass-input__suffix-icon"
      />
    </div>

    <p v-if="error" class="glass-input__error">{{ error }}</p>
    <p v-else-if="hint" class="glass-input__hint">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  prefixIcon: { type: Object, default: null },
  suffixIcon: { type: Object, default: null },
  rows: { type: Number, default: 3 },
});

defineEmits(['update:modelValue']);

const id = computed(() => `input-${Math.random().toString(36).slice(2, 9)}`);
</script>

<style scoped>
.glass-input-wrapper {
  @apply w-full;
}

.glass-input__label {
  @apply block text-sm font-medium text-white/80 mb-2;
}

.glass-input__container {
  @apply relative;
}

.glass-input {
  @apply w-full;
  @apply bg-white/10 backdrop-blur-sm;
  @apply border border-white/20 rounded-xl;
  @apply px-4 py-2.5;
  @apply text-white placeholder-white/40;
  @apply transition-all duration-200;
  @apply focus:outline-none focus:ring-2 focus:ring-primary-400/50 focus:border-primary-400/50;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.glass-input--textarea {
  @apply resize-none;
}

.glass-input__container--error .glass-input {
  @apply border-red-400/50 focus:ring-red-400/50;
}

.glass-input__prefix-icon,
.glass-input__suffix-icon {
  @apply absolute top-1/2 -translate-y-1/2 w-5 h-5 text-white/50;
}

.glass-input__prefix-icon {
  @apply left-3;
}

.glass-input__suffix-icon {
  @apply right-3;
}

.glass-input__error {
  @apply mt-1 text-sm text-red-400;
}

.glass-input__hint {
  @apply mt-1 text-sm text-white/50;
}
</style>
```

### 5.4 Pages Principales

#### Dashboard/Index.vue
```vue
<template>
  <AppLayout title="Tableau de bord">
    <div class="space-y-6">
      <!-- KPIs Row -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard
          label="Total Projets"
          :value="kpis.total_projects"
          :icon="FolderKanban"
          color="blue"
        />
        <KpiCard
          label="Deployes"
          :value="kpis.deployed?.count"
          :percent="kpis.deployed?.percent"
          :icon="Rocket"
          color="green"
          show-progress
        />
        <KpiCard
          label="En Cours"
          :value="kpis.in_progress?.count"
          :percent="kpis.in_progress?.percent"
          :icon="Code"
          color="blue"
          show-progress
        />
        <KpiCard
          label="Risques Critiques"
          :value="kpis.critical_risks"
          :icon="AlertTriangle"
          color="red"
          :subtext="kpis.critical_risks > 0 ? 'Attention requise' : 'Aucun'"
          :trend="kpis.critical_risks > 0 ? 'up' : 'stable'"
        />
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- RAG Distribution -->
        <GlassCard>
          <template #header>
            <h3 class="text-lg font-semibold text-white">Distribution RAG</h3>
          </template>
          <DonutChart
            :data="ragData"
            :colors="['#10B981', '#F59E0B', '#EF4444']"
          />
        </GlassCard>

        <!-- Category Distribution -->
        <GlassCard class="lg:col-span-2">
          <template #header>
            <h3 class="text-lg font-semibold text-white">Projets par Categorie</h3>
          </template>
          <BarChart :data="categoryData" />
        </GlassCard>
      </div>

      <!-- Bottom Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Critical Projects -->
        <GlassCard>
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-white">Projets Critiques</h3>
              <RagBadge status="Red" size="sm" />
            </div>
          </template>
          <CriticalProjects :projects="criticalProjects" />
        </GlassCard>

        <!-- Recent Activity -->
        <GlassCard>
          <template #header>
            <h3 class="text-lg font-semibold text-white">Activite Recente</h3>
          </template>
          <ActivityFeed :activities="recentActivity" />
        </GlassCard>
      </div>

      <!-- Upcoming Deadlines -->
      <GlassCard>
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white">Echeances a Venir</h3>
            <span class="text-sm text-white/60">30 prochains jours</span>
          </div>
        </template>
        <DeadlinesWidget :deadlines="upcomingDeadlines" />
      </GlassCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useDashboardStore } from '@/Stores/dashboard';
import {
  FolderKanban,
  Rocket,
  Code,
  AlertTriangle
} from 'lucide-vue-next';

import AppLayout from '@/Layouts/AppLayout.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import KpiCard from '@/Components/Dashboard/KpiCard.vue';
import DonutChart from '@/Components/Charts/DonutChart.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import CriticalProjects from '@/Components/Dashboard/CriticalProjects.vue';
import ActivityFeed from '@/Components/Dashboard/ActivityFeed.vue';
import DeadlinesWidget from '@/Components/Dashboard/DeadlinesWidget.vue';
import RagBadge from '@/Components/Project/RagBadge.vue';

const dashboardStore = useDashboardStore();

const kpis = computed(() => dashboardStore.kpis);
const ragData = computed(() => dashboardStore.ragDistribution);
const categoryData = computed(() => dashboardStore.categoryDistribution);
const criticalProjects = computed(() => dashboardStore.criticalProjects);
const recentActivity = computed(() => dashboardStore.recentActivity);
const upcomingDeadlines = computed(() => dashboardStore.upcomingDeadlines);

onMounted(() => {
  dashboardStore.fetchAll();
});
</script>
```

---

## 6. MODULES FONCTIONNELS

### 6.1 Module Dashboard

| Fonctionnalite | Description | Priorite |
|----------------|-------------|----------|
| KPIs | 6 indicateurs cles avec variations | P0 |
| RAG Chart | Donut chart distribution Green/Amber/Red | P0 |
| Category Chart | Bar chart projets par categorie | P0 |
| Critical Projects | Liste des projets Red avec risques | P0 |
| Activity Feed | 15 dernieres activites | P0 |
| Deadlines | Echeances 30 jours | P1 |
| Deployment Timeline | Line chart deploiements mensuels | P1 |
| Export Dashboard | PDF/PNG du dashboard | P2 |

### 6.2 Module Projects

| Fonctionnalite | Description | Priorite |
|----------------|-------------|----------|
| Liste projets | DataTable avec pagination et tri | P0 |
| Filtres avances | Categorie, RAG, Priorite, Owner, Status | P0 |
| Recherche globale | Par nom, code, description | P0 |
| Fiche projet | Vue detaillee avec onglets | P0 |
| CRUD projet | Create, Read, Update, Delete | P0 |
| Phases timeline | Visualisation des 5 phases | P0 |
| Update phase | Changement statut phase | P0 |
| Comments | Ajout/lecture commentaires | P1 |
| Activity log | Historique des modifications | P1 |
| Export | CSV, Excel, PDF | P1 |
| Vue Kanban | Drag & drop par statut | P2 |
| Duplicate | Clone d'un projet | P2 |
| Archive | Soft delete avec archive | P2 |

### 6.3 Module Risks

| Fonctionnalite | Description | Priorite |
|----------------|-------------|----------|
| Liste risques | DataTable avec filtres | P0 |
| CRUD risque | Create, Read, Update, Delete | P0 |
| Risk Matrix | Matrice Impact x Probabilite | P0 |
| Auto risk score | Calcul automatique du score | P0 |
| Status workflow | Open -> In Progress -> Mitigated -> Closed | P0 |
| Link to project | Association projet | P0 |
| Notifications | Alerte risque critique | P1 |

### 6.4 Module Changes

| Fonctionnalite | Description | Priorite |
|----------------|-------------|----------|
| Liste changes | DataTable avec filtres | P0 |
| CRUD change | Create, Read, Update, Delete | P0 |
| Workflow approbation | Pending -> Review -> Approved/Rejected | P0 |
| Impact analysis | Champ analyse d'impact | P1 |
| Approval history | Historique des approbations | P1 |

### 6.5 Module Import/Export

| Fonctionnalite | Description | Priorite |
|----------------|-------------|----------|
| Import Excel | Upload et parsing fichier source | P0 |
| Validation preview | Apercu avant import | P0 |
| Error report | Rapport des erreurs d'import | P0 |
| Export projects | CSV, Excel avec filtres | P1 |
| Export risks | CSV, Excel | P1 |
| Portfolio report | PDF rapport complet | P2 |

### 6.6 Module Admin

| Fonctionnalite | Description | Priorite |
|----------------|-------------|----------|
| Gestion users | CRUD utilisateurs | P0 |
| Roles & permissions | Admin, PM, Member, Viewer | P0 |
| Gestion categories | CRUD categories | P1 |
| System settings | Configuration generale | P2 |
| Audit logs | Consultation logs systeme | P2 |

---

## 7. API REST

### 7.1 Routes Completes

```php
// routes/api.php

Route::prefix('v1')->group(function () {

    // Auth
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);
        Route::put('/user/password', [AuthController::class, 'updatePassword']);
        Route::put('/user/preferences', [AuthController::class, 'updatePreferences']);

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
            Route::post('/restore', [ProjectController::class, 'restore']);
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

        // Users (Admin)
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', UserController::class);
            Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
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
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    });
});
```

### 7.2 Documentation OpenAPI (extraits)

```yaml
openapi: 3.0.3
info:
  title: PRISM API
  version: 1.0.0
  description: API de gestion de projets PRISM

servers:
  - url: http://localhost:8080/api/v1
    description: Development

paths:
  /projects:
    get:
      summary: Liste des projets
      tags: [Projects]
      parameters:
        - name: search
          in: query
          schema:
            type: string
        - name: category_id
          in: query
          schema:
            type: integer
        - name: rag_status
          in: query
          schema:
            type: string
            enum: [Green, Amber, Red]
        - name: priority
          in: query
          schema:
            type: string
            enum: [High, Medium, Low]
        - name: per_page
          in: query
          schema:
            type: integer
            default: 15
      responses:
        200:
          description: Liste paginee des projets
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ProjectCollection'

  /projects/{id}:
    get:
      summary: Detail d'un projet
      tags: [Projects]
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        200:
          description: Projet trouve
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Project'

components:
  schemas:
    Project:
      type: object
      properties:
        id:
          type: integer
        project_code:
          type: string
          example: PRISM-001
        name:
          type: string
        description:
          type: string
        category:
          $ref: '#/components/schemas/Category'
        priority:
          type: string
          enum: [High, Medium, Low]
        rag_status:
          type: string
          enum: [Green, Amber, Red]
        dev_status:
          type: string
        completion_percent:
          type: integer
        owner:
          $ref: '#/components/schemas/User'
        phases:
          type: array
          items:
            $ref: '#/components/schemas/ProjectPhase'
```

---

## 8. SECURITE

### 8.1 Authentification

- Laravel Sanctum pour SPA et API tokens
- Sessions cookie pour le frontend
- Tokens API pour integrations externes
- Password policy: min 12 caracteres, 1 majuscule, 1 chiffre, 1 special

### 8.2 Roles et Permissions

| Role | Permissions |
|------|-------------|
| **Admin** | Tout acces, gestion users, config systeme |
| **Project Manager** | CRUD projets/risques/changes assignes |
| **Team Member** | Lecture tout, modification phases assignees |
| **Viewer** | Lecture seule |

### 8.3 Securite Applicative

- CSRF protection sur tous les formulaires
- XSS prevention (echappement Blade/Vue)
- SQL injection prevention (Eloquent)
- Rate limiting: 60 req/min
- Headers securite: CSP, HSTS, X-Frame-Options
- Audit logs pour actions critiques

---

## 9. IMPORT DE DONNEES

### 9.1 Mapping Excel -> Base de donnees

| Feuille Excel | Table(s) | Traitement |
|---------------|----------|------------|
| PROJECT REGISTER | projects, categories | Upsert par project_code |
| STATUS TRACKING | project_phases | Update phases |
| RISK & ISSUES LOG | risks | Upsert par risk_code |
| CHANGE LOG | change_requests | Upsert par change_code |

### 9.2 Processus d'Import

1. Upload fichier Excel
2. Validation format et headers
3. Preview des donnees detectees
4. Rapport des erreurs potentielles
5. Confirmation utilisateur
6. Import en transaction
7. Rapport final avec statistiques

---

## 10. TESTS

### 10.1 Couverture Requise

| Type | Couverture Min | Outils |
|------|----------------|--------|
| Unit Tests | 80% | PHPUnit/Pest |
| Feature Tests | 70% | PHPUnit/Pest |
| E2E Tests | Flows critiques | Playwright |

### 10.2 Tests Critiques

- Authentication flow
- CRUD Projects
- Phase updates
- Risk creation + score calculation
- Change request workflow
- Import Excel
- Dashboard KPIs

---

## 11. DEPLOIEMENT

### 11.1 Environnements

| Environnement | URL | Usage |
|---------------|-----|-------|
| Development | localhost:8080 | Dev local |
| Staging | staging.prism.local | Tests/QA |
| Production | prism.moovmoney.tg | Production |

### 11.2 CI/CD Pipeline

```yaml
# .github/workflows/ci.yml
name: CI/CD

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php artisan test --coverage

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Build Docker image
        run: docker build -t prism:latest .
      - name: Push to registry
        run: docker push registry/prism:latest

  deploy:
    needs: build
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to production
        run: |
          ssh deploy@server 'cd /app && docker-compose pull && docker-compose up -d'
```

---

## 12. CHECKLIST DE LIVRAISON

### Phase 1: Setup (Semaine 1)
- [ ] Init projet Laravel 11
- [ ] Config Docker + docker-compose
- [ ] Config PostgreSQL
- [ ] Config Redis
- [ ] Config Laravel Sanctum
- [ ] Setup CI/CD GitHub Actions
- [ ] Config ESLint + Prettier

### Phase 2: Backend Core (Semaines 2-3)
- [ ] Toutes les migrations
- [ ] Tous les Models avec relations
- [ ] Seeders categories + admin user
- [ ] ProjectService
- [ ] RiskService
- [ ] DashboardService
- [ ] Form Requests validation
- [ ] API Resources
- [ ] Controllers API complets
- [ ] Routes API

### Phase 3: Frontend Base (Semaines 4-5)
- [ ] Config Vue.js 3 + Inertia
- [ ] Config TailwindCSS theme Glass
- [ ] AppLayout + Sidebar + Header
- [ ] Tous composants Glass/
- [ ] Composants Charts/
- [ ] Pinia stores
- [ ] Routing Inertia

### Phase 4: Dashboard (Semaine 6)
- [ ] Page Dashboard
- [ ] KPIs widgets
- [ ] Charts RAG + Categories
- [ ] Critical Projects widget
- [ ] Activity Feed
- [ ] Deadlines widget

### Phase 5: Projects (Semaines 7-8)
- [ ] Liste projets + DataTable
- [ ] Filtres avances
- [ ] Fiche projet detaillee
- [ ] Formulaire Create/Edit
- [ ] Phases timeline + update
- [ ] Comments
- [ ] Activity log

### Phase 6: Risks (Semaine 9)
- [ ] Liste risques
- [ ] CRUD risque
- [ ] Risk Matrix
- [ ] Status workflow

### Phase 7: Changes (Semaine 10)
- [ ] Liste change requests
- [ ] CRUD change
- [ ] Workflow approbation

### Phase 8: Import (Semaine 11)
- [ ] ExcelImportService
- [ ] Interface upload
- [ ] Preview + validation
- [ ] Rapport erreurs

### Phase 9: Notifications (Semaine 12)
- [ ] Laravel Reverb config
- [ ] Events projets
- [ ] Notifications bell
- [ ] Email notifications

### Phase 10: Admin (Semaine 13)
- [ ] Gestion users
- [ ] Roles & permissions
- [ ] Gestion categories

### Phase 11: Tests & QA (Semaines 14-15)
- [ ] Tests unitaires
- [ ] Tests feature API
- [ ] Tests E2E
- [ ] Bug fixes

### Phase 12: Deploiement (Semaine 16)
- [ ] Config production
- [ ] Documentation complete
- [ ] Import donnees initiales
- [ ] Formation utilisateurs
- [ ] Go-live

---

## ANNEXES

### A. Variables d'Environnement

```env
# .env.example

APP_NAME="PRISM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://prism.moovmoney.tg

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=prism
DB_USERNAME=prism
DB_PASSWORD=

REDIS_HOST=redis
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

BROADCAST_DRIVER=reverb
REVERB_APP_ID=prism
REVERB_APP_KEY=
REVERB_APP_SECRET=

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@prism.moovmoney.tg
```

### B. Commandes Utiles

```bash
# Installation
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app npm install
docker-compose exec app php artisan migrate --seed
docker-compose exec app npm run build

# Development
docker-compose exec app php artisan serve
docker-compose exec app npm run dev

# Tests
docker-compose exec app php artisan test
docker-compose exec app php artisan test --coverage

# Maintenance
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Import
docker-compose exec app php artisan import:excel storage/app/data.xlsx
```

---

**Document genere pour l'equipe de developpement PRISM**
**Version 1.0 - Janvier 2026**
