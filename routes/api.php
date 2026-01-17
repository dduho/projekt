<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('api.profile.update');
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('api.password.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('api.dashboard.stats');
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis'])->name('api.dashboard.kpis');
    Route::get('/dashboard/rag-distribution', [DashboardController::class, 'ragDistribution'])->name('api.dashboard.rag');
    Route::get('/dashboard/category-distribution', [DashboardController::class, 'categoryDistribution'])->name('api.dashboard.categories');
    Route::get('/dashboard/dev-status', [DashboardController::class, 'devStatusDistribution'])->name('api.dashboard.dev-status');
    Route::get('/dashboard/critical-projects', [DashboardController::class, 'criticalProjects'])->name('api.dashboard.critical');
    Route::get('/dashboard/activities', [DashboardController::class, 'recentActivities'])->name('api.dashboard.activities');
    Route::get('/dashboard/deadlines', [DashboardController::class, 'upcomingDeadlines'])->name('api.dashboard.deadlines');
    Route::get('/dashboard/risk-matrix', [DashboardController::class, 'riskMatrix'])->name('api.dashboard.risk-matrix');
    Route::post('/dashboard/refresh-cache', [DashboardController::class, 'refreshCache'])->name('api.dashboard.refresh');

    // Projects
    Route::apiResource('projects', ProjectController::class);
    Route::get('/projects/{project}/phases', [ProjectController::class, 'phases'])->name('api.projects.phases');
    Route::put('/projects/{project}/phases/{phase}', [ProjectController::class, 'updatePhase'])->name('api.projects.phases.update');
    Route::get('/projects/{project}/risks', [ProjectController::class, 'risks'])->name('api.projects.risks');
    Route::get('/projects/{project}/changes', [ProjectController::class, 'changes'])->name('api.projects.changes');
    Route::get('/projects/{project}/comments', [ProjectController::class, 'comments'])->name('api.projects.comments');
    Route::post('/projects/{project}/comments', [ProjectController::class, 'addComment'])->name('api.projects.comments.store');
    Route::get('/projects/{project}/activities', [ProjectController::class, 'activity'])->name('api.projects.activities');
    Route::post('/projects/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('api.projects.duplicate');
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('api.projects.archive');
    Route::post('/projects/{id}/restore', [ProjectController::class, 'restore'])->name('api.projects.restore');

    // Risks
    Route::apiResource('risks', RiskController::class);
    Route::get('/risks-matrix', [RiskController::class, 'matrix'])->name('api.risks.matrix');
    Route::patch('/risks/{risk}/status', [RiskController::class, 'updateStatus'])->name('api.risks.status');

    // Change Requests
    Route::apiResource('change-requests', ChangeRequestController::class)->parameters([
        'change-requests' => 'changeRequest'
    ]);
    Route::post('/change-requests/{changeRequest}/approve', [ChangeRequestController::class, 'approve'])->name('api.change-requests.approve');
    Route::post('/change-requests/{changeRequest}/reject', [ChangeRequestController::class, 'reject'])->name('api.change-requests.reject');
    Route::post('/change-requests/{changeRequest}/review', [ChangeRequestController::class, 'startReview'])->name('api.change-requests.review');

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Users (Admin only)
    Route::middleware('can:manage-users')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('api.users.role');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('api.users.reset-password');
    });

    // Import
    Route::post('/import/validate', [ImportController::class, 'validateFile'])->name('api.import.validate');
    Route::post('/import/excel', [ImportController::class, 'excel'])->name('api.import.excel');

    // Export
    Route::get('/export/projects', [ExportController::class, 'exportProjects'])->name('api.export.projects');
    Route::get('/export/risks', [ExportController::class, 'exportRisks'])->name('api.export.risks');
    Route::get('/export/changes', [ExportController::class, 'exportChangeRequests'])->name('api.export.changes');
    Route::get('/export/dashboard', [ExportController::class, 'exportDashboard'])->name('api.export.dashboard');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('api.notifications.unread');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('api.notifications.count');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('api.notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('api.notifications.destroy-all');
});
