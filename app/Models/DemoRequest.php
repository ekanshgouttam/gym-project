<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    public const SIZES = ['<100' => 'Under 100 members', '100-300' => '100–300 members', '300-700' => '300–700 members', '700+' => '700+ members', 'multi' => 'Multiple branches'];
    public const TIMES = ['morning' => 'Morning (10–12)', 'afternoon' => 'Afternoon (12–4)', 'evening' => 'Evening (4–7)'];
    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'scheduled' => 'Demo scheduled', 'converted' => 'Signed up', 'closed' => 'Closed'];

    protected $fillable = ['name', 'email', 'phone', 'gym_name', 'city', 'members', 'preferred_date', 'preferred_time', 'message', 'status'];

    protected $casts = ['preferred_date' => 'date'];

    public function statusBadge(): string
    {
        return [
            'new' => 'bg-volt-400/15 text-volt-300',
            'contacted' => 'bg-sky-500/15 text-sky-300',
            'scheduled' => 'bg-ember-500/15 text-ember-300',
            'converted' => 'bg-emerald-500/15 text-emerald-300',
            'closed' => 'bg-slate-500/15 text-slate-400',
        ][$this->status] ?? '';
    }
}
