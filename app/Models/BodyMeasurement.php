<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodyMeasurement extends Model
{
    public const PARTS = ['chest' => 'Chest', 'waist' => 'Waist', 'hips' => 'Hips', 'arms' => 'Arms', 'thighs' => 'Thighs', 'neck' => 'Neck'];

    protected $fillable = ['user_id', 'measured_on', 'chest_cm', 'waist_cm', 'hips_cm', 'arms_cm', 'thighs_cm', 'neck_cm', 'body_fat_pct'];

    protected $casts = [
        'measured_on' => 'date',
        'chest_cm' => 'float', 'waist_cm' => 'float', 'hips_cm' => 'float',
        'arms_cm' => 'float', 'thighs_cm' => 'float', 'neck_cm' => 'float', 'body_fat_pct' => 'float',
    ];
}
