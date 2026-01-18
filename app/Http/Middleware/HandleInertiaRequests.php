<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        $userData = null;
        if ($user) {
            try {
                // Charger les relations explicitement
                $user->load('roles', 'permissions');
                
                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name ?? 'user',
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->permissions->pluck('name')->toArray(),
                ];
            } catch (\Exception $e) {
                Log::error('HandleInertiaRequests error: ' . $e->getMessage());
                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'admin', // Fallback pour test
                    'roles' => ['admin'],
                    'permissions' => [],
                ];
            }
        }
        
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $userData,
            ],
            'locale' => app()->getLocale(),
            'translations' => function () {
                $locale = app()->getLocale();
                $translations = [];
                
                // Load JSON translations
                $jsonPath = lang_path("{$locale}.json");
                if (file_exists($jsonPath)) {
                    $translations = json_decode(file_get_contents($jsonPath), true);
                }
                
                // Load enum translations
                $enumPath = lang_path("{$locale}/enums.php");
                if (file_exists($enumPath)) {
                    $translations['enums'] = require $enumPath;
                }
                
                return $translations;
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'import_stats' => fn () => $request->session()->get('import_stats'),
                'import_errors' => fn () => $request->session()->get('import_errors'),
            ],
            'errors' => fn () => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object) [],
        ]);
    }
}
