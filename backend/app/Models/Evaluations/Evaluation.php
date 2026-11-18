<?php

namespace App\Models\Evaluations;

use App\Models\Annonces\Annonce;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read User|null $auteur
 * @property-read User|null $cible
 * @property-read Annonce|null $annonce
 * @property-read NoteDetaillee|null $notesDetaillees
 * @property-read Reservation|null $reservation
 */
class Evaluation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'evaluations';

    protected $primaryKey = 'id_evaluation';

    const CREATED_AT = 'date_creation';

    const UPDATED_AT = 'date_modification';

    protected $fillable = [
        'id_reservation', 'id_auteur', 'id_cible', 'id_annonce',
        'type_auteur', 'note', 'commentaire', 'est_signale',
        'motif_signalement',
    ];

    protected $casts = [
        'note' => 'decimal:2',
        'est_signale' => 'boolean',
        'date_creation' => 'datetime',
        'date_modification' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_auteur');
    }

    public function cible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_cible');
    }

    public function annonce(): BelongsTo
    {
        return $this->belongsTo(Annonce::class, 'id_annonce');
    }

    public function notesDetaillees(): HasOne
    {
        return $this->hasOne(NoteDetaillee::class, 'id_evaluation');
    }
}
