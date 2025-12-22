<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TaskService;
use Illuminate\Support\Facades\Log;


class TaskController extends Controller
{
    protected TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        
        $this->taskService = $taskService;
    }

    /**
     * Ambil task list (panel kiri)
     */
    public function index(Request $request)
    {
        $categoryId = $request->query('category_id');

        $tasks = $this->taskService->getTasksForUser(
            $request->user(),
            $categoryId
        );

        return response()->json([
            'tasks' => $tasks,
        ]);
    }

    /**
     * Ambil detail task (panel kanan)
     */
    public function show(Request $request, int $id)
    {
        $task = $this->taskService->getTaskDetail(
            $request->user(),
            $id
        );

        return response()->json([
            'task' => $task,
        ]);
    }

    /**
     * Ambil task pertama (auto select)
     */
    public function first(Request $request)
    {
        $task = $this->taskService->getFirstTaskForUser(
            $request->user()
        );

        return response()->json([
            'task' => $task,
        ]);
    }


    /**
     * Toggle status task (checkbox)
     */
    public function toggleStatus(Request $request, int $id)
    {
        // Log::info('user debug 3', [$request->user()]);
        
        $task = $this->taskService->toggleTaskStatus(
            $request->user(),
            $id
        );

        
        return response()->json([
            'task' => $task,
        ]);
    }


    /**
     * Update task detail (note / due)
     */
    public function updateDetail(Request $request, int $id)
    {
        $validated = $request->validate([
            'note' => 'nullable|string',
            'due'  => 'nullable|date',
            'title' => 'nullable|string'
        ]);

        $task = $this->taskService->updateTaskDetail(
            $request->user(),
            $id,
            $validated
        );

        return response()->json([
            'task' => $task,
        ]);
    }

    public function toggleSubtask(Request $request, int $id)
    {
        $subtask = $this->taskService->toggleSubtask(
            $request->user(),
            $id
        );

        return response()->json([
            'subtask' => $subtask
        ]);
    }



    public function storeSubtask(Request $request, int $taskId)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:255',
        ]);

        $subtask = $this->taskService->createSubtask(
            $request->user(),
            $taskId,
            $validated['content']
        );

        return response()->json([
            'subtask' => $subtask,
        ], 201);
    }

    public function destroySubtask(Request $request, int $id)
    {
        $this->taskService->deleteSubtask(
            $request->user(),
            $id
        );

        return response()->json([
            'message' => 'deleted'
        ]);
    }




}
