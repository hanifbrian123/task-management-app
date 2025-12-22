<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Subtask;

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

    /**
     * Toggle status task (yet <-> done)
     */
    public function toggleTaskStatus(User $user, int $taskId)
    {
        // Log::info('user', [
        //     $user
        // ]);
        $task = Task::where('id', $taskId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $task->status = $task->status === 'yet' ? 'done' : 'yet';
        $task->save();

        return $task;
    }

    /**
     * Update detail task (note / due)
     */
    public function updateTaskDetail(User $user, int $taskId, array $data)
    {
        $task = Task::where('id', $taskId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (array_key_exists('note', $data)) {
            $task->note = $data['note'];
        }

        if (array_key_exists('due', $data)) {
            $task->due = $data['due'];
        }

        if (array_key_exists('title', $data)) {
            $task->title = $data['title'];
        }

        $task->save();

        return $task->fresh(['categories', 'subtasks']);
    }

    public function toggleSubtask(User $user, int $subtaskId)
    {
        $subtask = Subtask::where('id', $subtaskId)
            ->whereHas('task', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->firstOrFail();

        $subtask->status = $subtask->status === 'yet' ? 'done' : 'yet';
        $subtask->save();

        return $subtask;
    }

    public function createSubtask(User $user, int $taskId, string $content)
    {
        $task = Task::where('id', $taskId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return $task->subtasks()->create([
            'content' => $content,
            'status'  => 'yet',
        ]);
    }

    public function deleteSubtask(User $user, int $subtaskId): void
    {
        $subtask = Subtask::where('id', $subtaskId)
            ->whereHas('task', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->firstOrFail();

        $subtask->delete();
    }

}
