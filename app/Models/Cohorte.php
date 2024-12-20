<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cohorte extends Model
{
    use HasFactory;

    protected $table = 'cohortes';

    // Champs modifiables
    protected $fillable = [
        'nom',
        'description',
        'horaires',
        'annee'
    ];

    // Cast de la colonne horaires en tableau JSON
    protected $casts = [
        'horaires' => 'array',
    ];
}
