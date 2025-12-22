<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Task;
use App\Models\Category;
use App\Models\Subtask;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // USER
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@test.com',
            'password' => Hash::make('password'),
        ]);

        // CATEGORIES
        $work = Category::create([
            'name' => 'Work',
            'user_id' => $user->id,
        ]);

        $personal = Category::create([
            'name' => 'Personal',
            'user_id' => $user->id,
        ]);

        // TASK
        $task1 = Task::create([
            'title' => 'Finish Laravel Task App',
            'due' => now()->addDays(3),
            'note' => 'Core features first',
            'priority' => 3,
            'status' => 'yet',
            'user_id' => $user->id,
        ]);

        $task2 = Task::create([
            'title' => 'Prepare Presentation',
            'due' => now()->addDays(1),
            'note' => 'Slides and demo',
            'priority' => 4,
            'status' => 'yet',
            'user_id' => $user->id,
        ]);

        // ATTACH CATEGORY
        $task1->categories()->attach([$work->id]);
        $task2->categories()->attach([$personal->id]);

        // SUBTASK
        Subtask::create([
            'content' => 'Design UI',
            'status' => 'done',
            'task_id' => $task1->id,
        ]);

        Subtask::create([
            'content' => 'Implement backend',
            'status' => 'yet',
            'task_id' => $task1->id,
        ]);
    }
}
