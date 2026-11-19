<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['user_id', 'gym_id', 'checked_in_at', 'checked_out_at', 'method'];

    protected $casts = ['checked_in_at' => 'datetime', 'checked_out_at' => 'datetime'];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function durationMinutes(): ?int
    {
        return $this->checked_out_at ? $this->checked_in_at->diffInMinutes($this->checked_out_at) : null;
    }
}
