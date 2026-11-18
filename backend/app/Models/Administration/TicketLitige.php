<?php

namespace App\Models\Administration;

use App\Models\Evaluations\Evaluation;
use App\Models\Reservations\Reservation;
use App\Models\Utilisateurs\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class, 'id_evaluation');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'id_reservation');
    }

    public function declarant()
    {
        return $this->belongsTo(User::class, 'id_declarant');
    }

    public function admin()
    {
        return $this->belongsTo(Administrateur::class, 'id_admin');
    }
}
