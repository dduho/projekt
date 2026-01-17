<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount('projects')
            ->orderBy('name')
            ->get();

        return response()->json(CategoryResource::collection($categories));
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('projects'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:categories',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:500',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color ?? '#5C6BC0',
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Catégorie créée avec succès.',
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:500',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color ?? $category->color,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Catégorie mise à jour.',
            'data' => new CategoryResource($category),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->projects()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une catégorie qui contient des projets.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Catégorie supprimée.',
        ]);
    }
}
