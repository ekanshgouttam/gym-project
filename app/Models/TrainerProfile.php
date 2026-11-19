<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerProfile extends Model
{
    protected $fillable = ['user_id', 'specialty', 'bio', 'experience_years', 'certifications', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
