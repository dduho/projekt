<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Show login page (Web)
     */
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Login - handles both Web (session) and API (token)
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // API request - return token
        if ($request->wantsJson()) {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Les identifiants sont incorrects.'],
                ]);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'user' => new UserResource($user->load('roles')),
                'token' => $token,
            ]);
        }

        // Web request - use session
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => 'Les identifiants sont incorrects.',
        ]);
    }

    /**
     * Logout - handles both Web and API
     */
    public function logout(Request $request)
    {
        if ($request->wantsJson()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Deconnexion reussie.']);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Get current user (API)
     */
    public function user(Request $request)
    {
        return new UserResource($request->user()->load('roles'));
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user = $request->user();
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->update(['avatar' => $path]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Profil mis a jour avec succes.',
                'user' => new UserResource($user->fresh()),
            ]);
        }

        return back()->with('success', 'Profil mis a jour avec succes.');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Mot de passe mis a jour avec succes.']);
        }

        return back()->with('success', 'Mot de passe mis a jour avec succes.');
    }
}
