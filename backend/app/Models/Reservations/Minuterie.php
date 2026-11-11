<?php

namespace App\Models\Reservations;

use Illuminate\Database\Eloquent\Model;

class Minuterie extends Model
{
    protected $fillable = ['reservation_id', 'expire_a', 'est_traitee'];
}
