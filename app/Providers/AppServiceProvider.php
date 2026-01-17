<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Risk;
use App\Models\ChangeRequest;
use App\Policies\ProjectPolicy;
use App\Policies\RiskPolicy;
use App\Policies\ChangeRequestPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Risk::class, RiskPolicy::class);
        Gate::policy(ChangeRequest::class, ChangeRequestPolicy::class);

        // Define manage-users gate for admin routes
        Gate::define('manage-users', function ($user) {
            return $user->hasRole('admin');
        });

        // Inertia configuration
        Inertia::share([
            'auth' => function () {
                /** @var \App\Models\User|null $user */
                $user = Auth::user();

                if (!$user) {
                    return ['user' => null];
                }

                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar ?? null,
                        'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [],
                        'permissions' => method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [],
                    ],
                ];
            },
            'flash' => function () {
                return [
                    'success' => session('success'),
                    'error' => session('error'),
                    'warning' => session('warning'),
                    'info' => session('info'),
                ];
            },
        ]);
    }
}

