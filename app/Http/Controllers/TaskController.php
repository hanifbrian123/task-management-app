<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TaskService;


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

}
