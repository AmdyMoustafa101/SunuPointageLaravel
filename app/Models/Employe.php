<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Employe extends Authenticatable
{
    use HasFactory;

    protected $table = 'employes';

    protected $fillable = [
        'nom',
        'prenom',
        'photo',
        'adresse',
        'telephone',
        'cardID',
        'role',
        'fonction',
        'matricule',
        'departement_id',
        'email',
        'password',
        'archived',
    ];

    // Relation avec le modèle Département
    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    // Mutateur pour le hashage du mot de passe
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }
}
