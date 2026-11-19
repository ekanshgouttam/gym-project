<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Records that a reminder of a given kind was sent for a membership, so it is never sent twice. */
class MembershipReminder extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['membership_id', 'kind'];
}
