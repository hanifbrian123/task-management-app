<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Subtask;

class TaskService
{
    /**
     * Ambil task list (kiri) + search
     */
    public function getTasksForUser(
        User $user,
        ?int $categoryId = null,
        ?string $search = null
    ) {
        $query = Task::query()
            ->where('user_id', $user->id)
            ->where('status', 'yet')
            ->with('categories')
            ->orderBy('due', 'asc')
            ->orderBy('priority', 'desc');

        // FILTER CATEGORY
        if ($categoryId) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        // 🔍 SEARCH (TITLE, NOTE, SUBTASK)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('note', 'like', "%{$search}%")
                ->orWhereHas('subtasks', function ($s) use ($search) {
                    $s->where('content', 'like', "%{$search}%");
                });
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



    public function attachCategory(User $user, int $taskId, int $categoryId)
    {
        $task = Task::where('id', $taskId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $task->categories()->syncWithoutDetaching([$categoryId]);

        return $task->load('categories');
    }


    public function detachCategory(User $user, int $taskId, int $categoryId)
    {
        $task = Task::where('id', $taskId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $task->categories()->detach($categoryId);

        return $task->load('categories');
    }


    public function updateSubtask(User $user, int $subtaskId, array $data)
    {
        $subtask = Subtask::where('id', $subtaskId)
            ->whereHas('task', fn ($q) => $q->where('user_id', $user->id))
            ->firstOrFail();

        $subtask->update([
            'content' => $data['content'] ?? $subtask->content,
        ]);

        return $subtask;
    }


    public function createTask(User $user, array $data)
    {
        return Task::create([
            'title'    => $data['title'],
            'status'   => 'yet',
            'priority' => 3,
            'user_id'  => $user->id,
        ]);
    }


    public function updatePriority(User $user, int $taskId, $priority=null)
    {
        Log::info('task debug', [
            'task_id' => $taskId, 'user_id' => $user->id
        ]);
        logger()->info('user id', [$user->id]);

        $task = Task::where('id', $taskId)
            ->where('user_id', $user->id)
            ->firstOrFail();
        
        $task->priority = $priority; // bisa null
        $task->save();

        return $task;
    }


    public function getTasks(User $user, ?int $categoryId = null, ?string $search = null)
    {
        return Task::query()
            ->where('user_id', $user->id)
            ->where('status', 'yet')

            // FILTER CATEGORY
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->whereHas('categories', fn ($c) =>
                    $c->where('categories.id', $categoryId)
                );
            })

            // SEARCH
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('subtasks', function ($s) use ($search) {
                            $s->where('content', 'like', "%{$search}%");
                        });
                });
            })

            ->orderBy('created_at', 'desc')
            ->get();
    }

}
