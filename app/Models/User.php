<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'gym_id',
        'name',
        'email',
        'phone',
        'avatar_path',
        'password',
        'status',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function memberProfile()
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function trainerProfile()
    {
        return $this->hasOne(TrainerProfile::class);
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function bodyScans()
    {
        return $this->hasMany(BodyScan::class);
    }

    public function weightLogs()
    {
        return $this->hasMany(WeightLog::class);
    }

    public function bodyMeasurements()
    {
        return $this->hasMany(BodyMeasurement::class);
    }

    public function workoutPlans()
    {
        return $this->hasMany(WorkoutPlan::class, 'member_id');
    }

    public function dietPlans()
    {
        return $this->hasMany(DietPlan::class, 'member_id');
    }

    /** The membership currently in force, if any. */
    public function activeMembership(): ?Membership
    {
        return $this->memberships()
            ->with('plan')
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', today())
            ->whereDate('ends_on', '>=', today())
            ->orderByDesc('ends_on')
            ->first();
    }

    public function hasActiveMembership(): bool
    {
        return $this->activeMembership() !== null;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? route('media.avatar', ['user' => $this->id, 'v' => substr(md5($this->avatar_path), 0, 8)]) : null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** The user's single primary role (each user holds exactly one). */
    public function primaryRole(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function roleLabel(): string
    {
        return Roles::label($this->primaryRole());
    }

    public function dashboardRoute(): string
    {
        return Roles::DASHBOARDS[$this->primaryRole()] ?? 'home';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Roles::SUPER_ADMIN);
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last);
    }
}
