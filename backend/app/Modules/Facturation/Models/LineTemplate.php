<?php

namespace App\Modules\Facturation\Models;

use Illuminate\Database\Eloquent\Model;

/** Ligne type proposée à la saisie (bloc A débours ou B prestations). */
class LineTemplate extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];
}