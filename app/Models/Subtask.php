<?php

namespace App\Models;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

class Subtask extends Model
{
    protected $fillable = [
        'content',
        'status',
        'task_id',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
