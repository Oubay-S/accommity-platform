<?php

namespace App\Models\Utilisateurs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationIdentite extends Model
{
    use HasFactory, HasUuids;

    public const STATUT_EN_COURS = 'en_cours';

    public const STATUT_VALIDEE = 'validee';

    public const STATUT_REJETEE = 'rejetee';

    protected $table = 'verification_identites';
    protected $primaryKey = 'id_verification';

    const CREATED_AT = 'date_soumission';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_utilisateur', 'type_piece', 'statut', 'motif_rejet',
        'chemin_document', 'id_admin_traitant', 'date_traitement',
    ];

    /** Le document d'identité n'est accessible qu'à l'administrateur (loi 09-08). */
    protected $hidden = ['chemin_document'];

    protected $casts = [
        'date_traitement' => 'datetime',
        'date_soumission' => 'datetime',
    ];

    public function estValidee(): bool
    {
        return $this->statut === self::STATUT_VALIDEE;
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_utilisateur');
    }

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_COURS);
    }

    public function scopeValidees(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_VALIDEE);
    }

    public function scopeRejetees(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_REJETEE);
    }
}
