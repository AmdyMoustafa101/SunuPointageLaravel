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
        'status',
        'archive'  // Enlever => 'boolean'
    ];

    // Cast des colonnes
    protected $casts = [
        'horaires' => 'array',
        'archive' => 'boolean'  // Déplacer le cast ici
    ];

    public function apprenants()
    {
        return $this->hasMany(Apprenant::class);
    }
}