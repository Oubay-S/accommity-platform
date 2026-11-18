<?php

namespace App\Models\Administration;

use App\Models\Evaluations\Evaluation;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Evaluation|null $evaluation
 * @property-read Reservation|null $reservation
 * @property-read User|null $declarant
 * @property-read Administrateur|null $admin
 */
class TicketLitige extends Model
{
    use HasUuids;

    public const STATUT_EN_COURS = 'en_cours';

    public const STATUT_CLOTURE = 'cloture';

    protected $table = 'ticket_litiges';

    protected $primaryKey = 'id_ticket';

    const CREATED_AT = 'date_creation';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_evaluation', 'id_declarant', 'id_admin', 'id_reservation',
        'motif', 'statut', 'date_cloture',
    ];

    protected function casts(): array
    {
        return [
            'date_creation' => 'datetime',
            'date_cloture' => 'datetime',
        ];
    }

    /** @return BelongsTo<Evaluation, $this> */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'id_evaluation');
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    /** @return BelongsTo<User, $this> */
    public function declarant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_declarant');
    }

    /** @return BelongsTo<Administrateur, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'id_admin');
    }
}
