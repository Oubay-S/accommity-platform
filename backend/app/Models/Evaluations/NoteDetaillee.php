<?php

namespace App\Models\Evaluations;

use Illuminate\Database\Eloquent\Model;

class NoteDetaillee extends Model
{
    protected $fillable = ['evaluation_id', 'critere', 'note'];
}
