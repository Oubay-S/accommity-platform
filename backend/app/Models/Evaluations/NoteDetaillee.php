<?php

namespace App\Models\Evaluations;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NoteDetaillee extends Model
{
    use HasUuids;

    protected $table = 'note_detaillees';
    protected $primaryKey = 'id_note';
    public $timestamps = false;

    protected $fillable = [
        'id_evaluation', 'proprete', 'communication', 'emplacement',
        'rapport_qualite_prix', 'exactitude',
    ];
}
