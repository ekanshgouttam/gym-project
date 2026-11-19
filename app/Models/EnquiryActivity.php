<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnquiryActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['enquiry_id', 'user_id', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
