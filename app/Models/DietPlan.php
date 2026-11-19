<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietPlan extends Model
{
    protected $fillable = ['member_id', 'trainer_id', 'title', 'daily_calories', 'macros', 'meals', 'notes', 'is_active'];

    protected $casts = ['macros' => 'array', 'meals' => 'array', 'is_active' => 'boolean'];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
