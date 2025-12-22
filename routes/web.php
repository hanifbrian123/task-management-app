<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CategoryController;

Route::get('/', function () {
    return view('welcome');
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
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
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







require __DIR__.'/auth.php';
