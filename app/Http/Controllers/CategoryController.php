<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }
    
    public function index(Request $request)
    {
        return response()->json([
            'categories' => $request->user()->categories()->get()
        ]);
    }
    public function store(Request $request)
    {
        Log::info('debug categories 1', ['user_id' => $request->user()->id]);
        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);


        $category = $this->categoryService->create(
            $request->user(),
            $validated
        );

        return response()->json([
            'category' => $category
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $category = $this->categoryService->update(
            $request->user(),
            $id,
            $validated
        );

        return response()->json(['category' => $category]);
    }

    public function destroy(Request $request, int $id)
    {
        $this->categoryService->delete(
            $request->user(),
            $id
        );

        return response()->json(['success' => true]);
    }


}
