<?php

namespace App\Models\Notifications;

use App\Enums\TypeNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Utilisateurs\User;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notifications';
    protected $primaryKey = 'id_notification';

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = null;

    protected $fillable = ['id_utilisateur', 'titre', 'contenu', 'type_alerte', 'est_lue'];

    protected $casts = [
        'type_alerte' => TypeNotification::class,
        'est_lue' => 'boolean',
        'date_creation' => 'datetime',
    ];

    public function destinataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_utilisateur');
    }

    public function scopeNonLues(Builder $query): Builder
    {
        return $query->where('est_lue', false);
    }

    public function marquerCommeLue(): bool
    {
        if ($this->est_lue) {
            return true;
        }

        return $this->forceFill(['est_lue' => true])->save();
    }
}
