<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $fillable = ['phone', 'purpose', 'code_hash', 'attempts', 'expires_at', 'verified_at', 'ip'];

    protected $casts = ['expires_at' => 'datetime', 'verified_at' => 'datetime'];
}
