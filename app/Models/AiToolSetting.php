<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiToolSetting extends Model
{
    protected $fillable = [
        'tool_key',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
