<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A prospective member (walk-in, call, website…) the gym is following up. */
class Enquiry extends Model
{
    public const SOURCES = ['walk_in' => 'Walk-in', 'phone' => 'Phone call', 'website' => 'Website', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'referral' => 'Referral', 'other' => 'Other'];
    public const STATUSES = ['new' => 'New', 'follow_up' => 'Follow-up', 'trial' => 'Trial session', 'converted' => 'Joined', 'lost' => 'Lost'];

    protected $fillable = ['gym_id', 'name', 'phone', 'email', 'source', 'interest', 'status', 'next_follow_up_on', 'notes', 'assigned_to', 'converted_user_id', 'converted_at', 'lost_reason'];

    protected $casts = ['next_follow_up_on' => 'date', 'converted_at' => 'datetime'];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function member()
    {
        return $this->belongsTo(User::class, 'converted_user_id');
    }

    public function activities()
    {
        return $this->hasMany(EnquiryActivity::class)->latest('id');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['new', 'follow_up', 'trial']);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['new', 'follow_up', 'trial'], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->next_follow_up_on && $this->next_follow_up_on->lt(today());
    }

    public function log(?User $by, string $note): void
    {
        $this->activities()->create(['user_id' => optional($by)->id, 'note' => mb_substr($note, 0, 255)]);
    }

    public function statusBadge(): string
    {
        return [
            'new' => 'bg-volt-400/15 text-volt-300',
            'follow_up' => 'bg-sky-500/15 text-sky-300',
            'trial' => 'bg-fuchsia-500/15 text-fuchsia-300',
            'converted' => 'bg-emerald-500/15 text-emerald-300',
            'lost' => 'bg-slate-500/15 text-slate-400',
        ][$this->status] ?? '';
    }
}
