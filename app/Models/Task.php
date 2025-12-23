<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title',
        'due',
        'note',
        'priority',
        'status',
        'reminder_at',
        'position',
        'user_id',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'due' => 'date',          // optional tapi sangat disarankan
        'reminder_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function subtasks()
    {
        return $this->hasMany(Subtask::class)->orderBy('position', 'asc');
    }


}
