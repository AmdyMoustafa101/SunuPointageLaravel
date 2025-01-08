<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cohorte extends Model
{
    use HasFactory;

    protected $table = 'cohortes';

    // Champs modifiables
    protected $fillable = [
        'nom',
        'description',
        'horaires',
        'annee',
        'status'
    ];

    // Cast de la colonne horaires en tableau JSON
    protected $casts = [
        'horaires' => 'array',
    ];
}
