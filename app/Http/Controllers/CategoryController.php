<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * List categories - Web (Inertia) or API (JSON)
     */
    public function index(Request $request)
    {
        $categories = Category::withCount('projects')
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(CategoryResource::collection($categories));
        }

        return Inertia::render('Categories/Index', [
            'categories' => $categories
        ]);
    }

    /**
     * Show category (API)
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('projects'));
    }

    /**
     * Store category - Web or API
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:500',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'color' => $validated['color'] ?? '#5C6BC0',
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Catégorie créée avec succès.',
                'data' => new CategoryResource($category),
            ], 201);
        }

        return back()->with('success', 'Category created successfully!');
    }

    /**
     * Update category - Web or API
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:500',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'color' => $validated['color'] ?? $category->color,
            'description' => $validated['description'] ?? $category->description,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Catégorie mise à jour.',
                'data' => new CategoryResource($category),
            ]);
        }

        return back()->with('success', 'Category updated successfully!');
    }

    /**
     * Delete category - Web or API
     */
    public function destroy(Request $request, Category $category)
    {
        if ($category->projects()->exists()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Impossible de supprimer une catégorie qui contient des projets.',
                ], 422);
            }
            return back()->with('error', 'Cannot delete category with existing projects!');
        }

        $category->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Catégorie supprimée.',
            ]);
        }

        return back()->with('success', 'Category deleted successfully!');
    }
}
