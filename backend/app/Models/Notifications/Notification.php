<?php

namespace App\Models\Notifications;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    protected $table = 'notifications';
    protected $primaryKey = 'id_notification';

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = null;

    protected $fillable = ['id_utilisateur', 'titre', 'contenu', 'type_alerte', 'est_lue'];

    protected $casts = [
        'est_lue' => 'boolean',
    ];
}
