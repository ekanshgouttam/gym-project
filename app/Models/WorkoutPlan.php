<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutPlan extends Model
{
    protected $fillable = ['member_id', 'trainer_id', 'title', 'goal', 'starts_on', 'ends_on', 'days', 'notes', 'is_active'];

    protected $casts = ['days' => 'array', 'starts_on' => 'date', 'ends_on' => 'date', 'is_active' => 'boolean'];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
