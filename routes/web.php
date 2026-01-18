<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectPhaseController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\CommentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest Routes (Authentication)
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    // Redirect root to dashboard
    Route::get('/', fn() => redirect()->route('dashboard'));

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profile
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Projects
    Route::resource('projects', ProjectController::class);
    
    // Project Phases
    Route::put('/phases/{phase}/status', [ProjectPhaseController::class, 'updateStatus'])->name('phases.update-status');
    Route::put('/projects/{project}/phases', [ProjectPhaseController::class, 'bulkUpdate'])->name('projects.phases.bulk-update');

    // Project Comments
    Route::post('/projects/{project}/comments', [CommentController::class, 'store'])->name('projects.comments.store');
    Route::put('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Risk Analysis (ML)
    Route::post('/projects/{project}/analyze-risks', [ProjectController::class, 'analyzeRisks'])->name('projects.analyze-risks');

    // Risks
    Route::get('/risks', [RiskController::class, 'index'])->name('risks.index');
    Route::get('/risks/create', [RiskController::class, 'create'])->name('risks.create');
    Route::post('/risks', [RiskController::class, 'store'])->name('risks.store');
    Route::get('/risks/matrix', [RiskController::class, 'matrix'])->name('risks.matrix');
    Route::get('/risks/{risk}', [RiskController::class, 'show'])->name('risks.show');
    Route::put('/risks/{risk}', [RiskController::class, 'update'])->name('risks.update');
    Route::delete('/risks/{risk}', [RiskController::class, 'destroy'])->name('risks.destroy');

    // Change Requests
    Route::get('/change-requests', [ChangeRequestController::class, 'index'])->name('change-requests.index');
    Route::get('/change-requests/create', [ChangeRequestController::class, 'create'])->name('change-requests.create');
    Route::post('/change-requests', [ChangeRequestController::class, 'store'])->name('change-requests.store');
    Route::get('/change-requests/{changeRequest}', [ChangeRequestController::class, 'show'])->name('change-requests.show');
    Route::put('/change-requests/{changeRequest}', [ChangeRequestController::class, 'update'])->name('change-requests.update');
    Route::post('/change-requests/{changeRequest}/approve', [ChangeRequestController::class, 'approve'])->name('change-requests.approve');
    Route::post('/change-requests/{changeRequest}/reject', [ChangeRequestController::class, 'reject'])->name('change-requests.reject');
    Route::delete('/change-requests/{changeRequest}', [ChangeRequestController::class, 'destroy'])->name('change-requests.destroy');

    // Import
    Route::get('/import', [ImportController::class, 'index'])->name('import.index');
    Route::post('/import/validate', [ImportController::class, 'validateFile'])->name('import.validate');
    Route::post('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('/import', [ImportController::class, 'import'])->name('import.store');
    Route::get('/import/template', [ImportController::class, 'downloadTemplate'])->name('import.template');

    // Export
    Route::get('/export/projects', [ExportController::class, 'exportProjects'])->name('export.projects');
    Route::get('/export/risks', [ExportController::class, 'exportRisks'])->name('export.risks');
    Route::get('/export/changes', [ExportController::class, 'exportChangeRequests'])->name('export.changes');
    Route::get('/export/dashboard', [ExportController::class, 'exportDashboard'])->name('export.dashboard');

    // Admin - Categories
    Route::middleware('role:admin')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Admin - Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

    // Settings
    Route::get('/settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings.index');
});
