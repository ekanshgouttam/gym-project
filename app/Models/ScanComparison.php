<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanComparison extends Model
{
    /** AI comparisons a member can generate per day (each one sends six photos to the API). */
    public const DAILY_LIMIT = 10;

    protected $fillable = ['user_id', 'from_scan_id', 'to_scan_id', 'status', 'summary', 'analysis', 'error'];

    protected $casts = ['analysis' => 'array'];

    public function from()
    {
        return $this->belongsTo(BodyScan::class, 'from_scan_id');
    }

    public function to()
    {
        return $this->belongsTo(BodyScan::class, 'to_scan_id');
    }
}
