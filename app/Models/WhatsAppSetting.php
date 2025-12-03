<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSetting extends Model
{
    protected $fillable = [
        'waha_api_url',
        'waha_api_token',
        'waha_session',
        'waha_group_id',
        'waha_enabled',
    ];

    protected $casts = [
        'waha_enabled' => 'boolean',
    ];
}
