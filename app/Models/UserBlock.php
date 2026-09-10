<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One person blocking another from messaging. See App\Domain\Messaging\Blocking. */
class UserBlock extends Model
{
    protected $fillable = ['blocker_id', 'blocked_id'];
}
