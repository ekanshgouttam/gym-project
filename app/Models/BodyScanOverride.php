<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodyScanOverride extends Model
{
    protected $fillable = ['member_id', 'granted_by', 'reason', 'expires_at', 'used_at', 'used_for_scan_id'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];

    public function scopeUsable($query)
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
