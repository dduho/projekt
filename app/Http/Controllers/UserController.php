<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * List users - Web (Inertia) or API (JSON)
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $users = User::query()
                ->with('roles')
                ->when($request->search, function ($q, $search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })
                ->when($request->role, fn($q, $role) => $q->role($role))
                ->orderBy('name')
                ->paginate($request->per_page ?? 15);

            return response()->json([
                'data' => UserResource::collection($users),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]);
        }

        $users = User::with('roles')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * Show user (API)
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }

    /**
     * Store user - Web or API
     */
    public function store(Request $request)
    {
        if ($request->wantsJson()) {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
                'role' => 'required|string|exists:roles,name',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole($request->role);

            return response()->json([
                'message' => 'Utilisateur créé avec succès.',
                'data' => new UserResource($user->load('roles')),
            ], 201);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:admin,manager,user,guest'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'User created successfully!');
    }

    /**
     * Update user - Web or API
     */
    public function update(Request $request, User $user)
    {
        if ($request->wantsJson()) {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
            ]);

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            return response()->json([
                'message' => 'Utilisateur mis à jour.',
                'data' => new UserResource($user),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:admin,manager,user,guest'
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password'])
            ]);
        }

        $user->syncRoles([$validated['role']]);

        return back()->with('success', 'User updated successfully!');
    }

    /**
     * Delete user - Web or API
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
                ], 422);
            }
            return back()->with('error', 'You cannot delete your own account!');
        }

        $user->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Utilisateur supprimé.',
            ]);
        }

        return back()->with('success', 'User deleted successfully!');
    }

    /**
     * Update user role (API)
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'Rôle mis à jour.',
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    /**
     * Reset user password (API)
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'password' => 'required|min:8',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Mot de passe réinitialisé.',
        ]);
    }
}
