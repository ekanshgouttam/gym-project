<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    protected $fillable = ['gym_id', 'name', 'slug', 'duration_months', 'price', 'description', 'features', 'is_featured', 'is_active', 'sort_order'];

    protected $casts = ['features' => 'array', 'is_featured' => 'boolean', 'is_active' => 'boolean', 'price' => 'decimal:2'];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    /** Active plans offered at a branch: its own plans plus network-wide ones. */
    public function scopeAvailableAt($query, ?int $gymId)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($gymId) {
                $q->whereNull('gym_id');
                if ($gymId) {
                    $q->orWhere('gym_id', $gymId);
                }
            })
            ->orderBy('sort_order')->orderBy('price');
    }

    public function monthlyPrice(): float
    {
        return round((float) $this->price / max(1, $this->duration_months), 0);
    }

    public function durationLabel(): string
    {
        $m = (int) $this->duration_months;
        if ($m % 12 === 0) {
            $years = intdiv($m, 12);
            return $years . ' ' . ($years === 1 ? 'year' : 'years');
        }

        return $m . ' ' . ($m === 1 ? 'month' : 'months');
    }
}
