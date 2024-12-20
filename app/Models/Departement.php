<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Departement extends Model
{
    //
    use HasFactory;

    protected $table = 'departements';
    // Champs remplissables
    protected $fillable = [
        'nom',
        'description',
        'horaires' // Stocké sous format JSON
    ];

    // Mutateur pour convertir horaires en tableau lors de la récupération
    protected $casts = [
        'horaires' => 'array',
    ];
}
