<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TicketLitige extends Model
{
    use HasUuids;

    protected $table = 'ticket_litiges';
    protected $primaryKey = 'id_ticket';

    const CREATED_AT = 'date_creation';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_evaluation', 'id_declarant', 'id_admin', 'id_reservation',
        'motif', 'statut', 'date_cloture',
    ];
}
