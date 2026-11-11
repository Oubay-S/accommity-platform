<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'type', 'titre', 'message', 'data', 'est_lue'];

    protected $casts = [
        'data' => 'array',
        'est_lue' => 'boolean'
    ];
}
