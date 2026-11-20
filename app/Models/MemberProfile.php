<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MemberProfile extends Model
{
    public const GOALS = [
        'fat_loss' => 'Fat loss',
        'muscle_gain' => 'Muscle gain',
        'recomposition' => 'Body recomposition',
        'strength' => 'Strength',
        'endurance' => 'Endurance',
        'general_fitness' => 'General fitness',
    ];

    public const DEFAULT_PREFS = ['membership' => true, 'scans' => true, 'plans' => true, 'promotions' => false];

    /** How the member registered. */
    public const SOURCES = ['qr' => 'QR scan', 'link' => 'Join link', 'manual' => 'Added by staff', 'import' => 'CSV import', 'app' => 'Mobile app'];

    protected $fillable = [
        'user_id', 'member_code', 'source', 'registered_by', 'trainer_id', 'date_of_birth', 'gender', 'height_cm', 'starting_weight_kg', 'target_weight_kg',
        'fitness_goal', 'emergency_contact_name', 'emergency_contact_phone', 'phone_verified_at', 'notification_prefs',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'phone_verified_at' => 'datetime',
        'notification_prefs' => 'array',
        'height_cm' => 'float',
        'starting_weight_kg' => 'float',
        'target_weight_kg' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The trainer this member is assigned to. */
    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function goalLabel(): string
    {
        return self::GOALS[$this->fitness_goal] ?? '—';
    }

    public function age(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function prefs(): array
    {
        return array_merge(self::DEFAULT_PREFS, $this->notification_prefs ?? []);
    }

    /**
     * Next sequential member code for a gym, e.g. IF-IRO-000042.
     * Call inside a transaction: the row lock keeps codes unique under concurrent sign-ups.
     */
    public static function nextCode(Gym $gym): string
    {
        $letters = strtoupper(substr(preg_replace('/[^a-z]/i', '', $gym->slug), 0, 3));
        $prefix = 'IF-' . ($letters !== '' ? $letters : 'GYM') . $gym->id . '-';

        $last = DB::table('member_profiles')
            ->where('member_code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('member_code')
            ->value('member_code');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
