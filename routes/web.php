<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CategoryController;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::middleware('auth')->group(function () {
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/first', [TaskController::class, 'first']);
    Route::get('/tasks/{id}', [TaskController::class, 'show'])
    ->whereNumber('id')
    ->middleware('auth');
    Route::patch('/tasks/{id}/toggle-status', [TaskController::class, 'toggleStatus']);
    Route::get('/test-toggle', function () {
        return view('test-toggle');
    });
    Route::patch('/tasks/{id}', [TaskController::class, 'updateDetail']);
});

Route::get('/categories', [CategoryController::class, 'index'])
    ->middleware('auth');


Route::patch('/subtasks/{id}/toggle', [TaskController::class, 'toggleSubtask'])
    ->middleware('auth');


Route::post('/tasks/{taskId}/subtasks', [TaskController::class, 'storeSubtask'])
    ->middleware('auth');

Route::delete('/subtasks/{id}', [TaskController::class, 'destroySubtask'])
    ->middleware('auth');

Route::post('/tasks/{taskId}/categories', [TaskController::class, 'attachCategory'])
    ->middleware('auth');

Route::delete('/tasks/{taskId}/categories/{categoryId}', [TaskController::class, 'detachCategory'])
    ->middleware('auth');



Route::patch('/subtasks/{id}', [TaskController::class, 'updateSubtask'])
    ->middleware('auth');

Route::post('/tasks', [TaskController::class, 'store'])
    ->middleware('auth');

Route::patch('/tasks/{taskId}/priority', [TaskController::class, 'updatePriority'])
    ->middleware('auth');

Route::post('/categories', [CategoryController::class, 'store'])
    ->middleware('auth');


Route::patch('/categories/{id}', [CategoryController::class, 'update'])
    ->middleware('auth');

Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
    ->middleware('auth');

Route::post('/tasks/reorder', [TaskController::class, 'reorder'])
->middleware('auth');

Route::post('/tasks/{taskId}/subtasks/reorder', [TaskController::class, 'reorderSubtasks'])
    ->middleware('auth');
Route::get('/tasks/completed', [TaskController::class, 'completed'])
    ->middleware('auth');

require __DIR__.'/auth.php';
