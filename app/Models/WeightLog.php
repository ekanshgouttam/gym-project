<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightLog extends Model
{
    protected $fillable = ['user_id', 'logged_on', 'weight_kg', 'note'];

    protected $casts = ['logged_on' => 'date', 'weight_kg' => 'float'];
}
