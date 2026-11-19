<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['gym_id', 'name', 'email', 'phone', 'subject', 'message'];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }
}
