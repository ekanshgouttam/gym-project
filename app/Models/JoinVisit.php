<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One browser session opening a gym's join page (from the QR code or a shared link). */
class JoinVisit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['gym_id', 'source'];
}
