<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;

class TaskService
{
    /**
     * Ambil task list (kiri)
     */
    public function getTasksForUser(User $user, ?int $categoryId = null)
    {
        $query = Task::query()
            ->where('user_id', $user->id)
            ->where('status', 'yet')
            ->with('categories')
            ->orderBy('due', 'asc')
            ->orderBy('priority', 'desc');

        if ($categoryId) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        return $query->get();
    }

    /**
     * Ambil task pertama untuk auto-select
     */
    public function getFirstTaskForUser(User $user)
    {
        return Task::where('user_id', $user->id)
            ->where('status', 'yet')
            ->with(['categories', 'subtasks'])
            ->orderBy('due', 'asc')
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Ambil detail task (panel kanan)
     */
    public function getTaskDetail(User $user, int $taskId)
    {
        return Task::where('id', $taskId)
            ->where('user_id', $user->id)
            ->with(['categories', 'subtasks'])
            ->firstOrFail();
    }
}
