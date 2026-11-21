<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A person's request to join a gym as a member, made from the mobile app. */
class RegistrationRequest extends Model
{
    public const SOURCES = ['app' => 'Gym list', 'qr' => 'QR scan'];

    protected $fillable = ['user_id', 'gym_id', 'status', 'source', 'rejection_reason', 'decided_by', 'decided_at'];

    protected $casts = ['decided_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
