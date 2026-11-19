<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Gym extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'email', 'phone', 'address', 'city', 'logo_path', 'settings', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'settings' => 'array'];

    /** Owner-editable preferences (Settings page) with their defaults. */
    public const SETTING_DEFAULTS = [
        'reminder_days' => [7, 3, 1],      // days before expiry to remind members
        'expiry_reminders' => true,
        'payment_reminders' => true,       // nudge members with an unpaid membership request
        'notify_owner' => true,            // owner gets a daily summary of what was sent
        'opening_hours' => '',
        'about' => '',
    ];

    public function setting(string $key)
    {
        return array_key_exists($key, $this->settings ?? []) ? $this->settings[$key] : (self::SETTING_DEFAULTS[$key] ?? null);
    }

    protected static function booted()
    {
        static::creating(function (Gym $gym) {
            // Readable QR link for new gyms, e.g. /join/gold-gym-andheri. Rotating it later gives a random token.
            $gym->join_token = $gym->join_token ?: static::readableJoinToken((string) $gym->slug);
        });
    }

    public static function newJoinToken(): string
    {
        return Str::random(24);
    }

    /** A URL-safe slug that no other gym uses yet. */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'gym';
        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    public static function readableJoinToken(string $slug): string
    {
        $base = substr(Str::slug($slug) ?: 'gym', 0, 32);
        $token = $base;
        for ($i = 2; static::where('join_token', $token)->exists(); $i++) {
            $token = $base . '-' . $i;
        }

        return $token;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? route('media.gym-logo', ['gym' => $this, 'v' => substr(md5($this->logo_path), 0, 8)]) : null;
    }

    /** The gym's current FlexFit subscription. */
    public function subscription()
    {
        return $this->hasOne(GymSubscription::class)->latestOfMany();
    }

    public function subscriptionPayments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function aiUsage()
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function supportRequests()
    {
        return $this->hasMany(SupportRequest::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plans()
    {
        return $this->hasMany(MembershipPlan::class);
    }

    /** The URL printed in the QR code: the join page, tagged so scans can be counted. */
    public function qrUrl(): string
    {
        return $this->joinUrl() . '?src=qr';
    }

    /** The public URL of this gym's registration page. */
    public function joinUrl(): string
    {
        return route('join.show', $this->join_token);
    }
}
