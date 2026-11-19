<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A support ticket a gym owner raises with FlexFit. */
class SupportRequest extends Model
{
    public const CATEGORIES = ['general' => 'General', 'billing' => 'Billing & subscription', 'technical' => 'Technical issue', 'ai_scans' => 'AI body scans', 'feature' => 'Feature request'];
    public const PRIORITIES = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];
    public const STATUSES = ['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];

    protected $fillable = ['gym_id', 'user_id', 'subject', 'message', 'category', 'priority', 'status', 'admin_reply', 'replied_at', 'resolved_at'];

    protected $casts = ['replied_at' => 'datetime', 'resolved_at' => 'datetime'];

    public function gym()
    {
        return $this->belongsTo(Gym::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function statusBadge(): string
    {
        return [
            'open' => 'bg-volt-400/15 text-volt-300',
            'in_progress' => 'bg-sky-500/15 text-sky-300',
            'resolved' => 'bg-emerald-500/15 text-emerald-300',
            'closed' => 'bg-slate-500/15 text-slate-400',
        ][$this->status] ?? 'bg-slate-500/15 text-slate-400';
    }

    public function priorityBadge(): string
    {
        return [
            'low' => 'bg-slate-500/15 text-slate-400',
            'normal' => 'bg-slate-500/15 text-slate-300',
            'high' => 'bg-ember-500/15 text-ember-300',
            'urgent' => 'bg-rose-500/15 text-rose-300',
        ][$this->priority] ?? '';
    }
}
