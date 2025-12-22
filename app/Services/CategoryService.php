<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Subtask;
use App\Models\Category;

class CategoryService
{
    public function create(User $user, array $data)
    {
        return Category::create([
            'name' => $data['name'],
            'user_id' => $user->id,
        ]);
    }
    public function update(User $user, int $categoryId, array $data)
    {
        $category = Category::where('id', $categoryId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $category->update([
            'name' => $data['name'],
        ]);

        return $category;
    }

    public function delete(User $user, int $categoryId)
    {
        $category = Category::where('id', $categoryId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // detach dari task (pivot)
        $category->tasks()->detach();

        $category->delete();

        return true;
    }

}